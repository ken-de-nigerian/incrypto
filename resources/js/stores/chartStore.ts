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

// Broadcast channel for cross-tab synchronization
let broadcastChannel: BroadcastChannel | null = null
if (typeof window !== 'undefined' && 'BroadcastChannel' in window) {
    broadcastChannel = new BroadcastChannel('forex-chart-sync')
}

export const useChartStore = defineStore('chart', () => {
    const selectedPair = ref<string>('EUR/USD')
    const pairDataMap = ref<Record<string, PairData>>({})
    const zoom = ref(1.0)
    const panX = ref(0)
    const openTrades = ref<OpenTrade[]>([])
    const chartError = ref<string | null>(null)
    const isProcessingBroadcast = ref(false)

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

    // Helper function to ensure candles are sorted by time
    function sortCandles(candles: Candle[]): Candle[] {
        return candles.sort((a, b) => a.time - b.time)
    }

    // Helper function to deduplicate candles by time
    function deduplicateCandles(candles: Candle[]): Candle[] {
        const seen = new Set<number>()
        return candles.filter(candle => {
            if (seen.has(candle.time)) {
                return false
            }
            seen.add(candle.time)
            return true
        })
    }

    // Broadcast state changes to other tabs
    function broadcastStateChange(type: string, payload: any) {
        if (broadcastChannel && !isProcessingBroadcast.value) {
            try {
                // Create a serializable copy of the payload
                const serializablePayload = JSON.parse(JSON.stringify(payload))
                broadcastChannel.postMessage({ type, payload: serializablePayload, timestamp: Date.now() })
            } catch (error) {
                console.warn('Failed to broadcast state change:', error)
                // Continue execution even if broadcast fails
            }
        }
    }

    // Setup broadcast channel listener
    if (broadcastChannel) {
        broadcastChannel.onmessage = (event) => {
            isProcessingBroadcast.value = true
            try {
                const { type, payload } = event.data

                switch (type) {
                    case 'PAIR_DATA_UPDATE':
                        if (pairDataMap.value[payload.pair]) {
                            Object.assign(pairDataMap.value[payload.pair], payload.data)
                        }
                        break
                    case 'CANDLES_INITIALIZED':
                        // Only update metadata, not the full candles array
                        // Each tab will fetch its own candles from the backend
                        if (pairDataMap.value[payload.pair]) {
                            Object.assign(pairDataMap.value[payload.pair], {
                                currentPrice: payload.data.currentPrice,
                                lastCandleTime: payload.data.lastCandleTime,
                                basePrice: payload.data.basePrice,
                                candleInterval: payload.data.candleInterval,
                                initialized: payload.data.initialized
                            })
                        }
                        break
                    case 'CANDLE_ADDED':
                        if (pairDataMap.value[payload.pair]?.initialized) {
                            const pairData = pairDataMap.value[payload.pair]
                            // Check if candle already exists
                            const exists = pairData.candles.some(c => c.time === payload.candle.time)
                            if (!exists) {
                                pairData.candles.push(payload.candle)
                                // Sort to maintain order
                                pairData.candles = sortCandles(pairData.candles)
                                if (pairData.candles.length > MAX_CANDLES) {
                                    pairData.candles.shift()
                                }
                                pairData.lastCandleTime = Math.max(
                                    pairData.lastCandleTime,
                                    payload.candle.time
                                )
                            }
                        }
                        break
                    case 'LAST_CANDLE_UPDATE':
                        if (pairDataMap.value[payload.pair]?.initialized) {
                            const pairData = pairDataMap.value[payload.pair]
                            if (pairData.candles.length > 0) {
                                Object.assign(pairData.candles[pairData.candles.length - 1], payload.updates)
                            }
                        }
                        break
                    case 'CURRENT_PRICE_UPDATE':
                        if (pairDataMap.value[payload.pair]) {
                            pairDataMap.value[payload.pair].currentPrice = payload.price
                        }
                        break
                    case 'OPEN_TRADES_UPDATE':
                        openTrades.value = payload.trades
                        break
                    case 'SELECTED_PAIR_CHANGE':
                        selectedPair.value = payload.pair
                        break
                }
            } finally {
                isProcessingBroadcast.value = false
            }
        }
    }

    function setPair(pair: string) {
        selectedPair.value = pair
        chartError.value = null
        try {
            broadcastStateChange('SELECTED_PAIR_CHANGE', { pair })
        } catch (error) {
            console.warn('Failed to broadcast pair change:', error)
        }
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
            60000, // 1 min
            300000, // 5 min
            900000, // 15 min
            1800000, // 30 min
            3600000, // 1 hour
            7200000, // 2 hours
            14400000, // 4 hours
            86400000, // 1 day
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
        if (!pairDataMap.value[pair]) {
            initializePairData(pair, currentPrice)
        }
        const pairData = pairDataMap.value[pair]
        const hasOpenTradesForPair = openTrades.value.some(t => t.pair === pair)
        if (hasOpenTradesForPair && pairData.initialized) {
            const storedLastTime = pairData.lastCandleTime
            let appendIndex = -1
            for (let i = 0; i < forexData.prices.length; i++) {
                const timestamp = forexData.prices[i][0]
                const backendTime = timestamp > 9999999999 ? timestamp : timestamp * 1000
                if (backendTime > storedLastTime) {
                    appendIndex = i
                    break
                }
            }
            if (appendIndex >= 0) {
                for (let i = appendIndex; i < forexData.prices.length; i++) {
                    const candleData = forexData.prices[i]
                    if (!Array.isArray(candleData) || candleData.length < 5) {
                        continue
                    }
                    const [timestamp, open, high, low, close] = candleData
                    const volume = forexData.volumes?.[i]?.[1] || 0
                    const candleTime = timestamp > 9999999999 ? timestamp : timestamp * 1000
                    if (!isFinite(open) || !isFinite(high) || !isFinite(low) || !isFinite(close) ||
                        open <= 0 || high <= 0 || low <= 0 || close <= 0) {
                        continue
                    }
                    const newCandle: Candle = {
                        time: candleTime,
                        open,
                        high,
                        low,
                        close,
                        volume
                    }
                    pairData.candles.push(newCandle)
                    if (pairData.candles.length > MAX_CANDLES) {
                        pairData.candles.shift()
                    }
                    pairData.lastCandleTime = candleTime
                    // Broadcast only primitive values
                    broadcastStateChange('CANDLE_ADDED', {
                        pair,
                        candle: {
                            time: candleTime,
                            open: Number(open),
                            high: Number(high),
                            low: Number(low),
                            close: Number(close),
                            volume: Number(volume)
                        }
                    })
                }

                if (pairData.candles.length > 0) {
                    pairData.currentPrice = pairData.candles[pairData.candles.length - 1].close
                    broadcastStateChange('CURRENT_PRICE_UPDATE', {
                        pair: String(pair),
                        price: Number(pairData.currentPrice)
                    })
                }
            }
            return
        }

        pairData.candles = []
        if (!forexData.prices || !Array.isArray(forexData.prices)) {
            setChartError('Invalid chart data received. Please refresh the page.')
            pairData.initialized = false
            return
        }
        const ohlcData = forexData.prices
        const volumeData = forexData.volumes || []
        if (ohlcData.length === 0) {
            setChartError('No historical data available. Please try again.')
            pairData.initialized = false
            return
        }
        ohlcData.forEach((candleData, index) => {
            if (!Array.isArray(candleData) || candleData.length < 5) {
                return
            }
            const [timestamp, open, high, low, close] = candleData
            const volume = volumeData[index]?.[1] || 0
            const candleTime = timestamp > 9999999999 ? timestamp : timestamp * 1000
            if (!isFinite(open) || !isFinite(high) ||
                !isFinite(low) || !isFinite(close)) {
                return
            }
            if (open <= 0 || high <= 0 || low <= 0 || close <= 0) {
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
            setChartError('Failed to process chart data. Please refresh the page.')
            pairData.initialized = false
            return
        }

        // Sort and deduplicate candles to ensure data integrity
        pairData.candles = sortCandles(deduplicateCandles(pairData.candles))

        pairData.candleInterval = detectCandleInterval(pairData.candles)
        const lastCandle = pairData.candles[pairData.candles.length - 1]
        pairData.currentPrice = currentPrice > 0 ? currentPrice : (lastCandle?.close || 0)
        pairData.basePrice = pairData.candles[0]?.open || pairData.currentPrice
        if (lastCandle) {
            pairData.lastCandleTime = lastCandle.time
        }
        pairData.initialized = true
        clearChartError()

        // Broadcast complete pair data to sync all tabs
        // Only send primitive values to avoid serialization issues
        broadcastStateChange('CANDLES_INITIALIZED', {
            pair: String(pair),
            data: {
                currentPrice: Number(pairData.currentPrice),
                lastCandleTime: Number(pairData.lastCandleTime),
                basePrice: Number(pairData.basePrice),
                candleInterval: Number(pairData.candleInterval),
                initialized: Boolean(pairData.initialized),
                candleCount: Number(pairData.candles.length)
            }
        })
    }

    function initializeCandlesFromOHLC(pair: string, ohlcData: any) {
        try {
            if (!pairDataMap.value[pair]) {
                initializePairData(pair)
            }
            const pairData = pairDataMap.value[pair]
            const currentPrice = parseFloat(ohlcData.price) || 0
            if (!currentPrice || currentPrice <= 0) {
                setChartError('Unable to fetch current price. Please check your connection.')
                pairData.initialized = false
                return
            }
            initializeCandlesFromForexData(pair, ohlcData, currentPrice)
        } catch (error) {
            console.error('Error initializing candles from OHLC:', error)
            setChartError('Failed to initialize chart data. Please refresh the page.')
        }
    }

    function updatePairData(pair: string, updates: Partial<PairData>) {
        if (pairDataMap.value[pair]) {
            Object.assign(pairDataMap.value[pair], updates)
            try {
                broadcastStateChange('PAIR_DATA_UPDATE', { pair, data: updates })
            } catch (error) {
                console.warn('Failed to broadcast pair data update:', error)
            }
        }
    }

    function addCandle(pair: string, candle: Candle) {
        const pairData = pairDataMap.value[pair]
        if (pairData && pairData.initialized) {
            // Check if candle already exists
            const exists = pairData.candles.some(c => c.time === candle.time)
            if (!exists) {
                pairData.candles.push(candle)
                // Sort to maintain order
                pairData.candles = sortCandles(pairData.candles)
                if (pairData.candles.length > MAX_CANDLES) {
                    pairData.candles.shift()
                }
                pairData.lastCandleTime = Math.max(pairData.lastCandleTime, candle.time)
                try {
                    broadcastStateChange('CANDLE_ADDED', { pair, candle })
                } catch (error) {
                    console.warn('Failed to broadcast candle added:', error)
                }
            }
        }
    }

    function updateLastCandle(pair: string, updates: Partial<Candle>) {
        const pairData = pairDataMap.value[pair]
        if (pairData && pairData.initialized && pairData.candles.length > 0) {
            const lastCandle = pairData.candles[pairData.candles.length - 1]
            if (lastCandle) {
                Object.assign(lastCandle, updates)
                try {
                    broadcastStateChange('LAST_CANDLE_UPDATE', { pair, updates })
                } catch (error) {
                    console.warn('Failed to broadcast last candle update:', error)
                }
            }
        }
    }

    function updateCurrentPrice(pair: string, price: number) {
        if (pairDataMap.value[pair]) {
            pairDataMap.value[pair].currentPrice = price
            try {
                broadcastStateChange('CURRENT_PRICE_UPDATE', { pair, price })
            } catch (error) {
                console.warn('Failed to broadcast current price update:', error)
            }
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
        try {
            broadcastStateChange('OPEN_TRADES_UPDATE', { trades })
        } catch (error) {
            console.warn('Failed to broadcast open trades update:', error)
        }
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
            // Check if candles are in ascending order
            if (data.candles && data.candles.length > 1) {
                for (let i = 1; i < data.candles.length; i++) {
                    if (data.candles[i].time <= data.candles[i - 1].time) {
                        console.warn(`Candles out of order for ${pair}, sorting...`)
                        data.candles = sortCandles(deduplicateCandles(data.candles))
                        issuesFound = true
                        break
                    }
                }
            }
            data.candles?.forEach((candle) => {
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

    function clearCorruptedData() {
        console.warn('Clearing corrupted chart data from localStorage')
        pairDataMap.value = {}
        if (typeof localStorage !== 'undefined') {
            try {
                localStorage.removeItem('forex-chart-store')
            } catch (e) {
                console.error('Failed to clear localStorage:', e)
            }
        }
    }

    return {
        selectedPair,
        pairDataMap,
        zoom,
        panX,
        openTrades,
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
        resetView,
        validateDataIntegrity,
        calculateTradePnL,
        setChartError,
        clearChartError,
        clearCorruptedData,
    }
}, {
    persist: {
        key: 'forex-chart-store',
        storage: localStorage,
        paths: ['selectedPair', 'zoom', 'panX', 'pairDataMap']
    }
})
