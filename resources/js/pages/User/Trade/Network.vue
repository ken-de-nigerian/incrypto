<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        AlertTriangleIcon,
        DollarSignIcon,
        Search,
        UsersIcon,
        WalletIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TradingModeSwitcher from '@/components/TradingModeSwitcher.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import MasterTraderDetailsModal from '@/components/MasterTraderDetailsModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
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
    const sortFilter = ref<string>('risk');
    const expertiseFilter = ref<string>('all');
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

    const filteredMasterTraders = computed(() => {
        let filtered = [...props.masterTraders.data];

        if (expertiseFilter.value !== 'all') {
            filtered = filtered.filter(t => t.expertise === expertiseFilter.value);
        }

        if (showFreeTrial.value) {
            filtered = filtered.filter(t => !t.commission_rate || parseFloat(t.commission_rate as string) === 0);
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
            filtered.sort((a, b) => parseFloat(a.risk_score as string) - parseFloat(b.risk_score as string));
        } else if (sortFilter.value === 'gain') {
            filtered.sort((a, b) => parseFloat(b.gain_percentage as string) - parseFloat(a.gain_percentage as string));
        } else if (sortFilter.value === 'copiers') {
            filtered.sort((a, b) => parseFloat(b.copiers_count as string) - parseFloat(a.copiers_count as string));
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

    const startCopying = (trader: MasterTrader) => {
        selectedMasterTrader.value = trader;
        isMasterTraderModalOpen.value = true;
    };

    const handleFundingClick = () => {
        isFundingModalOpen.value = true;
    };

    const handleWithdrawalClick = () => {
        isWithdrawalModalOpen.value = true;
    };

    const goToMasterTradersPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['masterTraders'],
        });
    };

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
    <AppLayout>
        <Head title="Copy Trading Network" />

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

            <div class="margin-bottom mt-6">
                <!-- Header with link -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h2 v-if="filteredMasterTraders.length > 0" class="text-xl sm:text-2xl font-bold text-card-foreground">Master Traders Rating</h2>
                    <TextLink
                        :href="route('user.trade.copied')"
                        class="inline-flex items-center justify-center px-4 py-2 bg-primary text-primary-foreground font-medium rounded-md hover:bg-primary/90 transition-colors shadow-sm">
                        My Copy Trades
                    </TextLink>
                </div>

                <!-- Filters and Search -->
                <div class="bg-card border border-border rounded-xl p-4 mb-6">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <!-- Search -->
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search traders by name..."
                                    class="w-full pl-10 pr-4 py-2.5 bg-background border border-border rounded-lg text-sm text-card-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <CustomSelectDropdown
                                v-model="sortFilter"
                                :options="sortOptions"
                                placeholder="Sort by"
                                class="w-full sm:w-48"
                            />

                            <CustomSelectDropdown
                                v-model="expertiseFilter"
                                :options="expertiseOptions"
                                placeholder="Expertise"
                                class="w-full sm:w-48"
                            />

                            <div class="flex items-center gap-2 px-4 py-2 bg-background border border-border rounded-lg">
                                <input
                                    v-model="showFreeTrial"
                                    type="checkbox"
                                    id="freeTrial"
                                    class="w-4 h-4 text-primary bg-background border-border rounded focus:ring-primary/20 focus:ring-2"
                                />
                                <label for="freeTrial" class="text-sm font-medium text-card-foreground cursor-pointer whitespace-nowrap">
                                    Free/Low Commission
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Master Traders Grid -->
                <div v-if="filteredMasterTraders.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div
                        v-for="trader in filteredMasterTraders"
                        :key="trader.id"
                        class="group relative bg-card border border-border rounded-2xl overflow-hidden flex flex-col">

                        <div class="p-5 flex-1 flex flex-col">
                            <div class="flex items-center justify-between mb-5">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center text-lg font-extrabold text-primary">
                                            {{ getTraderInitials(trader) }}
                                        </div>
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="font-bold text-card-foreground text-base truncate leading-tight">
                                            {{ getTraderName(trader) }}
                                        </h3>

                                        <div class="flex items-center gap-1.5 mt-1">
                                        <span :class="['text-[10px] px-2 py-0.5 rounded-full border font-medium uppercase tracking-wider', getExpertiseColor(trader.expertise)]">
                                            {{ trader.expertise }}
                                        </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider mb-0.5">Gain</p>
                                    <p class="text-xl font-black" :class="parseFloat(trader.gain_percentage as string) >= 0 ? 'text-green-500' : 'text-red-500'">
                                        {{ parseFloat(trader.gain_percentage as string) >= 0 ? '+' : '' }}{{ parseFloat(trader.gain_percentage as string).toFixed(2) }}%
                                    </p>
                                </div>
                            </div>

                            <div class="bg-muted/40 rounded-xl p-3 grid grid-cols-3 gap-2 mb-5 border border-border/50">
                                <div class="flex flex-col items-center justify-center border-r border-border/50 last:border-0">
                                    <span class="text-[10px] text-muted-foreground font-medium mb-1">Risk</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-background border border-border text-card-foreground">
                                    {{ trader.risk_score }}/10
                                </span>
                                </div>

                                <div class="flex flex-col items-center justify-center border-r border-border/50 last:border-0">
                                    <span class="text-[10px] text-muted-foreground font-medium mb-1">Copiers</span>
                                    <span class="text-sm font-bold text-card-foreground flex items-center gap-1">
                                    <UsersIcon class="w-3 h-3 text-muted-foreground" />
                                    {{ trader.copiers_count }}
                                </span>
                                </div>

                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-[10px] text-muted-foreground font-medium mb-1">Fee</span>
                                    <span class="text-sm font-bold" :class="!trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'text-green-600' : 'text-card-foreground'">
                                     {{ !trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'FREE' : `$${parseFloat(trader.commission_rate as string).toFixed(2)}` }}
                                </span>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <div class="flex items-end justify-between text-xs mb-2">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-muted-foreground font-medium">Total Profit</span>
                                        <span class="font-bold text-green-600">${{ parseFloat(trader.total_profit as string).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}</span>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] text-muted-foreground font-medium">Total Loss</span>
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

                        <div class="p-4 pt-0 mt-2">
                            <button
                                @click="startCopying(trader)"
                                :disabled="!isLiveMode"
                                :class="[
                                'w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-sm',
                                isLiveMode
                                    ? 'bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer'
                                    : 'bg-muted text-muted-foreground cursor-not-allowed opacity-70'
                            ]">
                                {{ !trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'Start Free Trial' : 'Copy Strategy' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-card border border-border rounded-xl p-12 text-center">
                    <UsersIcon class="w-16 h-16 text-muted-foreground/50 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-card-foreground mb-2">No Master Traders Found</h3>
                    <p class="text-sm text-muted-foreground">Try adjusting your filters to see more traders.</p>
                </div>

                <!-- Pagination -->
                <PaginationControls
                    v-if="props.masterTraders.last_page > 1"
                    :links="props.masterTraders.links"
                    :from="props.masterTraders.from"
                    :to="props.masterTraders.to"
                    :total="props.masterTraders.total"
                    @go-to-page="goToMasterTradersPage"
                    class="md:mt-6 md:pt-6 md:border-t md:border-border"
                />
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

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
