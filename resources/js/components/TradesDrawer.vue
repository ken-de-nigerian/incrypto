<script setup lang="ts">
    import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
    import { Loader2Icon, TrendingUp, X } from 'lucide-vue-next';
    import QuickActionModal from '@/components/QuickActionModal.vue';

    const props = defineProps<{
        modelValue: boolean;
        trades: Array<{
            id: number;
            pair: string;
            pair_name: string;
            type: 'Up' | 'Down';
            amount: string;
            leverage: number
            duration: string;
            entry_price: string;
            exit_price: string | null;
            status: 'Open' | 'Closed';
            pnl: string;
            trading_mode: 'demo' | 'live';
            opened_at: string;
            closed_at: string | null;
            expiry_time: string;
        }>;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', v: boolean): void;
    }>();

    const displayCount = ref(20);
    const loadingMore = ref(false);
    const itemsPerLoad = 20;
    const scrollEl = ref<HTMLElement | null>(null);
    const isMobile = ref(false);

    const updateIsMobile = () => {
        isMobile.value = window.innerWidth < 640;
    };

    onMounted(() => {
        updateIsMobile();
        window.addEventListener('resize', updateIsMobile);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', updateIsMobile);
    });

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

    const formatTradeTimestamp = (isoString: string | null): string => {
        if (!isoString) return 'N/A';
        const date = new Date(isoString);

        // Time format: "1:25 AM"
        const time = date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        // Date format: "Mon, Nov 3"
        const datePart = date.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        });

        return `${datePart} at ${time}`;
    };
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="modelValue" @click="close" class="fixed inset-0 bg-black/50 z-30" />
        </Transition>

        <Transition name="slide-left">
            <div v-if="modelValue && !isMobile"
                 class="hidden sm:block fixed top-0 right-0 h-screen w-80 bg-card border-l border-border z-40 flex flex-col overflow-hidden">
                <div class="flex items-center justify-between bg-muted/30 px-6 py-4 border-b border-border">
                    <h3 class="font-semibold text-card-foreground flex items-center gap-2">
                        <TrendingUp class="w-5 h-5 text-primary" /> Trades History
                    </h3>
                    <button @click="close" class="p-2 hover:bg-muted rounded-lg transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div ref="scrollEl" @scroll="onScroll"
                     class="flex-1 max-h-[90vh] overflow-y-auto custom-scrollbar padding-bottom p-4">
                    <div v-if="props.trades.length === 0" class="text-center py-8 text-muted-foreground">
                        <div class="flex justify-center mb-3">
                            <TrendingUp class="h-10 w-10 text-muted-foreground" />
                        </div>
                        <p class="text-base font-medium mb-1 text-card-foreground">No Trades History Found</p>
                        <p class="text-xs">There are no trades recorded for this account.</p>
                    </div>

                    <div v-for="trade in displayedTrades" :key="trade.id"
                         class="mb-3 p-3 bg-muted/20 border border-border/50 rounded-lg">

                        <div class="flex justify-between items-start mb-2 border-b border-border/50 pb-2">
                            <div>
                                <p class="text-base font-bold">{{ trade.pair }}</p>
                                <p class="text-xs font-medium"
                                   :class="trade.type === 'Up' ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ trade.type }} • {{ trade.status }}
                                </p>
                            </div>
                            <p class="text-lg font-bold"
                               :class="parseFloat(trade.pnl) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                {{ parseFloat(trade.pnl) >= 0 ? '+' : '' }}${{ parseFloat(trade.pnl).toFixed(2) }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-y-1 text-xs">
                            <div class="col-span-2 text-muted-foreground mb-1">{{ trade.pair_name }}</div>

                            <div class="text-muted-foreground">Amount:</div>
                            <div class="text-right font-medium text-card-foreground">${{ trade.amount }}</div>

                            <div class="text-muted-foreground">Leverage:</div>
                            <div class="text-right font-medium text-card-foreground">{{ trade.leverage }}x</div>

                            <div class="text-muted-foreground">Duration:</div>
                            <div class="text-right font-medium text-card-foreground">{{ trade.duration }}</div>

                            <div class="text-muted-foreground">Entry Price:</div>
                            <div class="text-right font-mono text-card-foreground">{{ trade.entry_price }}</div>
                            <div class="text-muted-foreground">Exit Price:</div>
                            <div class="text-right font-mono"
                                 :class="{ 'text-card-foreground': trade.exit_price, 'text-muted-foreground italic': !trade.exit_price }">
                                {{ trade.exit_price || 'N/A' }}
                            </div>

                            <div class="text-muted-foreground">Opened At:</div>
                            <div class="text-right font-medium text-card-foreground">{{ formatTradeTimestamp(trade.opened_at) }}</div>

                            <div class="text-muted-foreground">Closed At:</div>
                            <div class="text-right font-medium"
                                 :class="{ 'text-card-foreground': trade.closed_at, 'text-muted-foreground italic': !trade.closed_at }">
                                {{ formatTradeTimestamp(trade.closed_at) }}
                            </div>

                            <div class="text-muted-foreground">Expiry Time:</div>
                            <div class="text-right font-medium text-card-foreground">{{ formatTradeTimestamp(trade.expiry_time) }}</div>

                            <div class="text-muted-foreground">Mode:</div>
                            <div class="text-right font-semibold text-primary">{{ trade.trading_mode.toUpperCase() }}</div>
                        </div>
                    </div>

                    <div v-if="loadingMore" class="flex justify-center py-4">
                        <Loader2Icon class="w-4 h-4 text-primary animate-spin" />
                    </div>
                </div>
            </div>
        </Transition>

        <QuickActionModal
            v-if="modelValue && isMobile"
            :is-open="true"
            title="Trades History"
            subtitle="View your trade positions"
            @close="close"
            class="sm:hidden">

            <div ref="scrollEl" @scroll="onScroll" class="flex-1 max-h-[90vh] overflow-y-auto no-scrollbar">
                <div v-if="props.trades.length === 0" class="text-center py-8 text-muted-foreground">
                    <div class="flex justify-center mb-3">
                        <TrendingUp class="h-10 w-10 text-muted-foreground" />
                    </div>
                    <p class="text-base font-medium mb-1 text-card-foreground">No Trades History Found</p>
                    <p class="text-xs">There are no trades recorded for this account.</p>
                </div>

                <div v-for="trade in displayedTrades" :key="trade.id"
                     class="mb-3 p-3 bg-muted/20 border border-border/50 rounded-lg">
                    <div class="flex justify-between items-start mb-2 border-b border-border/50 pb-2">
                        <div>
                            <p class="text-base font-bold">{{ trade.pair }}</p>
                            <p class="text-xs font-medium"
                               :class="trade.type === 'Up' ? 'text-emerald-400' : 'text-rose-400'">
                                {{ trade.type }} • {{ trade.status }}
                            </p>
                        </div>
                        <p class="text-lg font-bold"
                           :class="parseFloat(trade.pnl) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                            {{ parseFloat(trade.pnl) >= 0 ? '+' : '' }}${{ parseFloat(trade.pnl).toFixed(2) }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-y-1 text-xs">
                        <div class="col-span-2 text-muted-foreground mb-1">{{ trade.pair_name }}</div>

                        <div class="text-muted-foreground">Amount:</div>
                        <div class="text-right font-medium text-card-foreground">${{ trade.amount }}</div>

                        <div class="text-muted-foreground">Leverage:</div>
                        <div class="text-right font-medium text-card-foreground">{{ trade.leverage }}x</div>

                        <div class="text-muted-foreground">Duration:</div>
                        <div class="text-right font-medium text-card-foreground">{{ trade.duration }}</div>

                        <div class="text-muted-foreground">Entry Price:</div>
                        <div class="text-right font-mono text-card-foreground">{{ trade.entry_price }}</div>
                        <div class="text-muted-foreground">Exit Price:</div>
                        <div class="text-right font-mono"
                             :class="{ 'text-card-foreground': trade.exit_price, 'text-muted-foreground italic': !trade.exit_price }">
                            {{ trade.exit_price || 'N/A' }}
                        </div>

                        <div class="text-muted-foreground">Opened At:</div>
                        <div class="text-right font-medium text-card-foreground">{{ formatTradeTimestamp(trade.opened_at) }}</div>

                        <div class="text-muted-foreground">Closed At:</div>
                        <div class="text-right font-medium"
                             :class="{ 'text-card-foreground': trade.closed_at, 'text-muted-foreground italic': !trade.closed_at }">
                            {{ formatTradeTimestamp(trade.closed_at) }}
                        </div>

                        <div class="text-muted-foreground">Expiry Time:</div>
                        <div class="text-right font-medium text-card-foreground">{{ formatTradeTimestamp(trade.expiry_time) }}</div>

                        <div class="text-muted-foreground">Mode:</div>
                        <div class="text-right font-semibold text-primary">{{ trade.trading_mode.toUpperCase() }}</div>
                    </div>
                </div>

                <div v-if="loadingMore" class="flex justify-center py-4">
                    <Loader2Icon class="w-4 h-4 text-primary animate-spin" />
                </div>
            </div>
        </QuickActionModal>
    </Teleport>
</template>

<style scoped>
    .fade-enter-active,
    .fade-leave-active,
    .slide-left-enter-active,
    .slide-left-leave-active { transition: all 0.3s ease; }

    .fade-enter-from,
    .fade-leave-to { opacity: 0; }
    .slide-left-enter-from,
    .slide-left-leave-to { transform: translateX(100%); }

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

    /* Styling to hide the scrollbar while allowing scrolling */
    .no-scrollbar::-webkit-scrollbar {
        display: none; /* Hide scrollbar for Chrome, Safari and Opera */
    }

    .no-scrollbar {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>
