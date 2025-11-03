<script setup lang="ts">
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
    import { ZoomOut, ZoomIn, Maximize2, Radio, RotateCcw } from 'lucide-vue-next'
    import { useChartStore, OpenTrade, TradeWithPnL } from '@/stores/chartStore'
    import { router } from '@inertiajs/vue3'

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
        low?: number | string
        high?: number | string
        volume?: number | string
        openTrades?: OpenTrade[]
    }

    const props = withDefaults(defineProps<Props>(), {
        pair: 'EUR/USD',
        price: 1.085,
        change: 0,
        low: 0,
        high: 0,
        volume: 0,
        openTrades: () => []
    })

    const emit = defineEmits<{
        'update:pair': [pair: string]
    }>()

    const chartStore = useChartStore()

    const container = ref<HTMLElement | null>(null)
    const canvas = ref<HTMLCanvasElement | null>(null)
    const isDark = ref(true)
    const crosshair = ref({ x: -1, y: -1, visible: false })
    const isMobile = ref(false)

    const zoom = computed({
        get: () => chartStore.zoom,
        set: (val) => chartStore.setZoom(val)
    })

    const panX = computed({
        get: () => chartStore.panX,
        set: (val) => chartStore.setPanX(val)
    })

    const externalChange = ref(0)
    const externalLow = ref(0)
    const externalHigh = ref(0)
    const externalVolume = ref(0)
    const lastPrice = ref(0)

    let animationId: number | null = null
    let tickInterval: ReturnType<typeof setInterval> | null = null
    let healthCheckInterval: ReturnType<typeof setInterval> | null = null
    let tradeExpiryCheckInterval: ReturnType<typeof setInterval> | null = null
    let resizeObserver: ResizeObserver | null = null
    let dragging = false
    let dragStartX = 0
    let panStart = 0
    let velocity = 0
    let lastDragTime = 0
    let lastDragX = 0

    let touchStartDistance = 0
    let touchStartZoom = 0
    let touchStartX = 0

    const isTouching = ref(false)
    const isDragging = ref(false)
    const touchStartPos = ref({ x: 0, y: 0 })

    const CANDLE_SPACING = 15
    const MIN_CANDLE_BODY = 1.5
    const BASE_TICK_MS = 200
    const TICK_MS = 1500
    const AXIS_SPACE_Y = 100
    const TOP_SPACE = 25
    const BOTTOM_SPACE = 50
    const TICKS_PER_CANDLE_TARGET = 12
    const CANDLE_INTERVAL_MS = TICK_MS * TICKS_PER_CANDLE_TARGET
    const FRICTION = 0.95
    const VELOCITY_THRESHOLD = 0.5
    const HEALTH_CHECK_INTERVAL = 30000
    const TRADE_EXPIRY_CHECK_INTERVAL = 1000
    const VOLATILITY_BASE = 0.00008
    const CLOSE_DISABLE_THRESHOLD = 10000
    const HIT_AREA = 20

    const bgColor = computed(() => (isDark.value ? '#1a1a1a4d' : '#ffffff'))
    const gridColor = computed(() => isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)')
    const textColor = computed(() => (isDark.value ? '#e5e5e5' : '#1f2937'))
    const upColor = '#26a69a'
    const downColor = '#ef5350'
    const crosshairColor = '#9ca3af'

    const AXIS_SPACE_X = computed(() => isMobile.value ? 0 : 60)

    const candles = computed(() => chartStore.currentPairData?.candles || [])
    const currentPrice = computed(() => chartStore.currentPairData?.currentPrice || 0)
    const lastCandleTime = computed(() => chartStore.currentPairData?.lastCandleTime || Date.now())

    const hoveredTrades = ref<TradeWithPnL[]>([])
    const tooltipPos = ref<{ x: number; y: number }>({ x: -1, y: -1 })
    const tooltipCloseRects = ref<{ tradeId: number, x: number, y: number, w: number, h: number }[]>([])
    const tooltipRects = ref<{ tradeId: number, x: number, y: number, w: number, h: number }[]>([])

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

    function getPricePrecision(pair: string): number {
        const symbol = pair.toUpperCase();
        if (symbol.includes('JPY')) {
            return 3;
        }
        return 5;
    }

    function roundPrice(price: number, pair: string): number {
        const precision = getPricePrecision(pair);
        const factor = Math.pow(10, precision);
        return Math.round(price * factor) / factor;
    }

    const validateChartState = (): boolean => {
        if (!isFinite(zoom.value) || zoom.value <= 0) {
            chartStore.resetView()
            return false
        }

        if (!isFinite(panX.value)) {
            chartStore.setPanX(0)
            return false
        }

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

    const closeTrade = (tradeId: number, isAutoClose: boolean = false) => {
        const trade = props.openTrades?.find(t => t.id === tradeId)
        if (!trade) return

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
                chartStore.setOpenTrades(chartStore.openTrades.filter(t => t.id !== tradeId))
                hoveredTrades.value = hoveredTrades.value.filter(t => t.id !== tradeId)
            },
            onError: (errors) => {
                console.error('Failed to close trade:', errors)
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

        props.openTrades?.forEach(trade => {
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

        externalChange.value = parseFloat(String(props.change)) || 0
        externalLow.value = parseFloat(String(props.low)) || 0
        externalHigh.value = parseFloat(String(props.high)) || 0
        externalVolume.value = parseFloat(String(props.volume)) || 0

        if (!chartStore.hasPairData) {
            chartStore.initializePairData(props.pair, parsedPrice)
            lastPrice.value = parsedPrice
        } else {
            chartStore.updateCurrentPrice(props.pair, parsedPrice)
            lastPrice.value = chartStore.currentPairData?.currentPrice || parsedPrice
        }

        chartStore.openTrades.forEach(t => {
            if (t.pair === props.pair) {
                chartStore.updateTradePnL(t.id, chartStore.currentPrice)
            }
        })
    }

    const generateCandles = (count = 100) => {
        if (chartStore.hasPairData && candles.value.length > 0) {
            if (container.value) autoPan(container.value.clientWidth)
            return
        }

        const data: Candle[] = []
        let price = currentPrice.value

        if (!isFinite(price) || price <= 0) {
            return
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

        if (container.value) autoPan(container.value.clientWidth)
    }

    const getChartMetrics = (width: number) => {
        const candleWidth = CANDLE_SPACING * zoom.value
        const chartWidth = Math.max(100, width - AXIS_SPACE_X.value - AXIS_SPACE_Y)
        const totalCandles = candles.value.length
        const totalWidth = totalCandles * candleWidth
        const maxPan = Math.max(0, totalWidth - chartWidth + 20)
        return { candleWidth, chartWidth, totalWidth, maxPan }
    }

    const autoPan = (containerWidth: number) => {
        const { totalWidth, chartWidth, maxPan } = getChartMetrics(containerWidth)
        const targetPan = Math.max(0, totalWidth - chartWidth + 20)
        panX.value = Math.min(maxPan, Math.max(0, targetPan))
    }

    const jumpToLive = () => {
        velocity = 0
        if (container.value) autoPan(container.value.clientWidth)
    }

    const fitToScreen = () => {
        zoom.value = 1.0
        velocity = 0
        if (container.value) autoPan(container.value.clientWidth)
    }

    const zoomIn = () => {
        velocity = 0
        zoom.value = Math.min(3, zoom.value * 1.2)
        if (container.value) {
            const { maxPan } = getChartMetrics(container.value.clientWidth)
            panX.value = Math.min(maxPan, panX.value)
        }
    }

    const zoomOut = () => {
        velocity = 0
        zoom.value = Math.max(0.2, zoom.value / 1.2)
        if (container.value) autoPan(container.value.clientWidth)
    }

    const resetView = () => {
        chartStore.resetView()
        velocity = 0
        if (container.value) autoPan(container.value.clientWidth)
    }

    const startTicking = () => {
        tickInterval = setInterval(() => {
            if (!validateChartState()) {
                return
            }

            lastPrice.value = currentPrice.value
            const volatilityScaleFactor = TICK_MS / BASE_TICK_MS
            const change = (Math.random() - 0.5) * VOLATILITY_BASE * currentPrice.value * volatilityScaleFactor

            let newPrice = roundPrice(currentPrice.value + change, props.pair)

            if (!isFinite(newPrice) || newPrice <= 0) {
                newPrice = currentPrice.value
                return
            }

            chartStore.updateCurrentPrice(props.pair, newPrice)
            const now = Date.now()

            chartStore.openTrades.forEach(t => {
                if (t.pair === props.pair) {
                    chartStore.updateTradePnL(t.id, newPrice)
                }
            })

            if (now - lastCandleTime.value >= CANDLE_INTERVAL_MS) {
                const lastClose = newPrice
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
                if (!dragging && container.value) autoPan(container.value.clientWidth)
            } else if (candles.value.length) {
                const lastCandle = candles.value[candles.value.length - 1]
                const newVolume = lastCandle.volume + Math.floor(Math.random() * 20)
                chartStore.updateLastCandle(props.pair, {
                    close: newPrice,
                    high: Math.max(lastCandle.high, newPrice),
                    low: Math.min(lastCandle.low, newPrice),
                    volume: isFinite(newVolume) && newVolume >= 0 ? newVolume : lastCandle.volume
                })
            }
        }, TICK_MS)
    }

    const draw = () => {
        if (!canvas.value || !container.value) return

        if (!validateChartState()) {
            return
        }

        const ctx = canvas.value.getContext('2d')
        if (!ctx) return

        const rect = canvas.value.getBoundingClientRect()
        const width = Math.floor(rect.width)
        const canvasHeight = Math.floor(rect.height)
        const dpr = window.devicePixelRatio || 1
        const axisSpaceX = AXIS_SPACE_X.value

        canvas.value.width = Math.max(1, Math.floor(width * dpr))
        canvas.value.height = Math.max(1, Math.floor(canvasHeight * dpr))
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0)

        ctx.clearRect(0, 0, width, canvasHeight)
        ctx.fillStyle = bgColor.value
        ctx.fillRect(0, 0, width, canvasHeight)

        const { candleWidth, chartWidth } = getChartMetrics(width)
        const mainHeight = canvasHeight - BOTTOM_SPACE - TOP_SPACE

        const displayPrecision = getPricePrecision(props.pair)

        const startCandleIdx = Math.max(0, Math.floor(panX.value / candleWidth))
        const maxCandles = Math.ceil(chartWidth / candleWidth) + 2
        const endCandleIdx = Math.min(candles.value.length, startCandleIdx + maxCandles)
        const visible = candles.value.slice(startCandleIdx, endCandleIdx)

        if (!visible.length) return

        const allPrices = visible.flatMap(c => [c.high, c.low])
        const maxP = Math.max(...allPrices, currentPrice.value)
        const minP = Math.min(...allPrices, currentPrice.value)

        if (!isFinite(maxP) || !isFinite(minP)) {
            return
        }

        const range = Math.max(0.0000001, maxP - minP)
        const paddedMax = maxP + range * 0.05
        const paddedMin = minP - range * 0.05
        const priceScale = paddedMax - paddedMin

        const priceToY = (price: number) => {
            const y = TOP_SPACE + mainHeight - ((price - paddedMin) / priceScale) * mainHeight
            return isFinite(y) ? y : TOP_SPACE + mainHeight / 2
        }

        ctx.strokeStyle = gridColor.value
        ctx.fillStyle = textColor.value
        ctx.font = isMobile.value ? '9px Inter, sans-serif' : '11px Inter, sans-serif'
        ctx.lineWidth = 0.5
        ctx.textAlign = isMobile.value ? 'right' : 'left'

        const numHGridLines = isMobile.value ? 4 : 6
        for (let i = 0; i <= numHGridLines; i++) {
            const y = TOP_SPACE + (mainHeight / numHGridLines) * i
            const price = paddedMax - priceScale * (i / numHGridLines)

            if (!isFinite(price)) continue

            ctx.beginPath()
            ctx.moveTo(axisSpaceX, y)
            ctx.lineTo(width - AXIS_SPACE_Y, y)
            ctx.stroke()

            const labelX = isMobile.value ? width - 4 : 8
            ctx.fillText(price.toFixed(displayPrecision), labelX, y + 3)
        }

        const bodyWidth = Math.max(MIN_CANDLE_BODY, candleWidth * 0.7)
        visible.forEach((c, i) => {
            const chartX = (i + startCandleIdx) * candleWidth - panX.value
            const x = axisSpaceX + chartX + candleWidth / 2

            if (x > width - AXIS_SPACE_Y || x < axisSpaceX) return

            const openY = priceToY(c.open)
            const closeY = priceToY(c.close)
            const highY = priceToY(c.high)
            const lowY = priceToY(c.low)

            if (!isFinite(openY) || !isFinite(closeY) || !isFinite(highY) || !isFinite(lowY)) {
                return
            }

            const isUp = c.close >= c.open
            ctx.strokeStyle = isUp ? upColor : downColor
            ctx.lineWidth = 1
            ctx.beginPath()
            ctx.moveTo(x, highY)
            ctx.lineTo(x, lowY)
            ctx.stroke()

            const bodyTop = Math.min(openY, closeY)
            const bodyHeight = Math.max(MIN_CANDLE_BODY, Math.abs(closeY - openY))
            ctx.fillStyle = isUp ? upColor : downColor
            ctx.fillRect(x - bodyWidth / 2, bodyTop, bodyWidth, bodyHeight)
            ctx.lineWidth = 1
            ctx.strokeStyle = isUp ? upColor : downColor
            ctx.strokeRect(x - bodyWidth / 2, bodyTop, bodyWidth, bodyHeight)
        })

        const curY = priceToY(currentPrice.value)
        if (!isFinite(curY)) return

        const currentPriceColor = priceMovementColor.value
        ctx.strokeStyle = currentPriceColor
        ctx.setLineDash([5, 5])
        ctx.beginPath()
        ctx.moveTo(axisSpaceX, curY)
        ctx.lineTo(width - AXIS_SPACE_Y, curY)
        ctx.stroke()
        ctx.setLineDash([])

        ctx.fillStyle = currentPriceColor
        ctx.fillRect(width - AXIS_SPACE_Y, curY - 10, AXIS_SPACE_Y, 20)
        ctx.fillStyle = 'white'
        ctx.font = isMobile.value ? 'bold 10px Inter' : 'bold 12px Inter'
        ctx.textAlign = 'center'
        ctx.fillText(`${currentPrice.value.toFixed(displayPrecision)}`, width - AXIS_SPACE_Y / 2, curY + 4)

        ctx.textAlign = 'center'
        ctx.fillStyle = textColor.value
        ctx.font = isMobile.value ? '8px Inter, sans-serif' : '11px Inter, sans-serif'

        visible.forEach((c, i) => {
            const chartX = (i + startCandleIdx) * candleWidth - panX.value
            const x = axisSpaceX + chartX + candleWidth / 2

            if (isMobile.value ? (i % 20 === 0) : (i % 10 === 0)) {
                ctx.strokeStyle = gridColor.value
                ctx.beginPath()
                ctx.moveTo(x, TOP_SPACE)
                ctx.lineTo(x, TOP_SPACE + mainHeight)
                ctx.stroke()

                const label = new Date(c.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                ctx.fillText(label, x, canvasHeight - 10)
            }
        })

        const trades = chartStore.openTradesForPair
        const entryLineColor = (t: TradeWithPnL) => t.type === 'Up' ? upColor : downColor
        const pnlColor = (t: TradeWithPnL) => t.pnl >= 0 ? upColor : downColor

        const priceGroups: TradeWithPnL[][] = []
        trades.forEach(trade => {
            const y = priceToY(trade.entry_price)
            if (!isFinite(y)) return

            let found = false
            for (const group of priceGroups) {
                const groupY = priceToY(group[0].entry_price)
                if (Math.abs(y - groupY) < 3) {
                    group.push(trade)
                    found = true
                    break
                }
            }
            if (!found) priceGroups.push([trade])
        })

        priceGroups.forEach(group => {
            group.forEach((trade, index) => {
                const y = priceToY(trade.entry_price)
                if (!isFinite(y)) return

                const offset = index * 2
                const adjustedY = y + offset
                const color = entryLineColor(trade)

                ctx.strokeStyle = color
                ctx.lineWidth = 1
                ctx.globalAlpha = 0.4
                ctx.setLineDash([4, 4])
                ctx.beginPath()
                ctx.moveTo(axisSpaceX, adjustedY)
                ctx.lineTo(width - AXIS_SPACE_Y, adjustedY)
                ctx.stroke()
                ctx.setLineDash([])
                ctx.globalAlpha = 1

                const entryText = trade.entry_price.toFixed(displayPrecision)
                ctx.font = isMobile.value ? 'bold 9px Inter' : 'bold 10px Inter'
                const entryWidth = ctx.measureText(entryText).width
                const badgeHeight = isMobile.value ? 16 : 18
                const badgePadding = 6
                const badgeWidth = entryWidth + badgePadding * 2
                const badgeX = axisSpaceX + 4
                const badgeY = adjustedY - badgeHeight / 2

                ctx.fillStyle = color
                ctx.beginPath()
                ctx.roundRect(badgeX, badgeY, badgeWidth, badgeHeight, 3)
                ctx.fill()

                ctx.fillStyle = 'white'
                ctx.textAlign = 'center'
                ctx.fillText(entryText, badgeX + badgeWidth / 2, adjustedY + 4)

                if (group.length > 1 && index === 0) {
                    const countBadgeSize = isMobile.value ? 16 : 18
                    const countX = badgeX + badgeWidth + 4
                    const countY = adjustedY - countBadgeSize / 2

                    ctx.fillStyle = isDark.value ? 'rgba(0,0,0,0.7)' : 'rgba(255,255,255,0.9)'
                    ctx.beginPath()
                    ctx.roundRect(countX, countY, countBadgeSize, countBadgeSize, 3)
                    ctx.fill()

                    ctx.strokeStyle = color
                    ctx.lineWidth = 1.5
                    ctx.stroke()

                    ctx.fillStyle = color
                    ctx.font = 'bold 10px Inter'
                    ctx.textAlign = 'center'
                    ctx.fillText(group.length.toString(), countX + countBadgeSize / 2, adjustedY + 4)
                }

                const totalValue = trade.amount + trade.pnl
                const pnlText = `${totalValue.toFixed(2)}`
                const pnlColorValue = pnlColor(trade)
                ctx.font = isMobile.value ? 'bold 9px Inter' : 'bold 10px Inter'
                const pnlWidth = ctx.measureText(pnlText).width
                const pnlBadgeWidth = pnlWidth + badgePadding * 2
                const pnlBadgeX = width - AXIS_SPACE_Y - pnlBadgeWidth - 4
                const pnlBadgeY = adjustedY - badgeHeight / 2

                ctx.fillStyle = isDark.value ? 'rgba(0,0,0,0.75)' : 'rgba(255,255,255,0.95)'
                ctx.beginPath()
                ctx.roundRect(pnlBadgeX, pnlBadgeY, pnlBadgeWidth, badgeHeight, 3)
                ctx.fill()

                ctx.strokeStyle = pnlColorValue
                ctx.lineWidth = 1.5
                ctx.stroke()

                ctx.fillStyle = pnlColorValue
                ctx.textAlign = 'center'
                ctx.fillText(pnlText, pnlBadgeX + pnlBadgeWidth / 2, adjustedY + 4)
            })
        })

        if (hoveredTrades.value.length > 0 && tooltipPos.value.x >= 0 && tooltipPos.value.y >= 0) {
            tooltipCloseRects.value = []
            tooltipRects.value = []

            const padding = isMobile.value ? 10 : 12
            const headerHeight = isMobile.value ? 28 : 32
            const rowHeight = isMobile.value ? 22 : 24
            const separatorHeight = 1
            const footerHeight = isMobile.value ? 36 : 40

            const tooltipWidth = isMobile.value ? 200 : 240
            const tooltipHeight = headerHeight + rowHeight * 3 + separatorHeight + footerHeight
            const tooltipSpacing = isMobile.value ? 6 : 8

            const totalTooltipsHeight = hoveredTrades.value.length * tooltipHeight + (hoveredTrades.value.length - 1) * tooltipSpacing
            const availableHeight = canvasHeight - TOP_SPACE - BOTTOM_SPACE - 20

            let startY: number
            if (totalTooltipsHeight > availableHeight) {
                startY = TOP_SPACE + 10
            } else {
                startY = tooltipPos.value.y - totalTooltipsHeight / 2
                if (startY < TOP_SPACE + 10) startY = TOP_SPACE + 10
                if (startY + totalTooltipsHeight > canvasHeight - BOTTOM_SPACE - 10) {
                    startY = canvasHeight - BOTTOM_SPACE - 10 - totalTooltipsHeight
                }
            }

            hoveredTrades.value.forEach((t, index) => {
                const tradeColor = t.type === 'Up' ? upColor : downColor
                const pnlColorValue = pnlColor(t)

                let remainingTimeMs = 0;
                let expiryTime: number = 0;
                const now = Date.now();
                const openedAt = new Date(t.opened_at).getTime();

                if ('expiry_time' in t && t.expiry_time) {
                    expiryTime = new Date(t.expiry_time).getTime();
                } else if ('duration' in t && t.duration) {
                    const durationMs = parseDurationToMs(t.duration);
                    expiryTime = openedAt + durationMs;
                } else {
                    expiryTime = openedAt + (5 * 60 * 1000);
                }

                remainingTimeMs = Math.max(0, expiryTime - now);
                const timeText = formatRemainingTime(remainingTimeMs);
                const isExpired = remainingTimeMs === 0;

                let tooltipX = tooltipPos.value.x + 12
                if (tooltipX + tooltipWidth > width - 20) {
                    tooltipX = tooltipPos.value.x - tooltipWidth - 12
                }
                if (tooltipX < 20) tooltipX = 20

                const tooltipY = startY + index * (tooltipHeight + tooltipSpacing)

                tooltipRects.value.push({ tradeId: t.id, x: tooltipX, y: tooltipY, w: tooltipWidth, h: tooltipHeight });

                ctx.shadowColor = 'rgba(0,0,0,0.4)'
                ctx.shadowBlur = 12
                ctx.shadowOffsetX = 0
                ctx.shadowOffsetY = 4

                const gradient = ctx.createLinearGradient(tooltipX, tooltipY, tooltipX, tooltipY + tooltipHeight)
                if (isDark.value) {
                    gradient.addColorStop(0, 'rgba(30,30,35,0.98)')
                    gradient.addColorStop(1, 'rgba(25,25,30,0.98)')
                } else {
                    gradient.addColorStop(0, 'rgba(255,255,255,0.98)')
                    gradient.addColorStop(1, 'rgba(248,248,250,0.98)')
                }
                ctx.fillStyle = gradient
                ctx.beginPath()
                ctx.roundRect(tooltipX, tooltipY, tooltipWidth, tooltipHeight, 8)
                ctx.fill()

                ctx.strokeStyle = tradeColor
                ctx.lineWidth = 2
                ctx.stroke()

                ctx.shadowColor = 'transparent'

                const headerGradient = ctx.createLinearGradient(tooltipX, tooltipY, tooltipX, tooltipY + headerHeight)
                headerGradient.addColorStop(0, tradeColor + '20')
                headerGradient.addColorStop(1, tradeColor + '10')
                ctx.fillStyle = headerGradient
                ctx.beginPath()
                ctx.roundRect(tooltipX, tooltipY, tooltipWidth, headerHeight, [8, 8, 0, 0])
                ctx.fill()

                ctx.fillStyle = tradeColor
                ctx.font = isMobile.value ? 'bold 11px Inter' : 'bold 12px Inter'
                ctx.textAlign = 'left'
                ctx.fillText(t.type.toUpperCase(), tooltipX + padding, tooltipY + headerHeight / 2 + 4)

                ctx.fillStyle = isExpired ? downColor : (remainingTimeMs < 60000 ? downColor : tradeColor)
                ctx.font = isMobile.value ? 'bold 11px Inter' : 'bold 12px Inter'
                ctx.textAlign = 'right'
                ctx.fillText(timeText, tooltipX + tooltipWidth - padding, tooltipY + headerHeight / 2 + 4)

                const rowStartY = tooltipY + headerHeight
                const rows = [
                    { label: 'Amount', value: `${t.amount.toFixed(2)}` },
                    { label: 'Entry', value: t.entry_price.toFixed(displayPrecision) },
                    { label: 'Time', value: new Date(t.opened_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }
                ]

                rows.forEach((row, i) => {
                    const y = rowStartY + i * rowHeight

                    if (i % 2 === 0) {
                        ctx.fillStyle = isDark.value ? 'rgba(255,255,255,0.02)' : 'rgba(0,0,0,0.02)'
                        ctx.fillRect(tooltipX, y, tooltipWidth, rowHeight)
                    }

                    ctx.fillStyle = isDark.value ? '#9ca3af' : '#6b7280'
                    ctx.font = isMobile.value ? '9px Inter' : '10px Inter'
                    ctx.textAlign = 'left'
                    ctx.fillText(row.label, tooltipX + padding, y + rowHeight / 2 + 3)

                    ctx.fillStyle = isDark.value ? '#e5e7eb' : '#1f2937'
                    ctx.font = isMobile.value ? 'bold 10px Inter' : 'bold 11px Inter'
                    ctx.textAlign = 'right'
                    ctx.fillText(row.value, tooltipX + tooltipWidth - padding, y + rowHeight / 2 + 3)
                })

                const separatorY = rowStartY + rowHeight * 3
                ctx.strokeStyle = isDark.value ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'
                ctx.lineWidth = 1
                ctx.beginPath()
                ctx.moveTo(tooltipX + padding, separatorY)
                ctx.lineTo(tooltipX + tooltipWidth - padding, separatorY)
                ctx.stroke()

                const footerY = separatorY + separatorHeight

                const pnlGradient = ctx.createLinearGradient(tooltipX, footerY, tooltipX, footerY + footerHeight)
                pnlGradient.addColorStop(0, pnlColorValue + '15')
                pnlGradient.addColorStop(1, pnlColorValue + '08')
                ctx.fillStyle = pnlGradient
                ctx.fillRect(tooltipX, footerY, tooltipWidth, footerHeight - 8)

                ctx.fillStyle = isDark.value ? '#9ca3af' : '#6b7280'
                ctx.font = isMobile.value ? '8px Inter' : '9px Inter'
                ctx.textAlign = 'left'
                ctx.fillText('PROFIT/LOSS', tooltipX + padding, footerY + 12)

                const tradePnL = t.pnl;
                const pnlText = `${(t.amount + tradePnL).toFixed(2)}`;

                ctx.fillStyle = pnlColorValue
                ctx.font = isMobile.value ? 'bold 13px Inter' : 'bold 14px Inter'
                ctx.textAlign = 'left'
                ctx.fillText(pnlText, tooltipX + padding, footerY + 28)

                const pnlPctText = `(${t.pnlPct}%)`
                ctx.font = isMobile.value ? 'bold 10px Inter' : 'bold 11px Inter'
                const pnlTextWidth = ctx.measureText(pnlText).width
                ctx.fillText(pnlPctText, tooltipX + padding + pnlTextWidth + 15, footerY + 28)

                const closeBtnWidth = isMobile.value ? 60 : 70
                const closeBtnHeight = isMobile.value ? 24 : 28
                const closeBtnX = tooltipX + tooltipWidth - closeBtnWidth - padding
                const closeBtnY = footerY + (footerHeight - closeBtnHeight) / 2

                if (remainingTimeMs > CLOSE_DISABLE_THRESHOLD) {
                    ctx.fillStyle = downColor
                    ctx.beginPath()
                    ctx.roundRect(closeBtnX, closeBtnY, closeBtnWidth, closeBtnHeight, 3)
                    ctx.fill()
                    ctx.fillStyle = 'white'
                    ctx.font = isMobile.value ? 'bold 10px Inter' : 'bold 11px Inter'
                    ctx.textAlign = 'center'
                    ctx.fillText('Close', closeBtnX + closeBtnWidth / 2, closeBtnY + closeBtnHeight / 2 + 4)
                    tooltipCloseRects.value.push({
                        tradeId: t.id,
                        x: closeBtnX,
                        y: closeBtnY,
                        w: closeBtnWidth,
                        h: closeBtnHeight
                    })
                } else if (remainingTimeMs > 0) {
                    ctx.fillStyle = gridColor.value
                    ctx.beginPath()
                    ctx.roundRect(closeBtnX, closeBtnY, closeBtnWidth, closeBtnHeight, 3)
                    ctx.fill()
                    ctx.fillStyle = textColor.value
                    ctx.font = isMobile.value ? 'bold 10px Inter' : 'bold 11px Inter'
                    ctx.textAlign = 'center'
                    ctx.fillText('Closing', closeBtnX + closeBtnWidth / 2, closeBtnY + closeBtnHeight / 2 + 4)
                } else {
                    ctx.fillStyle = gridColor.value
                    ctx.beginPath()
                    ctx.roundRect(closeBtnX, closeBtnY, closeBtnWidth, closeBtnHeight, 3)
                    ctx.fill()
                    ctx.fillStyle = textColor.value
                    ctx.font = isMobile.value ? 'bold 10px Inter' : 'bold 11px Inter'
                    ctx.textAlign = 'center'
                    ctx.fillText('Expired', closeBtnX + closeBtnWidth / 2, closeBtnY + closeBtnHeight / 2 + 4)
                }
            })
        } else {
            tooltipCloseRects.value = []
            tooltipRects.value = []
        }

        if (crosshair.value.visible && crosshair.value.x >= axisSpaceX && crosshair.value.x <= width - AXIS_SPACE_Y && crosshair.value.y >= TOP_SPACE && crosshair.value.y <= TOP_SPACE + mainHeight) {
            const chartX = crosshair.value.x - axisSpaceX + panX.value
            const candleIdx = Math.floor(chartX / candleWidth)
            const relativeIdx = candleIdx - startCandleIdx

            if (relativeIdx >= 0 && relativeIdx < visible.length) {
                const c = visible[relativeIdx]
                const snappedX = axisSpaceX + relativeIdx * candleWidth - panX.value + candleWidth / 2

                ctx.strokeStyle = crosshairColor
                ctx.lineWidth = 1
                ctx.setLineDash([3, 3])
                ctx.beginPath()
                ctx.moveTo(snappedX, TOP_SPACE)
                ctx.lineTo(snappedX, TOP_SPACE + mainHeight)
                ctx.stroke()
                ctx.beginPath()
                ctx.moveTo(axisSpaceX, crosshair.value.y)
                ctx.lineTo(width, crosshair.value.y)
                ctx.stroke()
                ctx.setLineDash([])

                const priceAtCrosshair = paddedMax - ((crosshair.value.y - TOP_SPACE) / mainHeight) * priceScale

                if (isFinite(priceAtCrosshair)) {
                    const priceLabel = priceAtCrosshair.toFixed(displayPrecision)
                    ctx.fillStyle = crosshairColor
                    ctx.fillRect(width - AXIS_SPACE_Y, crosshair.value.y - 10, AXIS_SPACE_Y, 20)
                    ctx.fillStyle = 'white'
                    ctx.font = isMobile.value ? '9px Inter' : '11px Inter'
                    ctx.textAlign = 'center'
                    ctx.fillText(priceLabel, width - AXIS_SPACE_Y / 2, crosshair.value.y + 4)
                }

                const timeLabel = new Date(c.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                const timeTextWidth = ctx.measureText(timeLabel).width
                ctx.fillStyle = crosshairColor
                ctx.fillRect(snappedX - timeTextWidth / 2 - 5, canvasHeight - BOTTOM_SPACE, timeTextWidth + 10, BOTTOM_SPACE)
                ctx.fillStyle = 'white'
                ctx.font = isMobile.value ? '8px Inter' : '11px Inter'
                ctx.fillText(timeLabel, snappedX, canvasHeight - 15)

                const shortPrecision = getPricePrecision(props.pair) === 5 ? 3 : 2
                const longPrecision = getPricePrecision(props.pair)

                const tip = isMobile.value
                    ? `O:${c.open.toFixed(shortPrecision)} H:${c.high.toFixed(shortPrecision)} L:${c.low.toFixed(shortPrecision)} C:${c.close.toFixed(shortPrecision)}`
                    : `O:${c.open.toFixed(longPrecision)} H:${c.high.toFixed(longPrecision)} L:${c.low.toFixed(longPrecision)} C:${c.close.toFixed(longPrecision)} V:${c.volume}`
                ctx.fillStyle = 'rgba(0,0,0,0.8)'
                const tooltipWidth = isMobile.value ? 140 : 190
                ctx.fillRect(snappedX + 10, crosshair.value.y - 30, tooltipWidth, 20)
                ctx.fillStyle = 'white'
                ctx.font = isMobile.value ? '8px monospace' : '10px monospace'
                ctx.textAlign = 'left'
                ctx.fillText(tip, snappedX + 15, crosshair.value.y - 17)
            }
        }
    }

    const applyKineticScrolling = () => {
        if (Math.abs(velocity) < VELOCITY_THRESHOLD) {
            velocity = 0
            return
        }

        if (!canvas.value) return

        const { maxPan } = getChartMetrics(canvas.value.getBoundingClientRect().width)
        panX.value += velocity
        panX.value = Math.max(0, Math.min(maxPan, panX.value))
        velocity *= FRICTION

        if (panX.value === 0 || panX.value === maxPan) velocity = 0
    }

    const animate = () => {
        applyKineticScrolling()
        draw()
        animationId = requestAnimationFrame(animate)
    }

    const handleMouseMove = (e: MouseEvent) => {
        if (dragging) return
        if (!canvas.value) return

        const rect = canvas.value.getBoundingClientRect()
        const mouseX = e.clientX - rect.left
        const mouseY = e.clientY - rect.top
        const constrainedY = Math.max(TOP_SPACE, Math.min(mouseY, TOP_SPACE + canvas.value.clientHeight - BOTTOM_SPACE - TOP_SPACE))
        crosshair.value = { x: mouseX, y: constrainedY, visible: true }

        const overCloseButton = findTradeAt(e.clientX, e.clientY, false)

        if (!isMobile.value) {
            for (const r of tooltipRects.value) {
                if (mouseX >= r.x && mouseX <= r.x + r.w && mouseY >= r.y && mouseY <= r.y + r.h) {
                    break
                }
            }

            const trades = chartStore.openTradesForPair
            const width = rect.width
            const canvasHeight = rect.height
            const mainHeight = canvasHeight - BOTTOM_SPACE - TOP_SPACE

            const { candleWidth } = getChartMetrics(width)

            const startCandleIdx = Math.max(0, Math.floor(panX.value / candleWidth))
            const maxCandles = Math.ceil((width - AXIS_SPACE_X.value - AXIS_SPACE_Y) / candleWidth) + 2
            const endCandleIdx = Math.min(candles.value.length, startCandleIdx + maxCandles)
            const visibleCandles = candles.value.slice(startCandleIdx, endCandleIdx)

            let closeTrades: TradeWithPnL[] = []

            let maxP = 0
            let minP = 0
            let range = 0.0000001
            let paddedMax = 0
            let paddedMin = 0
            let priceScale = 0.0000001
            let priceToY: (price: number) => number = (price) => mouseY

            if (visibleCandles.length) {
                const allPrices = visibleCandles.flatMap(c => [c.high, c.low])
                maxP = Math.max(...allPrices, currentPrice.value)
                minP = Math.min(...allPrices, currentPrice.value)
                range = Math.max(0.0000001, maxP - minP)
                paddedMax = maxP + range * 0.05
                paddedMin = minP - range * 0.05
                priceScale = paddedMax - paddedMin

                priceToY = (price: number) => TOP_SPACE + mainHeight - ((price - paddedMin) / priceScale) * mainHeight

                closeTrades = trades.filter(trade => Math.abs(mouseY - priceToY(trade.entry_price)) <= HIT_AREA)
            }

            if (hoveredTrades.value.length > 0) {
            } else {
                if (closeTrades.length > 0) {
                    hoveredTrades.value = closeTrades
                    const lineY = priceToY(closeTrades[0].entry_price)
                    tooltipPos.value = { x: mouseX, y: lineY }
                }
            }
        }

        if (canvas.value) {
            canvas.value.style.cursor = overCloseButton ? 'pointer' : 'crosshair'
        }
    }

    const handleMouseLeave = () => {
        crosshair.value.visible = false
        if (!isMobile.value) {
            hoveredTrades.value = []
            tooltipPos.value = { x: -1, y: -1 }
        }
    }

    const handleWheel = (e: WheelEvent) => {
        e.preventDefault()
        const oldZoom = zoom.value
        const factor = e.deltaY > 0 ? 0.9 : 1.1
        const newZoom = Math.max(0.2, Math.min(3, zoom.value * factor))

        if (canvas.value) {
            const rect = canvas.value.getBoundingClientRect()
            const pointerX = e.clientX - rect.left
            const chartCenter = pointerX - AXIS_SPACE_X.value
            const worldX = panX.value + chartCenter
            const zoomRatio = newZoom / oldZoom
            panX.value = Math.max(0, worldX * zoomRatio - chartCenter)

            const { maxPan } = getChartMetrics(rect.width)
            panX.value = Math.min(maxPan, panX.value)
        }

        zoom.value = newZoom
        velocity = 0
    }

    const handleMouseDown = (e: MouseEvent) => {
        if (!canvas.value) return

        const rect = canvas.value.getBoundingClientRect()
        if (e.clientX - rect.left < AXIS_SPACE_X.value || e.clientX - rect.left > rect.width - AXIS_SPACE_Y) return

        dragging = true
        dragStartX = e.clientX
        lastDragX = e.clientX
        lastDragTime = Date.now()
        panStart = panX.value
        velocity = 0
        crosshair.value.visible = false
        canvas.value.style.cursor = 'grabbing'
    }

    const handleMouseUp = () => {
        dragging = false
        if (canvas.value) canvas.value.style.cursor = 'crosshair'
    }

    const handleMouseDrag = (e: MouseEvent) => {
        if (!dragging || !canvas.value) return

        const now = Date.now()
        const delta = e.clientX - dragStartX
        const newPanX = panStart - delta

        const { maxPan } = getChartMetrics(canvas.value.getBoundingClientRect().width)
        panX.value = Math.max(0, Math.min(maxPan, newPanX))

        const timeDelta = now - lastDragTime
        if (timeDelta > 0) velocity = (lastDragX - e.clientX) / timeDelta
        lastDragX = e.clientX
        lastDragTime = now
    }

    const getTouchDistance = (touches: TouchList) => {
        if (touches.length < 2) return 0
        const dx = touches[0].clientX - touches[1].clientX
        const dy = touches[0].clientY - touches[1].clientY
        return Math.sqrt(dx * dx + dy * dy)
    }

    const handleTouchStart = (e: TouchEvent) => {
        if (e.touches.length === 1) {
            const rect = canvas.value?.getBoundingClientRect()
            if (!rect) return

            const touchX = e.touches[0].clientX - rect.left
            if (touchX < AXIS_SPACE_X.value || touchX > rect.width - AXIS_SPACE_Y) return

            isTouching.value = true
            isDragging.value = false
            touchStartPos.value = { x: e.touches[0].clientX, y: e.touches[0].clientY }
            crosshair.value.visible = false
        } else if (e.touches.length === 2) {
            isTouching.value = false
            isDragging.value = false
            velocity = 0
            touchStartDistance = getTouchDistance(e.touches)
            touchStartZoom = zoom.value
            touchStartX = (e.touches[0].clientX + e.touches[1].clientX) / 2
        }
    }

    const handleTouchMove = (e: TouchEvent) => {
        if (e.touches.length === 1 && isTouching.value) {
            const dx = e.touches[0].clientX - touchStartPos.value.x
            const dy = e.touches[0].clientY - touchStartPos.value.y
            if (Math.sqrt(dx * dx + dy * dy) > 5) {
                isDragging.value = true
                dragStartX = touchStartPos.value.x
                lastDragX = touchStartPos.value.x
                lastDragTime = Date.now()
                panStart = panX.value
                velocity = 0
            }

            if (isDragging.value) {
                const now = Date.now()
                const delta = e.touches[0].clientX - dragStartX
                const newPanX = panStart - delta

                const { maxPan } = getChartMetrics(canvas.value?.getBoundingClientRect().width || 0)
                panX.value = Math.max(0, Math.min(maxPan, newPanX))

                const timeDelta = now - lastDragTime
                if (timeDelta > 0) velocity = (lastDragX - e.touches[0].clientX) / timeDelta
                lastDragX = e.touches[0].clientX
                lastDragTime = now
            } else {
                e.preventDefault()
            }
        } else if (e.touches.length === 2) {
            e.preventDefault()
            const currentDistance = getTouchDistance(e.touches)
            if (currentDistance === 0) return

            const oldZoom = zoom.value
            const pinchRatio = currentDistance / touchStartDistance
            const newZoom = Math.max(0.2, Math.min(3, touchStartZoom * pinchRatio))

            if (canvas.value) {
                const rect = canvas.value.getBoundingClientRect()
                const pointerX = touchStartX - rect.left
                const chartCenter = pointerX - AXIS_SPACE_X.value
                const worldX = panX.value + chartCenter
                const zoomRatio = newZoom / oldZoom
                panX.value = Math.max(0, worldX * zoomRatio - chartCenter)

                const { maxPan } = getChartMetrics(rect.width)
                panX.value = Math.min(maxPan, panX.value)
            }

            zoom.value = newZoom
        }
    }

    const handleTouchEnd = (e: TouchEvent) => {
        if (isTouching.value && !isDragging.value) {
            if (canvas.value) {
                const simulatedEvent = new MouseEvent('click', {
                    clientX: touchStartPos.value.x,
                    clientY: touchStartPos.value.y,
                    bubbles: true,
                    cancelable: true
                })
                canvas.value.dispatchEvent(simulatedEvent)
            }
        }
        isTouching.value = false
        isDragging.value = false
        dragging = false
    }

    const checkMobile = () => {
        isMobile.value = window.innerWidth < 768
    }

    const findTradeAt = (clientX: number, clientY: number, isClick: boolean): boolean => {
        let overCloseButton = false
        if (!canvas.value) return false

        const rect = canvas.value.getBoundingClientRect()
        const x = clientX - rect.left
        const y = clientY - rect.top

        for (const r of tooltipCloseRects.value) {
            if (x >= r.x && x <= r.x + r.w && y >= r.y && y <= r.y + r.h) {
                if (isClick) {
                    closeTrade(r.tradeId)
                }
                overCloseButton = true
            }
        }

        if (isClick && overCloseButton) return true

        const width = rect.width
        const canvasHeight = rect.height
        const mainHeight = canvasHeight - BOTTOM_SPACE - TOP_SPACE

        const { candleWidth } = getChartMetrics(width)
        const startCandleIdx = Math.max(0, Math.floor(panX.value / candleWidth))
        const maxCandles = Math.ceil((width - AXIS_SPACE_X.value - AXIS_SPACE_Y) / candleWidth) + 2
        const endCandleIdx = Math.min(candles.value.length, startCandleIdx + maxCandles)
        const visible = candles.value.slice(startCandleIdx, endCandleIdx)

        if (!visible.length) {
            if (isClick) {
                hoveredTrades.value = []
                tooltipPos.value = { x: -1, y: -1 }
            }
            return overCloseButton
        }

        const allPrices = visible.flatMap(c => [c.high, c.low])
        const maxP = Math.max(...allPrices, currentPrice.value)
        const minP = Math.min(...allPrices, currentPrice.value)
        const range = Math.max(0.0000001, maxP - minP)
        const paddedMax = maxP + range * 0.05
        const paddedMin = minP - range * 0.05
        const priceScale = paddedMax - paddedMin

        const priceToY = (price: number) => TOP_SPACE + mainHeight - ((price - paddedMin) / priceScale) * mainHeight

        const trades = chartStore.openTradesForPair
        const closeTrades = trades.filter(trade => Math.abs(y - priceToY(trade.entry_price)) <= HIT_AREA)

        if (closeTrades.length > 0) {
            if (isClick) {
                hoveredTrades.value = closeTrades
                const lineY = priceToY(closeTrades[0].entry_price)
                tooltipPos.value = { x: x, y: lineY }
            }
        } else {
            let overExistingTooltip = false;
            for (const r of tooltipRects.value) {
                if (x >= r.x && x <= r.x + r.w && y >= r.y && y <= r.y + r.h) {
                    overExistingTooltip = true;
                    break;
                }
            }

            if (isClick && !overCloseButton && !overExistingTooltip) {
                hoveredTrades.value = []
                tooltipPos.value = { x: -1, y: -1 }
            }
        }

        return overCloseButton
    }

    const handleClick = (e: MouseEvent) => {
        const rect = canvas.value?.getBoundingClientRect()
        if (!rect) return

        const x = e.clientX - rect.left
        const y = e.clientY - rect.top

        if (findTradeAt(e.clientX, e.clientY, true)) {
            return
        }

        let clickedOnTooltip = false
        for (const r of tooltipRects.value) {
            if (x >= r.x && x <= r.x + r.w && y >= r.y && y <= r.y + r.h) {
                clickedOnTooltip = true;
                break;
            }
        }

        const trades = chartStore.openTradesForPair
        const width = rect.width
        const canvasHeight = rect.height
        const mainHeight = canvasHeight - BOTTOM_SPACE - TOP_SPACE
        const { candleWidth } = getChartMetrics(width)

        let clickedTrades: TradeWithPnL[] = []
        if (trades.length) {
            const startCandleIdx = Math.max(0, Math.floor(panX.value / candleWidth))
            const maxCandles = Math.ceil((width - AXIS_SPACE_X.value - AXIS_SPACE_Y) / candleWidth) + 2
            const endCandleIdx = Math.min(candles.value.length, startCandleIdx + maxCandles)
            const visibleCandles = candles.value.slice(startCandleIdx, endCandleIdx)

            if (visibleCandles.length) {
                const allPrices = visibleCandles.flatMap(c => [c.high, c.low])
                const maxP = Math.max(...allPrices, currentPrice.value)
                const minP = Math.min(...allPrices, currentPrice.value)
                const range = Math.max(0.0000001, maxP - minP)
                const paddedMax = maxP + range * 0.05
                const paddedMin = minP - range * 0.05
                const priceScale = paddedMax - paddedMin
                const priceToY = (price: number) => TOP_SPACE + mainHeight - ((price - paddedMin) / priceScale) * mainHeight

                clickedTrades = trades.filter(trade => Math.abs(y - priceToY(trade.entry_price)) <= HIT_AREA)

                if (clickedTrades.length > 0) {
                    hoveredTrades.value = clickedTrades
                    const lineY = priceToY(clickedTrades[0].entry_price)
                    tooltipPos.value = { x: x, y: lineY }
                    return
                }
            }
        }

        if (!clickedOnTooltip) {
            hoveredTrades.value = []
            tooltipPos.value = { x: -1, y: -1 }
        }
    }

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

    const priceMovementColor = computed(() => {
        if (currentPrice.value > lastPrice.value) return upColor
        if (currentPrice.value < lastPrice.value) return downColor
        return isBullish.value ? upColor : downColor
    })

    onMounted(() => {
        checkMobile()
        window.addEventListener('resize', checkMobile)

        isDark.value = document.documentElement.classList.contains('dark')

        if (chartStore.selectedPair && chartStore.selectedPair !== props.pair) {
            emit('update:pair', chartStore.selectedPair)
        } else {
            chartStore.setPair(props.pair)
        }

        chartStore.setOpenTrades(props.openTrades ?? [])
        initPrice()
        generateCandles()
        startTicking()
        animate()

        startTradeExpiryMonitoring()

        healthCheckInterval = setInterval(() => {
            if (!chartStore.validateDataIntegrity()) {
                chartStore.resetView()
            }
        }, HEALTH_CHECK_INTERVAL)

        resizeObserver = new ResizeObserver(() => {
            if (container.value && !dragging) autoPan(container.value.clientWidth)
        })
        if (container.value) resizeObserver.observe(container.value)

        canvas.value?.addEventListener('mousemove', handleMouseMove)
        canvas.value?.addEventListener('mouseleave', handleMouseLeave)
        canvas.value?.addEventListener('wheel', handleWheel, { passive: false })
        canvas.value?.addEventListener('mousedown', handleMouseDown)
        canvas.value?.addEventListener('click', handleClick)
        canvas.value?.addEventListener('touchstart', handleTouchStart, { passive: true })
        canvas.value?.addEventListener('touchmove', handleTouchMove, { passive: false })
        canvas.value?.addEventListener('touchend', handleTouchEnd, { passive: true })
        window.addEventListener('mousemove', handleMouseDrag)
        window.addEventListener('mouseup', handleMouseUp)

        const mo = new MutationObserver(() => {
            isDark.value = document.documentElement.classList.contains('dark')
        })
        mo.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

        onBeforeUnmount(() => mo.disconnect())
    })

    onBeforeUnmount(() => {
        window.removeEventListener('resize', checkMobile)
        if (animationId) cancelAnimationFrame(animationId)
        if (tickInterval) clearInterval(tickInterval)
        if (healthCheckInterval) clearInterval(healthCheckInterval)
        if (tradeExpiryCheckInterval) clearInterval(tradeExpiryCheckInterval)
        resizeObserver?.disconnect()
        canvas.value?.removeEventListener('mousemove', handleMouseMove)
        canvas.value?.removeEventListener('mouseleave', handleMouseLeave)
        canvas.value?.removeEventListener('wheel', handleWheel)
        canvas.value?.removeEventListener('mousedown', handleMouseDown)
        canvas.value?.removeEventListener('click', handleClick)
        canvas.value?.removeEventListener('touchstart', handleTouchStart)
        canvas.value?.removeEventListener('touchmove', handleTouchMove)
        canvas.value?.removeEventListener('touchend', handleTouchEnd)
        window.removeEventListener('mousemove', handleMouseDrag)
        window.removeEventListener('mouseup', handleMouseUp)
    })

    watch(() => props.pair, (newPair) => {
        chartStore.setPair(newPair)
        initPrice()
        generateCandles()
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
</script>

<template>
    <div class="w-full">
        <div :class="['flex gap-3 p-3 rounded-t-lg border-b items-center overflow-x-auto', isDark ? 'bg-muted/30' : 'bg-gray-50 border-gray-200']">
            <div class="flex items-center gap-2 py-2 flex-shrink-0">
                <span :class="['text-sm font-bold', isDark ? 'text-gray-200' : 'text-gray-900']">{{ props.pair }}</span>
                <span :class="['text-xs px-2 py-1 rounded font-semibold',
                    isBullish ? (isDark ? 'bg-teal-900/40 text-teal-300' : 'bg-teal-100 text-teal-700')
                    : (isDark ? 'bg-red-900/40 text-red-300' : 'bg-red-100 text-red-700')
                ]">
                    {{ isBullish ? '+' : '' }}{{ displayChange.toFixed(getPricePrecision(props.pair) - 2) }} ({{ changePercentage }}%)
                </span>
            </div>

            <div :class="['w-px h-6 flex-shrink-0', isDark ? 'bg-gray-700' : 'bg-gray-300']"></div>

            <button @click="zoomOut" :class="['ml-auto p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark ? 'bg-gray-800 hover:bg-gray-700 text-gray-300' : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300']" title="Zoom Out">
                <ZoomOut :size="18" />
            </button>

            <button @click="zoomIn" :class="['p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark ? 'bg-gray-800 hover:bg-gray-700 text-gray-300' : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300']" title="Zoom In">
                <ZoomIn :size="18" />
            </button>

            <button @click="fitToScreen" :class="['p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark ? 'bg-gray-800 hover:bg-gray-700 text-gray-300' : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300']" title="Fit to Screen">
                <Maximize2 :size="18" />
            </button>

            <button @click="resetView" :class="['p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark ? 'bg-gray-800 hover:bg-gray-700 text-gray-300' : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300']" title="Reset View">
                <RotateCcw :size="18" />
            </button>

            <button @click="jumpToLive" :class="['p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark ? 'bg-blue-900 hover:bg-blue-800 text-blue-300' : 'bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-300']" title="Jump to Live Data">
                <Radio :size="18" />
            </button>
        </div>

        <div ref="container" :style="{ height: isMobile ? '300px' : '500px' }">
            <canvas ref="canvas" class="w-full h-full cursor-crosshair select-none touch-none" />
        </div>
    </div>
</template>

<style scoped>
    canvas { image-rendering: optimizeSpeed; }
    button { user-select: none; -webkit-user-select: none; }
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    @media (min-width: 1024px) { ::-webkit-scrollbar { width: 8px; height: 8px; } }
    ::-webkit-scrollbar-track { background: hsl(var(--muted)); border-radius: 4px; }
    ::-webkit-scrollbar-thumb { background: hsl(var(--muted-foreground) / 0.3); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: hsl(var(--muted-foreground) / 0.5); }
    @media (max-width: 768px) { button { padding: 0.375rem; } }
</style>
