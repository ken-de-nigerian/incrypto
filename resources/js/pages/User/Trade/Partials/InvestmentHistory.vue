<script setup lang="ts">
    import { computed, onMounted, onUnmounted, ref } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        CalendarIcon,
        CheckCircleIcon,
        ClockIcon,
        DollarSignIcon,
        HistoryIcon,
        Search,
        TrendingUpIcon,
        WalletIcon,
        XCircleIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TradingModeSwitcher from '@/components/TradingModeSwitcher.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import TextLink from '@/components/TextLink.vue';

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

    interface PlanTimeSetting {
        id: number;
        name: string;
        period: number;
    }

    interface Plan {
        id: number;
        plan_time_settings_id: number;
        name: string;
        minimum: number | string;
        maximum: number | string;
        interest: number | string;
        period: number;
        status: 'active' | 'inactive';
        capital_back_status: 'yes' | 'no';
        repeat_time: number | string;
        plan_time_settings?: PlanTimeSetting;
    }

    interface InvestmentHistory {
        id: number;
        user_id: number;
        plan_id: number;
        plan?: Plan;
        amount: number | string;
        interest: number | string;
        period: number;
        repeat_time: number;
        repeat_time_count: number;
        next_time: string;
        last_time: string;
        status: 'running' | 'completed' | 'cancelled';
        capital_back_status: string;
        created_at: string;
        updated_at?: string;
    }

    interface PaginatedData<T> {
        current_page: number;
        data: T[];
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: { url: string | null; label: string; active: boolean; }[];
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    }

    interface InvestmentProgress {
        countdown: string;
        percentage: number;
        isExpired: boolean;
        currentCycle: number;
        totalCycles: number;
    }

    interface InvestmentStats {
        total_invested: number;
        total_earned: number;
        active_investments: number;
        completed_investments: number;
        total_profit: number;
    }

    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        auth: {
            user: {
                profile: UserProfile;
            }
            notification_count: number;
        };
        investment_histories: PaginatedData<InvestmentHistory>;
        stats: InvestmentStats;
    }>();

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const statusFilter = ref<string>('all');
    const searchQuery = ref('');
    const sortBy = ref<string>('newest');
    const dateFilter = ref<string>('all');
    const currentTime = ref(Date.now());
    let intervalId: number | null = null;

    const page = usePage();
    const user = computed(() => page.props.auth?.user);
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
        { label: 'Investments', href: route('user.trade.investment') },
        { label: 'History' }
    ];

    const calculateInvestmentProgress = (history: any): InvestmentProgress => {
        const now = currentTime.value;
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

    const filteredHistories = computed(() => {
        let filtered = props.investment_histories.data;

        // Status filter
        if (statusFilter.value !== 'all') {
            filtered = filtered.filter(h => h.status === statusFilter.value);
        }

        // Search filter
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(h =>
                h.plan?.name?.toLowerCase().includes(query) ||
                h.id.toString().includes(query)
            );
        }

        // Date filter
        if (dateFilter.value !== 'all') {
            const now = new Date();
            filtered = filtered.filter(h => {
                const createdDate = new Date(h.created_at);
                if (dateFilter.value === 'today') {
                    return createdDate.toDateString() === now.toDateString();
                } else if (dateFilter.value === 'week') {
                    const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                    return createdDate >= weekAgo;
                } else if (dateFilter.value === 'month') {
                    const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                    return createdDate >= monthAgo;
                }
                return true;
            });
        }

        // Sort
        const sorted = [...filtered];
        if (sortBy.value === 'newest') {
            sorted.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
        } else if (sortBy.value === 'oldest') {
            sorted.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());
        } else if (sortBy.value === 'amount_high') {
            sorted.sort((a, b) => parseFloat(b.amount as string) - parseFloat(a.amount as string));
        } else if (sortBy.value === 'amount_low') {
            sorted.sort((a, b) => parseFloat(a.amount as string) - parseFloat(b.amount as string));
        } else if (sortBy.value === 'profit_high') {
            sorted.sort((a, b) => {
                const aEarned = parseFloat(a.interest as string) * (a.repeat_time_count || 0);
                const bEarned = parseFloat(b.interest as string) * (b.repeat_time_count || 0);
                return bEarned - aEarned;
            });
        }

        return sorted.map(history => {
            const amount = typeof history.amount === 'string' ? parseFloat(history.amount) : history.amount;
            const interest = typeof history.interest === 'string' ? parseFloat(history.interest) : history.interest;
            const progress = calculateInvestmentProgress(history);

            const interestPerCycle = interest;
            const totalInterestEarned = interestPerCycle * (history.repeat_time_count || 0);
            const totalProjectedInterest = interestPerCycle * history.repeat_time;

            return {
                ...history,
                amount,
                interest,
                totalInterestEarned,
                totalProjectedInterest,
                planName: history.plan?.name || `Plan #${history.plan_id}`,
                periodName: history.plan?.plan_time_settings?.name || `${history.period} hours`,
                progress
            };
        });
    });

    const handleFundingClick = () => {
        if (!isLiveMode.value) return;
        isFundingModalOpen.value = true;
    };

    const handleWithdrawalClick = () => {
        if (!isLiveMode.value) return;
        isWithdrawalModalOpen.value = true;
    };

    const getStatusBadgeClass = (status: string) => {
        const classes = {
            running: 'bg-green-100 text-green-800 border-green-200',
            completed: 'bg-blue-100 text-blue-800 border-blue-200',
            cancelled: 'bg-red-100 text-red-800 border-red-200'
        };
        return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const getStatusIcon = (status: string) => {
        const icons = {
            running: CheckCircleIcon,
            completed: CheckCircleIcon,
            cancelled: XCircleIcon
        };
        return icons[status as keyof typeof icons] || ClockIcon;
    };

    const goToHistoryPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['investment_histories', 'stats'],
        });
    };

    const statusOptions = [
        { value: 'all', label: 'All Status' },
        { value: 'running', label: 'Running' },
        { value: 'completed', label: 'Completed' },
        { value: 'cancelled', label: 'Cancelled' },
    ];

    const sortOptions = [
        { value: 'newest', label: 'Newest First' },
        { value: 'oldest', label: 'Oldest First' },
        { value: 'amount_high', label: 'Highest Amount' },
        { value: 'amount_low', label: 'Lowest Amount' },
        { value: 'profit_high', label: 'Highest Profit' },
    ];

    const dateOptions = [
        { value: 'all', label: 'All Time' },
        { value: 'today', label: 'Today' },
        { value: 'week', label: 'Last 7 Days' },
        { value: 'month', label: 'Last 30 Days' },
    ];

    onMounted(() => {
        intervalId = window.setInterval(() => {
            currentTime.value = Date.now();
        }, 1000);
    });

    onUnmounted(() => {
        if (intervalId) {
            clearInterval(intervalId);
        }
    });
</script>

<template>
    <AppLayout>
        <Head title="Investment History" />

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

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-6">
                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <DollarSignIcon class="w-4 h-4 text-primary" />
                        <p class="text-xs text-muted-foreground font-semibold">Total Invested</p>
                    </div>
                    <p class="text-2xl font-bold text-card-foreground">${{ props.stats.total_invested.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <TrendingUpIcon class="w-4 h-4 text-green-600" />
                        <p class="text-xs text-muted-foreground font-semibold">Total Earned</p>
                    </div>
                    <p class="text-2xl font-bold text-green-600">${{ props.stats.total_earned.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircleIcon class="w-4 h-4 text-blue-600" />
                        <p class="text-xs text-muted-foreground font-semibold">Active</p>
                    </div>
                    <p class="text-2xl font-bold text-card-foreground">{{ props.stats.active_investments }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircleIcon class="w-4 h-4 text-cyan-600" />
                        <p class="text-xs text-muted-foreground font-semibold">Completed</p>
                    </div>
                    <p class="text-2xl font-bold text-card-foreground">{{ props.stats.completed_investments }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <TrendingUpIcon class="w-4 h-4" :class="props.stats.total_profit >= 0 ? 'text-green-600' : 'text-red-600'" />
                        <p class="text-xs text-muted-foreground font-semibold">Net Profit</p>
                    </div>
                    <p class="text-2xl font-bold" :class="props.stats.total_profit >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ props.stats.total_profit >= 0 ? '+' : '' }}${{ Math.abs(props.stats.total_profit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </p>
                </div>
            </div>

            <!-- Investment History Section -->
            <div class="mt-6 margin-bottom">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-xl sm:text-2xl font-bold text-card-foreground">
                        Investment History
                    </h2>

                    <TextLink :href="route('user.trade.investment')" class="inline-flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 sm:px-4 sm:py-2 bg-background border border-border text-card-foreground rounded-lg text-xs sm:text-sm font-semibold hover:bg-muted transition-colors shrink-0">
                        <DollarSignIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        <span class="hidden xs:inline">Investment Plans</span>
                        <span class="xs:hidden">Investment Plans</span>
                    </TextLink>
                </div>

                <div class="bg-card border border-border rounded-xl p-6">
                    <!-- Filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by plan or ID..."
                                class="w-full pl-10 pr-4 py-2.5 bg-background border border-border rounded-lg text-sm text-card-foreground placeholder:text-muted-foreground"
                            />
                        </div>

                        <CustomSelectDropdown
                            v-model="statusFilter"
                            :options="statusOptions"
                            placeholder="Status"
                        />

                        <CustomSelectDropdown
                            v-model="sortBy"
                            :options="sortOptions"
                            placeholder="Sort by"
                        />

                        <CustomSelectDropdown
                            v-model="dateFilter"
                            :options="dateOptions"
                            placeholder="Date Range"
                        />
                    </div>

                    <!-- History Content -->
                    <div v-if="filteredHistories.length > 0">
                        <!-- Desktop Table -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50 border-b border-border">
                                    <tr>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Plan</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Amount</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Interest/Cycle</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Total Earned</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Progress</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Countdown</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Cycles</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Status</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="history in filteredHistories" :key="history.id" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-4 text-sm font-medium text-card-foreground">{{ history.planName }}</td>
                                        <td class="px-4 py-4 text-sm font-semibold text-card-foreground">${{ history.amount.toLocaleString() }}</td>
                                        <td class="px-4 py-4 text-sm text-primary font-semibold">${{ history.interest.toLocaleString() }}</td>
                                        <td class="px-4 py-4 text-sm text-green-600 font-semibold">${{ history.totalInterestEarned.toLocaleString() }}</td>
                                        <td class="px-4 py-4">
                                            <div class="space-y-1">
                                                <div class="w-full bg-muted rounded-full h-1.5 overflow-hidden">
                                                    <div
                                                        class="h-full transition-all duration-300 rounded-full"
                                                        :class="history.progress.isExpired ? 'bg-blue-500' : 'bg-green-500'"
                                                        :style="{ width: `${history.progress.percentage}%` }">
                                                    </div>
                                                </div>
                                                <p class="text-xs text-muted-foreground">{{ history.progress.percentage.toFixed(1) }}%</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2">
                                                <ClockIcon class="w-4 h-4 text-muted-foreground" />
                                                <span class="text-sm font-semibold"
                                                  :class="history.progress.isExpired ? 'text-blue-600' : 'text-card-foreground'">
                                                    {{ history.progress.countdown }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="text-sm font-semibold text-card-foreground">
                                                {{ history.progress.currentCycle }} / {{ history.progress.totalCycles }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span :class="['inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border', getStatusBadgeClass(history.status)]">
                                                <component :is="getStatusIcon(history.status)" class="w-3.5 h-3.5" />
                                                {{ history.status.charAt(0).toUpperCase() + history.status.slice(1) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                <CalendarIcon class="w-4 h-4" />
                                                <span>{{ new Date(history.created_at).toLocaleDateString() }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="lg:hidden space-y-4">
                            <div v-for="history in filteredHistories" :key="history.id" class="bg-background border border-border rounded-xl p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <h4 class="font-semibold text-card-foreground">{{ history.planName }}</h4>
                                    </div>
                                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full border', getStatusBadgeClass(history.status)]">
                                        <component :is="getStatusIcon(history.status)" class="w-3 h-3" />
                                        {{ history.status }}
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <p class="text-xs text-muted-foreground">Amount</p>
                                            <p class="font-semibold text-card-foreground">${{ history.amount.toLocaleString() }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-muted-foreground">Interest/Cycle</p>
                                            <p class="font-semibold text-primary">${{ history.interest.toLocaleString() }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-muted-foreground">Total Earned</p>
                                            <p class="font-semibold text-green-600">${{ history.totalInterestEarned.toLocaleString() }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-muted-foreground">Cycles</p>
                                            <p class="font-semibold text-card-foreground">{{ history.progress.currentCycle }} / {{ history.progress.totalCycles }}</p>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-muted-foreground">Progress</span>
                                            <span class="font-semibold text-card-foreground">{{ history.progress.percentage.toFixed(1) }}%</span>
                                        </div>

                                        <div class="w-full bg-muted rounded-full h-1.5 overflow-hidden">
                                            <div
                                                class="h-full transition-all duration-300 rounded-full"
                                                :class="history.progress.isExpired ? 'bg-blue-500' : 'bg-green-500'"
                                                :style="{ width: `${history.progress.percentage}%` }">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Countdown -->
                                    <div class="flex items-center justify-between p-2 bg-muted/50 rounded-lg">
                                        <div class="flex items-center gap-2">
                                            <ClockIcon class="w-4 h-4 text-muted-foreground" />
                                            <span class="text-xs text-muted-foreground">Time Remaining</span>
                                        </div>
                                        <span
                                            class="text-sm font-bold"
                                            :class="history.progress.isExpired ? 'text-blue-600' : 'text-card-foreground'">
                                            {{ history.progress.countdown }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between pt-2 border-t border-border text-xs">
                                        <div class="flex items-center gap-1 text-muted-foreground">
                                            <CalendarIcon class="w-3 h-3" />
                                            <span>{{ new Date(history.created_at).toLocaleDateString() }}</span>
                                        </div>
                                        <span class="text-muted-foreground">{{ history.periodName }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center text-center py-16">
                        <HistoryIcon class="w-16 h-16 text-muted-foreground/50 mb-4" />
                        <h3 class="text-lg font-semibold text-card-foreground mb-2">No Investment History</h3>
                        <p class="text-sm text-muted-foreground mb-6 max-w-md">
                            {{ statusFilter !== 'all' || searchQuery || dateFilter !== 'all'
                            ? 'No investments match your current filters. Try adjusting your search criteria.'
                            : 'Start your first investment today and watch your portfolio grow!'
                            }}
                        </p>
                        <TextLink :href="route('user.trade.investment')" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl font-semibold hover:bg-primary/90 transition-colors">
                            <DollarSignIcon class="w-5 h-5" />
                            Browse Investment Plans
                        </TextLink>
                    </div>

                    <!-- Pagination -->
                    <PaginationControls
                        v-if="investment_histories.last_page > 1"
                        :links="investment_histories.links"
                        :from="investment_histories.from"
                        :to="investment_histories.to"
                        :total="investment_histories.total"
                        @go-to-page="goToHistoryPage"
                        class="mt-6 pt-6 border-t border-border"
                    />
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
