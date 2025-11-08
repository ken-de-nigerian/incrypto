import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface Candle {
    time: number
    open: number
    high: number
    low: number
    close: number
    volume: number
}

interface PairData {
    candles: Candle[]
    currentPrice: number
    lastCandleTime: number
    basePrice: number
    candleInterval: number
    initialized: boolean
}

interface ForexOHLCData {
    prices: number[][]
    volumes: number[][]
    ohlc: boolean
    provider: string
    success: boolean
}

export interface OpenTrade {
    id: number
    pair: string
    type: 'Up' | 'Down'
    amount: number
    leverage: number
    entry_price: number
    opened_at: string
    duration?: string
    expiry_time?: string
    trading_mode: 'demo' | 'live'
    is_demo_forced_win: boolean
    pnl: number
    pnlPct: string
}

export interface TradeWithPnL extends OpenTrade {
    pnl: number
    pnlPct: string
}

const MAX_CANDLES = 100
const MIN_ZOOM = 0.1
const MAX_ZOOM = 10
const MAX_PAN_THRESHOLD = 100000
const DEFAULT_CANDLE_INTERVAL_MS = 60000

export const useChartStore = defineStore('chart', () => {
    const selectedPair = ref<string>('EUR/USD')
    const pairDataMap = ref<Record<string, PairData>>({})
    const zoom = ref(1.0)
    const panX = ref(0)
    const openTrades = ref<OpenTrade[]>([])
    const connectionStatus = ref<'disconnected' | 'connecting' | 'connected' | 'error' | 'fallback'>('disconnected')
    const chartError = ref<string | null>(null)

    // OPTIMIZED: Memoized computed properties
    const isWebSocketConnected = computed(() =>
        connectionStatus.value === 'connected' || connectionStatus.value === 'fallback'
    )

    const currentPairData = computed(() => pairDataMap.value[selectedPair.value])

    const hasPairData = computed(() =>
        !!currentPairData.value &&
        currentPairData.value.initialized &&
        currentPairData.value.candles.length > 0
    )

    const currentPrice = computed(() => currentPairData.value?.currentPrice || 0)

    const openTradesForPair = computed(() =>
        openTrades.value.filter(t => t.pair === selectedPair.value)
    )

    const hasChartError = computed(() => !!chartError.value)

    function setPair(pair: string) {
        selectedPair.value = pair
        chartError.value = null
        if (!pairDataMap.value[pair]) {
            initializePairData(pair)
        }
    }

    function initializePairData(pair: string, initialPrice: number = 0) {
        if (!pairDataMap.value[pair]) {
            pairDataMap.value[pair] = {
                candles: [],
                currentPrice: initialPrice,
                lastCandleTime: Date.now(),
                basePrice: initialPrice,
                candleInterval: DEFAULT_CANDLE_INTERVAL_MS,
                initialized: false,
            }
        }
    }

    function setChartError(error: string) {
        chartError.value = error
        console.error('Chart Error:', error)
    }

    function clearChartError() {
        chartError.value = null
    }

    function detectCandleInterval(candles: Candle[]): number {
        if (candles.length < 2) return DEFAULT_CANDLE_INTERVAL_MS

        const intervals: number[] = []
        for (let i = 1; i < Math.min(10, candles.length); i++) {
            intervals.push(candles[i].time - candles[i - 1].time)
        }

        if (intervals.length === 0) return DEFAULT_CANDLE_INTERVAL_MS

        const avgInterval = intervals.reduce((a, b) => a + b, 0) / intervals.length

        const standardIntervals = [
            60000,      // 1 min
            300000,     // 5 min
            900000,     // 15 min
            1800000,    // 30 min
            3600000,    // 1 hour
            7200000,    // 2 hours
            14400000,   // 4 hours
            86400000,   // 1 day
        ]

        let closestInterval = standardIntervals[0]
        let minDiff = Math.abs(avgInterval - closestInterval)

        for (const interval of standardIntervals) {
            const diff = Math.abs(avgInterval - interval)
            if (diff < minDiff) {
                minDiff = diff
                closestInterval = interval
            }
        }

        return closestInterval
    }

    function initializeCandlesFromForexData(pair: string, forexData: ForexOHLCData, currentPrice: number = 0) {
        console.log('Initializing candles from Forex data', { pair, currentPrice })

        if (!pairDataMap.value[pair]) {
            initializePairData(pair, currentPrice)
        }

        const pairData = pairDataMap.value[pair]
        pairData.candles = []

        if (!forexData.prices || !Array.isArray(forexData.prices)) {
            console.error('Invalid forex data structure', forexData)
            setChartError('Invalid chart data received. Please refresh the page.')
            pairData.initialized = false
            return
        }

        const ohlcData = forexData.prices
        const volumeData = forexData.volumes || []

        if (ohlcData.length === 0) {
            console.error('Empty OHLC data received')
            setChartError('No historical data available. Please try again.')
            pairData.initialized = false
            return
        }

        console.log(`Processing ${ohlcData.length} candles for ${pair}`)

        ohlcData.forEach((candleData, index) => {
            if (!Array.isArray(candleData) || candleData.length < 5) {
                console.warn(`Invalid candle data at index ${index}`, candleData)
                return
            }

            const [timestamp, open, high, low, close] = candleData
            const volume = volumeData[index]?.[1] || 0

            const candleTime = timestamp > 9999999999 ? timestamp : timestamp * 1000

            if (!isFinite(open) || !isFinite(high) || !isFinite(low) || !isFinite(close)) {
                console.warn(`Invalid price values at index ${index}`, { open, high, low, close })
                return
            }

            if (open <= 0 || high <= 0 || low <= 0 || close <= 0) {
                console.warn(`Non-positive price values at index ${index}`, { open, high, low, close })
                return
            }

            pairData.candles.push({
                time: candleTime,
                open: open,
                high: high,
                low: low,
                close: close,
                volume: volume
            })
        })

        if (pairData.candles.length === 0) {
            console.error('No valid candles created from OHLC data')
            setChartError('Failed to process chart data. Please refresh the page.')
            pairData.initialized = false
            return
        }

        console.log(`Successfully created ${pairData.candles.length} candles for ${pair}`)

        pairData.candleInterval = detectCandleInterval(pairData.candles)
        console.log(`Detected candle interval: ${pairData.candleInterval}ms (${pairData.candleInterval / 60000} minutes)`)

        const lastCandle = pairData.candles[pairData.candles.length - 1]
        pairData.currentPrice = currentPrice > 0 ? currentPrice : (lastCandle?.close || 0)
        pairData.basePrice = pairData.candles[0]?.open || pairData.currentPrice

        if (lastCandle) {
            pairData.lastCandleTime = lastCandle.time
        }

        pairData.initialized = true
        clearChartError()

        console.log('Chart initialized successfully:', {
            pair,
            candleCount: pairData.candles.length,
            firstCandle: new Date(pairData.candles[0].time).toISOString(),
            lastCandle: new Date(lastCandle.time).toISOString(),
            currentPrice: pairData.currentPrice,
            basePrice: pairData.basePrice,
            interval: `${pairData.candleInterval / 60000} minutes`
        })
    }

    function initializeCandlesFromOHLC(pair: string, ohlcData: any) {
        console.log('initializeCandlesFromOHLC called', { pair, ohlcData })

        if (!pairDataMap.value[pair]) {
            initializePairData(pair)
        }

        const pairData = pairDataMap.value[pair]
        const currentPrice = parseFloat(ohlcData.price) || 0

        if (!currentPrice || currentPrice <= 0) {
            console.error('Invalid current price', { currentPrice })
            setChartError('Unable to fetch current price. Please check your connection.')
            pairData.initialized = false
            return
        }

        initializeCandlesFromForexData(pair, ohlcData, currentPrice)
    }

    function updatePairData(pair: string, updates: Partial<PairData>) {
        if (pairDataMap.value[pair]) {
            Object.assign(pairDataMap.value[pair], updates)
        }
    }

    function addCandle(pair: string, candle: Candle) {
        const pairData = pairDataMap.value[pair]
        if (pairData && pairData.initialized) {
            pairData.candles.push(candle)
            if (pairData.candles.length > MAX_CANDLES) {
                pairData.candles.shift()
            }
            pairData.lastCandleTime = candle.time
            console.log('New candle added:', candle)
        }
    }

    function updateLastCandle(pair: string, updates: Partial<Candle>) {
        const pairData = pairDataMap.value[pair]
        if (pairData && pairData.initialized && pairData.candles.length > 0) {
            const lastCandle = pairData.candles[pairData.candles.length - 1]
            if (lastCandle) {
                Object.assign(lastCandle, updates)
            }
        }
    }

    function updateCurrentPrice(pair: string, price: number) {
        if (pairDataMap.value[pair]) {
            pairDataMap.value[pair].currentPrice = price
        }
    }

    function updateLastCandleTime(pair: string, time: number) {
        if (pairDataMap.value[pair]) {
            pairDataMap.value[pair].lastCandleTime = time
        }
    }

    function setZoom(val: number) {
        zoom.value = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, val))
    }

    function setPanX(val: number) {
        panX.value = Math.max(-MAX_PAN_THRESHOLD, Math.min(MAX_PAN_THRESHOLD, val))
    }

    function setOpenTrades(trades: OpenTrade[]) {
        openTrades.value = trades
    }

    /**
     * OPTIMIZED: Client-side PnL calculation with proper rounding
     */
    function getPricePrecision(pair: string): number {
        const symbol = pair.toUpperCase();
        if (symbol.includes('JPY') || symbol.includes('RUB')) {
            return 3;
        }
        if (symbol.includes('XAU') || symbol.includes('XAG')) {
            return 2;
        }
        return 5;
    }

    function roundPrice(price: number, pair: string): number {
        const precision = getPricePrecision(pair);
        const factor = Math.pow(10, precision);
        return Math.round(price * factor) / factor;
    }

    function calculateTradePnL(trade: OpenTrade, currentPrice: number): { pnl: number, pnlPct: string } {
        const leverageFactor = trade.leverage || 1
        const tradeVolume = trade.amount * leverageFactor

        const roundedPrice = roundPrice(currentPrice, trade.pair)
        const roundedEntry = roundPrice(trade.entry_price, trade.pair)

        const diff = trade.type === 'Up'
            ? roundedPrice - roundedEntry
            : roundedEntry - roundedPrice

        const pnl = diff * tradeVolume

        const initialInvestment = trade.amount
        const pnlPct = ((pnl / initialInvestment) * 100).toFixed(2)

        return { pnl, pnlPct: pnlPct + '%' }
    }

    function updateTradePnL(tradeId: number, currentPrice: number) {
        const trade = openTrades.value.find(t => t.id === tradeId)
        if (trade) {
            const { pnl, pnlPct } = calculateTradePnL(trade, currentPrice)
            trade.pnl = pnl
            trade.pnlPct = pnlPct
        }
    }

    function setWebSocketStatus(status: 'disconnected' | 'connecting' | 'connected' | 'error' | 'fallback') {
        connectionStatus.value = status

        if (status === 'error') {
            setChartError('WebSocket connection failed. Trying fallback mode...')
        } else if (status === 'fallback') {
            clearChartError()
            console.warn('Using fallback polling mode')
        } else if (status === 'connected') {
            clearChartError()
        }
    }

    function resetView() {
        zoom.value = 1.0
        panX.value = 0
    }

    function validateDataIntegrity(): boolean {
        let issuesFound = false
        Object.entries(pairDataMap.value).forEach(([pair, data]) => {
            if (!data.candles) {
                issuesFound = true
            }

            if (!isFinite(data.currentPrice) || data.currentPrice <= 0) {
                issuesFound = true
            }

            data.candles?.forEach((candle, idx) => {
                if (!isFinite(candle.time) || !isFinite(candle.open) || !isFinite(candle.high) ||
                    !isFinite(candle.low) || !isFinite(candle.close)) {
                    issuesFound = true
                }
            })
        })

        if (!isFinite(zoom.value)) {
            resetView()
            issuesFound = true
        }

        if (!isFinite(panX.value)) {
            panX.value = 0
            issuesFound = true
        }

        return !issuesFound
    }

    function convertPairToFinnhub(pair: string): string {
        return `OANDA:${pair.replace('/', '_')}`
    }

    function convertFinnhubToPair(finnhubSymbol: string): string {
        return finnhubSymbol.replace('OANDA:', '').replace('_', '/')
    }

    function processFinnhubTrade(trade: any) {
        try {
            const symbol = convertFinnhubToPair(trade.s)
            const price = parseFloat(trade.p)
            const volume = parseFloat(trade.v) || 0
            const timestamp = trade.t * 1000

            const now = Date.now()
            let validTimestamp = timestamp

            // Fix timestamp if it's invalid (too far in past or future)
            if (Math.abs(timestamp - now) > 24 * 60 * 60 * 1000) {
                console.warn('Invalid timestamp detected, using current time', {
                    original: new Date(timestamp).toISOString(),
                    corrected: new Date(now).toISOString()
                })
                validTimestamp = now
            }

            if (!pairDataMap.value[symbol] || !isFinite(price) || price <= 0) {
                console.warn('Invalid trade data:', { symbol, price, isFinite: isFinite(price) })
                return
            }

            const pairData = pairDataMap.value[symbol]

            if (!pairData.initialized || pairData.candles.length === 0) {
                console.warn('Ignoring WebSocket trade - chart not initialized yet')
                return
            }

            updateCurrentPrice(symbol, price)

            // OPTIMIZED: Update PnL for all trades of this pair
            openTrades.value.forEach(t => {
                if (t.pair === symbol) {
                    updateTradePnL(t.id, price)
                }
            })

            const lastCandle = pairData.candles[pairData.candles.length - 1]
            const candleInterval = pairData.candleInterval || DEFAULT_CANDLE_INTERVAL_MS
            const candleTime = Math.floor(validTimestamp / candleInterval) * candleInterval
            const lastCandleTime = Math.floor(lastCandle.time / candleInterval) * candleInterval

            console.log('WebSocket tick:', {
                symbol,
                price,
                currentCandleTime: new Date(candleTime).toISOString(),
                lastCandleTime: new Date(lastCandleTime).toISOString(),
                interval: `${candleInterval / 60000} minutes`,
                willCreateNewCandle: candleTime !== lastCandleTime,
                timeDiff: `${(candleTime - lastCandleTime) / 60000} minutes`
            })

            // ENHANCED: If there's a big gap, fill it with interpolated candles
            const timeDiffMinutes = (candleTime - lastCandleTime) / candleInterval

            if (timeDiffMinutes > 1 && timeDiffMinutes <= 1440) {
                // Gap detected (more than 1 minute but less than 24 hours)
                console.log(`🔧 Bridging ${timeDiffMinutes} minute gap with interpolated candles`)

                // Fill the gap with candles using the last close price
                for (let i = 1; i < timeDiffMinutes; i++) {
                    const gapCandleTime = lastCandleTime + (i * candleInterval)
                    const gapCandle: Candle = {
                        time: gapCandleTime,
                        open: lastCandle.close,
                        high: lastCandle.close,
                        low: lastCandle.close,
                        close: lastCandle.close,
                        volume: 0,
                    }
                    addCandle(symbol, gapCandle)
                }
            }

            if (candleTime === lastCandleTime) {
                // Update existing candle
                const newHigh = Math.max(lastCandle.high, price)
                const newLow = Math.min(lastCandle.low, price)
                const newVolume = lastCandle.volume + volume

                updateLastCandle(symbol, {
                    close: price,
                    high: newHigh,
                    low: newLow,
                    volume: newVolume,
                })

                console.log('Updated existing candle:', { close: price, high: newHigh, low: newLow })
            } else {
                // Create a new candle
                const prevCandle = pairData.candles[pairData.candles.length - 1]
                const newCandle: Candle = {
                    time: candleTime,
                    open: prevCandle.close,
                    high: price,
                    low: price,
                    close: price,
                    volume: volume,
                }

                addCandle(symbol, newCandle)
                updateLastCandleTime(symbol, candleTime)

                console.log('✨ Created new candle:', newCandle)
            }
        } catch (error) {
            console.error('Error processing Finnhub trade:', error)
            return
        }
    }

    return {
        selectedPair,
        pairDataMap,
        zoom,
        panX,
        openTrades,
        isWebSocketConnected,
        connectionStatus,
        currentPairData,
        hasPairData,
        currentPrice,
        openTradesForPair,
        chartError,
        hasChartError,
        setPair,
        initializePairData,
        initializeCandlesFromOHLC,
        initializeCandlesFromForexData,
        updatePairData,
        addCandle,
        updateLastCandle,
        updateCurrentPrice,
        updateLastCandleTime,
        setZoom,
        setPanX,
        setOpenTrades,
        updateTradePnL,
        setWebSocketStatus,
        resetView,
        validateDataIntegrity,
        calculateTradePnL,
        convertPairToFinnhub,
        processFinnhubTrade,
        setChartError,
        clearChartError,
    }
}, {
    persist: {
        key: 'forex-chart-store',
        storage: localStorage,
        paths: ['selectedPair', 'zoom', 'panX']
    }
})
