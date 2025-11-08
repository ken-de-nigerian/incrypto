interface Trade {
    s: string // symbol
    p: number // price
    v: number // volume
    t: number // timestamp
}

interface BroadcastMessage {
    type: 'trade' | 'status' | 'leadership' | 'ping' | 'pong'
    data?: any
    tabId?: string
}

export class FinnhubWebSocketService {
    private ws: WebSocket | null = null
    private readonly apiKey: string
    private subscribedPairs: Set<string> = new Set()
    private reconnectTimeout: ReturnType<typeof setTimeout> | null = null
    private reconnectAttempts: number = 0
    private maxReconnectAttempts: number = 10
    private baseReconnectDelay: number = 1000
    private maxReconnectDelay: number = 30000
    private onTradeCallback: ((trade: any) => void) | null = null
    private onStatusChangeCallback: ((status: string) => void) | null = null
    private isIntentionalDisconnect: boolean = false

    // Tab coordination
    private broadcastChannel: BroadcastChannel | null = null
    private readonly tabId: string
    private isLeader: boolean = false
    private leaderCheckInterval: ReturnType<typeof setInterval> | null = null
    private lastLeaderPing: number = Date.now()
    private readonly LEADER_TIMEOUT = 5000

    // Health monitoring
    private healthCheckInterval: ReturnType<typeof setInterval> | null = null
    private lastPingTime: number = 0
    private lastPongTime: number = 0
    private readonly HEALTH_CHECK_INTERVAL = 30000
    private readonly PING_TIMEOUT = 10000

    // Fallback polling
    private pollingInterval: ReturnType<typeof setInterval> | null = null
    private readonly POLLING_INTERVAL = 10000
    private isFallbackMode: boolean = false

    constructor(apiKey: string) {
        this.apiKey = apiKey
        this.tabId = `tab_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
        this.initializeBroadcastChannel()
        this.electLeader()
    }

    /**
     * Initialize BroadcastChannel for tab coordination
     */
    private initializeBroadcastChannel() {
        try {
            this.broadcastChannel = new BroadcastChannel('finnhub_websocket')

            this.broadcastChannel.onmessage = (event: MessageEvent<BroadcastMessage>) => {
                const message = event.data

                switch (message.type) {
                    case 'trade':
                        // Follower tabs receive trade updates from leader
                        if (!this.isLeader && this.onTradeCallback) {
                            this.onTradeCallback(message.data)
                        }
                        break

                    case 'status':
                        // All tabs sync connection status
                        if (!this.isLeader) {
                            this.updateStatus(message.data)
                        }
                        break

                    case 'leadership':
                        // Handle leadership changes
                        if (message.data.tabId !== this.tabId) {
                            this.lastLeaderPing = Date.now()
                            if (this.isLeader) {
                                this.isLeader = false
                                this.disconnect()
                            }
                        }
                        break

                    case 'ping':
                        // Respond to leadership ping
                        if (this.isLeader) {
                            this.broadcastMessage({
                                type: 'pong',
                                tabId: this.tabId
                            })
                        }
                        break
                }
            }
        } catch (error) {
            console.error('BroadcastChannel not supported:', error)
            // Fallback: each tab manages its own connection
            this.isLeader = true
        }
    }

    /**
     * Elect leader tab using a simple ping-based protocol
     */
    private electLeader() {
        // Check if there's already a leader
        this.broadcastMessage({ type: 'ping' })

        // Wait for responses
        setTimeout(() => {
            if (Date.now() - this.lastLeaderPing > 1000) {
                // No leader found, become leader
                this.becomeLeader()
            }
        }, 1000)

        // Monitor leader health
        this.leaderCheckInterval = setInterval(() => {
            if (!this.isLeader) {
                // Check if leader is still alive
                if (Date.now() - this.lastLeaderPing > this.LEADER_TIMEOUT) {
                    this.becomeLeader()
                }
            } else {
                // Announce leadership
                this.broadcastMessage({
                    type: 'leadership',
                    data: { tabId: this.tabId }
                })
            }
        }, 2000)
    }

    /**
     * Become the leader tab
     */
    private becomeLeader() {
        if (this.isLeader) return

        console.log(`Tab ${this.tabId} becoming leader`)
        this.isLeader = true
        this.connect()

        this.broadcastMessage({
            type: 'leadership',
            data: { tabId: this.tabId }
        })
    }

    /**
     * Broadcast message to all tabs
     */
    private broadcastMessage(message: BroadcastMessage) {
        try {
            this.broadcastChannel?.postMessage(message)
        } catch (error) {
            console.error('Failed to broadcast message:', error)
        }
    }

    /**
     * Connect to Finnhub WebSocket (only leader tab)
     */
    connect() {
        if (!this.isLeader) {
            console.log('Not leader, skipping WebSocket connection')
            return
        }

        if (!this.apiKey) {
            console.error('No API key provided')
            return
        }

        if (this.ws?.readyState === WebSocket.OPEN) {
            return
        }

        this.isIntentionalDisconnect = false
        this.updateStatus('connecting')

        try {
            this.ws = new WebSocket(`wss://ws.finnhub.io?token=${this.apiKey}`)

            this.ws.onopen = () => {
                console.log('WebSocket connected')
                this.updateStatus('connected')
                this.reconnectAttempts = 0
                this.isFallbackMode = false
                this.stopPolling()

                // Resubscribe to all pairs
                this.subscribedPairs.forEach(pair => {
                    this.subscribeToPair(pair, false)
                })

                // Start health checks
                this.startHealthCheck()
            }

            this.ws.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data)

                    // ENHANCED: Log all messages for debugging
                    console.log('📨 Finnhub raw message:', message)

                    if (message.type === 'trade' && message.data) {
                        console.log(`✅ Received ${message.data.length} trade(s)`)
                        message.data.forEach((trade: Trade) => {
                            console.log('📊 Processing trade:', {
                                symbol: trade.s,
                                price: trade.p,
                                volume: trade.v,
                                timestamp: new Date(trade.t * 1000).toISOString()
                            })

                            // Process locally
                            if (this.onTradeCallback) {
                                this.onTradeCallback(trade)
                            }

                            // Broadcast to other tabs
                            this.broadcastMessage({
                                type: 'trade',
                                data: trade
                            })
                        })
                    } else if (message.type === 'ping') {
                        this.lastPongTime = Date.now()
                        this.ws?.send(JSON.stringify({ type: 'pong' }))
                        console.log('🏓 Received ping, sent pong')
                    } else {
                        console.log('ℹ️ Other message type:', message.type)
                    }
                } catch (error) {
                    console.error('❌ Error parsing WebSocket message:', error)
                }
            }

            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error)
                this.updateStatus('error')
            }

            this.ws.onclose = (event) => {
                console.log('WebSocket closed:', event.code, event.reason)
                this.updateStatus('disconnected')
                this.stopHealthCheck()

                if (!this.isIntentionalDisconnect && this.reconnectAttempts < this.maxReconnectAttempts) {
                    this.scheduleReconnect()
                } else if (this.reconnectAttempts >= this.maxReconnectAttempts) {
                    // Switch to fallback polling mode
                    this.startFallbackPolling()
                }
            }
        } catch (error) {
            console.error('Error creating WebSocket:', error)
            this.updateStatus('error')
            this.startFallbackPolling()
        }
    }

    /**
     * Schedule reconnection with exponential backoff
     */
    private scheduleReconnect() {
        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout)
        }

        this.reconnectAttempts++
        const delay = Math.min(
            this.baseReconnectDelay * Math.pow(2, this.reconnectAttempts - 1),
            this.maxReconnectDelay
        )

        console.log(`Scheduling reconnect attempt ${this.reconnectAttempts} in ${delay}ms`)

        this.reconnectTimeout = setTimeout(() => {
            this.connect()
        }, delay)
    }

    /**
     * Start health check ping/pong
     */
    private startHealthCheck() {
        this.stopHealthCheck()

        this.healthCheckInterval = setInterval(() => {
            if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
                return
            }

            // Check if last pong was received
            if (this.lastPingTime > 0 && this.lastPongTime < this.lastPingTime) {
                const timeSinceLastPing = Date.now() - this.lastPingTime
                if (timeSinceLastPing > this.PING_TIMEOUT) {
                    console.warn('Health check failed, reconnecting')
                    this.ws.close()
                    return
                }
            }

            // Send ping
            this.lastPingTime = Date.now()
            try {
                this.ws.send(JSON.stringify({ type: 'ping' }))
            } catch (error) {
                console.error('Failed to send ping:', error)
            }
        }, this.HEALTH_CHECK_INTERVAL)
    }

    /**
     * Stop health check
     */
    private stopHealthCheck() {
        if (this.healthCheckInterval) {
            clearInterval(this.healthCheckInterval)
            this.healthCheckInterval = null
        }
    }

    /**
     * Start fallback polling when WebSocket fails
     */
    private startFallbackPolling() {
        if (this.isFallbackMode) return

        console.warn('WebSocket failed, switching to fallback polling mode')
        this.isFallbackMode = true
        this.updateStatus('fallback')

        this.pollingInterval = setInterval(async () => {
            // Poll price data for subscribed pairs
            for (const pair of this.subscribedPairs) {
                try {
                    // Use your existing API endpoint
                    const response = await fetch(`/api/forex/quote/${pair.replace('/', '-')}`)
                    if (response.ok) {
                        const data = await response.json()

                        // Simulate trade message
                        const trade = {
                            s: `OANDA:${pair.replace('/', '_')}`,
                            p: parseFloat(data.price),
                            v: 0,
                            t: Math.floor(Date.now() / 1000)
                        }

                        if (this.onTradeCallback) {
                            this.onTradeCallback(trade)
                        }

                        this.broadcastMessage({
                            type: 'trade',
                            data: trade
                        })
                    }
                } catch (error) {
                    console.error(`Failed to poll price for ${pair}:`, error)
                }
            }
        }, this.POLLING_INTERVAL)
    }

    /**
     * Stop fallback polling
     */
    private stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval)
            this.pollingInterval = null
            this.isFallbackMode = false
        }
    }

    /**
     * Disconnect WebSocket
     */
    disconnect() {
        this.isIntentionalDisconnect = true

        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout)
            this.reconnectTimeout = null
        }

        this.stopHealthCheck()
        this.stopPolling()

        if (this.ws) {
            this.subscribedPairs.forEach(pair => {
                this.unsubscribeFromPair(pair)
            })

            this.ws.close(1000, 'Intentional disconnect')
            this.ws = null
        }

        this.updateStatus('disconnected')
    }

    /**
     * Subscribe to a currency pair
     */
    subscribeToPair(pair: string, trackSubscription: boolean = true) {
        if (trackSubscription) {
            this.subscribedPairs.add(pair)
        }

        if (!this.isLeader) {
            return true
        }

        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
            return false
        }

        const finnhubSymbol = this.convertPairToFinnhub(pair)
        const subscribeMessage = { type: 'subscribe', symbol: finnhubSymbol }

        try {
            this.ws.send(JSON.stringify(subscribeMessage))
            console.log(`Subscribed to ${finnhubSymbol}`)
            return true
        } catch (error) {
            console.error(`Failed to subscribe to ${finnhubSymbol}:`, error)
            return false
        }
    }

    /**
     * Unsubscribe from a currency pair
     */
    unsubscribeFromPair(pair: string) {
        this.subscribedPairs.delete(pair)

        if (!this.isLeader || !this.ws || this.ws.readyState !== WebSocket.OPEN) {
            return
        }

        const finnhubSymbol = this.convertPairToFinnhub(pair)
        const unsubscribeMessage = { type: 'unsubscribe', symbol: finnhubSymbol }

        try {
            this.ws.send(JSON.stringify(unsubscribeMessage))
            console.log(`Unsubscribed from ${finnhubSymbol}`)
        } catch (error) {
            console.error(`Failed to unsubscribe from ${finnhubSymbol}:`, error)
        }
    }

    /**
     * Switch currency pair
     */
    switchPair(oldPair: string, newPair: string) {
        if (oldPair && oldPair !== newPair) {
            this.unsubscribeFromPair(oldPair)
        }
        this.subscribeToPair(newPair)
    }

    /**
     * Register trade callback
     */
    onTrade(callback: (trade: any) => void) {
        this.onTradeCallback = callback
    }

    /**
     * Register status change callback
     */
    onStatusChange(callback: (status: string) => void) {
        this.onStatusChangeCallback = callback
    }

    /**
     * Convert pair format to Finnhub symbol
     */
    private convertPairToFinnhub(pair: string): string {
        return `OANDA:${pair.replace('/', '_')}`
    }

    /**
     * Update connection status
     */
    private updateStatus(status: string) {
        if (this.onStatusChangeCallback) {
            this.onStatusChangeCallback(status)
        }

        // Broadcast status to other tabs
        if (this.isLeader) {
            this.broadcastMessage({
                type: 'status',
                data: status
            })
        }
    }

    /**
     * Check if connected
     */
    isConnected(): boolean {
        return this.ws?.readyState === WebSocket.OPEN || this.isFallbackMode
    }

    /**
     * Cleanup on destroy
     */
    destroy() {
        this.disconnect()

        if (this.leaderCheckInterval) {
            clearInterval(this.leaderCheckInterval)
        }

        if (this.broadcastChannel) {
            this.broadcastChannel.close()
        }
    }
}

// Singleton management
let finnhubService: FinnhubWebSocketService | null = null

export function initializeFinnhubService(apiKey: string): FinnhubWebSocketService {
    if (!finnhubService) {
        finnhubService = new FinnhubWebSocketService(apiKey)
    }
    return finnhubService
}

export function getFinnhubService(): FinnhubWebSocketService | null {
    return finnhubService
}

export function resetFinnhubService() {
    if (finnhubService) {
        finnhubService.destroy()
        finnhubService = null
    }
}
