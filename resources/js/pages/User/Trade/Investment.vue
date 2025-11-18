<script setup lang="ts">
    import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        AlertTriangleIcon,
        CalculatorIcon,
        CalendarIcon,
        CheckCircleIcon,
        ClockIcon,
        DollarSignIcon,
        HistoryIcon,
        PercentIcon,
        PiggyBankIcon,
        Search,
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
    import InvestmentModal from '@/components/InvestmentModal.vue';
    import CalculatorModal from '@/components/CalculatorModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';

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
        created_at?: string;
        updated_at?: string;
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
        plans: PaginatedData<Plan>;
        investment_histories: PaginatedData<InvestmentHistory>;
    }>();

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const isInvestmentModalOpen = ref(false);
    const isCalculatorModalOpen = ref(false);
    const selectedPlan = ref<Plan | null>(null);
    const calculatorPlan = ref<Plan | null>(null);
    const statusFilter = ref<string>('all');
    const searchQuery = ref('');
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
        { label: 'Investments' }
    ];

    const formattedPlans = computed(() => {
        return props.plans.data.map(plan => {
            const minimum = typeof plan.minimum === 'string' ? parseFloat(plan.minimum) : plan.minimum;
            const maximum = typeof plan.maximum === 'string' ? parseFloat(plan.maximum) : plan.maximum;
            const interest = typeof plan.interest === 'string' ? parseFloat(plan.interest) : plan.interest;
            const capitalBack = plan.capital_back_status === 'yes';

            return {
                ...plan,
                minimum,
                maximum,
                interest,
                capitalBack,
                periodName: plan.plan_time_settings?.name || `${plan.period} Days`
            };
        });
    });

    const calculateInvestmentProgress = (history: any): InvestmentProgress => {
        const now = currentTime.value;
        const nextPayoutTime = new Date(history.next_time).getTime();
        const createdTime = new Date(history.created_at).getTime();

        const totalDuration = history.period * 60 * 60 * 1000;
        const elapsed = now - createdTime;
        const remaining = nextPayoutTime - now;

        if (remaining <= 0 || history.status !== 'running') {
            return {
                countdown: 'Matured',
                percentage: 100,
                isExpired: true
            };
        }

        const percentage = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));

        // Calculate countdown
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
            isExpired: false
        };
    };

    const filteredHistories = computed(() => {
        let filtered = props.investment_histories.data;

        if (statusFilter.value !== 'all') {
            filtered = filtered.filter(h => h.status === statusFilter.value);
        }

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(h =>
                h.plan?.name?.toLowerCase().includes(query) ||
                h.id.toString().includes(query)
            );
        }

        return filtered.map(history => {
            const amount = typeof history.amount === 'string' ? parseFloat(history.amount) : history.amount;
            const interest = typeof history.interest === 'string' ? parseFloat(history.interest) : history.interest;
            const progress = calculateInvestmentProgress(history);

            return {
                ...history,
                amount,
                interest,
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

    const openInvestmentModal = (plan: Plan) => {
        if (!isLiveMode.value) return;
        selectedPlan.value = plan;
        isInvestmentModalOpen.value = true;
    };

    const closeInvestmentModal = () => {
        isInvestmentModalOpen.value = false;
        selectedPlan.value = null;
    };

    const openCalculatorModal = (plan: Plan) => {
        calculatorPlan.value = plan;
        isCalculatorModalOpen.value = true;
    };

    const closeCalculatorModal = () => {
        isCalculatorModalOpen.value = false;
        calculatorPlan.value = null;
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

    const goToPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const actionTypeOptions = ref([
        { value: 'all', label: 'All Status' },
        { value: 'running', label: 'Running' },
        { value: 'completed', label: 'Completed'},
        { value: 'cancelled', label: 'Cancelled' },
    ]);

    onMounted(() => {
        // Update countdown every second
        intervalId = window.setInterval(() => {
            currentTime.value = Date.now();
        }, 1000);
    });

    onUnmounted(() => {
        if (intervalId) {
            clearInterval(intervalId);
        }
    });

    watch([isFundingModalOpen, isWithdrawalModalOpen, isInvestmentModalOpen, isCalculatorModalOpen], ([funding, withdrawal, investment, calculator]) => {
        document.body.style.overflow = funding || withdrawal || investment || calculator ? 'hidden' : '';
    });
</script>

<template>
    <Head title="Investments & Staking" />

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
                        <h2 class="text-xl font-semibold text-muted-foreground mb-1">Balance</h2>

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
                            <span>Switch to Live Mode to make investments</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row md:items-end gap-4 md:gap-3 w-full md:w-auto">
                        <div class="flex gap-3 w-full sm:w-auto">
                            <button
                                v-if="isLiveMode"
                                @click="handleFundingClick"
                                class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold transition-colors hover:bg-muted cursor-pointer">
                                <WalletIcon class="w-4 h-4" />
                                Deposit
                            </button>

                            <button
                                v-if="isLiveMode"
                                @click="handleWithdrawalClick"
                                class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold transition-colors hover:bg-muted cursor-pointer">
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

            <!-- Investment Plans Section -->
            <div class="mt-6" id="plans-section">
                <h2 class="text-xl sm:text-2xl font-bold text-card-foreground mb-4 flex items-center gap-2">
                    Investment Plans
                </h2>

                <div v-if="formattedPlans.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="plan in formattedPlans"
                        :key="plan.id"
                        class="bg-card border border-border rounded-xl p-5 hover:border-primary/50 transition-all duration-200 group">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-lg font-bold text-card-foreground">{{ plan.name }}</h3>
                            <span
                                class="px-3 py-1 text-xs font-semibold rounded-full border"
                                :class="plan.capitalBack ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'">
                                {{ plan.capitalBack ? 'Capital Back' : 'No Capital Back' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="flex items-center gap-2">
                                <DollarSignIcon class="w-4 h-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Min - Max</p>
                                    <p class="text-sm font-semibold text-card-foreground">
                                        ${{ plan.minimum.toLocaleString() }} - ${{ plan.maximum.toLocaleString() }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <PercentIcon class="w-4 h-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Interest</p>
                                    <p class="text-sm font-semibold text-primary">{{ plan.interest }}% ROI</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <CalendarIcon class="w-4 h-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Period</p>
                                    <p class="text-sm font-semibold text-card-foreground">{{ plan.periodName }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <ClockIcon class="w-4 h-4 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Repeat Time</p>
                                    <p class="text-sm font-semibold text-card-foreground">{{ plan.repeat_time }}x</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="openInvestmentModal(plan)"
                                :disabled="!isLiveMode"
                                :class="[
                                    'flex items-center justify-center gap-2 py-2.5 rounded-lg font-semibold transition-colors cursor-pointer',
                                    isLiveMode
                                        ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                        : 'bg-muted text-muted-foreground cursor-not-allowed'
                                ]">
                                <PiggyBankIcon class="w-4 h-4" />
                                Invest
                            </button>

                            <button
                                @click="openCalculatorModal(plan)"
                                class="flex items-center justify-center gap-2 py-2.5 bg-background border border-border text-card-foreground rounded-lg font-semibold hover:bg-muted transition-colors cursor-pointer">
                                <CalculatorIcon class="w-4 h-4" />
                                Calculate
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center text-center py-12 bg-card border border-border rounded-2xl">
                    <PiggyBankIcon class="w-16 h-16 text-muted-foreground/50 mb-4" />
                    <h3 class="text-lg font-semibold text-card-foreground mb-2">No Plans Available</h3>
                    <p class="text-sm text-muted-foreground">Check back later for investment opportunities.</p>
                </div>

                <PaginationControls
                    v-if="plans.last_page > 1"
                    :links="plans.links"
                    :from="plans.from"
                    :to="plans.to"
                    :total="plans.total"
                    @go-to-page="goToPage"
                    class="mt-6"
                />
            </div>

            <!-- Investment History Section -->
            <div class="mt-8 margin-bottom" id="history-section">
                <h2 class="text-xl sm:text-2xl font-bold text-card-foreground mb-4 flex items-center gap-2">
                    Investment History
                </h2>

                <div class="bg-card border border-border rounded-2xl p-6">
                    <!-- Filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                                <Search class="w-4 h-4 text-muted-foreground" />
                            </div>
                            <input v-model="searchQuery" type="text" placeholder="Search by plan or ID..." class="w-full rounded-lg border border-border input-crypto text-sm font-medium pl-8 h-10 lg:h-auto" />
                        </div>

                        <CustomSelectDropdown
                            v-model="statusFilter"
                            :options="actionTypeOptions"
                            placeholder="Select Status"
                        />
                    </div>

                    <!-- History Content -->
                    <div v-if="filteredHistories.length > 0">
                        <!-- Desktop Table -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Plan</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Amount</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Interest</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Progress</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Countdown</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="history in filteredHistories" :key="history.id" class="border-t border-border hover:bg-muted/30 transition-colors">
                                        <td class="px-4 py-3 text-sm font-medium text-card-foreground">{{ history.planName }}</td>
                                        <td class="px-4 py-3 text-sm text-card-foreground">${{ history.amount.toLocaleString() }}</td>
                                        <td class="px-4 py-3 text-sm text-primary font-semibold">${{ history.interest.toLocaleString() }}</td>
                                        <td class="px-4 py-3">
                                            <div class="space-y-1">
                                                <div class="w-full bg-muted rounded-full h-1 overflow-hidden">
                                                    <div
                                                        class="h-full transition-all duration-300 rounded-full"
                                                        :class="history.progress.isExpired ? 'bg-blue-500' : 'bg-green-500'"
                                                        :style="{ width: `${history.progress.percentage}%` }">
                                                    </div>
                                                </div>
                                                <p class="text-xs text-muted-foreground">{{ history.progress.percentage.toFixed(1) }}%</p>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <ClockIcon class="w-4 h-4 text-muted-foreground" />
                                                <span class="text-sm font-semibold"
                                                      :class="history.progress.isExpired ? 'text-blue-600' : 'text-card-foreground'">
                                                    {{ history.progress.countdown }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border', getStatusBadgeClass(history.status)]">
                                                <component :is="getStatusIcon(history.status)" class="w-3 h-3" />
                                                {{ history.status.charAt(0).toUpperCase() + history.status.slice(1) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="lg:hidden space-y-3">
                            <div v-for="history in filteredHistories" :key="history.id" class="bg-background border border-border rounded-lg p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <h4 class="font-semibold text-card-foreground">{{ history.planName }}</h4>
                                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full border', getStatusBadgeClass(history.status)]">
                                        <component :is="getStatusIcon(history.status)" class="w-3 h-3" />
                                        {{ history.status.charAt(0).toUpperCase() + history.status.slice(1) }}
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <p class="text-xs text-muted-foreground">Amount</p>
                                            <p class="font-semibold text-card-foreground">${{ history.amount.toLocaleString() }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-muted-foreground">Interest</p>
                                            <p class="font-semibold text-primary">${{ history.interest.toLocaleString() }}</p>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-muted-foreground">Progress</span>
                                            <span class="font-semibold text-card-foreground">{{ history.progress.percentage.toFixed(1) }}%</span>
                                        </div>

                                        <div class="w-full bg-muted rounded-full h-1 overflow-hidden">
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

                                    <div class="grid grid-cols-2 gap-2 text-sm pt-2 border-t border-border">
                                        <div>
                                            <p class="text-xs text-muted-foreground">Period</p>
                                            <p class="text-xs text-card-foreground">{{ history.periodName }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Repeat</p>
                                            <p class="text-xs text-card-foreground">{{ history.repeat_time }}x</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center text-center py-12">
                        <HistoryIcon class="w-16 h-16 text-muted-foreground/50 mb-4" />
                        <h3 class="text-lg font-semibold text-card-foreground mb-2">No Investments Yet</h3>
                        <p class="text-sm text-muted-foreground mb-4">Start your first investment today and watch your money grow!</p>
                    </div>

                    <!-- History Pagination -->
                    <PaginationControls
                        v-if="investment_histories.last_page > 1"
                        :links="investment_histories.links"
                        :from="investment_histories.from"
                        :to="investment_histories.to"
                        :total="investment_histories.total"
                        @go-to-page="goToPage"
                        class="md:mt-6 md:pt-6 md:border-t md:border-border"
                    />
                </div>
            </div>
        </div>

        <!-- Modals -->
        <InvestmentModal
            :is-open="isInvestmentModalOpen"
            :plan="selectedPlan"
            :live-balance="liveBalance"
            :is-live-mode="isLiveMode"
            @close="closeInvestmentModal"
        />

        <CalculatorModal
            :is-open="isCalculatorModalOpen"
            :plan="calculatorPlan"
            @close="closeCalculatorModal"
        />

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
