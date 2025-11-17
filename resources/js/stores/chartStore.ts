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
    nextUrl: string | null
    isLoadingHistorical: boolean
    hasMoreHistoricalData: boolean
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
    accumulated_pnl?: number
    effective_entry_price?: number
}

export interface TradeWithPnL extends OpenTrade {
    pnl: number
    pnlPct: string
    accumulated_pnl?: number
    effective_entry_price?: number
}

const MAX_CANDLES = 5000
const MIN_ZOOM = 0.1
const MAX_ZOOM = 10
const MAX_PAN_THRESHOLD = 100000
const DEFAULT_CANDLE_INTERVAL_MS = 60000
let broadcastChannel: BroadcastChannel | null = null
let masterTabId: string | null = null
let currentTabId: string | null = null

if (typeof window !== 'undefined') {

    currentTabId = `tab-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`

    if ('BroadcastChannel' in window) {
        broadcastChannel = new BroadcastChannel('forex-chart-sync')
    }

    const checkMasterTab = () => {
        const storedMaster = sessionStorage.getItem('forex-master-tab')
        if (!storedMaster) {
            masterTabId = currentTabId
            sessionStorage.setItem('forex-master-tab', currentTabId!)
        } else {
            masterTabId = storedMaster
        }
    }

    checkMasterTab()

    window.addEventListener('beforeunload', () => {
        const storedMaster = sessionStorage.getItem('forex-master-tab')
        if (storedMaster === currentTabId) {
            sessionStorage.removeItem('forex-master-tab')
            if (broadcastChannel) {
                broadcastChannel.postMessage({
                    type: 'MASTER_TAB_CLOSED',
                    timestamp: Date.now()
                })
            }
        }
    })
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
                    case 'MASTER_TAB_CLOSED':
                        const storedMaster = sessionStorage.getItem('forex-master-tab')
                        if (!storedMaster && currentTabId) {
                            masterTabId = currentTabId
                            sessionStorage.setItem('forex-master-tab', currentTabId)
                        }
                        break
                    case 'SYNC_STATE_REQUEST':
                        if (currentTabId === masterTabId) {
                            broadcastStateChange('FULL_STATE_SYNC', {
                                pairDataMap: pairDataMap.value,
                                openTrades: openTrades.value,
                                selectedPair: selectedPair.value
                            })
                        }
                        break
                    case 'FULL_STATE_SYNC':
                        if (currentTabId !== masterTabId && payload) {
                            if (payload.pairDataMap) {
                                Object.keys(payload.pairDataMap).forEach(pair => {
                                    if (!pairDataMap.value[pair]) {
                                        pairDataMap.value[pair] = payload.pairDataMap[pair]
                                    } else {
                                        const masterData = payload.pairDataMap[pair]
                                        const localData = pairDataMap.value[pair]
                                        if (masterData.lastCandleTime > localData.lastCandleTime) {
                                            pairDataMap.value[pair] = masterData
                                        }
                                    }
                                })
                            }
                            if (payload.openTrades) {
                                openTrades.value = payload.openTrades
                                recalculateOpenTradesPnL()
                            }
                        }
                        break
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
                                initialized: payload.data.initialized,
                                nextUrl: payload.data.nextUrl,
                                hasMoreHistoricalData: payload.data.hasMoreHistoricalData
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
                    case 'HISTORICAL_CANDLES_PREPENDED':
                        if (pairDataMap.value[payload.pair]?.initialized) {
                            const pairData = pairDataMap.value[payload.pair]
                            pairData.candles = [...payload.candles, ...pairData.candles]
                            pairData.candles = deduplicateCandles(sortCandles(pairData.candles))
                            pairData.nextUrl = payload.nextUrl
                            pairData.hasMoreHistoricalData = payload.hasMoreHistoricalData
                            pairData.isLoadingHistorical = false
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
                        recalculateOpenTradesPnL()
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

        setTimeout(() => {
            if (currentTabId !== masterTabId) {
                broadcastChannel?.postMessage({
                    type: 'SYNC_STATE_REQUEST',
                    timestamp: Date.now()
                })
            }
        }, 100)
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
                lastCandleTime: Math.floor(Date.now() / 1000),
                basePrice: isFinite(initialPrice) && initialPrice > 0 ? initialPrice : 0,
                candleInterval: DEFAULT_CANDLE_INTERVAL_MS,
                initialized: false,
                nextUrl: null,
                isLoadingHistorical: false,
                hasMoreHistoricalData: true
            }
        }
    }

    function setChartError(error: string) {
        chartError.value = error
    }

    function clearChartError() {
        chartError.value = null
    }

    function initializeCandlesFromOHLC(
        pair: string,
        ohlcData: ForexOHLCData | null,
        initialPrice: number,
        nextUrl: string | null = null
    ) {
        if (!pair || !ohlcData) {
            console.warn('Invalid pair or OHLC data')
            return
        }

        initializePairData(pair, initialPrice)

        const pairData = pairDataMap.value[pair]

        if (!ohlcData.prices || !Array.isArray(ohlcData.prices) || ohlcData.prices.length === 0) {
            console.warn(`No valid OHLC prices for pair ${pair}`)
            pairData.initialized = true
            pairData.currentPrice = initialPrice
            pairData.basePrice = initialPrice
            pairData.nextUrl = nextUrl
            pairData.hasMoreHistoricalData = !!nextUrl
            pairData.lastCandleTime = Math.floor(Date.now() / 1000)
            broadcastStateChange('CANDLES_INITIALIZED', {
                pair,
                data: {
                    currentPrice: initialPrice,
                    lastCandleTime: pairData.lastCandleTime,
                    basePrice: initialPrice,
                    candleInterval: pairData.candleInterval,
                    initialized: true,
                    nextUrl: nextUrl,
                    hasMoreHistoricalData: !!nextUrl
                }
            })
            return
        }

        const prices = ohlcData.prices
        const volumes = ohlcData.volumes || []

        const rawCandles: Candle[] = prices.map((p, idx) => {
            const timestamp = p[0]
            const open = p[1]
            const high = p[2]
            const low = p[3]
            const close = p[4]
            const volume = volumes[idx] ? volumes[idx][1] : 0

            return {
                time: timestamp / 1000,
                open,
                high,
                low,
                close,
                volume,
            }
        })

        pairData.candles = deduplicateCandles(sortCandles(rawCandles.filter(isValidCandle)))

        if (pairData.candles.length > MAX_CANDLES) {
            pairData.candles = pairData.candles.slice(-MAX_CANDLES)
        }

        pairData.initialized = true
        pairData.currentPrice = initialPrice
        pairData.basePrice = initialPrice
        pairData.nextUrl = nextUrl
        pairData.hasMoreHistoricalData = !!nextUrl

        if (pairData.candles.length > 0) {
            pairData.lastCandleTime = pairData.candles[pairData.candles.length - 1].time
        }

        broadcastStateChange('CANDLES_INITIALIZED', {
            pair,
            data: {
                currentPrice: initialPrice,
                lastCandleTime: pairData.lastCandleTime,
                basePrice: initialPrice,
                candleInterval: pairData.candleInterval,
                initialized: true,
                nextUrl: nextUrl,
                hasMoreHistoricalData: !!nextUrl
            }
        })
    }

    function initializeCandlesFromForexData(
        pair: string,
        chartData: Candle[],
        initialPrice: number,
        nextUrl: string | null = null
    ) {
        if (!pair || !Array.isArray(chartData)) {
            console.warn('Invalid pair or chart data')
            return
        }

        initializePairData(pair, initialPrice)

        const pairData = pairDataMap.value[pair]

        if (chartData.length === 0) {
            console.warn(`No chart data for pair ${pair}`)
            pairData.initialized = true
            pairData.currentPrice = initialPrice
            pairData.basePrice = initialPrice
            pairData.nextUrl = nextUrl
            pairData.hasMoreHistoricalData = !!nextUrl
            pairData.lastCandleTime = Math.floor(Date.now() / 1000)
            broadcastStateChange('CANDLES_INITIALIZED', {
                pair,
                data: {
                    currentPrice: initialPrice,
                    lastCandleTime: pairData.lastCandleTime,
                    basePrice: initialPrice,
                    candleInterval: pairData.candleInterval,
                    initialized: true,
                    nextUrl: nextUrl,
                    hasMoreHistoricalData: !!nextUrl
                }
            })
            return
        }

        pairData.candles = deduplicateCandles(sortCandles(chartData.filter(isValidCandle)))

        if (pairData.candles.length > MAX_CANDLES) {
            pairData.candles = pairData.candles.slice(-MAX_CANDLES)
        }

        pairData.initialized = true
        pairData.currentPrice = initialPrice
        pairData.basePrice = initialPrice
        pairData.nextUrl = nextUrl
        pairData.hasMoreHistoricalData = !!nextUrl

        if (pairData.candles.length > 0) {
            pairData.lastCandleTime = pairData.candles[pairData.candles.length - 1].time
        }

        broadcastStateChange('CANDLES_INITIALIZED', {
            pair,
            data: {
                currentPrice: initialPrice,
                lastCandleTime: pairData.lastCandleTime,
                basePrice: initialPrice,
                candleInterval: pairData.candleInterval,
                initialized: true,
                nextUrl: nextUrl,
                hasMoreHistoricalData: !!nextUrl
            }
        })
    }

    function prependHistoricalCandles(
        pair: string,
        historicalCandles: Candle[],
        nextUrl: string | null = null
    ) {
        if (!pair || !pairDataMap.value[pair]) {
            console.warn('Invalid pair or pair data not initialized')
            return
        }

        const pairData = pairDataMap.value[pair]

        if (!Array.isArray(historicalCandles) || historicalCandles.length === 0) {
            console.warn('No historical candles to prepend')
            pairData.isLoadingHistorical = false
            pairData.hasMoreHistoricalData = false
            pairData.nextUrl = null
            return
        }

        const validHistoricalCandles = historicalCandles.filter(isValidCandle)

        pairData.candles = [...validHistoricalCandles, ...pairData.candles]
        pairData.candles = deduplicateCandles(sortCandles(pairData.candles))

        if (pairData.candles.length > MAX_CANDLES) {
            pairData.candles = pairData.candles.slice(-MAX_CANDLES)
        }

        if (pairData.candles.length > 0) {
            pairData.lastCandleTime = pairData.candles[pairData.candles.length - 1].time
        }

        pairData.nextUrl = nextUrl
        pairData.hasMoreHistoricalData = !!nextUrl
        pairData.isLoadingHistorical = false

        broadcastStateChange('HISTORICAL_CANDLES_PREPENDED', {
            pair,
            candles: validHistoricalCandles,
            nextUrl: nextUrl,
            hasMoreHistoricalData: !!nextUrl
        })
    }

    function setHistoricalLoadingState(pair: string, isLoading: boolean) {
        if (!pair || !pairDataMap.value[pair]) {
            return
        }
        pairDataMap.value[pair].isLoadingHistorical = isLoading
    }

    function updatePairData(pair: string, updates: Partial<PairData>) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        const validUpdates: Record<string, any> = {}
        for (const key in updates) {
            const value = (updates as any)[key]
            if (value !== undefined && value !== null) {
                validUpdates[key] = value
            }
        }

        if (Object.keys(validUpdates).length > 0) {
            Object.assign(pairDataMap.value[pair], validUpdates)
            try {
                broadcastStateChange('PAIR_DATA_UPDATE', { pair, data: validUpdates })
            } catch (error) {
                console.warn('Failed to broadcast pair data update:', error)
            }
        }
    }

    function addCandle(pair: string, candle: Candle) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        const pairData = pairDataMap.value[pair]

        if (!isValidCandle(candle)) {
            console.warn('Invalid candle, skipping:', candle)
            return
        }

        const exists = pairData.candles.some(c => c.time === candle.time)
        if (exists) {
            return
        }

        pairData.candles.push(candle)
        pairData.candles = sortCandles(pairData.candles)

        if (pairData.candles.length > MAX_CANDLES) {
            pairData.candles.shift()
        }

        pairData.lastCandleTime = Math.max(pairData.lastCandleTime, candle.time)

        try {
            broadcastStateChange('CANDLE_ADDED', { pair, candle })
        } catch (error) {
            console.warn('Failed to broadcast candle addition:', error)
        }
    }

    function updateLastCandle(pair: string, updates: Partial<Candle>) {
        if (!pairDataMap.value[pair]?.initialized) {
            return
        }

        const pairData = pairDataMap.value[pair]
        if (pairData.candles.length === 0) {
            return
        }

        const lastCandle = pairData.candles[pairData.candles.length - 1]
        const original = { ...lastCandle }

        for (const key in updates) {
            const value = (updates as any)[key]
            if (value !== undefined && value !== null && isFinite(value) && value > 0) {
                (lastCandle as any)[key] = value
            }
        }

        if (!isValidCandle(lastCandle)) {
            console.warn('Updated candle is invalid, reverting:', lastCandle)
            Object.assign(lastCandle, original)
            return
        }

        try {
            broadcastStateChange('LAST_CANDLE_UPDATE', { pair, updates })
        } catch (error) {
            console.warn('Failed to broadcast last candle update:', error)
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
        openTrades.value.forEach(trade => {
            if (trade.pair === pair) {
                const { pnl, pnlPct } = calculateTradePnL(trade, price)
                trade.pnl = pnl
                trade.pnLPct = pnlPct
            }
        })
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

        openTrades.value = trades.map(trade => {
            if (trade.accumulated_pnl === undefined) {
                trade.accumulated_pnl = 0
            }
            if (trade.effective_entry_price === undefined) {
                trade.effective_entry_price = trade.entry_price
            }
            return trade
        })

        recalculateOpenTradesPnL()
        try {
            broadcastStateChange('OPEN_TRADES_UPDATE', { trades: openTrades.value })
        } catch (error) {
            console.warn('Failed to broadcast open trades update:', error)
        }
    }

    function recalculateOpenTradesPnL() {
        openTrades.value.forEach(trade => {
            const pairData = pairDataMap.value[trade.pair]
            if (pairData && isFinite(pairData.currentPrice) && pairData.currentPrice > 0) {
                const { pnl, pnlPct } = calculateTradePnL(trade, pairData.currentPrice)
                trade.pnl = pnl
                trade.pnlPct = pnlPct
            } else {
                trade.pnl = trade.accumulated_pnl || 0
                trade.pnlPct = '0%'
            }
        })
    }

    function accumulateTradesPnL() {
        openTrades.value.forEach(trade => {
            const pairData = pairDataMap.value[trade.pair]
            if (pairData && isFinite(pairData.currentPrice) && pairData.currentPrice > 0) {
                const { pnl } = calculateTradePnL(trade, pairData.currentPrice)
                trade.accumulated_pnl = pnl
                trade.effective_entry_price = pairData.currentPrice
                trade.pnl = pnl
            }
        })

        try {
            broadcastStateChange('OPEN_TRADES_UPDATE', { trades: openTrades.value })
        } catch (error) {
            console.warn('Failed to broadcast accumulated trades update:', error)
        }
    }

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
            return { pnl: trade.accumulated_pnl || 0, pnlPct: '0%' }
        }

        if (!isFinite(trade.amount) || !isFinite(trade.leverage) || !isFinite(trade.entry_price)) {
            return { pnl: trade.accumulated_pnl || 0, pnlPct: '0%' }
        }

        const leverageFactor = trade.leverage || 1
        const roundedPrice = roundPrice(currentPrice, trade.pair)

        const effectiveEntry = trade.effective_entry_price || trade.entry_price
        const roundedEntry = roundPrice(effectiveEntry, trade.pair)

        const priceChangePercent = trade.type === 'Up'
            ? (roundedPrice - roundedEntry) / roundedEntry
            : (roundedEntry - roundedPrice) / roundedEntry

        const currentSessionPnL = trade.amount * leverageFactor * priceChangePercent

        const accumulatedPnL = trade.accumulated_pnl || 0
        const totalPnL = accumulatedPnL + currentSessionPnL

        const pnlPct = ((totalPnL / trade.amount) * 100).toFixed(2)

        return { pnl: totalPnL, pnlPct: pnlPct + '%' }
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
                data.candles = []
                issuesFound = true
            }

            if (data.candles.length > 1) {
                for (let i = 1; i < data.candles.length; i++) {
                    if (data.candles[i].time <= data.candles[i - 1].time) {
                        console.warn(`Candles out of order for ${pair}, sorting...`)
                        data.candles = sortCandles(deduplicateCandles(data.candles))
                        issuesFound = true
                        if (data.candles.length > 0) {
                            data.lastCandleTime = data.candles[data.candles.length - 1].time
                        }
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

            if (data.candles.length > 0) {
                if (!isFinite(data.currentPrice) || data.currentPrice <= 0) {
                    console.warn(`Invalid currentPrice for ${pair}, setting to last valid close`)
                    data.currentPrice = data.candles[data.candles.length - 1].close
                    data.basePrice = data.currentPrice
                    issuesFound = true
                }
                if (!isFinite(data.lastCandleTime) || data.lastCandleTime <= 0) {
                    console.warn(`Invalid lastCandleTime for ${pair}, setting to last candle time`)
                    data.lastCandleTime = data.candles[data.candles.length - 1].time
                    issuesFound = true
                }
            } else {
                if (!isFinite(data.currentPrice) || data.currentPrice <= 0) {
                    console.warn(`No candles and invalid currentPrice for ${pair}, resetting`)
                    data.currentPrice = 0
                    data.basePrice = 0
                    data.lastCandleTime = Math.floor(Date.now() / 1000)
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
                validateDataIntegrity()
            } catch (error) {
                console.error('Error during data validation:', error)
                clearCorruptedData()
            }
        }, 100)
    }

    const isMasterTab = computed(() => currentTabId === masterTabId)
    const currentTab = computed(() => currentTabId)
    const masterTab = computed(() => masterTabId)

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
        prependHistoricalCandles,
        setHistoricalLoadingState,
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
        recalculateOpenTradesPnL,
        accumulateTradesPnL,
        isMasterTab,
        currentTab,
        masterTab,
    }
}, {
    persist: {
        key: 'forex-chart-store',
        storage: localStorage,
        paths: ['selectedPair', 'zoom', 'panX', 'pairDataMap', 'openTrades'],
        afterRestore: (ctx) => {
            ctx.store.recalculateOpenTradesPnL()
        }
    }
})
