<script setup lang="ts">
    import { BarChart3Icon } from 'lucide-vue-next';
    import { computed } from 'vue';

    const props = defineProps<{
        tokens: Array<{
            symbol: string;
            name: string;
            logo: string;
            price_change_24h: number;
        }>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
    }>();

    const topTokens = computed(() => {
        // Now 'props' can be accessed safely here.
        return props.tokens
            .map(token => ({
                ...token,
                balance: props.userBalances[token.symbol] || 0,
                value: (props.userBalances[token.symbol] || 0) * (props.prices[token.symbol] || 0),
            }))
            .filter(token => token.balance > 0)
            .sort((a, b) => b.value - a.value)
            .slice(0, 5);
    });
</script>

<template>
    <!-- Top Holdings -->
    <div class="bg-card border border-border rounded-2xl p-4">
        <h3 class="text-xs sm:text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
            <BarChart3Icon class="w-4 h-4" />
            Top Holdings
        </h3>

        <div class="space-y-3">
            <div v-for="token in topTokens" :key="token.symbol" class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <img :src="token.logo" :alt="token.symbol" class="w-6 h-6 rounded-full" />
                    <div>
                        <div class="text-xs sm:text-sm font-medium text-card-foreground">{{ token.symbol }}</div>
                        <div class="text-xs text-muted-foreground">{{ token.balance.toFixed(4) }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs sm:text-sm font-semibold text-card-foreground">${{ token.value.toFixed(2) }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* Ensure text doesn't overflow on small screens */
    .text-xs {
        font-size: 0.75rem;
    }
    @media (max-width: 320px) {
        .rounded-2xl {
            padding: 0.75rem;
        }
        .text-xs {
            font-size: 0.7rem;
        }
    }
</style>
