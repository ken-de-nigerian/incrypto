<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        AlertTriangleIcon,
        DollarSignIcon,
        Search,
        UsersIcon,
        WalletIcon,
        CheckCircleIcon,
        XCircleIcon,
        ClockIcon,
        EyeIcon,
        TrendingUp,
        TrendingDown,
        BarChart3
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TradingModeSwitcher from '@/components/TradingModeSwitcher.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import CopyTradeTransactionsModal from '@/components/CopyTradeTransactionsModal.vue';

    interface Token {
        symbol: string;
        name: string;
        logo: string;
        decimals: number;
        price_change_24h: number;
    }

    interface UserProfile {
        live_trading_balance: number | string;
        demo_trading_balance: number | string;
        trading_status: 'live' | 'demo';
    }

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        profile: UserProfile;
    }

    interface MasterTrader {
        id: number;
        user_id: number;
        expertise: 'Newcomer' | 'Growing talent' | 'High achiever' | 'Expert' | 'Legend';
        risk_score: number | string;
        gain_percentage: number | string;
        copiers_count: number | string;
        commission_rate: number | string | null;
        total_profit: number | string;
        total_loss: number | string;
        is_active: boolean;
        bio: string | null;
        total_trades: number | string;
        win_rate: number | string;
        user: User | null;
    }

    interface CopyTradeTransaction {
        id: number;
        copy_trade_id: number;
        type: 'up' | 'down';
        amount: number;
        description: string | null;
        metadata: {
            asset_pair?: string;
            strategy?: string;
            entry_price?: number;
            exit_price?: number;
            position_size?: number;
            leverage?: number;
            holding_time_minutes?: number;
        } | null;
        created_at: string;
        updated_at: string;
    }

    interface CopyTrade {
        id: number;
        user_id: number;
        master_trader_id: number;
        current_profit: number | string;
        current_loss: number | string;
        total_commission_paid: number | string;
        status: 'active' | 'paused' | 'stopped';
        started_at: string;
        paused_at: string | null;
        stopped_at: string | null;
        master_trader: MasterTrader | null;
        transactions?: CopyTradeTransaction[];
    }

    interface PaginatedMasterTraders {
        current_page: number;
        data: MasterTrader[];
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    }

    interface PaginatedCopyTrades {
        current_page: number;
        data: CopyTrade[];
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    }

    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        masterTraders: PaginatedMasterTraders;
        copyTrades: PaginatedCopyTrades;
        auth: {
            user: User;
            notification_count: number;
        };
    }>();

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const isTransactionsModalOpen = ref(false);
    const selectedCopyTrade = ref<CopyTrade | null>(null);
    const sortFilter = ref<string>('risk');
    const expertiseFilter = ref<string>('all');
    const statusFilter = ref<string>('all');
    const searchQuery = ref('');
    const showFreeTrial = ref(false);

    const page = usePage();
    const user = computed(() => page.props.auth?.user as User);
    const userProfile = computed(() => user.value?.profile as UserProfile);
    const isLiveMode = ref(userProfile.value?.trading_status === 'live');

    const liveBalance = computed(() => {
        const bal = userProfile.value?.live_trading_balance || 0.00;
        return typeof bal === 'string' ? parseFloat(bal) : bal;
    });

    const demoBalance = computed(() => {
        const bal = userProfile.value?.demo_trading_balance || 0.00;
        return typeof bal === 'string' ? parseFloat(bal) : bal;
    });

    const currentBalance = computed(() => isLiveMode.value ? liveBalance.value : demoBalance.value);
    const notificationCount = computed(() => page.props.auth?.notification_count || 0);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const pricesMap = computed(() => props.prices);

    const holdings = computed(() => {
        return props.tokens.map(token => {
            const balance = props.userBalances[token.symbol] || 0;
            const price = pricesMap.value[token.symbol] || 1;
            const isFiat = token.symbol === 'USD' || token.name.includes('Tether');

            return {
                symbol: token.symbol,
                name: token.name,
                logo: token.logo,
                balance: balance,
                value: balance * price,
                assetType: isFiat ? 'fiat' : 'crypto'
            };
        });
    });

    const cryptoHoldings = computed(() => holdings.value.filter(h => h.assetType === 'crypto'));

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Trading', href: route('user.trade.index') },
        { label: 'Copy Trading' }
    ];

    const masterTraders = computed(() => props.masterTraders.data);
    const copyTrades = computed(() => props.copyTrades.data);

    const filteredMasterTraders = computed(() => {
        let filtered = [...masterTraders.value];

        if (expertiseFilter.value !== 'all') {
            filtered = filtered.filter(t => t.expertise === expertiseFilter.value);
        }

        if (showFreeTrial.value) {
            filtered = filtered.filter(t => !t.commission_rate || parseFloat(t.commission_rate) === 0);
        }

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(t =>
                t.user?.first_name?.toLowerCase().includes(query) ||
                t.user?.last_name?.toLowerCase().includes(query) ||
                `${t.user?.first_name} ${t.user?.last_name}`?.toLowerCase().includes(query)
            );
        }

        if (sortFilter.value === 'risk') {
            filtered.sort((a, b) => parseInt(a.risk_score as string) - parseInt(b.risk_score as string));
        } else if (sortFilter.value === 'gain') {
            filtered.sort((a, b) => parseFloat(b.gain_percentage as string) - parseFloat(a.gain_percentage as string));
        } else if (sortFilter.value === 'copiers') {
            filtered.sort((a, b) => parseInt(b.copiers_count as string) - parseInt(a.copiers_count as string));
        }

        return filtered;
    });

    const filteredCopyTrades = computed(() => {
        let filtered = [...copyTrades.value];

        if (statusFilter.value !== 'all') {
            filtered = filtered.filter(t => t.status === statusFilter.value);
        }

        return filtered;
    });

    const getTraderName = (trader: MasterTrader) => {
        if (!trader.user) return 'Unknown Trader';
        return `${trader.user.first_name} ${trader.user.last_name}`;
    };

    const getTraderInitials = (trader: MasterTrader) => {
        if (!trader.user) return '';
        const first = trader.user.first_name?.charAt(0) || '';
        const last = trader.user.last_name?.charAt(0) || '';
        return `${first}${last}`.toUpperCase();
    };

    const getExpertiseColor = (expertise: string) => {
        const colors = {
            'Newcomer': 'bg-gray-100 text-gray-800 border-gray-200',
            'Growing talent': 'bg-blue-100 text-blue-800 border-blue-200',
            'High achiever': 'bg-green-100 text-green-800 border-green-200',
            'Expert': 'bg-cyan-100 text-cyan-800 border-cyan-200',
            'Legend': 'bg-orange-100 text-orange-800 border-orange-200'
        };
        return colors[expertise as keyof typeof colors] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const getStatusBadgeClass = (status: string) => {
        const classes = {
            active: 'bg-green-100 text-green-800 border-green-200',
            paused: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            stopped: 'bg-red-100 text-red-800 border-red-200'
        };
        return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const getStatusIcon = (status: string) => {
        const icons = {
            active: CheckCircleIcon,
            paused: ClockIcon,
            stopped: XCircleIcon
        };
        return icons[status as keyof typeof icons] || ClockIcon;
    };

    const getNetProfit = (trade: CopyTrade) => {
        return parseFloat(trade.current_profit as string) - parseFloat(trade.current_loss as string);
    };

    const handleFundingClick = () => {
        if (!isLiveMode.value) return;
        isFundingModalOpen.value = true;
    };

    const handleWithdrawalClick = () => {
        if (!isLiveMode.value) return;
        isWithdrawalModalOpen.value = true;
    };

    const startCopying = (trader: MasterTrader) => {
        if (!isLiveMode.value) return;
        // TODO: Implement copy modal
        console.log('Start copying:', trader);
    };

    const handlePauseCopyTrade = async (copyTrade: CopyTrade) => {
        if (!confirm('Are you sure you want to pause this copy trade?')) return;

        try {
            await router.post(route('user.trade.copy.pause', copyTrade.id));
            await router.reload({ only: ['copyTrades'] });
            isTransactionsModalOpen.value = false;
        } catch (error) {
            console.error('Failed to pause copy trade:', error);
        }
    };

    const handleResumeCopyTrade = async (copyTrade: CopyTrade) => {
        if (!confirm('Are you sure you want to resume this copy trade?')) return;

        try {
            await router.post(route('user.trade.copy.resume', copyTrade.id));
            await router.reload({ only: ['copyTrades'] });
            isTransactionsModalOpen.value = false;
        } catch (error) {
            console.error('Failed to resume copy trade:', error);
        }
    };

    const handleStopCopyTrade = async (copyTrade: CopyTrade) => {
        if (!confirm('Are you sure you want to stop this copy trade? This action cannot be undone.')) return;

        try {
            await router.delete(route('user.trade.copy.stop', copyTrade.id));
            await router.reload({ only: ['copyTrades'] });
            isTransactionsModalOpen.value = false;
        } catch (error) {
            console.error('Failed to stop copy trade:', error);
        }
    };

    const viewTransactions = (trade: CopyTrade) => {
        selectedCopyTrade.value = trade;
        isTransactionsModalOpen.value = true;
    };

    const sortOptions = ref([
        { value: 'risk', label: 'Risk Score' },
        { value: 'gain', label: 'Top Gainers' },
        { value: 'copiers', label: 'Most Popular' }
    ]);

    const expertiseOptions = ref([
        { value: 'all', label: 'All Expertise' },
        { value: 'Legend', label: 'Legend' },
        { value: 'Expert', label: 'Expert' },
        { value: 'High achiever', label: 'High Achiever' },
        { value: 'Growing talent', label: 'Growing Talent' },
        { value: 'Newcomer', label: 'Newcomer' }
    ]);

    const statusOptions = ref([
        { value: 'all', label: 'All Status' },
        { value: 'active', label: 'Active' },
        { value: 'paused', label: 'Paused' },
        { value: 'stopped', label: 'Stopped' }
    ]);

    watch([isFundingModalOpen, isWithdrawalModalOpen, isTransactionsModalOpen], ([funding, withdrawal, transactions]) => {
        if (funding || withdrawal || transactions) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
</script>

<template>
    <Head title="Copy Trading" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="isNotificationsModalOpen = true"
            />

            <!-- Balance Card -->
            <div class="grid grid-cols-1 gap-6 mt-6">
                <div class="bg-card border border-border rounded-2xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <h2 class="text-xl font-semibold text-muted-foreground mb-1">Wallet Balance</h2>

                        <div class="flex items-end gap-3">
                            <span class="text-2xl sm:text-4xl font-extrabold text-card-foreground">
                                ${{ currentBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </span>
                        </div>

                        <div class="text-sm font-medium text-muted-foreground mt-1">
                            Mode: <span class="font-bold" :class="isLiveMode ? 'text-primary' : 'text-card-foreground'">{{ isLiveMode ? 'Live' : 'Demo' }}</span>
                        </div>

                        <div v-if="!isLiveMode" class="flex items-center gap-2 mt-2 text-xs border rounded-lg px-3 py-2">
                            <AlertTriangleIcon class="w-3 h-3" />
                            <span>Switch to Live Mode to start copy trading</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row md:items-end gap-4 md:gap-3 w-full md:w-auto">
                        <div class="flex gap-3 w-full sm:w-auto">
                            <button
                                v-if="isLiveMode"
                                @click="handleFundingClick"
                                class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-muted cursor-pointer">
                                <WalletIcon class="w-4 h-4" />
                                Deposit
                            </button>

                            <button
                                v-if="isLiveMode"
                                @click="handleWithdrawalClick"
                                class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-muted cursor-pointer">
                                <DollarSignIcon class="w-4 h-4" />
                                Withdraw
                            </button>
                        </div>

                        <TradingModeSwitcher
                            :is-live-mode="isLiveMode"
                            :live-balance="liveBalance"
                            :demo-balance="demoBalance"
                            @update:is-live-mode="isLiveMode = $event"
                        />
                    </div>
                </div>
            </div>

            <!-- Master Traders Section -->
            <div class="mt-6" id="traders-section">
                <h2 class="text-xl sm:text-2xl font-bold text-card-foreground mb-4">Master Traders Rating</h2>

                <!-- Filters -->
                <div class="bg-card border border-border rounded-2xl p-6 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <CustomSelectDropdown
                            v-model="sortFilter"
                            :options="sortOptions"
                            placeholder="Sort By"
                        />

                        <div class="relative">
                            <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                                <Search class="w-4 h-4 text-muted-foreground" />
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by name..."
                                class="w-full rounded-lg border border-border input-crypto text-sm font-medium pl-8 h-10 lg:h-auto"
                            />
                        </div>

                        <CustomSelectDropdown
                            v-model="expertiseFilter"
                            :options="expertiseOptions"
                            placeholder="Expertise Level"
                        />

                        <div class="flex items-center gap-2 px-4 py-2 bg-background border border-border rounded-lg">
                            <input
                                type="checkbox"
                                id="freeTrial"
                                v-model="showFreeTrial"
                                class="rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <label for="freeTrial" class="text-sm font-medium text-card-foreground cursor-pointer">
                                Free/Low Commission
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Master Traders Grid -->
                <div v-if="filteredMasterTraders.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="trader in filteredMasterTraders"
                        :key="trader.id"
                        class="bg-card border border-border rounded-xl p-5 hover:border-primary/50 transition-all duration-200">

                        <!-- Header -->
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center text-lg font-bold text-primary">
                                {{ getTraderInitials(trader) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-card-foreground truncate">{{ getTraderName(trader) }}</h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span :class="['text-xs px-2 py-0.5 rounded-full border font-semibold', getExpertiseColor(trader.expertise)]">
                                        {{ trader.expertise }}
                                    </span>
                                    <span class="text-xs px-2 py-0.5 rounded-full border bg-red-100 text-red-800 border-red-200 font-semibold">
                                        {{ trader.risk_score }} risk
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div>
                                <div class="flex gap-1 mb-1">
                                    <TrendingUp class="w-3 h-3" :class="parseFloat(trader.gain_percentage as string) >= 0 ? 'text-green-600' : 'text-red-600'" />
                                    <p class="text-xs text-muted-foreground">Gain</p>
                                </div>
                                <p class="text-sm font-bold" :class="parseFloat(trader.gain_percentage as string) >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ parseFloat(trader.gain_percentage as string) >= 0 ? '+' : '' }}{{ parseFloat(trader.gain_percentage as string).toFixed(2) }}%
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="flex items-center justify-center gap-1 mb-1">
                                    <UsersIcon class="w-3 h-3 text-card-foreground" />
                                    <p class="text-xs text-muted-foreground">Copiers</p>
                                </div>
                                <p class="text-sm font-bold text-card-foreground">{{ trader.copiers_count }}</p>
                            </div>

                            <div class="text-center">
                                <div class="flex items-center justify-center gap-1 mb-1">
                                    <DollarSignIcon class="w-3 h-3 text-card-foreground" />
                                    <p class="text-xs text-muted-foreground">Commission</p>
                                </div>
                                <p class="text-sm font-bold" :class="!trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'text-green-600' : 'text-card-foreground'">
                                    {{ !trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'FREE' : `${parseFloat(trader.commission_rate as string).toFixed(0)}%` }}
                                </p>
                            </div>
                        </div>

                        <!-- Profit/Loss Bar -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-muted-foreground flex items-center gap-1">
                                    <BarChart3 class="w-3 h-3" />
                                    Profit/Loss
                                </span>
                                <span class="font-semibold text-card-foreground">
                                    {{ Math.round((parseFloat(trader.total_profit) / (parseFloat(trader.total_profit) + parseFloat(trader.total_loss)) * 100) || 0 )}}%
                                </span>
                            </div>

                            <div class="w-full bg-muted rounded-full h-1 overflow-hidden relative">
                                <div
                                    class="h-full bg-green-500 rounded-l-full transition-all duration-300 absolute left-0"
                                    :style="{ width: `${Math.round((parseFloat(trader.total_profit) / (parseFloat(trader.total_profit) + parseFloat(trader.total_loss)) * 100) || 0 )}%` }">
                                </div>
                                <div
                                    class="h-full bg-red-500 rounded-r-full transition-all duration-300 absolute right-0"
                                    :style="{ width: `${Math.round((parseFloat(trader.total_loss) / (parseFloat(trader.total_profit) + parseFloat(trader.total_loss)) * 100) || 0 )}%` }">
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs mt-1">
                                <span class="text-green-600">${{ parseFloat(trader.total_profit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                <span class="text-red-600">${{ parseFloat(trader.total_loss).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button
                            @click="startCopying(trader)"
                            :disabled="!isLiveMode"
                            :class="[
                                'w-full flex items-center justify-center gap-2 py-2.5 rounded-lg font-semibold',
                                isLiveMode
                                    ? 'bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer'
                                    : 'bg-muted text-muted-foreground cursor-not-allowed'
                            ]">
                            <UsersIcon class="w-4 h-4" />
                            {{ !trader.commission_rate || trader.commission_rate === 0 ? 'Start Free Trial' : 'Copy Trader' }}
                        </button>
                    </div>
                </div>

                <div v-else class="bg-card border border-border rounded-xl p-12 text-center">
                    <UsersIcon class="w-16 h-16 text-muted-foreground/50 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-card-foreground mb-2">No Master Traders Found</h3>
                    <p class="text-sm text-muted-foreground">Try adjusting your filters to see more traders.</p>
                </div>
            </div>

            <!-- My Copy Trades Section -->
            <div class="mt-8 margin-bottom" id="copies-section">
                <h2 class="text-xl sm:text-2xl font-bold text-card-foreground mb-4">My Copy Trades</h2>

                <div class="bg-card border border-border rounded-2xl p-6">
                    <!-- Filter -->
                    <div class="mb-6">
                        <CustomSelectDropdown
                            v-model="statusFilter"
                            :options="statusOptions"
                            placeholder="Filter Status"
                            class="max-w-xs"
                        />
                    </div>

                    <!-- Copy Trades Content -->
                    <div v-if="filteredCopyTrades.length > 0">
                        <!-- Desktop Table -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50">
                                <tr>
                                    <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Master Trader</th>
                                    <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Profit/Loss</th>
                                    <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Commission Paid</th>
                                    <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Start Date</th>
                                    <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Status</th>
                                    <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="trade in filteredCopyTrades" :key="trade.id" class="border-t border-border hover:bg-muted/30">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-secondary flex items-center justify-center text-sm font-bold text-primary">
                                                {{ getTraderInitials(trade.master_trader!) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-card-foreground">{{ getTraderName(trade.master_trader!) }}</p>
                                                <p class="text-xs text-muted-foreground">{{ trade.master_trader?.expertise || 'Unknown' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-green-600">+${{ parseFloat(trade.current_profit as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                            <span class="text-sm font-semibold text-red-600">-${{ parseFloat(trade.current_loss as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                            <span class="text-xs font-bold mt-1" :class="getNetProfit(trade) >= 0 ? 'text-green-600' : 'text-red-600'">
                                                    Net: {{ getNetProfit(trade) >= 0 ? '+' : '' }}${{ Math.abs(getNetProfit(trade)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                                </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-card-foreground">${{ parseFloat(trade.total_commission_paid as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                                    <td class="px-4 py-3 text-sm text-card-foreground">{{ new Date(trade.started_at).toLocaleDateString() }}</td>
                                    <td class="px-4 py-3">
                                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border', getStatusBadgeClass(trade.status)]">
                                                <component :is="getStatusIcon(trade.status)" class="w-3 h-3" />
                                                {{ trade.status.charAt(0).toUpperCase() + trade.status.slice(1) }}
                                            </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            @click="viewTransactions(trade)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-primary-foreground rounded-lg text-xs font-semibold hover:bg-primary/90 cursor-pointer">
                                            <EyeIcon class="w-3.5 h-3.5" />
                                            View Trades
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="lg:hidden space-y-3">
                            <div v-for="trade in filteredCopyTrades" :key="trade.id" class="bg-background border border-border rounded-lg p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center text-sm font-bold text-primary">
                                            {{ getTraderInitials(trade.master_trader!) }}
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-card-foreground">{{ getTraderName(trade.master_trader!) }}</h4>
                                            <p class="text-xs text-muted-foreground">{{ trade.master_trader?.expertise || 'Unknown' }}</p>
                                        </div>
                                    </div>
                                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full border', getStatusBadgeClass(trade.status)]">
                                        <component :is="getStatusIcon(trade.status)" class="w-3 h-3" />
                                        {{ trade.status.charAt(0).toUpperCase() + trade.status.slice(1) }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <div class="flex items-center gap-1 mb-1">
                                            <TrendingUp class="w-3 h-3 text-green-600" />
                                            <p class="text-xs text-muted-foreground">Profit</p>
                                        </div>
                                        <p class="font-semibold text-green-600">+${{ parseFloat(trade.current_profit as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1 mb-1">
                                            <TrendingDown class="w-3 h-3 text-red-600" />
                                            <p class="text-xs text-muted-foreground">Loss</p>
                                        </div>
                                        <p class="font-semibold text-red-600">-${{ parseFloat(trade.current_loss as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1 mb-1">
                                            <BarChart3 class="w-3 h-3" :class="getNetProfit(trade) >= 0 ? 'text-green-600' : 'text-red-600'" />
                                            <p class="text-xs text-muted-foreground">Net P/L</p>
                                        </div>
                                        <p class="font-semibold" :class="getNetProfit(trade) >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ getNetProfit(trade) >= 0 ? '+' : '' }}${{ Math.abs(getNetProfit(trade)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                        </p>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1 mb-1">
                                            <DollarSignIcon class="w-3 h-3 text-card-foreground" />
                                            <p class="text-xs text-muted-foreground">Commission</p>
                                        </div>
                                        <p class="font-semibold text-card-foreground">${{ parseFloat(trade.total_commission_paid as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-border">
                                    <button
                                        @click="viewTransactions(trade)"
                                        class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:bg-primary/90 cursor-pointer">
                                        <EyeIcon class="w-4 h-4" />
                                        View Trades
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center text-center py-12">
                        <UsersIcon class="w-16 h-16 text-muted-foreground/50 mb-4" />
                        <h3 class="text-lg font-semibold text-card-foreground mb-2">No Copy Trades</h3>
                        <p class="text-sm text-muted-foreground mb-4">
                            {{ statusFilter === 'all' ? 'Start copying a master trader to see your trades here!' : `No ${statusFilter} copy trades found.` }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <FundingModal
            :is-open="isFundingModalOpen"
            :live-balance="liveBalance"
            :crypto-holdings="cryptoHoldings"
            :prices-map="pricesMap"
            @close="isFundingModalOpen = false"
        />

        <WithdrawalModal
            :is-open="isWithdrawalModalOpen"
            :live-balance="liveBalance"
            :crypto-holdings="cryptoHoldings"
            :prices-map="pricesMap"
            @close="isWithdrawalModalOpen = false"
        />

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />

        <CopyTradeTransactionsModal
            :is-open="isTransactionsModalOpen"
            :copy-trade="selectedCopyTrade"
            @close="isTransactionsModalOpen = false"
            @pause="handlePauseCopyTrade"
            @resume="handleResumeCopyTrade"
            @stop="handleStopCopyTrade"
        />
    </AppLayout>
</template>

<style scoped>
    button:focus-visible,
    input:focus-visible,
    select:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
