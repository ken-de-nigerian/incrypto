<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import {
        ArrowRightLeftIcon, ArrowDownLeftIcon, ArrowUpRightIcon, HistoryIcon,
        SearchIcon, FilterIcon, XIcon, Loader2Icon, CheckCircleIcon,
        ClockIcon, AlertCircleIcon, ExternalLinkIcon, CopyIcon, CheckIcon,
        ActivityIcon, InfoIcon, ArrowUpDownIcon,
        SortAscIcon, SortDescIcon, CalendarIcon,
        ShieldCheckIcon, ZapIcon, TrophyIcon
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    interface Transaction {
        id: number;
        type?: string;
        from_token?: string;
        to_token?: string;
        token_symbol?: string;
        from_amount?: number;
        to_amount?: number;
        amount?: number;
        wallet_address?: string;
        recipient_address?: string;
        transaction_hash?: string;
        chain?: string;
        fee?: number;
        status: string;
        created_at: string;
    }

    const props = defineProps({
        transactions: {
            type: Object as () => {
                swaps: Transaction[];
                received: Transaction[];
                sent: Transaction[];
            },
            required: true
        },
        statistics: {
            type: Object as () => {
                total: number;
                completed: number;
                pending: number;
                swaps: number;
                received: number;
                sent: number;
            },
            required: true
        }
    });

    const activeTab = ref<'all' | 'swaps' | 'received' | 'sent'>('all');
    const searchQuery = ref('');
    const sortOrder = ref<'asc' | 'desc' | 'default'>('default');
    const filterByStatus = ref<string>('all');
    const showFilters = ref(false);
    const displayCount = ref(12);
    const isLoadingMore = ref(false);
    const scrollContainer = ref<HTMLElement | null>(null);
    const copiedHash = ref<string | null>(null);
    const itemsPerLoad = 12;
    const loadBuffer = 300;

    // Combine all transactions with type labels
    const allTransactions = computed(() => {
        const swaps = props.transactions.swaps.map(tx => ({ ...tx, type: 'swap' as const }));
        const received = props.transactions.received.map(tx => ({ ...tx, type: 'received' as const }));
        const sent = props.transactions.sent.map(tx => ({ ...tx, type: 'sent' as const }));

        return [...swaps, ...received, ...sent].sort((a, b) =>
            new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
        );
    });

    // Filter transactions based on active tab
    const tabFilteredTransactions = computed(() => {
        switch (activeTab.value) {
            case 'swaps':
                return allTransactions.value.filter(tx => tx.type === 'swap');
            case 'received':
                return allTransactions.value.filter(tx => tx.type === 'received');
            case 'sent':
                return allTransactions.value.filter(tx => tx.type === 'sent');
            default:
                return allTransactions.value;
        }
    });

    // Apply search and filters
    const filteredTransactions = computed(() => {
        let filtered = [...tabFilteredTransactions.value];

        if (searchQuery.value.trim()) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(tx =>
                tx.transaction_hash?.toLowerCase().includes(query) ||
                tx.token_symbol?.toLowerCase().includes(query) ||
                tx.from_token?.toLowerCase().includes(query) ||
                tx.to_token?.toLowerCase().includes(query) ||
                tx.wallet_address?.toLowerCase().includes(query) ||
                tx.recipient_address?.toLowerCase().includes(query)
            );
        }

        if (filterByStatus.value !== 'all') {
            filtered = filtered.filter(tx => tx.status === filterByStatus.value);
        }

        if (sortOrder.value === 'asc') {
            filtered.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());
        } else if (sortOrder.value === 'desc') {
            filtered.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
        }

        return filtered;
    });

    const displayedTransactions = computed(() => {
        return filteredTransactions.value.slice(0, displayCount.value);
    });

    const hasMoreTransactions = computed(() => {
        return displayCount.value < filteredTransactions.value.length;
    });

    const totalTransactionsCount = computed(() => filteredTransactions.value.length);

    const hasActiveFilters = computed(() => {
        return searchQuery.value.trim() !== '' || sortOrder.value !== 'default' || filterByStatus.value !== 'all';
    });

    const getTransactionIcon = (type: string) => {
        switch (type) {
            case 'swap':
                return ArrowRightLeftIcon;
            case 'received':
                return ArrowDownLeftIcon;
            case 'sent':
                return ArrowUpRightIcon;
            default:
                return HistoryIcon;
        }
    };

    const getTransactionColor = (type: string) => {
        switch (type) {
            case 'swap':
                return 'text-primary bg-primary/10 border-primary/30';
            case 'received':
                return 'text-green-600 bg-green-500/10 border-green-500/30';
            case 'sent':
                return 'text-blue-600 bg-blue-500/10 border-blue-500/30';
            default:
                return 'text-muted-foreground bg-muted border-border';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status.toLowerCase()) {
            case 'completed':
            case 'success':
                return CheckCircleIcon;
            case 'pending':
            case 'processing':
                return ClockIcon;
            case 'failed':
            case 'error':
                return AlertCircleIcon;
            default:
                return ClockIcon;
        }
    };

    const getStatusColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'completed':
            case 'success':
                return 'text-green-600 bg-green-500/10 border-green-500/30';
            case 'pending':
            case 'processing':
                return 'text-warning bg-warning/10 border-warning/30';
            case 'failed':
            case 'error':
                return 'text-destructive bg-destructive/10 border-destructive/30';
            default:
                return 'text-muted-foreground bg-muted border-border';
        }
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return 'Recently';

        const date = new Date(dateString);
        const now = new Date();
        const diffInDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

        if (diffInDays === 0) return 'Today';
        if (diffInDays === 1) return 'Yesterday';
        if (diffInDays < 7) return `${diffInDays} days ago`;

        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const formatAmount = (amount: number | undefined) => {
        if (!amount) return '0.00';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 8
        }).format(amount);
    };

    const truncateHash = (hash: string | undefined) => {
        if (!hash) return 'N/A';
        return `${hash.slice(0, 6)}...${hash.slice(-4)}`;
    };

    const copyToClipboard = (text: string, id: number) => {
        navigator.clipboard.writeText(text);
        copiedHash.value = `${id}`;
        setTimeout(() => {
            copiedHash.value = null;
        }, 2000);
    };

    const getExplorerUrl = (hash: string, chain: string = 'ethereum') => {
        const explorers: Record<string, string> = {
            ethereum: 'https://etherscan.io/tx/',
            bsc: 'https://bscscan.com/tx/',
            polygon: 'https://polygonscan.com/tx/',
            arbitrum: 'https://arbiscan.io/tx/',
            optimism: 'https://optimistic.etherscan.io/tx/',
        };
        return `${explorers[chain.toLowerCase()] || explorers.ethereum}${hash}`;
    };

    const toggleSortOrder = () => {
        if (sortOrder.value === 'default') {
            sortOrder.value = 'desc';
        } else if (sortOrder.value === 'desc') {
            sortOrder.value = 'asc';
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
        filterByStatus.value = 'all';
    };

    const loadMore = () => {
        if (isLoadingMore.value || !hasMoreTransactions.value) return;

        isLoadingMore.value = true;

        setTimeout(() => {
            displayCount.value = Math.min(
                displayCount.value + itemsPerLoad,
                totalTransactionsCount.value
            );
            isLoadingMore.value = false;
        }, 500);
    };

    const handleScroll = (event: Event) => {
        const target = event.target as HTMLElement;
        const scrollTop = target.scrollTop;
        const scrollHeight = target.scrollHeight;
        const clientHeight = target.clientHeight;

        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);

        if (distanceFromBottom < loadBuffer && hasMoreTransactions.value && !isLoadingMore.value) {
            loadMore();
        }
    };

    const tabs = [
        { id: 'all', label: 'All Transactions', icon: HistoryIcon },
        { id: 'swaps', label: 'Swaps', icon: ArrowRightLeftIcon },
        { id: 'received', label: 'Received', icon: ArrowDownLeftIcon },
        { id: 'sent', label: 'Sent', icon: ArrowUpRightIcon }
    ];

    const securityFeatures = [
        { icon: ShieldCheckIcon, title: 'Secure Transactions', description: 'All transactions are encrypted and secured with blockchain technology' },
        { icon: ActivityIcon, title: 'Real-time Tracking', description: 'Monitor your transactions in real-time with instant status updates' },
        { icon: ZapIcon, title: 'Fast Processing', description: 'Quick transaction processing with optimized network routing' },
        { icon: TrophyIcon, title: 'Low Fees', description: 'Competitive transaction fees with transparent pricing' }
    ];

    watch([searchQuery, sortOrder, filterByStatus, activeTab], () => {
        displayCount.value = Math.min(itemsPerLoad, totalTransactionsCount.value);
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = 0;
        }
    });
</script>

<template>
    <div class="w-full mx-auto">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <!-- Header Section -->
                <div class="bg-gradient-to-br from-primary/10 via-primary/5 to-transparent rounded-2xl border border-primary/20 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                <HistoryIcon class="w-7 h-7 text-primary" />
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl sm:text-3xl font-bold text-card-foreground mb-2">Transaction History</h2>
                                <p class="text-muted-foreground text-sm sm:text-base">
                                    Track all your cryptocurrency transactions including swaps, received payments, and sent transfers. Monitor your transaction status and view detailed information for each operation.
                                </p>
                            </div>
                        </div>

                        <!-- Statistics Grid -->
                        <div class="mt-6 grid grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Total Transactions</p>
                                <p class="text-2xl font-bold text-primary">{{ statistics.total }}</p>
                            </div>
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Completed</p>
                                <p class="text-2xl font-bold text-green-600">{{ statistics.completed }}</p>
                            </div>
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Pending</p>
                                <p class="text-2xl font-bold text-warning">{{ statistics.pending }}</p>
                            </div>
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Swaps</p>
                                <p class="text-xl font-bold text-primary">{{ statistics.swaps }}</p>
                            </div>
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Received</p>
                                <p class="text-xl font-bold text-green-600">{{ statistics.received }}</p>
                            </div>
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Sent</p>
                                <p class="text-xl font-bold text-blue-600">{{ statistics.sent }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions List Card -->
                <div class="bg-card rounded-2xl border border-border overflow-hidden margin-bottom">
                    <!-- Tabs -->
                    <div class="bg-muted/30 px-4 sm:px-6 py-4 border-b border-border">
                        <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id as typeof activeTab"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-lg font-medium text-sm transition-all whitespace-nowrap cursor-pointer"
                                :class="activeTab === tab.id
                                    ? 'bg-primary text-primary-foreground shadow-sm'
                                    : 'text-muted-foreground hover:bg-muted hover:text-card-foreground'"
                            >
                                <component :is="tab.icon" class="w-4 h-4" />
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Filters Bar -->
                    <div class="bg-muted/20 px-4 sm:px-6 py-4 border-b border-border">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <component :is="getTransactionIcon(activeTab)" class="w-5 h-5 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-card-foreground">
                                    {{ tabs.find(t => t.id === activeTab)?.label }}
                                </h3>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    @click="showFilters = !showFilters"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted hover:bg-muted/80 text-muted-foreground border border-border flex items-center gap-2 cursor-pointer"
                                    :class="{ 'bg-primary/10 text-primary border-primary/30': hasActiveFilters }"
                                >
                                    <FilterIcon class="w-3.5 h-3.5" />
                                    Filters
                                    <span v-if="hasActiveFilters" class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                </button>

                                <span class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted text-muted-foreground border border-border">
                                    {{ displayedTransactions.length }} of {{ totalTransactionsCount }}
                                </span>
                            </div>
                        </div>

                        <!-- Filter Controls -->
                        <div v-if="showFilters" class="mt-4 space-y-3 pt-4 border-t border-border">
                            <div class="relative">
                                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by hash, token, or address..."
                                    class="w-full pl-10 pr-10 py-2.5 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="clearSearch"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 hover:bg-muted rounded cursor-pointer"
                                >
                                    <XIcon class="w-4 h-4 text-muted-foreground" />
                                </button>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-muted-foreground mb-1.5">Sort By Date</label>
                                    <button
                                        @click="toggleSortOrder"
                                        class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm hover:bg-muted flex items-center justify-between cursor-pointer"
                                    >
                                        <span>
                                            {{ sortOrder === 'asc' ? 'Oldest First' : sortOrder === 'desc' ? 'Newest First' : 'Recent First' }}
                                        </span>
                                        <SortAscIcon v-if="sortOrder === 'asc'" class="w-4 h-4 text-primary" />
                                        <SortDescIcon v-else-if="sortOrder === 'desc'" class="w-4 h-4 text-primary" />
                                        <ArrowUpDownIcon v-else class="w-4 h-4 text-muted-foreground" />
                                    </button>
                                </div>

                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-muted-foreground mb-1.5">Filter by Status</label>
                                    <select
                                        v-model="filterByStatus"
                                        class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm hover:bg-muted cursor-pointer"
                                    >
                                        <option value="all">All Statuses</option>
                                        <option value="completed">Completed</option>
                                        <option value="success">Success</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                            </div>

                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="w-full px-4 py-2 border border-border bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <XIcon class="w-4 h-4" />
                                Clear All Filters
                            </button>
                        </div>
                    </div>

                    <!-- Transactions List -->
                    <div ref="scrollContainer" @scroll="handleScroll" class="p-4 sm:p-6 max-h-[800px] overflow-y-auto custom-scrollbar">
                        <!-- Empty State - No Transactions -->
                        <div v-if="allTransactions.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-muted mx-auto mb-4 flex items-center justify-center">
                                <HistoryIcon class="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">No Transactions Yet</h4>
                            <p class="text-sm text-muted-foreground mb-6">Your transaction history will appear here once you start trading.</p>
                            <TextLink :href="route('user.dashboard')" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-semibold inline-flex items-center gap-2">
                                <ActivityIcon class="w-4 h-4" />
                                Start Trading
                            </TextLink>
                        </div>

                        <!-- Empty State - No Results -->
                        <div v-else-if="displayedTransactions.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-muted mx-auto mb-4 flex items-center justify-center">
                                <SearchIcon class="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">No Transactions Found</h4>
                            <p class="text-sm text-muted-foreground mb-4">Try adjusting your search or filter criteria.</p>
                            <button @click="clearFilters" class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium cursor-pointer">
                                Clear Filters
                            </button>
                        </div>

                        <!-- Transactions Grid -->
                        <div v-else class="space-y-4">
                            <div
                                v-for="tx in displayedTransactions"
                                :key="tx.id"
                                class="group bg-gradient-to-br from-card to-muted/20 border border-border rounded-xl p-5 transition-all duration-200 hover:border-primary/30"
                            >
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 border" :class="getTransactionColor(tx.type)">
                                            <component :is="getTransactionIcon(tx.type)" class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <h4 class="text-base font-semibold text-card-foreground capitalize">{{ tx.type }}</h4>
                                            <p class="text-xs text-muted-foreground">{{ formatDate(tx.created_at) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <component :is="getStatusIcon(tx.status)" class="w-4 h-4" :class="getStatusColor(tx.status).split(' ')[0]" />
                                        <span class="px-2 py-0.5 text-xs rounded-full border capitalize" :class="getStatusColor(tx.status)">
                                            {{ tx.status }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Swap Details -->
                                <div v-if="tx.type === 'swap'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">To</p>
                                        <p class="text-sm font-semibold text-primary">{{ formatAmount(tx.to_amount) }} {{ tx.to_token }}</p>
                                    </div>
                                    <div v-if="tx.chain" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Network</p>
                                        <p class="text-sm font-semibold text-card-foreground capitalize">{{ tx.chain }}</p>
                                    </div>
                                    <div v-if="tx.transaction_hash" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1">
                                            Transaction Hash
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.transaction_hash) }}</p>
                                            <button
                                                @click="copyToClipboard(tx.transaction_hash!, tx.id)"
                                                class="p-1 hover:bg-muted rounded cursor-pointer"
                                            >
                                                <CheckIcon v-if="copiedHash === `${tx.id}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                            <a
                                                :href="getExplorerUrl(tx.transaction_hash!, tx.chain)"
                                                target="_blank"
                                                class="p-1 hover:bg-muted rounded cursor-pointer"
                                            >
                                                <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Received Details -->
                                <div v-if="tx.type === 'received'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Amount Received</p>
                                        <p class="text-sm font-semibold text-green-600">+{{ formatAmount(tx.amount) }} {{ tx.token_symbol }}</p>
                                    </div>
                                    <div v-if="tx.wallet_address" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Wallet Address</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.wallet_address) }}</p>
                                            <button
                                                @click="copyToClipboard(tx.wallet_address!, tx.id)"
                                                class="p-1 hover:bg-muted rounded cursor-pointer"
                                            >
                                                <CheckIcon v-if="copiedHash === `${tx.id}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="tx.transaction_hash" class="bg-muted/50 rounded-lg p-3 sm:col-span-2">
                                        <p class="text-xs text-muted-foreground mb-1">Transaction Hash</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.transaction_hash) }}</p>
                                            <button
                                                @click="copyToClipboard(tx.transaction_hash!, tx.id + 1000)"
                                                class="p-1 hover:bg-muted rounded cursor-pointer"
                                            >
                                                <CheckIcon v-if="copiedHash === `${tx.id + 1000}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                            <a
                                                :href="getExplorerUrl(tx.transaction_hash!)"
                                                target="_blank"
                                                class="p-1 hover:bg-muted rounded cursor-pointer"
                                            >
                                                <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sent Details -->
                                <div v-if="tx.type === 'sent'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Amount Sent</p>
                                        <p class="text-sm font-semibold text-blue-600">-{{ formatAmount(tx.amount) }} {{ tx.token_symbol }}</p>
                                    </div>
                                    <div v-if="tx.fee" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Transaction Fee</p>
                                        <p class="text-sm font-semibold text-card-foreground">{{ formatAmount(tx.fee) }} {{ tx.token_symbol }}</p>
                                    </div>
                                    <div v-if="tx.recipient_address" class="bg-muted/50 rounded-lg p-3 sm:col-span-2">
                                        <p class="text-xs text-muted-foreground mb-1">Recipient Address</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.recipient_address) }}</p>
                                            <button
                                                @click="copyToClipboard(tx.recipient_address!, tx.id + 2000)"
                                                class="p-1 hover:bg-muted rounded cursor-pointer"
                                            >
                                                <CheckIcon v-if="copiedHash === `${tx.id + 2000}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="tx.transaction_hash" class="bg-muted/50 rounded-lg p-3 sm:col-span-2">
                                        <p class="text-xs text-muted-foreground mb-1">Transaction Hash</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.transaction_hash) }}</p>
                                            <button
                                                @click="copyToClipboard(tx.transaction_hash!, tx.id + 3000)"
                                                class="p-1 hover:bg-muted rounded cursor-pointer"
                                            >
                                                <CheckIcon v-if="copiedHash === `${tx.id + 3000}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                            <a
                                                :href="getExplorerUrl(tx.transaction_hash!)"
                                                target="_blank"
                                                class="p-1 hover:bg-muted rounded cursor-pointer"
                                            >
                                                <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading More Indicator -->
                        <div v-if="isLoadingMore" class="flex items-center justify-center py-8">
                            <Loader2Icon class="w-8 h-8 text-primary animate-spin" />
                        </div>

                        <!-- End of List Indicator -->
                        <div v-else-if="!hasMoreTransactions && displayedTransactions.length > itemsPerLoad" class="text-center py-8">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-muted/50 rounded-full text-sm text-muted-foreground">
                                <CheckCircleIcon class="w-4 h-4" />
                                You've reached the end of the list
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="hidden sm:block">
                <div class="xl:col-span-1 space-y-6 sticky top-6">
                    <!-- Security Features -->
                    <div class="bg-gradient-to-br from-primary/5 to-transparent border border-primary/20 rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                            <ShieldCheckIcon class="w-5 h-5 text-primary" />
                            Security & Features
                        </h5>
                        <ul class="space-y-4">
                            <li v-for="(feature, index) in securityFeatures" :key="index" class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <component :is="feature.icon" class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <h6 class="text-sm font-semibold text-card-foreground mb-1">{{ feature.title }}</h6>
                                    <p class="text-xs text-muted-foreground">{{ feature.description }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Transaction Info -->
                    <div class="bg-gradient-to-br from-card to-muted/20 border border-border rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                            <InfoIcon class="w-5 h-5 text-primary" />
                            Understanding Transactions
                        </h5>
                        <div class="space-y-4 text-xs text-muted-foreground">
                            <div>
                                <h6 class="font-semibold text-card-foreground mb-1 flex items-center gap-2">
                                    <ArrowRightLeftIcon class="w-3.5 h-3.5 text-primary" />
                                    Swaps
                                </h6>
                                <p>Exchange one cryptocurrency for another instantly at the best available rates.</p>
                            </div>
                            <div>
                                <h6 class="font-semibold text-card-foreground mb-1 flex items-center gap-2">
                                    <ArrowDownLeftIcon class="w-3.5 h-3.5 text-green-600" />
                                    Received
                                </h6>
                                <p>Crypto deposits that have been sent to your wallet addresses.</p>
                            </div>
                            <div>
                                <h6 class="font-semibold text-card-foreground mb-1 flex items-center gap-2">
                                    <ArrowUpRightIcon class="w-3.5 h-3.5 text-blue-600" />
                                    Sent
                                </h6>
                                <p>Crypto transfers you've initiated to external wallet addresses.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Status Guide -->
                    <div class="bg-warning/5 border border-warning/20 rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                            <CalendarIcon class="w-5 h-5" />
                            Transaction Status Guide
                        </h5>
                        <ul class="space-y-3 text-xs text-muted-foreground">
                            <li class="flex items-start gap-2">
                                <CheckCircleIcon class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" />
                                <div>
                                    <span class="font-semibold text-card-foreground">Completed:</span>
                                    <span class="block">Transaction successfully confirmed on blockchain</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-2">
                                <ClockIcon class="w-4 h-4 text-warning mt-0.5 flex-shrink-0" />
                                <div>
                                    <span class="font-semibold text-card-foreground">Pending:</span>
                                    <span class="block">Awaiting blockchain confirmation (usually 5-30 minutes)</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-2">
                                <AlertCircleIcon class="w-4 h-4 text-destructive mt-0.5 flex-shrink-0" />
                                <div>
                                    <span class="font-semibold text-card-foreground">Failed:</span>
                                    <span class="block">Transaction unsuccessful, funds returned to source</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Help Section -->
                    <div class="text-center p-6 bg-gradient-to-br from-card to-muted/20 border border-border rounded-2xl">
                        <div class="w-12 h-12 rounded-full bg-primary/10 mx-auto mb-3 flex items-center justify-center">
                            <InfoIcon class="w-6 h-6 text-primary" />
                        </div>
                        <h5 class="text-sm font-semibold text-card-foreground mb-2">Need Transaction Help?</h5>
                        <p class="text-xs text-muted-foreground mb-4">
                            Having issues with a transaction or need clarification? Our support team is ready to assist you.
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

<style scoped>
    .custom-scrollbar {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    .hide-scrollbar {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
