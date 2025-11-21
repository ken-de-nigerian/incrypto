<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        CalculatorIcon,
        CalendarIcon,
        ClockIcon,
        DollarSignIcon,
        HistoryIcon,
        PercentIcon,
        PiggyBankIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
    import InvestmentModal from '@/components/InvestmentModal.vue';
    import CalculatorModal from '@/components/CalculatorModal.vue';
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
        created_at?: string;
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
    }>();

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const isInvestmentModalOpen = ref(false);
    const isCalculatorModalOpen = ref(false);
    const selectedPlan = ref<Plan | null>(null);
    const calculatorPlan = ref<Plan | null>(null);

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
            const repeatTime = typeof plan.repeat_time === 'string' ? parseInt(plan.repeat_time) : plan.repeat_time;
            const capitalBack = plan.capital_back_status === 'yes';

            return {
                ...plan,
                minimum,
                maximum,
                interest,
                repeatTime,
                capitalBack,
                periodName: plan.plan_time_settings?.name || `${plan.period} Days`
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

    const goToPlansPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['plans'],
        });
    };

    watch([isFundingModalOpen, isWithdrawalModalOpen, isInvestmentModalOpen, isCalculatorModalOpen], ([funding, withdrawal, investment, calculator]) => {
        document.body.style.overflow = (funding || withdrawal || investment || calculator) ? 'hidden' : '';
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

            <WalletBalanceCard
                :current-balance="currentBalance"
                v-model:is-live-mode="isLiveMode"
                :live-balance="liveBalance"
                :demo-balance="demoBalance"
                warning-message="Switch to Live Mode to make investments."
                @deposit="handleFundingClick"
                @withdraw="handleWithdrawalClick"
            />

            <div class="mt-6 mb-8 sm:mb-0" id="plans-section">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                    <h2 class="text-xl sm:text-2xl font-bold text-card-foreground">
                        Investment Plans
                    </h2>

                    <TextLink :href="route('user.trade.investment.history')" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-3 sm:py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-muted transition-colors touch-manipulation">
                        <HistoryIcon class="w-4 h-4" />
                        <span>My Investments</span>
                    </TextLink>
                </div>

                <div v-if="formattedPlans.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <div
                        v-for="plan in formattedPlans"
                        :key="plan.id"
                        class="bg-card border border-border rounded-2xl p-5 hover:border-primary/50 transition-all duration-200 group flex flex-col">

                        <div class="flex items-start justify-between mb-4 pb-4 border-b border-border/50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                    <PiggyBankIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-card-foreground">{{ plan.name }}</h3>
                            </div>
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full border"
                                :class="plan.capitalBack ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'">
                                {{ plan.capitalBack ? 'Capital Back' : 'No Return' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-x-4 gap-y-5 mb-6">
                            <div>
                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 mb-1">
                                    <DollarSignIcon class="w-3.5 h-3.5" />
                                    Range (Min - Max)
                                </p>
                                <p class="text-sm font-bold text-card-foreground truncate">
                                    ${{ plan.minimum.toLocaleString() }} - ${{ plan.maximum.toLocaleString() }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 mb-1">
                                    <PercentIcon class="w-3.5 h-3.5" />
                                    Interest ROI
                                </p>
                                <p class="text-sm font-extrabold text-green-600">{{ plan.interest }}%</p>
                            </div>

                            <div>
                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 mb-1">
                                    <CalendarIcon class="w-3.5 h-3.5" />
                                    Duration
                                </p>
                                <p class="text-sm font-semibold text-card-foreground">{{ plan.periodName }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 mb-1">
                                    <ClockIcon class="w-3.5 h-3.5" />
                                    Repeat Time
                                </p>
                                <p class="text-sm font-semibold text-card-foreground">{{ plan.repeatTime }}x</p>
                            </div>
                        </div>

                        <div class="mt-auto grid grid-cols-2 gap-3">
                            <button
                                @click="openInvestmentModal(plan)"
                                :disabled="!isLiveMode"
                                :class="[
                                    'flex items-center justify-center gap-2 py-3 rounded-xl font-semibold transition-all active:scale-[0.98] cursor-pointer touch-manipulation',
                                    isLiveMode
                                        ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                        : 'bg-muted text-muted-foreground cursor-not-allowed border border-border'
                                ]">
                                <PiggyBankIcon class="w-4 h-4" />
                                Invest
                            </button>

                            <button
                                @click="openCalculatorModal(plan)"
                                class="flex items-center justify-center gap-2 py-3 bg-background border border-border text-card-foreground rounded-xl font-semibold hover:bg-muted transition-all active:scale-[0.98] cursor-pointer touch-manipulation">
                                <CalculatorIcon class="w-4 h-4" />
                                Calculate
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center text-center py-16 bg-card border border-border rounded-2xl">
                    <PiggyBankIcon class="w-16 h-16 text-muted-foreground/30 mb-4" />
                    <h3 class="text-lg font-semibold text-card-foreground mb-2">No Plans Available</h3>
                    <p class="text-sm text-muted-foreground">Check back later for investment opportunities.</p>
                </div>

                <PaginationControls
                    v-if="plans.last_page > 1"
                    :links="plans.links"
                    :from="plans.from"
                    :to="plans.to"
                    :total="plans.total"
                    @go-to-page="goToPlansPage"
                    class="mt-8 pt-6 border-t border-border"
                />
            </div>
        </div>

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
</style>
