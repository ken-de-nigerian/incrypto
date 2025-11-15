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

    function sortCandles(candles: Candle[]): Candle[] {
        return candles.sort((a, b) => a.time - b.time)
    }

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

    function isValidCandle(candle: Candle): boolean {
        return (
            isFinite(candle.time) &&
            isFinite(candle.open) &&
            isFinite(candle.high) &&
            isFinite(candle.low) &&
            isFinite(candle.close) &&
            isFinite(candle.volume) &&
            candle.open > 0 &&
            candle.high > 0 &&
            candle.low > 0 &&
            candle.close > 0 &&
            candle.high >= candle.low &&
            candle.high >= candle.open &&
            candle.high >= candle.close &&
            candle.low <= candle.open &&
            candle.low <= candle.close
        )
    }

    function broadcastStateChange(type: string, payload: any) {
        if (broadcastChannel && !isProcessingBroadcast.value) {
            try {
                const serializablePayload = JSON.parse(JSON.stringify(payload))
                broadcastChannel.postMessage({ type, payload: serializablePayload, timestamp: Date.now() })
            } catch (error) {
                console.warn('Failed to broadcast state change:', error)
            }
        }
    }

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
                            const exists = pairData.candles.some(c => c.time === payload.candle.time)
                            if (!exists && isValidCandle(payload.candle)) {
                                pairData.candles.push(payload.candle)
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
                            const price = payload.price
                            if (isFinite(price) && price > 0) {
                                pairDataMap.value[payload.pair].currentPrice = price
                            }
                        }
                        break
                    case 'OPEN_TRADES_UPDATE':
                        openTrades.value = payload.trades
                        break
                    case 'SELECTED_PAIR_CHANGE':
                        selectedPair.value = payload.pair
                        break
                }
            } catch (error) {
                console.error('Error processing broadcast message:', error)
            } finally {
                isProcessingBroadcast.value = false
            }
        }
    }

    function setPair(pair: string) {
        if (!pair) {
            console.error('Invalid pair provided to setPair')
            return
        }

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
        if (!pair) {
            console.error('Invalid pair provided to initializePairData')
            return
        }

        if (!pairDataMap.value[pair]) {
            pairDataMap.value[pair] = {
                candles: [],
                currentPrice: isFinite(initialPrice) && initialPrice > 0 ? initialPrice : 0,
                lastCandleTime: Date.now(),
                basePrice: isFinite(initialPrice) && initialPrice > 0 ? initialPrice : 0,
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
        return Math.max(avgInterval, 1000)
    }

    function initializeCandlesFromOHLC(pair: string, ohlcData: ForexOHLCData) {
        try {
            if (!pair) {
                console.error('Invalid pair provided to initializeCandlesFromOHLC')
                return
            }

            if (!ohlcData || !ohlcData.prices || !Array.isArray(ohlcData.prices)) {
                console.error('Invalid OHLC data provided')
                return
            }

            if (!pairDataMap.value[pair]) {
                initializePairData(pair)
            }

            const pairData = pairDataMap.value[pair]
            const candles: Candle[] = []

            for (let i = 0; i < ohlcData.prices.length; i++) {
                const priceData = ohlcData.prices[i]
                if (!priceData || priceData.length < 5) continue

                const candle: Candle = {
                    time: priceData[0],
                    open: priceData[1],
                    high: priceData[2],
                    low: priceData[3],
                    close: priceData[4],
                    volume: ohlcData.volumes?.[i]?.[1] || 0
                }

                if (isValidCandle(candle)) {
                    candles.push(candle)
                } else {
                    console.warn('Skipping invalid candle:', candle)
                }
            }

            if (candles.length === 0) {
                console.error('No valid candles found in OHLC data')
                setChartError('No valid chart data available')
                return
            }

            pairData.candles = sortCandles(deduplicateCandles(candles))

            const lastCandle = pairData.candles[pairData.candles.length - 1]
            pairData.currentPrice = lastCandle.close
            pairData.basePrice = pairData.candles[0].open
            pairData.lastCandleTime = lastCandle.time
            pairData.candleInterval = detectCandleInterval(pairData.candles)
            pairData.initialized = true

            clearChartError()

            try {
                broadcastStateChange('CANDLES_INITIALIZED', {
                    pair,
                    data: {
                        currentPrice: pairData.currentPrice,
                        lastCandleTime: pairData.lastCandleTime,
                        basePrice: pairData.basePrice,
                        candleInterval: pairData.candleInterval,
                        initialized: true
                    }
                })
            } catch (error) {
                console.warn('Failed to broadcast candles initialization:', error)
            }

        } catch (error) {
            console.error('Error initializing candles from OHLC:', error)
            setChartError('Failed to initialize chart data. Please refresh the page.')
        }
    }

    function initializeCandlesFromForexData(pair: string, ohlcData: ForexOHLCData, currentPrice: number = 0) {
        try {
            if (!pair) {
                console.error('Invalid pair provided to initializeCandlesFromForexData')
                return
            }

            if (!isFinite(currentPrice) || currentPrice <= 0) {
                console.warn('Invalid currentPrice provided, attempting to use last close price')
            }

            initializeCandlesFromOHLC(pair, ohlcData)

            if (isFinite(currentPrice) && currentPrice > 0 && pairDataMap.value[pair]) {
                pairDataMap.value[pair].currentPrice = currentPrice
            }
        } catch (error) {
            console.error('Error initializing candles from Forex data:', error)
            setChartError('Failed to initialize chart data. Please refresh the page.')
        }
    }

    function updatePairData(pair: string, updates: Partial<PairData>) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found in pairDataMap`)
            return
        }

        if (updates.currentPrice !== undefined) {
            if (!isFinite(updates.currentPrice) || updates.currentPrice <= 0) {
                console.warn('Invalid currentPrice in updates, skipping')
                delete updates.currentPrice
            }
        }

        if (updates.candles !== undefined) {
            if (!Array.isArray(updates.candles)) {
                console.warn('Invalid candles in updates, skipping')
                delete updates.candles
            } else {
                updates.candles = updates.candles.filter(isValidCandle)
                updates.candles = sortCandles(deduplicateCandles(updates.candles))
            }
        }

        Object.assign(pairDataMap.value[pair], updates)

        try {
            broadcastStateChange('PAIR_DATA_UPDATE', { pair, data: updates })
        } catch (error) {
            console.warn('Failed to broadcast pair data update:', error)
        }
    }

    function addCandle(pair: string, candle: Candle) {
        const pairData = pairDataMap.value[pair]
        if (!pairData || !pairData.initialized) {
            console.warn(`Cannot add candle: pair ${pair} not initialized`)
            return
        }

        if (!isValidCandle(candle)) {
            console.warn('Attempted to add invalid candle:', candle)
            return
        }

        const exists = pairData.candles.some(c => c.time === candle.time)
        if (!exists) {
            pairData.candles.push(candle)
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

    function updateLastCandle(pair: string, updates: Partial<Candle>) {
        const pairData = pairDataMap.value[pair]
        if (!pairData || !pairData.initialized || pairData.candles.length === 0) {
            console.warn(`Cannot update last candle: pair ${pair} not properly initialized`)
            return
        }

        const lastCandle = pairData.candles[pairData.candles.length - 1]
        if (lastCandle) {
            const updatedCandle = { ...lastCandle, ...updates }
            if (isValidCandle(updatedCandle)) {
                Object.assign(lastCandle, updates)
                try {
                    broadcastStateChange('LAST_CANDLE_UPDATE', { pair, updates })
                } catch (error) {
                    console.warn('Failed to broadcast last candle update:', error)
                }
            } else {
                console.warn('Invalid candle update, skipping:', updates)
            }
        }
    }

    function updateCurrentPrice(pair: string, price: number) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        if (!isFinite(price) || price <= 0) {
            console.warn('Invalid price update:', price)
            return
        }

        pairDataMap.value[pair].currentPrice = price
        try {
            broadcastStateChange('CURRENT_PRICE_UPDATE', { pair, price })
        } catch (error) {
            console.warn('Failed to broadcast current price update:', error)
        }
    }

    function updateLastCandleTime(pair: string, time: number) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        if (!isFinite(time) || time <= 0) {
            console.warn('Invalid time update:', time)
            return
        }

        pairDataMap.value[pair].lastCandleTime = time
    }

    function setZoom(val: number) {
        if (!isFinite(val)) {
            console.warn('Invalid zoom value, resetting to default')
            zoom.value = 1.0
            return
        }
        zoom.value = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, val))
    }

    function setPanX(val: number) {
        if (!isFinite(val)) {
            console.warn('Invalid panX value, resetting to default')
            panX.value = 0
            return
        }
        panX.value = Math.max(-MAX_PAN_THRESHOLD, Math.min(MAX_PAN_THRESHOLD, val))
    }

    function setOpenTrades(trades: OpenTrade[]) {
        if (!Array.isArray(trades)) {
            console.warn('Invalid trades array provided')
            return
        }

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
        if (!isFinite(price)) {
            console.warn('Invalid price for rounding:', price)
            return 0
        }
        const precision = getPricePrecision(pair);
        const factor = Math.pow(10, precision);
        return Math.round(price * factor) / factor;
    }

    function calculateTradePnL(trade: OpenTrade, currentPrice: number): { pnl: number, pnlPct: string } {
        if (!trade || !isFinite(currentPrice) || currentPrice <= 0) {
            return { pnl: 0, pnlPct: '0%' }
        }

        if (!isFinite(trade.amount) || !isFinite(trade.leverage) || !isFinite(trade.entry_price)) {
            return { pnl: 0, pnlPct: '0%' }
        }

        const leverageFactor = trade.leverage || 1
        const roundedPrice = roundPrice(currentPrice, trade.pair)
        const roundedEntry = roundPrice(trade.entry_price, trade.pair)

        const priceChangePercent = trade.type === 'Up'
            ? (roundedPrice - roundedEntry) / roundedEntry
            : (roundedEntry - roundedPrice) / roundedEntry

        const pnl = trade.amount * leverageFactor * priceChangePercent
        const pnlPct = ((pnl / trade.amount) * 100).toFixed(2)

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

            if (!data.candles || !Array.isArray(data.candles)) {
                console.warn(`Invalid candles array for ${pair}`)
                issuesFound = true
            }

            if (!isFinite(data.currentPrice) || data.currentPrice <= 0) {
                console.warn(`Invalid currentPrice for ${pair}:`, data.currentPrice)
                issuesFound = true
            }

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

            if (data.candles) {
                const validCandles = data.candles.filter(isValidCandle)
                if (validCandles.length !== data.candles.length) {
                    console.warn(`Found ${data.candles.length - validCandles.length} invalid candles for ${pair}`)
                    data.candles = validCandles
                    issuesFound = true
                }
            }
        })

        if (!isFinite(zoom.value) || zoom.value < MIN_ZOOM || zoom.value > MAX_ZOOM) {
            console.warn('Invalid zoom value, resetting')
            resetView()
            issuesFound = true
        }

        if (!isFinite(panX.value) || Math.abs(panX.value) > MAX_PAN_THRESHOLD) {
            console.warn('Invalid panX value, resetting')
            panX.value = 0
            issuesFound = true
        }

        return !issuesFound
    }

    function clearCorruptedData() {
        console.warn('Clearing corrupted chart data from localStorage')
        pairDataMap.value = {}
        selectedPair.value = 'EUR/USD'
        zoom.value = 1.0
        panX.value = 0
        openTrades.value = []
        chartError.value = null

        if (typeof localStorage !== 'undefined') {
            try {
                localStorage.removeItem('forex-chart-store')
            } catch (e) {
                console.error('Failed to clear localStorage:', e)
            }
        }
    }

    if (typeof window !== 'undefined') {
        setTimeout(() => {
            try {
                const isValid = validateDataIntegrity()
                if (!isValid) {
                    console.warn('Data integrity issues found during initialization')
                    clearCorruptedData()
                }
            } catch (error) {
                console.error('Error during data validation:', error)
                clearCorruptedData()
            }
        }, 100)
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
