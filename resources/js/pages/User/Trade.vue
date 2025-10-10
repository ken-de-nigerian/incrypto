<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import {
    ActivityIcon,
    ChevronDownIcon,
    DollarSignIcon,
    LayersIcon,
    ListIcon,
    LockIcon,
    SearchIcon,
    UsersIcon,
    XIcon,
    ZapIcon,
    RefreshCcwIcon,
    WalletIcon, // Added back for button description
    BriefcaseIcon, // Added back for button description
} from 'lucide-vue-next';
import Breadcrumb from '@/components/Breadcrumb.vue';
import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
import NotificationsModal from '@/components/utilities/NotificationsModal.vue';

// --- Data Models (from previous state) ---
interface Asset {
    symbol: string;
    name: string;
    logo: string;
    price: number;
    price_change_24h: number;
    type: 'forex' | 'stock';
    bid?: number;
    ask?: number;
    high?: number;
    low?: number;
    sentimentLong?: number;
    marketCap?: number;
    volume?: number;
    peRatio?: number;
    dividendYield?: number;
}

interface Tick {
    price: number;
    timestamp: string;
}

interface Holding {
    symbol: string;
    name: string;
    logo: string;
    balance: number;
    value: number;
    assetType: 'fiat' | 'stock' | 'crypto'; // Added 'crypto' back for conversion logic
}

interface Order {
    id: number;
    type: 'Market' | 'Limit' | 'Stop-Limit';
    side: 'Buy' | 'Sell' | 'Long' | 'Short';
    symbol: string;
    amount: number;
    price?: number;
    stopLoss?: number;
    takeProfit?: number;
    status: 'Open' | 'Filled' | 'Canceled';
    date: string;
}

interface Position {
    id: number;
    symbol: string;
    side: 'Long' | 'Short';
    amount: number;
    entryPrice: number;
    currentPrice: number;
    pnl: number;
    leverage: number;
    pnlUSD: number;
}

interface InvestmentPlan {
    id: number;
    name: string;
    apy: number;
    minInvestment: number;
    lockPeriodDays: number;
}

interface Investment {
    id: number;
    planId: number;
    amount: number;
    startDate: string;
    endDate: string;
    status: 'Active' | 'Matured' | 'Pending';
    estimatedPnl: number;
}

interface CopyTrader {
    id: number;
    name: string;
    roi: number;
    winRate: number;
    totalTrades: number;
    avgTradeDuration: string;
}

// --- Hardcoded Data (Adjusted for Crypto/Fiat Balances) ---
const hardcodedData = {
    userHoldings: [
        { symbol: 'USD', name: 'US Dollar', logo: '/logos/usd.svg', balance: 8500, value: 8500, assetType: 'fiat' },
        { symbol: 'BTC', name: 'Bitcoin', logo: '/logos/btc.svg', balance: 0.1, value: 6500, assetType: 'crypto' },
        { symbol: 'ETH', name: 'Ethereum', logo: '/logos/eth.svg', balance: 0.5, value: 1600, assetType: 'crypto' },
        { symbol: 'TSLA', name: 'Tesla', logo: '/logos/tsla.svg', balance: 10, value: 1900, assetType: 'stock' },
    ] as Holding[],
    portfolioValueUSD: 18500.00,
    portfolioChange24h: 0.85,
    openOrders: [
        { id: 1, type: 'Limit', side: 'Buy', symbol: 'TSLA', amount: 5, price: 185, status: 'Open', date: '2025-10-10T10:00:00' },
        { id: 2, type: 'Limit', side: 'Long', symbol: 'GBP/JPY', amount: 10000, price: 145.00, status: 'Open', date: '2025-10-10T11:00:00' },
    ] as Order[],
    activePositions: [
        { id: 11, symbol: 'EUR/USD', side: 'Short', amount: 50000, entryPrice: 1.080, currentPrice: 1.075, pnl: 0.46, leverage: 50, pnlUSD: 250.00 },
    ] as Position[],
    tradeHistory: [
        { id: 20, type: 'Market', side: 'Buy', symbol: 'AAPL', amount: 10, price: 174.50, status: 'Filled', date: '2025-10-10T09:30:00' },
        { id: 21, type: 'Market', side: 'Short', symbol: 'USD/CAD', amount: 5000, price: 1.375, status: 'Filled', date: '2025-10-10T08:00:00' },
    ] as Order[],
    forexAssets: [
        { symbol: 'EUR/USD', name: 'Euro / US Dollar', logo: '', price: 1.0754, price_change_24h: 0.25, type: 'forex', bid: 1.0753, ask: 1.0755, high: 1.0780, low: 1.0720, sentimentLong: 60 },
        { symbol: 'GBP/JPY', name: 'Pound / Yen', logo: '', price: 145.20, price_change_24h: -0.15, type: 'forex', bid: 145.18, ask: 145.22, high: 145.50, low: 144.90, sentimentLong: 45 },
        { symbol: 'USD/CAD', name: 'US Dollar / Canadian Dollar', logo: '', price: 1.3712, price_change_24h: 0.10, type: 'forex', bid: 1.3711, ask: 1.3713, high: 1.3720, low: 1.3680, sentimentLong: 55 },
    ] as Asset[],
    stockAssets: [
        { symbol: 'TSLA', name: 'Tesla', logo: '', price: 190.50, price_change_24h: -3.2, type: 'stock', marketCap: 600_000_000_000, volume: 80_000_000, peRatio: 50, dividendYield: 0 },
        { symbol: 'AAPL', name: 'Apple', logo: '', price: 175.80, price_change_24h: 1.1, type: 'stock', marketCap: 2_700_000_000_000, volume: 60_000_000, peRatio: 28, dividendYield: 0.56 },
        { symbol: 'GOOGL', name: 'Alphabet', logo: '', price: 140.25, price_change_24h: 0.5, type: 'stock', marketCap: 1_700_000_000_000, volume: 30_000_000, peRatio: 25, dividendYield: 0 },
    ] as Asset[],
    investmentPlans: [
        { id: 2, name: 'Managed Bond Portfolio', apy: 4.5, minInvestment: 500, lockPeriodDays: 30 },
        { id: 3, name: 'Long Term Index Fund', apy: 9.5, minInvestment: 5000, lockPeriodDays: 365 },
        { id: 4, name: 'Managed Forex Portfolio', apy: 15.0, minInvestment: 10000, lockPeriodDays: 180 },
    ] as InvestmentPlan[],
    investments: [
        { id: 2, planId: 2, amount: 1000, startDate: '2025-10-01', endDate: '2025-10-31', status: 'Active', estimatedPnl: 20 },
    ] as Investment[],
    copyTraders: [
        { id: 101, name: 'FX Master', roi: 31, winRate: 85, totalTrades: 120, avgTradeDuration: '3d' },
        { id: 102, name: 'Stock King', roi: 28, winRate: 78, totalTrades: 95, avgTradeDuration: '5d' },
        { id: 103, name: 'Alpha Quant', roi: 25, winRate: 82, totalTrades: 150, avgTradeDuration: '2d' },
    ] as CopyTrader[],
    tickData: {
        'EUR/USD': [
            { price: 1.0754, timestamp: '12:00:01' }, { price: 1.0753, timestamp: '12:00:02' }, { price: 1.0755, timestamp: '12:00:03' },
            { price: 1.0756, timestamp: '12:00:04' }, { price: 1.0754, timestamp: '12:00:05' }, { price: 1.0752, timestamp: '12:00:06' },
            { price: 1.0753, timestamp: '12:00:07' }, { price: 1.0755, timestamp: '12:00:08' }, { price: 1.0756, timestamp: '12:00:09' },
            { price: 1.0754, timestamp: '12:00:10' },
        ],
    } as Record<string, Tick[]>,
};

// All tradable assets
const allTradableAssets: Asset[] = [ ...hardcodedData.forexAssets, ...hardcodedData.stockAssets ];

// --- Props ---
const props = hardcodedData;

// --- State Management ---
const isWalletConnected = ref(true);
const walletAddress = ref('0xTradFiHub...');

// Trading Mode State
const isLiveMode = ref(false); // true for Live Trading, false for Demo Trading
const liveBalance = ref(props.userHoldings.find(h => h.symbol === 'USD')?.balance || 0); // USD balance for live trading
const demoBalance = ref(100000.00); // Fixed demo balance

// Funding Modal State
const isFundingModalOpen = ref(false);
const fundingAmount = ref(1000);
const fundingSourceToken = ref('BTC'); // Token to convert/deposit
const fundingSourceBalance = computed(() => props.userHoldings.find(h => h.symbol === fundingSourceToken.value)?.balance || 0);
const fundingConversionRate = computed(() => {
    if (fundingSourceToken.value === 'BTC') return 65000;
    if (fundingSourceToken.value === 'ETH') return 3200;
    return 1;
});
const estimatedUSDFunds = computed(() => {
    const amount = parseFloat(fundingAmount.value.toString()) || 0;
    return (amount * fundingConversionRate.value).toFixed(2);
});

// Unified asset selection state
const activeTradeAssetSymbol = ref('EUR/USD');
const activeTradingTab = ref<'Forex' | 'Stocks' | 'Invest'>('Forex');

// Order Panel State
const activeOrderType = ref<'Market' | 'Limit' | 'Stop-Limit'>('Market');
const forexLeverage = ref(50);
const forexPositionSide = ref<'Long' | 'Short'>('Long');
const forexVolume = ref('0.1');
const stopLossPrice = ref('');
const takeProfitPrice = ref('');
const stockAmount = ref('');
const stockTradeSide = ref<'Buy' | 'Sell'>('Buy');
const isOrderProcessing = ref(false);

// UI/Utility State
const activeUtilityTab = ref<'Assets' | 'Positions' | 'Investments'>('Assets');
const isAssetModalOpen = ref(false);
const modalAssetType = ref<'forex' | 'stock'>('forex');
const modalSearchQuery = ref('');
const modalActiveTab = ref<'popular' | 'all'>('all');

const isInvestmentModalOpen = ref(false);
const isCopyTraderModalOpen = ref(false);
const selectedInvestmentPlan = ref<InvestmentPlan | null>(null);
const selectedCopyTrader = ref<CopyTrader | null>(null);
const investmentAmount = ref('');
const copyTradeAmount = ref('');
const copyTradeSettings = ref({ autoClose: false, maxLoss: 10 });

const isNotificationsModalOpen = ref(false);
const page = usePage();
const user = computed(() => page.props.auth?.user);
const notificationCount = computed(() => page.props.auth?.notification_count || 0);
const initials = computed(() => {
    if (user.value) {
        const first = user.value.first_name?.charAt(0) || '';
        const last = user.value.last_name?.charAt(0) || '';
        return `${first}${last}`.toUpperCase();
    }
    return '';
});

const breadcrumbItems = [ { label: 'Dashboard', href: route('user.dashboard') }, { label: 'Trade' } ];

// --- Computed Properties ---
const currentBalance = computed(() => isLiveMode.value ? liveBalance.value : demoBalance.value);

const currentTradeAsset = computed<Asset>(() => {
    const asset = allTradableAssets.find(a => a.symbol === activeTradeAssetSymbol.value);
    return asset || {
        symbol: activeTradeAssetSymbol.value, name: activeTradeAssetSymbol.value, logo: '', price: 0, price_change_24h: 0,
        type: activeTradeAssetSymbol.value.includes('/') ? 'forex' : 'stock', bid: 0, ask: 0, high: 0, low: 0,
        sentimentLong: 50, marketCap: 0, volume: 0, peRatio: 0, dividendYield: 0
    };
});

const isForexAsset = computed(() => currentTradeAsset.value.type === 'forex');

const currentTickData = computed(() => {
    return props.tickData[activeTradeAssetSymbol.value] || hardcodedData.tickData['EUR/USD'];
});

const calculatedSpreadInPips = computed(() => {
    const { bid, ask } = currentTradeAsset.value;
    if (!bid || !ask) return 'N/A';
    const multiplier = isForexAsset.value && activeTradeAssetSymbol.value.includes('JPY') ? 100 : 10000;
    return ((ask - bid) * multiplier).toFixed(1);
});

const marginCalculation = computed(() => {
    const volumeLots = parseFloat(forexVolume.value) || 0;
    if (!volumeLots || !currentTradeAsset.value.price) return { requiredMargin: 0, commission: 0, swap: -1.20 };

    const contractSize = 100_000;
    const volumeUnits = volumeLots * contractSize;
    const leverage = forexLeverage.value;
    const price = currentTradeAsset.value.price;

    const requiredMargin = (volumeUnits * price) / leverage;
    const commission = volumeLots * 5;
    return { requiredMargin: requiredMargin, commission: commission, swap: -1.20 };
});

const calculatePnlForPrice = (targetPrice: number): number => {
    const volumeLots = parseFloat(forexVolume.value) || 0;
    const entryPrice = currentTradeAsset.value.price;
    if (!volumeLots || !entryPrice || !targetPrice) return 0;
    const direction = forexPositionSide.value === 'Long' ? 1 : -1;
    const pipsDiff = (targetPrice - entryPrice) * (isForexAsset.value && activeTradeAssetSymbol.value.includes('JPY') ? 100 : 10000);
    return direction * pipsDiff * (volumeLots * 10);
};

const stopLossPnl = computed(() => {
    const slPrice = parseFloat(stopLossPrice.value);
    if (!slPrice) return 0;
    const pnl = calculatePnlForPrice(slPrice);
    return pnl < 0 ? pnl.toFixed(2) : '0.00';
});

const takeProfitPnl = computed(() => {
    const tpPrice = parseFloat(takeProfitPrice.value);
    if (!tpPrice) return 0;
    const pnl = calculatePnlForPrice(tpPrice);
    return pnl > 0 ? pnl.toFixed(2) : '0.00';
});

const totalInvestedValue = computed(() => props.investments.reduce((sum, inv) => sum + inv.amount, 0).toFixed(2));

const investmentProgress = computed(() => {
    return props.investments.map(inv => {
        const plan = props.investmentPlans.find(p => p.id === inv.planId);
        const start = new Date(inv.startDate);
        const end = new Date(inv.endDate);
        const totalDays = (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24);
        const daysPassed = (new Date().getTime() - start.getTime()) / (1000 * 60 * 60 * 24);
        const progress = Math.min((daysPassed / totalDays) * 100, 100);
        const daysLeft = Math.max(totalDays - daysPassed, 0);
        return { ...inv, planName: plan?.name, progress, daysLeft };
    });
});

const filteredModalAssets = computed(() => {
    let assets = allTradableAssets.filter(a => a.type === modalAssetType.value);
    if (modalSearchQuery.value) {
        const query = modalSearchQuery.value.toLowerCase();
        assets = assets.filter(a => a.symbol.toLowerCase().includes(query) || a.name.toLowerCase().includes(query));
    }
    if (modalAssetType.value === 'forex' && modalActiveTab.value === 'popular') {
        const popularSymbols = ['EUR/USD', 'GBP/JPY'];
        assets = assets.filter(a => popularSymbols.includes(a.symbol));
    } else if (modalAssetType.value === 'stock' && modalActiveTab.value === 'popular') {
        const popularSymbols = ['TSLA', 'AAPL'];
        assets = assets.filter(a => popularSymbols.includes(a.symbol));
    }
    return assets;
});

const cryptoHoldings = computed(() => props.userHoldings.filter(h => h.assetType === 'crypto'));

// --- Methods ---
const openNotificationsModal = () => { isNotificationsModalOpen.value = true; };
const closeNotificationsModal = () => { isNotificationsModalOpen.value = false; };

const connectWallet = () => { isWalletConnected.value = true; walletAddress.value = '0xTradFiHub...'; };
const executeTrade = async () => {
    if (!isWalletConnected.value) { connectWallet(); return; }
    if (isLiveMode.value && currentBalance.value < parseFloat(marginCalculation.value.requiredMargin.toFixed(2))) {
        alert('Insufficient Live Balance/Margin. Please fund your account.');
        isFundingModalOpen.value = true;
        return;
    }
    isOrderProcessing.value = true;
    await new Promise(resolve => setTimeout(resolve, 1500));
    console.log(`Executed ${isLiveMode.value ? 'LIVE' : 'DEMO'} ${activeTradingTab.value} Trade on ${activeTradeAssetSymbol.value}. Status: Success`);
    if (!isLiveMode.value) { demoBalance.value -= 100; } // Mock demo loss/gain
    isOrderProcessing.value = false;
    stockAmount.value = ''; forexVolume.value = ''; stopLossPrice.value = ''; takeProfitPrice.value = '';
};

const openAssetModal = (type: 'forex' | 'stock') => {
    modalAssetType.value = type; modalSearchQuery.value = ''; modalActiveTab.value = 'all';
    isAssetModalOpen.value = true;
};

const selectAsset = (asset: Asset) => {
    activeTradeAssetSymbol.value = asset.symbol;
    activeTradingTab.value = asset.type === 'forex' ? 'Forex' : 'Stocks';
    isAssetModalOpen.value = false;
};

const openInvestmentModal = (plan: InvestmentPlan) => {
    selectedInvestmentPlan.value = plan; investmentAmount.value = '';
    isInvestmentModalOpen.value = true;
};

const openCopyTraderModal = (trader: CopyTrader | null = null) => {
    selectedCopyTrader.value = trader; // Allows opening the modal without selecting a specific trader first
    copyTradeAmount.value = '';
    copyTradeSettings.value = { autoClose: false, maxLoss: 10 };
    isCopyTraderModalOpen.value = true;
};

const copyTrader = async () => {
    if (!copyTradeAmount.value) { console.error('Invalid copy trade settings'); return; }
    isOrderProcessing.value = true;
    await new Promise(resolve => setTimeout(resolve, 1500));
    console.log(`Started copying ${selectedCopyTrader.value?.name || 'Trader'} with ${copyTradeAmount.value}`);
    isOrderProcessing.value = false;
    copyTradeAmount.value = '';
    copyTradeSettings.value = { autoClose: false, maxLoss: 10 };
    isCopyTraderModalOpen.value = false;
};

const performFunding = async () => {
    const amount = parseFloat(estimatedUSDFunds.value);
    if (fundingSourceBalance.value < (parseFloat(fundingAmount.value.toString()) || 0)) {
        alert('Insufficient source token balance.');
        return;
    }
    isOrderProcessing.value = true;
    await new Promise(resolve => setTimeout(resolve, 1500));
    liveBalance.value += amount;
    console.log(`Converted ${fundingAmount.value} ${fundingSourceToken.value} to $${amount} USD. New Live Balance: $${liveBalance.value}`);
    isOrderProcessing.value = false;
    isFundingModalOpen.value = false;
};

const selectFundingToken = (symbol: string) => {
    fundingSourceToken.value = symbol;
    fundingAmount.value = 1;
};

// --- Initialization ---
onMounted(() => {
    if (!allTradableAssets.some(a => a.symbol === activeTradeAssetSymbol.value)) {
        activeTradeAssetSymbol.value = props.forexAssets[0].symbol;
    }
});
</script>

<template>
    <Head title="Trade Terminal" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-6">
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex gap-2 border-b border-border p-2 bg-card rounded-xl">
                        <button
                            @click="activeUtilityTab = 'Assets'"
                            :class="['flex-1 py-2 text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-1', activeUtilityTab === 'Assets' ? 'bg-primary text-primary-foreground' : 'bg-muted/50 text-muted-foreground hover:bg-muted']"
                        >
                            <ListIcon class="w-4 h-4" /> Assets
                        </button>
                        <button
                            @click="activeUtilityTab = 'Positions'"
                            :class="['flex-1 py-2 text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-1', activeUtilityTab === 'Positions' ? 'bg-primary text-primary-foreground' : 'bg-muted/50 text-muted-foreground hover:bg-muted']"
                        >
                            <LayersIcon class="w-4 h-4" /> Trades
                        </button>
                        <button
                            @click="activeUtilityTab = 'Investments'"
                            :class="['flex-1 py-2 text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-1', activeUtilityTab === 'Investments' ? 'bg-primary text-primary-foreground' : 'bg-muted/50 text-muted-foreground hover:bg-muted']"
                        >
                            <LockIcon class="w-4 h-4" /> Invest
                        </button>
                    </div>

                    <div v-if="activeUtilityTab === 'Assets'" class="bg-card border border-border rounded-2xl p-3 shadow-xl h-[500px] overflow-y-auto">
                        <h3 class="text-sm font-semibold text-card-foreground mb-3">Forex Pairs</h3>
                        <div class="space-y-1">
                            <button
                                v-for="asset in props.forexAssets.slice(0, 5)"
                                :key="asset.symbol"
                                @click="selectAsset(asset)"
                                :class="['w-full p-2 rounded-lg text-left text-xs transition-colors border-l-4', activeTradeAssetSymbol === asset.symbol ? 'bg-primary/20 border-primary text-primary font-medium' : 'bg-background/50 border-transparent hover:bg-muted/50 text-card-foreground']"
                            >
                                <div class="flex justify-between items-center">
                                    <span>{{ asset.symbol }}</span>
                                    <span :class="asset.price_change_24h >= 0 ? 'text-primary' : 'text-destructive'">
                                        {{ asset.price_change_24h >= 0 ? '+' : '' }}{{ asset.price_change_24h.toFixed(2) }}%
                                    </span>
                                </div>
                            </button>
                        </div>
                        <h3 class="text-sm font-semibold text-card-foreground mt-4 mb-3 border-t border-border pt-3">Stocks</h3>
                        <div class="space-y-1">
                            <button
                                v-for="asset in props.stockAssets.slice(0, 5)"
                                :key="asset.symbol"
                                @click="selectAsset(asset)"
                                :class="['w-full p-2 rounded-lg text-left text-xs transition-colors border-l-4', activeTradeAssetSymbol === asset.symbol ? 'bg-primary/20 border-primary text-primary font-medium' : 'bg-background/50 border-transparent hover:bg-muted/50 text-card-foreground']"
                            >
                                <div class="flex justify-between items-center">
                                    <span>{{ asset.symbol }}</span>
                                    <span :class="asset.price_change_24h >= 0 ? 'text-primary' : 'text-destructive'">
                                        {{ asset.price_change_24h >= 0 ? '+' : '' }}{{ asset.price_change_24h.toFixed(2) }}%
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div v-else-if="activeUtilityTab === 'Positions'" class="bg-card border border-border rounded-2xl p-3 shadow-xl h-[500px] overflow-y-auto space-y-3">
                        <h3 class="text-sm font-semibold text-card-foreground">Active Positions</h3>
                        <div v-if="props.activePositions.length === 0" class="text-center text-xs text-muted-foreground p-4">No open positions.</div>
                        <div v-for="position in props.activePositions" :key="position.id" class="p-2 rounded-lg bg-background/50 space-y-1">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-bold" :class="position.side === 'Long' ? 'text-primary' : 'text-destructive'">{{ position.side.toUpperCase() }} {{ position.symbol }} ({{ position.leverage }}x)</span>
                                <span class="font-bold" :class="position.pnlUSD >= 0 ? 'text-primary' : 'text-destructive'">${{ position.pnlUSD.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-[10px] text-muted-foreground">
                                <span>Entry: {{ position.entryPrice.toFixed(4) }}</span>
                                <span>Current: {{ position.currentPrice.toFixed(4) }}</span>
                                <button class="text-primary hover:opacity-70">Close</button>
                            </div>
                        </div>
                        <h3 class="text-sm font-semibold text-card-foreground mt-4 border-t border-border pt-3">Open Orders</h3>
                        <div v-if="props.openOrders.length === 0" class="text-center text-xs text-muted-foreground p-4">No open orders.</div>
                    </div>

                    <div v-else-if="activeUtilityTab === 'Investments'" class="bg-card border border-border rounded-2xl p-3 shadow-xl h-[500px] overflow-y-auto space-y-3">
                        <div class="flex justify-between items-center">
                            <h3 class="text-sm font-semibold text-card-foreground">My Investments</h3>
                            <span class="text-xs font-semibold text-primary">Total: ${{ totalInvestedValue }}</span>
                        </div>
                        <div v-if="investmentProgress.length === 0" class="text-center text-xs text-muted-foreground p-4">No active investments.</div>
                        <div v-for="inv in investmentProgress" :key="inv.id" class="p-2 bg-background rounded-lg space-y-1">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-medium text-card-foreground">{{ inv.planName }}</span>
                                <span :class="inv.estimatedPnl >= 0 ? 'text-primary' : 'text-destructive'">P&L: ${{ inv.estimatedPnl.toFixed(2) }}</span>
                            </div>
                            <div class="relative w-full h-1.5 bg-muted rounded-full">
                                <div :style="{ width: `${inv.progress}%` }" class="absolute h-1.5 bg-primary rounded-full transition-all"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-muted-foreground">
                                <span>Amount: ${{ inv.amount.toLocaleString() }}</span>
                                <span>{{ inv.daysLeft.toFixed(0) }} days left</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7 space-y-4">
                    <div class="bg-card border border-border rounded-2xl p-3 shadow-xl flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <button @click="openAssetModal(currentTradeAsset.type)" class="flex items-center gap-2 p-2 bg-muted/50 rounded-lg hover:bg-muted transition-colors">
                                <ActivityIcon class="w-5 h-5 text-primary" />
                                <span class="text-lg font-bold text-card-foreground">{{ activeTradeAssetSymbol }}</span>
                                <ChevronDownIcon class="w-4 h-4 text-muted-foreground" />
                            </button>
                            <div class="text-xl font-extrabold" :class="currentTradeAsset.price_change_24h >= 0 ? 'text-primary' : 'text-destructive'">
                                {{ currentTradeAsset.price.toFixed(isForexAsset ? 4 : 2) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="relative inline-flex rounded-lg p-1" :class="isLiveMode ? 'bg-primary/20' : 'bg-muted/50'">
                                <button
                                    @click="isLiveMode = true"
                                    :class="['px-3 py-1 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1', isLiveMode ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted/70']"
                                >
                                    <ZapIcon class="w-3 h-3" /> Live
                                </button>
                                <button
                                    @click="isLiveMode = false"
                                    :class="['px-3 py-1 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1', !isLiveMode ? 'bg-background text-card-foreground shadow-md' : 'text-muted-foreground hover:bg-muted/70']"
                                >
                                    <DollarSignIcon class="w-3 h-3" /> Demo
                                </button>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-muted-foreground">{{ isLiveMode ? 'Live Balance (USD)' : 'Demo Balance (USD)' }}</div>
                                <div class="text-xl font-bold text-card-foreground flex items-center gap-2">
                                    ${{ currentBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    <button v-if="isLiveMode" @click="isFundingModalOpen = true" class="p-1 bg-primary/10 text-primary rounded-full hover:bg-primary/20 transition-colors" title="Fund Live Account">
                                        <WalletIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card border border-border rounded-2xl shadow-xl p-4 h-[550px] flex flex-col">
                        <div class="flex gap-2 text-sm border-b border-border pb-2 mb-2">
                            <button class="px-3 py-1 rounded bg-primary text-primary-foreground">1M</button>
                            <button class="px-3 py-1 rounded text-muted-foreground hover:bg-muted">5M</button>
                            <button class="px-3 py-1 rounded text-muted-foreground hover:bg-muted">1H</button>
                            <button class="px-3 py-1 rounded text-muted-foreground hover:bg-muted">1D</button>
                            <button class="px-3 py-1 rounded text-muted-foreground hover:bg-muted">Candles</button>
                            <button class="px-3 py-1 rounded text-muted-foreground hover:bg-muted">Indicators</button>
                        </div>
                        <div class="flex-1 w-full bg-muted/30 rounded-lg flex items-center justify-center text-muted-foreground text-xl border-dashed border-2 border-border/50">
                            **TradingView Chart Widget Placeholder** (Maximized Visual Space)
                        </div>

                        <div v-if="isForexAsset" class="mt-2 text-xs border-t border-border pt-2">
                            <span class="text-muted-foreground mr-2">Tick Feed:</span>
                            <span v-for="(tick, index) in currentTickData.slice(0, 10)" :key="index" :class="['font-mono mr-2', index > 0 && tick.price > currentTickData[index - 1].price ? 'text-primary' : 'text-destructive']">
                                {{ tick.price.toFixed(4) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-card border border-border rounded-2xl shadow-xl p-4 space-y-4">
                        <div class="flex gap-2 border-b border-border">
                            <button @click="activeTradingTab = 'Forex'" :class="['flex-1 py-2 text-sm font-bold relative transition-colors', activeTradingTab === 'Forex' ? 'text-primary' : 'text-muted-foreground hover:text-card-foreground']">
                                Forex
                                <div v-if="activeTradingTab === 'Forex'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                            </button>
                            <button @click="activeTradingTab = 'Stocks'" :class="['flex-1 py-2 text-sm font-bold relative transition-colors', activeTradingTab === 'Stocks' ? 'text-primary' : 'text-muted-foreground hover:text-card-foreground']">
                                Stocks
                                <div v-if="activeTradingTab === 'Stocks'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                            </button>
                            <button @click="activeTradingTab = 'Invest'" :class="['flex-1 py-2 text-sm font-bold relative transition-colors', activeTradingTab === 'Invest' ? 'text-primary' : 'text-muted-foreground hover:text-card-foreground']">
                                Invest
                                <div v-if="activeTradingTab === 'Invest'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                            </button>
                        </div>

                        <div v-if="activeTradingTab === 'Forex'" class="space-y-3">
                            <div class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>Leverage:</span>
                                <select v-model.number="forexLeverage" class="bg-muted/50 rounded-lg text-xs p-1 focus:outline-none focus:ring-1 focus:ring-primary">
                                    <option :value="10">10x</option>
                                    <option :value="50">50x</option>
                                    <option :value="100">100x</option>
                                </select>
                            </div>
                            <div class="flex gap-1 bg-muted rounded-lg p-0.5">
                                <button
                                    v-for="type in ['Market', 'Limit', 'Stop-Limit']"
                                    :key="type"
                                    @click="activeOrderType = type as 'Market' | 'Limit' | 'Stop-Limit'"
                                    :class="['flex-1 py-1.5 text-xs font-semibold rounded-lg transition-colors', activeOrderType === type ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted/70']"
                                >
                                    {{ type }}
                                </button>
                            </div>
                            <div class="relative">
                                <input v-model="forexVolume" type="number" step="0.1" placeholder="Volume (Lots)" class="w-full p-3 pr-16 bg-muted/50 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">LOTS</span>
                            </div>
                            <div class="relative">
                                <input v-model="stopLossPrice" type="number" step="0.0001" placeholder="Stop Loss Price" class="w-full p-3 pr-16 bg-muted/50 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-destructive transition-all" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-destructive text-xs font-bold">SL (${{ stopLossPnl }})</span>
                            </div>
                            <div class="relative">
                                <input v-model="takeProfitPrice" type="number" step="0.0001" placeholder="Take Profit Price" class="w-full p-3 pr-16 bg-muted/50 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-primary transition-all" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-primary text-xs font-bold">TP (${{ takeProfitPnl }})</span>
                            </div>

                            <div class="text-xs text-muted-foreground space-y-1 border-t border-border pt-3">
                                <div>Req. Margin ({{ forexLeverage }}x): <span class="font-semibold text-card-foreground">${{ marginCalculation.requiredMargin.toFixed(2) }}</span></div>
                                <div>Est. Commission: <span class="font-semibold text-card-foreground">${{ marginCalculation.commission.toFixed(2) }}</span></div>
                                <div class="text-destructive/80">Swap/Rollover: ${{ marginCalculation.swap.toFixed(2) }}/day</div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="forexPositionSide = 'Long'; executeTrade()" :disabled="!isWalletConnected || isOrderProcessing || !forexVolume" :class="['flex-1 py-3 font-bold rounded-lg transition-opacity', !isWalletConnected || isOrderProcessing || !forexVolume ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                                    Buy
                                </button>
                                <button @click="forexPositionSide = 'Short'; executeTrade()" :disabled="!isWalletConnected || isOrderProcessing || !forexVolume" :class="['flex-1 py-3 font-bold rounded-lg transition-opacity', !isWalletConnected || isOrderProcessing || !forexVolume ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-destructive hover:opacity-90 text-primary-foreground']">
                                    Sell
                                </button>
                            </div>
                        </div>

                        <div v-else-if="activeTradingTab === 'Stocks'" class="space-y-3">
                            <div class="relative">
                                <input v-model="stockAmount" type="number" step="any" placeholder="Amount (Shares)" class="w-full p-3 pr-20 bg-muted/50 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">SHARES</span>
                            </div>
                            <div class="flex gap-2">
                                <button @click="stockTradeSide = 'Buy'; executeTrade()" :disabled="!isWalletConnected || isOrderProcessing || !stockAmount" :class="['flex-1 py-3 font-bold rounded-lg transition-opacity', !isWalletConnected || isOrderProcessing || !stockAmount ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                                    Buy
                                </button>
                                <button @click="stockTradeSide = 'Sell'; executeTrade()" :disabled="!isWalletConnected || isOrderProcessing || !stockAmount" :class="['flex-1 py-3 font-bold rounded-lg transition-opacity', !isWalletConnected || isOrderProcessing || !stockAmount ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-destructive hover:opacity-90 text-primary-foreground']">
                                    Sell
                                </button>
                            </div>
                            <div class="bg-muted rounded-lg p-3 space-y-1 text-xs">
                                <h5 class="font-semibold text-card-foreground">Asset Metrics</h5>
                                <div class="flex justify-between text-muted-foreground">
                                    <span>P/E Ratio:</span>
                                    <span class="font-medium text-card-foreground">{{ currentTradeAsset.peRatio || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between text-muted-foreground">
                                    <span>Dividend Yield:</span>
                                    <span class="font-medium text-card-foreground">{{ currentTradeAsset.dividendYield ? currentTradeAsset.dividendYield + '%' : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTradingTab === 'Invest'" class="space-y-3">
                            <div class="bg-muted rounded-lg p-3 space-y-2 text-sm shadow-inner">
                                <h5 class="font-semibold text-card-foreground">Available Plans</h5>
                                <div v-for="plan in props.investmentPlans.slice(0, 3)" :key="plan.id" class="flex justify-between items-center text-xs">
                                    <span class="text-muted-foreground">{{ plan.name }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-primary font-bold">{{ plan.apy }}% APY</span>
                                        <button @click="openInvestmentModal(plan)" class="text-primary hover:underline">Invest</button>
                                    </div>
                                </div>
                            </div>
                            <button @click="activeUtilityTab = 'Investments'" class="w-full py-2 text-sm font-semibold rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                                View Full Portfolio
                            </button>
                        </div>
                    </div>

                    <button @click="openCopyTraderModal(null)" class="w-full bg-card border border-border rounded-2xl p-4 shadow-xl flex items-center justify-between hover:bg-muted transition-colors">
                        <div class="flex items-center gap-3">
                            <UsersIcon class="w-5 h-5 text-primary" />
                            <h3 class="text-sm font-semibold text-card-foreground">Copy Trading Network</h3>
                        </div>
                        <div class="text-primary text-xs font-semibold flex items-center gap-1">
                            Start Copying <ChevronDownIcon class="w-4 h-4" />
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="isAssetModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isAssetModalOpen = false">
                <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl overflow-hidden">
                    <div class="p-4 border-b border-border flex items-center justify-between">
                        <h3 class="text-lg font-bold text-card-foreground">Select {{ modalAssetType.charAt(0).toUpperCase() + modalAssetType.slice(1) }} Asset</h3>
                        <button @click="isAssetModalOpen = false" class="p-2 hover:bg-muted rounded-lg">
                            <XIcon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>
                    <div class="p-4 border-b border-border">
                        <div class="relative">
                            <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                            <input
                                v-model="modalSearchQuery"
                                type="text"
                                placeholder="Search by name or symbol"
                                class="w-full pl-10 pr-4 py-3 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>
                    <div class="px-4 pt-2 flex gap-2 border-b border-border">
                        <button
                            @click="modalActiveTab = 'popular'"
                            :class="[
                                'px-4 py-2 text-sm font-medium relative transition-colors',
                                modalActiveTab === 'popular' ? 'text-primary' : 'text-muted-foreground hover:text-card-foreground'
                            ]"
                        >
                            Popular
                            <div v-if="modalActiveTab === 'popular'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                        </button>
                        <button
                            @click="modalActiveTab = 'all'"
                            :class="[
                                'px-4 py-2 text-sm font-medium relative transition-colors',
                                modalActiveTab === 'all' ? 'text-primary' : 'text-muted-foreground hover:text-card-foreground'
                            ]"
                        >
                            All Assets
                            <div v-if="modalActiveTab === 'all'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                        </button>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <button
                            v-for="asset in filteredModalAssets"
                            :key="asset.symbol"
                            @click="selectAsset(asset)"
                            class="w-full p-4 hover:bg-muted cursor-pointer flex items-center justify-between border-b border-border/50 last:border-b-0"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-sm font-bold text-primary">{{ asset.symbol.charAt(0) }}</div>
                                <div>
                                    <div class="font-semibold text-card-foreground">{{ asset.symbol }}</div>
                                    <div class="text-xs text-muted-foreground">{{ asset.name }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-card-foreground">
                                    {{ asset.price.toFixed(modalAssetType === 'forex' ? 4 : 2) }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    <span :class="asset.price_change_24h >= 0 ? 'text-primary' : 'text-destructive'">
                                        {{ asset.price_change_24h >= 0 ? '+' : '' }}{{ asset.price_change_24h.toFixed(2) }}% (24h)
                                    </span>
                                </div>
                            </div>
                        </button>
                        <p v-if="filteredModalAssets.length === 0" class="text-center text-sm text-muted-foreground p-4">No results found.</p>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="isFundingModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isFundingModalOpen = false">
                <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl overflow-hidden">
                    <div class="p-4 border-b border-border flex items-center justify-between">
                        <h3 class="text-lg font-bold text-card-foreground">Fund Live Trading Account</h3>
                        <button @click="isFundingModalOpen = false" class="p-2 hover:bg-muted rounded-lg">
                            <XIcon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="bg-muted/30 p-3 rounded-lg space-y-2">
                            <p class="text-sm font-semibold text-card-foreground flex items-center gap-2">
                                <BriefcaseIcon class="w-4 h-4 text-primary" />
                                Current Live Balance: <span class="text-primary">${{ liveBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Increase your margin by **converting your available crypto holdings** instantly into USD fiat for live trading.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-card-foreground">1. Select Source Crypto to Convert:</h4>
                            <div class="flex gap-3 overflow-x-auto pb-2">
                                <button
                                    v-for="holding in cryptoHoldings"
                                    :key="holding.symbol"
                                    @click="selectFundingToken(holding.symbol)"
                                    :class="['p-3 rounded-lg text-xs border transition-all', fundingSourceToken === holding.symbol ? 'bg-primary border-primary text-primary-foreground' : 'bg-background border-border text-muted-foreground hover:bg-muted/50']"
                                >
                                    {{ holding.symbol }} ({{ holding.balance.toFixed(4) }})
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-card-foreground">2. Amount to Convert:</h4>
                            <div class="relative">
                                <input v-model.number="fundingAmount" type="number" step="any" placeholder="Amount" class="w-full p-3 pr-20 bg-muted/50 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">{{ fundingSourceToken }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-muted-foreground bg-muted/50 p-2 rounded-lg">
                                <span>Balance: {{ fundingSourceBalance.toFixed(4) }} {{ fundingSourceToken }}</span>
                                <span>Rate: 1 {{ fundingSourceToken }} = ${{ fundingConversionRate.toLocaleString() }} USD</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-primary/20 border border-primary/50 rounded-lg">
                            <div class="text-sm font-medium text-card-foreground flex items-center gap-2">
                                <RefreshCcwIcon class="w-4 h-4 text-primary" />
                                Estimated USD Funds:
                            </div>
                            <span class="text-lg font-bold text-primary">${{ estimatedUSDFunds }}</span>
                        </div>

                        <button :disabled="!isWalletConnected || isOrderProcessing || parseFloat(estimatedUSDFunds) <= 0 || fundingSourceBalance < (parseFloat(fundingAmount.toString()) || 0)" @click="performFunding" :class="['w-full py-3 font-bold rounded-lg transition-opacity', !isWalletConnected || isOrderProcessing || parseFloat(estimatedUSDFunds) <= 0 || fundingSourceBalance < (parseFloat(fundingAmount.toString()) || 0) ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                            {{ isOrderProcessing ? 'Converting...' : `Convert ${fundingSourceToken} & Fund Live Account` }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="isInvestmentModalOpen && selectedInvestmentPlan" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isInvestmentModalOpen = false">
                <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl overflow-hidden">
                    <div class="p-4 border-b border-border flex items-center justify-between">
                        <h3 class="text-lg font-bold text-card-foreground">Invest in {{ selectedInvestmentPlan.name }}</h3>
                        <button @click="isInvestmentModalOpen = false" class="p-2 hover:bg-muted rounded-lg">
                            <XIcon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="text-sm text-muted-foreground bg-muted/30 p-3 rounded-lg">
                            <p>Min Investment: <span class="font-medium text-card-foreground">${{ selectedInvestmentPlan.minInvestment.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span></p>
                            <p>APY: <span class="font-medium text-primary">{{ selectedInvestmentPlan.apy }}%</span></p>
                            <p>Lock Period: <span class="font-medium text-card-foreground">{{ selectedInvestmentPlan.lockPeriodDays }} days</span></p>
                        </div>
                        <div class="relative">
                            <input v-model="investmentAmount" type="number" step="any" :placeholder="`Min ${selectedInvestmentPlan.minInvestment} USD`" class="w-full p-3 pr-16 bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all" />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">USD</span>
                        </div>
                        <p v-if="investmentAmount && parseFloat(investmentAmount) < selectedInvestmentPlan.minInvestment" class="text-xs text-destructive">Amount must be at least ${{ selectedInvestmentPlan.minInvestment.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
                        <button
                            :disabled="!investmentAmount || parseFloat(investmentAmount) < selectedInvestmentPlan.minInvestment || isOrderProcessing"
                            @click="investInPlan"
                            :class="[
                                'w-full py-3 font-bold rounded-lg transition-opacity',
                                !investmentAmount || parseFloat(investmentAmount) < selectedInvestmentPlan.minInvestment || isOrderProcessing
                                    ? 'bg-muted text-muted-foreground cursor-not-allowed'
                                    : 'bg-primary hover:opacity-90 text-primary-foreground'
                            ]"
                        >
                            {{ isOrderProcessing ? 'Processing...' : 'Confirm Investment' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="isCopyTraderModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isCopyTraderModalOpen = false">
                <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl overflow-hidden">
                    <div class="p-4 border-b border-border flex items-center justify-between">
                        <h3 class="text-lg font-bold text-card-foreground">Copy Trading Setup</h3>
                        <button @click="isCopyTraderModalOpen = false" class="p-2 hover:bg-muted rounded-lg">
                            <XIcon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="bg-muted/30 p-3 rounded-lg space-y-2">
                            <h4 class="text-sm font-semibold text-card-foreground">Select a Top Trader:</h4>
                            <div class="space-y-1 max-h-40 overflow-y-auto">
                                <div v-for="trader in props.copyTraders" :key="trader.id" @click="selectedCopyTrader = trader" :class="['flex items-center justify-between p-2 rounded-lg text-xs cursor-pointer transition-colors', selectedCopyTrader?.id === trader.id ? 'bg-primary/20 border border-primary' : 'bg-background hover:bg-muted/50']">
                                    <span class="font-medium text-card-foreground">{{ trader.name }}</span>
                                    <span class="font-bold text-primary">+{{ trader.roi }}% ROI</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedCopyTrader" class="text-sm text-muted-foreground bg-muted/30 p-3 rounded-lg">
                            <p>Copying: <span class="font-bold text-card-foreground">{{ selectedCopyTrader.name }}</span></p>
                            <p>Win Rate: <span class="font-medium text-card-foreground">{{ selectedCopyTrader.winRate }}%</span></p>
                        </div>

                        <div class="relative">
                            <input v-model="copyTradeAmount" type="number" step="any" placeholder="Investment Amount (USD)" class="w-full p-3 pr-16 bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all" />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">USD</span>
                        </div>

                        <div class="space-y-3 p-3 bg-muted/30 rounded-lg">
                            <h5 class="text-xs font-semibold text-card-foreground">Risk Management</h5>
                            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                                <input type="checkbox" v-model="copyTradeSettings.autoClose" class="rounded text-primary focus:ring-primary" />
                                Auto-close losing trades
                            </label>
                            <div class="relative">
                                <input v-model.number="copyTradeSettings.maxLoss" type="number" step="1" placeholder="Max Loss (%)" class="w-full p-3 pr-16 bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">%</span>
                            </div>
                        </div>
                        <button
                            :disabled="!copyTradeAmount || isOrderProcessing || !selectedCopyTrader"
                            @click="copyTrader"
                            :class="[
                                'w-full py-3 font-bold rounded-lg transition-opacity',
                                !copyTradeAmount || isOrderProcessing || !selectedCopyTrader
                                    ? 'bg-muted text-muted-foreground cursor-not-allowed'
                                    : 'bg-primary hover:opacity-90 text-primary-foreground'
                            ]"
                        >
                            {{ isOrderProcessing ? 'Processing...' : 'Start Copying Strategy' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>


        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="closeNotificationsModal"
        />
    </AppLayout>
</template>

<style scoped>
/* Scoped styles remain unchanged for consistency */
input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 12px; height: 12px; background: hsl(var(--primary)); cursor: pointer; border-radius: 50%; transition: all 0.2s ease; }
input[type="range"]::-moz-range-thumb { width: 12px; height: 12px; background: hsl(var(--primary)); cursor: pointer; border-radius: 50%; border: none; transition: all 0.2s ease; }
input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type="number"] { -moz-appearance: textfield; }
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: hsl(var(--muted)); border-radius: 4px; }
::-webkit-scrollbar-thumb { background: hsl(var(--muted-foreground) / 0.3); border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: hsl(var(--muted-foreground) / 0.5); }
button:focus-visible, input:focus-visible, select:focus-visible { outline: 2px solid hsl(var(--primary)); outline-offset: 2px; }
@keyframes spin { to { transform: rotate(360deg); } }
.animate-spin { animation: spin 1s linear infinite; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.transition-all { transition: all 0.2s ease; }
</style>
