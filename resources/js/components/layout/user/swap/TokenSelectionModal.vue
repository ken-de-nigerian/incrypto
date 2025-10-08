<script setup lang="ts">
import { ref, computed, watch } from 'vue';
    import { SearchIcon, XIcon } from 'lucide-vue-next';

    interface Token {
        symbol: string;
        name: string;
        logo: string;
    }

    const props = defineProps<{
        isOpen: boolean;
        tokens: Token[];
        popularTokens: string[];
        userBalances: Record<string, number>;
        prices: Record<string, number>;
    }>();

    const emit = defineEmits(['update:isOpen', 'select']);

    const searchQuery = ref('');
    const activeTab = ref<'popular' | 'all'>('popular');

    const filteredTokens = computed(() => {
        if (!searchQuery.value) return props.tokens;
        const lowerQuery = searchQuery.value.toLowerCase();
        return props.tokens.filter(
            token =>
                token.symbol.toLowerCase().includes(lowerQuery) ||
                token.name.toLowerCase().includes(lowerQuery)
        );
    });

    const popularTokensList = computed(() => {
        return props.tokens.filter(t => props.popularTokens.includes(t.symbol));
    });

    const selectToken = (token: Token) => {
        emit('select', token);
        emit('update:isOpen', false);
        searchQuery.value = '';
    };

    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @click.self="emit('update:isOpen', false)">
            <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl flex flex-col">
                <div class="p-4 border-b border-border flex items-center justify-between">
                    <h3 class="text-lg font-bold text-card-foreground">Select Token</h3>
                    <button @click="emit('update:isOpen', false)" class="p-2 hover-bg-muted rounded-lg">
                        <XIcon class="w-5 h-5 text-muted-foreground" />
                    </button>
                </div>
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
                        :class="['px-4 py-2 text-sm font-medium relative', activeTab === 'popular' ? 'text-primary' : 'text-muted-foreground']">
                        Popular
                        <div v-if="activeTab === 'popular'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                    </button>
                    <button
                        @click="activeTab = 'all'"
                        :class="['px-4 py-2 text-sm font-medium relative', activeTab === 'all' ? 'text-primary' : 'text-muted-foreground']">
                        All Tokens
                        <div v-if="activeTab === 'all'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                    </button>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <div
                        v-for="token in activeTab === 'popular' ? popularTokensList : filteredTokens"
                        :key="token.symbol"
                        @click="selectToken(token)"
                        class="p-4 hover:bg-muted cursor-pointer flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img :src="token.logo" :alt="token.symbol" class="w-8 h-8 rounded-full" />
                            <div>
                                <div class="font-semibold text-card-foreground">{{ token.symbol }}</div>
                                <div class="text-xs text-muted-foreground">{{ token.name }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium text-card-foreground">
                                {{ (userBalances[token.symbol] || 0).toFixed(4) }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                ${{ ((userBalances[token.symbol] || 0) * (prices[token.symbol] || 0)).toFixed(2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
