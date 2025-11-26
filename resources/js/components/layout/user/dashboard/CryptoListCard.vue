<script setup lang="ts">
    import { TrendingUpIcon, TrendingDownIcon, Loader2, RotateCwIcon, Wallet2 } from 'lucide-vue-next';
    import { computed, ref, watch } from 'vue';

    type Token = {
        symbol: string;
        name: string;
        logo: string;
        decimals: number;
        price_change_24h: number;
        price?: number;
    };

    type FullToken = Token & {
        balance: number;
        value: number;
    };

    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        portfolioChange24h: number;
    }>();

    const emit = defineEmits<{
        (e: 'select-token', token: FullToken): void
    }>()

    const sortKey = ref<'name' | 'price' | 'change'>('name');
    const sortOrder = ref<'asc' | 'desc'>('asc');
    const displayLimit = ref(3);
    const increment = 3;
    const isLoading = ref(false);
    const selectedTokenSymbol = ref<string | null>(null);

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


    const processedTokens = computed<FullToken[]>(() => {
        if (!props.tokens || !props.userBalances || !props.prices) {
            return [];
        }

        return props.tokens.map(token => {
            const balance = props.userBalances[token.symbol] ?? 0;
            const price = props.prices[token.symbol] ?? 0;
            const value = balance * price;

            return {
                ...token,
                balance: balance,
                price: price,
                value: value,
            } as FullToken;
        });
    });

    const sortedTokens = computed<FullToken[]>(() => {
        const tokens = [...processedTokens.value];

        tokens.sort((a, b) => {
            let aValue: any;
            let bValue: any;

            if (sortKey.value === 'name') {
                aValue = a.name.toLowerCase();
                bValue = b.name.toLowerCase();
            } else if (sortKey.value === 'price') {
                aValue = a.price;
                bValue = b.price;
            } else if (sortKey.value === 'change') {
                aValue = a.price_change_24h;
                bValue = b.price_change_24h;
            } else {
                return 0;
            }

            if (typeof aValue === 'string' && typeof bValue === 'string') {
                return sortOrder.value === 'asc' ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
            }

            return sortOrder.value === 'asc' ? aValue - bValue : bValue - aValue;
        });

        return tokens;
    });

    const displayedTokens = computed(() => {
        return sortedTokens.value.slice(0, displayLimit.value);
    });

    const hasMoreTokens = computed(() => {
        return displayLimit.value < sortedTokens.value.length;
    });

    // REMOVED: The faulty displaySymbol computed property

    const selectToken = (token: FullToken) => {
        selectedTokenSymbol.value = token.symbol;
        emit('select-token', token);
    };

    const sortBy = (key: 'name' | 'price' | 'change') => {
        if (sortKey.value === key) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortKey.value = key;
            sortOrder.value = (key === 'name' ? 'asc' : 'desc');
        }

        if (sortedTokens.value.length > 0) {
            selectToken(sortedTokens.value[0]);
        }
    };

    const loadMore = () => {
        if (isLoading.value) return;
        isLoading.value = true;
        setTimeout(() => {
            displayLimit.value += increment;
            isLoading.value = false;
        }, 500);
    };

    watch(sortedTokens, (newTokens) => {
        if (!selectedTokenSymbol.value && newTokens.length > 0) {
            selectToken(newTokens[0]);
        }
    }, { immediate: true });
</script>

<template>
    <div class="card-crypto p-4 sm:p-6">
        <div v-if="displayedTokens.length > 0" class="flex items-center justify-between text-xs sm:text-sm text-muted-foreground mb-3 sm:mb-4 px-1">
            <button @click="sortBy('name')" class="flex items-center gap-1 hover:text-card-foreground">
                <span>Name</span>
                <span v-if="sortKey === 'name'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </button>

            <button @click="sortBy('change')" class="flex items-center gap-1 hover:text-card-foreground">
                <span class="hidden sm:inline">Change 24h</span>
                <span class="sm:hidden">Change</span>
                <span v-if="sortKey === 'change'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </button>
        </div>

        <div v-else class="text-center text-sm text-muted-foreground py-10 px-4">
            <div class="flex justify-center mb-4">
                <Wallet2 class="h-12 w-12 text-muted-foreground" />
            </div>
            <p class="text-lg font-medium mb-2 text-card-foreground">No Tokens Found</p>
            <p class="text-sm">We couldn't find any tokens that match your current visibility settings or filters.</p>
        </div>

        <div class="no-scrollbar overflow-y-auto max-h-[400px]">
            <div class="space-y-2 sm:space-y-3">
                <div v-for="token in displayedTokens"
                     :key="token.symbol"
                     @click="selectToken(token)"
                     :class="[
                    'flex items-center gap-2 rounded-lg border p-2 cursor-pointer',
                        selectedTokenSymbol === token.symbol
                        ? 'bg-primary/10 border-primary text-primary ring-primary'
                        : 'bg-secondary/20 border-border text-muted-foreground hover:border-primary/50',
                    ]">

                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <button @click.stop>
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" viewBox="0 0 16 16" fill="currentColor" :stroke="selectedTokenSymbol === token.symbol ? 'currentColor' : 'currentColor'" stroke-width="1.5">
                                <path d="M8 1l2.5 5 5.5.8-4 3.9.9 5.3-4.9-2.6-4.9 2.6.9-5.3-4-3.9 5.5-.8z" />
                            </svg>
                        </button>
                        <img :src="token.logo" :alt="token.symbol" loading="lazy" class="w-8 h-8 rounded-full border border-border flex-shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="text-card-foreground font-medium text-xs sm:text-sm truncate">{{ formatSymbol(token.symbol) }}</p>
                            <p class="text-muted-foreground text-xs">{{ token.name }}</p>
                        </div>
                    </div>

                    <div class="text-right ml-2 sm:ml-4">
                        <div class="font-semibold text-card-foreground">
                            {{ token.balance.toFixed(4) }}
                        </div>

                        <div class="text-sm text-muted-foreground">
                            ${{ token.value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </div>

                        <div :class="['text-xs flex items-center justify-end gap-1', token.price_change_24h >= 0 ? 'text-primary' : 'text-destructive']">
                            <TrendingUpIcon v-if="token.price_change_24h >= 0" class="w-3 h-3" />
                            <TrendingDownIcon v-else class="w-3 h-3" />
                            {{ token.price_change_24h >= 0 ? '+' : '' }}{{ token.price_change_24h.toFixed(2) }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="hasMoreTokens" class="mt-4 pt-4 flex justify-center">
            <button
                @click="loadMore"
                :disabled="isLoading"
                class="bg-secondary/50 text-secondary-foreground rounded-lg sm:rounded-xl hover:bg-muted/70 py-2 sm:py-2.5 cursor-pointer text-sm sm:text-base disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 px-6">

                <template v-if="isLoading">
                    <Loader2 class="w-4 h-4 animate-spin" />
                    <span>Loading...</span>
                </template>

                <template v-else>
                    <RotateCwIcon class="w-4 h-4" />
                    <span>Load More</span>
                </template>
            </button>
        </div>
    </div>
</template>

<style scoped>
    /* WebKit browsers (Chrome, Safari) */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    /* MS Edge and Firefox */
    .no-scrollbar {
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
    }
</style>
