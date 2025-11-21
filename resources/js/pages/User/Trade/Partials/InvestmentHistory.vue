<script setup lang="ts">
    import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        CheckCircleIcon,
        ClockIcon,
        DollarSignIcon,
        HistoryIcon,
        Search,
        TrendingUpIcon,
        XCircleIcon,
        XIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import TextLink from '@/components/TextLink.vue';
    import WalletBalanceCard from '@/components/WalletBalanceCard.vue';

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

    const urlParams = new URLSearchParams(window.location.search);
    const statusFilter = ref<string>(urlParams.get('status') || 'all');
    const searchQuery = ref(urlParams.get('search') || '');
    const sortBy = ref<string>(urlParams.get('sort') || 'newest');
    const dateFilter = ref<string>(urlParams.get('date') || 'all');

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

    const hasActiveFilters = computed(() => {
        return statusFilter.value !== 'all' ||
            searchQuery.value !== '' ||
            sortBy.value !== 'newest' ||
            dateFilter.value !== 'all';
    });

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

    const processedHistories = computed(() => {
        return props.investment_histories.data.map(history => {
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

    const applyFilters = () => {
        router.get(route('user.trade.investment.history'), {
            status: statusFilter.value,
            search: searchQuery.value,
            sort: sortBy.value,
            date: dateFilter.value
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['investment_histories'],
        });
    };

    const clearAllFilters = () => {
        statusFilter.value = 'all';
        searchQuery.value = '';
        sortBy.value = 'newest';
        dateFilter.value = 'all';

        router.get(route('user.trade.investment.history'), {}, {
            preserveState: true,
            preserveScroll: false,
            only: ['investment_histories'],
        });
    };

    const getStatusBadgeClass = (status: string) => {
        const classes = {
            running: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            completed: 'bg-green-100 text-green-800 border-green-200',
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
            only: ['investment_histories'],
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

    let searchTimeout: NodeJS.Timeout;
    watch(searchQuery, () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    });

    watch([statusFilter, sortBy, dateFilter], () => {
        applyFilters();
    });

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

            <WalletBalanceCard
                :current-balance="currentBalance"
                v-model:is-live-mode="isLiveMode"
                :live-balance="liveBalance"
                :demo-balance="demoBalance"
                warning-message="Switch to Live Mode to make investments."
                @deposit="handleFundingClick"
                @withdraw="handleWithdrawalClick"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mt-6">
                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <DollarSignIcon class="w-4 h-4 text-primary" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Invested</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground truncate">${{ props.stats.total_invested.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <TrendingUpIcon class="w-4 h-4 text-green-600" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Earned</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-green-600 truncate">${{ props.stats.total_earned.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircleIcon class="w-4 h-4 text-blue-600" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Active</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground">{{ props.stats.active_investments }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircleIcon class="w-4 h-4 text-cyan-600" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Completed</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground">{{ props.stats.completed_investments }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <TrendingUpIcon class="w-4 h-4" :class="props.stats.total_profit >= 0 ? 'text-green-600' : 'text-red-600'" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Net Profit</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold truncate" :class="props.stats.total_profit >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ props.stats.total_profit >= 0 ? '+' : '' }}${{ Math.abs(props.stats.total_profit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </p>
                </div>
            </div>

            <div class="mt-6 mb-8 sm:mb-0">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-xl sm:text-2xl font-bold text-card-foreground">
                        Investment History
                    </h2>

                    <TextLink :href="route('user.trade.investment')" class="inline-flex items-center gap-1.5 sm:gap-2 px-3 py-2 bg-background border border-border text-card-foreground rounded-lg text-xs sm:text-sm font-semibold hover:bg-muted transition-colors touch-manipulation">
                        <DollarSignIcon class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        <span>Plans</span>
                    </TextLink>
                </div>

                <div class="bg-card border border-border rounded-xl p-4 sm:p-6">
                    <div class="space-y-4 mb-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1 relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by plan..."
                                    class="w-full pl-10 pr-4 py-2.5 bg-background border border-border rounded-lg text-sm text-card-foreground placeholder:text-muted-foreground transition-all"
                                />
                            </div>

                            <button
                                v-if="hasActiveFilters"
                                @click="clearAllFilters"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-background border border-border text-card-foreground rounded-lg text-sm font-semibold hover:bg-destructive hover:text-destructive-foreground hover:border-destructive transition-colors whitespace-nowrap cursor-pointer touch-manipulation">
                                <XIcon class="w-4 h-4" />
                                Clear Filters
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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

                        <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 pt-3 border-t border-border/60">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Active:</span>

                            <span v-if="statusFilter !== 'all'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                {{ statusOptions.find(o => o.value === statusFilter)?.label }}
                            </span>

                            <span v-if="sortBy !== 'newest'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                {{ sortOptions.find(o => o.value === sortBy)?.label }}
                            </span>

                            <span v-if="dateFilter !== 'all'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                {{ dateOptions.find(o => o.value === dateFilter)?.label }}
                            </span>

                            <span v-if="searchQuery" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                "{{ searchQuery }}"
                            </span>
                        </div>
                    </div>

                    <div v-if="processedHistories.length > 0">
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50 border-b border-border">
                                    <tr>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Plan</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Amount</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Interest/Cycle</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Total Earned</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3 w-48">Progress</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Countdown</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Cycles</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Status</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="history in processedHistories" :key="history.id" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-4 text-sm font-bold text-card-foreground">{{ history.planName }}</td>
                                        <td class="px-4 py-4 text-sm font-semibold text-card-foreground">${{ history.amount.toLocaleString() }}</td>
                                        <td class="px-4 py-4 text-sm text-primary font-semibold">${{ history.interest.toLocaleString() }}</td>
                                        <td class="px-4 py-4 text-sm text-green-600 font-semibold">${{ history.totalInterestEarned.toLocaleString() }}</td>
                                        <td class="px-4 py-4">
                                            <div class="space-y-1.5">
                                                <div class="w-full bg-muted rounded-full h-1 overflow-hidden">
                                                    <div
                                                        class="h-full transition-all duration-500 rounded-full"
                                                        :class="history.progress.isExpired ? 'bg-green-500' : 'bg-yellow-500'"
                                                        :style="{ width: `${history.progress.percentage}%` }">
                                                    </div>
                                                </div>
                                                <p class="text-xs font-medium text-muted-foreground text-right">{{ history.progress.percentage.toFixed(1) }}%</p>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-1.5">
                                                <ClockIcon class="w-3.5 h-3.5 text-muted-foreground" />
                                                <span class="text-sm font-mono font-medium"
                                                  :class="history.progress.isExpired ? 'text-green-600' : 'text-card-foreground'">
                                                        {{ history.progress.countdown }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4">
                                            <span class="text-sm font-semibold text-card-foreground">
                                                {{ history.progress.currentCycle }} <span class="text-muted-foreground font-normal">/ {{ history.progress.totalCycles }}</span>
                                            </span>
                                        </td>

                                        <td class="px-4 py-4">
                                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold uppercase tracking-wide rounded-full border', getStatusBadgeClass(history.status)]">
                                                <component :is="getStatusIcon(history.status)" class="w-3 h-3" />
                                                {{ history.status }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                <span>{{ new Date(history.created_at).toLocaleDateString() }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="lg:hidden space-y-4">
                            <div v-for="history in processedHistories" :key="history.id" class="bg-background border border-border rounded-xl p-4 hover:border-primary/30 transition-colors">
                                <div class="flex items-start justify-between mb-3 pb-3 border-b border-border/50">
                                    <div>
                                        <h4 class="font-bold text-card-foreground">{{ history.planName }}</h4>
                                        <span class="text-xs text-muted-foreground">{{ new Date(history.created_at).toLocaleDateString() }}</span>
                                    </div>
                                    <span :class="['inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full border', getStatusBadgeClass(history.status)]">
                                        <component :is="getStatusIcon(history.status)" class="w-3 h-3" />
                                        {{ history.status }}
                                    </span>
                                </div>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm">
                                        <div>
                                            <p class="text-[10px] font-medium text-muted-foreground uppercase mb-0.5">Amount</p>
                                            <p class="font-bold text-card-foreground">${{ history.amount.toLocaleString() }}</p>
                                        </div>

                                        <div class="text-right">
                                            <p class="text-[10px] font-medium text-muted-foreground uppercase mb-0.5">Total Earned</p>
                                            <p class="font-bold text-green-600">+${{ history.totalInterestEarned.toLocaleString() }}</p>
                                        </div>

                                        <div>
                                            <p class="text-[10px] font-medium text-muted-foreground uppercase mb-0.5">Interest/Cycle</p>
                                            <p class="font-semibold text-primary">${{ history.interest.toLocaleString() }}</p>
                                        </div>

                                        <div class="text-right">
                                            <p class="text-[10px] font-medium text-muted-foreground uppercase mb-0.5">Cycles</p>
                                            <p class="font-semibold text-card-foreground">{{ history.progress.currentCycle }} / {{ history.progress.totalCycles }}</p>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 pt-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-medium text-muted-foreground">Progress</span>
                                            <span class="font-bold text-card-foreground">{{ history.progress.percentage.toFixed(1) }}%</span>
                                        </div>

                                        <div class="w-full bg-muted rounded-full h-1 overflow-hidden">
                                            <div
                                                class="h-full transition-all duration-500 rounded-full"
                                                :class="history.progress.isExpired ? 'bg-green-500' : 'bg-yellow-500'"
                                                :style="{ width: `${history.progress.percentage}%` }">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between p-2.5 bg-muted/30 rounded-lg border border-border/50">
                                        <div class="flex items-center gap-1.5">
                                            <ClockIcon class="w-3.5 h-3.5 text-muted-foreground" />
                                            <span class="text-xs font-medium text-muted-foreground">Time Remaining</span>
                                        </div>
                                        <span
                                            class="text-sm font-mono font-bold"
                                            :class="history.progress.isExpired ? 'text-green-600' : 'text-card-foreground'">
                                            {{ history.progress.countdown }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center text-center py-16 px-4">
                        <div class="w-16 h-16 rounded-full bg-muted/50 flex items-center justify-center mb-4">
                            <HistoryIcon class="w-8 h-8 text-muted-foreground/50" />
                        </div>

                        <h3 class="text-lg font-bold text-card-foreground mb-2">No Investment History</h3>

                        <p class="text-sm text-muted-foreground mb-6 max-w-xs mx-auto">
                            {{ hasActiveFilters
                            ? 'No investments match your current filters.'
                            : 'Start your first investment today and watch your portfolio grow!'
                            }}
                        </p>

                        <div class="flex flex-wrap justify-center gap-3">
                            <button
                                v-if="hasActiveFilters"
                                @click="clearAllFilters"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-background border border-border text-card-foreground rounded-xl font-semibold hover:bg-muted transition-colors cursor-pointer touch-manipulation cursor-pointer">
                                <XIcon class="w-4 h-4" />
                                Clear Filters
                            </button>

                            <TextLink :href="route('user.trade.investment')" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl font-semibold hover:bg-primary/90 transition-colors touch-manipulation">
                                <DollarSignIcon class="w-4 h-4" />
                                View Plans
                            </TextLink>
                        </div>
                    </div>

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
</style>
