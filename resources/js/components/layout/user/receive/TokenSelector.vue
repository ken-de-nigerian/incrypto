<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { SearchIcon, TrendingDownIcon, TrendingUpIcon } from 'lucide-vue-next';

    const props = defineProps<{
        tokens: Array<any>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        popularTokensList: string[];
    }>();

    const emit = defineEmits(['token-selected']);

    const searchQuery = ref('');
    const activeTab = ref<'popular' | 'all'>('popular');

    const filteredTokens = computed(() => {
        if (!props.tokens) return [];
        const query = searchQuery.value.toLowerCase();
        return props.tokens.filter(token =>
            token.symbol.toLowerCase().includes(query) ||
            token.name.toLowerCase().includes(query)
        );
    });

    const popularTokens = computed(() => {
        if (!props.tokens || !props.popularTokensList) return [];
        return props.tokens.filter(t => props.popularTokensList.includes(t.symbol));
    });

    const displayedTokens = computed(() => {
        const tokensToDisplay = activeTab.value === 'popular' ? popularTokens.value : filteredTokens.value;
        return tokensToDisplay.map(token => ({
            ...token,
            balance: props.userBalances[token.symbol] || 0,
            value: (props.userBalances[token.symbol] || 0) * (props.prices[token.symbol] || 0),
        }));
    });

    const selectToken = (token: any) => {
        emit('token-selected', token);
    };
</script>

<template>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-card-foreground">Receive Crypto</h1>
        <p class="text-sm text-muted-foreground mt-1">Select a token to view your receiving address</p>
    </div>

    <div class="bg-card border border-border rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-border">
            <div class="relative">
                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by name or symbol"
                    class="w-full pl-10 pr-4 py-3 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />
            </div>
        </div>

        <div class="px-4 pt-2 flex gap-2 border-b border-border">
            <button
                @click="activeTab = 'popular'"
                :class="['px-4 py-2 text-sm font-medium relative', activeTab === 'popular' ? 'text-primary' : 'text-muted-foreground']"
            >
                Popular
                <div v-if="activeTab === 'popular'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
            </button>
            <button
                @click="activeTab = 'all'"
                :class="['px-4 py-2 text-sm font-medium relative', activeTab === 'all' ? 'text-primary' : 'text-muted-foreground']"
            >
                All Assets
                <div v-if="activeTab === 'all'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
            </button>
        </div>

        <div class="max-h-[600px] overflow-y-auto">
            <div v-if="displayedTokens.length === 0" class="p-8 text-center">
                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <SearchIcon class="w-8 h-8 text-muted-foreground" />
                </div>
                <p class="text-sm text-muted-foreground">No tokens found</p>
            </div>
            <div
                v-for="token in displayedTokens"
                :key="token.symbol"
                @click="selectToken(token)"
                class="p-4 border-b border-border last:border-b-0 hover:bg-muted/50 cursor-pointer transition-colors"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <img :src="token.logo" :alt="token.symbol" class="w-10 h-10 rounded-full flex-shrink-0" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <div class="font-semibold text-card-foreground">{{ token.symbol }}</div>
                            </div>
                            <div class="text-xs text-muted-foreground truncate">{{ token.name }}</div>
                            <div v-if="token.address" class="text-xs text-muted-foreground font-mono truncate mt-0.5">
                                {{ token.address.slice(0, 10) }}...{{ token.address.slice(-8) }}
                            </div>
                        </div>
                    </div>

                    <div class="text-right ml-2 sm:ml-4">
                        <div class="font-semibold text-card-foreground">
                            {{ token.balance.toFixed(4) }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            ${{ token.value.toFixed(2) }}
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
    </div>
</template>
