<script setup lang="ts">
    import { computed, ref, watch, onMounted } from 'vue';
    import {
        ArrowDownIcon, Settings2Icon, RefreshCwIcon, ChevronDownIcon,
        SearchIcon, XIcon, AlertCircleIcon, CheckCircleIcon, ClockIcon,
        TrendingUpIcon, TrendingDownIcon, ExternalLinkIcon, InfoIcon,
        WalletIcon, CopyIcon, CheckIcon, ZapIcon, LayersIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';

    // Props
    const props = defineProps<{
        tokens: Array<{
            symbol: string;
            name: string;
            address: string;
            logo: string;
            decimals: number;
            chain: string;
        }>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
    }>();

    // State
    const isWalletConnected = ref(false);
    const walletAddress = ref('');
    const selectedChain = ref('Ethereum');
    const fromToken = ref(props.tokens[0] || null);
    const toToken = ref(props.tokens[1] || null);
    const fromAmount = ref('');
    const toAmount = ref('');
    const isFromModalOpen = ref(false);
    const isToModalOpen = ref(false);
    const isSettingsOpen = ref(false);
    const slippage = ref(0.5);
    const customSlippage = ref('');
    const deadline = ref(20);
    const gasPreset = ref<'low' | 'medium' | 'high' | 'custom'>('medium');
    const customGas = ref('');
    const isSwapping = ref(false);
    const showRouteDetails = ref(false);
    const searchQuery = ref('');
    const copiedAddress = ref(false);
    const activeTab = ref<'popular' | 'all'>('popular');
    const showHistory = ref(false);
    const needsApproval = ref(true);
    const isApproving = ref(false);

    const page = usePage();
    const user = computed(() => page.props.auth?.user);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const isNotificationsModalOpen = ref(false);
    const notificationCount = computed(() => page.props.auth?.notification_count || 0);

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Swap Crypto' }
    ];

    // Mock transaction history
    const transactionHistory = ref<Array<{
        id: number;
        date: string;
        from: string;
        to: string;
        amount: string;
        status: 'success' | 'failed';
        hash: string;
    }>>([
        { id: 1, date: '2025-10-08 14:23', from: 'ETH', to: 'USDC', amount: '1.5', status: 'success', hash: '0x1234...5678' },
        { id: 2, date: '2025-10-08 12:15', from: 'USDC', to: 'ETH', amount: '3000', status: 'success', hash: '0xabcd...efgh' },
        { id: 3, date: '2025-10-07 18:45', from: 'BTC', to: 'ETH', amount: '0.05', status: 'failed', hash: '0x9876...5432' },
    ]);

    // Computed
    const chains = computed(() => ['Ethereum', 'BSC', 'Polygon', 'Arbitrum', 'Optimism']);

    const filteredTokens = computed(() => {
        if (!props.tokens) return [];
        return props.tokens.filter(token => {
            const matchesSearch = token.symbol.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                token.name.toLowerCase().includes(searchQuery.value.toLowerCase());
            const matchesChain = selectedChain.value === 'All' || token.chain === selectedChain.value;
            return matchesSearch && matchesChain;
        });
    });

    const popularTokens = computed(() => {
        if (!props.tokens) return [];
        const popularSymbols = ['ETH', 'USDC', 'USDT', 'BTC', 'BNB', 'MATIC'];
        return props.tokens.filter(t => popularSymbols.includes(t.symbol));
    });

    const fromBalance = computed(() => {
        if (!fromToken.value || !props.userBalances) return 0;
        return props.userBalances[fromToken.value.symbol] || 0;
    });

    const toBalance = computed(() => {
        if (!toToken.value || !props.userBalances) return 0;
        return props.userBalances[toToken.value.symbol] || 0;
    });

    const exchangeRate = computed(() => {
        if (!fromAmount.value || isNaN(parseFloat(fromAmount.value))) return 0;
        if (!fromToken.value || !toToken.value || !props.prices) return 0;
        const fromPrice = props.prices[fromToken.value.symbol] || 0;
        const toPrice = props.prices[toToken.value.symbol] || 0;
        if (toPrice === 0) return 0;
        return fromPrice / toPrice;
    });

    const priceImpact = computed(() => {
        if (!fromAmount.value || !toAmount.value) return 0;
        if (!fromToken.value || !props.prices) return 0;
        const amount = parseFloat(fromAmount.value);
        const liquidity = 1000000;
        return (amount * (props.prices[fromToken.value.symbol] || 0) / liquidity) * 100;
    });

    const priceImpactClass = computed(() => {
        if (priceImpact.value < 0.1) return 'text-primary';
        if (priceImpact.value < 1) return 'text-yellow-500';
        return 'text-destructive';
    });

    const minimumReceived = computed(() => {
        if (!toAmount.value) return '0';
        const amount = parseFloat(toAmount.value);
        const slippageMultiplier = 1 - (slippage.value / 100);
        return (amount * slippageMultiplier).toFixed(6);
    });

    const estimatedGas = computed(() => {
        const gasPresets: Record<string, { gwei: number; time: string; usd: number }> = {
            low: { gwei: 25, time: '~3 min', usd: 3.5 },
            medium: { gwei: 35, time: '~1 min', usd: 4.9 },
            high: { gwei: 50, time: '~15 sec', usd: 7.0 },
            custom: { gwei: parseFloat(customGas.value) || 35, time: '~1 min', usd: (parseFloat(customGas.value) || 35) * 0.14 }
        };
        return gasPresets[gasPreset.value] || gasPresets.medium;
    });

    const swapRoute = computed(() => {
        if (!fromToken.value || !toToken.value) return { primary: '', pools: [], alternatives: [] };
        return {
            primary: `${fromToken.value.symbol} → ${toToken.value.symbol} via Uniswap V3`,
            pools: ['Uniswap V3 Pool', '0.3% fee'],
            alternatives: [
                `${fromToken.value.symbol} → ${toToken.value.symbol} via SushiSwap (0.2% worse)`,
                `${fromToken.value.symbol} → USDC → ${toToken.value.symbol} via Curve (0.5% worse)`
            ]
        };
    });

    const canSwap = computed(() => {
        if (!isWalletConnected.value) return false;
        if (!fromAmount.value || isNaN(parseFloat(fromAmount.value))) return false;
        if (parseFloat(fromAmount.value) <= 0) return false;
        if (parseFloat(fromAmount.value) > fromBalance.value) return false;
        return !(isSwapping.value || isApproving.value);
    });

    const swapButtonText = computed(() => {
        if (!isWalletConnected.value) return 'Connect Wallet';
        if (isApproving.value) return 'Approving...';
        if (needsApproval.value && fromAmount.value) return `Approve ${fromToken.value?.symbol || ''}`;
        if (isSwapping.value) return 'Swapping...';
        if (!fromAmount.value) return 'Enter Amount';
        if (parseFloat(fromAmount.value) > fromBalance.value) return 'Insufficient Balance';
        return 'Swap';
    });

    // Methods
    const connectWallet = () => {
        setTimeout(() => {
            isWalletConnected.value = true;
            walletAddress.value = '0x742d...3a4f';
        }, 1000);
    };

    const disconnectWallet = () => {
        isWalletConnected.value = false;
        walletAddress.value = '';
    };

    const copyAddress = async () => {
        try {
            await navigator.clipboard.writeText('0x742d35Cc6634C0532925a3b844Bc9e3a4f');
            copiedAddress.value = true;
            setTimeout(() => copiedAddress.value = false, 2000);
        } catch (err) {
            console.error('Failed to copy address:', err);
        }
    };

    const selectFromToken = (token: typeof props.tokens[0]) => {
        if (token.symbol === toToken.value?.symbol) {
            toToken.value = fromToken.value;
        }
        fromToken.value = token;
        isFromModalOpen.value = false;
        calculateToAmount();
        needsApproval.value = true;
    };

    const selectToToken = (token: typeof props.tokens[0]) => {
        if (token.symbol === fromToken.value?.symbol) {
            fromToken.value = toToken.value;
        }
        toToken.value = token;
        isToModalOpen.value = false;
        calculateToAmount();
    };

    const reverseTokens = () => {
        const temp = fromToken.value;
        fromToken.value = toToken.value;
        toToken.value = temp;

        const tempAmount = fromAmount.value;
        fromAmount.value = toAmount.value;
        toAmount.value = tempAmount;

        needsApproval.value = true;
    };

    const setMaxAmount = () => {
        if (!fromToken.value) return;
        const gasReserve = fromToken.value.symbol === 'ETH' ? 0.01 : 0;
        fromAmount.value = Math.max(0, fromBalance.value - gasReserve).toString();
        calculateToAmount();
    };

    const calculateToAmount = () => {
        if (!fromAmount.value || isNaN(parseFloat(fromAmount.value))) {
            toAmount.value = '';
            return;
        }

        setTimeout(() => {
            const amount = parseFloat(fromAmount.value) * exchangeRate.value;
            toAmount.value = amount.toFixed(6);
        }, 300);
    };

    const setSlippage = (value: number) => {
        slippage.value = value;
        customSlippage.value = '';
    };

    const setCustomSlippage = () => {
        const value = parseFloat(customSlippage.value);
        if (!isNaN(value) && value >= 0 && value <= 10) {
            slippage.value = value;
        }
    };

    const setCustomGas = () => {
        const value = parseFloat(customGas.value);
        if (!isNaN(value) && value >= 0) {
            gasPreset.value = 'custom';
            showToast(`Custom gas set to ${value} Gwei`, 'info');
        }
    };

    const approveToken = () => {
        isApproving.value = true;
        setTimeout(() => {
            needsApproval.value = false;
            isApproving.value = false;
            showToast('Token approved successfully!', 'success');
        }, 2000);
    };

    const executeSwap = () => {
        if (!canSwap.value) {
            if (!isWalletConnected.value) {
                connectWallet();
            }
            return;
        }

        if (needsApproval.value) {
            approveToken();
            return;
        }

        isSwapping.value = true;

        setTimeout(() => {
            isSwapping.value = false;
            const swappedAmount = fromAmount.value;
            fromAmount.value = '';
            toAmount.value = '';
            needsApproval.value = true;
            showToast('Swap completed successfully!', 'success');

            transactionHistory.value.unshift({
                id: Date.now(),
                date: new Date().toLocaleString(),
                from: fromToken.value?.symbol || '',
                to: toToken.value?.symbol || '',
                amount: swappedAmount,
                status: 'success',
                hash: '0x' + Math.random().toString(16).slice(2, 10) + '...' + Math.random().toString(16).slice(2, 6)
            });
        }, 3000);
    };

    const showToast = (message: string, type: 'success' | 'error' | 'info') => {
        console.log(`[${type.toUpperCase()}] ${message}`);
    };

    // Watch for amount changes
    watch(fromAmount, () => {
        calculateToAmount();
    });

    // Initialize
    onMounted(() => {
        if (props.tokens && props.tokens.length > 0) {
            fromToken.value = props.tokens[0];
            toToken.value = props.tokens[1] || props.tokens[0];
        } else {
            console.warn('No tokens available for initialization');
            fromToken.value = null;
            toToken.value = null;
        }
        calculateToAmount();
    });
</script>

<template>
    <Head title="Swap Crypto" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="w-full max-w-2xl mx-auto p-4">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-card-foreground">Swap Tokens</h1>
                        <p class="text-sm text-muted-foreground mt-1">Trade tokens instantly across chains</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Chain Selector -->
                        <select
                            v-model="selectedChain"
                            class="px-3 py-2 bg-card border border-border rounded-lg text-sm text-card-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                            <option v-for="chain in chains" :key="chain" :value="chain">{{ chain }}</option>
                        </select>

                        <!-- Wallet Connection -->
                        <button
                            v-if="!isWalletConnected"
                            @click="connectWallet"
                            class="flex items-center gap-2 px-4 py-2 bg-primary hover:opacity-90 text-primary-foreground rounded-lg font-semibold text-sm transition-opacity">
                            <WalletIcon class="w-4 h-4" />
                            Connect Wallet
                        </button>

                        <div v-else class="flex items-center gap-2">
                            <div class="px-3 py-2 bg-card border border-border rounded-lg flex items-center gap-2">
                                <div class="w-2 h-2 bg-primary rounded-full"></div>
                                <span class="text-sm font-mono text-card-foreground">{{ walletAddress }}</span>
                                <button @click="copyAddress" class="p-1 hover:bg-muted rounded transition-colors">
                                    <CheckIcon v-if="copiedAddress" class="w-3 h-3 text-primary" />
                                    <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                                </button>
                            </div>
                            <button
                                @click="disconnectWallet"
                                class="p-2 bg-card border border-border hover:bg-muted rounded-lg transition-colors">
                                <XIcon class="w-4 h-4 text-muted-foreground" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Swap Card -->
                <div class="bg-card border border-border rounded-2xl p-6 shadow-lg">
                    <!-- Settings Button -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <ZapIcon class="w-5 h-5 text-primary" />
                            <span class="text-sm font-semibold text-card-foreground">Quick Swap</span>
                        </div>
                        <button
                            @click="isSettingsOpen = !isSettingsOpen"
                            class="p-2 hover:bg-muted rounded-lg transition-colors">
                            <Settings2Icon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>

                    <!-- Settings Panel -->
                    <div v-if="isSettingsOpen" class="mb-6 p-4 bg-muted/50 rounded-xl border border-border space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-card-foreground mb-2 block">Slippage Tolerance</label>
                            <div class="flex items-center gap-2">
                                <button
                                    v-for="s in [0.1, 0.5, 1]"
                                    :key="s"
                                    @click="setSlippage(s)"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                        slippage === s ? 'bg-primary text-primary-foreground' : 'bg-background text-muted-foreground hover:bg-muted'
                                    ]">
                                    {{ s }}%
                                </button>
                                <input
                                    v-model="customSlippage"
                                    @blur="setCustomSlippage"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="10"
                                    placeholder="Custom"
                                    class="flex-1 px-3 py-1.5 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                            </div>
                            <p v-if="slippage > 1" class="text-xs text-yellow-500 mt-1 flex items-center gap-1">
                                <AlertCircleIcon class="w-3 h-3" />
                                High slippage may result in unfavorable rates
                            </p>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-card-foreground mb-2 block">Transaction Deadline</label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model.number="deadline"
                                    type="number"
                                    min="1"
                                    max="60"
                                    class="flex-1 px-3 py-1.5 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                                <span class="text-sm text-muted-foreground">minutes</span>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-card-foreground mb-2 block">Gas Price</label>
                            <div class="flex items-center gap-2">
                                <button
                                    v-for="preset in ['low', 'medium', 'high']"
                                    :key="preset"
                                    @click="gasPreset = preset as 'low' | 'medium' | 'high'"
                                    :class="[
                                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                        gasPreset === preset ? 'bg-primary text-primary-foreground' : 'bg-background text-muted-foreground hover:bg-muted'
                                    ]">
                                    <div class="capitalize">{{ preset }}</div>
                                    <div class="text-xs opacity-70">{{ estimatedGas.time }}</div>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-card-foreground mb-2 block">Custom Gas Price</label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="customGas"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    placeholder="Custom Gwei"
                                    class="flex-1 px-3 py-1.5 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                    @blur="setCustomGas"
                                />
                                <span class="text-sm text-muted-foreground">Gwei</span>
                            </div>
                        </div>
                    </div>

                    <!-- From Token -->
                    <div v-if="fromToken" class="bg-muted/50 rounded-xl p-4 border border-border">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-muted-foreground">From</span>
                            <span class="text-xs text-muted-foreground">
                                Balance: {{ fromBalance.toFixed(4) }} {{ fromToken.symbol }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                @click="isFromModalOpen = true"
                                class="flex items-center gap-2 px-3 py-2 bg-background hover:bg-muted border border-border rounded-lg transition-colors">
                                <img :src="fromToken.logo" :alt="fromToken.symbol" class="w-6 h-6 rounded-full" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22%3E%3Ctext y=%2218%22 font-size=%2220%22%3E💎%3C/text%3E%3C/svg%3E'" />
                                <span class="font-semibold text-card-foreground">{{ fromToken.symbol }}</span>
                                <ChevronDownIcon class="w-4 h-4 text-muted-foreground" />
                            </button>

                            <div class="flex-1 flex flex-col items-end">
                                <input
                                    v-model="fromAmount"
                                    type="number"
                                    step="any"
                                    placeholder="0.0"
                                    class="w-full text-right text-2xl font-semibold bg-transparent border-none outline-none text-card-foreground placeholder:text-muted-foreground/50" />
                                <span class="text-xs text-muted-foreground mt-1">
                                    ≈ ${{ (parseFloat(fromAmount || '0') * (prices[fromToken.symbol] || 0)).toFixed(2) }}
                                </span>
                            </div>
                        </div>

                        <button
                            @click="setMaxAmount"
                            class="mt-2 px-2 py-1 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-semibold rounded transition-colors">
                            MAX
                        </button>
                    </div>

                    <!-- Reverse Button -->
                    <div class="flex justify-center -my-3 relative z-10">
                        <button
                            @click="reverseTokens"
                            class="p-2 bg-card border-2 border-border hover:border-primary rounded-full transition-all hover:rotate-180 duration-300">
                            <ArrowDownIcon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>

                    <!-- To Token -->
                    <div v-if="toToken" class="bg-muted/50 rounded-xl p-4 border border-border">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-muted-foreground">To</span>
                            <span class="text-xs text-muted-foreground">
                                Balance: {{ toBalance.toFixed(4) }} {{ toToken.symbol }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                @click="isToModalOpen = true"
                                class="flex items-center gap-2 px-3 py-2 bg-background hover:bg-muted border border-border rounded-lg transition-colors">
                                <img :src="toToken.logo" :alt="toToken.symbol" class="w-6 h-6 rounded-full" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22%3E%3Ctext y=%2218%22 font-size=%2220%22%3E💎%3C/text%3E%3C/svg%3E'" />
                                <span class="font-semibold text-card-foreground">{{ toToken.symbol }}</span>
                                <ChevronDownIcon class="w-4 h-4 text-muted-foreground" />
                            </button>

                            <div class="flex-1 flex flex-col items-end">
                                <input
                                    :value="toAmount"
                                    readonly
                                    type="number"
                                    placeholder="0.0"
                                    class="w-full text-right text-2xl font-semibold bg-transparent border-none outline-none text-card-foreground placeholder:text-muted-foreground/50" />
                                <span class="text-xs text-muted-foreground mt-1">
                                    ≈ ${{ (parseFloat(toAmount || '0') * (prices[toToken.symbol] || 0)).toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Swap Details -->
                    <div v-if="fromAmount && toAmount && fromToken && toToken" class="mt-4 p-4 bg-muted/30 rounded-xl space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Rate</span>
                            <span class="font-medium text-card-foreground">
                                1 {{ fromToken.symbol }} = {{ exchangeRate.toFixed(6) }} {{ toToken.symbol }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Price Impact</span>
                            <span :class="priceImpactClass" class="font-medium flex items-center gap-1">
                                <TrendingUpIcon v-if="priceImpact < 0.1" class="w-3 h-3" />
                                <TrendingDownIcon v-else class="w-3 h-3" />
                                {{ priceImpact.toFixed(2) }}%
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Minimum Received</span>
                            <span class="font-medium text-card-foreground">{{ minimumReceived }} {{ toToken.symbol }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Est. Gas Fee</span>
                            <span class="font-medium text-card-foreground">${{ estimatedGas.usd }}</span>
                        </div>

                        <!-- Route Details -->
                        <button
                            @click="showRouteDetails = !showRouteDetails"
                            class="w-full flex items-center justify-between py-2 text-left hover:opacity-70 transition-opacity">
                            <span class="text-muted-foreground flex items-center gap-2">
                                <LayersIcon class="w-4 h-4" />
                                Route
                            </span>
                            <ChevronDownIcon
                                :class="['w-4 h-4 text-muted-foreground transition-transform', showRouteDetails && 'rotate-180']" />
                        </button>

                        <div v-if="showRouteDetails" class="pl-6 space-y-2 pt-2 border-t border-border">
                            <div class="text-xs">
                                <div class="font-medium text-card-foreground mb-1">Best Route:</div>
                                <div class="text-muted-foreground">{{ swapRoute.primary }}</div>
                                <div class="text-muted-foreground mt-1">{{ swapRoute.pools.join(' • ') }}</div>
                            </div>

                            <div class="text-xs">
                                <div class="font-medium text-card-foreground mb-1">Alternative Routes:</div>
                                <div v-for="(alt, idx) in swapRoute.alternatives" :key="idx" class="text-muted-foreground">
                                    • {{ alt }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Swap Button -->
                    <button
                        @click="executeSwap"
                        :disabled="!canSwap && isWalletConnected"
                        :class="[
                            'w-full mt-6 py-4 rounded-xl font-bold text-lg transition-all',
                            canSwap || !isWalletConnected
                                ? 'bg-primary hover:opacity-90 text-primary-foreground'
                                : 'bg-muted text-muted-foreground cursor-not-allowed'
                        ]">
                        <span v-if="isSwapping || isApproving" class="flex items-center justify-center gap-2">
                            <RefreshCwIcon class="w-5 h-5 animate-spin" />
                            {{ swapButtonText }}
                        </span>
                        <span v-else>{{ swapButtonText }}</span>
                    </button>

                    <!-- Warning Messages -->
                    <div v-if="priceImpact > 3" class="mt-4 p-3 bg-destructive/10 border border-destructive/30 rounded-lg flex items-start gap-2">
                        <AlertCircleIcon class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
                        <div class="text-sm">
                            <p class="font-semibold text-destructive">High Price Impact Warning</p>
                            <p class="text-muted-foreground mt-1">This swap has a price impact of {{ priceImpact.toFixed(2) }}%. Consider splitting into smaller trades.</p>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="mt-6">
                    <button
                        @click="showHistory = !showHistory"
                        class="w-full flex items-center justify-between p-4 bg-card border border-border rounded-xl hover:bg-muted transition-colors">
                        <span class="font-semibold text-card-foreground flex items-center gap-2">
                            <ClockIcon class="w-5 h-5" />
                            Recent Transactions
                        </span>
                        <ChevronDownIcon
                            :class="['w-5 h-5 text-muted-foreground transition-transform', showHistory && 'rotate-180']" />
                    </button>

                    <div v-if="showHistory" class="mt-2 bg-card border border-border rounded-xl overflow-hidden">
                        <div v-for="tx in transactionHistory" :key="tx.id" class="p-4 border-b border-border last:border-b-0 hover:bg-muted/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'w-8 h-8 rounded-full flex items-center justify-center',
                                        tx.status === 'success' ? 'bg-primary/10' : 'bg-destructive/10'
                                    ]">
                                        <CheckCircleIcon v-if="tx.status === 'success'" class="w-5 h-5 text-primary" />
                                        <XIcon v-else class="w-5 h-5 text-destructive" />
                                    </div>

                                    <div>
                                        <div class="font-medium text-card-foreground">
                                            {{ tx.amount }} {{ tx.from }} → {{ tx.to }}
                                        </div>
                                        <div class="text-xs text-muted-foreground flex items-center gap-2 mt-1">
                                            {{ tx.date }}
                                            <a :href="`https://etherscan.io/tx/${tx.hash}`" target="_blank" class="flex items-center gap-1 hover:text-primary transition-colors">
                                                {{ tx.hash }}
                                                <ExternalLinkIcon class="w-3 h-3" />
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <span :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    tx.status === 'success' ? 'bg-primary/10 text-primary' : 'bg-destructive/10 text-destructive'
                                ]">
                                    {{ tx.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Token Selection Modal (FROM) -->
                <Teleport to="body">
                    <div
                        v-if="isFromModalOpen"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                        @click.self="isFromModalOpen = false">
                        <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                            <!-- Modal Header -->
                            <div class="p-4 border-b border-border flex items-center justify-between">
                                <h3 class="text-lg font-bold text-card-foreground">Select Token</h3>
                                <button
                                    @click="isFromModalOpen = false"
                                    class="p-2 hover:bg-muted rounded-lg transition-colors">
                                    <XIcon class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>

                            <!-- Search -->
                            <div class="p-4 border-b border-border">
                                <div class="relative">
                                    <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search by name or symbol"
                                        class="w-full pl-10 pr-4 py-3 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="px-4 pt-2 flex gap-2 border-b border-border">
                                <button
                                    @click="activeTab = 'popular'"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium transition-colors relative',
                                        activeTab === 'popular'
                                            ? 'text-primary'
                                            : 'text-muted-foreground hover:text-card-foreground'
                                    ]">
                                    Popular
                                    <div v-if="activeTab === 'popular'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                                </button>
                                <button
                                    @click="activeTab = 'all'"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium transition-colors relative',
                                        activeTab === 'all'
                                            ? 'text-primary'
                                            : 'text-muted-foreground hover:text-card-foreground'
                                    ]">
                                    All Tokens
                                    <div v-if="activeTab === 'all'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                                </button>
                            </div>

                            <!-- Token List -->
                            <div class="max-h-96 overflow-y-auto">
                                <div
                                    v-for="token in activeTab === 'popular' ? popularTokens : filteredTokens"
                                    :key="token.symbol"
                                    @click="selectFromToken(token)"
                                    class="p-4 hover:bg-muted transition-colors cursor-pointer flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <img :src="token.logo" :alt="token.symbol" class="w-8 h-8 rounded-full" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2232%22 height=%2232%22%3E%3Ctext y=%2224%22 font-size=%2224%22%3E💎%3C/text%3E%3C/svg%3E'" />
                                        <div>
                                            <div class="font-semibold text-card-foreground">{{ token.symbol }}</div>
                                            <div class="text-xs text-muted-foreground">{{ token.name }}</div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="font-medium text-card-foreground">
                                            {{ (userBalances[token.symbol] || 0).toFixed(4) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            ${{ ((userBalances[token.symbol] || 0) * (prices[token.symbol] || 0)).toFixed(2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Custom Token -->
                            <div class="p-4 border-t border-border">
                                <button class="w-full py-3 bg-muted hover:bg-muted/80 text-muted-foreground rounded-lg font-medium text-sm transition-colors flex items-center justify-center gap-2">
                                    <InfoIcon class="w-4 h-4" />
                                    Add Custom Token by Address
                                </button>
                            </div>
                        </div>
                    </div>
                </Teleport>

                <!-- Token Selection Modal (TO) -->
                <Teleport to="body">
                    <div
                        v-if="isToModalOpen"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                        @click.self="isToModalOpen = false">
                        <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                            <!-- Modal Header -->
                            <div class="p-4 border-b border-border flex items-center justify-between">
                                <h3 class="text-lg font-bold text-card-foreground">Select Token</h3>
                                <button
                                    @click="isToModalOpen = false"
                                    class="p-2 hover:bg-muted rounded-lg transition-colors">
                                    <XIcon class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>

                            <!-- Search -->
                            <div class="p-4 border-b border-border">
                                <div class="relative">
                                    <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search by name or symbol"
                                        class="w-full pl-10 pr-4 py-3 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="px-4 pt-2 flex gap-2 border-b border-border">
                                <button
                                    @click="activeTab = 'popular'"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium transition-colors relative',
                                        activeTab === 'popular'
                                            ? 'text-primary'
                                            : 'text-muted-foreground hover:text-card-foreground'
                                    ]">
                                    Popular
                                    <div v-if="activeTab === 'popular'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                                </button>
                                <button
                                    @click="activeTab = 'all'"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium transition-colors relative',
                                        activeTab === 'all'
                                            ? 'text-primary'
                                            : 'text-muted-foreground hover:text-card-foreground'
                                    ]">
                                    All Tokens
                                    <div v-if="activeTab === 'all'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                                </button>
                            </div>

                            <!-- Token List -->
                            <div class="max-h-96 overflow-y-auto">
                                <div
                                    v-for="token in activeTab === 'popular' ? popularTokens : filteredTokens"
                                    :key="token.symbol"
                                    @click="selectToToken(token)"
                                    class="p-4 hover:bg-muted transition-colors cursor-pointer flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <img :src="token.logo" :alt="token.symbol" class="w-8 h-8 rounded-full" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2232%22 height=%2232%22%3E%3Ctext y=%2224%22 font-size=%2224%22%3E💎%3C/text%3E%3C/svg%3E'" />
                                        <div>
                                            <div class="font-semibold text-card-foreground">{{ token.symbol }}</div>
                                            <div class="text-xs text-muted-foreground">{{ token.name }}</div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="font-medium text-card-foreground">
                                            {{ (userBalances[token.symbol] || 0).toFixed(4) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            ${{ ((userBalances[token.symbol] || 0) * (prices[token.symbol] || 0)).toFixed(2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Custom Token -->
                            <div class="p-4 border-t border-border">
                                <button class="w-full py-3 bg-muted hover:bg-muted/80 text-muted-foreground rounded-lg font-medium text-sm transition-colors flex items-center justify-center gap-2">
                                    <InfoIcon class="w-4 h-4" />
                                    Add Custom Token by Address
                                </button>
                            </div>
                        </div>
                    </div>
                </Teleport>
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style scoped>
    /* Custom animations */
    @keyframes fade-in {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes zoom-in {
        from {
            transform: scale(0.95);
        }
        to {
            transform: scale(1);
        }
    }

    .animate-in {
        animation: fade-in 0.2s ease-out, zoom-in 0.2s ease-out;
    }

    /* Hide number input spinners */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }

    /* Smooth transitions */
    * {
        transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: hsl(var(--muted));
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: hsl(var(--muted-foreground) / 0.3);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--muted-foreground) / 0.5);
    }

    /* Focus styles for accessibility */
    button:focus-visible,
    input:focus-visible,
    select:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }

    /* Loading animation */
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>
