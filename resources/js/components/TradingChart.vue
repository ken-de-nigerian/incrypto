<script setup lang="ts">
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { Maximize2, Radio, RotateCcw, ZoomIn, ZoomOut } from 'lucide-vue-next';
    import { OpenTrade, TradeWithPnL, useChartStore } from '@/stores/chartStore';
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
    }

    const props = withDefaults(defineProps<Props>(), {
        pair: 'EUR/USD',
        price: 1.085,
        change: 0,
        openTrades: () => []
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

    let streamingDataProvider: Generator<CandlestickData<number>, void, unknown> | null = null

    const BASE_TICK_MS = 100
    const TICK_MS = 1500
    const UPDATES_PER_CANDLE = 20
    const CANDLE_INTERVAL_MS = TICK_MS * UPDATES_PER_CANDLE
    const HEALTH_CHECK_INTERVAL = 30000
    const TRADE_EXPIRY_CHECK_INTERVAL = 1000
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

    const candleTooltip = ref<{ visible: boolean; x: number; y: number; data: any; snappedX: number }>({ visible: false, x: 0, y: 0, data: null, snappedX: 0 })
    const crossPriceLabel = ref<{ visible: boolean; y: number; price: string }>({ visible: false, y: 0, price: '' })
    const timeLabel = ref<{ visible: boolean; x: number; text: string; width: number }>({ visible: false, x: 0, text: '', width: 0 })

    const leftBadges = ref<any[]>([])
    const rightBadges = ref<any[]>([])

    const hasData = computed(() => candles.value.length > 0 && chartStore.hasPairData)

    const displayPrecision = computed(() => getPricePrecision(props.pair))

    const activePairTrades = computed(() => {
        return chartStore.openTrades.filter(t => t.pair === props.pair)
    })

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

    const initPrice = () => {
        const base = parseFloat(String(props.price)) || 1.085
        const parsedPrice = roundPrice(base, props.pair)

        if (!isFinite(parsedPrice) || parsedPrice <= 0) {
            return
        }

        if (chartStore.hasPairData) {
            chartStore.updateCurrentPrice(props.pair, parsedPrice)
        }

        chartStore.openTrades.forEach(t => {
            if (t.pair === props.pair) {
                chartStore.updateTradePnL(t.id, currentPrice.value)
            }
        })
    }

    function* createRealtimeTickGenerator(historicalCandles: Candle[]): Generator<CandlestickData<number>, void, unknown> {
        let initialCandle: Candle;
        let avgVolatility: number;

        if (historicalCandles.length > 0) {
            initialCandle = historicalCandles[historicalCandles.length - 1];

            const last20Candles = historicalCandles.slice(-20);
            const totalRange = last20Candles.reduce((acc, c) => acc + (c.high - c.low), 0);
            avgVolatility = totalRange / last20Candles.length;

            if (avgVolatility <= 0) {
                avgVolatility = initialCandle.close * 0.0005;
            }

        } else {
            const basePrice = parseFloat(String(props.price)) || 1.085
            initialCandle = {
                time: (Date.now() - CANDLE_INTERVAL_MS),
                open: basePrice,
                high: basePrice + basePrice * 0.00025,
                low: basePrice - basePrice * 0.00025,
                close: basePrice,
                volume: 0
            }
            avgVolatility = basePrice * 0.0005;
        }

        let lastClose = initialCandle.close
        let lastTime = Math.floor(initialCandle.time / 1000)

        const baseNoisePercent = 0.00005

        while (true) {
            const newCandleTime = lastTime + (CANDLE_INTERVAL_MS / 1000)
            const candleVolatilityMultiplier = 0.4 + (Math.random() * Math.random()) * 2.0;
            const thisCandleTargetRange = avgVolatility * candleVolatilityMultiplier;
            const tickVolatility = thisCandleTargetRange > 0 ? (thisCandleTargetRange / (UPDATES_PER_CANDLE / 2)) : (lastClose * 0.0001);

            let currentCandle: CandlestickData<number> = {
                time: newCandleTime,
                open: lastClose,
                high: lastClose,
                low: lastClose,
                close: lastClose,
            }

            for (let i = 0; i < UPDATES_PER_CANDLE; i++) {
                const baseTickNoise = (lastClose * baseNoisePercent) * (Math.random() - 0.5);
                let tickMovement = (Math.random() - 0.5) * (tickVolatility * 2) + baseTickNoise;

                if (Math.random() < 0.3) {
                    tickMovement += (currentCandle.close - currentCandle.open) * 0.01
                }

                let newClose = currentCandle.close + tickMovement
                newClose = roundPrice(newClose, props.pair)

                currentCandle.close = newClose
                if (newClose > currentCandle.high) {
                    currentCandle.high = newClose
                }
                if (newClose < currentCandle.low) {
                    currentCandle.low = newClose
                }

                yield { ...currentCandle }
            }

            lastClose = currentCandle.close
            lastTime = currentCandle.time
        }
    }

    const prepareSimulationFromBackend = () => {
        streamingDataProvider = createRealtimeTickGenerator(candles.value)
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
            if (!validateChartState() || !candlestickSeries || !streamingDataProvider) {
                return
            }

            const update = streamingDataProvider.next();

            const candleData = update.value;

            if (!candleData) return;

            candlestickSeries.update(candleData);

            const internalCandle: Candle = {
                time: candleData.time * 1000,
                open: candleData.open,
                high: candleData.high,
                low: candleData.low,
                close: candleData.close,
                volume: Math.floor(Math.random() * 500) + 100,
            };

            if (candles.value.length > 0) {
                const lastCandle = candles.value[candles.value.length - 1];
                if (Math.floor(lastCandle.time / 1000) === candleData.time) {
                    chartStore.updateLastCandle(props.pair, {
                        close: candleData.close,
                        high: candleData.high,
                        low: candleData.low,
                        volume: internalCandle.volume
                    });
                } else {
                    chartStore.addCandle(props.pair, internalCandle);
                    chartStore.updateLastCandleTime(props.pair, internalCandle.time);
                }
            } else {
                chartStore.addCandle(props.pair, internalCandle);
                chartStore.updateLastCandleTime(props.pair, internalCandle.time);
            }

            chartStore.updateCurrentPrice(props.pair, candleData.close);

            chartStore.openTrades.forEach(t => {
                if (t.pair === props.pair) {
                    chartStore.updateTradePnL(t.id, candleData.close)
                }
            })

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
        if (
            !param.point ||
            !param.time ||
            !chartContainer.value ||
            !candlestickSeries ||
            !chart
        ) {
            candleTooltip.value.visible = false
            crossPriceLabel.value.visible = false
            timeLabel.value.visible = false
            return
        }

        const rect = chartContainer.value.getBoundingClientRect()
        const width = rect.width
        const height = rect.height

        if (
            param.point.x < 0 ||
            param.point.x > width ||
            param.point.y < 0 ||
            param.point.y > height
        ) {
            candleTooltip.value.visible = false
            crossPriceLabel.value.visible = false
            timeLabel.value.visible = false
            return
        }

        const priceScale = chart.priceScale('right')
        const timeScale = chart.timeScale()
        const coordinateY = param.point.y

        const logicalPrice = candlestickSeries.coordinateToPrice(coordinateY)

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
                timeLabel.value.width = tempCtx.measureText(timeText).width
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
            chart.timeScale().subscribeVisibleTimeRangeChange(updateTradeBadges)
            chart.timeScale().subscribeVisibleLogicalRangeChange(updateTradeBadges)

            nextTick(() => {
                if (candles.value.length > 0 && candlestickSeries) {
                    const lwData = candles.value.map(mapToLWData)
                    candlestickSeries.setData(lwData)
                    dataSet.value = true
                    fitToScreen()
                }

                drawTradeLines()
                updateTradeBadges()
            })

            applyChartTheme();

        } catch (error) {
            console.error('Failed to initialize chart:', error)
        }
    }

    const drawTradeLines = () => {
        if (!candlestickSeries) {
            return
        }

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

        props.openTrades?.forEach(trade => {
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

        requestAnimationFrame(() => {
            initializeChart()
            prepareSimulationFromBackend()
            setTimeout(() => {
                startTicking()
            }, 500)
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
            drawTradeLines()
            updateTradeBadges()
        }, { immediate: true, deep: true })
    })

    watch([isDark], () => {
        applyChartTheme();
    })

    watch(() => props.pair, (newPair) => {
        if (tickInterval) {
            clearInterval(tickInterval)
            tickInterval = null
        }
        streamingDataProvider = null

        chartStore.setPair(newPair)
        initPrice()
        dataSet.value = false
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

            if (tickInterval) {
                clearInterval(tickInterval)
            }
            prepareSimulationFromBackend()
            startTicking()
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
            chart.remove()
            chart = null
        }

        candlestickSeries = null
        currentPriceLineId = null
        tradePriceLineIds.value.clear()
        streamingDataProvider = null
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
                        :class="isBullish ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'">
                        {{ displayChange >= 0 ? '+' : '' }}{{ displayChange.toFixed(displayPrecision) }}
                        ({{ changePercentage }}%)
                    </span>
                </div>
            </div>

            <div class="chart-controls">
                <div class="flex items-center gap-1 px-2 py-1 bg-card rounded-lg border border-border">
                    <span class="status-dot w-3 h-3 sm:w-4 sm:h-4 bg-success"></span>
                    <span class="hidden sm:inline text-[10px] sm:text-xs font-medium text-muted-foreground">
                        Connected
                    </span>
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
            <div v-if="activePairTrades.length > 0" class="active-trades-list" :class="{ 'is-mobile': isMobile }">
                <div class="active-trades-scroll-wrapper">
                    <div v-for="trade in activePairTrades" :key="trade.id"
                         class="active-trade-item-minimal"
                         :style="{ color: trade.type === 'Up' ? 'hsl(var(--success))' : 'hsl(var(--destructive))' }">
                        <span class="trade-direction" :class="trade.type === 'Up' ? 'text-success' : 'text-destructive'">
                            <svg v-if="trade.type === 'Up'" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            <span>{{ trade.type.toUpperCase() }}</span>
                        </span>

                        <span class="trade-pnl-minimal" :style="{ color: pnlColor(trade) }">
                            {{ trade.pnl >= 0 ? '+' : '' }}{{ trade.pnl?.toFixed(2) ?? '...' }}
                        </span>

                        <span class="trade-time-minimal">
                            {{ getTradeTimeText(trade) }}
                        </span>

                        <button
                            @click="closeTrade(trade.id)"
                            class="close-btn-minimal"
                            :disabled="closingTrades.has(trade.id)"
                            title="Close Trade">
                            {{ closingTrades.has(trade.id) ? '...' : '✕' }}
                        </button>
                    </div>
                </div>
            </div>

            <div ref="chartContainer" class="chart" />

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
        background: hsl(var(--background));
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
        background: hsl(var(--background));
        border-bottom: 1px solid hsl(var(--border));
        flex-shrink: 0;
    }

    .pair-info h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
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
    }

    .change {
        font-size: 14px;
        font-weight: 500;
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
        background: hsl(var(--secondary));
        cursor: pointer;
        transition: all 0.2s;
        opacity: 0.7;
        position: relative;
    }

    .control-btn:hover {
        background: hsl(var(--muted));
        opacity: 1;
    }

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

    .active-trades-list {
        position: absolute;
        top: 12px;
        left: 12px;
        background: linear-gradient(135deg, hsl(var(--background)) 0%, hsl(var(--background) / 0.95) 100%);
        backdrop-filter: blur(12px);
        border: 1px solid hsl(var(--border) / 0.5);
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 990;
        padding: 0;
        width: 280px;
        font-size: 12px;
        overflow: hidden;
    }

    .active-trades-scroll-wrapper {
        max-height: 240px;
        overflow-y: auto;
        padding: 8px;
    }

    /* Custom scrollbar styling */
    .active-trades-scroll-wrapper::-webkit-scrollbar {
        width: 4px;
    }

    .active-trades-scroll-wrapper::-webkit-scrollbar-track {
        background: transparent;
    }

    .active-trades-scroll-wrapper::-webkit-scrollbar-thumb {
        background: hsl(var(--border));
        border-radius: 2px;
    }

    .active-trades-scroll-wrapper::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--muted-foreground));
    }

    .active-trade-item-minimal {
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        grid-template-rows: auto auto;
        gap: 6px 10px;
        padding: 10px 12px;
        background: hsl(var(--card));
        border: 1px solid hsl(var(--border) / 0.4);
        border-radius: 6px;
        margin-bottom: 8px;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .active-trade-item-minimal::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: currentColor;
        opacity: 0.8;
    }

    .active-trade-item-minimal:hover {
        background: hsl(var(--muted) / 0.3);
        border-color: hsl(var(--border));
        transform: translateX(2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .active-trade-item-minimal:last-child {
        margin-bottom: 0;
    }

    .trade-direction {
        grid-column: 1;
        grid-row: 1;
        display: flex;
        align-items: center;
        gap: 4px;
        font-weight: 700;
        font-size: 11px;
        letter-spacing: 0;
    }

    .trade-direction svg {
        flex-shrink: 0;
    }

    .trade-pnl-minimal {
        grid-column: 2;
        grid-row: 1;
        font-weight: 700;
        font-size: 13px;
        text-align: right;
        justify-self: end;
        letter-spacing: 0;
    }

    .trade-time-minimal {
        grid-column: 1 / 3;
        grid-row: 2;
        font-size: 10px;
        color: hsl(var(--muted-foreground) / 0.8);
        font-weight: 500;
        letter-spacing: 0;
    }

    .close-btn-minimal {
        grid-column: 4;
        grid-row: 1 / 3;
        align-self: center;
        padding: 0;
        background: hsl(var(--destructive) / 0.15);
        color: hsl(var(--destructive));
        border: 1px solid hsl(var(--destructive) / 0.3);
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        line-height: 1;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close-btn-minimal:hover:not(:disabled) {
        background: hsl(var(--destructive));
        color: hsl(var(--destructive-foreground));
        border-color: hsl(var(--destructive));
        transform: scale(1.05);
    }

    .close-btn-minimal:active:not(:disabled) {
        transform: scale(0.95);
    }

    .close-btn-minimal:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .text-success {
        color: hsl(var(--success));
    }
    .text-destructive {
        color: hsl(var(--destructive));
    }

    /* Candle Tooltip */
    .candle-tooltip {
        position: absolute;
        background: hsl(var(--background));
        border: 1px solid hsl(var(--border));
        border-radius: 6px;
        padding: 8px 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        font-size: 11px;
    }

    .candle-tooltip.is-mobile {
        font-size: 10px;
        padding: 6px 10px;
    }

    .candle-tooltip-header {
        font-weight: 600;
        font-size: 12px;
        margin-bottom: 6px;
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
        opacity: 0.8;
    }

    /* Crosshair Labels */
    .crosshair-price-label {
        position: absolute;
        right: 8px;
        transform: translateY(-50%);
        background: hsl(var(--background));
        border: 1px solid hsl(var(--border));
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: 500;
        color: hsl(var(--foreground));
        z-index: 999;
    }

    .crosshair-price-label.is-mobile {
        font-size: 10px;
        padding: 1px 4px;
    }

    .time-label {
        position: absolute;
        bottom: 8px;
        background: hsl(var(--background));
        border: 1px solid hsl(var(--border));
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 11px;
        font-weight: 500;
        color: hsl(var(--foreground));
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
        background: hsl(var(--background));
        z-index: 998;
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
    }

    .badge-count {
        position: absolute;
        top: -6px;
        right: -6px;
        background: hsl(217.2 91.2% 59.8%);
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

        .active-trades-list {
            width: 240px;
            top: 8px;
            left: 8px;
        }

        .active-trades-scroll-wrapper {
            max-height: 180px;
            padding: 6px;
        }

        .active-trade-item-minimal {
            padding: 8px 10px;
            margin-bottom: 6px;
        }

        .trade-direction {
            font-size: 10px;
        }

        .trade-pnl-minimal {
            font-size: 12px;
        }

        .trade-time-minimal {
            font-size: 9px;
        }

        .close-btn-minimal {
            width: 24px;
            height: 24px;
            font-size: 12px;
        }
    }
</style>
