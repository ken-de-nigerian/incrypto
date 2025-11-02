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
    entry_price: number
    opened_at: string
    duration?: string
    expiry_time?: string
}

export interface TradeWithPnL extends OpenTrade {
    pnl: number
    pnlPct: string
}

// Constants for cleanup thresholds
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

    const openTradesForPair = computed(() => {
        return openTrades.value
            .filter(t => t.pair === selectedPair.value)
            .map(t => {
                const diff = t.type === 'Up'
                    ? currentPrice.value - t.entry_price
                    : t.entry_price - currentPrice.value
                const pnl = diff * t.amount
                const pnlPct = t.entry_price
                    ? ((diff / t.entry_price) * 100).toFixed(2)
                    : '0.00'
                return { ...t, pnl, pnlPct }
            })
    })

    function setPair(pair: string) {
        selectedPair.value = pair
        // Reset view when switching pairs to prevent carried-over issues
        resetView()
    }

    function initializePairData(pair: string, initialPrice: number) {
        // Validate price to prevent NaN/Infinity
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
            // Validate numeric values before updating
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

        // Validate candle data
        const validCandle = {
            time: candle.time,
            open: isFinite(candle.open) ? candle.open : 0,
            high: isFinite(candle.high) ? candle.high : 0,
            low: isFinite(candle.low) ? candle.low : 0,
            close: isFinite(candle.close) ? candle.close : 0,
            volume: isFinite(candle.volume) ? candle.volume : 0
        }

        const candles = pairDataMap.value[pair].candles
        candles.push(validCandle)

        // Keep only last MAX_CANDLES candles
        if (candles.length > MAX_CANDLES) {
            candles.shift()
        }

        // Remove candles older than 24 hours
        const cutoffTime = Date.now() - MAX_CANDLE_AGE_MS
        while (candles.length > 0 && candles[0].time < cutoffTime) {
            candles.shift()
        }
    }

    function updateLastCandle(pair: string, updates: Partial<Candle>) {
        const candles = pairDataMap.value[pair]?.candles
        if (!candles || candles.length === 0) return

        const lastCandle = candles[candles.length - 1]

        // Validate updates before applying
        if (updates.high !== undefined && isFinite(updates.high)) {
            lastCandle.high = updates.high
        }
        if (updates.low !== undefined && isFinite(updates.low)) {
            lastCandle.low = updates.low
        }
        if (updates.close !== undefined && isFinite(updates.close)) {
            lastCandle.close = updates.close
        }
        if (updates.volume !== undefined && isFinite(updates.volume)) {
            lastCandle.volume = updates.volume
        }
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
        // Clamp zoom between MIN_ZOOM and MAX_ZOOM with validation
        const validValue = isFinite(value) ? value : 1.0
        zoom.value = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, validValue))
    }

    function setPanX(value: number) {
        // Validate and reset if extreme
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
        openTrades.value = trades
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

    // Utility function to clean up stale pair data
    function cleanupStalePairs(activePairs: string[]) {
        const currentPairs = Object.keys(pairDataMap.value)
        currentPairs.forEach(pair => {
            if (!activePairs.includes(pair)) {
                delete pairDataMap.value[pair]
            }
        })
    }

    // Health check function to validate data integrity
    function validateDataIntegrity() {
        let issuesFound = false

        Object.entries(pairDataMap.value).forEach(([pair, data]) => {
            // Check for invalid prices
            if (!isFinite(data.currentPrice) || data.currentPrice <= 0) {
                console.warn(`Invalid currentPrice for ${pair}:`, data.currentPrice)
                issuesFound = true
            }

            // Check for invalid candles
            data.candles.forEach((candle, idx) => {
                if (!isFinite(candle.open) || !isFinite(candle.high) ||
                    !isFinite(candle.low) || !isFinite(candle.close)) {
                    console.warn(`Invalid candle at index ${idx} for ${pair}`)
                    issuesFound = true
                }
            })
        })

        // Check zoom and pan
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
        resetView,
        clearPairData,
        clearAllData,
        cleanupStalePairs,
        validateDataIntegrity
    }
}, {
    persist: {
        key: 'forex-chart-store',
        storage: localStorage,
        paths: ['selectedPair']
    }
})
