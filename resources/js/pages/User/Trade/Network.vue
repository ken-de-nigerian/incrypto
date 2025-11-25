<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        Copy,
        HistoryIcon,
        Search,
        UsersIcon,
        XIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import MasterTraderDetailsModal from '@/components/MasterTraderDetailsModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
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
        is_copied?: boolean;
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
        masterTraders: PaginatedData<MasterTrader>;
        activeCopiedTraderIds: number[];
        auth: {
            user: User;
            notification_count: number;
        };
    }>();

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const isMasterTraderModalOpen = ref(false);
    const selectedMasterTrader = ref<MasterTrader | null>(null);

    const page = usePage();

    const urlParams = new URLSearchParams(window.location.search);
    const sortFilter = ref<string>(urlParams.get('sort') || 'risk');
    const expertiseFilter = ref<string>(urlParams.get('expertise') || 'all');
    const searchQuery = ref(urlParams.get('search') || '');
    const showFreeTrial = ref(urlParams.get('free_trial') === '1');

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

    const hasActiveFilters = computed(() => {
        return sortFilter.value !== 'risk' ||
            expertiseFilter.value !== 'all' ||
            searchQuery.value !== '' ||
            showFreeTrial.value;
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

    const isTraderCopied = (traderId: number) => {
        return props.activeCopiedTraderIds.includes(traderId);
    };

    const startCopying = (trader: MasterTrader) => {
        if (isTraderCopied(trader.id)) {
            return;
        }
        selectedMasterTrader.value = trader;
        isMasterTraderModalOpen.value = true;
    };

    const handleFundingClick = () => {
        isFundingModalOpen.value = true;
    };

    const handleWithdrawalClick = () => {
        isWithdrawalModalOpen.value = true;
    };

    const applyFilters = () => {
        router.get(route('user.trade.network'), {
            sort: sortFilter.value,
            expertise: expertiseFilter.value,
            search: searchQuery.value,
            free_trial: showFreeTrial.value ? '1' : '0'
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['masterTraders', 'activeCopiedTraderIds'],
        });
    };

    const clearAllFilters = () => {
        sortFilter.value = 'risk';
        expertiseFilter.value = 'all';
        searchQuery.value = '';
        showFreeTrial.value = false;

        router.get(route('user.trade.network'), {}, {
            preserveState: true,
            preserveScroll: false,
            only: ['masterTraders', 'activeCopiedTraderIds'],
        });
    };

    const goToMasterTradersPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['masterTraders', 'activeCopiedTraderIds'],
        });
    };

    let searchTimeout: NodeJS.Timeout;
    watch(searchQuery, () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    });

    watch([sortFilter, expertiseFilter, showFreeTrial], () => {
        applyFilters();
    });

    const sortOptions = [
        { value: 'risk', label: 'Lowest Risk' },
        { value: 'gain', label: 'Highest Gain' },
        { value: 'copiers', label: 'Most Copied' }
    ];

    const expertiseOptions = [
        { value: 'all', label: 'All Levels' },
        { value: 'Newcomer', label: 'Newcomer' },
        { value: 'Growing talent', label: 'Growing Talent' },
        { value: 'High achiever', label: 'High Achiever' },
        { value: 'Expert', label: 'Expert' },
        { value: 'Legend', label: 'Legend' }
    ];
</script>

<template>
    <Head title="Copy Trading Network" />

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
                warning-message="Switch to Live Mode to start copy trading with real funds."
                @deposit="handleFundingClick"
                @withdraw="handleWithdrawalClick"
            />

            <div class="mt-6 mb-8 sm:mb-0">
                <div class="bg-card border border-border rounded-xl p-4 mb-6">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col md:flex-row gap-3">
                            <div class="flex-1">
                                <div class="relative">
                                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search traders..."
                                        class="w-full pl-11 pr-4 py-3 sm:py-2.5 bg-background border border-border rounded-lg text-base sm:text-sm text-card-foreground placeholder:text-muted-foreground transition-all"
                                    />
                                </div>
                            </div>

                            <button
                                v-if="hasActiveFilters"
                                @click="clearAllFilters"
                                class="flex items-center justify-center gap-2 px-4 py-3 sm:py-2.5 bg-background border border-border text-card-foreground rounded-lg text-sm font-semibold hover:bg-destructive hover:text-destructive-foreground hover:border-destructive transition-colors whitespace-nowrap cursor-pointer touch-manipulation">
                                <XIcon class="w-4 h-4" />
                                Clear Filters
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <CustomSelectDropdown
                                v-model="sortFilter"
                                :options="sortOptions"
                                placeholder="Sort by"
                                class="w-full"
                            />

                            <CustomSelectDropdown
                                v-model="expertiseFilter"
                                :options="expertiseOptions"
                                placeholder="Expertise"
                                class="w-full"
                            />

                            <div class="sm:col-span-2 lg:col-span-1 flex items-center gap-3 px-4 py-3 sm:py-2 bg-background border border-border rounded-lg touch-manipulation">
                                <input
                                    v-model="showFreeTrial"
                                    type="checkbox"
                                    id="freeTrial"
                                    class="w-5 h-5 sm:w-4 sm:h-4 text-primary bg-background border-border rounded cursor-pointer"
                                />
                                <label for="freeTrial" class="text-sm font-medium text-card-foreground cursor-pointer whitespace-nowrap flex-1">
                                    Free/Low Commission
                                </label>
                            </div>
                        </div>

                        <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 pt-3 border-t border-border/60">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Active:</span>

                            <span v-if="sortFilter !== 'risk'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                {{ sortOptions.find(o => o.value === sortFilter)?.label }}
                            </span>

                            <span v-if="expertiseFilter !== 'all'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                {{ expertiseOptions.find(o => o.value === expertiseFilter)?.label }}
                            </span>

                            <span v-if="searchQuery" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                "{{ searchQuery }}"
                            </span>

                            <span v-if="showFreeTrial" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                Free Trial
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                    <h2 class="text-xl sm:text-2xl font-bold text-card-foreground">
                        Master Traders
                    </h2>

                    <TextLink :href="route('user.trade.network.copied')" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-3 sm:py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-muted transition-colors touch-manipulation">
                        <HistoryIcon class="w-4 h-4" />
                        <span>My Copy Trades</span>
                    </TextLink>
                </div>

                <div v-if="props.masterTraders.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    <div
                        v-for="trader in props.masterTraders.data"
                        :key="trader.id"
                        class="group bg-card border border-border rounded-2xl overflow-hidden flex flex-col hover:border-primary/40 transition-colors">

                        <div class="p-5 flex-1 flex flex-col">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-12 h-12 rounded-full bg-secondary flex-shrink-0 flex items-center justify-center text-lg font-extrabold text-primary">
                                        {{ getTraderInitials(trader) }}
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="font-bold text-card-foreground text-base truncate leading-tight mb-1">
                                            {{ getTraderName(trader) }}
                                        </h3>
                                        <span :class="['inline-block text-[10px] px-2 py-0.5 rounded-full border font-bold uppercase tracking-wide', getExpertiseColor(trader.expertise)]">
                                            {{ trader.expertise }}
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right flex-shrink-0 pl-2">
                                    <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider mb-0.5">Gain</p>
                                    <p class="text-lg font-black leading-none" :class="parseFloat(trader.gain_percentage as string) >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ parseFloat(trader.gain_percentage as string) >= 0 ? '+' : '' }}{{ parseFloat(trader.gain_percentage as string).toFixed(2) }}%
                                    </p>
                                </div>
                            </div>

                            <div class="bg-muted/30 rounded-xl p-3 grid grid-cols-3 gap-2 mb-5 border border-border/50">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <span class="text-[10px] text-muted-foreground font-semibold mb-1">Risk</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-background border border-border text-card-foreground">
                                        {{ trader.risk_score }}/10
                                    </span>
                                </div>

                                <div class="flex flex-col items-center justify-center text-center border-l border-border/50">
                                    <span class="text-[10px] text-muted-foreground font-semibold mb-1">Copiers</span>
                                    <span class="text-sm font-bold text-card-foreground flex items-center gap-1">
                                        <UsersIcon class="w-3 h-3 text-muted-foreground" />
                                        {{ trader.copiers_count }}
                                    </span>
                                </div>

                                <div class="flex flex-col items-center justify-center text-center border-l border-border/50">
                                    <span class="text-[10px] text-muted-foreground font-semibold mb-1">Fee</span>
                                    <span class="text-sm font-bold" :class="!trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'text-green-600' : 'text-card-foreground'">
                                         {{ !trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'FREE' : `$${parseFloat(trader.commission_rate as string).toFixed(2)}` }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <div class="flex items-end justify-between text-xs mb-2 px-0.5">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-muted-foreground font-semibold">Profit</span>
                                        <span class="font-bold text-green-600">${{ parseFloat(trader.total_profit as string).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}</span>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] text-muted-foreground font-semibold">Loss</span>
                                        <span class="font-bold text-red-600">${{ parseFloat(trader.total_loss as string).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}</span>
                                    </div>
                                </div>

                                <div class="w-full h-1 bg-muted rounded-full overflow-hidden flex">
                                    <div
                                        class="h-full bg-green-500"
                                        :style="{ width: `${Math.round((parseFloat(trader.total_profit as string) / (parseFloat(trader.total_profit as string) + parseFloat(trader.total_loss as string)) * 100) || 50 )}%` }">
                                    </div>
                                    <div class="h-full w-px bg-background"></div>
                                    <div class="h-full bg-red-500"
                                         :style="{ width: `${Math.round((parseFloat(trader.total_loss as string) / (parseFloat(trader.total_profit as string) + parseFloat(trader.total_loss as string)) * 100) || 50 )}%` }">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 pt-0 mt-1">
                            <button
                                @click="startCopying(trader)"
                                :disabled="!isLiveMode || isTraderCopied(trader.id)"
                                :class="[
                                'w-full flex items-center justify-center gap-2 py-3.5 sm:py-3 rounded-xl font-bold text-sm transition-all active:scale-[0.98] touch-manipulation',
                                isTraderCopied(trader.id)
                                ? 'bg-muted text-muted-foreground cursor-not-allowed border border-border'
                                : isLiveMode
                                ? 'bg-primary/10 text-primary border border-border hover:bg-primary/20 cursor-pointer'
                                : 'bg-muted text-muted-foreground cursor-not-allowed opacity-70'
                            ]">
                                <template v-if="isTraderCopied(trader.id)">
                                    Already Copying
                                </template>
                                <template v-else>
                                    <Copy class="w-3.5 h-3.5" />
                                    {{ !trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'Start Free Trial' : 'Copy Strategy' }}
                                </template>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-card border border-border rounded-xl p-12 text-center">
                    <UsersIcon class="w-16 h-16 text-muted-foreground/30 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-card-foreground mb-2">No Master Traders Found</h3>
                    <p class="text-sm text-muted-foreground mb-6 max-w-xs mx-auto">Try adjusting your filters to see more traders.</p>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearAllFilters"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:bg-primary/90 transition-colors cursor-pointer touch-manipulation">
                        <XIcon class="w-4 h-4" />
                        Clear All Filters
                    </button>
                </div>

                <PaginationControls
                    v-if="props.masterTraders.last_page > 1"
                    :links="props.masterTraders.links"
                    :from="props.masterTraders.from"
                    :to="props.masterTraders.to"
                    :total="props.masterTraders.total"
                    @go-to-page="goToMasterTradersPage"
                    class="mt-8 pt-6 border-t border-border"
                />
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

        <MasterTraderDetailsModal
            :is-open="isMasterTraderModalOpen"
            :master-trader="selectedMasterTrader"
            :current-balance="currentBalance"
            @close="isMasterTraderModalOpen = false; selectedMasterTrader = null"
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
