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
    pnl: number
    pnlPct: string
}

export interface TradeWithPnL extends OpenTrade {
    pnl: number
    pnlPct: string
}

const MAX_CANDLES = 200
const MAX_CANDLE_AGE_MS = 24 * 60 * 60 * 1000
const MIN_ZOOM = 0.1
const MAX_ZOOM = 10
const MAX_PAN_THRESHOLD = 100000

export const useChartStore = defineStore('chart', () => {
    const selectedPair = ref<string>('EUR/USD')
    const pairDataMap = ref<Record<string, PairData>>({})
    const zoom = ref(1.0)
    const panX = ref(0)
    const openTrades = ref<OpenTrade[]>([])

    const currentPairData = computed(() => {
        return pairDataMap.value[selectedPair.value]
    })

    const hasPairData = computed(() => {
        return !!pairDataMap.value[selectedPair.value]
    })

    const currentPrice = computed(() => currentPairData.value?.currentPrice || 0)

    const calculateTradePnL = (trade: OpenTrade, price: number) => {
        const diff = trade.type === 'Up'
            ? price - trade.entry_price
            : trade.entry_price - price
        const pnl = diff * trade.amount
        const pnlPct = trade.entry_price !== 0
            ? ((diff / trade.entry_price) * 100).toFixed(2)
            : '0.00'
        return { pnl, pnlPct }
    }

    const openTradesForPair = computed<TradeWithPnL[]>(() => {
        return openTrades.value
            .filter(t => t.pair === selectedPair.value)
    })

    function setPair(pair: string) {
        selectedPair.value = pair
        resetView()
    }

    function initializePairData(pair: string, initialPrice: number) {
        const validPrice = isFinite(initialPrice) && initialPrice > 0
            ? initialPrice
            : 1.0

        if (!pairDataMap.value[pair]) {
            pairDataMap.value[pair] = {
                candles: [],
                currentPrice: validPrice,
                lastCandleTime: Date.now(),
                basePrice: validPrice
            }
        }
    }

    function updatePairData(pair: string, data: Partial<PairData>) {
        if (pairDataMap.value[pair]) {
            const updates: Partial<PairData> = {}

            if (data.currentPrice !== undefined) {
                updates.currentPrice = isFinite(data.currentPrice) && data.currentPrice > 0
                    ? data.currentPrice
                    : pairDataMap.value[pair].currentPrice
            }

            if (data.basePrice !== undefined) {
                updates.basePrice = isFinite(data.basePrice) && data.basePrice > 0
                    ? data.basePrice
                    : pairDataMap.value[pair].basePrice
            }

            if (data.candles !== undefined) {
                updates.candles = data.candles
            }

            if (data.lastCandleTime !== undefined) {
                updates.lastCandleTime = data.lastCandleTime
            }

            pairDataMap.value[pair] = {
                ...pairDataMap.value[pair],
                ...updates
            }
        }
    }

    function addCandle(pair: string, candle: Candle) {
        if (!pairDataMap.value[pair]) return

        const validCandle = {
            time: isFinite(candle.time) ? candle.time : Date.now(),
            open: isFinite(candle.open) ? candle.open : pairDataMap.value[pair].currentPrice,
            high: isFinite(candle.high) ? candle.high : pairDataMap.value[pair].currentPrice,
            low: isFinite(candle.low) ? candle.low : pairDataMap.value[pair].currentPrice,
            close: isFinite(candle.close) ? candle.close : pairDataMap.value[pair].currentPrice,
            volume: isFinite(candle.volume) && candle.volume >= 0 ? candle.volume : 0
        }

        if (validCandle.high < validCandle.low) {
            [validCandle.high, validCandle.low] = [validCandle.low, validCandle.high]
        }

        const candles = pairDataMap.value[pair].candles
        candles.push(validCandle)

        if (candles.length > MAX_CANDLES) {
            candles.shift()
        }

        const cutoffTime = Date.now() - MAX_CANDLE_AGE_MS
        while (candles.length > 0 && candles[0].time < cutoffTime) {
            candles.shift()
        }
    }

    function updateLastCandle(pair: string, updates: Partial<Candle>) {
        const candles = pairDataMap.value[pair]?.candles
        if (!candles || candles.length === 0) return

        const lastCandle = candles[candles.length - 1]

        if (updates.high !== undefined && isFinite(updates.high)) {
            lastCandle.high = Math.max(lastCandle.high, updates.high)
        }
        if (updates.low !== undefined && isFinite(updates.low)) {
            lastCandle.low = Math.min(lastCandle.low, updates.low)
        }
        if (updates.close !== undefined && isFinite(updates.close)) {
            lastCandle.close = updates.close
        }
        if (updates.volume !== undefined && isFinite(updates.volume) && updates.volume >= 0) {
            lastCandle.volume = updates.volume
        }

        lastCandle.high = Math.max(lastCandle.high, lastCandle.open, lastCandle.close)
        lastCandle.low = Math.min(lastCandle.low, lastCandle.open, lastCandle.close)
    }

    function updateCurrentPrice(pair: string, price: number) {
        if (pairDataMap.value[pair] && isFinite(price) && price > 0) {
            pairDataMap.value[pair].currentPrice = price
        }
    }

    function updateLastCandleTime(pair: string, time: number) {
        if (pairDataMap.value[pair] && isFinite(time)) {
            pairDataMap.value[pair].lastCandleTime = time
        }
    }

    function setZoom(value: number) {
        const validValue = isFinite(value) ? value : 1.0
        zoom.value = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, validValue))
    }

    function setPanX(value: number) {
        if (!isFinite(value)) {
            panX.value = 0
            return
        }

        if (Math.abs(value) > MAX_PAN_THRESHOLD) {
            panX.value = 0
        } else {
            panX.value = value
        }
    }

    function setOpenTrades(trades: OpenTrade[]) {
        openTrades.value = trades.map(t => {
            const existingTrade = openTrades.value.find(et => et.id === t.id)
            if (existingTrade) {
                return existingTrade
            }

            if (t.pnl === undefined || t.pnlPct === undefined) {
                const { pnl, pnlPct } = calculateTradePnL(t, currentPrice.value)
                return { ...t, pnl, pnlPct }
            }
            return t
        })
    }

    function updateTradePnL(tradeId: number, price: number) {
        const tradeIndex = openTrades.value.findIndex(t => t.id === tradeId)
        if (tradeIndex === -1) return

        const trade = openTrades.value[tradeIndex]
        const { pnl, pnlPct } = calculateTradePnL(trade, price)
        openTrades.value[tradeIndex] = { ...trade, pnl, pnlPct }
    }

    function resetView() {
        zoom.value = 1.0
        panX.value = 0
    }

    function clearPairData(pair: string) {
        delete pairDataMap.value[pair]
    }

    function clearAllData() {
        pairDataMap.value = {}
        resetView()
    }

    function cleanupStalePairs(activePairs: string[]) {
        const currentPairs = Object.keys(pairDataMap.value)
        currentPairs.forEach(pair => {
            if (!activePairs.includes(pair)) {
                delete pairDataMap.value[pair]
            }
        })
    }

    function validateDataIntegrity() {
        let issuesFound = false

        Object.entries(pairDataMap.value).forEach(([pair, data]) => {
            if (!isFinite(data.currentPrice) || data.currentPrice <= 0) {
                console.warn(`Invalid currentPrice for ${pair}:`, data.currentPrice)
                issuesFound = true
            }

            data.candles.forEach((candle, idx) => {
                if (!isFinite(candle.open) || !isFinite(candle.high) ||
                    !isFinite(candle.low) || !isFinite(candle.close)) {
                    console.warn(`Invalid candle at index ${idx} for ${pair}`)
                    issuesFound = true
                }
            })
        })

        if (!isFinite(zoom.value)) {
            console.warn('Invalid zoom value:', zoom.value)
            resetView()
            issuesFound = true
        }

        if (!isFinite(panX.value)) {
            console.warn('Invalid panX value:', panX.value)
            panX.value = 0
            issuesFound = true
        }

        return !issuesFound
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
        setPair,
        initializePairData,
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
        clearPairData,
        clearAllData,
        cleanupStalePairs,
        validateDataIntegrity,
        calculateTradePnL
    }
}, {
    persist: {
        key: 'forex-chart-store',
        storage: localStorage,
        paths: ['selectedPair', 'pairDataMap', 'openTrades', 'zoom', 'panX']
    }
})
