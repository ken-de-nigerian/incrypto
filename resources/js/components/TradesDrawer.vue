<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Loader2Icon, TrendingUp, X } from 'lucide-vue-next';

    const props = defineProps<{
        modelValue: boolean;
        trades: Array<{
            id: number;
            pair: string;
            pairName: string;
            type: 'Buy' | 'Sell';
            status: 'Open' | 'Closed';
            pnl: number;
            timestamp: string;
        }>;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', v: boolean): void;
    }>();

    const displayCount = ref(20);
    const loadingMore = ref(false);
    const itemsPerLoad = 20;
    const scrollEl = ref<HTMLElement | null>(null);

    const displayedTrades = computed(() => props.trades.slice(0, displayCount.value));
    const hasMore = computed(() => displayCount.value < props.trades.length);

    const loadMore = () => {
        if (loadingMore.value || !hasMore.value) return;
        loadingMore.value = true;
        setTimeout(() => {
            displayCount.value = Math.min(displayCount.value + itemsPerLoad, props.trades.length);
            loadingMore.value = false;
        }, 500);
    };

    const onScroll = (e: Event) => {
        const el = e.target as HTMLElement;
        const distance = el.scrollHeight - (el.scrollTop + el.clientHeight);
        if (distance < 300 && hasMore.value && !loadingMore.value) loadMore();
    };

    const close = () => emit('update:modelValue', false);
</script>

<template>
    <Teleport to="body">
        <!-- Overlay -->
        <Transition name="fade">
            <div v-if="modelValue" @click="close" class="fixed inset-0 bg-black/50 z-30" />
        </Transition>

        <!-- Desktop right sidebar -->
        <Transition name="slide-left">
            <div
                v-if="modelValue"
                class="hidden sm:block fixed top-0 right-0 h-screen w-80 bg-card border-l border-border z-40 flex flex-col overflow-hidden"
            >
                <div class="flex items-center justify-between bg-muted/30 px-6 py-4 border-b border-border">
                    <h3 class="font-semibold text-card-foreground flex items-center gap-2">
                        <TrendingUp class="w-5 h-5 text-primary" /> Open Trades
                    </h3>
                    <button @click="close" class="p-2 hover:bg-muted rounded-lg transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div
                    ref="scrollEl"
                    @scroll="onScroll"
                    class="flex-1 max-h-[90vh] padding-bottom padding-bottom p-4"
                >
                    <div v-if="props.trades.length === 0" class="text-center py-8 text-muted-foreground">
                        No open trades
                    </div>

                    <div
                        v-for="trade in displayedTrades"
                        :key="trade.id"
                        class="mb-3 p-3 bg-muted/20 border border-border/50 rounded-lg"
                    >
                        <div class="flex justify-between items-start mb-1">
                            <div>
                                <p class="text-sm font-semibold">
                                    {{ trade.pair }} <span class="text-xs text-muted-foreground">({{ trade.pairName }})</span>
                                </p>
                                <p class="text-xs" :class="trade.type === 'Buy' ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ trade.type }} • {{ trade.status }}
                                </p>
                            </div>
                            <p class="text-sm font-medium" :class="trade.pnl >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                {{ trade.pnl >= 0 ? '+' : '' }}${{ trade.pnl.toFixed(2) }}
                            </p>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ new Date(trade.timestamp).toLocaleString() }}</p>
                    </div>

                    <div v-if="loadingMore" class="flex justify-center py-4">
                        <Loader2Icon class="w-4 h-4 text-primary animate-spin" />
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Mobile bottom sheet -->
        <Transition name="slide-up">
            <div
                v-if="modelValue"
                class="sm:hidden fixed bottom-0 left-0 right-0 bg-card border-t border-border z-40 max-h-[calc(100vh-5rem)] rounded-t-2xl flex flex-col overflow-hidden"
            >
                <div class="flex items-center justify-between bg-muted/30 px-4 py-3 border-b border-border">
                    <h3 class="font-semibold text-card-foreground flex items-center gap-2">
                        <TrendingUp class="w-5 h-5 text-primary" /> Open Trades
                    </h3>
                    <button @click="close" class="p-2 hover:bg-muted rounded-lg transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div
                    ref="scrollEl"
                    @scroll="onScroll"
                    class="flex-1 max-h-[90vh] padding-bottom padding-bottom p-4"
                >
                    <div v-if="props.trades.length === 0" class="text-center py-8 text-muted-foreground">
                        No open trades
                    </div>

                    <div
                        v-for="trade in displayedTrades"
                        :key="trade.id"
                        class="mb-3 p-3 bg-muted/20 border border-border/50 rounded-lg"
                    >
                        <div class="flex justify-between items-start mb-1">
                            <div>
                                <p class="text-sm font-semibold">
                                    {{ trade.pair }}
                                </p>
                                <p class="text-xs" :class="trade.type === 'Buy' ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ trade.type }} • {{ trade.status }}
                                </p>
                            </div>
                            <p class="text-sm font-medium" :class="trade.pnl >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                {{ trade.pnl >= 0 ? '+' : '' }}${{ trade.pnl.toFixed(2) }}
                            </p>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ trade.pairName }}</p>
                    </div>

                    <div v-if="loadingMore" class="flex justify-center py-4">
                        <Loader2Icon class="w-4 h-4 text-primary animate-spin" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
    .fade-enter-active,
    .fade-leave-active,
    .slide-left-enter-active,
    .slide-left-leave-active,
    .slide-up-enter-active,
    .slide-up-leave-active { transition: all 0.3s ease; }

    .fade-enter-from,
    .fade-leave-to { opacity: 0; }
    .slide-left-enter-from,
    .slide-left-leave-to { transform: translateX(100%); }
    .slide-up-enter-from,
    .slide-up-leave-to { transform: translateY(100%); }

    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgb(100 116 139 / 0.5) transparent;
    }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgb(100 116 139 / 0.5);
        border-radius: 2px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: rgb(100 116 139 / 0.8); }

    .padding-bottom {
        padding-bottom: 150px;
    }
</style>
