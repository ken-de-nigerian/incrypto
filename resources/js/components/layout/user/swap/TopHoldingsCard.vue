<script setup lang="ts">
    import { BarChart3Icon, Wallet } from 'lucide-vue-next';
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
            .slice(0, 5);
    });

    // Function to format the token symbol
    const formatSymbol = (symbol: string): string => {
        if (!symbol) return '';
        const formatted = symbol.replace(/USDT_(BEP20|ERC20|TRC20)/i, (match) => {
            return match.replace('_', ' ');
        });

        return formatted.toUpperCase();
    };
</script>

<template>
    <div class="hidden sm:block">
        <div class="bg-card border border-border rounded-2xl p-4">
            <div v-if="topTokens.length > 0">
                <h3 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                    <BarChart3Icon class="w-4 h-4" />
                    Top Holdings
                </h3>

                <div class="space-y-3">
                    <div v-for="token in topTokens" :key="token.symbol" class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <img :src="token.logo" :alt="token.symbol" loading="lazy" class="w-6 h-6 rounded-full" />
                            <div>
                                <div class="text-sm font-medium text-card-foreground">{{ formatSymbol(token.symbol) }}</div>
                                <div class="text-xs text-muted-foreground">{{ token.balance.toFixed(4) }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-card-foreground">${{ token.value.toFixed(2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center text-sm text-muted-foreground py-8">
                <div class="flex justify-center mb-3">
                    <Wallet class="h-10 w-10 text-muted-foreground/60" />
                </div>
                <p class="text-base font-medium mb-1 text-card-foreground">No Holdings</p>
                <p class="text-xs">Your portfolio will be tracked here once you acquire tokens.</p>
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
