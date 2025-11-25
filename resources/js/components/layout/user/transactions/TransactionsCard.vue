<script setup lang="ts">
    import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
    import {
        ArrowRightLeftIcon, ArrowDownLeftIcon, ArrowUpRightIcon, HistoryIcon,
        SearchIcon, FilterIcon, XIcon, Loader2Icon, CheckCircleIcon,
        ClockIcon, AlertCircleIcon, CopyIcon, CheckIcon,
        ActivityIcon, ArrowUpDownIcon,
        SortAscIcon, SortDescIcon, TrendingUpIcon, TrendingDownIcon,
        DollarSignIcon, BarChart3Icon, CoinsIcon, WalletIcon, PieChartIcon, ExternalLinkIcon
    } from 'lucide-vue-next';
    import { router } from '@inertiajs/vue3';

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
        from_amount?: number | string;
        to_amount?: number | string;
        chain?: string;
        // Received/Sent crypto
        token_symbol?: string;
        amount?: number | string;
        wallet_address?: string;
        recipient_address?: string;
        transaction_hash?: string;
        fee?: number | string;
        // Trades (forex, stocks, crypto)
        pair?: string;
        pair_name?: string;
        entry_price?: number | string;
        exit_price?: number | string;
        leverage?: number | string;
        duration?: string;
        pnl?: number | string;
        trading_mode?: string;
        opened_at?: string;
        closed_at?: string;
        // Investments
        plan_id?: number;
        plan_name?: string;
        interest?: number | string;
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
                failed: number;
                successRate: number;
                totalVolume: number;
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
    const dateFilter = ref<string>('all');
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

    const computedStatistics = computed(() => {
        const allTx = allTransactions.value;
        const total = allTx.length;
        const completed = allTx.filter(tx => ['completed', 'success', 'closed'].includes(tx.status.toLowerCase())).length;
        const pending = allTx.filter(tx => ['pending', 'processing', 'open', 'running'].includes(tx.status.toLowerCase())).length;
        const failed = allTx.filter(tx => ['failed', 'error', 'cancelled'].includes(tx.status.toLowerCase())).length;
        const successRate = total > 0 ? Math.round((completed / total) * 100) : 0;

        let totalVolume = 0;
        allTx.forEach(tx => {
            if (tx.type === 'swap') {
                totalVolume += Number(tx.from_amount || 0) + Number(tx.to_amount || 0);
            } else {
                totalVolume += Math.abs(Number(tx.amount || 0));
            }
        });

        const swaps = allTx.filter(tx => tx.type === 'swap').length;
        const received = allTx.filter(tx => tx.type === 'received').length;
        const sent = allTx.filter(tx => tx.type === 'sent').length;
        const forex_trades = allTx.filter(tx => tx.type === 'forex_trade').length;
        const stock_trades = allTx.filter(tx => tx.type === 'stock_trade').length;
        const crypto_trades = allTx.filter(tx => tx.type === 'crypto_trade').length;
        const investments = allTx.filter(tx => tx.type === 'investment').length;

        return {
            total,
            completed,
            pending,
            failed,
            successRate,
            totalVolume: Math.round(totalVolume * 100) / 100,
            swaps,
            received,
            sent,
            forex_trades,
            stock_trades,
            crypto_trades,
            investments
        };
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

        if (dateFilter.value !== 'all') {
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

            filtered = filtered.filter(tx => {
                const txDate = new Date(tx.created_at);
                const txDateOnly = new Date(txDate.getFullYear(), txDate.getMonth(), txDate.getDate());

                switch (dateFilter.value) {
                    case 'today':
                        return txDateOnly.getTime() === today.getTime();
                    case 'week':
                        const weekAgo = new Date(today);
                        weekAgo.setDate(weekAgo.getDate() - 7);
                        return txDate >= weekAgo;
                    case 'month':
                        const monthAgo = new Date(today);
                        monthAgo.setDate(monthAgo.getDate() - 30);
                        return txDate >= monthAgo;
                    default:
                        return true;
                }
            });
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
        return searchQuery.value.trim() !== '' || sortOrder.value !== 'default' || filterByStatus.value !== 'all' || dateFilter.value !== 'all';
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
        return 'text-muted-foreground bg-muted/20 border-border/50';
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

    const formatAmount = (amount: number | string | undefined) => {
        const num = Number(amount);
        if (isNaN(num) && num !== 0) return '0.00';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 8
        }).format(num);
    };

    const truncateHash = (hash: string | undefined) => {
        if (!hash) return 'N/A';
        if (hash.length < 11) return hash;
        return `${hash.slice(0, 6)}...${hash.slice(-4)}`;
    };

    const copyToClipboard = (text: string, id: number) => {
        if (!navigator.clipboard) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                copiedHash.value = `${id}`;
                setTimeout(() => {
                    copiedHash.value = null;
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            document.body.removeChild(textArea);
        } else {
            navigator.clipboard.writeText(text).then(() => {
                copiedHash.value = `${id}`;
                setTimeout(() => {
                    copiedHash.value = null;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }
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
        const interestPerCycle = Number(tx.interest || 0);
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
        dateFilter.value = 'all';
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

    const navigateToTab = (tabId: string) => {
        activeTab.value = tabId as typeof activeTab.value;
        router.visit(route('user.transactions.index', { tab: tabId }), {
            preserveScroll: true,
            preserveState: true
        });
    };

    const tabs = [
        { id: 'all', label: 'All', icon: HistoryIcon },
        { id: 'swaps', label: 'Swaps', icon: ArrowRightLeftIcon },
        { id: 'received', label: 'Received', icon: ArrowDownLeftIcon },
        { id: 'sent', label: 'Sent', icon: ArrowUpRightIcon },
        { id: 'forex', label: 'Forex', icon: DollarSignIcon },
        { id: 'stocks', label: 'Stocks', icon: BarChart3Icon },
        { id: 'crypto_trades', label: 'Crypto', icon: CoinsIcon },
        { id: 'investments', label: 'Investments', icon: WalletIcon }
    ];

    watch([searchQuery, sortOrder, filterByStatus, activeTab, dateFilter], () => {
        displayCount.value = Math.min(itemsPerLoad, totalTransactionsCount.value);
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = 0;
        }
    });
</script>

<template>
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">Transaction Center</h1>
            <p class="text-muted-foreground">
                Track all your transactions including crypto swaps, transfers, trades (forex, stocks, crypto), and investments.
            </p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
        <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                    <ActivityIcon class="w-5 h-5" />
                </div>
                <span class="text-xs font-medium">Total</span>
            </div>
            <p class="text-2xl font-bold text-card-foreground">{{ computedStatistics.total }}</p>
            <p class="text-xs text-muted-foreground mt-1">All transactions</p>
        </div>

        <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                    <CheckCircleIcon class="w-5 h-5" />
                </div>
                <span class="text-xs font-medium">Success</span>
            </div>
            <p class="text-2xl font-bold text-card-foreground">{{ computedStatistics.completed }}</p>
            <p class="text-xs text-muted-foreground mt-1">{{ computedStatistics.successRate }}% rate</p>
        </div>

        <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                    <ClockIcon class="w-5 h-5" />
                </div>
                <span class="text-xs font-medium">Pending</span>
            </div>
            <p class="text-2xl font-bold text-card-foreground">{{ computedStatistics.pending }}</p>
            <p class="text-xs text-muted-foreground mt-1">In progress</p>
        </div>

        <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                    <AlertCircleIcon class="w-5 h-5" />
                </div>
                <span class="text-xs font-medium">Failed</span>
            </div>
            <p class="text-2xl font-bold text-card-foreground">{{ computedStatistics.failed }}</p>
            <p class="text-xs text-muted-foreground mt-1">Unsuccessful</p>
        </div>

        <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                    <DollarSignIcon class="w-5 h-5" />
                </div>
                <span class="text-xs font-medium">Volume</span>
            </div>
            <p class="text-xl font-bold text-card-foreground">${{ formatAmount(computedStatistics.totalVolume) }}</p>
            <p class="text-xs text-muted-foreground mt-1">Total value</p>
        </div>

        <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
            <div class="flex items-center justify-between mb-2">
                <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                    <PieChartIcon class="w-5 h-5" />
                </div>
                <span class="text-xs font-medium">Types</span>
            </div>
            <p class="text-2xl font-bold text-card-foreground">7</p>
            <p class="text-xs text-muted-foreground mt-1">Categories</p>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-card rounded-2xl border border-border overflow-hidden margin-bottom">
        <!-- Tabs -->
        <div class="bg-muted/30 px-4 sm:px-6 py-4 border-b border-border">
            <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar pb-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="navigateToTab(tab.id)"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm duration-200 whitespace-nowrap cursor-pointer"
                    :class="activeTab === tab.id
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground border border-border hover:bg-muted hover:text-card-foreground'"
                >
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.label }}
                    <span
                        v-if="tab.id !== 'all'"
                        class="px-2 py-0.5 text-xs rounded-full"
                        :class="activeTab === tab.id ? 'bg-primary-foreground/20' : 'bg-muted'"
                    >
                        {{ computedStatistics[tab.id === 'forex' ? 'forex_trades' : tab.id === 'stocks' ? 'stock_trades' : tab.id === 'crypto_trades' ? 'crypto_trades' : tab.id] }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Filters & Controls -->
        <div class="bg-background/50 px-4 sm:px-6 py-4 border-b border-border">
            <div class="flex flex-col gap-4">
                <!-- Top Row: Search & View Controls -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="relative flex-1">
                        <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search transactions..."
                            class="w-full pl-10 pr-10 py-2.5 input-crypto border border-border rounded-lg text-sm"
                        />
                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 hover:bg-muted/50 rounded cursor-pointer"
                        >
                            <XIcon class="w-4 h-4 text-muted-foreground" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="showFilters = !showFilters"
                            class="px-4 py-2.5 rounded-lg text-sm font-medium bg-background hover:bg-muted border border-border flex items-center gap-2 cursor-pointer"
                            :class="{ 'bg-primary/10 text-primary border-primary/30': hasActiveFilters }"
                        >
                            <FilterIcon class="w-4 h-4" />
                            <span class="hidden sm:inline">Filters</span>
                            <span v-if="hasActiveFilters" class="w-2 h-2 rounded-full bg-primary"></span>
                        </button>

                        <span class="px-3 py-2.5 rounded-lg text-sm font-medium bg-muted text-muted-foreground border border-border whitespace-nowrap">
                            {{ displayedTransactions.length }} / {{ totalTransactionsCount }}
                        </span>
                    </div>
                </div>

                <!-- Expandable Filters -->
                <div v-if="showFilters" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-border">
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1.5">Date Range</label>
                        <select
                            v-model="dateFilter"
                            class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm hover:bg-muted/50 cursor-pointer"
                        >
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">Last 7 Days</option>
                            <option value="month">Last 30 Days</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1.5">Status</label>
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

                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1.5">Sort Order</label>
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

                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="sm:col-span-3 px-4 py-2 border border-destructive/30 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <XIcon class="w-4 h-4" />
                        Clear All Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Transactions Content -->
        <div ref="scrollContainer" @scroll="handleScroll" class="p-4 sm:p-6 max-h-[800px] overflow-y-auto hide-scrollbar">
            <!-- Empty States -->
            <div v-if="allTransactions.length === 0" class="text-center py-16">
                <div class="w-20 h-20 rounded-2xl bg-muted/70 mx-auto mb-4 flex items-center justify-center">
                    <HistoryIcon class="w-10 h-10 text-muted-foreground" />
                </div>
                <h4 class="text-xl font-semibold text-card-foreground mb-2">No Transactions Yet</h4>
                <p class="text-sm text-muted-foreground">Transactions will appear here once users start trading.</p>
            </div>

            <div v-else-if="displayedTransactions.length === 0" class="text-center py-16">
                <div class="w-20 h-20 rounded-2xl bg-muted/70 mx-auto mb-4 flex items-center justify-center">
                    <SearchIcon class="w-10 h-10 text-muted-foreground" />
                </div>
                <h4 class="text-xl font-semibold text-card-foreground mb-2">No Results Found</h4>
                <p class="text-sm text-muted-foreground mb-4">Try adjusting your filters or search terms.</p>
                <button @click="clearFilters" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium cursor-pointer">
                    Clear Filters
                </button>
            </div>

            <!-- Grid View -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="tx in displayedTransactions"
                    :key="tx.compositeId"
                    class="group bg-background border border-border rounded-xl overflow-hidden hover:border-primary/30 duration-300 cursor-pointer"
                >
                    <!-- Card Header -->
                    <div class="p-4 border-b border-border bg-muted/30">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center border" :class="getTransactionColor(tx.type)">
                                    <component :is="getTransactionIcon(tx.type)" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-card-foreground">{{ getTransactionLabel(tx.type) }}</h4>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(tx.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <component :is="getStatusIcon(tx.status)" class="w-3.5 h-3.5" :class="getStatusColor(tx.status).split(' ')[0]" />
                            <span class="px-2 py-0.5 text-xs rounded-full border capitalize font-medium" :class="getStatusColor(tx.status)">
                                {{ tx.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="p-4 space-y-3">
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
                                    <a :href="getExplorerUrl(tx.transaction_hash!, tx.chain)" target="_blank" rel="noopener noreferrer" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
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
                                    <a :href="getExplorerUrl(tx.transaction_hash!)" target="_blank" rel="noopener noreferrer" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
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
                                    <a :href="getExplorerUrl(tx.transaction_hash!)" target="_blank" rel="noopener noreferrer" class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                        <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Trade Transactions (Forex, Stock, Crypto) -->
                        <div v-if="['forex_trade', 'stock_trade', 'crypto_trade'].includes(tx.type)" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Pair:</span>
                                <span class="text-sm font-semibold text-card-foreground">{{ tx.pair_name || tx.pair }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Direction:</span>
                                <div class="flex items-center gap-1">
                                    <component :is="tx.trade_direction === 'Up' ? TrendingUpIcon : TrendingDownIcon"
                                               class="w-3.5 h-3.5"
                                               :class="tx.trade_direction === 'Up' ? 'text-success' : 'text-destructive'" />
                                    <span class="text-sm font-semibold" :class="tx.trade_direction === 'Up' ? 'text-success' : 'text-destructive'">
                                                    {{ tx.trade_direction }}
                                                </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Amount:</span>
                                <span class="text-sm font-semibold text-card-foreground">${{ formatAmount(tx.amount) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">P&L:</span>
                                <span class="text-sm font-bold" :class="tx.pnl >= 0 ? 'text-success' : 'text-destructive'">
                                                {{ tx.pnl >= 0 ? '+' : '' }}${{ formatAmount(tx.pnl) }}
                                            </span>
                            </div>
                        </div>

                        <!-- Investment Transaction -->
                        <div v-if="tx.type === 'investment'" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Plan:</span>
                                <span class="text-sm font-semibold text-card-foreground">{{ tx.plan_name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Invested:</span>
                                <span class="text-sm font-semibold text-card-foreground">${{ formatAmount(tx.amount) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Earned:</span>
                                <span class="text-sm font-bold text-success">+${{ formatAmount(tx.totalInterestEarned) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Progress:</span>
                                <span class="text-sm font-semibold text-card-foreground">{{ tx.progress?.currentCycle }}/{{ tx.progress?.totalCycles }}</span>
                            </div>
                            <div class="w-full bg-muted rounded-full h-1 overflow-hidden">
                                <div
                                    class="h-full duration-500 rounded-full"
                                    :class="tx.progress?.isExpired ? 'bg-success' : 'bg-warning'"
                                    :style="{ width: `${tx.progress?.percentage}%` }">
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-primary/5 rounded-lg border border-primary/20">
                                <div class="flex items-center gap-2">
                                    <ClockIcon class="w-3.5 h-3.5 text-primary" />
                                    <span class="text-xs font-medium text-muted-foreground">
                                                    {{ tx.progress?.isExpired ? 'Status' : 'Next Payout' }}
                                                </span>
                                </div>
                                <span
                                    class="text-xs font-mono font-bold"
                                    :class="tx.progress?.isExpired ? 'text-success' : 'text-primary'">
                                                {{ tx.progress?.countdown }}
                                            </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading More -->
            <div v-if="isLoadingMore" class="flex items-center justify-center py-8">
                <Loader2Icon class="w-8 h-8 text-primary animate-spin" />
            </div>

            <!-- End of List -->
            <div v-else-if="!hasMoreTransactions && displayedTransactions.length > itemsPerLoad" class="text-center py-8">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-muted/50 rounded-full text-sm text-muted-foreground">
                    <CheckCircleIcon class="w-4 h-4" />
                    You've reached the end
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
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
