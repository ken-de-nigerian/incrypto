<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import {
        History,
        CreditCard,
        CheckCircle,
        X,
        Clock,
        DollarSign,
        Briefcase,
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import LoanRequestModal from '@/components/LoanRequestModal.vue';
    import WalletBalanceCard from '@/components/WalletBalanceCard.vue';
    import PaginationControls from '@/components/PaginationControls.vue';

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

    interface Loan {
        id: number;
        title: string;
        loan_amount: number;
        monthly_emi: number;
        tenure_months: number;
        interest_rate: number;
        total_payment: number;
        status: 'pending' | 'approved' | 'rejected' | 'completed';
        created_at: string;
        due_date?: string;
    }

    interface LoanStats {
        total_borrowed: number;
        active_loans: number;
        total_repaid: number;
        pending_requests: number;
    }

    interface LoanSettings {
        min_amount: number;
        max_amount: number;
        interest_rate: number;
        repayment_period: number;
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
        loans: {
            data: Loan[];
            links: any[];
            current_page: number;
            last_page: number;
            total: number;
            from: number;
            to: number;
        };
        stats: LoanStats;
        loanSettings: LoanSettings;
    }>();

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const isLoanRequestModalOpen = ref(false);

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
        { label: 'Loans & Credit' }
    ];

    const defaultLoanSettings = computed<LoanSettings>(() => ({
        min_amount: props.loanSettings?.min_amount || 1000,
        max_amount: props.loanSettings?.max_amount || 100000,
        interest_rate: props.loanSettings?.interest_rate || 5,
        repayment_period: props.loanSettings?.repayment_period || 60
    }));

    const handleFundingClick = () => {
        if (!isLiveMode.value) return;
        isFundingModalOpen.value = true;
    };

    const handleWithdrawalClick = () => {
        if (!isLiveMode.value) return;
        isWithdrawalModalOpen.value = true;
    };

    const openLoanRequestModal = () => {
        isLoanRequestModalOpen.value = true;
    };

    const closeLoanRequestModal = () => {
        isLoanRequestModalOpen.value = false;
    };

    const getStatusBadgeClass = (status: string) => {
        switch (status) {
            case 'approved': return 'bg-green-100 text-green-700 border-green-200';
            case 'completed': return 'bg-blue-100 text-blue-700 border-blue-200';
            case 'rejected': return 'bg-red-100 text-red-700 border-red-200';
            default: return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved': return CheckCircle;
            case 'completed': return CheckCircle;
            case 'rejected': return X;
            default: return Clock;
        }
    };

    watch([isFundingModalOpen, isWithdrawalModalOpen, isLoanRequestModalOpen], ([funding, withdrawal, loan]) => {
        document.body.style.overflow = (funding || withdrawal || loan) ? 'hidden' : '';
    });
</script>

<template>
    <Head title="Loans & Credit" />

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
                @deposit="handleFundingClick"
                @withdraw="handleWithdrawalClick"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-6">
                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <DollarSign class="w-4 h-4" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Total Borrowed</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground truncate">
                        ${{ props.stats.total_borrowed.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Briefcase class="w-4 h-4" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Active Loans</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground">
                        {{ props.stats.active_loans }}
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircle class="w-4 h-4" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Total Repaid</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold truncate">
                        ${{ props.stats.total_repaid.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Clock class="w-4 h-4" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Pending Review</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground">
                        {{ props.stats.pending_requests }}
                    </p>
                </div>
            </div>

            <div class="mt-8 mb-8 sm:mb-0">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                    <h2 class="text-xl sm:text-2xl font-bold text-card-foreground">
                        Loan History
                    </h2>

                    <button
                        @click="openLoanRequestModal"
                        class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-3 sm:py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-muted cursor-pointer transition-colors touch-manipulation">
                        <CreditCard class="w-4 h-4" />
                        <span>New Application</span>
                    </button>
                </div>

                <div class="bg-card border border-border rounded-xl p-0 overflow-hidden">
                    <div v-if="props.loans.data.length > 0">
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50 border-b border-border">
                                    <tr>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Title</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Amount</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">EMI</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Rate</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Tenure</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Status</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="loan in props.loans.data" :key="loan.id" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-bold text-card-foreground">{{ loan.title }}</p>
                                        </td>

                                        <td class="px-6 py-4 text-sm font-semibold text-card-foreground">
                                            ${{ parseFloat(loan.loan_amount.toString()).toLocaleString() }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-primary font-semibold">
                                            ${{ parseFloat(loan.monthly_emi.toString()).toLocaleString() }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-muted-foreground">
                                            {{ loan.interest_rate }}%
                                        </td>

                                        <td class="px-6 py-4 text-sm text-muted-foreground">
                                            {{ loan.tenure_months }} Months
                                        </td>

                                        <td class="px-6 py-4">
                                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold uppercase tracking-wide rounded-full border', getStatusBadgeClass(loan.status)]">
                                                <component :is="getStatusIcon(loan.status)" class="w-3 h-3" />
                                                {{ loan.status }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-sm text-muted-foreground">
                                            {{ new Date(loan.created_at).toLocaleDateString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="lg:hidden divide-y divide-border">
                            <div v-for="loan in props.loans.data" :key="loan.id" class="p-4 hover:bg-muted/20">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h4 class="font-bold text-card-foreground">{{ loan.title }}</h4>
                                        <span class="text-xs text-muted-foreground">{{ new Date(loan.created_at).toLocaleDateString() }}</span>
                                    </div>

                                    <span :class="['inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full border', getStatusBadgeClass(loan.status)]">
                                        <component :is="getStatusIcon(loan.status)" class="w-3 h-3" />
                                        {{ loan.status }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-sm mt-3">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Amount</p>
                                        <p class="font-semibold text-card-foreground">${{ parseFloat(loan.loan_amount.toString()).toLocaleString() }}</p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-xs text-muted-foreground">Monthly EMI</p>
                                        <p class="font-semibold text-primary">${{ parseFloat(loan.monthly_emi.toString()).toLocaleString() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <PaginationControls
                            v-if="props.loans.last_page > 1"
                            :links="props.loans.links"
                            :from="props.loans.from"
                            :to="props.loans.to"
                            :total="props.loans.total"
                            class="p-4 sm:p-6 pt-6 border-t border-border"
                        />
                    </div>

                    <div v-else class="flex flex-col items-center justify-center text-center py-16 px-4">
                        <div class="w-16 h-16 rounded-full bg-muted/50 flex items-center justify-center mb-4">
                            <History class="w-8 h-8 text-muted-foreground/50" />
                        </div>

                        <h3 class="text-lg font-bold text-card-foreground mb-2">No Loan History</h3>
                        <p class="text-sm text-muted-foreground mb-6 max-w-xs mx-auto">
                            You haven't requested any loans yet. Apply for a loan to get started.
                        </p>

                        <button
                            @click="openLoanRequestModal"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
                            <CreditCard class="w-4 h-4" />
                            Request Loan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <LoanRequestModal
            :is-open="isLoanRequestModalOpen"
            :loan-settings="defaultLoanSettings"
            :is-live-mode="isLiveMode"
            @close="closeLoanRequestModal"
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
