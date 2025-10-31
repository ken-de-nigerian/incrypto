<script setup lang="ts">
    import { computed, ref, watch, onMounted } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import {
        DollarSignIcon,
        WalletIcon,
        TrendingUp,
        ZapIcon,
        FilterIcon,
        TrendingDown,
        SortAscIcon,
        SortDescIcon,
        ArrowUpDownIcon,
        Loader2Icon,
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TradingModeSwitcher from '@/components/TradingModeSwitcher.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import TradeModal from '@/components/TradeModal.vue';

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

    interface Trade {
        id: number;
        pair: string;
        pairName: string;
        type: 'Buy' | 'Sell';
        status: 'Open' | 'Closed';
        pnl: number;
        timestamp: string;
    }

    interface ForexPair {
        symbol: string;
        name: string;
        price: string;
        change: string;
        high: string;
        low: string;
        volume: string;
    }

    interface TradingStatItem {
        icon: any;
        label: string;
        value: string;
        color: string;
    }

    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        forexPairs: Array<ForexPair>;
        trades: Array<Trade>;
        tradingStats: Array<TradingStatItem>;
        auth: {
            user: {
                profile: UserProfile;
                first_name: string;
                last_name: string;
            }
            notification_count: number;
        };
    }>();

    // --- State Management ---
    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const globalAlert = ref<{ message: string; type: 'success' | 'error' | ''; show: boolean }>({
        message: '',
        type: '',
        show: false
    });

    const isTradeModalOpen = ref(false);
    const tradeModalData = ref({
        type: null as 'Buy' | 'Sell' | null,
        pair: '',
        price: '',
        change: '',
        high: '',
        low: '',
        volume: ''
    });

    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const userProfile = computed(() => user.value?.profile as UserProfile);

    // Safely convert balances to numbers
    const convertToNumber = (value: any): number => {
        if (typeof value === 'number') return value;
        if (typeof value === 'string') return parseFloat(value) || 0;
        return 0;
    };

    const isLiveMode = ref(userProfile.value?.trading_status === 'live');
    const liveBalance = computed(() => convertToNumber(userProfile.value?.live_trading_balance));
    const demoBalance = computed(() => convertToNumber(userProfile.value?.demo_trading_balance));
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

    const activeTab = ref('trending');

    // Dynamic margin calculations
    const usedMargin = computed(() => currentBalance.value * 0.15);
    const availableMargin = computed(() => currentBalance.value - usedMargin.value);
    const marginLevel = computed(() => currentBalance.value > 0 ? ((currentBalance.value / usedMargin.value) * 100) : 100);

    // Search and filter state
    const searchQuery = ref('');
    const sortOrder = ref<'asc' | 'desc' | 'default'>('default');
    const timeframe = ref<string>('1h');
    const showFilters = ref(false);

    // Infinite scroll state
    const displayCount = ref(8);
    const isLoadingMore = ref(false);
    const scrollContainer = ref<HTMLElement | null>(null);
    const itemsPerLoad = 8;
    const loadBuffer = 300;

    const tabs = [
        { id: 'trending', label: 'Trending Pairs', icon: TrendingUp },
        { id: 'gainers', label: 'Gainers', icon: TrendingUp },
        { id: 'losers', label: 'Losers', icon: TrendingDown }
    ];

    const timeframes = ['5m', '15m', '1h', '4h', '1d', '1w'];

    // Use backend data
    const forexPairs = computed(() => props.forexPairs || []);
    const tradeHistory = computed(() => props.trades || []);
    const tradingStats = computed(() => props.tradingStats || []);

    const filteredPairs = computed(() => {
        let filtered = [...forexPairs.value];

        if (searchQuery.value.trim()) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(p =>
                p.symbol.toLowerCase().includes(query) ||
                p.name.toLowerCase().includes(query)
            );
        }

        if (activeTab.value === 'gainers') {
            filtered = filtered.filter(p => parseFloat(p.change) > 0);
        } else if (activeTab.value === 'losers') {
            filtered = filtered.filter(p => parseFloat(p.change) < 0);
        }

        if (sortOrder.value === 'asc') {
            filtered = [...filtered].sort((a, b) => a.symbol.localeCompare(b.symbol));
        } else if (sortOrder.value === 'desc') {
            filtered = [...filtered].sort((a, b) => b.symbol.localeCompare(a.symbol));
        }

        return filtered;
    });

    const displayedPairs = computed(() => {
        return filteredPairs.value.slice(0, displayCount.value);
    });

    const hasMorePairs = computed(() => {
        return displayCount.value < filteredPairs.value.length;
    });

    const totalPairsCount = computed(() => filteredPairs.value.length);
    const hasActiveFilters = computed(() => {
        return searchQuery.value.trim() !== '' || sortOrder.value !== 'default' || timeframe.value !== '1h';
    });

    const toggleSortOrder = () => {
        if (sortOrder.value === 'default') {
            sortOrder.value = 'asc';
        } else if (sortOrder.value === 'asc') {
            sortOrder.value = 'desc';
        } else {
            sortOrder.value = 'default';
        }
    };

    const clearFilters = () => {
        searchQuery.value = '';
        sortOrder.value = 'default';
        timeframe.value = '1h';
    };

    const loadMore = () => {
        if (isLoadingMore.value || !hasMorePairs.value) return;
        isLoadingMore.value = true;
        setTimeout(() => {
            displayCount.value = Math.min(displayCount.value + itemsPerLoad, totalPairsCount.value);
            isLoadingMore.value = false;
        }, 500);
    };

    const handleScroll = (event: Event) => {
        const target = event.target as HTMLElement;
        const scrollTop = target.scrollTop;
        const scrollHeight = target.scrollHeight;
        const clientHeight = target.clientHeight;
        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);

        if (distanceFromBottom < loadBuffer && hasMorePairs.value && !isLoadingMore.value) {
            loadMore();
        }
    };

    watch([searchQuery, activeTab, timeframe], () => {
        displayCount.value = Math.min(itemsPerLoad, totalPairsCount.value);
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = 0;
        }
    });

    onMounted(() => {
        displayCount.value = Math.min(itemsPerLoad, totalPairsCount.value);
    });

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Trading' }
    ];

    const showAlert = (message: string, type: 'success' | 'error') => {
        globalAlert.value = { message, type, show: true };
        setTimeout(() => {
            globalAlert.value = { message: '', type: '', show: false };
        }, 5000);
    };

    const handleFundingClick = () => {
        if (!isLiveMode.value) {
            showAlert("You must be in Live Trading mode to fund the account.", 'error');
            return;
        }
        isFundingModalOpen.value = true;
    };

    const handleWithdrawalClick = () => {
        if (!isLiveMode.value) {
            showAlert("Withdrawal is only available in Live Trading mode.", 'error');
            return;
        }
        isWithdrawalModalOpen.value = true;
    };

    watch([isFundingModalOpen, isWithdrawalModalOpen, isTradeModalOpen], ([fundingOpen, withdrawalOpen, tradeOpen]) => {
        document.body.style.overflow = fundingOpen || withdrawalOpen || tradeOpen ? 'hidden' : '';
    });

    const openTradeModal = (pairData: ForexPair, type: 'Buy' | 'Sell') => {
        tradeModalData.value = {
            type,
            pair: pairData.symbol,
            price: pairData.price,
            change: pairData.change,
            high: pairData.high,
            low: pairData.low,
            volume: pairData.volume
        };
        isTradeModalOpen.value = true;
    };

    const closeTradeModal = () => {
        isTradeModalOpen.value = false;
    };

    const handleTradeExecute = (tradeData: any) => {
        showAlert(`${tradeData.type} order on ${tradeData.pair} executed successfully!`, 'success');
    };
</script>

<template>
    <Head title="Forex Trading" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="isNotificationsModalOpen = true"
            />

            <Transition name="fade" mode="out-in">
                <div v-if="globalAlert.show" :class="['fixed top-4 right-4 z-50 p-4 rounded-lg text-sm font-semibold shadow-lg max-w-sm flex items-center gap-2', globalAlert.type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700']">
                    <span>{{ globalAlert.message }}</span>
                    <button @click="globalAlert.show = false" class="ml-auto p-1 hover:bg-black/10 rounded cursor-pointer">
                        ✕
                    </button>
                </div>
            </Transition>

            <!-- Trading Balance Card -->
            <div class="bg-card border border-border rounded-2xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 mt-6">
                <div>
                    <h2 class="text-xl font-semibold text-muted-foreground mb-1">Trading Balance</h2>
                    <div class="flex items-end gap-3">
                        <span class="text-3xl sm:text-4xl font-extrabold text-card-foreground">
                            ${{ currentBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </span>
                    </div>
                    <div class="text-sm font-medium text-muted-foreground mt-1">
                        Mode: <span class="font-bold" :class="isLiveMode ? 'text-primary' : 'text-card-foreground'">{{ isLiveMode ? 'Live Trading' : 'Demo Mode' }}</span>
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

            <!-- Three Column Trading Layout -->
            <div class="mt-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Sidebar - Account Details & Trading Tips -->
                    <div class="hidden lg:block space-y-6">
                        <!-- Account Details Card -->
                        <div class="bg-gradient-to-br from-primary/10 to-primary/5 border border-primary/20 rounded-2xl p-6">
                            <h5 class="text-sm font-semibold text-card-foreground mb-4">Account Details</h5>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs text-muted-foreground mb-1">Current Balance</p>
                                    <p class="text-2xl font-bold text-primary">${{ currentBalance.toFixed(2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground mb-1">Equity</p>
                                    <p class="text-lg font-semibold text-card-foreground">${{ currentBalance.toFixed(2) }}</p>
                                </div>
                                <div class="pt-2 border-t border-border">
                                    <p class="text-xs text-muted-foreground mb-1">Margin Level</p>
                                    <p class="text-lg font-semibold" :class="marginLevel > 100 ? 'text-emerald-500' : 'text-warning'">{{ marginLevel.toFixed(2) }}%</p>
                                </div>
                            </div>
                        </div>

                        <!-- Margin Details -->
                        <div class="bg-card border border-border rounded-2xl p-6">
                            <h5 class="text-sm font-semibold text-card-foreground mb-4">Margin Details</h5>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Used Margin</span>
                                    <span class="text-sm font-semibold text-warning">${{ usedMargin.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Available</span>
                                    <span class="text-sm font-semibold text-emerald-500">${{ availableMargin.toFixed(2) }}</span>
                                </div>
                                <div class="pt-2 border-t border-border">
                                    <div class="w-full bg-muted/50 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-primary to-primary/70 h-2 rounded-full" :style="`width: ${currentBalance > 0 ? (usedMargin / currentBalance) * 100 : 0}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Trading Tips -->
                        <div class="bg-warning/10 border border-warning/20 rounded-2xl p-6">
                            <h5 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                                <ZapIcon class="w-5 h-5" />
                                Pro Tips
                            </h5>
                            <ul class="space-y-2 text-xs text-muted-foreground">
                                <li class="flex items-start gap-2">
                                    <span class="text-primary font-bold">•</span>
                                    <span>Use stop losses on all trades</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-primary font-bold">•</span>
                                    <span>Monitor economic calendars</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-primary font-bold">•</span>
                                    <span>Risk only 1-2% per trade</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Center - Trade History -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Account Stats -->
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="stat in tradingStats" :key="stat.label" class="bg-card/70 backdrop-blur border border-border rounded-xl p-4">
                                <p class="text-xs text-muted-foreground mb-2">{{ stat.label }}</p>
                                <p :class="['text-2xl font-bold', stat.color]">{{ stat.value }}</p>
                            </div>
                        </div>

                        <!-- Trade History Table -->
                        <div class="bg-card border border-border rounded-xl overflow-hidden max-h-96 overflow-y-auto custom-scrollbar">
                            <div class="bg-muted/30 px-4 py-3 border-b border-border sticky top-0">
                                <h3 class="text-sm font-semibold text-card-foreground">Trade History</h3>
                            </div>
                            <table class="w-full text-xs">
                                <thead class="bg-muted/20 sticky top-12">
                                <tr class="border-b border-border">
                                    <th class="text-left px-3 py-2 font-medium text-muted-foreground">Pair</th>
                                    <th class="text-center px-3 py-2 font-medium text-muted-foreground">Type</th>
                                    <th class="text-center px-3 py-2 font-medium text-muted-foreground">Status</th>
                                    <th class="text-right px-3 py-2 font-medium text-muted-foreground">P&L</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="trade in tradeHistory" :key="trade.id" class="border-b border-border/50 hover:bg-muted/20 transition">
                                    <td class="px-3 py-3">
                                        <div class="text-white font-medium">{{ trade.pair }}</div>
                                        <div class="text-muted-foreground text-xs">{{ trade.timestamp }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span :class="[
                                            'text-xs font-semibold px-2 py-1 rounded inline-block',
                                            trade.type === 'Buy'
                                                ? 'bg-emerald-500/20 text-emerald-400'
                                                : 'bg-rose-500/20 text-rose-400'
                                        ]">
                                            {{ trade.type }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span :class="[
                                            'text-xs font-semibold px-2 py-1 rounded inline-block border',
                                            trade.status === 'Open'
                                                ? 'bg-blue-500/20 text-blue-400 border-blue-500/30'
                                                : 'bg-muted/70 text-muted-foreground border-border'
                                        ]">
                                            {{ trade.status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div :class="[
                                            'font-semibold',
                                            trade.pnl >= 0 ? 'text-emerald-400' : 'text-rose-400'
                                        ]">
                                            {{ trade.pnl >= 0 ? '+' : '' }}${{ Math.abs(trade.pnl).toFixed(2) }}
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right Sidebar - Available Pairs -->
                    <div class="space-y-6">
                        <!-- Forex Pairs List -->
                        <div class="bg-card border border-border rounded-2xl overflow-hidden margin-bottom">
                            <div class="bg-muted/30 px-6 py-4 border-b border-border">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <TrendingUp class="w-5 h-5 text-muted-foreground" />
                                        <h3 class="font-semibold text-card-foreground">Available Pairs</h3>
                                    </div>
                                    <span class="text-xs text-muted-foreground">{{ displayedPairs.length }} of {{ totalPairsCount }}</span>
                                </div>

                                <!-- Tabs for Pair Filtering -->
                                <div class="flex gap-2 border-b border-border pb-3 overflow-x-auto mb-3">
                                    <button
                                        v-for="tab in tabs"
                                        :key="tab.id"
                                        @click="activeTab = tab.id"
                                        :class="[
                                            'text-xs px-2.5 py-1 rounded-lg transition whitespace-nowrap font-medium cursor-pointer',
                                            activeTab === tab.id
                                              ? 'bg-primary text-primary-foreground'
                                              : 'text-muted-foreground hover:bg-muted/50 border border-border'
                                          ]">
                                        {{ tab.label.split(' ')[0] }}
                                    </button>
                                </div>

                                <button
                                    @click="showFilters = !showFilters"
                                    class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-muted/70 hover:bg-muted/80 text-muted-foreground border border-border flex items-center gap-2 cursor-pointer"
                                    :class="{ 'bg-primary/10 text-primary border-primary/30': hasActiveFilters }"
                                >
                                    <FilterIcon class="w-3.5 h-3.5" />
                                    Filters
                                    <span v-if="hasActiveFilters" class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                </button>

                                <div v-if="showFilters" class="mt-3 space-y-2 pt-3 border-t border-border">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search pairs..."
                                        class="w-full px-3 py-1.5 bg-background border border-border rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    />
                                    <div class="flex gap-2">
                                        <select v-model="timeframe" class="flex-1 px-2 py-1.5 bg-background border border-border rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary/20">
                                            <option v-for="tf in timeframes" :key="tf" :value="tf">
                                                {{ tf }}
                                            </option>
                                        </select>
                                        <button @click="toggleSortOrder" class="flex-1 px-2 py-1.5 bg-background border border-border rounded-lg text-xs hover:bg-muted/50 flex items-center justify-center gap-1 cursor-pointer">
                                            <SortAscIcon v-if="sortOrder === 'asc'" class="w-3 h-3 text-primary" />
                                            <SortDescIcon v-else-if="sortOrder === 'desc'" class="w-3 h-3 text-primary" />
                                            <ArrowUpDownIcon v-else class="w-3 h-3 text-muted-foreground" />
                                        </button>
                                    </div>
                                    <button v-if="hasActiveFilters" @click="clearFilters" class="w-full px-3 py-1.5 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-xs font-medium cursor-pointer">
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <div ref="scrollContainer" @scroll="handleScroll" class="p-4 max-h-[500px] overflow-y-auto custom-scrollbar space-y-2">
                                <div v-if="displayedPairs.length === 0" class="text-center py-6 text-muted-foreground text-xs">
                                    No pairs found
                                </div>

                                <div v-for="pair in displayedPairs" :key="pair.symbol" class="group bg-gradient-to-br from-card to-muted/20 border border-border hover:border-primary/30 rounded-lg p-3 transition">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-card-foreground">{{ pair.symbol }}</p>
                                            <p class="text-xs text-muted-foreground">{{ pair.name }}</p>
                                        </div>
                                        <div :class="[
                                            'text-xs font-semibold px-2 py-1 rounded',
                                            parseFloat(pair.change) >= 0
                                              ? 'bg-emerald-500/20 text-emerald-400'
                                              : 'bg-rose-500/20 text-rose-400'
                                          ]">
                                            {{ parseFloat(pair.change) >= 0 ? '+' : '' }}{{ pair.change }}%
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between text-xs mb-3">
                                        <span class="text-white font-semibold">{{ pair.price }}</span>
                                        <span class="text-muted-foreground">Vol: {{ pair.volume }}</span>
                                    </div>

                                    <div class="flex gap-2">
                                        <button
                                            @click="openTradeModal(pair, 'Buy')"
                                            class="flex-1 px-2 py-1.5 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 rounded-lg text-xs font-medium transition border border-emerald-500/30 cursor-pointer">
                                            Buy
                                        </button>
                                        <button
                                            @click="openTradeModal(pair, 'Sell')"
                                            class="flex-1 px-2 py-1.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-400 rounded-lg text-xs font-medium transition border border-rose-500/30 cursor-pointer">
                                            Sell
                                        </button>
                                    </div>
                                </div>

                                <div v-if="isLoadingMore" class="flex justify-center py-4">
                                    <Loader2Icon class="w-5 h-5 text-primary animate-spin" />
                                </div>
                            </div>
                        </div>
                    </div>
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

        <TradeModal
            :is-open="isTradeModalOpen"
            :type="tradeModalData.type"
            :pair="tradeModalData.pair"
            :price="tradeModalData.price"
            :change="tradeModalData.change"
            :high="tradeModalData.high"
            :low="tradeModalData.low"
            :volume="tradeModalData.volume"
            :available-margin="availableMargin"
            @close="closeTradeModal"
            @execute="handleTradeExecute"
        />

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />
    </AppLayout>
</template>

<style scoped>
    .fade-enter-active, .fade-leave-active {
        transition: opacity 0.3s ease;
    }
    .fade-enter-from, .fade-leave-to {
        opacity: 0;
    }

    button:focus-visible,
    input:focus-visible,
    select:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }

    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgb(100 116 139 / 0.5) transparent;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgb(100 116 139 / 0.5);
        border-radius: 2px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgb(100 116 139 / 0.8);
    }

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
