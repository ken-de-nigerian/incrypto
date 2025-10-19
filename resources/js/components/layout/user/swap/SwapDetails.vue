<script setup lang="ts">
    import { ref, computed } from 'vue';
    import { LayersIcon, ChevronDownIcon, TrendingUpIcon, TrendingDownIcon, AlertCircleIcon } from 'lucide-vue-next';

    const props = defineProps<{
        fromToken: { symbol: string } | null;
        toToken: { symbol: string } | null;
        fromAmount: string;
        toAmount: string;
        prices: Record<string, number>;
        slippage: number;
        gasPrices: Record<string, { gwei: number; time: string; usd: number }>;
        gasPreset: 'low' | 'medium' | 'high';
    }>();

    const showRouteDetails = ref(false);

    const exchangeRate = computed(() => {
        if (!props.fromAmount || isNaN(parseFloat(props.fromAmount))) return 0;
        if (!props.fromToken || !props.toToken || !props.prices) return 0;
        const fromPrice = props.prices[props.fromToken.symbol] || 0;
        const toPrice = props.prices[props.toToken.symbol] || 0;
        return toPrice === 0 ? 0 : fromPrice / toPrice;
    });

    const priceImpact = computed(() => {
        if (!props.fromAmount || !props.toAmount) return 0;
        if (!props.fromToken || !props.prices) return 0;
        const amount = parseFloat(props.fromAmount);
        const liquidity = 1000000;
        return (amount * (props.prices[props.fromToken.symbol] || 0) / liquidity) * 100;
    });

    const priceImpactClass = computed(() => {
        if (priceImpact.value < 0.1) return 'text-primary';
        if (priceImpact.value < 1) return 'text-warning';
        return 'text-destructive';
    });

    const minimumReceived = computed(() => {
        if (!props.toAmount) return '0';
        const amount = parseFloat(props.toAmount);
        const slippageMultiplier = 1 - props.slippage / 100;
        return (amount * slippageMultiplier).toFixed(6);
    });

    const estimatedGas = computed(() => {
        return props.gasPrices[props.gasPreset] || props.gasPrices.medium;
    });

    const swapRoute = computed(() => {
        if (!props.fromToken || !props.toToken) return { primary: '', pools: [], alternatives: [] };
        return {
            primary: `${formatSymbol(props.fromToken.symbol)} → ${formatSymbol(props.toToken.symbol)} via Uniswap V3`,
            pools: ['Uniswap V3 Pool', '0.3% fee'],
            alternatives: [
                `${formatSymbol(props.fromToken.symbol)} → ${formatSymbol(props.toToken.symbol)} via SushiSwap (0.2% worse)`,
                `${formatSymbol(props.fromToken.symbol)} → USDC → ${formatSymbol(props.toToken.symbol)} via Curve (0.5% worse)`,
            ],
        };
    });

    // Function to format the token symbol
    const formatSymbol = (symbol: string): string => {
        if (!symbol) return '';

        // Regex to find USDT_ followed by BEP20, ERC20, or TRC20 (case-insensitive)
        const formatted = symbol.replace(/USDT_(BEP20|ERC20|TRC20)/i, (match) => {
            // Replace the underscore with a space only in the matched segment
            return match.replace('_', ' ');
        });

        return formatted.toUpperCase();
    };
</script>

<template>
    <div v-if="fromAmount && toAmount && fromToken && toToken" class="mt-4 p-3 sm:p-4 bg-muted/30 rounded-xl space-y-2 text-xs sm:text-sm">
        <div class="flex items-center justify-between">
            <span class="text-muted-foreground">Rate</span>
            <span class="font-medium text-card-foreground">
        1 {{ formatSymbol(fromToken.symbol) }} = {{ exchangeRate.toFixed(6) }} {{ formatSymbol(toToken.symbol) }}
      </span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-muted-foreground">Price Impact</span>
            <span :class="priceImpactClass" class="font-medium flex items-center gap-1">
        <TrendingUpIcon v-if="priceImpact < 0.1" class="w-3 h-3" />
        <TrendingDownIcon v-else class="w-3 h-3" />
        {{ priceImpact.toFixed(2) }}%
      </span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-muted-foreground">Minimum Received</span>
            <span class="font-medium text-card-foreground">{{ minimumReceived }} {{ formatSymbol(toToken.symbol) }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-muted-foreground">Est. Gas Fee</span>
            <span class="font-medium text-card-foreground">${{ estimatedGas.usd.toFixed(2) }}</span>
        </div>
        <button
            @click="showRouteDetails = !showRouteDetails"
            class="w-full flex items-center justify-between py-2 text-left hover:opacity-70 cursor-pointer"
        >
          <span class="text-muted-foreground flex items-center gap-2">
            <LayersIcon class="w-4 h-4" />
            Route
          </span>
            <ChevronDownIcon :class="['w-4 h-4 text-muted-foreground transition-transform', showRouteDetails && 'rotate-180']" />
        </button>
        <div v-if="showRouteDetails" class="pl-4 sm:pl-6 space-y-2 pt-2 border-t border-border">
            <div class="text-xs">
                <div class="font-medium text-card-foreground mb-1">Best Route:</div>
                <div class="text-muted-foreground">{{ swapRoute.primary }}</div>
                <div class="text-muted-foreground mt-1">{{ swapRoute.pools.join(' • ') }}</div>
            </div>
            <div class="text-xs">
                <div class="font-medium text-card-foreground mb-1">Alternative Routes:</div>
                <div v-for="(alt, idx) in swapRoute.alternatives" :key="idx" class="text-muted-foreground">
                    • {{ alt }}
                </div>
            </div>
        </div>
        <div v-if="priceImpact > 3" class="mt-4 p-3 bg-destructive/10 border border-destructive/30 rounded-lg flex items-start gap-2">
            <AlertCircleIcon class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
            <div class="text-xs sm:text-sm">
                <p class="font-semibold text-destructive">High Price Impact Warning</p>
                <p class="text-muted-foreground mt-1">This swap has a price impact of {{ priceImpact.toFixed(2) }}%. Consider splitting into smaller trades.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
    @media (max-width: 320px) {
        .text-xs {
            font-size: 0.7rem;
        }
        .p-3 {
            padding: 0.5rem;
        }
    }
</style>
