<script setup lang="ts">
    import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import axios from 'axios';
    import {
        DollarSignIcon,
        WalletIcon,
        TrendingUp,
        Gift,
        HelpCircle,
        Settings,
        ArrowUpDown
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TradingModeSwitcher from '@/components/TradingModeSwitcher.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import TradingChart from '@/components/TradingChart.vue';
    import TextLink from '@/components/TextLink.vue';
    import DesktopTradingPanel from '@/components/DesktopTradingPanel.vue';
    import MobileTradingPanel from '@/components/MobileTradingPanel.vue';
    import PairDrawer from '@/components/PairDrawer.vue';
    import TradesDrawer from '@/components/TradesDrawer.vue';
    import { useChartStore } from '@/stores/chartStore';

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
        pair_name: string;
        type: 'Up' | 'Down';
        amount: string;
        leverage: number
        duration: string;
        entry_price: string;
        exit_price: string | null;
        status: 'Open' | 'Closed';
        pnl: string;
        trading_mode: 'demo' | 'live';
        opened_at: string;
        closed_at: string | null;
        expiry_time: string;
    }

    interface ForexPair {
        symbol: string;
        name: string;
        polygon?: string;
        priority?: number;
        price?: string;
        change?: string;
        high?: string;
        low?: string;
        volume?: string;
        stale?: boolean;
        source?: string;
        updated_at?: string;
    }

    interface ChartData {
        success: boolean;
        data: Array<{
            time: number;
            open: number;
            high: number;
            low: number;
            close: number;
            volume: number;
        }>;
        high: string
        low: string
        volume: string
        symbol: string;
        timeframe: string;
    }

    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        forexPairs: Array<ForexPair>;
        trades: Array<Trade>;
        auth: {
            user: {
                profile: UserProfile;
                first_name: string;
                last_name: string
            };
            notification_count: number;
        };
    }>();

    const chartStore = useChartStore();

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const isLeftDrawerOpen = ref(false);
    const isRightDrawerOpen = ref(false);

    const selectedPair = ref<ForexPair | null>(null);
    const selectedPairSymbol = ref<string>('');
    const isLoadingPairData = ref(false);
    const pairDataCache = ref<Map<string, ForexPair>>(new Map());
    const chartData = ref<ChartData | null>(null);
    const hasChartData = ref(false);

    const isInitializing = ref(false);
    const initializationError = ref<string | null>(null);

    const availableLeverages = ref([50, 100, 200, 500, 1000]);
    const tradeFormData = ref({
        type: null as 'Up' | 'Down' | null,
        amount: 0,
        duration: '5m',
        leverage: 500
    });
    const tradeError = ref('');
    const isExecutingTrade = ref(false);

    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const userProfile = computed(() => user.value?.profile as UserProfile);

    const convertToNumber = (v: any): number => (typeof v === 'number' ? v : parseFloat(v) || 0);
    const isLiveMode = ref(userProfile.value?.trading_status === 'live');
    const liveBalance = computed(() => convertToNumber(userProfile.value?.live_trading_balance));
    const demoBalance = computed(() => convertToNumber(userProfile.value?.demo_trading_balance));
    const currentBalance = computed(() => isLiveMode.value ? liveBalance.value : demoBalance.value);

    const notificationCount = computed(() => page.props.auth?.notification_count || 0);
    const initials = computed(() => {
        if (user.value) {
            const f = user.value.first_name?.charAt(0) || '';
            const l = user.value.last_name?.charAt(0) || '';
            return `${f}${l}`.toUpperCase();
        }
        return '';
    });

    const pricesMap = computed(() => props.prices);
    const holdings = computed(() => props.tokens.map(t => {
        const bal = props.userBalances[t.symbol] || 0;
        const price = pricesMap.value[t.symbol] || 1;
        const isFiat = t.symbol === 'USD' || t.name.includes('Tether');
        return { symbol: t.symbol, name: t.name, logo: t.logo, balance: bal, value: bal * price, assetType: isFiat ? 'fiat' : 'crypto' };
    }));
    const cryptoHoldings = computed(() => holdings.value.filter(h => h.assetType === 'crypto'));

    const usedMargin = computed(() => currentBalance.value * 0.15);
    const availableMargin = computed(() => currentBalance.value - usedMargin.value);

    const durations = ['1m', '5m', '15m', '30m', '1h', '4h', '1d'];
    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Trading' }
    ];

    const openTrades = computed(() => props.trades
        .filter(t => t.status === 'Open' && t.trading_mode === (isLiveMode.value ? 'live' : 'demo'))
        .map(t => ({
            id: t.id,
            pair: t.pair,
            type: t.type,
            amount: parseFloat(t.amount),
            leverage: t.leverage,
            entry_price: parseFloat(t.entry_price),
            opened_at: t.opened_at,
            duration: t.duration,
            expiry_time: t.expiry_time
        }))
    );

    watch(openTrades, (val) => chartStore.setOpenTrades(val), { immediate: true });

    const handleFundingClick = () => { if (!isLiveMode.value) return; isFundingModalOpen.value = true; };
    const handleWithdrawalClick = () => { if (!isLiveMode.value) return; isWithdrawalModalOpen.value = true; };

    const fetchPairData = async (symbol: string): Promise<ForexPair | null> => {
        if (pairDataCache.value.has(symbol)) {
            const cachedData = pairDataCache.value.get(symbol);
            if (cachedData && cachedData.price) {
                return cachedData;
            }
        }

        isLoadingPairData.value = true;
        hasChartData.value = false;

        try {
            const encodedSymbol = encodeURIComponent(symbol);
            const response = await axios.get(route('user.trade.forex.chart.data', { symbol: encodedSymbol }));

            if (response.data.success) {
                const pairData = response.data.data;

                chartData.value = response.data;

                if (chartData.value && chartData.value.data && Array.isArray(chartData.value.data)) {
                    const formattedData = {
                        prices: chartData.value.data.map(candle => [
                            candle.time,
                            candle.open,
                            candle.high,
                            candle.low,
                            candle.close
                        ]),
                        volumes: chartData.value.data.map(candle => [
                            candle.time,
                            candle.volume
                        ]),
                        ohlc: true,
                        provider: 'backend',
                        success: true,
                        price: pairData.price || String(chartData.value.data[chartData.value.data.length - 1]?.close || 0)
                    };

                    chartStore.initializeCandlesFromOHLC(symbol, formattedData);
                    hasChartData.value = true;
                }

                pairDataCache.value.set(symbol, pairData);
                return pairData;
            } else {
                return null;
            }
        } catch (error) {
            return null;
        } finally {
            isLoadingPairData.value = false;
        }
    };

    const selectPair = async (pair: ForexPair) => {
        chartStore.setPair(pair.symbol);

        selectedPairSymbol.value = pair.symbol;
        selectedPair.value = { ...pair };

        const pairData = await fetchPairData(pair.symbol);

        if (pairData) {
            selectedPair.value = {
                ...pair,
                ...pairData
            };

            if (pairData.price) {
                const priceValue = parseFloat(pairData.price);
                if (isFinite(priceValue) && priceValue > 0) {
                    chartStore.updateCurrentPrice(pair.symbol, priceValue);
                }
            }
        }
    };

    const refreshPairData = async () => {
        if (selectedPair.value) {
            pairDataCache.value.delete(selectedPair.value.symbol);
            await selectPair(selectedPair.value);
        }
    };

    const validateTrade = () => {
        tradeError.value = '';
        if (!tradeFormData.value.type) { tradeError.value = 'Please select Up or Down'; return false; }
        if (!tradeFormData.value.amount) { tradeError.value = 'Please enter a valid amount'; return false; }
        if (tradeFormData.value.amount > availableMargin.value) { tradeError.value = 'Insufficient margin available'; return false; }
        return true;
    };

    const executeTrade = async () => {
        if (!validateTrade() || !selectedPair.value) return;
        isExecutingTrade.value = true;
        tradeError.value = '';
        try {

            const currentPairData = chartStore.pairDataMap[selectedPair.value.symbol];
            const currentPrice = currentPairData?.currentPrice ||
                parseFloat(selectedPair.value.price || '0');

            if (!currentPrice || currentPrice <= 0 || !isFinite(currentPrice)) {
                tradeError.value = 'Invalid current price. Please refresh and try again.';
                isExecutingTrade.value = false;
                return;
            }

            const tradeData = {
                pair: selectedPair.value.symbol,
                pair_name: selectedPair.value.name,
                type: tradeFormData.value.type,
                amount: tradeFormData.value.amount,
                duration: tradeFormData.value.duration,
                leverage: tradeFormData.value.leverage,
                entry_price: currentPrice,
                trading_mode: isLiveMode.value ? 'live' : 'demo'
            };

            await new Promise((resolve, reject) => {
                router.post(route('user.trade.forex.execute'), tradeData, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: (page) => {
                        tradeFormData.value = { type: null, amount: 0, duration: '5m', leverage: tradeFormData.value.leverage };
                        tradeError.value = '';
                        resolve(page);
                    },
                    onError: (errors) => {
                        if (errors.amount) tradeError.value = errors.amount;
                        else if (errors.leverage) tradeError.value = errors.leverage;
                        else if (errors.pair) tradeError.value = errors.pair;
                        else if (errors.type) tradeError.value = errors.type;
                        else if (errors.entry_price) tradeError.value = errors.entry_price;
                        else tradeError.value = 'Failed to execute trade. Please try again.';
                        reject(errors);
                    },
                    onFinish: () => {
                        isExecutingTrade.value = false;
                    }
                });
            });
        } catch (e: any) {
            tradeError.value = e.message || 'Trade execution failed. Please try again.';
            isExecutingTrade.value = false;
        }
    };

    const setMaxAmount = () => { tradeFormData.value.amount = availableMargin.value; };

    const toggleLeftDrawer = () => {
        isLeftDrawerOpen.value = !isLeftDrawerOpen.value;
        if (isLeftDrawerOpen.value) isRightDrawerOpen.value = false;
    };
    const toggleRightDrawer = () => {
        isRightDrawerOpen.value = !isRightDrawerOpen.value;
        if (isRightDrawerOpen.value) isLeftDrawerOpen.value = false;
    };

    watch([isFundingModalOpen, isWithdrawalModalOpen, isLeftDrawerOpen, isRightDrawerOpen],
        ([f, w, l, r]) => {
            document.body.style.overflow = f || w || l || r ? 'hidden' : '';
        });

    let refreshInterval: ReturnType<typeof setInterval> | null = null;

    onMounted(async () => {
        isInitializing.value = true;
        initializationError.value = null;

        try {
            const persistedSymbol = chartStore.selectedPair;
            let initialPair: ForexPair | undefined;

            if (persistedSymbol) {
                initialPair = props.forexPairs.find(p => p.symbol === persistedSymbol);
            }

            if (!initialPair) {
                initialPair = props.forexPairs.find(p => p.price) || props.forexPairs[0];
            }

            if (!initialPair) {
                throw new Error('No forex pairs available');
            }

            await selectPair(initialPair);

            if (!chartStore.hasPairData) {
                console.error('Chart initialization failed');
            }

            refreshInterval = setInterval(() => {
                if (selectedPair.value && !isLoadingPairData.value) {
                    refreshPairData();
                }
            }, 300000);

            isInitializing.value = false;

        } catch (error: any) {
            initializationError.value = error.message || 'Failed to initialize chart. Please refresh the page.';
            isInitializing.value = false;
        }
    });

    onUnmounted(() => {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
</script>

<template>
    <Head title="Forex Trading" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-20 sm:pb-8 h-screen flex flex-col">

            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="isNotificationsModalOpen = true"
            />

            <div class="bg-card border border-border rounded-2xl p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mt-6 flex-shrink-0">
                <div>
                    <h2 class="text-lg sm:text-xl font-semibold text-muted-foreground mb-1">Trading Balance</h2>
                    <div class="flex items-end gap-3">
                        <span class="text-2xl sm:text-4xl font-extrabold text-card-foreground">
                            ${{ currentBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </span>
                    </div>
                    <div class="text-xs sm:text-sm font-medium text-muted-foreground mt-1">
                        Mode: <span class="font-bold" :class="isLiveMode ? 'text-primary' : 'text-card-foreground'">
                            {{ isLiveMode ? 'Live Trading' : 'Demo Mode' }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3 w-full sm:w-auto">
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button v-if="isLiveMode" @click="handleFundingClick" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-xs sm:text-sm font-semibold hover:bg-muted cursor-pointer">
                            <WalletIcon class="w-4 h-4" /> <span>Deposit</span>
                        </button>

                        <button v-if="isLiveMode" @click="handleWithdrawalClick" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-xs sm:text-sm font-semibold hover:bg-muted cursor-pointer">
                            <DollarSignIcon class="w-4 h-4" /> <span>Withdraw</span>
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

            <div class="mt-4 flex-1 flex flex-col lg:flex-row gap-3 lg:min-h-0">
                <div class="flex-1 bg-card border border-border rounded-2xl flex flex-col lg:flex-row lg:min-h-0 lg:overflow-hidden" :class="{ 'padding-bottom': !selectedPair }">
                    <div class="flex lg:hidden bg-muted/20 border-b border-border p-3 flex-shrink-0">
                        <div class="flex w-full items-center justify-between gap-2">
                            <button @click="toggleLeftDrawer" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-xs sm:text-sm font-semibold hover:bg-muted cursor-pointer" title="Market">
                                <div class="flex items-center justify-center w-7 h-7 rounded-full" :class="isLeftDrawerOpen ? 'bg-primary/20' : 'bg-primary/10 group-hover:bg-primary/20'">
                                    <ArrowUpDown class="w-4 h-4" :class="isLeftDrawerOpen ? 'text-primary' : 'text-primary'" />
                                </div>
                                <span class="text-xs font-semibold tracking-tight">Market Pairs</span>
                            </button>

                            <button @click="toggleRightDrawer" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-xs sm:text-sm font-semibold hover:bg-muted cursor-pointer" title="Trades">
                                <div class="flex items-center justify-center w-7 h-7 rounded-full" :class="isRightDrawerOpen ? 'bg-emerald-500/20' : 'bg-emerald-500/10 group-hover:bg-emerald-500/20'">
                                    <TrendingUp class="w-4 h-4" :class="isRightDrawerOpen ? 'text-emerald-500' : 'text-emerald-500'" />
                                </div>
                                <span class="text-xs font-semibold tracking-tight">Trade History</span>
                            </button>
                        </div>
                    </div>

                    <div class="hidden lg:flex flex-col gap-2 bg-muted/20 border-r border-border p-3 flex-shrink-0">
                        <div class="w-14 h-14 mb-18 flex flex-col items-center justify-center bg-card border border-border rounded-xl hover:bg-muted transition cursor-pointer">
                            <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" alt="Profile" class="w-full h-full rounded-xl object-cover" />
                            <span v-else class="text-xl font-bold text-primary">{{ initials }}</span>
                        </div>

                        <button @click="toggleLeftDrawer" class="w-14 h-14 flex flex-col items-center justify-center bg-card border border-border rounded-xl hover:bg-muted transition cursor-pointer" title="Market">
                            <ArrowUpDown class="w-5 h-5 text-primary" />
                            <span class="text-[10px] mt-1 text-muted-foreground">Market</span>
                        </button>

                        <button @click="toggleRightDrawer" class="w-14 h-14 flex flex-col items-center justify-center bg-card border border-border rounded-xl hover:bg-muted transition cursor-pointer" title="Trades">
                            <TrendingUp class="w-5 h-5 text-primary" />
                            <span class="text-[10px] mt-1 text-muted-foreground">Trades</span>
                        </button>

                        <TextLink :href="route('user.rewards.index')" class="w-14 h-14 flex flex-col items-center justify-center bg-card border border-border rounded-xl hover:bg-muted transition cursor-pointer" title="Rewards">
                            <Gift class="w-5 h-5 text-primary" />
                            <span class="text-[10px] mt-1 text-muted-foreground">Rewards</span>
                        </TextLink>

                        <TextLink :href="route('user.support.index')" class="w-14 h-14 flex flex-col items-center justify-center bg-card border border-border rounded-xl hover:bg-muted transition cursor-pointer" title="Help">
                            <HelpCircle class="w-5 h-5 text-primary" />
                            <span class="text-[10px] mt-1 text-muted-foreground">Help</span>
                        </TextLink>

                        <TextLink :href="route('user.profile.index')" class="w-14 h-14 mt-18 flex flex-col items-center justify-center bg-card border border-border rounded-xl hover:bg-muted transition cursor-pointer" title="Settings">
                            <Settings class="w-5 h-5 text-primary" />
                            <span class="text-[10px] mt-1 text-muted-foreground">Settings</span>
                        </TextLink>
                    </div>

                    <div class="flex-1 h-[calc(100vh-280px)] lg:h-auto lg:min-h-0 relative lg:overflow-hidden">
                        <!-- Show chart when we have data, regardless of selectedPair.price -->
                        <TradingChart
                            v-if="hasChartData && selectedPair"
                            v-model:pair="selectedPairSymbol"
                            :price="selectedPair.price || '0'"
                            :change="selectedPair.change || '0'"
                            :low="selectedPair.low"
                            :high="selectedPair.high"
                            :volume="selectedPair.volume"
                            :open-trades="openTrades"
                            :use-backend-data="true"
                        />

                        <!-- Show loading state -->
                        <div v-else-if="isLoadingPairData" class="text-center text-muted-foreground text-sm py-4 h-full height-300 flex flex-col justify-center items-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                <p class="text-sm text-muted-foreground">Loading chart data...</p>
                            </div>
                        </div>

                        <!-- Show no data state only when not loading and no chart data -->
                        <div v-else class="text-center text-muted-foreground text-sm py-4 h-full flex flex-col justify-center items-center">
                            <div class="flex justify-center mb-3">
                                <TrendingUp class="h-10 w-10 text-muted-foreground" />
                            </div>
                            <p class="text-base font-medium mb-1 text-card-foreground">
                                No Chart Found
                            </p>
                            <p class="text-xs">
                                Select a pair to view the trading chart
                            </p>
                        </div>
                    </div>

                    <DesktopTradingPanel
                        :selected-pair="selectedPair"
                        :high="chartData?.high ?? '0.00'"
                        :low="chartData?.low ?? '0.00'"
                        :volume="chartData?.volume ?? '0'"
                        :trade-form-data="tradeFormData"
                        :durations="durations"
                        :available-margin="availableMargin"
                        :is-executing-trade="isExecutingTrade"
                        :trade-error="tradeError"
                        @update:duration="(d) => tradeFormData.duration = d"
                        @update:amount="(a) => tradeFormData.amount = a"
                        @update:type="(t) => tradeFormData.type = t"
                        @update:leverage="(l) => tradeFormData.leverage = l"
                        @execute-trade="executeTrade"
                        @set-max-amount="setMaxAmount"
                        :available-leverages="availableLeverages"
                    />
                </div>

                <MobileTradingPanel
                    :selected-pair="selectedPair"
                    :high="chartData?.high ?? '0.00'"
                    :low="chartData?.low ?? '0.00'"
                    :volume="chartData?.volume ?? '0'"
                    :trade-form-data="tradeFormData"
                    :durations="durations"
                    :available-margin="availableMargin"
                    :is-executing-trade="isExecutingTrade"
                    :trade-error="tradeError"
                    @update:duration="(d) => tradeFormData.duration = d"
                    @update:amount="(a) => tradeFormData.amount = a"
                    @update:type="(t) => tradeFormData.type = t"
                    @update:leverage="(l) => tradeFormData.leverage = l"
                    @execute-trade="executeTrade"
                    @set-max-amount="setMaxAmount"
                    :available-leverages="availableLeverages"
                />
            </div>

            <PairDrawer
                v-model="isLeftDrawerOpen"
                :pairs="props.forexPairs"
                :selected-symbol="selectedPair?.symbol"
                @select-pair="async (pair) => {
                    await selectPair(pair);
                    isLeftDrawerOpen = false;
                }"
            />

            <TradesDrawer
                v-model="isRightDrawerOpen"
                :trades="props.trades"
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
        </div>
    </AppLayout>
</template>

<style scoped>
    @media (max-width: 640px) {
        .padding-bottom {
            margin-bottom: 50px;
        }

        .height-300 {
            height: 300px !important;
        }
    }
</style>
