<script lang="ts" setup>
    import { computed, ref, watch } from 'vue';
    import axios from 'axios';
    import TextLink from '@/components/TextLink.vue';
    import { AlertTriangle } from 'lucide-vue-next';

    type ChartToken = {
        symbol: string;
        name: string;
        logo: string;
        decimals: number;
        price_change_24h: number;
        price: number;
        balance: number;
        value: number;
        coingecko_id?: string;
    };

    type ChartDataPoint = {
        timestamp: number;
        price: number;
        volume: number;
    };

    const props = defineProps<{
        selectedToken?: ChartToken | null;
    }>();

    const selectedTimeframe = ref('1D');
    const timeframes = [
        { label: '1D', days: 1 },
        { label: '7D', days: 7 },
        { label: '14D', days: 14 },
        { label: '1M', days: 30 },
        { label: '3M', days: 90 },
        { label: '1Y', days: 365 },
    ];

    const chartData = ref<ChartDataPoint[]>([]);
    const isLoadingChart = ref(false);
    const chartError = ref<string | null>(null);

    // Interactivity State
    const isHovering = ref(false);
    const hoverX = ref(0);
    const nearestDataPoint = ref<ChartDataPoint | null>(null);

    const CHART_WIDTH = 100;
    const PRICE_CHART_HEIGHT = 70;
    const VOLUME_CHART_HEIGHT = 20;
    const VOLUME_OFFSET_Y = 5;
    const TOTAL_SVG_HEIGHT = PRICE_CHART_HEIGHT + VOLUME_CHART_HEIGHT + VOLUME_OFFSET_Y + 5;

    const getCoinGeckoId = (token: ChartToken): string => {
        if (token.coingecko_id) return token.coingecko_id;
        const mapping: Record<string, string> = {
            'BTC': 'bitcoin',
            'ETH': 'ethereum',
            'USDT': 'tether',
            'BNB': 'binancecoin',
            'SOL': 'solana',
            'XRP': 'ripple',
            'USDC': 'usd-coin',
            'ADA': 'cardano',
            'DOGE': 'dogecoin',
            'TRX': 'tron',
            'MATIC': 'matic-network',
            'DOT': 'polkadot',
            'LTC': 'litecoin',
            'AVAX': 'avalanche-2',
            'LINK': 'chainlink',
            'UNI': 'uniswap',
        };
        return mapping[token.symbol.toUpperCase()] || token.symbol.toLowerCase();
    };

    const formatPrice = (price: number, decimals: number = 2): string => {
        if (price < 0.01 && price > 0) return price.toFixed(6);
        if (price < 1) return price.toFixed(4);
        return price.toFixed(decimals > 2 ? 4 : 2);
    };

    const getCurvePoints = (points: { x: number, y: number }[]): string => {
        if (points.length < 2) return '';
        let curve = `M${points[0].x},${points[0].y} `;
        for (let i = 0; i < points.length - 1; i++) {
            const p0 = i > 0 ? points[i - 1] : points[i];
            const p1 = points[i];
            const p2 = points[i + 1];
            const p3 = i < points.length - 2 ? points[i + 2] : points[i + 1];

            const tension = 0.5;
            const cp1x = p1.x + (p2.x - p0.x) / 6 * tension;
            const cp1y = p1.y + (p2.y - p0.y) / 6 * tension;
            const cp2x = p2.x - (p3.x - p1.x) / 6 * tension;
            const cp2y = p2.y - (p3.y - p1.y) / 6 * tension;

            curve += `C${cp1x.toFixed(2)},${cp1y.toFixed(2)},${cp2x.toFixed(2)},${cp2y.toFixed(2)},${p2.x.toFixed(2)},${p2.y.toFixed(2)} `;
        }
        return curve.trim();
    };

    const fetchChartData = async (token: ChartToken, days: number) => {
        if (!token) return;
        isLoadingChart.value = true;
        chartError.value = null;

        try {
            const coinId = getCoinGeckoId(token);
            const response = await axios.get(route('user.chart.data'), {
                params: { symbol: coinId, days }
            });

            const { prices = [], total_volumes = [] } = response.data;
            chartData.value = prices
                .map((item: [number, number], index: number) => ({
                    timestamp: item[0],
                    price: item[1],
                    volume: total_volumes[index] ? total_volumes[index][1] : 0,
                }))
                .filter(d => d.price > 0);

            if (chartData.value.length === 0) {
                chartError.value = 'No valid price data available for this period.';
            }
        } catch (error: any) {
            console.error('Error fetching chart data:', error);
            chartError.value = error.response?.data?.error || error.request
                ? 'Network error. Please check your connection.'
                : 'Failed to load chart data';
            chartData.value = [];
        } finally {
            isLoadingChart.value = false;
        }
    };

    const chartCoordinates = computed(() => {
        if (chartData.value.length < 2) return { linePath: '', areaPath: '', isPositive: false, minPrice: 0, maxPrice: 0 };

        const prices = chartData.value.map(d => d.price);
        const minPrice = Math.min(...prices);
        const maxPrice = Math.max(...prices);
        const priceRange = maxPrice - minPrice || 1;

        const points: { x: number, y: number }[] = chartData.value.map((point, index) => {
            const x = (index / (chartData.value.length - 1)) * CHART_WIDTH;
            const normalizedY = ((point.price - minPrice) / priceRange) * PRICE_CHART_HEIGHT;
            const y = PRICE_CHART_HEIGHT - normalizedY;
            return { x, y };
        });

        const linePath = getCurvePoints(points);
        const areaPath = `M${points[0].x},${PRICE_CHART_HEIGHT} L${linePath.substring(1)} L${points[points.length - 1].x},${PRICE_CHART_HEIGHT} Z`;

        const isPositive = chartData.value[chartData.value.length - 1].price >= chartData.value[0].price;

        return { linePath, areaPath, isPositive, minPrice, maxPrice };
    });

    const priceLabels = computed(() => {
        const { minPrice, maxPrice } = chartCoordinates.value;
        if (minPrice === maxPrice) return [];

        const labels = [];
        const numLabels = 5;
        const step = (maxPrice - minPrice) / (numLabels - 1);

        for (let i = 0; i < numLabels; i++) {
            const price = minPrice + step * i;
            const normalizedY = ((price - minPrice) / (maxPrice - minPrice)) * PRICE_CHART_HEIGHT;
            const y = PRICE_CHART_HEIGHT - normalizedY;

            labels.push({
                price: `$${formatPrice(price, props.selectedToken?.decimals)}`,
                y: y.toFixed(2),
            });
        }
        return labels;
    });

    const timeLabels = computed(() => {
        if (chartData.value.length < 2) return [];

        const numLabels = Math.min(6, chartData.value.length);
        const step = (chartData.value.length - 1) / (numLabels - 1);
        const labels = [];

        for (let i = 0; i < numLabels; i++) {
            const index = Math.round(i * step);
            const dataPoint = chartData.value[index];
            const x = (index / (chartData.value.length - 1)) * CHART_WIDTH;
            const date = new Date(dataPoint.timestamp);

            let formatOptions: Intl.DateTimeFormatOptions = {};
            switch (selectedTimeframe.value) {
                case '1D':
                    formatOptions = { hour: 'numeric', minute: 'numeric' };
                    break;
                case '7D':
                case '14D':
                    formatOptions = { day: 'numeric', month: 'short' };
                    break;
                default:
                    formatOptions = { month: 'short', year: 'numeric' };
                    break;
            }

            labels.push({
                label: date.toLocaleDateString(undefined, formatOptions),
                x: x.toFixed(2),
            });
        }
        return labels;
    });

    const volumeBars = computed(() => {
        if (chartData.value.length === 0) return [];
        const volumes = chartData.value.map(d => d.volume);
        const maxVolume = Math.max(...volumes) || 1;
        const barWidth = 100 / chartData.value.length;

        return chartData.value.map((point, index) => {
            const prevPrice = index > 0 ? chartData.value[index - 1].price : point.price;
            const isUp = point.price >= prevPrice;
            const normalizedHeight = (point.volume / maxVolume) * VOLUME_CHART_HEIGHT;

            return {
                x: index * barWidth,
                width: Math.max(0, barWidth - 0.2),
                height: Math.max(normalizedHeight, 0.5),
                color: isUp ? 'fill-emerald-500/50' : 'fill-red-500/50',
            };
        });
    });

    const handleMouseMove = (event: MouseEvent) => {
        const chartContainer = event.currentTarget as HTMLElement;
        const rect = chartContainer.getBoundingClientRect();
        const clientX = event.clientX - rect.left;
        const chartAreaWidth = rect.width;

        const normalizedX = (clientX / chartAreaWidth) * CHART_WIDTH;
        hoverX.value = normalizedX;

        if (chartData.value.length > 0) {
            let minDistance = Infinity;
            let nearestIndex = -1;

            chartData.value.forEach((_, index) => {
                const dataPointNormalizedX = (index / (chartData.value.length - 1)) * CHART_WIDTH;
                const distance = Math.abs(dataPointNormalizedX - normalizedX);

                if (distance < minDistance) {
                    minDistance = distance;
                    nearestIndex = index;
                }
            });

            if (nearestIndex !== -1 && chartData.value[nearestIndex]) {
                nearestDataPoint.value = chartData.value[nearestIndex];
                hoverX.value = (nearestIndex / (chartData.value.length - 1)) * CHART_WIDTH;
            }
        }
    };

    const handleMouseEnter = () => {
        isHovering.value = true;
    };

    const handleMouseLeave = () => {
        isHovering.value = false;
        nearestDataPoint.value = null;
    };

    const tooltipY = computed(() => {
        if (!nearestDataPoint.value || chartData.value.length < 2) return 0;
        const { minPrice, maxPrice } = chartCoordinates.value;
        const priceRange = maxPrice - minPrice || 1;
        const normalizedY = ((nearestDataPoint.value.price - minPrice) / priceRange) * PRICE_CHART_HEIGHT;
        return PRICE_CHART_HEIGHT - normalizedY;
    });

    const displayPrice = computed(() => props.selectedToken?.price ? formatPrice(props.selectedToken.price, props.selectedToken.decimals) : '0.00');
    const displayPriceChange = computed(() => props.selectedToken?.price_change_24h?.toFixed(2) ?? '0.00');
    const isPositiveChange = computed(() => (props.selectedToken?.price_change_24h ?? 0) >= 0);
    const high24h = computed(() => chartData.value.length ? `$${formatPrice(Math.max(...chartData.value.map(d => d.price)), props.selectedToken?.decimals)}` : 'N/A');
    const low24h = computed(() => chartData.value.length ? `$${formatPrice(Math.min(...chartData.value.map(d => d.price)), props.selectedToken?.decimals)}` : 'N/A');

    const tooltipTimestamp = computed(() => {
        if (!nearestDataPoint.value) return '';
        const date = new Date(nearestDataPoint.value.timestamp);
        return date.toLocaleString();
    });

    const displaySymbol = computed(() => {
        if (!props.selectedToken) return '';
        const symbol = props.selectedToken.symbol;
        const formatted = symbol.replace(/USDT_(BEP20|ERC20|TRC20)/i, (match) => {
            return match.replace('_', ' ');
        });
        return formatted.toUpperCase();
    });

    watch(() => props.selectedToken, (newToken) => {
        if (newToken) {
            const timeframe = timeframes.find(tf => tf.label === selectedTimeframe.value);
            fetchChartData(newToken, timeframe?.days || 1);
        } else {
            chartData.value = [];
        }
    }, { immediate: true });

    watch(selectedTimeframe, (newTimeframe) => {
        if (props.selectedToken) {
            const timeframe = timeframes.find(tf => tf.label === newTimeframe);
            fetchChartData(props.selectedToken, timeframe?.days || 1);
        }
    });
</script>

<template>
    <div class="card-crypto text-card-foreground rounded-xl p-4 sm:p-6" v-if="selectedToken">
        <div class="flex flex-wrap items-start justify-between gap-3 sm:gap-4 mb-4">
            <div class="flex items-center gap-3">
                <img :src="selectedToken.logo" :alt="`${selectedToken.symbol} logo`" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full" />
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">{{ displaySymbol }} / <span class="text-muted-foreground font-normal">USD</span></h2>
                    <p class="text-2xl sm:text-4xl font-extrabold text-foreground">${{ displayPrice }}</p>
                </div>
            </div>

            <div class="flex flex-col items-end">
                <span :class="['inline-flex items-center px-2 py-1 rounded-md text-sm font-semibold transition-colors duration-300', isPositiveChange ? 'btn-crypto' : 'bg-red-500/20 text-red-400']">
                    {{ isPositiveChange ? '+' : '' }}{{ displayPriceChange }}% (24h)
                </span>
                <div class="text-xs text-muted-foreground mt-2 space-y-0.5">
                    <p class="flex justify-between gap-2 sm:gap-4"><span class="text-muted-foreground">24h H:</span> <span class="font-medium text-card-foreground">{{ high24h }}</span></p>
                    <p class="flex justify-between gap-2 sm:gap-4"><span class="text-muted-foreground">24h L:</span> <span class="font-medium text-card-foreground">{{ low24h }}</span></p>
                </div>
            </div>
        </div>

        <hr class="border-b border-border my-4" />

        <div class="flex items-center gap-2 mb-6 overflow-x-auto scrollbar-hide py-1">
            <button v-for="tf in timeframes" :key="tf.label" @click="selectedTimeframe = tf.label"
                    :class="['flex-shrink-0 px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 focus:outline-none cursor-pointer',
                             selectedTimeframe === tf.label ? 'btn-crypto text-gray-900 scale-[0.98]' : 'bg-secondary text-secondary-foreground active:scale-[0.95]']">
                {{ tf.label }}
            </button>
        </div>

        <div class="relative rounded-lg h-72 sm:h-96 mb-6 overflow-hidden"
             @mousemove="handleMouseMove" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave" ref="chartContainer">

            <div v-if="isLoadingChart || chartError || chartData.length < 2" class="absolute inset-0 flex items-center justify-center bg-secondary z-10">
                <div v-if="isLoadingChart" class="flex flex-col items-center gap-3">
                    <div class="flex space-x-2">
                        <div class="dot dot-1 bg-primary"></div>
                        <div class="dot dot-2 bg-primary"></div>
                        <div class="dot dot-3 bg-primary"></div>
                    </div>
                    <span class="text-sm text-muted-foreground pulse">Loading chart data...</span>
                </div>

                <div v-else-if="chartError" class="text-center p-6">
                    <div class="flex flex-col items-center mb-4 text-red-500">
                        <AlertTriangle class="w-10 h-10 mb-2" />
                        <span class="text-base font-semibold">{{ chartError }}</span>
                    </div>

                    <button
                        @click="fetchChartData(selectedToken, timeframes.find(tf => tf.label === selectedTimeframe)?.days || 1)"
                        class="btn-crypto mt-3 px-4 py-2 text-sm sm:text-base cursor-pointer">
                        <span class="mr-1">Retry</span>
                    </button>
                </div>

                <div v-else class="text-center p-6">
                    <span class="text-sm text-muted-foreground">Not enough data to display the chart.</span>
                </div>
            </div>

            <svg v-else class="w-full h-full" :viewBox="`0 0 ${CHART_WIDTH} ${TOTAL_SVG_HEIGHT}`" preserveAspectRatio="none">

                <g :transform="`translate(0, 0)`">

                    <line v-for="(label, idx) in priceLabels" :key="'grid-h-' + idx"
                          x1="0" :y1="label.y" x2="100" :y2="label.y"
                          stroke="rgba(107, 114, 128, 0.15)" stroke-width="0.5" stroke-dasharray="2,2" class="transition-all duration-300" />

                    <path :d="chartCoordinates.areaPath" :class="[chartCoordinates.isPositive ? 'fill-emerald-500/10' : 'fill-red-500/10']" style="transition: all 0.5s ease-out;" />

                    <path fill="none" :stroke="chartCoordinates.isPositive ? '#10b981' : '#ef4444'" stroke-width="0.2" :d="chartCoordinates.linePath" style="transition: all 0.5s ease-out;" />

                    <g v-if="isHovering && nearestDataPoint">
                        <line x1="0" :y1="tooltipY" x2="100" :y2="tooltipY" stroke="#6b7280" stroke-width="0.5" stroke-dasharray="2,2" />
                        <circle :cx="hoverX" :cy="tooltipY" r="0.75" :fill="chartCoordinates.isPositive ? '#10b981' : '#ef4444'" stroke="#fff" stroke-width="0.2" />
                    </g>
                </g>

                <g :transform="`translate(0, ${PRICE_CHART_HEIGHT + VOLUME_OFFSET_Y})`">

                    <line x1="0" y1="0" x2="100" y2="0" stroke="rgba(107, 114, 128, 0.25)" stroke-width="0.3" />

                    <rect v-for="(vol, idx) in volumeBars" :key="'vol-' + idx"
                          :x="vol.x"
                          :y="VOLUME_CHART_HEIGHT - vol.height"
                          :width="vol.width" :height="vol.height"
                          :class="['transition-all duration-300', vol.color]"
                          rx="0.2" ry="0.2" />

                    <line v-if="isHovering && nearestDataPoint" :x1="hoverX" y1="0" :x2="hoverX" :y2="VOLUME_CHART_HEIGHT" stroke="#6b7280" stroke-width="0.5" stroke-dasharray="2,2" />
                </g>

                <g class="text-xs">
                    <text v-for="(label, idx) in priceLabels" :key="'y-label-' + idx"
                          x="101.5" :y="label.y" dy="0.3em"
                          text-anchor="start" :fill="'hsl(var(--muted-foreground))'"
                          class="hidden md:block transition-all duration-300">
                        {{ label.price }}
                    </text>
                    <rect v-if="isHovering && nearestDataPoint" x="100" :y="tooltipY - 1.5" width="10" height="3" fill="#6b7280" />
                    <text v-if="isHovering && nearestDataPoint" x="101.5" :y="tooltipY" dy="0.3em" text-anchor="start" class="font-medium text-xs text-card-foreground">
                        ${{ formatPrice(nearestDataPoint.price, selectedToken?.decimals) }}
                    </text>
                </g>
            </svg>

            <div class="absolute left-0 right-0 h-4 flex justify-between px-2 text-xs text-muted-foreground select-none"
                 :style="{ bottom: `4px` }">
                 <span v-for="(label, idx) in timeLabels" :key="'x-label-' + idx"
                       :style="{ left: `${(label.x / 100) * 100}%`, transform: 'translateX(-50%)' }"
                       class="absolute bottom-0 hidden md:block">
                     {{ label.label }}
                 </span>
            </div>

            <div v-if="isHovering && nearestDataPoint"
                 :style="{ left: `${(hoverX / 100) * 100}%`, top: `${(tooltipY / TOTAL_SVG_HEIGHT) * 100}%` }"
                 class="absolute transform -translate-x-1/2 -translate-y-[110%] min-w-[140px] bg-secondary text-secondary-foreground backdrop-blur-sm text-card-foreground p-2 text-xs rounded-md pointer-events-none transition-opacity duration-100 z-20">
                <p class="font-bold text-primary mb-1">{{ tooltipTimestamp }}</p>
                <p>Price: <span class="font-semibold text-foreground">${{ formatPrice(nearestDataPoint.price, selectedToken?.decimals) }}</span></p>
                <p>Volume: <span class="font-semibold text-foreground">{{ (nearestDataPoint.volume / 1e6).toFixed(2) }}M</span></p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-center">
            <TextLink :href="route('user.send.index')" class="bg-secondary text-secondary-foreground py-3 rounded-xl text-base font-semibold hover:bg-muted transition-colors duration-200">
                SEND
            </TextLink>

            <TextLink :href="route('user.receive.index')" class="bg-primary text-primary-foreground py-3 rounded-xl text-base font-semibold hover:bg-primary/90 transition-colors duration-200">
                RECEIVE
            </TextLink>
        </div>
    </div>
    <div v-else class="flex items-center justify-center p-8 bg-gray-800 rounded-xl text-muted-foreground min-h-[500px] text-lg">
        Select a token to view its chart.
    </div>
</template>

<style scoped>
    /* New styles for dot animation */
    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: dot-pulse 1.2s infinite ease-in-out;
    }

    .dot-1 {
        animation-delay: 0s;
    }

    .dot-2 {
        animation-delay: 0.2s;
    }

    .dot-3 {
        animation-delay: 0.4s;
    }

    @keyframes dot-pulse {
        0%, 100% {
            transform: translateY(0);
            opacity: 1;
        }
        50% {
            transform: translateY(-8px);
            opacity: 0.5;
        }
    }

    /* Existing styles */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
