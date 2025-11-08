<script setup lang="ts">
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { Maximize2, Radio, RotateCcw, ZoomIn, ZoomOut } from 'lucide-vue-next';
    import { OpenTrade, TradeWithPnL, useChartStore } from '@/stores/chartStore';
    import { getFinnhubService } from '@/services/finnhubWebSocket';
    import { router } from '@inertiajs/vue3';
    import type { CandlestickData, IChartApi, ISeriesApi, MouseEventParams } from 'lightweight-charts';
    import { createChart } from 'lightweight-charts';

    interface Candle {
        time: number
        open: number
        high: number
        low: number
        close: number
        volume: number
    }

    interface Props {
        pair: string
        price: number | string
        change?: number | string
        openTrades?: OpenTrade[]
        useBackendData?: boolean
    }

    const props = withDefaults(defineProps<Props>(), {
        pair: 'EUR/USD',
        price: 1.085,
        change: 0,
        openTrades: () => [],
        useBackendData: false
    })

    const emit = defineEmits<{
        'update:pair': [pair: string]
    }>()

    const chartStore = useChartStore()

    const chartContainer = ref<HTMLDivElement | null>(null)
    const chartWrapper = ref<HTMLDivElement | null>(null)
    const isMobile = ref(false)
    const isDark = ref(true)

    let chart: IChartApi | null = null
    let candlestickSeries: ISeriesApi<'Candlestick'> | null = null
    let currentPriceLineId: string | null = null
    let tradePriceLineIds = ref<Map<number, string>>(new Map())
    let dataSet = ref(false)
    let lastKnownCandleTime = ref(0)

    let tickInterval: ReturnType<typeof setInterval> | null = null
    let healthCheckInterval: ReturnType<typeof setInterval> | null = null
    let tradeExpiryCheckInterval: ReturnType<typeof setInterval> | null = null
    let resizeObserver: ResizeObserver | null = null

    let closingTrades = ref<Set<number>>(new Set())

    const BASE_TICK_MS = 200
    const TICK_MS = 1500
    const TICKS_PER_CANDLE_TARGET = 12
    const CANDLE_INTERVAL_MS = TICK_MS * TICKS_PER_CANDLE_TARGET
    const HEALTH_CHECK_INTERVAL = 30000
    const TRADE_EXPIRY_CHECK_INTERVAL = 1000
    const VOLATILITY_BASE = 0.00008
    const CLOSE_DISABLE_THRESHOLD = 10000

    const getCssVar = (varName: string, element = document.documentElement) => {
        const tempElement = document.createElement('div');
        tempElement.style.setProperty('display', 'none');
        document.body.appendChild(tempElement);
        if (isDark.value) {
            tempElement.classList.add('dark');
        } else {
            tempElement.classList.remove('dark');
        }
        const style = getComputedStyle(tempElement);
        const value = style.getPropertyValue(varName).trim();
        document.body.removeChild(tempElement);
        if (!value) {
            switch(varName) {
                case '--background': return isDark.value ? '#000000' : '#ffffff';
                case '--foreground': return isDark.value ? '#ffffff' : '#000000';
                case '--border': return isDark.value ? '#333333' : '#cccccc';
                case '--success': return '#26a69a';
                case '--destructive': return '#ef5350';
                case '--muted': return isDark.value ? '#1a1a1a' : '#f0f0f0';
                default: return isDark.value ? '#ffffff' : '#000000';
            }
        }
        return `hsl(var(${varName}))`
    }

    const upColorLW = computed(() => isDark.value ? '#26a69a' : '#10b981')
    const downColorLW = computed(() => isDark.value ? '#ef5350' : '#ef4444')
    const crosshairColorLW = computed(() => isDark.value ? '#9ca3af' : '#6b7280')

    const bgColor = computed(() => isDark.value ? getCssVar('--background') : getCssVar('--background'))
    const gridColor = computed(() => isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)')
    const textColor = computed(() => isDark.value ? getCssVar('--foreground') : getCssVar('--foreground'))

    const upColor = computed(() => `hsl(var(--success))` )
    const downColor = computed(() => `hsl(var(--destructive))` )
    const crosshairColor = computed(() => `hsl(var(--muted))` )


    const AXIS_SPACE_X = computed(() => isMobile.value ? 0 : 60)
    const AXIS_SPACE_Y = 70

    const candles = computed(() => chartStore.currentPairData?.candles || [])
    const currentPrice = computed(() => chartStore.currentPairData?.currentPrice || parseFloat(String(props.price)) || 1.085)
    const lastCandleTime = computed(() => chartStore.currentPairData?.lastCandleTime || Date.now())

    const hoveredTrades = ref<TradeWithPnL[]>([])
    const tooltipX = ref(0)
    const tooltipBaseY = ref(0)
    const candleTooltip = ref<{ visible: boolean; x: number; y: number; data: any; snappedX: number }>({ visible: false, x: 0, y: 0, data: null, snappedX: 0 })
    const crossPriceLabel = ref<{ visible: boolean; y: number; price: string }>({ visible: false, y: 0, price: '' })
    const timeLabel = ref<{ visible: boolean; x: number; text: string; width: number }>({ visible: false, x: 0, text: '', width: 0 })

    const leftBadges = ref<any[]>([])
    const rightBadges = ref<any[]>([])

    const hasData = computed(() => candles.value.length > 0 && chartStore.hasPairData)

    const displayPrecision = computed(() => getPricePrecision(props.pair))

    const tooltipWidth = computed(() => isMobile.value ? 200 : 240)
    const tooltipHeight = computed(() => isMobile.value ? 140 : 160)
    const tooltipSpacing = computed(() => isMobile.value ? 6 : 8)
    const rowHeight = computed(() => isMobile.value ? 22 : 24)
    const headerHeight = computed(() => isMobile.value ? 28 : 32)
    const footerHeight = computed(() => isMobile.value ? 36 : 40)

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

    const validateChartState = (): boolean => {
        if (!isFinite(currentPrice.value) || currentPrice.value <= 0) {
            return false
        }

        return !candles.value.some(c =>
            !isFinite(c.open) || !isFinite(c.high) ||
            !isFinite(c.low) || !isFinite(c.close)
        );
    }

    const calculatePnL = (trade: OpenTrade, price: number): number => {
        const leverageFactor = trade.leverage || 1
        const tradeVolume = trade.amount * leverageFactor

        const diff = trade.type === 'Up'
            ? price - trade.entry_price
            : trade.entry_price - price
        return diff * tradeVolume
    }

    const entryLineColor = (t: TradeWithPnL) => t.type === 'Up' ? upColor.value : downColor.value
    const pnlColor = (t: TradeWithPnL) => t.pnl >= 0 ? upColor.value : downColor.value

    const closeTrade = (tradeId: number, isAutoClose: boolean = false) => {
        if (closingTrades.value.has(tradeId)) {
            return
        }

        const trade = props.openTrades?.find(t => t.id === tradeId)
        if (!trade) return

        closingTrades.value.add(tradeId)

        chartStore.setOpenTrades(chartStore.openTrades.filter(t => t.id !== tradeId))
        hoveredTrades.value = hoveredTrades.value.filter(t => t.id !== tradeId)

        const pnl = calculatePnL(trade, currentPrice.value)
        const exitPrice = roundPrice(currentPrice.value, props.pair)

        const closeData = {
            exit_price: exitPrice,
            pnl: parseFloat(pnl.toFixed(2)),
            closed_at: new Date().toISOString(),
            is_auto_close: isAutoClose
        }

        router.patch(route('user.trade.forex.close', { trade: tradeId }), closeData, {
            preserveScroll: true,
            onSuccess: () => {
                closingTrades.value.delete(tradeId)
            },
            onError: (errors) => {
                console.error('Failed to close trade:', errors)
                closingTrades.value.delete(tradeId)
            },
            onFinish: () => {
                closingTrades.value.delete(tradeId)
            }
        })
    }

    const parseDurationToMs = (duration: string): number => {
        const value = parseInt(duration)
        const unit = duration.slice(-1)

        switch(unit) {
            case 'm': return value * 60 * 1000
            case 'h': return value * 60 * 60 * 1000
            case 'd': return value * 24 * 60 * 60 * 1000
            default: return value * 60 * 1000
        }
    }

    const checkExpiredTrades = () => {
        const now = new Date().getTime()

        props.openTrades
            ?.filter(trade => !closingTrades.value.has(trade.id))
            .forEach(trade => {
                const openedAt = new Date(trade.opened_at).getTime()

                let expiryTime: number

                if ('expiry_time' in trade && trade.expiry_time) {
                    expiryTime = new Date(trade.expiry_time).getTime()
                } else if ('duration' in trade && trade.duration) {
                    const durationMs = parseDurationToMs(trade.duration)
                    expiryTime = openedAt + durationMs
                } else {
                    expiryTime = openedAt + (5 * 60 * 1000)
                }

                if (now >= expiryTime) {
                    closeTrade(trade.id, true)
                }
            })
    }

    const startTradeExpiryMonitoring = () => {
        tradeExpiryCheckInterval = setInterval(() => {
            checkExpiredTrades()
        }, TRADE_EXPIRY_CHECK_INTERVAL)
    }

    const handleWebSocketTrade = (trade: any) => {
        if (candlestickSeries && candles.value.length > 0) {
            const lastBar = mapToLWData(candles.value[candles.value.length - 1])
            candlestickSeries.update(lastBar)
        }
        updateCurrentPriceLine()
        updateTradeBadges()
    }

    const initPrice = () => {
        const base = parseFloat(String(props.price)) || 1.085
        const parsedPrice = roundPrice(base, props.pair)

        if (!isFinite(parsedPrice) || parsedPrice <= 0) {
            return
        }

        if (!props.useBackendData && !chartStore.hasPairData) {
            chartStore.initializePairData(props.pair, parsedPrice)
        } else if (chartStore.hasPairData) {
            chartStore.updateCurrentPrice(props.pair, parsedPrice)
        }

        chartStore.openTrades.forEach(t => {
            if (t.pair === props.pair) {
                chartStore.updateTradePnL(t.id, currentPrice.value)
            }
        })
    }

    const generateCandles = (count = 100) => {
        if (props.useBackendData && chartStore.hasPairData && candles.value.length > 0) {
            return
        }

        if (!props.useBackendData && (!chartStore.hasPairData || candles.value.length === 0)) {
            const data: Candle[] = []
            let price = currentPrice.value

            if (!isFinite(price) || price <= 0) {
                price = 1.085
            }

            const now = Date.now()
            for (let i = count - 1; i >= 0; i--) {
                const time = now - i * CANDLE_INTERVAL_MS
                const volatility = 0.0003 + Math.random() * 0.0004
                const change = (Math.random() - 0.5) * volatility * price
                const open = price
                const close = price + change
                const high = Math.max(open, close) + Math.random() * 0.0001 * price
                const low = Math.min(open, close) - Math.random() * 0.0001 * price

                data.push({
                    time,
                    open: roundPrice(open, props.pair),
                    high: roundPrice(high, props.pair),
                    low: roundPrice(low, props.pair),
                    close: roundPrice(close, props.pair),
                    volume: Math.floor(Math.random() * 1500) + 500,
                })
                price = roundPrice(close, props.pair)
            }

            chartStore.updatePairData(props.pair, {
                candles: data,
                lastCandleTime: now,
                basePrice: data[0]?.open || currentPrice.value
            })
        }
    }

    const mapToLWData = (candle: Candle): CandlestickData<number> => ({
        time: Math.floor(candle.time / 1000),
        open: candle.open,
        high: candle.high,
        low: candle.low,
        close: candle.close
    })

    const updateTradeBadges = () => {
        if (!candlestickSeries || !chartContainer.value || !chart) return

        try {
            const priceScale = chart.priceScale('right')

            if (!priceScale || typeof priceScale.priceToCoordinate !== 'function') {
                return
            }

            const height = chartContainer.value.clientHeight
            const groups: { [key: string]: TradeWithPnL[] } = {}

            props.openTrades?.forEach(t => {
                const key = t.entry_price.toFixed(displayPrecision.value)
                if (!groups[key]) groups[key] = []
                groups[key].push(t as TradeWithPnL)
            })

            const lefts: any[] = []
            const rights: any[] = []

            Object.entries(groups).forEach(([key, group]) => {
                const price = parseFloat(key)

                const coordinate = priceScale.priceToCoordinate(price)
                if (!isFinite(coordinate)) return

                group.forEach((trade, index) => {
                    const offset = index * 3
                    const y = coordinate + offset
                    if (y < -20 || y > height + 20) return

                    lefts.push({
                        tradeId: trade.id,
                        y,
                        entryText: trade.entry_price.toFixed(displayPrecision.value),
                        color: entryLineColor(trade),
                        showCount: index === 0,
                        count: group.length
                    })

                    const pnl = chartStore.openTrades.find(t => t.id === trade.id)?.pnl || calculatePnL(trade, currentPrice.value)
                    const totalValue = (trade.amount + pnl).toFixed(2)
                    rights.push({
                        tradeId: trade.id,
                        y,
                        pnlText: totalValue,
                        color: pnlColor(trade)
                    })
                })
            })

            leftBadges.value = lefts
            rightBadges.value = rights
        } catch (error) {
            console.warn('updateTradeBadges error:', error)
        }
    }

    const jumpToLive = () => {
        if (chart) chart.timeScale().scrollToRealTime()
    }

    const fitToScreen = () => {
        if (chart && candlestickSeries) {
            chart.timeScale().fitContent()
            jumpToLive()
        }
    }

    const zoomIn = () => {
        if (!chart) return
        const timeScale = chart.timeScale()
        const visibleRange = timeScale.getVisibleRange()
        if (visibleRange) {
            const newRange = (visibleRange.to - visibleRange.from) * 0.8
            const center = (visibleRange.from + visibleRange.to) / 2
            timeScale.setVisibleRange({
                from: center - newRange / 2,
                to: center + newRange / 2
            })
        }
    }

    const zoomOut = () => {
        if (!chart) return
        const timeScale = chart.timeScale()
        const visibleRange = timeScale.getVisibleRange()
        if (visibleRange) {
            const newRange = (visibleRange.to - visibleRange.from) * 1.2
            const center = (visibleRange.from + visibleRange.to) / 2
            timeScale.setVisibleRange({
                from: center - newRange / 2,
                to: center + newRange / 2
            })
        }
    }

    const resetView = () => {
        fitToScreen()
    }

    const startTicking = () => {
        if (tickInterval) clearInterval(tickInterval)

        tickInterval = setInterval(() => {
            if (!validateChartState() || !candlestickSeries) {
                return
            }

            const now = Date.now()
            const wasAboutToAdd = now - lastCandleTime.value >= CANDLE_INTERVAL_MS

            if (!props.useBackendData) {
                const volatilityScaleFactor = TICK_MS / BASE_TICK_MS
                const change = (Math.random() - 0.5) * VOLATILITY_BASE * currentPrice.value * volatilityScaleFactor

                let newPrice = roundPrice(currentPrice.value + change, props.pair)

                if (!isFinite(newPrice) || newPrice <= 0) {
                    newPrice = currentPrice.value
                }

                chartStore.updateCurrentPrice(props.pair, newPrice)
            }

            chartStore.openTrades.forEach(t => {
                if (t.pair === props.pair) {
                    chartStore.updateTradePnL(t.id, currentPrice.value)
                }
            })

            if (!props.useBackendData) {
                if (wasAboutToAdd) {
                    const lastClose = currentPrice.value
                    const volume = Math.floor(Math.random() * 500) + 100
                    chartStore.addCandle(props.pair, {
                        time: now,
                        open: roundPrice(lastClose, props.pair),
                        high: roundPrice(lastClose, props.pair),
                        low: roundPrice(lastClose, props.pair),
                        close: roundPrice(lastClose, props.pair),
                        volume: isFinite(volume) && volume >= 0 ? volume : 100,
                    })
                    chartStore.updateLastCandleTime(props.pair, now)
                    jumpToLive()
                } else if (candles.value.length) {
                    const lastCandle = candles.value[candles.value.length - 1]
                    const newVolume = lastCandle.volume + Math.floor(Math.random() * 20)
                    chartStore.updateLastCandle(props.pair, {
                        close: currentPrice.value,
                        high: Math.max(lastCandle.high, currentPrice.value),
                        low: Math.min(lastCandle.low, currentPrice.value),
                        volume: isFinite(newVolume) && newVolume >= 0 ? newVolume : lastCandle.volume
                    })
                }
            } else {
                if (candles.value.length) {
                    const lastCandle = candles.value[candles.value.length - 1]
                    chartStore.updateLastCandle(props.pair, {
                        close: currentPrice.value,
                        high: Math.max(lastCandle.high, currentPrice.value),
                        low: Math.min(lastCandle.low, currentPrice.value)
                    })
                }
            }

            if (candlestickSeries && candles.value.length > 0) {
                const lastBar = mapToLWData(candles.value[candles.value.length - 1])
                candlestickSeries.update(lastBar)
            }

            updateCurrentPriceLine()
            updateTradeBadges()

            lastKnownCandleTime.value = lastCandleTime.value
        }, TICK_MS)
    }

    const updateCurrentPriceLine = () => {
        if (!candlestickSeries || !isFinite(currentPrice.value)) return

        const color = priceMovementColorLW.value

        if (currentPriceLineId) {
            try {
                candlestickSeries.removePriceLine(currentPriceLineId)
            } catch (e) {
                console.warn('Failed to remove current price line:', e)
            }
        }

        try {
            currentPriceLineId = candlestickSeries.createPriceLine({
                price: currentPrice.value,
                color: color,
                lineWidth: 2,
                lineStyle: 2,
                axisLabelVisible: true,
                axisLabelColor: color,
                axisLabelTextColor: isDark.value ? '#000000' : '#ffffff',
            })
        } catch (e) {
            console.warn('Failed to create current price line:', e)
        }
    }

    const applyChartTheme = () => {
        if (!chart) return;
        chart.applyOptions({
            layout: {
                background: { color: isDark.value ? '#000000' : '#ffffff' },
                textColor: isDark.value ? '#e5e5e5' : '#1f2937',
            },
            grid: {
                vertLines: { color: gridColor.value },
                horzLines: { color: gridColor.value },
            },
        });

        if (candlestickSeries) {
            chart.removeSeries(candlestickSeries)
        }

        candlestickSeries = chart.addCandlestickSeries({
            upColor: upColorLW.value,
            downColor: downColorLW.value,
            borderVisible: false,
            wickUpColor: upColorLW.value,
            wickDownColor: downColorLW.value,
        });

        if (candles.value.length > 0) {
            const lwData = candles.value.map(mapToLWData)
            candlestickSeries.setData(lwData)
        }

        updateCurrentPriceLine();
        updateTradeBadges();
    };


    const handleCrosshairMove = (param: MouseEventParams) => {
        if (!chartContainer.value || !candlestickSeries || !chart || !param.point) {
            return
        }

        const rect = chartContainer.value.getBoundingClientRect()
        const width = rect.width
        const height = rect.height

        if (
            param.point === undefined ||
            !param.time ||
            param.point.x < 0 ||
            param.point.x > width ||
            param.point.y < 0 ||
            param.point.y > height
        ) {
            hoveredTrades.value = []
            candleTooltip.value.visible = false
            crossPriceLabel.value.visible = false
            timeLabel.value.visible = false
            return
        }

        const priceScale = chart.priceScale('right')
        const timeScale = chart.timeScale()
        const coordinateY = param.point.y

        const logicalPrice = candlestickSeries.coordinateToPrice(coordinateY)

        const threshold = 0.001

        const nearbyTrades = props.openTrades
            ?.filter(t => Math.abs(logicalPrice - t.entry_price) <= threshold)
            .map(t => ({ ...t, pnl: calculatePnL(t, currentPrice.value) })) as TradeWithPnL[] || []

        if (nearbyTrades.length > 0) {
            hoveredTrades.value = nearbyTrades
            candleTooltip.value.visible = false

            const totalHeight = hoveredTrades.value.length * tooltipHeight.value + Math.max(0, hoveredTrades.value.length - 1) * tooltipSpacing.value
            const availableHeight = height - 20
            let baseY = param.point.y - totalHeight / 2
            if (totalHeight > availableHeight) {
                baseY = 10
            } else {
                if (baseY < 10) baseY = 10
                if (baseY + totalHeight > height - 10) baseY = height - 10 - totalHeight
            }
            tooltipBaseY.value = baseY
            tooltipX.value = param.point.x + 12
            if (tooltipX.value + tooltipWidth.value > width - 20) {
                tooltipX.value = param.point.x - tooltipWidth.value - 12
            }
            if (tooltipX.value < 20) tooltipX.value = 20

            crossPriceLabel.value = { visible: true, y: param.point.y, price: logicalPrice.toFixed(displayPrecision.value) }
            const snappedX = timeScale.timeToCoordinate(param.time)
            timeLabel.value = {
                visible: true,
                x: snappedX,
                text: new Date(param.time * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                width: 0
            }
            const ctx = document.createElement('canvas').getContext('2d')
            if (ctx) {
                ctx.font = isMobile.value ? '8px Inter' : '11px Inter'
                timeLabel.value.width = ctx.measureText(timeLabel.value.text).width
            }
        } else {
            hoveredTrades.value = []
            const candleData = param.seriesData.get(candlestickSeries)
            if (candleData && 'open' in candleData) {
                candleTooltip.value = {
                    visible: true,
                    x: param.point.x + 10,
                    y: param.point.y - 30,
                    data: candleData,
                    snappedX: timeScale.timeToCoordinate(param.time)
                }
                if (candleTooltip.value.x + 190 > width) candleTooltip.value.x = param.point.x - 200
                if (candleTooltip.value.y < 30) candleTooltip.value.y = param.point.y + 10
                crossPriceLabel.value = { visible: true, y: param.point.y, price: logicalPrice.toFixed(displayPrecision.value) }
                const timeText = new Date(param.time * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                timeLabel.value = {
                    visible: true,
                    x: timeScale.timeToCoordinate(param.time),
                    text: timeText,
                    width: 0
                }
                const tempCtx = document.createElement('canvas').getContext('2d')
                if (tempCtx) {
                    tempCtx.font = isMobile.value ? '8px Inter' : '11px Inter'
                    tempCtx.measureText(timeText).width
                }
            } else {
                candleTooltip.value.visible = false
                crossPriceLabel.value.visible = false
                timeLabel.value.visible = false
            }
        }
    }

    const handleClick = (param: MouseEventParams) => {
        if (!candlestickSeries || !chart || !param.point) return

        const logicalPrice = candlestickSeries.coordinateToPrice(param.point.y)
        const threshold = 0.001

        const clickedTrades = props.openTrades
            ?.filter(t => Math.abs(logicalPrice - t.entry_price) <= threshold)
            .map(t => ({ ...t, pnl: calculatePnL(t, currentPrice.value) })) as TradeWithPnL[] || []

        if (clickedTrades.length > 0) {
            hoveredTrades.value = clickedTrades
            const rect = chartContainer.value?.getBoundingClientRect()
            if (rect) {
                tooltipX.value = param.point.x + 12
                tooltipBaseY.value = param.point.y - 70
            }
        }
    }

    const formatRemainingTime = (ms: number): string => {
        if (ms <= 0) return 'EXPIRED'
        const totalSeconds = Math.floor(ms / 1000)
        const hours = Math.floor(totalSeconds / 3600)
        const minutes = Math.floor((totalSeconds % 3600) / 60)
        const seconds = totalSeconds % 60

        if (hours > 0) {
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
        }
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
    }

    const getTradeTimeText = (trade: TradeWithPnL) => {
        const now = Date.now()
        const openedAt = new Date(trade.opened_at).getTime()

        let expiryTime: number

        if ('expiry_time' in trade && trade.expiry_time) {
            expiryTime = new Date(trade.expiry_time).getTime()
        } else if ('duration' in trade && trade.duration) {
            const durationMs = parseDurationToMs(trade.duration)
            expiryTime = openedAt + durationMs
        } else {
            expiryTime = openedAt + (5 * 60 * 1000)
        }

        const remainingTimeMs = Math.max(0, expiryTime - now)
        return formatRemainingTime(remainingTimeMs)
    }

    const getTradeTimeColor = (trade: TradeWithPnL) => {
        const remaining = getRemainingMs(trade)
        const isExpired = remaining <= 0
        if (isExpired) return downColor.value
        if (remaining < 60000) return downColor.value
        return entryLineColor(trade)
    }

    const getRemainingMs = (trade: TradeWithPnL) => {
        const now = Date.now()
        const openedAt = new Date(trade.opened_at).getTime()

        let expiryTime: number

        if ('expiry_time' in trade && trade.expiry_time) {
            expiryTime = new Date(trade.expiry_time).getTime()
        } else if ('duration' in trade && trade.duration) {
            const durationMs = parseDurationToMs(trade.duration)
            expiryTime = openedAt + durationMs
        } else {
            expiryTime = openedAt + (5 * 60 * 1000)
        }

        return Math.max(0, expiryTime - now)
    }

    const getTooltipStyle = (index: number) => ({
        position: 'absolute' as const,
        left: tooltipX.value + 'px',
        top: (tooltipBaseY.value + index * (tooltipHeight.value + tooltipSpacing.value)) + 'px',
        width: tooltipWidth.value + 'px',
        zIndex: 1000
    })

    const externalChange = ref(0)
    const lastPrice = ref(0)

    const displayChange = computed(() => {
        return externalChange.value !== 0 ? externalChange.value : currentPrice.value - (chartStore.currentPairData?.basePrice ?? currentPrice.value)
    })

    const basePrice = computed(() => {
        return externalChange.value !== 0 ? currentPrice.value - externalChange.value : chartStore.currentPairData?.basePrice ?? currentPrice.value
    })

    const changePercentage = computed(() => {
        return basePrice.value !== 0 ? ((displayChange.value / basePrice.value) * 100).toFixed(2) : '0.00'
    })

    const isBullish = computed(() => {
        return displayChange.value >= 0
    })

    const priceMovementColorLW = computed(() => {
        const up = '#26a69a'
        const down = '#ef5350'

        if (currentPrice.value > lastPrice.value) return up
        if (currentPrice.value < lastPrice.value) return down
        return isBullish.value ? up : down
    })

    const handleResize = () => {
        isMobile.value = window.innerWidth <= 768

        if (chart && chartContainer.value) {
            const width = chartContainer.value.clientWidth
            const height = chartContainer.value.clientHeight

            if (width > 0 && height > 0) {
                chart.resize(width, height)
            }
        }
    }

    const initializeChart = () => {
        if (!chartContainer.value) {
            return
        }

        try {
            handleResize()

            const width = chartContainer.value.clientWidth
            const height = chartContainer.value.clientHeight

            const lwBgColor = isDark.value ? '#000000' : '#ffffff'
            const lwTextColor = isDark.value ? '#e5e5e5' : '#1f2937'

            chart = createChart(chartContainer.value, {
                width,
                height,
                layout: {
                    background: { color: lwBgColor },
                    textColor: lwTextColor,
                },
                grid: {
                    vertLines: { color: gridColor.value },
                    horzLines: { color: gridColor.value },
                },
                crosshair: {
                    mode: 1,
                    vertLine: {
                        color: crosshairColorLW.value,
                        labelVisible: false,
                    },
                    horzLine: {
                        color: crosshairColorLW.value,
                        labelVisible: false,
                    },
                },
                rightPriceScale: {
                    borderVisible: false,
                    width: 70,
                },
                leftPriceScale: {
                    visible: false,
                },
                timeScale: {
                    borderVisible: false,
                    timeVisible: true,
                    secondsVisible: false,
                },
                handleScroll: {
                    mouseWheel: true,
                    pressedMouseMove: true,
                    horzTouchDrag: true,
                    vertTouchDrag: true,
                },
            })

            candlestickSeries = chart.addCandlestickSeries({
                upColor: upColorLW.value,
                downColor: downColorLW.value,
                borderVisible: false,
                wickUpColor: upColorLW.value,
                wickDownColor: downColorLW.value,
            })

            chart.subscribeCrosshairMove(handleCrosshairMove)
            chart.subscribeClick(handleClick)
            chart.timeScale().subscribeVisibleTimeRangeChange(updateTradeBadges)
            chart.timeScale().subscribeVisibleLogicalRangeChange(updateTradeBadges)

            nextTick(() => {
                if (candles.value.length > 0 && candlestickSeries) {
                    const lwData = candles.value.map(mapToLWData)
                    candlestickSeries.setData(lwData)
                    dataSet.value = true
                    fitToScreen()
                }
            })

            applyChartTheme();

        } catch (error) {
            console.error('Failed to initialize chart:', error)
        }
    }

    onMounted(() => {
        handleResize()
        window.addEventListener('resize', handleResize)

        isDark.value = document.documentElement.classList.contains('dark')

        const mo = new MutationObserver(() => {
            isDark.value = document.documentElement.classList.contains('dark')
        })
        mo.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

        onBeforeUnmount(() => mo.disconnect())

        if (chartStore.selectedPair && chartStore.selectedPair !== props.pair) {
            emit('update:pair', chartStore.selectedPair)
        } else {
            chartStore.setPair(props.pair)
        }

        chartStore.setOpenTrades(props.openTrades ?? [])
        initPrice()

        const wsService = getFinnhubService()
        if (wsService) {
            wsService.onTrade(handleWebSocketTrade)
            if (wsService.isConnected() && chartStore.hasPairData) {
                wsService.subscribeToPair(props.pair)
            }
        }

        requestAnimationFrame(() => {
            initializeChart()

            if (!props.useBackendData) {
                generateCandles()
                setTimeout(() => {
                    startTicking()
                }, 500)
            } else {
                startTradeExpiryMonitoring()
            }
        })

        startTradeExpiryMonitoring()

        resizeObserver = new ResizeObserver((entries) => {
            const entry = entries[0]
            if (entry && chart) {
                try {
                    const newWidth = entry.contentRect.width
                    const newHeight = entry.contentRect.height

                    if (newWidth !== chart.options().width || newHeight !== chart.options().height) {
                        chart.resize(newWidth, newHeight)
                        updateTradeBadges()
                    }
                } catch (error) {
                    console.warn('Resize observer error:', error)
                }
            }
        })

        if (chartContainer.value) {
            resizeObserver.observe(chartContainer.value)
        }

        watch(() => props.openTrades, (newTrades) => {
            tradePriceLineIds.value.forEach((id) => {
                if (candlestickSeries && id) {
                    try {
                        candlestickSeries.removePriceLine(id)
                    } catch (e) {
                        console.warn('Failed to remove price line:', e)
                    }
                }
            })
            tradePriceLineIds.value.clear()

            newTrades?.forEach(trade => {
                if (candlestickSeries) {
                    try {

                        const id = candlestickSeries.createPriceLine({
                            price: trade.entry_price,
                            color: entryLineColor(trade as TradeWithPnL),
                            lineWidth: 1,
                            lineStyle: 2,
                            axisLabelVisible: false,
                            lineVisible: true,
                        })
                        tradePriceLineIds.value.set(trade.id, id)
                    } catch (e) {
                        console.warn('Failed to create price line for trade:', trade.id, e)
                    }
                }
            })

            nextTick(() => {
                updateTradeBadges()
            })

        }, { immediate: true, deep: true })
    })

    watch([isDark], () => {
        applyChartTheme();
    })

    watch(() => props.pair, (newPair) => {
        chartStore.setPair(newPair)
        initPrice()
        if (!props.useBackendData) {
            generateCandles()
        }
        dataSet.value = false

        nextTick(() => {
            if (candlestickSeries && candles.value.length > 0) {
                const lwData = candles.value.map(mapToLWData)
                candlestickSeries.setData(lwData)
                dataSet.value = true
                fitToScreen()
            }
        })
    })

    watch(() => props.price, () => {
        initPrice()
    })

    watch(() => props.change, (newVal) => {
        externalChange.value = parseFloat(String(newVal)) || 0
    })

    watch(() => props.openTrades, (newVal) => {
        chartStore.setOpenTrades(newVal ?? [])
    }, { deep: true })

    watch(candles, (newCandles) => {
        if (newCandles.length && !dataSet.value && candlestickSeries) {
            const lwData = newCandles.map(mapToLWData)
            candlestickSeries.setData(lwData)
            dataSet.value = true
            fitToScreen()
        }
    }, { deep: true })

    watch(currentPrice, (newPrice, oldPrice) => {
        lastPrice.value = oldPrice
        updateCurrentPriceLine()
        updateTradeBadges()
    })

    onBeforeUnmount(() => {
        if (tickInterval) {
            clearInterval(tickInterval)
            tickInterval = null
        }

        if (healthCheckInterval) {
            clearInterval(healthCheckInterval)
            healthCheckInterval = null
        }

        if (tradeExpiryCheckInterval) {
            clearInterval(tradeExpiryCheckInterval)
            tradeExpiryCheckInterval = null
        }

        if (resizeObserver && chartContainer.value) {
            resizeObserver.unobserve(chartContainer.value)
            resizeObserver.disconnect()
            resizeObserver = null
        }

        window.removeEventListener('resize', handleResize)

        if (chart) {
            chart.unsubscribeCrosshairMove(handleCrosshairMove)
            chart.unsubscribeClick(handleClick)
            chart.remove()
            chart = null
        }

        candlestickSeries = null
        currentPriceLineId = null
        tradePriceLineIds.value.clear()
    })
</script>

<template>
    <div class="trading-chart-container" ref="chartWrapper">
        <div class="chart-header">
            <div class="pair-info">
                <h2 class="text-foreground">{{ pair }}</h2>
                <div class="price-info flex items-center gap-2">
                    <span class="price text-foreground">{{ currentPrice.toFixed(displayPrecision) }}</span>

                    <span
                        class="change inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium"
                        :class="isBullish ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'"
                    >
            {{ displayChange >= 0 ? '+' : '' }}{{ displayChange.toFixed(displayPrecision) }}
            ({{ changePercentage }}%)
        </span>
                </div>
            </div>


            <div class="chart-controls">
                <div class="flex items-center gap-2">
                    <button
                        :class="[
                            'control-btn border border-border rounded-xl status-indicator',
                            chartStore.connectionStatus === 'connected'
                                ? 'border-success'
                                : chartStore.connectionStatus === 'connecting'
                                ? 'border-warning animate-pulse'
                                : 'border-destructive'
                        ]"
                        :title="chartStore.connectionStatus">
                        <span class="status-dot"
                              :class="{
                                'bg-success': chartStore.connectionStatus === 'connected',
                                'bg-warning animate-pulse': chartStore.connectionStatus === 'connecting',
                                'bg-destructive': chartStore.connectionStatus === 'disconnected'
                              }">
                        </span>
                    </button>
                </div>

                <button @click="zoomIn" class="control-btn border border-border rounded-xl" title="Zoom In">
                    <ZoomIn :size="16" />
                </button>

                <button @click="zoomOut" class="control-btn border border-border rounded-xl" title="Zoom Out">
                    <ZoomOut :size="16" />
                </button>

                <button @click="fitToScreen" class="control-btn border border-border rounded-xl" title="Fit to Screen">
                    <Maximize2 :size="16" />
                </button>

                <button @click="jumpToLive" class="control-btn border border-border rounded-xl" title="Jump to Live">
                    <Radio :size="16" />
                </button>

                <button @click="resetView" class="control-btn border border-border rounded-xl" title="Reset View">
                    <RotateCcw :size="16" />
                </button>
            </div>
        </div>

        <div class="chart-wrapper">
            <div ref="chartContainer" class="chart" />

            <div v-for="(trade, index) in hoveredTrades"
                 :key="trade.id"
                 :style="getTooltipStyle(index)"
                 class="trade-tooltip"
                 :class="{ 'is-mobile': isMobile }">
                <div class="tooltip-header" :style="{ backgroundColor: entryLineColor(trade) }">
                    <span class="trade-type">{{ trade.type.toUpperCase() }}</span>
                    <span class="trade-time" :style="{ color: getTradeTimeColor(trade) }">
                        {{ getTradeTimeText(trade) }}
                    </span>
                </div>
                <div class="tooltip-content">
                    <div class="tooltip-row">
                        <span>Entry:</span>
                        <span>{{ trade.entry_price.toFixed(displayPrecision) }}</span>
                    </div>
                    <div class="tooltip-row">
                        <span>Amount:</span>
                        <span>${{ trade.amount.toFixed(2) }}</span>
                    </div>
                    <div class="tooltip-row">
                        <span>Leverage:</span>
                        <span>{{ trade.leverage || 1 }}x</span>
                    </div>
                    <div class="tooltip-row">
                        <span>Current:</span>
                        <span>{{ currentPrice.toFixed(displayPrecision) }}</span>
                    </div>
                </div>
                <div class="tooltip-footer">
                    <div class="pnl-display" :style="{ color: pnlColor(trade) }">
                        P&L: {{ trade.pnl >= 0 ? '+' : '' }}{{ trade.pnl.toFixed(2) }}
                    </div>
                    <button
                        @click="closeTrade(trade.id)"
                        class="close-btn"
                        :disabled="closingTrades.has(trade.id)"
                    >
                        {{ closingTrades.has(trade.id) ? 'Closing...' : 'Close' }}
                    </button>
                </div>
            </div>

            <div v-if="candleTooltip.visible"
                 class="candle-tooltip"
                 :class="{ 'is-mobile': isMobile }"
                 :style="{
                    left: candleTooltip.x + 'px',
                    top: candleTooltip.y + 'px'
                }">
                <div class="candle-tooltip-header text-foreground">
                    <span>{{ pair }}</span>
                </div>
                <div class="candle-tooltip-content">
                    <div class="candle-row">
                        <span class="text-muted-foreground">O:</span>
                        <span class="text-foreground">{{ candleTooltip.data?.open?.toFixed(displayPrecision) }}</span>
                    </div>
                    <div class="candle-row">
                        <span class="text-muted-foreground">H:</span>
                        <span class="text-foreground">{{ candleTooltip.data?.high?.toFixed(displayPrecision) }}</span>
                    </div>
                    <div class="candle-row">
                        <span class="text-muted-foreground">L:</span>
                        <span class="text-foreground">{{ candleTooltip.data?.low?.toFixed(displayPrecision) }}</span>
                    </div>
                    <div class="candle-row">
                        <span class="text-muted-foreground">C:</span>
                        <span class="text-foreground">{{ candleTooltip.data?.close?.toFixed(displayPrecision) }}</span>
                    </div>
                </div>
            </div>

            <div v-if="crossPriceLabel.visible"
                 class="crosshair-price-label"
                 :class="{ 'is-mobile': isMobile }"
                 :style="{ top: crossPriceLabel.y + 'px' }">
                {{ crossPriceLabel.price }}
            </div>

            <div v-if="timeLabel.visible"
                 class="time-label"
                 :class="{ 'is-mobile': isMobile }"
                 :style="{ left: timeLabel.x - timeLabel.width / 2 + 'px' }">
                {{ timeLabel.text }}
            </div>

            <div v-for="badge in leftBadges"
                 :key="'left-' + badge.tradeId"
                 class="trade-badge left-badge"
                 :class="{ 'is-mobile': isMobile }"
                 :style="{ top: badge.y + 'px', borderColor: badge.color }">
                <span class="badge-text text-foreground">{{ badge.entryText }}</span>
                <div v-if="badge.showCount && badge.count > 1" class="badge-count">
                    {{ badge.count }}
                </div>
            </div>

            <div v-for="badge in rightBadges"
                 :key="'right-' + badge.tradeId"
                 class="trade-badge right-badge"
                 :class="{ 'is-mobile': isMobile }"
                 :style="{ top: badge.y + 'px', borderColor: badge.color }">
                <span class="badge-text" :style="{ color: badge.color }">
                    {{ badge.pnlText }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .trading-chart-container {
        position: relative;
        width: 100%;
        height: 100%;
        background: v-bind(bgColor);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    /* Chart Header */
    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: v-bind(bgColor);
        border-bottom: 1px solid v-bind(gridColor);
        flex-shrink: 0;
    }

    .pair-info h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        /* Replaced v-bind(textColor) with Tailwind utility text-foreground in template for better CSS variable use */
    }

    .price-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 4px;
    }

    .price {
        font-size: 16px;
        font-weight: 600;
        /* Replaced v-bind(textColor) with Tailwind utility text-foreground in template */
    }

    .change {
        font-size: 14px;
        font-weight: 500;
    }

    /* Replaced hardcoded change colors with Tailwind utility classes based on theme variables */
    .change.positive {
        /* This is a fallback and should be overridden by 'text-success' utility */
    }

    .change.negative {
        /* This is a fallback and should be overridden by 'text-destructive' utility */
    }

    .chart-controls {
        display: flex;
        gap: 4px;
    }

    .control-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: v-bind(gridColor);
        cursor: pointer;
        transition: all 0.2s;
        opacity: 0.7;
        position: relative;
    }

    .control-btn:hover {
        background: v-bind(bgColor);
        opacity: 1;
    }

    /* Status Indicator - relies on utility classes from app.css */
    .status-indicator {
        background: transparent !important;
    }

    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        opacity: 1;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* Chart Wrapper */
    .chart-wrapper {
        position: relative;
        width: 100%;
        flex: 1;
        min-height: 400px;
        max-height: calc(100vh - 120px);
    }

    .chart {
        width: 100%;
        height: 100%;
        min-height: 0;
    }

    /* Trade Tooltip */
    .trade-tooltip {
        position: absolute;
        background: v-bind(bgColor); /* Uses hsl(var(--background)) */
        border: 1px solid v-bind(gridColor);
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        width: v-bind(tooltipWidth + 'px');
    }

    .trade-tooltip.is-mobile {
        width: 200px;
    }

    .tooltip-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        border-radius: 6px 6px 0 0;
        color: hsl(var(--primary-foreground)); /* Assuming header text should be primary-foreground for max contrast on entryLineColor */
        font-weight: 600;
        font-size: 12px;
        /* background-color is dynamically set via v-bind inline style from entryLineColor */
    }

    .tooltip-content {
        padding: 12px;
    }

    .tooltip-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
        font-size: 12px;
        color: v-bind(textColor); /* Uses hsl(var(--foreground)) */
        opacity: 0.8;
    }

    .tooltip-row:last-child {
        margin-bottom: 0;
    }

    .tooltip-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        border-top: 1px solid v-bind(gridColor);
    }

    .pnl-display {
        font-weight: 600;
        font-size: 12px;
        /* color is dynamically set via v-bind inline style from pnlColor */
    }

    .close-btn {
        padding: 4px 8px;
        background: hsl(var(--destructive)); /* Use destructive color */
        color: hsl(var(--destructive-foreground)); /* Use destructive-foreground color */
        border: none;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }

    .close-btn:hover:not(:disabled) {
        background: hsl(var(--destructive) / 0.8); /* Slightly darker destructive on hover */
    }

    .close-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Candle Tooltip */
    .candle-tooltip {
        position: absolute;
        background: v-bind(bgColor); /* Uses hsl(var(--background)) */
        border: 1px solid v-bind(gridColor);
        border-radius: 6px;
        padding: 8px 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        font-size: 11px;
        /* Replaced v-bind(textColor) with Tailwind utility text-foreground in template */
    }

    .candle-tooltip.is-mobile {
        font-size: 10px;
        padding: 6px 10px;
    }

    .candle-tooltip-header {
        font-weight: 600;
        font-size: 12px;
        margin-bottom: 6px;
        /* Replaced v-bind(textColor) with Tailwind utility text-foreground in template */
    }

    .candle-tooltip-content {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .candle-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: 11px;
        /* Replaced v-bind(textColor) with Tailwind utility text-foreground/text-muted-foreground in template */
        opacity: 0.8;
    }

    /* Crosshair Labels */
    .crosshair-price-label {
        position: absolute;
        right: 8px;
        transform: translateY(-50%);
        background: v-bind(bgColor); /* Uses hsl(var(--background)) */
        border: 1px solid v-bind(gridColor);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: 500;
        color: v-bind(textColor); /* Uses hsl(var(--foreground)) */
        z-index: 999;
    }

    .crosshair-price-label.is-mobile {
        font-size: 10px;
        padding: 1px 4px;
    }

    .time-label {
        position: absolute;
        bottom: 8px;
        background: v-bind(bgColor); /* Uses hsl(var(--background)) */
        border: 1px solid v-bind(gridColor);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: 500;
        color: v-bind(textColor); /* Uses hsl(var(--foreground)) */
        z-index: 999;
    }

    .time-label.is-mobile {
        font-size: 10px;
        padding: 1px 4px;
    }

    /* Trade Badges */
    .trade-badge {
        position: absolute;
        border: 1px solid;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: 500;
        background: v-bind(bgColor); /* Uses hsl(var(--background)) */
        z-index: 998;
        /* border-color is dynamically set via v-bind inline style from badge.color */
    }

    .trade-badge.is-mobile {
        font-size: 10px;
        padding: 1px 4px;
    }

    .left-badge {
        left: 8px;
    }

    .right-badge {
        right: 8px;
    }

    .badge-text {
        white-space: nowrap;
        /* Replaced v-bind(textColor) with Tailwind utility text-foreground in template */
        /* color is dynamically set via v-bind inline style from badge.color for right-badge */
    }

    .badge-count {
        position: absolute;
        top: -6px;
        right: -6px;
        background: hsl(217.2 91.2% 59.8%); /* Blue color for count (tailwind blue-500 equivalent) */
        color: white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .chart-header {
            padding: 8px 12px;
        }

        .pair-info h2 {
            font-size: 16px;
        }

        .price {
            font-size: 14px;
        }

        .change {
            font-size: 12px;
        }

        .control-btn {
            width: 28px;
            height: 28px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
        }

        .chart-wrapper {
            min-height: 300px;
            height: 300px;
        }
    }
</style>
