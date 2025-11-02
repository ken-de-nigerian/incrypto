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
}

export interface TradeWithPnL extends OpenTrade {
    pnl: number
    pnlPct: string
}

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
    }

    function initializePairData(pair: string, initialPrice: number) {
        if (!pairDataMap.value[pair]) {
            pairDataMap.value[pair] = {
                candles: [],
                currentPrice: initialPrice,
                lastCandleTime: Date.now(),
                basePrice: initialPrice
            }
        }
    }

    function updatePairData(pair: string, data: Partial<PairData>) {
        if (pairDataMap.value[pair]) {
            pairDataMap.value[pair] = {
                ...pairDataMap.value[pair],
                ...data
            }
        }
    }

    function addCandle(pair: string, candle: Candle) {
        if (pairDataMap.value[pair]) {
            pairDataMap.value[pair].candles.push(candle)
            if (pairDataMap.value[pair].candles.length > 200) {
                pairDataMap.value[pair].candles.shift()
            }
        }
    }

    function updateLastCandle(pair: string, updates: Partial<Candle>) {
        const candles = pairDataMap.value[pair]?.candles
        if (candles && candles.length > 0) {
            Object.assign(candles[candles.length - 1], updates)
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

    function setZoom(value: number) {
        zoom.value = value
    }

    function setPanX(value: number) {
        panX.value = value
    }

    function setOpenTrades(trades: OpenTrade[]) {
        openTrades.value = trades
    }

    function clearPairData(pair: string) {
        delete pairDataMap.value[pair]
    }

    function clearAllData() {
        pairDataMap.value = {}
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
        clearPairData,
        clearAllData
    }
}, {
    persist: {
        key: 'forex-chart-store',
        storage: localStorage,
        paths: ['selectedPair', 'zoom', 'panX']
    }
})
