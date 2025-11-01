<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import {
        ArrowUpDownIcon,
        FilterIcon,
        Loader2Icon,
        SortAscIcon,
        SortDescIcon,
        X
    } from 'lucide-vue-next';

    const props = defineProps<{
        modelValue: boolean;
        pairs: Array<{
            symbol: string;
            name: string;
            price: string;
            change: string;
            high: string;
            low: string;
            volume: string;
        }>;
        selectedSymbol?: string;
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', v: boolean): void;
        (e: 'select-pair', pair: typeof props.pairs[number]): void;
    }>();

    const searchQuery = ref('');
    const sortOrder = ref<'asc' | 'desc' | 'default'>('default');
    const showFilters = ref(false);
    const displayCount = ref(20);
    const isLoadingMore = ref(false);
    const itemsPerLoad = 20;
    const scrollEl = ref<HTMLElement | null>(null);

    const filteredPairs = computed(() => {
        let list = [...props.pairs];
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase();
            list = list.filter(p => p.symbol.toLowerCase().includes(q) || p.name.toLowerCase().includes(q));
        }
        if (sortOrder.value === 'asc') list.sort((a, b) => a.symbol.localeCompare(b.symbol));
        else if (sortOrder.value === 'desc') list.sort((a, b) => b.symbol.localeCompare(a.symbol));
        return list;
    });

    const displayedPairs = computed(() => filteredPairs.value.slice(0, displayCount.value));
    const hasMore = computed(() => displayCount.value < filteredPairs.value.length);
    const hasActiveFilters = computed(() => searchQuery.value.trim() !== '');

    const toggleSort = () => {
        sortOrder.value = sortOrder.value === 'default' ? 'asc' : sortOrder.value === 'asc' ? 'desc' : 'default';
    };
    const clearFilters = () => {
        searchQuery.value = '';
        sortOrder.value = 'default';
    };

    const loadMore = () => {
        if (isLoadingMore.value || !hasMore.value) return;
        isLoadingMore.value = true;
        setTimeout(() => {
            displayCount.value = Math.min(displayCount.value + itemsPerLoad, filteredPairs.value.length);
            isLoadingMore.value = false;
        }, 500);
    };

    const onScroll = (e: Event) => {
        const el = e.target as HTMLElement;
        const distance = el.scrollHeight - (el.scrollTop + el.clientHeight);
        if (distance < 300 && hasMore.value && !isLoadingMore.value) loadMore();
    };

    watch(searchQuery, () => {
        displayCount.value = Math.min(itemsPerLoad, filteredPairs.value.length);
        scrollEl.value?.scrollTo({ top: 0 });
    });

    const close = () => emit('update:modelValue', false);
</script>

<template>
    <Teleport to="body">
        <!-- Overlay -->
        <Transition name="fade">
            <div v-if="modelValue" @click="close" class="fixed inset-0 bg-black/50 z-30" />
        </Transition>

        <!-- Desktop left sidebar -->
        <Transition name="slide-right">
            <div
                v-if="modelValue"
                class="hidden sm:block fixed top-0 left-0 h-screen w-80 bg-card border-r border-border z-40 flex flex-col overflow-hidden"
            >
                <div class="flex items-center justify-between bg-muted/30 px-6 py-4 border-b border-border">
                    <h3 class="font-semibold text-card-foreground flex items-center gap-2">
                        <ArrowUpDownIcon class="w-5 h-5 text-muted-foreground" /> Available Pairs
                    </h3>
                    <button @click="close" class="p-2 hover:bg-muted rounded-lg transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Search / filter -->
                <div class="bg-muted/30 px-6 py-4 border-b border-border space-y-2">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search pairs..."
                        class="w-full px-3 py-2 bg-background border border-border rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    />
                    <button
                        @click="showFilters = !showFilters"
                        class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-muted/70 hover:bg-muted/80 text-muted-foreground border border-border flex items-center gap-2 cursor-pointer justify-center transition"
                        :class="{ 'bg-primary/10 text-primary border-primary/30': hasActiveFilters }"
                    >
                        <FilterIcon class="w-3.5 h-3.5" /> Filter
                    </button>

                    <div v-if="showFilters" class="pt-2 border-t border-border space-y-2">
                        <button
                            @click="toggleSort"
                            class="w-full px-2 py-1.5 bg-background border border-border rounded-lg text-xs hover:bg-muted/50 flex items-center justify-center gap-1 cursor-pointer transition"
                        >
                            <SortAscIcon v-if="sortOrder === 'asc'" class="w-3 h-3 text-primary" />
                            <SortDescIcon v-else-if="sortOrder === 'desc'" class="w-3 h-3 text-primary" />
                            <ArrowUpDownIcon v-else class="w-3 h-3 text-muted-foreground" />
                            <span>Sort</span>
                        </button>
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="w-full px-3 py-1.5 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-xs font-medium cursor-pointer transition"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Scrollable list -->
                <div
                    ref="scrollEl"
                    @scroll="onScroll"
                    class="flex-1 max-h-[90vh] overflow-y-auto custom-scrollbar padding-bottom p-4"
                >
                    <div v-if="displayedPairs.length === 0" class="text-center py-6 text-muted-foreground text-xs">
                        No pairs found
                    </div>

                    <div
                        v-for="pair in displayedPairs"
                        :key="pair.symbol"
                        @click="emit('select-pair', pair); close()"
                        :class="[
                          'mb-3 p-3 border border-border/50 rounded-lg cursor-pointer transition hover:bg-muted/50',
                          selectedSymbol === pair.symbol ? 'bg-primary/10 border-l-4 border-l-primary' : ''
                        ]">
                        <div class="flex items-start justify-between mb-1">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-card-foreground">{{ pair.symbol }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ pair.name }}</p>
                            </div>
                            <div
                                :class="[
                                  'text-xs font-semibold px-2 py-1 rounded ml-2 flex-shrink-0',
                                  parseFloat(pair.change) >= 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'
                                ]">
                                {{ parseFloat(pair.change) >= 0 ? '+' : '' }}{{ pair.change }}%
                            </div>
                        </div>
                        <p class="text-sm font-bold text-muted-foreground">{{ pair.price }}</p>
                    </div>

                    <div v-if="isLoadingMore" class="flex justify-center py-4">
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
                        <ArrowUpDownIcon class="w-5 h-5 text-muted-foreground" /> Available Pairs
                    </h3>
                    <button @click="close" class="p-2 hover:bg-muted rounded-lg transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Search / filter (mobile) -->
                <div class="bg-muted/30 px-4 py-3 border-b border-border space-y-2">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search pairs..."
                        class="w-full px-3 py-2 bg-background border border-border rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    />
                    <button
                        @click="showFilters = !showFilters"
                        class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-muted/70 hover:bg-muted/80 text-muted-foreground border border-border flex items-center gap-2 cursor-pointer justify-center transition"
                        :class="{ 'bg-primary/10 text-primary border-primary/30': hasActiveFilters }"
                    >
                        <FilterIcon class="w-3.5 h-3.5" /> Filter
                    </button>

                    <div v-if="showFilters" class="pt-2 border-t border-border space-y-2">
                        <button
                            @click="toggleSort"
                            class="w-full px-2 py-1.5 bg-background border border-border rounded-lg text-xs hover:bg-muted/50 flex items-center justify-center gap-1 cursor-pointer transition"
                        >
                            <SortAscIcon v-if="sortOrder === 'asc'" class="w-3 h-3 text-primary" />
                            <SortDescIcon v-else-if="sortOrder === 'desc'" class="w-3 h-3 text-primary" />
                            <ArrowUpDownIcon v-else class="w-3 h-3 text-muted-foreground" />
                            <span>Sort</span>
                        </button>
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="w-full px-3 py-1.5 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-xs font-medium cursor-pointer transition"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Mobile scrollable list -->
                <div
                    ref="scrollEl"
                    @scroll="onScroll"
                    class="flex-1 max-h-[90vh] overflow-y-auto custom-scrollbar padding-bottom p-4"
                >
                    <div v-if="displayedPairs.length === 0" class="text-center py-6 text-muted-foreground text-xs">
                        No pairs found
                    </div>

                    <div
                        v-for="pair in displayedPairs"
                        :key="pair.symbol"
                        @click="emit('select-pair', pair); close()"
                        :class="[
                          'mb-3 p-3 border border-border/50 rounded-lg cursor-pointer transition hover:bg-muted/50',
                          selectedSymbol === pair.symbol ? 'bg-primary/10 border-l-4 border-l-primary' : ''
                        ]">
                        <div class="flex items-start justify-between mb-1">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-card-foreground">{{ pair.symbol }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ pair.name }}</p>
                            </div>
                            <div :class="[
                                  'text-xs font-semibold px-2 py-1 rounded ml-2 flex-shrink-0',
                                  parseFloat(pair.change) >= 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'
                                ]">
                                {{ parseFloat(pair.change) >= 0 ? '+' : '' }}{{ pair.change }}%
                            </div>
                        </div>
                        <p class="text-sm font-bold text-muted-foreground">{{ pair.price }}</p>
                    </div>

                    <div v-if="isLoadingMore" class="flex justify-center py-4">
                        <Loader2Icon class="w-4 h-4 text-primary animate-spin" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
    /* ---- transitions ---- */
    .fade-enter-active,
    .fade-leave-active,
    .slide-right-enter-active,
    .slide-right-leave-active,
    .slide-up-enter-active,
    .slide-up-leave-active { transition: all 0.3s ease; }

    .fade-enter-from,
    .fade-leave-to { opacity: 0; }
    .slide-right-enter-from,
    .slide-right-leave-to { transform: translateX(-100%); }
    .slide-up-enter-from,
    .slide-up-leave-to { transform: translateY(100%); }

    /* ---- scrollbar ---- */
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
