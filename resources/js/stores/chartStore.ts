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
    hasMoreHistoricalData: true
    simulationPhase: number
    lastSimulationUpdate: number
    activeSimulatedCandle: Candle | null
    currentCandleTick: number
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

interface StoreMetadata {
    version: string
    lastUpdated: number
    expiresAt: number
}

const MAX_CANDLES = 5000
const MIN_ZOOM = 0.1
const MAX_ZOOM = 10
const MAX_PAN_THRESHOLD = 100000
const DEFAULT_CANDLE_INTERVAL_MS = 60000
const STORE_VERSION = '1.0.0'
const STORE_EXPIRY_MS = 24 * 60 * 60 * 1000

export const useChartStore = defineStore('chart', () => {
    const selectedPair = ref<string>('EUR/USD')
    const pairDataMap = ref<Record<string, PairData>>({})
    const zoom = ref(1.0)
    const panX = ref(0)
    const openTrades = ref<OpenTrade[]>([])
    const chartError = ref<string | null>(null)
    const metadata = ref<StoreMetadata>({
        version: STORE_VERSION,
        lastUpdated: Date.now(),
        expiresAt: Date.now() + STORE_EXPIRY_MS
    })

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

    function checkAndClearExpiredData(): boolean {
        const now = Date.now()

        if (!metadata.value.expiresAt || now > metadata.value.expiresAt) {
            console.warn('Store data expired, clearing...')
            clearCorruptedData()
            return true
        }

        if (metadata.value.version !== STORE_VERSION) {
            console.warn('Store version mismatch, clearing...')
            clearCorruptedData()
            return true
        }

        return false
    }

    function updateMetadata() {
        metadata.value = {
            version: STORE_VERSION,
            lastUpdated: Date.now(),
            expiresAt: Date.now() + STORE_EXPIRY_MS
        }
    }

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

    function setPair(pair: string) {
        if (!pair) {
            console.error('Invalid pair provided to setPair')
            return
        }

        selectedPair.value = pair
        chartError.value = null

        if (!pairDataMap.value[pair]) {
            initializePairData(pair)
        }

        updateMetadata()
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
                nextUrl: null,
                isLoadingHistorical: false,
                hasMoreHistoricalData: true,
                simulationPhase: 0,
                lastSimulationUpdate: Date.now(),
                activeSimulatedCandle: null,
                currentCandleTick: 0
            }
        }

        updateMetadata()
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
            updateMetadata()
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
            pairData.lastCandleTime = pairData.candles[pairData.candles.length - 1].time * 1000
        }

        updateMetadata()
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
            updateMetadata()
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
            pairData.lastCandleTime = pairData.candles[pairData.candles.length - 1].time * 1000
        }

        updateMetadata()
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

        pairData.nextUrl = nextUrl
        pairData.hasMoreHistoricalData = !!nextUrl
        pairData.isLoadingHistorical = false

        updateMetadata()
    }

    function setHistoricalLoadingState(pair: string, isLoading: boolean) {
        if (pairDataMap.value[pair]) {
            pairDataMap.value[pair].isLoadingHistorical = isLoading
        }
    }

    function updatePairData(pair: string, updates: Partial<PairData>) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        Object.assign(pairDataMap.value[pair], updates)
        updateMetadata()
    }

    function addCandle(pair: string, candle: Candle) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        if (!isValidCandle(candle)) {
            console.warn('Invalid candle data, skipping')
            return
        }

        const pairData = pairDataMap.value[pair]

        const exists = pairData.candles.some(c => c.time === candle.time)
        if (exists) {
            return
        }

        pairData.candles.push(candle)
        pairData.candles = sortCandles(pairData.candles)

        if (pairData.candles.length > MAX_CANDLES) {
            pairData.candles.shift()
        }

        pairData.lastCandleTime = Math.max(pairData.lastCandleTime, candle.time * 1000)
        updateMetadata()
    }

    function updateLastCandle(pair: string, updates: Partial<Candle>) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        const pairData = pairDataMap.value[pair]
        if (pairData.candles.length === 0) {
            console.warn(`No candles available for pair ${pair}`)
            return
        }

        Object.assign(pairData.candles[pairData.candles.length - 1], updates)
    }

    function updateCurrentPrice(pair: string, price: number) {
        if (!pairDataMap.value[pair]) {
            initializePairData(pair, price)
        }

        if (!isFinite(price) || price <= 0) {
            console.warn('Invalid price update:', price)
            return
        }

        const pairData = pairDataMap.value[pair]
        pairData.currentPrice = price

        if (pairData.basePrice === 0) {
            pairData.basePrice = price
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

    function updateSimulationPhase(pair: string, phase: number) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        pairDataMap.value[pair].simulationPhase = phase
        pairDataMap.value[pair].lastSimulationUpdate = Date.now()
    }

    function updateActiveSimulatedCandle(pair: string, candle: Candle | null) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }

        pairDataMap.value[pair].activeSimulatedCandle = candle
    }

    function getActiveSimulatedCandle(pair: string): Candle | null {
        if (!pairDataMap.value[pair]) {
            return null
        }
        return pairDataMap.value[pair].activeSimulatedCandle
    }

    function updateCurrentCandleTick(pair: string, tick: number) {
        if (!pairDataMap.value[pair]) {
            console.warn(`Pair ${pair} not found`)
            return
        }
        pairDataMap.value[pair].currentCandleTick = tick
    }

    function getCurrentCandleTick(pair: string): number {
        if (!pairDataMap.value[pair]) {
            return 0
        }
        return pairDataMap.value[pair].currentCandleTick || 0
    }

    function getSimulationPhase(pair: string): number {
        if (!pairDataMap.value[pair]) {
            return 0
        }
        return pairDataMap.value[pair].simulationPhase || 0
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

        const existingTradesMap = new Map(
            openTrades.value.map(t => [t.id, { pnl: t.pnl, pnlPct: t.pnlPct }])
        )

        openTrades.value = trades.map(trade => {
            const existing = existingTradesMap.get(trade.id)
            if (existing && isFinite(existing.pnl)) {
                return {
                    ...trade,
                    pnl: existing.pnl,
                    pnlPct: existing.pnlPct
                }
            }
            return trade
        })
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

            if (!isFinite(data.currentPrice) || data.currentPrice < 0) {
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
        metadata.value = {
            version: STORE_VERSION,
            lastUpdated: Date.now(),
            expiresAt: Date.now() + STORE_EXPIRY_MS
        }

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
                const isExpired = checkAndClearExpiredData()

                if (!isExpired) {
                    const isValid = validateDataIntegrity()
                    if (!isValid) {
                        console.warn('Data integrity issues found during initialization')
                    }
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
        metadata,
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
        updateSimulationPhase,
        updateActiveSimulatedCandle,
        getActiveSimulatedCandle,
        updateCurrentCandleTick,
        getCurrentCandleTick,
        getSimulationPhase,
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
        checkAndClearExpiredData,
    }
}, {
    persist: {
        key: 'forex-chart-store',
        storage: localStorage,
        paths: ['selectedPair', 'zoom', 'panX', 'pairDataMap', 'openTrades', 'metadata']
    }
})
