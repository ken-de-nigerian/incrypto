<script setup lang="ts">
    import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import {
        AlertCircleIcon,
        ArrowDownLeftIcon,
        ArrowRightLeftIcon,
        ArrowUpDownIcon,
        ArrowUpRightIcon,
        CheckCircleIcon,
        CheckIcon,
        ClockIcon,
        CopyIcon,
        FilterIcon,
        HistoryIcon,
        Loader2Icon,
        SearchIcon,
        SortAscIcon,
        SortDescIcon,
        ThumbsDownIcon,
        ThumbsUpIcon,
        XIcon,
        DollarSignIcon,
        BarChart3Icon,
        CoinsIcon,
        WalletIcon,
        TrendingUpIcon,
        TrendingDownIcon,
        UserIcon,
        ActivityIcon,
        PieChartIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TextLink from '@/components/TextLink.vue';
    import CryptoApprovalModal from '@/components/CryptoApprovalModal.vue';
    import TradeManagementModal from '@/components/TradeManagementModal.vue';
    import InvestmentCancellationModal from '@/components/InvestmentCancellationModal.vue';

    interface InvestmentProgress {
        countdown: string;
        percentage: number;
        isExpired: boolean;
        currentCycle: number;
        totalCycles: number;
    }

    interface Transaction {
        id: number;
        user_id?: number;
        user_name?: string;
        user_email?: string;
        type?: string;
        trade_direction?: string;
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
        plan_id?: number;
        plan_name?: string;
        interest?: number;
        period?: number;
        repeat_time?: number;
        repeat_time_count?: number;
        next_time?: string;
        last_time?: string;
        capital_back_status?: string;
        status: string;
        created_at: string;
        compositeId?: string;
        progress?: InvestmentProgress;
        totalInterestEarned?: number;
    }

    const props = defineProps({
        crypto_swaps: {
            type: Array as () => Transaction[],
            default: () => []
        },
        received_cryptos: {
            type: Array as () => Transaction[],
            default: () => []
        },
        sent_cryptos: {
            type: Array as () => Transaction[],
            default: () => []
        },
        forex_trades: {
            type: Array as () => Transaction[],
            default: () => []
        },
        stock_trades: {
            type: Array as () => Transaction[],
            default: () => []
        },
        crypto_trades: {
            type: Array as () => Transaction[],
            default: () => []
        },
        investment_histories: {
            type: Array as () => Transaction[],
            default: () => []
        },
        tab: {
            type: String as () => 'all' | 'swaps' | 'received' | 'sent' | 'forex' | 'stocks' | 'crypto_trades' | 'investments',
            default: 'all'
        }
    });

    const page = usePage();
    const activeTab = ref<'all' | 'swaps' | 'received' | 'sent' | 'forex' | 'stocks' | 'crypto_trades' | 'investments'>(props.tab);
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
    const dateFilter = ref<'all' | 'today' | 'week' | 'month' | 'custom'>('all');

    const currentTime = ref(new Date());
    let countdownInterval: ReturnType<typeof setInterval> | null = null;

    const selectedTransaction = ref<Transaction | null>(null);
    const showCryptoModal = ref(false);
    const showTradeModal = ref(false);
    const showInvestmentModal = ref(false);
    const modalActionType = ref<'approve' | 'reject' | 'close' | 'cancel' | null>(null);

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
        if (days > 0) countdown = `${days}d ${hours}h ${minutes}m`;
        else if (hours > 0) countdown = `${hours}h ${minutes}m ${seconds}s`;
        else if (minutes > 0) countdown = `${minutes}m ${seconds}s`;
        else countdown = `${seconds}s`;

        return { countdown, percentage, isExpired: false, currentCycle, totalCycles };
    };

    const calculateTotalInterestEarned = (tx: Transaction): number => {
        return (tx.repeat_time_count || 0) * (tx.interest || 0);
    };

    const allTransactions = computed(() => {
        const swaps = props.crypto_swaps.map(tx => ({
            ...tx,
            type: 'swap' as const,
            compositeId: `swap-${tx.id}`
        }));
        const received = props.received_cryptos.map(tx => ({
            ...tx,
            type: 'received' as const,
            compositeId: `received-${tx.id}`
        }));
        const sent = props.sent_cryptos.map(tx => ({
            ...tx,
            type: 'sent' as const,
            compositeId: `sent-${tx.id}`
        }));
        const forexTrades = props.forex_trades.map(tx => ({
            ...tx,
            trade_direction: (tx as any).type,
            type: 'forex_trade' as const,
            compositeId: `forex-${tx.id}`
        }));
        const stockTrades = props.stock_trades.map(tx => ({
            ...tx,
            trade_direction: (tx as any).type,
            type: 'stock_trade' as const,
            compositeId: `stock-${tx.id}`
        }));
        const cryptoTrades = props.crypto_trades.map(tx => ({
            ...tx,
            trade_direction: (tx as any).type,
            type: 'crypto_trade' as const,
            compositeId: `crypto-trade-${tx.id}`
        }));
        const investments = props.investment_histories.map(tx => ({
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
                tx.user_name?.toLowerCase().includes(query) ||
                tx.user_email?.toLowerCase().includes(query) ||
                tx.pair?.toLowerCase().includes(query) ||
                tx.pair_name?.toLowerCase().includes(query) ||
                tx.plan_name?.toLowerCase().includes(query)
            );
        }

        if (filterByStatus.value !== 'all') {
            filtered = filtered.filter(tx => tx.status.toLowerCase() === filterByStatus.value.toLowerCase());
        }

        if (dateFilter.value !== 'all') {
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

            filtered = filtered.filter(tx => {
                const txDate = new Date(tx.created_at);

                if (dateFilter.value === 'today') {
                    return txDate >= today;
                } else if (dateFilter.value === 'week') {
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    return txDate >= weekAgo;
                } else if (dateFilter.value === 'month') {
                    const monthAgo = new Date(today);
                    monthAgo.setMonth(monthAgo.getMonth() - 1);
                    return txDate >= monthAgo;
                }
                return true;
            });
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

    const statistics = computed(() => {
        const all = allTransactions.value;
        const completed = all.filter(tx => tx.status === 'completed' || tx.status === 'success' || tx.status === 'Closed').length;
        const pending = all.filter(tx => tx.status === 'pending' || tx.status === 'processing' || tx.status === 'Open' || tx.status === 'running').length;
        const failed = all.filter(tx => tx.status === 'failed' || tx.status === 'error' || tx.status === 'cancelled').length;

        const totalVolume = all.reduce((sum, tx) => {
            const amount = parseFloat(tx.amount as any) || 0;
            return sum + amount;
        }, 0);

        const successRate = all.length > 0 ? ((completed / all.length) * 100).toFixed(1) : '0';

        return {
            total: all.length,
            completed,
            pending,
            failed,
            swaps: props.crypto_swaps.length,
            received: props.received_cryptos.length,
            sent: props.sent_cryptos.length,
            forex_trades: props.forex_trades.length,
            stock_trades: props.stock_trades.length,
            crypto_trades: props.crypto_trades.length,
            investments: props.investment_histories.length,
            totalVolume,
            successRate
        };
    });

    const getTransactionIcon = (type: string) => {
        switch (type) {
            case 'swap': return ArrowRightLeftIcon;
            case 'received': return ArrowDownLeftIcon;
            case 'sent': return ArrowUpRightIcon;
            case 'forex_trade': return DollarSignIcon;
            case 'stock_trade': return BarChart3Icon;
            case 'crypto_trade': return CoinsIcon;
            case 'investment': return WalletIcon;
            default: return HistoryIcon;
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
        switch (status.toLowerCase()) {
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
        dateFilter.value = 'all';
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
        { id: 'all', label: 'All', icon: HistoryIcon },
        { id: 'swaps', label: 'Swaps', icon: ArrowRightLeftIcon, params: { tab: 'swaps' } },
        { id: 'received', label: 'Received', icon: ArrowDownLeftIcon, params: { tab: 'received' } },
        { id: 'sent', label: 'Sent', icon: ArrowUpRightIcon, params: { tab: 'sent' } },
        { id: 'forex', label: 'Forex', icon: DollarSignIcon, params: { tab: 'forex' } },
        { id: 'stocks', label: 'Stocks', icon: BarChart3Icon, params: { tab: 'stocks' } },
        { id: 'crypto_trades', label: 'Crypto', icon: CoinsIcon, params: { tab: 'crypto_trades' } },
        { id: 'investments', label: 'Investments', icon: WalletIcon, params: { tab: 'investments' } }
    ];

    const user = computed(() => page.props.auth.user);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const isNotificationsModalOpen = ref(false);
    const notificationCount = computed(() => page.props.auth.notification_count);

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: 'Transactions' }
    ];

    const openCryptoModal = (tx: Transaction, type: 'approve' | 'reject') => {
        selectedTransaction.value = tx;
        modalActionType.value = type;
        showCryptoModal.value = true;
    };

    const openTradeModal = (tx: Transaction) => {
        selectedTransaction.value = tx;
        modalActionType.value = 'close';
        showTradeModal.value = true;
    };

    const openInvestmentModal = (tx: Transaction) => {
        selectedTransaction.value = tx;
        modalActionType.value = 'cancel';
        showInvestmentModal.value = true;
    };

    const closeCryptoModal = () => {
        showCryptoModal.value = false;
        selectedTransaction.value = null;
        modalActionType.value = null;
    };

    const closeTradeModal = () => {
        showTradeModal.value = false;
        selectedTransaction.value = null;
        modalActionType.value = null;
    };

    const closeInvestmentModal = () => {
        showInvestmentModal.value = false;
        selectedTransaction.value = null;
        modalActionType.value = null;
    };

    watch([searchQuery, sortOrder, filterByStatus, activeTab, dateFilter], () => {
        displayCount.value = Math.min(itemsPerLoad, totalTransactionsCount.value);
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = 0;
        }
    });
</script>

<template>
    <Head title="Transactions" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="mt-8 space-y-6">
                <!-- Header Section -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">Transaction Center</h1>
                        <p class="text-muted-foreground">Monitor and manage all platform transactions in real-time</p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center">
                                <ActivityIcon class="w-5 h-5 text-primary" />
                            </div>
                            <span class="text-xs font-medium text-primary/70">Total</span>
                        </div>
                        <p class="text-2xl font-bold text-card-foreground">{{ statistics.total }}</p>
                        <p class="text-xs text-muted-foreground mt-1">All transactions</p>
                    </div>

                    <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 rounded-lg bg-success/20 flex items-center justify-center">
                                <CheckCircleIcon class="w-5 h-5 text-success" />
                            </div>
                            <span class="text-xs font-medium text-success/70">Success</span>
                        </div>
                        <p class="text-2xl font-bold text-card-foreground">{{ statistics.completed }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ statistics.successRate }}% rate</p>
                    </div>

                    <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 rounded-lg bg-warning/20 flex items-center justify-center">
                                <ClockIcon class="w-5 h-5 text-warning" />
                            </div>
                            <span class="text-xs font-medium text-warning/70">Pending</span>
                        </div>
                        <p class="text-2xl font-bold text-card-foreground">{{ statistics.pending }}</p>
                        <p class="text-xs text-muted-foreground mt-1">In progress</p>
                    </div>

                    <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 rounded-lg bg-destructive/20 flex items-center justify-center">
                                <AlertCircleIcon class="w-5 h-5 text-destructive" />
                            </div>
                            <span class="text-xs font-medium text-destructive/70">Failed</span>
                        </div>
                        <p class="text-2xl font-bold text-card-foreground">{{ statistics.failed }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Unsuccessful</p>
                    </div>

                    <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <DollarSignIcon class="w-5 h-5 text-blue-500" />
                            </div>
                            <span class="text-xs font-medium text-blue-500/70">Volume</span>
                        </div>
                        <p class="text-xl font-bold text-card-foreground">${{ formatAmount(statistics.totalVolume) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Total value</p>
                    </div>

                    <div class="bg-card backdrop-blur-sm rounded-xl p-4 border border-border cursor-pointer">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                <PieChartIcon class="w-5 h-5 text-purple-500" />
                            </div>
                            <span class="text-xs font-medium text-purple-500/70">Types</span>
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
                            <TextLink
                                v-for="tab in tabs"
                                :key="tab.id"
                                :href="tab.params ? route('admin.transaction.index', tab.params) : route('admin.transaction.index')"
                                class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm duration-200 whitespace-nowrap cursor-pointer"
                                :class="activeTab === tab.id
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground border border-border hover:bg-muted hover:text-card-foreground'"
                                preserve-scroll
                                preserve-state
                                @click="activeTab = tab.id as typeof activeTab"
                            >
                                <component :is="tab.icon" class="w-4 h-4" />
                                {{ tab.label }}
                                <span
                                    v-if="tab.id !== 'all'"
                                    class="px-2 py-0.5 text-xs rounded-full"
                                    :class="activeTab === tab.id ? 'bg-primary-foreground/20' : 'bg-muted'"
                                >
                                    {{ statistics[tab.id === 'forex' ? 'forex_trades' : tab.id === 'stocks' ? 'stock_trades' : tab.id === 'crypto_trades' ? 'crypto_trades' : tab.id] }}
                                </span>
                            </TextLink>
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
                    <div ref="scrollContainer" @scroll="handleScroll" class="p-4 sm:p-6 max-h-[800px] overflow-y-auto no-scrollbar">
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
                                    <!-- User Info (if available) -->
                                    <div v-if="tx.user_id" class="flex items-center justify-between gap-2 p-2 bg-primary/5 rounded-lg border border-primary/20">
                                        <div class="flex items-center gap-2 flex-1 min-w-0">
                                            <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                                                <UserIcon class="w-4 h-4 text-primary" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-semibold text-card-foreground truncate">{{ tx.user_name }}</p>
                                                <p class="text-xs text-muted-foreground truncate">{{ tx.user_email }}</p>
                                            </div>
                                        </div>
                                        <TextLink
                                            :href="route('admin.users.show', tx.user_id)"
                                            class="px-2 py-1 text-xs font-medium bg-primary hover:bg-primary/90 text-primary-foreground rounded cursor-pointer whitespace-nowrap flex-shrink-0"
                                        >
                                            View
                                        </TextLink>
                                    </div>

                                    <!-- Swap Details -->
                                    <div v-if="tx.type === 'swap'" class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-muted-foreground">From:</span>
                                            <span class="font-semibold text-card-foreground">{{ formatAmount(tx.from_amount) }} {{ tx.from_token }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-muted-foreground">To:</span>
                                            <span class="font-semibold text-card-foreground">{{ formatAmount(tx.to_amount) }} {{ tx.to_token }}</span>
                                        </div>
                                        <div v-if="tx.chain" class="flex items-center justify-between text-sm">
                                            <span class="text-muted-foreground">Network:</span>
                                            <span class="font-medium text-card-foreground capitalize">{{ tx.chain }}</span>
                                        </div>
                                    </div>

                                    <!-- Received/Sent Details -->
                                    <div v-if="tx.type === 'received' || tx.type === 'sent'" class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-muted-foreground">Amount:</span>
                                            <span class="text-sm font-semibold" :class="tx.type === 'received' ? 'text-success' : 'text-accent'">
                                                {{ tx.type === 'received' ? '+' : '-' }}{{ formatAmount(tx.amount) }} {{ tx.token_symbol }}
                                            </span>
                                        </div>
                                        <div v-if="tx.transaction_hash" class="flex items-center justify-between text-sm">
                                            <span class="text-muted-foreground">Hash:</span>
                                            <div class="flex items-center gap-1">
                                                <span class="font-mono text-xs">{{ truncateHash(tx.transaction_hash) }}</span>
                                                <button @click="copyToClipboard(tx.transaction_hash!, tx.id)" class="p-1 hover:bg-muted rounded cursor-pointer">
                                                    <CheckIcon v-if="copiedHash === `${tx.id}`" class="w-3 h-3 text-success" />
                                                    <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Trade Details -->
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

                                    <!-- Investment Details -->
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

                                <!-- Card Actions -->
                                <div v-if="(tx.status === 'pending' || tx.status === 'processing') && (tx.type === 'swap' || tx.type === 'received' || tx.type === 'sent')"
                                     class="p-4 border-t border-border bg-muted/20">
                                    <div class="flex gap-2">
                                        <button @click="openCryptoModal(tx, 'approve')" class="flex-1 px-3 py-2 bg-success hover:bg-success/90 text-white rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer">
                                            <ThumbsUpIcon class="w-3.5 h-3.5" />
                                            Approve
                                        </button>
                                        <button @click="openCryptoModal(tx, 'reject')" class="flex-1 px-3 py-2 bg-destructive hover:bg-destructive/90 text-white rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer">
                                            <ThumbsDownIcon class="w-3.5 h-3.5" />
                                            Reject
                                        </button>
                                    </div>
                                </div>

                                <div v-if="['running', 'open', 'pending'].includes(tx.status.toLowerCase()) && ['forex_trade', 'stock_trade', 'crypto_trade'].includes(tx.type)"
                                     class="p-4 border-t border-border bg-muted/20">
                                    <button
                                        @click="openTradeModal(tx)"
                                        class="w-full px-3 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-xs font-medium flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <AlertCircleIcon class="w-4 h-4" />
                                        Manage Trade
                                    </button>
                                </div>

                                <div v-if="tx.status === 'running' && tx.type === 'investment'"
                                     class="p-4 border-t border-border bg-muted/20">
                                    <button
                                        @click="openInvestmentModal(tx)"
                                        class="w-full px-3 py-2 bg-destructive/10 hover:bg-destructive/20 text-destructive border border-destructive/30 rounded-lg text-xs font-medium flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <XIcon class="w-4 h-4" />
                                        Cancel Investment
                                    </button>
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
            </div>
        </div>
    </AppLayout>

    <CryptoApprovalModal
        v-if="selectedTransaction"
        :is-open="showCryptoModal"
        :transaction="selectedTransaction"
        :action-type="modalActionType"
        @close="closeCryptoModal"
    />

    <TradeManagementModal
        v-if="selectedTransaction"
        :is-open="showTradeModal"
        :trade="selectedTransaction"
        @close="closeTradeModal"
    />

    <InvestmentCancellationModal
        v-if="selectedTransaction"
        :is-open="showInvestmentModal"
        :investment="selectedTransaction"
        @close="closeInvestmentModal"
    />

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style scoped>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
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
