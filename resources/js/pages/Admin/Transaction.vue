<script setup lang="ts">
    import { computed, onMounted, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
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
        ExternalLinkIcon,
        FilterIcon,
        HistoryIcon,
        Loader2Icon,
        SearchIcon,
        SortAscIcon,
        SortDescIcon,
        ThumbsDownIcon,
        ThumbsUpIcon,
        XIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import InputError from '@/components/InputError.vue';
    import TextLink from '@/components/TextLink.vue';

    interface Transaction {
        id: number;
        user_id?: number;
        user_name?: string;
        user_email?: string;
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
        crypto_swaps: { type: Array as () => Transaction[], default: () => [] },
        received_cryptos: { type: Array as () => Transaction[], default: () => [] },
        sent_cryptos: { type: Array as () => Transaction[], default: () => [] },
        forex_trades: { type: Array as () => Transaction[], default: () => [] },      // NEW
        stock_trades: { type: Array as () => Transaction[], default: () => [] },      // NEW
        crypto_trades: { type: Array as () => Transaction[], default: () => [] },     // NEW
        investment_histories: { type: Array as () => Transaction[], default: () => [] }, // NEW
        tab: { type: String, default: 'all' }  // NEW - for tab persistence
    });

    const page = usePage();
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

    // Transaction action modal
    const showActionModal = ref(false);
    const selectedTransaction = ref<Transaction | null>(null);
    const actionType = ref<'approve' | 'reject' | null>(null);
    const isProcessing = ref(false);
    const approvalAmount = ref<string>('');
    const validationErrors = ref<Record<string, string>>({});

    // Initialize active tab from query string
    onMounted(() => {
        activeTab.value = page.url.includes('tab=swaps') ? 'swaps'
            : page.url.includes('tab=received') ? 'received'
                : page.url.includes('tab=sent') ? 'sent'
                    : 'all';
    });

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

        return [...swaps, ...received, ...sent].sort((a, b) =>
            new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
        );
    });

    // Filter transactions based on the active tab
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
                tx.recipient_address?.toLowerCase().includes(query) ||
                tx.user_name?.toLowerCase().includes(query) ||
                tx.user_email?.toLowerCase().includes(query)
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

    const statistics = computed(() => {
        const all = allTransactions.value;
        const completed = all.filter(tx => tx.status === 'completed' || tx.status === 'success').length;
        const pending = all.filter(tx => tx.status === 'pending' || tx.status === 'processing').length;

        return {
            total: all.length,
            completed,
            pending,
            swaps: props.crypto_swaps.length,
            received: props.received_cryptos.length,
            sent: props.sent_cryptos.length
        };
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
        const colors: Record<string, string> = {
            'swap': 'text-primary bg-primary/10 border-primary/30',
            'received': 'text-success bg-success/10 border-success/30',
            'sent': 'text-accent bg-accent/10 border-accent/30',
        };
        return colors[type] || 'text-muted-foreground bg-muted/20 border-border/50';
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
        const colors: Record<string, string> = {
            'completed': 'text-success bg-success/10 border-success/30',
            'success': 'text-success bg-success/10 border-success/30',
            'pending': 'text-warning bg-warning/10 border-warning/30',
            'processing': 'text-warning bg-warning/10 border-warning/30',
            'failed': 'text-destructive bg-destructive/10 border-destructive/30',
            'error': 'text-destructive bg-destructive/10 border-destructive/30',
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
        { id: 'swaps', label: 'Swaps', icon: ArrowRightLeftIcon, params: { tab: 'swaps' } },
        { id: 'received', label: 'Received', icon: ArrowDownLeftIcon, params: { tab: 'received' } },
        { id: 'sent', label: 'Sent', icon: ArrowUpRightIcon, params: { tab: 'sent' } }
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
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Transactions' }
    ];

    // Validation function
    const validateForm = (): boolean => {
        validationErrors.value = {};

        if (actionType.value === 'approve' && selectedTransaction.value?.type === 'received') {
            if (!approvalAmount.value || approvalAmount.value.toString().trim() === '') {
                validationErrors.value.amount = 'Amount is required';
                return false;
            }

            const amount = parseFloat(approvalAmount.value.toString());

            if (isNaN(amount)) {
                validationErrors.value.amount = 'Please enter a valid amount';
                return false;
            }

            if (amount <= 0) {
                validationErrors.value.amount = 'Amount must be greater than 0';
                return false;
            }

            if (amount > 999999999) {
                validationErrors.value.amount = 'Amount is too large';
                return false;
            }
        }

        return true;
    };

    // Action modal functions
    const openActionModal = (tx: Transaction, type: 'approve' | 'reject') => {
        selectedTransaction.value = tx;
        actionType.value = type;
        approvalAmount.value = '';
        validationErrors.value = {};
        showActionModal.value = true;
    };

    const closeActionModal = () => {
        showActionModal.value = false;
        selectedTransaction.value = null;
        actionType.value = null;
        isProcessing.value = false;
        approvalAmount.value = '';
        validationErrors.value = {};
    };

    const confirmAction = () => {
        if (!selectedTransaction.value || !actionType.value) return;

        if (!validateForm()) {
            return;
        }

        isProcessing.value = true;

        const endpoint = actionType.value === 'approve'
            ? route('admin.transaction.approve')
            : route('admin.transaction.reject');

        const payload: any = {
            transaction_id: selectedTransaction.value.id,
            transaction_type: selectedTransaction.value.type
        };

        if (actionType.value === 'approve' && selectedTransaction.value.type === 'received' && approvalAmount.value) {
            payload.amount = parseFloat(approvalAmount.value);
        }

        router.post(endpoint, payload, {
            onSuccess: () => {
                closeActionModal();
            },
            onError: (error) => {
                console.error('Error processing transaction:', error);
            },
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    };

    watch([searchQuery, sortOrder, filterByStatus], () => {
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

            <div class="mt-8">
                <div class="w-full mx-auto space-y-6">
                    <div class="bg-gradient-to-br from-primary/10 via-primary/10 to-transparent rounded-2xl border border-primary/20 overflow-hidden">
                        <div class="p-6 sm:p-8">
                            <div class="flex items-start gap-4 mb-6">
                                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                    <HistoryIcon class="w-7 h-7 text-primary" />
                                </div>
                                <div class="flex-1">
                                    <h2 class="text-2xl sm:text-3xl font-bold text-card-foreground mb-2">Transaction History</h2>
                                    <p class="text-muted-foreground text-sm sm:text-base">
                                        Monitor all cryptocurrency transactions on the platform including swaps, received payments, and sent transfers.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                    <p class="text-xs text-muted-foreground mb-1">Total Transactions</p>
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
                                    <p class="text-xs text-muted-foreground mb-1">Received</p>
                                    <p class="text-xl font-bold text-success">{{ statistics.received }}</p>
                                </div>
                                <div class="bg-card/70 backdrop-blur-sm rounded-xl p-4 border border-border">
                                    <p class="text-xs text-muted-foreground mb-1">Sent</p>
                                    <p class="text-xl font-bold text-accent">{{ statistics.sent }}</p>
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
                                    :href="tab.params ? route('admin.transaction.index', tab.params) : route('admin.transaction.index')"
                                    class="flex items-center gap-2 px-4 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 whitespace-nowrap cursor-pointer"
                                    :class="activeTab === tab.id
                                        ? 'bg-primary text-primary-foreground shadow-sm scale-105'
                                        : 'text-muted-foreground hover:bg-muted/70 hover:text-card-foreground'"
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
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted/70 hover:bg-muted/80 text-muted-foreground border border-border flex items-center gap-2 cursor-pointer transition-all"
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
                                        placeholder="Search by hash, token, address, or user..."
                                        class="w-full pl-10 pr-10 py-2.5 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
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
                                            class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm hover:bg-muted/50 flex items-center justify-between cursor-pointer transition-all"
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
                                            <option value="pending">Pending</option>
                                            <option value="processing">Processing</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                    </div>
                                </div>

                                <button
                                    v-if="hasActiveFilters"
                                    @click="clearFilters"
                                    class="w-full px-4 py-2 border border-border bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium flex items-center justify-center gap-2 cursor-pointer transition-all"
                                >
                                    <XIcon class="w-4 h-4" />
                                    Clear All Filters
                                </button>
                            </div>
                        </div>

                        <div ref="scrollContainer" @scroll="handleScroll" class="p-4 sm:p-6 max-h-[800px] overflow-y-auto custom-scrollbar">
                            <div v-if="allTransactions.length === 0" class="text-center py-12">
                                <div class="w-16 h-16 rounded-full bg-muted/70 mx-auto mb-4 flex items-center justify-center">
                                    <HistoryIcon class="w-full h-full text-muted-foreground" />
                                </div>
                                <h4 class="text-lg font-semibold text-card-foreground mb-2">No Transactions</h4>
                                <p class="text-sm text-muted-foreground">No transactions recorded on the platform yet.</p>
                            </div>

                            <div v-else-if="displayedTransactions.length === 0" class="text-center py-12">
                                <div class="w-16 h-16 rounded-full bg-muted/70 mx-auto mb-4 flex items-center justify-center">
                                    <SearchIcon class="w-8 h-8 text-muted-foreground" />
                                </div>
                                <h4 class="text-lg font-semibold text-card-foreground mb-2">No Transactions Found</h4>
                                <p class="text-sm text-muted-foreground mb-4">Try adjusting your search or filter criteria.</p>
                                <button @click="clearFilters" class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium cursor-pointer transition-all">
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

                                    <div v-if="tx.user_id" class="mb-4 p-3 bg-primary/5 rounded-lg border border-primary/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 flex-1 w-full">
                                            <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                                                <span class="text-sm font-bold text-primary">{{ (tx.user_name || 'U').charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-card-foreground">{{ tx.user_name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ tx.user_email }}</p>
                                            </div>
                                        </div>
                                        <TextLink :href="route('admin.users.show', tx.user_id)"
                                                  class="w-full sm:w-auto px-3 py-1.5 text-xs font-medium bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg cursor-pointer transition-all whitespace-nowrap sm:ml-2 text-center">
                                            View Profile
                                        </TextLink>
                                    </div>

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
                                            <p class="text-xs text-muted-foreground mb-1">Transaction Hash</p>
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.transaction_hash) }}</p>
                                                <button
                                                    @click="copyToClipboard(tx.transaction_hash!, tx.id)"
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                    <CheckIcon v-if="copiedHash === `${tx.id}`" class="w-3 h-3 text-primary" />
                                                    <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                                </button>
                                                <a
                                                    :href="getExplorerUrl(tx.transaction_hash!, tx.chain)"
                                                    target="_blank"
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer">
                                                    <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                                </a>
                                            </div>
                                        </div>
                                        <div v-if="(tx.status === 'pending' || tx.status === 'processing')" class="bg-warning/10 border border-warning/30 rounded-lg p-3 sm:col-span-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 flex-shrink-0">
                                                <AlertCircleIcon class="w-4 h-4 text-warning" />
                                                <p class="text-xs text-warning font-medium">Awaiting approval</p>
                                            </div>
                                            <div class="flex gap-2 w-full sm:w-auto">
                                                <button
                                                    @click="openActionModal(tx, 'approve')"
                                                    class="flex-1 px-3 py-1.5 bg-success hover:bg-success/90 text-white rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer transition-all"
                                                >
                                                    <ThumbsUpIcon class="w-3.5 h-3.5" />
                                                    Approve
                                                </button>
                                                <button
                                                    @click="openActionModal(tx, 'reject')"
                                                    class="flex-1 px-3 py-1.5 bg-destructive hover:bg-destructive/90 text-white rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer transition-all"
                                                >
                                                    <ThumbsDownIcon class="w-3.5 h-3.5" />
                                                    Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="tx.type === 'received'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Amount Received</p>
                                            <p class="text-sm font-semibold text-success">+{{ formatAmount(tx.amount) }} {{ tx.token_symbol }}</p>
                                        </div>
                                        <div v-if="tx.wallet_address" class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Wallet Address</p>
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.wallet_address) }}</p>
                                                <button
                                                    @click="copyToClipboard(tx.wallet_address!, tx.id + 1000)"
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer"
                                                >
                                                    <CheckIcon v-if="copiedHash === `${tx.id + 1000}`" class="w-3 h-3 text-primary" />
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
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer"
                                                >
                                                    <CheckIcon v-if="copiedHash === `${tx.id + 1000}`" class="w-3 h-3 text-primary" />
                                                    <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                                </button>
                                                <a
                                                    :href="getExplorerUrl(tx.transaction_hash!)"
                                                    target="_blank"
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer"
                                                >
                                                    <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                                </a>
                                            </div>
                                        </div>
                                        <div v-if="(tx.status === 'pending' || tx.status === 'processing')" class="bg-warning/10 border border-warning/30 rounded-lg p-3 sm:col-span-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 flex-shrink-0">
                                                <AlertCircleIcon class="w-4 h-4 text-warning" />
                                                <p class="text-xs text-warning font-medium">Awaiting approval</p>
                                            </div>
                                            <div class="flex gap-2 w-full sm:w-auto">
                                                <button
                                                    @click="openActionModal(tx, 'approve')"
                                                    class="flex-1 px-3 py-1.5 bg-success hover:bg-success/90 text-white rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer transition-all"
                                                >
                                                    <ThumbsUpIcon class="w-3.5 h-3.5" />
                                                    Approve
                                                </button>
                                                <button
                                                    @click="openActionModal(tx, 'reject')"
                                                    class="flex-1 px-3 py-1.5 bg-destructive hover:bg-destructive/90 text-white rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer transition-all"
                                                >
                                                    <ThumbsDownIcon class="w-3.5 h-3.5" />
                                                    Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="tx.type === 'sent'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Amount Sent</p>
                                            <p class="text-sm font-semibold text-accent">-{{ formatAmount(tx.amount) }} {{ tx.token_symbol }}</p>
                                        </div>
                                        <div v-if="tx.fee" class="bg-muted/50 rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Transaction Fee</p>
                                            <p class="text-sm font-semibold text-card-foreground">{{ formatAmount(tx.fee) }} {{ tx.token_symbol }}</p>
                                        </div>
                                        <div v-if="tx.wallet_address" class="bg-muted/50 rounded-lg p-3 sm:col-span-2">
                                            <p class="text-xs text-muted-foreground mb-1">Recipient Address</p>
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-mono text-card-foreground">{{ truncateHash(tx.wallet_address) }}</p>
                                                <button
                                                    @click="copyToClipboard(tx.wallet_address!, tx.id + 2000)"
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer"
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
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer"
                                                >
                                                    <CheckIcon v-if="copiedHash === `${tx.id + 3000}`" class="w-3 h-3 text-primary" />
                                                    <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                                </button>
                                                <a
                                                    :href="getExplorerUrl(tx.transaction_hash!)"
                                                    target="_blank"
                                                    class="p-1 hover:bg-muted/70 rounded cursor-pointer"
                                                >
                                                    <ExternalLinkIcon class="w-3 h-3 text-muted-foreground" />
                                                </a>
                                            </div>
                                        </div>
                                        <div v-if="(tx.status === 'pending' || tx.status === 'processing')" class="bg-warning/10 border border-warning/30 rounded-lg p-3 sm:col-span-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 flex-shrink-0">
                                                <AlertCircleIcon class="w-4 h-4 text-warning" />
                                                <p class="text-xs text-warning font-medium">Awaiting approval</p>
                                            </div>
                                            <div class="flex gap-2 w-full sm:w-auto">
                                                <button
                                                    @click="openActionModal(tx, 'approve')"
                                                    class="flex-1 px-3 py-1.5 bg-success hover:bg-success/90 text-white rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer transition-all"
                                                >
                                                    <ThumbsUpIcon class="w-3.5 h-3.5" />
                                                    Approve
                                                </button>
                                                <button
                                                    @click="openActionModal(tx, 'reject')"
                                                    class="flex-1 px-3 py-1.5 bg-destructive hover:bg-destructive/90 text-white rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer transition-all"
                                                >
                                                    <ThumbsDownIcon class="w-3.5 h-3.5" />
                                                    Reject
                                                </button>
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
            </div>
        </div>
    </AppLayout>

    <QuickActionModal
        :is-open="showActionModal"
        :title="actionType === 'approve' ? 'Review & Approve Transaction' : 'Reject Transaction'"
        :subtitle="actionType === 'approve' ? 'Please verify the transaction details and confirm approval' : 'Please confirm the rejection of this transaction'"
        @close="closeActionModal">

        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <div v-if="actionType === 'approve'" class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center">
                    <ThumbsUpIcon class="w-5 h-5 text-success" />
                </div>
                <div v-else class="w-10 h-10 rounded-full bg-destructive/10 flex items-center justify-center">
                    <ThumbsDownIcon class="w-5 h-5 text-destructive" />
                </div>
                <h3 class="text-lg font-semibold text-card-foreground">
                    {{ actionType === 'approve' ? 'Approve Transaction' : 'Reject Transaction' }}
                </h3>
            </div>

            <div v-if="selectedTransaction" class="p-4 bg-muted/30 rounded-lg border border-border/50 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-medium text-muted-foreground">Transaction Type</span>
                    <span class="px-2 py-1 text-xs font-semibold text-card-foreground capitalize rounded bg-primary/10 border border-primary/20">{{ selectedTransaction.type }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-medium text-muted-foreground">User</span>
                    <span class="text-sm font-semibold text-card-foreground">{{ selectedTransaction.user_name }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-medium text-muted-foreground">Transaction Date</span>
                    <span class="text-sm font-semibold text-card-foreground">{{ formatDate(selectedTransaction.created_at) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-medium text-muted-foreground">Transaction ID</span>
                    <span class="text-sm font-mono text-card-foreground">{{ truncateHash(selectedTransaction.transaction_hash) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-medium text-muted-foreground">Current Status</span>
                    <span class="px-2 py-0.5 text-xs rounded-full border capitalize bg-warning/10 text-warning border-warning/30">
                        {{ selectedTransaction.status }}
                    </span>
                </div>
            </div>

            <div v-if="actionType === 'approve' && selectedTransaction?.type === 'received'" class="space-y-3">
                <div class="p-3 bg-info/10 border border-info/30 rounded-lg">
                    <p class="text-xs text-info font-medium">⚠ Received Transaction</p>
                    <p class="text-xs text-info/80 mt-1">Enter the actual amount that was received from the user. This amount will be recorded and the transaction will be marked as completed.</p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Enter Amount Received *</label>
                    <div class="relative">
                        <input
                            v-model="approvalAmount"
                            type="text"
                            placeholder="0.00"
                            class="input-crypto w-full text-sm"
                            :class="{ 'border-destructive/50': validationErrors.amount }"
                        />
                        <span v-if="selectedTransaction?.token_symbol" class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">
                            {{ selectedTransaction.token_symbol }}
                        </span>
                    </div>
                    <InputError v-if="validationErrors.amount" :message="validationErrors.amount" />
                    <p class="text-xs text-muted-foreground mt-2">This is the amount the user actually transferred. Ensure accuracy before confirming.</p>
                </div>
            </div>

            <div v-else-if="actionType === 'approve'" class="p-3 bg-success/10 border border-success/30 rounded-lg">
                <p class="text-xs text-success font-medium">✓ Ready to Approve</p>
                <p class="text-xs text-success/80 mt-1">This transaction will be marked as approved and completed in the system. This action cannot be undone.</p>
            </div>

            <div v-if="actionType === 'reject'" class="p-3 bg-destructive/10 border border-destructive/30 rounded-lg">
                <p class="text-xs text-destructive font-medium">⚠ Rejection Warning</p>
                <p class="text-xs text-destructive/80 mt-1">Once rejected, this transaction will be marked as failed and cannot be recovered. The user will be notified of the rejection.</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button
                    @click="confirmAction"
                    :disabled="isProcessing || (actionType === 'approve' && selectedTransaction?.type === 'received' && !approvalAmount)"
                    :class="actionType === 'approve'
                        ? 'flex-1 px-4 py-2.5 bg-success hover:bg-success/90 text-white rounded-lg font-medium text-sm cursor-pointer transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2'
                        : 'flex-1 px-4 py-2.5 bg-destructive hover:bg-destructive/90 text-white rounded-lg font-medium text-sm cursor-pointer transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2'"
                >
                    <Loader2Icon v-if="isProcessing" class="w-4 h-4 animate-spin" />
                    {{ actionType === 'approve' ? 'Approve Transaction' : 'Reject Transaction' }}
                </button>
            </div>
        </div>
    </QuickActionModal>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
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
