<script setup lang="ts">
    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { ArrowDown, ArrowUp, Maximize2, Radio, ZoomIn, ZoomOut } from 'lucide-vue-next';
    import { OpenTrade, TradeWithPnL, useChartStore } from '@/stores/chartStore';
    import { router } from '@inertiajs/vue3';
    import type { CandlestickData, IChartApi, ISeriesApi, MouseEventParams } from 'lightweight-charts';
    import { createChart } from 'lightweight-charts';
    import seedrandom from 'seedrandom';

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
        baseFlagUrl?: string
        quoteFlagUrl?: string
    }

    const props = withDefaults(defineProps<Props>(), {
        pair: 'EUR/USD',
        price: 1.085,
        change: 0,
        openTrades: () => [],
        baseFlagUrl: '',
        quoteFlagUrl: ''
    })

    const emit = defineEmits<{
        'update:pair': [pair: string]
    }>()

    const chartStore = useChartStore()
    const chartContainer = ref<HTMLDivElement | null>(null)
    const isMobile = ref(false)
    const isDark = ref(true)

    let chart: IChartApi | null = null
    let candlestickSeries: ISeriesApi<'Candlestick'> | null = null
    let currentPriceLineId: string | null = null
    let tradePriceLineIds = ref<Map<number, string>>(new Map())
    let dataSet = ref(false)
    let tickInterval: ReturnType<typeof setInterval> | null = null
    let tradeExpiryCheckInterval: ReturnType<typeof setInterval> | null = null
    let resizeObserver: ResizeObserver | null = null
    let closingTrades = ref<Set<number>>(new Set())
    let streamingDataProvider: Generator<CandlestickData<number>, void, unknown> | null = null

    const TICK_MS = 1500
    const UPDATES_PER_CANDLE = 20
    const CANDLE_INTERVAL_MS = TICK_MS * UPDATES_PER_CANDLE
    const TRADE_EXPIRY_CHECK_INTERVAL = 1000
    const CLOSE_BUTTON_DISABLE_THRESHOLD = 30000

    const simulationPhase = ref(0)
    const trendBias = ref(0)
    const volatilityMultiplier = ref(1.0)

    const upColorLW = computed(() => isDark.value ? '#26a69a' : '#10b981')
    const downColorLW = computed(() => isDark.value ? '#ef5350' : '#ef4444')
    const crosshairColorLW = computed(() => isDark.value ? '#9ca3af' : '#6b7280')
    const gridColor = computed(() => isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)')

    const upColor = computed(() => upColorLW.value)
    const downColor = computed(() => downColorLW.value)

    const candles = computed(() => chartStore.currentPairData?.candles || [])
    const currentPrice = computed(() => chartStore.currentPairData?.currentPrice || parseFloat(String(props.price)) || 1.085)

    const candleTooltip = ref<{ visible: boolean; x: number; y: number; data: any; snappedX: number }>({
        visible: false, x: 0, y: 0, data: null, snappedX: 0
    })
    const crossPriceLabel = ref<{ visible: boolean; y: number; price: string }>({
        visible: false, y: 0, price: ''
    })
    const timeLabel = ref<{ visible: boolean; x: number; text: string; width: number }>({
        visible: false, x: 0, text: '', width: 0
    })

    const displayPrecision = computed(() => getPricePrecision(props.pair))

    const activePairTrades = computed(() => {
        return chartStore.openTrades.filter(t => t.pair === props.pair)
    })

    const totalPnL = computed(() => {
        return activePairTrades.value.reduce((sum, trade) => sum + (trade.pnl || 0), 0)
    })

    const isCloseButtonDisabled = (tradeId: number): boolean => {
        const remainingTime = tradeRemainingTimes.value.get(tradeId) || 0
        return remainingTime <= CLOSE_BUTTON_DISABLE_THRESHOLD || closingTrades.value.has(tradeId)
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

    const generateSessionSeed = (pair: string): string => {
        const now = new Date()
        const sessionTime = Math.floor(now.getTime() / 60000) * 60000
        return `${pair}-${sessionTime}`
    }

    let seededRNG: () => number

    const initializeSeededRNG = (pair: string) => {
        const seed = generateSessionSeed(pair)
        seededRNG = seedrandom(seed)
    }

    const closeTrade = (tradeId: number, isAutoClose: boolean = false, reason: string = '') => {
        if (closingTrades.value.has(tradeId)) {
            return
        }
        const trade = props.openTrades?.find(t => t.id === tradeId)
        if (!trade) return

        closingTrades.value.add(tradeId)
        const lineId = tradePriceLineIds.value.get(tradeId)

        if (candlestickSeries) {
            [lineId].forEach(id => {
                if (id) {
                    try {
                        candlestickSeries!.removePriceLine(id)
                    } catch (e) {
                        console.warn('Failed to remove price line:', e)
                    }
                }
            })
        }

        tradePriceLineIds.value.delete(tradeId)
        tradeRemainingTimes.value.delete(tradeId)

        chartStore.setOpenTrades(chartStore.openTrades.filter(t => t.id !== tradeId))

        const pnl = calculatePnL(trade, currentPrice.value)
        const exitPrice = roundPrice(currentPrice.value, props.pair)
        const closeData = {
            exit_price: exitPrice,
            pnl: parseFloat(pnl.toFixed(2)),
            closed_at: new Date().toISOString(),
            is_auto_close: isAutoClose,
            close_reason: reason
        }

        router.patch(route('user.trade.forex.close', { trade: tradeId }), closeData, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                closingTrades.value.delete(tradeId)
            },
            onError: () => {
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

    const tradeRemainingTimes = ref<Map<number, number>>(new Map())

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

                const remainingTime = Math.max(0, expiryTime - now)
                tradeRemainingTimes.value.set(trade.id, remainingTime)

                if (remainingTime <= 0) {
                    closeTrade(trade.id, true, 'Trade Expired')
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

        if (chartStore.hasPairData && chartStore.currentPairData.currentPrice > 0) {
        } else {
            chartStore.updateCurrentPrice(props.pair, parsedPrice)
        }

        chartStore.openTrades.forEach(t => {
            if (t.pair === props.pair) {
                chartStore.updateTradePnL(t.id, currentPrice.value)
            }
        })
    }

    function* createRealtimeTickGenerator(historicalCandles: Candle[]): Generator<CandlestickData<number>, void, unknown> {
        const random = seededRNG

        let initialCandle: Candle;
        let avgVolatility: number;

        const storedCurrentPrice = chartStore.currentPairData?.currentPrice || 0

        if (historicalCandles.length > 0) {
            initialCandle = historicalCandles[historicalCandles.length - 1];

            if (storedCurrentPrice > 0) {
                initialCandle = {
                    ...initialCandle,
                    close: storedCurrentPrice,
                    high: Math.max(initialCandle.high, storedCurrentPrice),
                    low: Math.min(initialCandle.low, storedCurrentPrice)
                }
            }

            const last20Candles = historicalCandles.slice(-20);
            const totalRange = last20Candles.reduce((acc, c) => acc + (c.high - c.low), 0);
            avgVolatility = totalRange / last20Candles.length;
            if (avgVolatility <= 0) {
                avgVolatility = initialCandle.close * 0.0005;
            }
        } else {
            const basePrice = storedCurrentPrice > 0 ? storedCurrentPrice : (parseFloat(String(props.price)) || 1.085)
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

        while (true) {
            const newCandleTime = lastTime + (CANDLE_INTERVAL_MS / 1000)

            const phaseProgress = (simulationPhase.value % 100) / 100
            const trendStrength = Math.sin(phaseProgress * Math.PI * 2) * 0.3
            const currentTrendBias = trendBias.value + trendStrength

            const candleVolatilityMultiplier = 0.5 + random() * 1.5
            const thisCandleTargetRange = avgVolatility * candleVolatilityMultiplier * volatilityMultiplier.value
            const tickVolatility = thisCandleTargetRange > 0
                ? (thisCandleTargetRange / (UPDATES_PER_CANDLE / 2))
                : (lastClose * 0.0001)

            let currentCandle: CandlestickData<number> = {
                time: newCandleTime,
                open: lastClose,
                high: lastClose,
                low: lastClose,
                close: lastClose,
            }

            for (let i = 0; i < UPDATES_PER_CANDLE; i++) {
                const u1 = random()
                const u2 = random()
                const gaussianNoise = Math.sqrt(-2.0 * Math.log(u1)) * Math.cos(2.0 * Math.PI * u2)

                const trendComponent = currentTrendBias * tickVolatility * 0.5
                const noiseComponent = gaussianNoise * tickVolatility

                let tickMovement = trendComponent + noiseComponent

                if (Math.abs(currentCandle.close - currentCandle.open) > thisCandleTargetRange * 0.7) {
                    tickMovement -= (currentCandle.close - currentCandle.open) * 0.1
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
            simulationPhase.value++
        }
    }

    const prepareSimulationFromBackend = () => {
        initializeSeededRNG(props.pair)
        streamingDataProvider = createRealtimeTickGenerator(candles.value)
    }

    const mapToLWData = (candle: Candle): CandlestickData<number> => ({
        time: Math.floor(candle.time / 1000),
        open: candle.open,
        high: candle.high,
        low: candle.low,
        close: candle.close
    })

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

            const tooltipWidth = isMobile.value ? 150 : 190
            const tooltipHeight = 80

            if (candleTooltip.value.x + tooltipWidth > width) {
                candleTooltip.value.x = param.point.x - tooltipWidth - 10
            }
            if (candleTooltip.value.y < tooltipHeight) {
                candleTooltip.value.y = param.point.y + 10
            }

            crossPriceLabel.value = {
                visible: true,
                y: param.point.y,
                price: logicalPrice.toFixed(displayPrecision.value)
            }

            const timeText = isMobile.value
                ? new Date(param.time * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                : new Date(param.time * 1000).toLocaleString([], {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })

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

        if (isMobile.value) {
            if (hours > 0) {
                return `${hours}h ${minutes}m`
            }
            return `${minutes}m ${seconds}s`
        }

        if (hours > 0) {
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
        }
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
    }

    const getTradeTimeText = (trade: TradeWithPnL) => {
        const remainingTime = tradeRemainingTimes.value.get(trade.id) || 0
        return formatRemainingTime(remainingTime)
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
        const width = window.innerWidth
        isMobile.value = width <= 768

        if (chart && chartContainer.value) {
            const containerWidth = chartContainer.value.clientWidth
            const containerHeight = chartContainer.value.clientHeight
            if (containerWidth > 0 && containerHeight > 0) {
                chart.resize(containerWidth, containerHeight)
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
                    attributionLogo: false,
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
                    width: isMobile.value ? 50 : 70,
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

            nextTick(() => {
                if (candles.value.length > 0 && candlestickSeries) {
                    const lwData = candles.value.map(mapToLWData)
                    candlestickSeries.setData(lwData)
                    dataSet.value = true
                    fitToScreen()
                }
                drawTradeLines()
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
                        lineWidth: isMobile.value ? 1 : 2,
                        lineStyle: 0,
                        axisLabelVisible: !isMobile.value,
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

        initializeSeededRNG(props.pair)

        initPrice()

        checkExpiredTrades()

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
                    }
                } catch (error) {
                    console.warn('Resize observer error:', error)
                }
            }
        })

        if (chartContainer.value) {
            resizeObserver.observe(chartContainer.value)
        }

        watch(() => props.openTrades, () => {
            drawTradeLines()
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

        initializeSeededRNG(newPair)

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
    })

    onBeforeUnmount(() => {
        if (tickInterval) {
            clearInterval(tickInterval)
            tickInterval = null
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
    <div class="relative w-full h-full bg-background overflow-hidden flex flex-col">
        <div class="flex flex-row justify-between items-center px-2 sm:px-4 py-2 bg-background border-b border-border shrink-0 gap-2">
            <div class="flex flex-col">
                <div class="flex items-center gap-3">
                    <div v-if="baseFlagUrl && quoteFlagUrl" class="flex-shrink-0 flex items-center">
                        <img :src="baseFlagUrl"
                             :alt="pair.split('/')[0]"
                             class="w-8 h-8 object-cover border-border border-2 rounded-full z-10 flex-shrink-0"
                        >
                        <img :src="quoteFlagUrl"
                             :alt="pair.split('/')[1]"
                             class="w-8 h-8 object-cover border-border border-2 rounded-full -ml-1.5 flex-shrink-0"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <h2 class="mb-0 text-sm sm:text-xl font-semibold text-foreground">{{ pair }}</h2>
                        <div class="flex items-center gap-1 px-1.5 sm:px-2 py-0.5 sm:py-1 bg-secondary rounded-lg border border-border sm:hidden">
                            <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-success flex-shrink-0"></span>
                            <span class="text-[8px] sm:text-[10px] font-medium text-muted-foreground">Live</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-0.5 sm:mt-1">
                    <span class="text-xs sm:text-base font-semibold text-foreground">{{ currentPrice.toFixed(displayPrecision) }}</span>
                    <span
                        class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-medium"
                        :class="isBullish ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'">
                        {{ displayChange >= 0 ? '+' : '' }}{{ displayChange.toFixed(displayPrecision) }}
                        ({{ changePercentage }}%)
                    </span>
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-2">
                <div class="flex items-center gap-1 px-2 py-1 bg-secondary rounded-xl border border-border">
                    <span class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-success flex-shrink-0"></span>
                    <span class="text-[10px] sm:text-xs font-medium text-muted-foreground whitespace-nowrap">
                        Live
                    </span>
                </div>

                <button @click="zoomIn" class="flex items-center justify-center w-8 h-8 bg-secondary border border-border rounded-xl cursor-pointer transition-all duration-200 hover:bg-muted" title="Zoom In">
                    <ZoomIn :size="16" />
                </button>

                <button @click="zoomOut" class="flex items-center justify-center w-8 h-8 bg-secondary border border-border rounded-xl cursor-pointer transition-all duration-200 hover:bg-muted" title="Zoom Out">
                    <ZoomOut :size="16" />
                </button>

                <button @click="fitToScreen" class="flex items-center justify-center w-8 h-8 bg-secondary border border-border rounded-xl cursor-pointer transition-all duration-200 hover:bg-muted" title="Fit to Screen">
                    <Maximize2 :size="16" />
                </button>

                <button @click="jumpToLive" class="flex items-center justify-center w-8 h-8 bg-secondary border border-border rounded-xl cursor-pointer transition-all duration-200 hover:bg-muted" title="Jump to Live">
                    <Radio :size="16" />
                </button>
            </div>

            <div class="flex sm:hidden items-center gap-1">
                <div class="flex items-center gap-1">
                    <button @click="zoomIn" class="flex items-center justify-center w-7 h-7 bg-secondary border border-border rounded-lg" title="Zoom In">
                        <ZoomIn :size="14" />
                    </button>

                    <button @click="zoomOut" class="flex items-center justify-center w-7 h-7 bg-secondary border border-border rounded-lg" title="Zoom Out">
                        <ZoomOut :size="14" />
                    </button>

                    <button @click="fitToScreen" class="flex items-center justify-center w-7 h-7 bg-secondary border border-border rounded-lg" title="Fit">
                        <Maximize2 :size="14" />
                    </button>

                    <button @click="jumpToLive" class="flex items-center justify-center w-7 h-7 bg-secondary border border-border rounded-lg" title="Live">
                        <Radio :size="14" />
                    </button>
                </div>
            </div>
        </div>

        <div class="relative w-full flex-1 min-h-[250px] sm:min-h-[300px]">
            <div v-if="activePairTrades.length > 0"
                 class="absolute top-2 left-2 bg-gradient-to-br from-background to-background/95 backdrop-blur-md border border-border/50 rounded-lg shadow-md z-[990] w-[200px] sm:w-[280px] overflow-hidden text-xs">
                <div class="p-2 border-b border-border/50 bg-background/50">
                    <h3 class="font-semibold mb-1 text-foreground text-xs sm:text-sm">Active Trades</h3>
                    <div class="flex items-center justify-between text-[10px] sm:text-xs text-muted-foreground">
                        <span>{{ activePairTrades.length }} open</span>
                        <span :class="totalPnL >= 0 ? 'text-success font-medium' : 'text-destructive font-medium'">
                            {{ totalPnL >= 0 ? '+' : '' }}{{ totalPnL.toFixed(2) }}
                        </span>
                    </div>
                </div>

                <div class="max-h-[150px] sm:max-h-[220px] overflow-y-auto no-scrollbar">
                    <div v-for="trade in activePairTrades" :key="trade.id"
                         class="flex items-center justify-between gap-1 sm:gap-2 p-1.5 sm:p-2 border-b">
                        <div class="flex items-center gap-1 sm:gap-2 flex-1 min-w-0">
                            <div class="flex flex-col items-center gap-0.5 text-xs font-bold text-current shrink-0 w-6 sm:w-8">
                                <ArrowUp v-if="trade.type === 'Up'" class="w-3 h-3 sm:w-4 sm:h-4" />
                                <ArrowDown v-else class="w-3 h-3 sm:w-4 sm:h-4" />
                                <span class="text-[8px] sm:text-[10px] uppercase">{{ trade.type }}</span>
                            </div>

                            <div class="flex flex-col min-w-0">
                                <span class="font-medium text-foreground text-[10px] sm:text-xs truncate">
                                    {{ trade.entry_price.toFixed(displayPrecision) }}
                                </span>

                                <span class="text-[8px] sm:text-[10px] text-muted-foreground">
                                    ${{ trade.amount }} x {{ trade.leverage || 1 }}
                                </span>
                            </div>
                        </div>

                        <div class="text-right min-w-[50px] sm:min-w-[60px] flex-shrink-0">
                            <div class="text-xs sm:text-sm font-bold" :class="pnlColor(trade)">
                                {{ trade.pnl >= 0 ? '+' : '' }}{{ trade.pnl?.toFixed(2) }}
                            </div>

                            <div class="text-[8px] sm:text-[10px] text-muted-foreground mt-0.5">
                                {{ getTradeTimeText(trade) }}
                            </div>
                        </div>

                        <button
                            @click="closeTrade(trade.id)"
                            class="p-1 sm:p-1.5 bg-destructive/10 text-destructive border border-destructive/30 rounded-md cursor-pointer text-xs sm:text-sm font-semibold transition-all duration-200 hover:bg-destructive hover:text-destructive-foreground disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                            :disabled="isCloseButtonDisabled(trade.id)"
                            :title="isCloseButtonDisabled(trade.id) && tradeRemainingTimes.get(trade.id) > 0 ? 'Cannot close in last 30s' : 'Close'">
                            {{ closingTrades.has(trade.id) ? '...' : '✕' }}
                        </button>
                    </div>
                </div>
            </div>

            <div ref="chartContainer" class="w-full h-full min-h-0" />

            <div v-if="candleTooltip.visible"
                 class="absolute bg-background border border-border rounded-md px-2 sm:px-3 py-1 sm:py-2 shadow-md z-[1000] text-[9px] sm:text-xs"
                 :style="{
                    left: candleTooltip.x + 'px',
                    top: candleTooltip.y + 'px'
                }">
                <div class="font-semibold text-[10px] sm:text-sm mb-1 text-foreground">
                    <span>{{ pair }}</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <div class="flex justify-between gap-2 text-[9px] sm:text-xs opacity-80">
                        <span class="text-muted-foreground">O:</span>
                        <span class="text-foreground">{{ candleTooltip.data?.open?.toFixed(displayPrecision) }}</span>
                    </div>
                    <div class="flex justify-between gap-2 text-[9px] sm:text-xs opacity-80">
                        <span class="text-muted-foreground">H:</span>
                        <span class="text-foreground">{{ candleTooltip.data?.high?.toFixed(displayPrecision) }}</span>
                    </div>
                    <div class="flex justify-between gap-2 text-[9px] sm:text-xs opacity-80">
                        <span class="text-muted-foreground">L:</span>
                        <span class="text-foreground">{{ candleTooltip.data?.low?.toFixed(displayPrecision) }}</span>
                    </div>
                    <div class="flex justify-between gap-2 text-[9px] sm:text-xs opacity-80">
                        <span class="text-muted-foreground">C:</span>
                        <span class="text-foreground">{{ candleTooltip.data?.close?.toFixed(displayPrecision) }}</span>
                    </div>
                </div>
            </div>

            <div v-if="crossPriceLabel.visible"
                 class="absolute right-2 -translate-y-1/2 bg-background border border-border rounded px-1 py-0.5 text-[9px] sm:text-xs font-medium text-foreground z-[999]"
                 :style="{ top: crossPriceLabel.y + 'px' }">
                {{ crossPriceLabel.price }}
            </div>

            <div v-if="timeLabel.visible"
                 class="absolute bottom-2 bg-background border border-border rounded px-1 py-0.5 text-[9px] sm:text-xs font-medium text-foreground z-[999]"
                 :style="{ left: timeLabel.x - timeLabel.width / 2 + 'px' }">
                {{ timeLabel.text }}
            </div>
        </div>
    </div>
</template>

<style scoped>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    @media (max-width: 768px) {
        * {
            -webkit-tap-highlight-color: transparent;
        }

        button {
            touch-action: manipulation;
        }
    }
</style>
