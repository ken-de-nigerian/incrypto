<script setup lang="ts">
    import { DollarSignIcon, TrendingUpIcon, TrendingDownIcon, BarChart3Icon } from 'lucide-vue-next';

    defineProps<{
        fromToken: { symbol: string; price_change_24h: number } | null;
        toToken: { symbol: string; price_change_24h: number } | null;
        prices: Record<string, number>;
    }>();

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
    <div class="bg-card border border-border rounded-2xl p-4">
        <div v-if="fromToken && toToken">
            <h3 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                <DollarSignIcon class="w-4 h-4" />
                Market Info
            </h3>

            <div class="space-y-3">
                <div>
                    <div class="text-xs text-muted-foreground mb-1">{{ formatSymbol(fromToken.symbol) }} Price</div>
                    <div class="text-lg font-bold text-card-foreground">
                        ${{ (prices[fromToken.symbol] || 0).toFixed(2) }}
                    </div>
                    <div :class="['text-xs flex items-center gap-1 mt-1', fromToken.price_change_24h >= 0 ? 'text-primary' : 'text-destructive']">
                        <TrendingUpIcon v-if="fromToken.price_change_24h >= 0" class="w-3 h-3" />
                        <TrendingDownIcon v-else class="w-3 h-3" />
                        {{ fromToken.price_change_24h.toFixed(2) }}% (24h)
                    </div>
                </div>

                <div class="border-t border-border pt-3">
                    <div class="text-xs text-muted-foreground mb-1">{{ formatSymbol(toToken.symbol) }} Price</div>
                    <div class="text-lg font-bold text-card-foreground">
                        ${{ (prices[toToken.symbol] || 0).toFixed(2) }}
                    </div>
                    <div :class="['text-xs flex items-center gap-1 mt-1', toToken.price_change_24h >= 0 ? 'text-primary' : 'text-destructive']">
                        <TrendingUpIcon v-if="toToken.price_change_24h >= 0" class="w-3 h-3" />
                        <TrendingDownIcon v-else class="w-3 h-3" />
                        {{ toToken.price_change_24h.toFixed(2) }}% (24h)
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center text-sm text-muted-foreground py-4">
            <div class="flex justify-center mb-3">
                <BarChart3Icon class="h-10 w-10 text-muted-foreground/60" />
            </div>
            <p class="text-base font-medium mb-1 text-card-foreground">No Market Data</p>
            <p class="text-xs">We are currently unable to display market activity. Please check back later.</p>
        </div>
    </div>
</template>
