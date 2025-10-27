<script setup lang="ts">
    import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
    import { WalletIcon, CheckCircleIcon, PlusCircleIcon, ArrowRightIcon, InfoIcon, ShieldCheckIcon, TrendingUpIcon, LockIcon, ZapIcon, ExternalLinkIcon, CopyIcon, CheckIcon, Loader2Icon, SearchIcon, ArrowUpDownIcon, SortAscIcon, SortDescIcon, FilterIcon, XIcon } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    const props = defineProps({
        userWallets: {
            type: Array as () => Array<{
                id: number;
                wallet_id: string;
                wallet_name: string;
                wallet_logo?: string;
                wallet_security_type: string;
                connected_at: string;
            }>,
            default: () => []
        },
        availableWallets: {
            type: Array as () => Array<{
                id: string;
                name: string;
                logo?: string;
                description: string;
                type: string;
                is_popular?: boolean;
            }>,
            default: () => []
        }
    });

    const copiedAddress = ref<string | null>(null);

    // Search and filter state
    const searchQuery = ref('');
    const sortOrder = ref<'asc' | 'desc' | 'default'>('default');
    const filterByType = ref<string>('all');
    const showFilters = ref(false);

    // Infinite scroll state
    const displayCount = ref(8);
    const isLoadingMore = ref(false);
    const scrollContainer = ref<HTMLElement | null>(null);
    const itemsPerLoad = 8;
    const loadBuffer = 300;

    const connectedWallets = computed(() => props.userWallets);
    const walletTypes = computed(() => {
        const types = new Set(props.availableWallets.map(w => w.type));
        return ['all', ...Array.from(types)];
    });

    const availableToConnect = computed(() => {
        const connectedIds = connectedWallets.value.map(w => w.wallet_id);
        let filtered = props.availableWallets.filter(w => !connectedIds.includes(w.id));

        // Apply search filter
        if (searchQuery.value.trim()) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(w =>
                w.name.toLowerCase().includes(query) ||
                w.description.toLowerCase().includes(query) ||
                w.type.toLowerCase().includes(query)
            );
        }

        // Apply type filter
        if (filterByType.value !== 'all') {
            filtered = filtered.filter(w => w.type === filterByType.value);
        }

        // Apply sorting
        if (sortOrder.value === 'asc') {
            filtered = [...filtered].sort((a, b) => a.name.localeCompare(b.name));
        } else if (sortOrder.value === 'desc') {
            filtered = [...filtered].sort((a, b) => b.name.localeCompare(a.name));
        } else {
            filtered = [...filtered].sort((a, b) => a.name.localeCompare(b.name));
        }

        return filtered;
    });

    // Paginated wallets for infinite scroll
    const displayedWallets = computed(() => {
        return availableToConnect.value.slice(0, displayCount.value);
    });

    const hasMoreWallets = computed(() => {
        return displayCount.value < availableToConnect.value.length;
    });

    const totalWalletsCount = computed(() => availableToConnect.value.length);

    const walletBenefits = [
        { icon: ShieldCheckIcon, text: 'Secure and encrypted wallet connections' },
        { icon: TrendingUpIcon, text: 'Real-time activity tracking and updates' },
        { icon: ZapIcon, text: 'Instant deposits and withdrawals' },
        { icon: LockIcon, text: 'Non-custodial - you control your assets' }
    ];

    const copyAddress = (id: string) => {
        navigator.clipboard.writeText(id);
        copiedAddress.value = id;
        setTimeout(() => {
            copiedAddress.value = null;
        }, 2000);
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const toggleSortOrder = () => {
        if (sortOrder.value === 'default') {
            sortOrder.value = 'asc';
        } else if (sortOrder.value === 'asc') {
            sortOrder.value = 'desc';
        } else {
            sortOrder.value = 'default';
        }
    };

    const clearSearch = () => {
        searchQuery.value = '';
    };

    const clearFilters = () => {
        searchQuery.value = '';
        sortOrder.value = 'default';
        filterByType.value = 'all';
    };

    const hasActiveFilters = computed(() => {
        return searchQuery.value.trim() !== '' || sortOrder.value !== 'default' || filterByType.value !== 'all';
    });

    // Load more wallets
    const loadMore = () => {
        if (isLoadingMore.value || !hasMoreWallets.value) return;

        isLoadingMore.value = true;

        // Simulate network delay for smooth UX
        setTimeout(() => {
            displayCount.value = Math.min(
                displayCount.value + itemsPerLoad,
                totalWalletsCount.value
            );
            isLoadingMore.value = false;
        }, 500);
    };

    // Scroll handler for infinite scroll
    const handleScroll = (event: Event) => {
        const target = event.target as HTMLElement;
        const scrollTop = target.scrollTop;
        const scrollHeight = target.scrollHeight;
        const clientHeight = target.clientHeight;

        // Calculate distance from bottom
        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);

        // Load more when within buffer distance from the bottom
        if (distanceFromBottom < loadBuffer && hasMoreWallets.value && !isLoadingMore.value) {
            loadMore();
        }
    };

    // Watch for search/filter changes to reset pagination
    watch([searchQuery, sortOrder, filterByType], () => {
        displayCount.value = Math.min(itemsPerLoad, totalWalletsCount.value);
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = 0;
        }
    });

    // Watch for wallet list changes to reset if needed
    watch(() => props.availableWallets.length, (newLength) => {
        if (newLength < displayCount.value) {
            displayCount.value = Math.min(itemsPerLoad, newLength);
        }
    });

    onMounted(() => {
        // Set initial display count
        displayCount.value = Math.min(itemsPerLoad, totalWalletsCount.value);
    });

    onUnmounted(() => {
        // Cleanup logic remains the same
        if (copiedAddress.value !== null) {
            copiedAddress.value = null;
        }

        scrollContainer.value = null;

        searchQuery.value = '';
        sortOrder.value = 'default';
        filterByType.value = 'all';

        displayCount.value = itemsPerLoad;
        isLoadingMore.value = false;
    });
</script>

<template>
    <div class="w-full mx-auto">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-gradient-to-br from-primary/10 via-primary/10 to-transparent rounded-2xl border border-primary/20 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                <WalletIcon class="w-7 h-7 text-primary" />
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl sm:text-3xl font-bold text-card-foreground mb-2">Wallet Connect</h2>
                                <p class="text-muted-foreground text-sm sm:text-base">
                                    Connect your crypto wallets to seamlessly manage deposits, withdrawals, and track your portfolio across multiple chains.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Connected</p>
                                <p class="text-2xl font-bold text-primary">{{ connectedWallets.length }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Available</p>
                                <p class="text-2xl font-bold text-card-foreground">{{ totalWalletsCount }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Status</p>
                                <p class="text-sm font-semibold text-primary flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                                    Active
                                </p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Security</p>
                                <p class="text-sm font-semibold text-primary flex items-center gap-1">
                                    <ShieldCheckIcon class="w-4 h-4" />
                                    Verified
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border border-border overflow-hidden">
                    <div class="bg-muted/30 px-6 py-4 border-b border-border flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <CheckCircleIcon class="w-5 h-5 text-primary" />
                            <h3 class="text-lg font-semibold text-card-foreground">Connected Wallets</h3>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary border border-primary/30">
                            {{ connectedWallets.length }} Active
                        </span>
                    </div>

                    <div class="p-6">
                        <div v-if="connectedWallets.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-muted/70 mx-auto mb-4 flex items-center justify-center">
                                <WalletIcon class="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">No Wallets Connected</h4>
                            <p class="text-sm text-muted-foreground mb-6">Connect your first wallet to start trading and managing your assets.</p>
                        </div>

                        <div v-else class="grid gap-4">
                            <div v-for="wallet in connectedWallets"
                                 :key="wallet.id"
                                 class="group bg-muted/30 hover:bg-muted/50 border border-border rounded-xl p-5 transition-all duration-200 ">
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-start justify-between gap-4">
                                    <div class="flex items-start gap-4 flex-1">
                                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                            <img v-if="wallet.wallet_logo" :src="`https://www.cryptocompare.com${wallet.wallet_logo}`" loading="lazy" :alt="wallet.wallet_name" class="w-8 h-8 rounded-lg" />
                                            <span v-else class="text-sm font-bold text-primary">{{ getInitials(wallet.wallet_name) }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <h4 class="text-base font-semibold text-card-foreground">{{ wallet.wallet_name }}</h4>
                                                <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full border border-primary/30">
                                                    Connected
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2 mb-2">
                                                <code class="text-xs text-muted-foreground bg-muted/50 px-2 py-1 rounded font-mono truncate">
                                                    {{ wallet.wallet_id }}
                                                </code>

                                                <button
                                                    @click="copyAddress(wallet.wallet_id)"
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer flex-shrink-0"
                                                    :title="copiedAddress === wallet.wallet_id ? 'Copied!' : 'Copy ID'">
                                                    <CheckIcon v-if="copiedAddress === wallet.wallet_id" class="w-3.5 h-3.5 text-primary" />
                                                    <CopyIcon v-else class="w-3.5 h-3.5 text-muted-foreground" />
                                                </button>
                                            </div>

                                            <div class="flex items-center gap-4 text-xs text-muted-foreground flex-wrap">
                                                <span class="flex items-center gap-1">
                                                    <ShieldCheckIcon class="w-3.5 h-3.5" />
                                                    Type: {{ wallet.wallet_security_type }}
                                                </span>
                                                <span>Connected {{ wallet.connected_at }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border border-border overflow-hidden margin-bottom">
                    <div class="bg-muted/30 px-6 py-4 border-b border-border">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <PlusCircleIcon class="w-5 h-5 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-card-foreground">Available Wallets</h3>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    @click="showFilters = !showFilters"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted/70 hover:bg-muted/80 text-muted-foreground border border-border flex items-center gap-2"
                                    :class="{ 'bg-primary/10 text-primary border-primary/30': hasActiveFilters }">
                                    <FilterIcon class="w-3.5 h-3.5" />
                                    Filters
                                    <span v-if="hasActiveFilters" class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                </button>

                                <span class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted/70 text-muted-foreground border border-border">
                                    {{ displayedWallets.length }} of {{ totalWalletsCount }}
                                </span>
                            </div>
                        </div>

                        <div v-if="showFilters" class="mt-4 space-y-3 pt-4 border-t border-border">
                            <div class="relative">
                                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search wallets..."
                                    class="w-full pl-10 pr-10 py-2.5 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                                <button v-if="searchQuery" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 hover:bg-muted/50 rounded">
                                    <XIcon class="w-4 h-4 text-muted-foreground" />
                                </button>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-muted-foreground mb-1.5">Wallet Type</label>
                                    <select v-model="filterByType" class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all hover:bg-muted/50 cursor-pointer">
                                        <option v-for="type in walletTypes" :key="type" :value="type">
                                            {{ type === 'all' ? 'All Types' : type }}
                                        </option>
                                    </select>
                                </div>

                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-muted-foreground mb-1.5">Sort By Name</label>
                                    <button @click="toggleSortOrder" class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm hover:bg-muted/50 flex items-center justify-between">
                                        <span>
                                            {{ sortOrder === 'asc' ? 'A → Z' : sortOrder === 'desc' ? 'Z → A' : 'A → Z (Default)' }}
                                        </span>
                                        <SortAscIcon v-if="sortOrder === 'asc'" class="w-4 h-4 text-primary" />
                                        <SortDescIcon v-else-if="sortOrder === 'desc'" class="w-4 h-4 text-primary" />
                                        <ArrowUpDownIcon v-else class="w-4 h-4 text-muted-foreground" />
                                    </button>
                                </div>
                            </div>

                            <button v-if="hasActiveFilters" @click="clearFilters" class="w-full px-4 py-2 border border-border bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium flex items-center justify-center gap-2 cursor-pointer">
                                <XIcon class="w-4 h-4" />
                                Clear All Filters
                            </button>
                        </div>
                    </div>

                    <div ref="scrollContainer" @scroll="handleScroll" class="p-6 max-h-[800px] overflow-y-auto custom-scrollbar">
                        <div v-if="availableToConnect.length === 0 && !hasActiveFilters" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-primary/10 mx-auto mb-4 flex items-center justify-center">
                                <CheckCircleIcon class="w-8 h-8 text-primary" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">All Wallets Connected!</h4>
                            <p class="text-sm text-muted-foreground">You've connected all available wallet types. Great job!</p>
                        </div>

                        <div v-else-if="displayedWallets.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-muted/70 mx-auto mb-4 flex items-center justify-center">
                                <SearchIcon class="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">No Wallets Found</h4>
                            <p class="text-sm text-muted-foreground mb-4">Try adjusting your search or filter criteria.</p>
                            <button @click="clearFilters" class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium">
                                Clear Filters
                            </button>
                        </div>

                        <div v-else class="grid sm:grid-cols-2 gap-4">
                            <div v-for="wallet in displayedWallets" :key="wallet.id" class="group bg-gradient-to-br from-card to-muted/20 border border-border rounded-xl p-5 transition-all duration-200 hover:border-primary/30 relative overflow-hidden">
                                <span v-if="wallet.is_popular" class="absolute top-3 right-3 px-2 py-1 bg-primary text-primary-foreground text-xs rounded-full font-medium">
                                    Popular
                                </span>
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border group-hover:ring-primary/40 transition-all">
                                        <img v-if="wallet.logo" :src="`https://www.cryptocompare.com${wallet.logo}`" :alt="wallet.name" loading="lazy" class="w-8 h-8 rounded-lg" />
                                        <span v-else class="text-sm font-bold text-primary">{{ getInitials(wallet.name) }}</span>
                                    </div>

                                    <div class="flex-1">
                                        <h4 class="text-base font-semibold text-card-foreground mb-1">{{ wallet.name }}</h4>
                                        <span class="text-xs text-muted-foreground px-2 py-0.5 bg-muted/50 rounded-full">
                                            {{ wallet.type }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-sm text-muted-foreground mb-4 line-clamp-2">{{ wallet.description }}</p>
                                <TextLink :href="route('user.wallet.create', { id: wallet.id })" class="w-full px-4 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-semibold transition-all flex items-center justify-center gap-2 cursor-pointer">
                                    <PlusCircleIcon class="w-4 h-4" />
                                    Connect Wallet
                                    <ArrowRightIcon class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </TextLink>
                            </div>
                        </div>

                        <div v-if="isLoadingMore" class="flex items-center justify-center py-8">
                            <Loader2Icon class="w-8 h-8 text-primary animate-spin" />
                        </div>
                        <div v-else-if="!hasMoreWallets && displayedWallets.length > itemsPerLoad" class="text-center py-8">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-muted/50 rounded-full text-sm text-muted-foreground">
                                <CheckCircleIcon class="w-4 h-4" />
                                You've reached the end of the list
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block">
                <div class="xl:col-span-1 space-y-6">
                    <div class="bg-gradient-to-br from-primary/10 to-primary/10 border border-primary/20 rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                            <ShieldCheckIcon class="w-5 h-5 text-primary" />
                            Why Connect Your Wallet?
                        </h5>
                        <ul class="space-y-3">
                            <li v-for="(benefit, index) in walletBenefits" :key="index" class="flex items-start gap-3 text-sm text-muted-foreground">
                                <component :is="benefit.icon" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                <span>{{ benefit.text }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-card border border-border rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                            <InfoIcon class="w-5 h-5 text-primary" />
                            Security Best Practices
                        </h5>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <span class="text-primary mt-0.5 font-bold">1.</span>
                                <span>Never share your private keys or seed phrase.</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <span class="text-primary mt-0.5 font-bold">2.</span>
                                <span>Always verify wallet IDs before transactions.</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted-foreground">
                                <span class="text-primary mt-0.5 font-bold">3.</span>
                                <span>Enable two-factor authentication for added security.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-warning/10 border border-warning/20 rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                            <ZapIcon class="w-5 h-5" />
                            Important Notice
                        </h5>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            We never store your private keys. All wallet connections are secure and encrypted. You maintain full control of your assets at all times.
                        </p>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-card to-muted/20 border border-border rounded-2xl">
                        <div class="w-12 h-12 rounded-full bg-primary/10 mx-auto mb-3 flex items-center justify-center">
                            <InfoIcon class="w-6 h-6 text-primary" />
                        </div>

                        <h5 class="text-sm font-semibold text-card-foreground mb-2">Need Help?</h5>
                        <p class="text-xs text-muted-foreground mb-4">
                            Our support team is here to assist you with wallet connections and troubleshooting.
                        </p>

                        <TextLink :href="route('user.support.index')" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                            Contact Support
                            <ExternalLinkIcon class="w-4 h-4" />
                        </TextLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    .custom-scrollbar {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
