<script setup lang="ts">
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { ZoomOut, ZoomIn, Maximize2, Radio } from 'lucide-vue-next';

    // --- INTERFACE DEFINITIONS ---
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
    }

    // --- PROPS & INITIALIZATION ---
    const props = withDefaults(defineProps<Props>(), {
        pair: 'EUR/USD',
        price: 1.085,
        change: 0,
        low: 0,
        high: 0,
        volume: 0,
    })

    const container = ref<HTMLElement | null>(null)
    const canvas = ref<HTMLCanvasElement | null>(null)
    const isDark = ref(true)
    const candles = ref<Candle[]>([])
    const currentPrice = ref(0)
    const externalChange = ref(0)
    const externalLow = ref(0)
    const externalHigh = ref(0)
    const externalVolume = ref(0)
    const crosshair = ref({ x: -1, y: -1, visible: false })
    const zoom = ref(1.0)
    const panX = ref(0)
    const isMobile = ref(false)

    let animationId: number | null = null
    let tickInterval: ReturnType<typeof setInterval> | null = null
    let resizeObserver: ResizeObserver | null = null
    let lastCandleTime = 0
    let dragging = false
    let dragStartX = 0
    let panStart = 0
    let velocity = 0
    let lastDragTime = 0
    let lastDragX = 0

    // Touch state
    let touchStartDistance = 0
    let touchStartZoom = 0
    let touchStartX = 0

    // --- CONSTANTS ---
    const CANDLE_SPACING = 15
    const MIN_CANDLE_BODY = 1.5
    const TICK_MS = 200
    const AXIS_SPACE_Y = 100
    const TOP_SPACE = 40
    const BOTTOM_SPACE = 40
    const CANDLE_INTERVAL_MS = 5_000
    const FRICTION = 0.95
    const VELOCITY_THRESHOLD = 0.5

    // --- COLORS & STYLES ---
    const bgColor = computed(() => (isDark.value ? '#1a1a1a4d' : '#ffffff'))
    const gridColor = computed(() =>
        isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)'
    )
    const textColor = computed(() => (isDark.value ? '#e5e5e5' : '#1f2937'))
    const upColor = '#26a69a'
    const downColor = '#ef5350'
    const crosshairColor = '#9ca3af'

    // Responsive axis space
    const AXIS_SPACE_X = computed(() => isMobile.value ? 0 : 60)

    // --- DATA & CANDLE LOGIC ---

    const initPrice = () => {
        const base = parseFloat(String(props.price)) || 1.085
        currentPrice.value = parseFloat(base.toFixed(5))
        externalChange.value = parseFloat(String(props.change)) || 0
        externalLow.value = parseFloat(String(props.low)) || 0
        externalHigh.value = parseFloat(String(props.high)) || 0
        externalVolume.value = parseFloat(String(props.volume)) || 0
        lastCandleTime = Date.now()
    }

    const generateCandles = (count = 100) => {
        const data: Candle[] = []
        let price = currentPrice.value
        for (let i = count - 1; i >= 0; i--) {
            const time = lastCandleTime - i * CANDLE_INTERVAL_MS
            const volatility = 0.0003 + Math.random() * 0.0004
            const change = (Math.random() - 0.5) * volatility * price
            const open = price
            const close = price + change
            const high = Math.max(open, close) + Math.random() * 0.0001 * price
            const low = Math.min(open, close) - Math.random() * 0.0001 * price

            data.push({
                time,
                open: parseFloat(open.toFixed(5)),
                high: parseFloat(high.toFixed(5)),
                low: parseFloat(low.toFixed(5)),
                close: parseFloat(close.toFixed(5)),
                volume: Math.floor(Math.random() * 1500) + 500,
            })
            price = close
        }
        candles.value = data
        if (container.value) {
            autoPan(container.value.clientWidth)
        }
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
        if (container.value) {
            autoPan(container.value.clientWidth)
        }
    }

    const fitToScreen = () => {
        zoom.value = 1.0
        velocity = 0
        if (container.value) {
            autoPan(container.value.clientWidth)
        }
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
        if (container.value) {
            autoPan(container.value.clientWidth)
        }
    }

    const startTicking = () => {
        tickInterval = setInterval(() => {
            lastPrice.value = currentPrice.value
            const change = (Math.random() - 0.5) * 0.00008 * currentPrice.value
            currentPrice.value = parseFloat((currentPrice.value + change).toFixed(5))

            const now = Date.now()
            if (now - lastCandleTime >= CANDLE_INTERVAL_MS) {
                const lastClose = currentPrice.value
                candles.value.push({
                    time: now,
                    open: parseFloat(lastClose.toFixed(5)),
                    high: parseFloat(lastClose.toFixed(5)),
                    low: parseFloat(lastClose.toFixed(5)),
                    close: parseFloat(lastClose.toFixed(5)),
                    volume: Math.floor(Math.random() * 500) + 100,
                })
                lastCandleTime = now
                if (!dragging && container.value) autoPan(container.value.clientWidth)
            } else if (candles.value.length) {
                const last = candles.value[candles.value.length - 1]
                last.close = currentPrice.value
                last.high = Math.max(last.high, currentPrice.value)
                last.low = Math.min(last.low, currentPrice.value)
                last.volume += Math.floor(Math.random() * 20)
            }
        }, TICK_MS)
    }

    // --- DRAWING LOGIC ---

    const draw = () => {
        if (!canvas.value || !container.value) return
        const ctx = canvas.value.getContext('2d')
        if (!ctx) return

        const rect = canvas.value.getBoundingClientRect()
        const width = Math.floor(rect.width)
        const height = Math.floor(rect.height)
        const dpr = window.devicePixelRatio || 1
        const axisSpaceX = AXIS_SPACE_X.value

        canvas.value.width = Math.max(1, Math.floor(width * dpr))
        canvas.value.height = Math.max(1, Math.floor(height * dpr))
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0)

        ctx.clearRect(0, 0, width, height)
        ctx.fillStyle = bgColor.value
        ctx.fillRect(0, 0, width, height)

        const { candleWidth, chartWidth } = getChartMetrics(width)
        const mainHeight = height - BOTTOM_SPACE - TOP_SPACE

        const startCandleIdx = Math.max(0, Math.floor(panX.value / candleWidth))
        const maxCandles = Math.ceil(chartWidth / candleWidth) + 2
        const endCandleIdx = Math.min(candles.value.length, startCandleIdx + maxCandles)
        const visible = candles.value.slice(startCandleIdx, endCandleIdx)

        if (!visible.length) return

        const allPrices = visible.flatMap(c => [c.high, c.low])
        const maxP = Math.max(...allPrices, currentPrice.value)
        const minP = Math.min(...allPrices, currentPrice.value)
        const range = Math.max(0.0000001, maxP - minP)

        const paddedMax = maxP + range * 0.05
        const paddedMin = minP - range * 0.05
        const priceScale = paddedMax - paddedMin

        const priceToY = (price: number) => TOP_SPACE + mainHeight - ((price - paddedMin) / priceScale) * mainHeight

        ctx.strokeStyle = gridColor.value
        ctx.fillStyle = textColor.value
        ctx.font = isMobile.value ? '9px Inter, sans-serif' : '11px Inter, sans-serif'
        ctx.lineWidth = 0.5
        ctx.textAlign = isMobile.value ? 'right' : 'left'

        const numHGridLines = isMobile.value ? 4 : 6
        for (let i = 0; i <= numHGridLines; i++) {
            const y = TOP_SPACE + (mainHeight / numHGridLines) * i
            const price = paddedMax - priceScale * (i / numHGridLines)

            ctx.beginPath()
            ctx.moveTo(axisSpaceX, y)
            ctx.lineTo(width - AXIS_SPACE_Y, y)
            ctx.stroke()

            const labelX = isMobile.value ? width - 4 : 8
            ctx.fillText(price.toFixed(isMobile.value ? 3 : 5), labelX, y + 3)
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

        // Draw current price line and label
        const curY = priceToY(currentPrice.value)
        const currentPriceColor = priceMovementColor.value

        ctx.strokeStyle = currentPriceColor
        ctx.setLineDash([5, 5])
        ctx.beginPath()
        ctx.moveTo(axisSpaceX, curY)
        ctx.lineTo(width - AXIS_SPACE_Y, curY)
        ctx.stroke()
        ctx.setLineDash([])

        // Current price box on the right
        ctx.fillStyle = currentPriceColor
        ctx.fillRect(width - AXIS_SPACE_Y, curY - 10, AXIS_SPACE_Y, 20)
        ctx.fillStyle = 'white'
        ctx.font = isMobile.value ? 'bold 10px Inter' : 'bold 12px Inter'
        ctx.textAlign = 'center'
        ctx.fillText(`${currentPrice.value.toFixed(isMobile.value ? 3 : 5)}`, width - AXIS_SPACE_Y / 2, curY + 4)

        // Vertical grid & time labels (at bottom, centered)
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
                ctx.fillText(label, x, height - 10)
            }
        })

        // Crosshair with improved mobile layout
        if (
            crosshair.value.visible &&
            crosshair.value.x >= axisSpaceX &&
            crosshair.value.x <= width - AXIS_SPACE_Y &&
            crosshair.value.y >= TOP_SPACE &&
            crosshair.value.y <= TOP_SPACE + mainHeight
        ) {
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
                const priceLabel = priceAtCrosshair.toFixed(isMobile.value ? 3 : 5)

                ctx.fillStyle = crosshairColor
                ctx.fillRect(width - AXIS_SPACE_Y, crosshair.value.y - 10, AXIS_SPACE_Y, 20)
                ctx.fillStyle = 'white'
                ctx.font = isMobile.value ? '9px Inter' : '11px Inter'
                ctx.textAlign = 'center'
                ctx.fillText(priceLabel, width - AXIS_SPACE_Y / 2, crosshair.value.y + 4)

                const timeLabel = new Date(c.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                const timeTextWidth = ctx.measureText(timeLabel).width

                ctx.fillStyle = crosshairColor
                ctx.fillRect(snappedX - timeTextWidth / 2 - 5, height - BOTTOM_SPACE, timeTextWidth + 10, BOTTOM_SPACE)
                ctx.fillStyle = 'white'
                ctx.font = isMobile.value ? '8px Inter' : '11px Inter'
                ctx.fillText(timeLabel, snappedX, height - 15)

                // Compact tooltip for mobile
                const tip = isMobile.value
                    ? `O:${c.open.toFixed(3)} H:${c.high.toFixed(3)} L:${c.low.toFixed(3)} C:${c.close.toFixed(3)}`
                    : `O:${c.open} H:${c.high} L:${c.low} C:${c.close} V:${c.volume}`

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

        if (panX.value === 0 || panX.value === maxPan) {
            velocity = 0
        }
    }

    const animate = () => {
        applyKineticScrolling()
        draw()
        animationId = requestAnimationFrame(animate)
    }

    // --- INTERACTIONS ---

    const handleMouseMove = (e: MouseEvent) => {
        if (!canvas.value) return
        const rect = canvas.value.getBoundingClientRect()
        const mouseX = e.clientX - rect.left
        const mouseY = e.clientY - rect.top

        const constrainedY = Math.max(TOP_SPACE, Math.min(mouseY, TOP_SPACE + canvas.value.clientHeight - BOTTOM_SPACE - TOP_SPACE))

        crosshair.value = {
            x: mouseX,
            y: constrainedY,
            visible: true,
        }
    }

    const handleMouseLeave = () => (crosshair.value.visible = false)

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
        if (timeDelta > 0) {
            velocity = (lastDragX - e.clientX) / timeDelta
        }
        lastDragX = e.clientX
        lastDragTime = now
    }

    // --- TOUCH HANDLERS ---

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

            dragging = true
            dragStartX = e.touches[0].clientX
            lastDragX = e.touches[0].clientX
            lastDragTime = Date.now()
            panStart = panX.value
            velocity = 0
            crosshair.value.visible = false
        } else if (e.touches.length === 2) {
            dragging = false
            velocity = 0
            touchStartDistance = getTouchDistance(e.touches)
            touchStartZoom = zoom.value
            touchStartX = (e.touches[0].clientX + e.touches[1].clientX) / 2
        }
    }

    const handleTouchMove = (e: TouchEvent) => {
        if (e.touches.length === 1 && dragging) {
            const now = Date.now()
            const delta = e.touches[0].clientX - dragStartX
            const newPanX = panStart - delta
            const { maxPan } = getChartMetrics(canvas.value?.getBoundingClientRect().width || 0)
            panX.value = Math.max(0, Math.min(maxPan, newPanX))

            const timeDelta = now - lastDragTime
            if (timeDelta > 0) {
                velocity = (lastDragX - e.touches[0].clientX) / timeDelta
            }
            lastDragX = e.touches[0].clientX
            lastDragTime = now
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

    const handleTouchEnd = () => {
        dragging = false
    }

    const checkMobile = () => {
        isMobile.value = window.innerWidth < 768
    }

    // Computed properties for display
    const displayChange = computed(() => {
        return externalChange.value !== 0 ? externalChange.value : currentPrice.value - (candles.value[0]?.open ?? 0)
    })

    const basePrice = computed(() => {
        return externalChange.value !== 0 ? currentPrice.value - externalChange.value : candles.value[0]?.open ?? currentPrice.value
    })

    const changePercentage = computed(() => {
        return basePrice.value !== 0 ? ((displayChange.value / basePrice.value) * 100).toFixed(2) : '0.00'
    })

    const isBullish = computed(() => {
        return displayChange.value >= 0
    })

    // Track price movement for real-time color updates
    const lastPrice = ref(currentPrice.value)
    const priceMovementColor = computed(() => {
        if (currentPrice.value > lastPrice.value) return upColor
        if (currentPrice.value < lastPrice.value) return downColor
        return isBullish.value ? upColor : downColor
    })

    onMounted(() => {
        checkMobile()
        window.addEventListener('resize', checkMobile)

        isDark.value = document.documentElement.classList.contains('dark')
        initPrice()
        generateCandles()
        startTicking()
        animate()

        resizeObserver = new ResizeObserver(() => {
            if (container.value && !dragging) {
                autoPan(container.value.clientWidth)
            }
        })
        if (container.value) resizeObserver.observe(container.value)

        canvas.value?.addEventListener('mousemove', handleMouseMove)
        canvas.value?.addEventListener('mouseleave', handleMouseLeave)
        canvas.value?.addEventListener('wheel', handleWheel, { passive: false })
        canvas.value?.addEventListener('mousedown', handleMouseDown)
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
        resizeObserver?.disconnect()
        canvas.value?.removeEventListener('mousemove', handleMouseMove)
        canvas.value?.removeEventListener('mouseleave', handleMouseLeave)
        canvas.value?.removeEventListener('wheel', handleWheel)
        canvas.value?.removeEventListener('mousedown', handleMouseDown)
        canvas.value?.removeEventListener('touchstart', handleTouchStart)
        canvas.value?.removeEventListener('touchmove', handleTouchMove)
        canvas.value?.removeEventListener('touchend', handleTouchEnd)
        window.removeEventListener('mousemove', handleMouseDrag)
        window.removeEventListener('mouseup', handleMouseUp)
    })

    watch(() => props.price, () => {
        initPrice()
        generateCandles()
    })

    watch(() => props.change, (newVal) => {
        externalChange.value = parseFloat(String(newVal)) || 0
    })

    watch(() => props.pair, generateCandles)
</script>

<template>
    <div class="w-full">
        <!-- Control Bar -->
        <div :class="[ 'flex gap-3 p-3 rounded-t-lg border-b items-center overflow-x-auto', isDark ? 'bg-muted/30' : 'bg-gray-50 border-gray-200' ]">
            <!-- Pair & Price Info -->
            <div class="flex items-center gap-2 py-2 flex-shrink-0">
                <span :class="['text-sm font-bold', isDark ? 'text-gray-200' : 'text-gray-900']">
                    {{ props.pair }}
                </span>
                    <span :class="['text-xs px-2 py-1 rounded font-semibold',
                    isBullish ? (isDark ? 'bg-teal-900/40 text-teal-300' : 'bg-teal-100 text-teal-700')
                    : (isDark ? 'bg-red-900/40 text-red-300' : 'bg-red-100 text-red-700')
                ]">
                    {{ isBullish ? '+' : '' }}{{ displayChange.toFixed(3) }} ({{ changePercentage }}%)
                </span>
            </div>

            <!-- Divider -->
            <div :class="['w-px h-6 flex-shrink-0', isDark ? 'bg-gray-700' : 'bg-gray-300']"></div>

            <!-- Controls -->
            <button
                @click="zoomOut"
                :class="[
                'ml-auto p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark
                    ? 'bg-gray-800 hover:bg-gray-700 text-gray-300'
                    : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300']"
                title="Zoom Out">
                <ZoomOut :size="18" />
            </button>

            <button
                @click="zoomIn"
                :class="[
                'p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark
                    ? 'bg-gray-800 hover:bg-gray-700 text-gray-300'
                    : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300']"
                title="Zoom In">
                <ZoomIn :size="18" />
            </button>

            <button
                @click="fitToScreen"
                :class="[
                'p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark
                    ? 'bg-gray-800 hover:bg-gray-700 text-gray-300'
                    : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-300']"
                title="Fit to Screen">
                <Maximize2 :size="18" />
            </button>

            <button
                @click="jumpToLive"
                :class="[
                'p-2 rounded transition-colors flex items-center justify-center cursor-pointer flex-shrink-0',
                isDark
                    ? 'bg-blue-900 hover:bg-blue-800 text-blue-300'
                    : 'bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-300' ]"
                title="Jump to Live Data">
                <Radio :size="18" />
            </button>
        </div>

        <!-- Canvas Container -->
        <div ref="container" :style="{ height: isMobile ? '300px' : '400px' }">
            <canvas ref="canvas" class="w-full h-full cursor-crosshair select-none touch-none" />
        </div>
    </div>
</template>

<style scoped>
    canvas {
        image-rendering: optimizeSpeed;
    }

    button {
        user-select: none;
        -webkit-user-select: none;
    }

    ::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }
    @media (min-width: 1024px) {
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
    }
    ::-webkit-scrollbar-track {
        background: hsl(var(--muted));
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb {
        background: hsl(var(--muted-foreground) / 0.3);
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--muted-foreground) / 0.5);
    }

    @media (max-width: 768px) {
        button {
            padding: 0.375rem;
        }
    }
</style>
