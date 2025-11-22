<script setup lang="ts">
    import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
    import {
        ArrowRightLeftIcon, ArrowDownLeftIcon, ArrowUpRightIcon, HistoryIcon,
        SearchIcon, FilterIcon, XIcon, Loader2Icon, CheckCircleIcon,
        ClockIcon, AlertCircleIcon, ExternalLinkIcon, CopyIcon, CheckIcon,
        ActivityIcon, InfoIcon, ArrowUpDownIcon,
        SortAscIcon, SortDescIcon,
        ShieldCheckIcon, ZapIcon, TrophyIcon, TrendingUpIcon, TrendingDownIcon,
        DollarSignIcon, BarChart3Icon, CoinsIcon, WalletIcon
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    interface InvestmentProgress {
        countdown: string;
        percentage: number;
        isExpired: boolean;
        currentCycle: number;
        totalCycles: number;
    }

    interface Transaction {
        id: number;
        type?: string;
        trade_direction?: string;
        // Crypto swaps
        from_token?: string;
        to_token?: string;
        from_amount?: number;
        to_amount?: number;
        chain?: string;
        // Received/Sent crypto
        token_symbol?: string;
        amount?: number;
        wallet_address?: string;
        recipient_address?: string;
        transaction_hash?: string;
        fee?: number;
        // Trades (forex, stocks, crypto)
        pair?: string;
        pair_name?: string;
        entry_price?: number;
        exit_price?: number;
        leverage?: number;
        duration?: string;
        pnl?: number;
        trading_mode?: string;
        opened_at?: string;
        closed_at?: string;
        expiry_time?: string;
        // Investments
        plan_id?: number;
        plan_name?: string;
        interest?: number;
        period?: number;
        repeat_time?: number;
        repeat_time_count?: number;
        next_time?: string;
        last_time?: string;
        capital_back_status?: string;
        // Common
        status: string;
        created_at: string;
        compositeId?: string;
        // Computed investment properties
        progress?: InvestmentProgress;
        totalInterestEarned?: number;
    }

    const props = defineProps({
        transactions: {
            type: Object as () => {
                swaps: Transaction[];
                received: Transaction[];
                sent: Transaction[];
                forex_trades: Transaction[];
                stock_trades: Transaction[];
                crypto_trades: Transaction[];
                investments: Transaction[];
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
                forex_trades: number;
                stock_trades: number;
                crypto_trades: number;
                investments: number;
            },
            required: true
        },
        currentTab: {
            type: String as () => 'all' | 'swaps' | 'received' | 'sent' | 'forex' | 'stocks' | 'crypto_trades' | 'investments',
            default: 'all'
        }
    });

    const activeTab = ref<'all' | 'swaps' | 'received' | 'sent' | 'forex' | 'stocks' | 'crypto_trades' | 'investments'>(props.currentTab);
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
    const currentTime = ref(new Date());
    let countdownInterval: ReturnType<typeof setInterval> | null = null;

    onMounted(() => {
        countdownInterval = setInterval(() => {
            currentTime.value = new Date();
        }, 1000);
    });

    onUnmounted(() => {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });

    const allTransactions = computed(() => {
        const swaps = (props.transactions.swaps || []).map(tx => ({
            ...tx,
            type: 'swap' as const,
            compositeId: `swap-${tx.id}`
        }));
        const received = (props.transactions.received || []).map(tx => ({
            ...tx,
            type: 'received' as const,
            compositeId: `received-${tx.id}`
        }));
        const sent = (props.transactions.sent || []).map(tx => ({
            ...tx,
            type: 'sent' as const,
            compositeId: `sent-${tx.id}`
        }));
        const forexTrades = (props.transactions.forex_trades || []).map(tx => ({
            ...tx,
            trade_direction: (tx as any).type,
            type: 'forex_trade' as const,
            compositeId: `forex-${tx.id}`
        }));
        const stockTrades = (props.transactions.stock_trades || []).map(tx => ({
            ...tx,
            trade_direction: (tx as any).type,
            type: 'stock_trade' as const,
            compositeId: `stock-${tx.id}`
        }));
        const cryptoTrades = (props.transactions.crypto_trades || []).map(tx => ({
            ...tx,
            trade_direction: (tx as any).type,
            type: 'crypto_trade' as const,
            compositeId: `crypto-trade-${tx.id}`
        }));
        const investments = (props.transactions.investments || []).map(tx => ({
            ...tx,
            type: 'investment' as const,
            compositeId: `investment-${tx.id}`,
            progress: calculateInvestmentProgress(tx),
            totalInterestEarned: calculateTotalInterestEarned(tx)
        }));

        return [...swaps, ...received, ...sent, ...forexTrades, ...stockTrades, ...cryptoTrades, ...investments].sort((a, b) =>
            new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
        );
    });

    const tabFilteredTransactions = computed(() => {
        switch (activeTab.value) {
            case 'swaps':
                return allTransactions.value.filter(tx => tx.type === 'swap');
            case 'received':
                return allTransactions.value.filter(tx => tx.type === 'received');
            case 'sent':
                return allTransactions.value.filter(tx => tx.type === 'sent');
            case 'forex':
                return allTransactions.value.filter(tx => tx.type === 'forex_trade');
            case 'stocks':
                return allTransactions.value.filter(tx => tx.type === 'stock_trade');
            case 'crypto_trades':
                return allTransactions.value.filter(tx => tx.type === 'crypto_trade');
            case 'investments':
                return allTransactions.value.filter(tx => tx.type === 'investment');
            default:
                return allTransactions.value;
        }
    });

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
                tx.recipient_address?.toLowerCase().includes(query) ||
                tx.pair?.toLowerCase().includes(query) ||
                tx.pair_name?.toLowerCase().includes(query) ||
                tx.plan_name?.toLowerCase().includes(query)
            );
        }

        if (filterByStatus.value !== 'all') {
            filtered = filtered.filter(tx => tx.status.toLowerCase() === filterByStatus.value.toLowerCase());
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
            case 'forex_trade':
                return DollarSignIcon;
            case 'stock_trade':
                return BarChart3Icon;
            case 'crypto_trade':
                return CoinsIcon;
            case 'investment':
                return WalletIcon;
            default:
                return HistoryIcon;
        }
    };

    const getTransactionColor = (type: string) => {
        const colors: Record<string, string> = {
            'swap': 'text-primary bg-primary/10 border-primary/30',
            'received': 'text-success bg-success/10 border-success/30',
            'sent': 'text-accent bg-accent/10 border-accent/30',
            'forex_trade': 'text-blue-500 bg-blue-500/10 border-blue-500/30',
            'stock_trade': 'text-purple-500 bg-purple-500/10 border-purple-500/30',
            'crypto_trade': 'text-orange-500 bg-orange-500/10 border-orange-500/30',
            'investment': 'text-green-500 bg-green-500/10 border-green-500/30',
        };
        return colors[type] || 'text-muted-foreground bg-muted/20 border-border/50';
    };

    const getTransactionLabel = (type: string) => {
        const labels: Record<string, string> = {
            'swap': 'Swap',
            'received': 'Received',
            'sent': 'Sent',
            'forex_trade': 'Forex Trade',
            'stock_trade': 'Stock Trade',
            'crypto_trade': 'Crypto Trade',
            'investment': 'Investment',
        };
        return labels[type] || type;
    };

    const getStatusIcon = (status: string) => {
        const statusLower = status.toLowerCase();
        switch (statusLower) {
            case 'completed':
            case 'success':
            case 'closed':
                return CheckCircleIcon;
            case 'pending':
            case 'processing':
            case 'open':
            case 'running':
                return ClockIcon;
            case 'failed':
            case 'error':
            case 'cancelled':
                return AlertCircleIcon;
            default:
                return ClockIcon;
        }
    };

    const getStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            'completed': 'text-success bg-success/10 border-success/30',
            'success': 'text-success bg-success/10 border-success/30',
            'closed': 'text-success bg-success/10 border-success/30',
            'pending': 'text-warning bg-warning/10 border-warning/30',
            'processing': 'text-warning bg-warning/10 border-warning/30',
            'open': 'text-warning bg-warning/10 border-warning/30',
            'running': 'text-warning bg-warning/10 border-warning/30',
            'failed': 'text-destructive bg-destructive/10 border-destructive/30',
            'error': 'text-destructive bg-destructive/10 border-destructive/30',
            'cancelled': 'text-destructive bg-destructive/10 border-destructive/30',
        };
        return colors[status.toLowerCase()] || 'text-muted-foreground bg-muted/20 border-border/50';
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

    const calculateInvestmentProgress = (history: any): InvestmentProgress => {
        const now = currentTime.value.getTime();
        const nextPayoutTime = new Date(history.next_time).getTime();

        const cycleStartTime = history.last_time
            ? new Date(history.last_time).getTime()
            : new Date(history.created_at).getTime();

        const currentCycle = history.repeat_time_count || 0;
        const totalCycles = history.repeat_time || 1;

        const cycleDuration = history.period * 60 * 60 * 1000;

        const elapsed = now - cycleStartTime;
        const remaining = nextPayoutTime - now;

        if (currentCycle >= totalCycles || history.status !== 'running') {
            return {
                countdown: 'Completed',
                percentage: 100,
                isExpired: true,
                currentCycle,
                totalCycles
            };
        }

        if (remaining <= 0) {
            return {
                countdown: 'Cycle Matured',
                percentage: 100,
                isExpired: true,
                currentCycle,
                totalCycles
            };
        }

        const percentage = Math.min(100, Math.max(0, (elapsed / cycleDuration) * 100));

        const days = Math.floor(remaining / (1000 * 60 * 60 * 24));
        const hours = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

        let countdown = '';
        if (days > 0) {
            countdown = `${days}d ${hours}h ${minutes}m`;
        } else if (hours > 0) {
            countdown = `${hours}h ${minutes}m ${seconds}s`;
        } else if (minutes > 0) {
            countdown = `${minutes}m ${seconds}s`;
        } else {
            countdown = `${seconds}s`;
        }

        return {
            countdown,
            percentage,
            isExpired: false,
            currentCycle,
            totalCycles
        };
    };

    const calculateTotalInterestEarned = (tx: Transaction): number => {
        const currentCycle = tx.repeat_time_count || 0;
        const interestPerCycle = tx.interest || 0;
        return currentCycle * interestPerCycle;
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
        { id: 'all', label: 'All Transactions', icon: HistoryIcon, params: {} },
        { id: 'swaps', label: 'Swaps', icon: ArrowRightLeftIcon, params: { tab: 'swaps' } },
        { id: 'received', label: 'Received', icon: ArrowDownLeftIcon, params: { tab: 'received' } },
        { id: 'sent', label: 'Sent', icon: ArrowUpRightIcon, params: { tab: 'sent' } },
        { id: 'forex', label: 'Forex', icon: DollarSignIcon, params: { tab: 'forex' } },
        { id: 'stocks', label: 'Stocks', icon: BarChart3Icon, params: { tab: 'stocks' } },
        { id: 'crypto_trades', label: 'Crypto Trades', icon: CoinsIcon, params: { tab: 'crypto_trades' } },
        { id: 'investments', label: 'Investments', icon: WalletIcon, params: { tab: 'investments' } }
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
                <div class="rounded-2xl border border-primary/20 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                <HistoryIcon class="w-7 h-7 text-primary" />
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl sm:text-3xl font-bold text-card-foreground mb-2">Transaction History</h2>
                                <p class="text-muted-foreground text-sm sm:text-base">
                                    Track all your transactions including crypto swaps, transfers, trades (forex, stocks, crypto), and investments. Monitor status and view detailed information.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Total</p>
                                <p class="text-2xl font-bold text-primary">{{ statistics.total }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Completed</p>
                                <p class="text-2xl font-bold text-success">{{ statistics.completed }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Pending</p>
                                <p class="text-2xl font-bold text-warning">{{ statistics.pending }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Swaps</p>
                                <p class="text-xl font-bold text-primary">{{ statistics.swaps }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Forex</p>
                                <p class="text-xl font-bold text-blue-500">{{ statistics.forex_trades }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Stocks</p>
                                <p class="text-xl font-bold text-purple-500">{{ statistics.stock_trades }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Crypto Trades</p>
                                <p class="text-xl font-bold text-orange-500">{{ statistics.crypto_trades }}</p>
                            </div>
                            <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Investments</p>
                                <p class="text-xl font-bold text-green-500">{{ statistics.investments }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border border-border overflow-hidden margin-bottom">
                    <div class="bg-muted/30 px-4 sm:px-6 py-4 border-b border-border">
                        <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar">
                            <TextLink
                                v-for="tab in tabs"
                                :key="tab.id"
                                :href="route('user.transactions.index', tab.params)"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 whitespace-nowrap cursor-pointer"
                                :class="activeTab === tab.id
                                    ? 'bg-primary text-primary-foreground scale-105'
                                    : 'text-muted-foreground hover:bg-muted/70 hover:text-card-foreground'"
                                preserve-scroll
                                preserve-state
                                @click="activeTab = tab.id as typeof activeTab"
                            >
                                <component :is="tab.icon" class="w-4 h-4" />
                                {{ tab.label }}
                            </TextLink>
                        </div>
                    </div>

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
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted/70 hover:bg-muted/80 text-muted-foreground border border-border flex items-center gap-2 cursor-pointer"
                                    :class="{ 'bg-primary/10 text-primary border-primary/30': hasActiveFilters }"
                                >
                                    <FilterIcon class="w-3.5 h-3.5" />
                                    Filters
                                    <span v-if="hasActiveFilters" class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                </button>

                                <span class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted/70 text-muted-foreground border border-border">
                                    {{ displayedTransactions.length }} of {{ totalTransactionsCount }}
                                </span>
                            </div>
                        </div>

                        <div v-if="showFilters" class="mt-4 space-y-3 pt-4 border-t border-border">
                            <div class="relative">
                                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by hash, token, pair, or plan..."
                                    class="w-full pl-10 pr-10 py-2.5 bg-background border border-border rounded-lg text-sm input-crypto"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="clearSearch"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 hover:bg-muted/50 rounded cursor-pointer"
                                >
                                    <XIcon class="w-4 h-4 text-muted-foreground" />
                                </button>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-muted-foreground mb-1.5">Sort By Date</label>
                                    <button
                                        @click="toggleSortOrder"
                                        class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm hover:bg-muted/50 flex items-center justify-between cursor-pointer"
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
                                        class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm hover:bg-muted/50 cursor-pointer"
                                    >
                                        <option value="all">All Statuses</option>
                                        <option value="completed">Completed</option>
                                        <option value="success">Success</option>
                                        <option value="closed">Closed</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="open">Open</option>
                                        <option value="running">Running</option>
                                        <option value="failed">Failed</option>
                                        <option value="cancelled">Cancelled</option>
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

                    <div ref="scrollContainer" @scroll="handleScroll" class="p-4 sm:p-6 max-h-[800px] overflow-y-auto custom-scrollbar">
                        <div v-if="allTransactions.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-muted/70 mx-auto mb-4 flex items-center justify-center">
                                <HistoryIcon class="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">No Transactions Yet</h4>
                            <p class="text-sm text-muted-foreground mb-6">Your transaction history will appear here once you start trading.</p>
                            <TextLink :href="route('user.dashboard')" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-semibold inline-flex items-center gap-2">
                                <ActivityIcon class="w-4 h-4" />
                                Start Trading
                            </TextLink>
                        </div>

                        <div v-else-if="displayedTransactions.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-muted/70 mx-auto mb-4 flex items-center justify-center">
                                <SearchIcon class="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">No Transactions Found</h4>
                            <p class="text-sm text-muted-foreground mb-4">Try adjusting your search or filter criteria.</p>
                            <button @click="clearFilters" class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium cursor-pointer">
                                Clear Filters
                            </button>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="tx in displayedTransactions"
                                :key="tx.compositeId"
                                class="group bg-gradient-to-br from-card to-muted/20 border border-border rounded-xl p-5 transition-all duration-200 hover:border-primary/30"
                            >
                                <div class="flex flex-col lg:flex-row items-start justify-between gap-4 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 border" :class="getTransactionColor(tx.type)">
                                            <component :is="getTransactionIcon(tx.type)" class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <h4 class="text-base font-semibold text-card-foreground capitalize">{{ getTransactionLabel(tx.type) }}</h4>
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

                                <!-- Swap Transaction -->
                                <div v-if="tx.type === 'swap'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">From</p>
                                        <p class="text-sm font-semibold text-card-foreground">{{ formatAmount(tx.from_amount) }} {{ tx.from_token }}</p>
                                    </div>
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">To</p>
                                        <p class="text-sm font-semibold text-card-foreground">{{ formatAmount(tx.to_amount) }} {{ tx.to_token }}</p>
                                    </div>
                                    <div v-if="tx.chain" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Network</p>
                                        <p class="text-sm font-semibold text-card-foreground capitalize">{{ tx.chain }}</p>
                                    </div>
                                    <div v-if="tx.transaction_hash" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1 flex items-center gap-1">Transaction Hash</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.transaction_hash) }}</p>
                                            <button @click="copyToClipboard(tx.transaction_hash!, tx.id)" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                <CheckIcon v-if="copiedHash === `${tx.id}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                            <a :href="getExplorerUrl(tx.transaction_hash!, tx.chain)" target="_blank" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Received Transaction -->
                                <div v-if="tx.type === 'received'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Amount Received</p>
                                        <p class="text-sm font-semibold text-success">+{{ formatAmount(tx.amount) }} {{ tx.token_symbol }}</p>
                                    </div>
                                    <div v-if="tx.wallet_address" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Wallet Address</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.wallet_address) }}</p>
                                            <button @click="copyToClipboard(tx.wallet_address!, tx.id + 1000)" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                <CheckIcon v-if="copiedHash === `${tx.id + 1000}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="tx.transaction_hash" class="bg-muted/50 rounded-lg p-3 sm:col-span-2">
                                        <p class="text-xs text-muted-foreground mb-1">Transaction Hash</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.transaction_hash) }}</p>
                                            <button @click="copyToClipboard(tx.transaction_hash!, tx.id + 1000)" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                <CheckIcon v-if="copiedHash === `${tx.id + 1000}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                            <a :href="getExplorerUrl(tx.transaction_hash!)" target="_blank" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sent Transaction -->
                                <div v-if="tx.type === 'sent'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Amount Sent</p>
                                        <p class="text-sm font-semibold text-accent">-{{ formatAmount(tx.amount) }} {{ tx.token_symbol }}</p>
                                    </div>
                                    <div v-if="tx.fee" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Transaction Fee</p>
                                        <p class="text-sm font-semibold text-card-foreground">{{ formatAmount(tx.fee) }} {{ tx.token_symbol }}</p>
                                    </div>
                                    <div v-if="tx.recipient_address" class="bg-muted/50 rounded-lg p-3 sm:col-span-2">
                                        <p class="text-xs text-muted-foreground mb-1">Recipient Address</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.recipient_address) }}</p>
                                            <button @click="copyToClipboard(tx.recipient_address!, tx.id + 2000)" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                <CheckIcon v-if="copiedHash === `${tx.id + 2000}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="tx.transaction_hash" class="bg-muted/50 rounded-lg p-3 sm:col-span-2">
                                        <p class="text-xs text-muted-foreground mb-1">Transaction Hash</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.transaction_hash) }}</p>
                                            <button @click="copyToClipboard(tx.transaction_hash!, tx.id + 3000)" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                <CheckIcon v-if="copiedHash === `${tx.id + 3000}`" class="w-3 h-3 text-primary" />
                                                <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                            </button>
                                            <a :href="getExplorerUrl(tx.transaction_hash!)" target="_blank" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Trade Transactions (Forex, Stock, Crypto) -->
                                <div v-if="['forex_trade', 'stock_trade', 'crypto_trade'].includes(tx.type)" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Trading Pair</p>
                                        <p class="text-sm font-semibold text-card-foreground">{{ tx.pair_name || tx.pair }}</p>
                                    </div>
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Position Type</p>
                                        <div class="flex items-center gap-1">
                                            <component :is="tx.trade_direction === 'Up' ? TrendingUpIcon : TrendingDownIcon"
                                                       class="w-3.5 h-3.5"
                                                       :class="tx.trade_direction === 'Up' ? 'text-success' : 'text-destructive'" />
                                            <p class="text-sm font-semibold" :class="tx.trade_direction === 'Up' ? 'text-success' : 'text-destructive'">
                                                {{ tx.trade_direction }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Amount</p>
                                        <p class="text-sm font-semibold text-card-foreground">${{ formatAmount(tx.amount) }}</p>
                                    </div>
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Leverage</p>
                                        <p class="text-sm font-semibold text-card-foreground">{{ tx.leverage }}x</p>
                                    </div>
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Entry Price</p>
                                        <p class="text-sm font-semibold text-card-foreground">${{ formatAmount(tx.entry_price) }}</p>
                                    </div>
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Exit Price</p>
                                        <p class="text-sm font-semibold text-card-foreground">
                                            {{ tx.exit_price ? `$${formatAmount(tx.exit_price)}` : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">P&L</p>
                                        <p class="text-sm font-semibold" :class="tx.pnl >= 0 ? 'text-success' : 'text-destructive'">
                                            {{ tx.pnl >= 0 ? '+' : '' }}${{ formatAmount(tx.pnl) }}
                                        </p>
                                    </div>
                                    <div class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Trading Mode</p>
                                        <p class="text-sm font-semibold text-card-foreground capitalize">{{ tx.trading_mode }}</p>
                                    </div>
                                    <div v-if="tx.duration" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Duration</p>
                                        <p class="text-sm font-semibold text-card-foreground">{{ tx.duration }}</p>
                                    </div>
                                    <div v-if="tx.expiry_time" class="bg-muted/50 rounded-lg p-3">
                                        <p class="text-xs text-muted-foreground mb-1">Expiry Time</p>
                                        <p class="text-sm font-semibold text-card-foreground">{{ formatDate(tx.expiry_time) }}</p>
                                    </div>
                                </div>

                                <!-- Investment Transaction -->
                                <div v-if="tx.type === 'investment'" class="space-y-4">
                                    <!-- Header Info -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Investment Plan</p>
                                            <p class="text-sm font-semibold text-card-foreground">{{ tx.plan_name }}</p>
                                        </div>
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Amount Invested</p>
                                            <p class="text-sm font-semibold text-card-foreground">${{ formatAmount(tx.amount) }}</p>
                                        </div>
                                    </div>

                                    <!-- Financial Details -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Interest/Cycle</p>
                                            <p class="text-sm font-semibold text-primary">${{ formatAmount(tx.interest) }}</p>
                                        </div>
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Total Earned</p>
                                            <p class="text-sm font-semibold text-success">+${{ formatAmount(tx.totalInterestEarned) }}</p>
                                        </div>
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Period</p>
                                            <p class="text-sm font-semibold text-card-foreground">{{ tx.period >= 24 ? `${tx.period / 24} ${tx.period / 24 === 1 ? 'day' : 'days'}` : `${tx.period} ${tx.period === 1 ? 'hour' : 'hours'}` }}</p>
                                        </div>
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Cycles</p>
                                            <p class="text-sm font-semibold text-card-foreground">{{ tx.progress?.currentCycle }} / {{ tx.progress?.totalCycles }}</p>
                                        </div>
                                    </div>

                                    <!-- Current Cycle Progress -->
                                    <div class="bg-muted/50 rounded-lg p-3 space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-medium text-muted-foreground">Progress</span>
                                            <span class="font-bold text-card-foreground">{{ tx.progress?.percentage.toFixed(1) }}%</span>
                                        </div>
                                        <div class="w-full bg-muted rounded-full h-1 overflow-hidden">
                                            <div
                                                class="h-full transition-all duration-500 rounded-full"
                                                :class="tx.progress?.isExpired ? 'bg-success' : 'bg-warning'"
                                                :style="{ width: `${tx.progress?.percentage}%` }">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Countdown Timer -->
                                    <div class="rounded-lg p-3 border border-primary/20">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <ClockIcon class="w-4 h-4 text-primary" />
                                                <span class="text-xs font-medium text-muted-foreground">
                                                    {{ tx.progress?.isExpired ? 'Status' : 'Next Payout In' }}
                                                </span>
                                            </div>
                                            <span
                                                class="text-sm font-mono font-bold"
                                                :class="tx.progress?.isExpired ? 'text-success' : 'text-primary'">
                                                {{ tx.progress?.countdown }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Additional Details -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Capital Back</p>
                                            <p class="text-sm font-semibold" :class="tx.capital_back_status === 'yes' ? 'text-success' : 'text-muted-foreground'">
                                                {{ tx.capital_back_status === 'yes' ? 'Yes' : 'No' }}
                                            </p>
                                        </div>
                                        <div v-if="tx.last_time" class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Last Payout</p>
                                            <p class="text-sm font-semibold text-card-foreground">{{ formatDate(tx.last_time) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="isLoadingMore" class="flex items-center justify-center py-8">
                            <Loader2Icon class="w-8 h-8 text-primary animate-spin" />
                        </div>

                        <div v-else-if="!hasMoreTransactions && displayedTransactions.length > itemsPerLoad" class="text-center py-8">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-muted/50 rounded-full text-sm text-muted-foreground">
                                <CheckCircleIcon class="w-4 h-4" />
                                You've reached the end of the list
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block">
                <div class="xl:col-span-1 space-y-6 sticky top-6">
                    <div class="border border-primary/20 rounded-2xl p-6">
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

                    <div class="border border-border rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                            <InfoIcon class="w-5 h-5 text-primary" />
                            Transaction Types
                        </h5>
                        <div class="space-y-4 text-xs text-muted-foreground">
                            <div>
                                <h6 class="font-semibold text-card-foreground mb-1 flex items-center gap-2">
                                    <ArrowRightLeftIcon class="w-3.5 h-3.5 text-primary" />
                                    Swaps
                                </h6>
                                <p>Exchange one cryptocurrency for another instantly.</p>
                            </div>
                            <div>
                                <h6 class="font-semibold text-card-foreground mb-1 flex items-center gap-2">
                                    <DollarSignIcon class="w-3.5 h-3.5 text-blue-500" />
                                    Trades
                                </h6>
                                <p>Forex, stocks, and crypto trading with leverage.</p>
                            </div>
                            <div>
                                <h6 class="font-semibold text-card-foreground mb-1 flex items-center gap-2">
                                    <WalletIcon class="w-3.5 h-3.5 text-green-500" />
                                    Investments
                                </h6>
                                <p>Long-term investment plans with regular payouts.</p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center p-6 border border-border rounded-2xl">
                        <div class="w-12 h-12 rounded-full bg-primary/10 mx-auto mb-3 flex items-center justify-center">
                            <InfoIcon class="w-6 h-6 text-primary" />
                        </div>
                        <h5 class="text-sm font-semibold text-card-foreground mb-2">Need Help?</h5>
                        <p class="text-xs text-muted-foreground mb-4">
                            Having issues with a transaction? Our support team is ready to assist you.
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
