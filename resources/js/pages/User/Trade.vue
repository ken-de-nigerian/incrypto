<script setup lang="ts">
    import { computed, onMounted, ref, nextTick, watch } from 'vue';
    import { Head, usePage, router } from '@inertiajs/vue3';
    import {
        ChevronDownIcon,
        DollarSignIcon,
        UsersIcon,
        XIcon,
        ZapIcon,
        RefreshCcwIcon,
        WalletIcon,
        BriefcaseIcon,
        PiggyBankIcon,
        BarChartIcon,
        GlobeIcon,
        ClockIcon,
        ArrowUpRightIcon,
        Loader2Icon,
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TextLink from '@/components/TextLink.vue';

    interface Token {
        symbol: string;
        name: string;
        logo: string;
        decimals: number;
        price_change_24h: number;
    }

    interface UserProfile {
        live_trading_balance: number;
        demo_trading_balance: number;
        trading_status: 'live' | 'demo';
    }

    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        portfolioChange24h: number;
        auth: {
            user: {
                profile: UserProfile;
            }
            notification_count: number;
        };
    }>();

    // --- State Management ---
    const isWalletConnected = ref(true);
    const isOrderProcessing = ref(false);
    const isNotificationsModalOpen = ref(false);
    const globalAlert = ref<{ message: string; type: 'success' | 'error' | ''; show: boolean }>({ message: '', type: '', show: false });

    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const userProfile = computed(() => user.value?.profile as UserProfile);
    const isLiveMode = ref(userProfile.value?.trading_status === 'live');
    const liveBalance = computed(() => userProfile.value?.live_trading_balance || 0.00);
    const demoBalance = computed(() => userProfile.value?.demo_trading_balance || 0.00);

    const fundingError = ref('');
    const withdrawalError = ref('');

    const showAlert = (message: string, type: 'success' | 'error') => {
        globalAlert.value = { message, type, show: true };
        setTimeout(() => {
            globalAlert.value = { message: '', type: '', show: false };
        }, 5000);
    };

    const updateTradingMode = (mode: 'live' | 'demo') => {
        if (isLiveMode.value === (mode === 'live')) return;

        isOrderProcessing.value = true;
        router.put(route('user.profile.update.trading.status'), { status: mode }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isLiveMode.value = mode === 'live';
                isOrderProcessing.value = false;
                showAlert(`Successfully switched to ${mode === 'live' ? 'Live Trading' : 'Demo Mode'}.`, 'success');
            },
            onError: (errors) => {
                console.error("Failed to change trading mode:", errors);
                showAlert(errors?.status || "Failed to change trading mode.", 'error');
                isOrderProcessing.value = false;
            }
        });
    };

    const currentPortfolioValue = computed(() => isLiveMode.value ? liveBalance.value : demoBalance.value);
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
        }).filter(h => h.balance > 0);
    });

    const cryptoHoldings = computed(() => holdings.value.filter(h => h.assetType === 'crypto'));

    const isFundingModalOpen = ref(false);
    const fundingAmount = ref<number | null>(1);
    const fundingSourceToken = ref('BTC');
    const fundingConversionRate = computed(() => pricesMap.value[fundingSourceToken.value] || 0);

    const isWithdrawalModalOpen = ref(false);
    const withdrawalAmountUSD = ref<number | null>(100);
    const withdrawalTargetToken = ref('BTC');
    const fundingTargetTokenRate = computed(() => pricesMap.value[withdrawalTargetToken.value] || 0);

    const notificationCount = computed(() => page.props.auth?.notification_count || 0);
    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Trading Hub' }
    ];

    const fundingSourceBalance = computed(() => holdings.value.find(h => h.symbol === fundingSourceToken.value)?.balance || 0);
    const estimatedUSDFunds = computed(() => {
        const amount = parseFloat(fundingAmount.value?.toString() || '0') || 0;
        return (amount * fundingConversionRate.value).toFixed(2);
    });

    const estimatedCryptoToReceive = computed(() => {
        const amountUSD = parseFloat(withdrawalAmountUSD.value?.toString() || '0') || 0;
        const rate = fundingTargetTokenRate.value;
        return rate > 0 ? (amountUSD / rate).toFixed(8) : '0.00000000';
    });

    const quickLinks = [
        { title: 'Forex Trading', icon: GlobeIcon, description: 'Trade major and exotic currency pairs with leverage.', route: route('user.dashboard') },
        { title: 'Stock Trading', icon: BarChartIcon, description: 'Invest in global stocks and ETFs.', route: route('user.dashboard') },
        { title: 'Crypto/Holdings', icon: WalletIcon, description: 'View and manage your crypto and fiat balances.', route: route('user.dashboard')},
        { title: 'Investments & Staking', icon: PiggyBankIcon, description: 'Explore fixed-term investment plans and earn APY.', route: route('user.dashboard') },
        { title: 'Copy Trading Network', icon: UsersIcon, description: 'Automatically copy top-performing traders.', route: route('user.dashboard') },
        { title: 'Trade History', icon: ClockIcon, description: 'Review all past trades and orders.', route: route('user.dashboard') },
    ];

    const validateFunding = () => {
        const sourceAmount = parseFloat(fundingAmount.value?.toString() || '0') || 0;
        let error = '';

        if (sourceAmount <= 0) {
            error = 'Amount must be greater than zero.';
        } else if (sourceAmount > fundingSourceBalance.value) {
            error = `Insufficient ${fundingSourceToken.value} balance. Max: ${fundingSourceBalance.value.toFixed(8)}`;
        } else if (fundingConversionRate.value <= 0) {
            error = `No market price available for ${fundingSourceToken.value}.`;
        }

        fundingError.value = error;
        return error === '';
    };

    const performFunding = async () => {
        if (!isLiveMode.value) {
            showAlert("You must be in Live Trading mode to fund the account.", 'error');
            return;
        }

        if (!validateFunding()) return;

        const amountUSD = parseFloat(estimatedUSDFunds.value);
        const sourceAmount = parseFloat(fundingAmount.value?.toString() || '0') || 0;

        isOrderProcessing.value = true;

        router.post(route('user.trade.fund.account'), {
            source_symbol: fundingSourceToken.value,
            source_amount: sourceAmount,
            estimated_funds: amountUSD,
        }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isOrderProcessing.value = false;
                isFundingModalOpen.value = false;
                fundingAmount.value = 1;
            },
            onError: (errors) => {
                fundingError.value = Object.values(errors)[0] || 'An unexpected error occurred.';
                showAlert(fundingError.value, 'error');
                isOrderProcessing.value = false;
            }
        });
    };

    const selectFundingToken = (symbol: string) => {
        fundingSourceToken.value = symbol;
        const maxAmount = fundingSourceBalance.value;
        fundingAmount.value = maxAmount > 0 ? Math.min(1, maxAmount) : 0.00000001;
        fundingError.value = '';
        nextTick(() => validateFunding());
    };

    const openWithdrawalModal = () => {
        if (!isLiveMode.value) {
            showAlert("Withdrawal is only available in Live Trading mode.", 'error');
            return;
        }

        isWithdrawalModalOpen.value = true;
        nextTick(() => {
            withdrawalAmountUSD.value = liveBalance.value;
            if (!cryptoHoldings.value.some(h => h.symbol === withdrawalTargetToken.value)) {
                withdrawalTargetToken.value = cryptoHoldings.value[0]?.symbol || 'BTC';
            }
            withdrawalError.value = '';
            validateWithdrawal();
        });
    };

    const selectWithdrawalToken = (symbol: string) => {
        withdrawalTargetToken.value = symbol;
        withdrawalError.value = '';
        nextTick(() => validateWithdrawal());
    };

    const validateWithdrawal = () => {
        const amountUSD = parseFloat(withdrawalAmountUSD.value?.toString() || '0') || 0;
        let error = '';

        if (amountUSD <= 0.01) {
            error = 'Amount must be greater than $0.01.';
        } else if (amountUSD > liveBalance.value) {
            error = `Insufficient Live Balance. Max: $${liveBalance.value.toFixed(2)}`;
        } else if (fundingTargetTokenRate.value <= 0) {
            error = `No market price available for ${withdrawalTargetToken.value}.`;
        }

        withdrawalError.value = error;
        return error === '';
    };

    const performWithdrawal = async () => {
        if (!validateWithdrawal()) return;

        const amountUSD = parseFloat(withdrawalAmountUSD.value?.toString() || '0') || 0;
        const amountCrypto = estimatedCryptoToReceive.value;

        isOrderProcessing.value = true;

        router.post(route('user.trade.withdraw.account'), {
            target_symbol: withdrawalTargetToken.value,
            usd_amount: amountUSD,
            estimated_crypto: amountCrypto
        }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isOrderProcessing.value = false;
                isWithdrawalModalOpen.value = false;
                withdrawalAmountUSD.value = 100;
            },
            onError: (errors) => {
                withdrawalError.value = Object.values(errors)[0] || 'An unexpected error occurred.';
                showAlert(withdrawalError.value, 'error');
                isOrderProcessing.value = false;
            }
        });
    };

    watch([isFundingModalOpen, isWithdrawalModalOpen], ([fundingOpen, withdrawalOpen]) => {
        document.body.style.overflow = fundingOpen || withdrawalOpen ? 'hidden' : '';
    });

    onMounted(() => {
        const initialCryptoSymbol = cryptoHoldings.value[0]?.symbol || 'BTC';
        fundingSourceToken.value = initialCryptoSymbol;
        withdrawalTargetToken.value = initialCryptoSymbol;
        const maxFundAmount = fundingSourceBalance.value;
        fundingAmount.value = maxFundAmount > 0 ? Math.min(1, maxFundAmount) : 0.00000001;
        withdrawalAmountUSD.value = liveBalance.value;
    });
</script>

<template>
    <Head title="Trading Hub Dashboard" />

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
                    <button @click="globalAlert.show = false" class="ml-auto p-1 hover:bg-black/10 rounded">
                        <XIcon class="w-4 h-4" />
                    </button>
                </div>
            </Transition>

            <div class="grid grid-cols-1 gap-6 mt-6">
                <div class="bg-card border border-border rounded-2xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <h2 class="text-xl font-semibold text-muted-foreground mb-1">Trading Balance</h2>
                        <div class="flex items-end gap-3">
                            <span class="text-3xl sm:text-4xl font-extrabold text-card-foreground">
                                ${{ currentPortfolioValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
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
                                @click="isFundingModalOpen = true; fundingError = ''"
                                class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-xl text-sm font-semibold transition-colors hover:bg-primary/90"
                                :disabled="isOrderProcessing">
                                <WalletIcon class="w-4 h-4" />
                                Deposit
                            </button>

                            <button
                                v-if="isLiveMode"
                                @click="openWithdrawalModal"
                                class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold transition-colors hover:bg-muted"
                                :disabled="isOrderProcessing">
                                <DollarSignIcon class="w-4 h-4" />
                                Withdraw
                            </button>

                            <div v-if="!isLiveMode" class="flex-1 md:flex-none h-10 w-full"></div>
                        </div>

                        <div class="relative inline-flex rounded-xl p-1 shadow-inner w-full sm:w-auto" :class="isLiveMode ? 'bg-primary/20' : 'bg-muted/50'">
                            <button
                                @click="updateTradingMode('live')"
                                :disabled="isOrderProcessing"
                                :class="['flex-1 sm:flex-none px-3 py-1.5 text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-1', isLiveMode ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted/70', isOrderProcessing ? 'opacity-70 cursor-wait' : '']">
                                <ZapIcon class="w-4 h-4" />
                                Live
                            </button>

                            <button
                                @click="updateTradingMode('demo')"
                                :disabled="isOrderProcessing"
                                :class="['flex-1 sm:flex-none px-3 py-1.5 text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-1', !isLiveMode ? 'bg-background text-card-foreground' : 'text-muted-foreground hover:bg-muted/70', isOrderProcessing ? 'opacity-70 cursor-wait' : '']">
                                <DollarSignIcon class="w-4 h-4" />
                                Demo
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-card-foreground mb-4">Quick Trade Navigation</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        <TextLink
                            v-for="link in quickLinks"
                            :key="link.title"
                            :href="link.route"
                            class="bg-card border border-border rounded-2xl p-4 sm:p-5 hover:border-primary/50 transition-all duration-200 block group">
                            <component :is="link.icon" class="w-6 h-6 sm:w-7 sm:h-7 text-primary mb-2 sm:mb-3 group-hover:scale-110 transition-transform" />
                            <h4 class="text-base sm:text-lg font-bold text-card-foreground mb-1">{{ link.title }}</h4>
                            <p class="text-xs sm:text-sm text-muted-foreground mb-3">{{ link.description }}</p>
                            <span class="flex items-center text-sm font-semibold text-primary group-hover:underline">
                                Go <ChevronDownIcon class="w-4 h-4 ml-1 rotate-[-90deg] group-hover:rotate-0 transition-transform" />
                            </span>
                        </TextLink>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="isFundingModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4 bg-black/50 backdrop-blur-sm" @click.self="isFundingModalOpen = false">
                <div class="w-full h-full sm:h-auto sm:max-w-md bg-card border border-border rounded-none sm:rounded-2xl overflow-y-auto">
                    <div class="p-4 border-b border-border flex items-center justify-between flex-shrink-0">
                        <h3 class="text-lg font-bold text-card-foreground">Fund Live Trading Account</h3>
                        <button @click="isFundingModalOpen = false" class="p-2 hover:bg-muted rounded-lg">
                            <XIcon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div v-if="fundingError" class="p-3 rounded-lg bg-red-100 border border-red-400 text-red-700 text-sm font-semibold flex items-center gap-2">
                            <XIcon class="w-4 h-4" />
                            {{ fundingError }}
                        </div>

                        <div class="bg-muted/30 p-3 rounded-lg space-y-2">
                            <p class="text-sm font-semibold text-card-foreground flex items-center gap-2">
                                <BriefcaseIcon class="w-4 h-4 text-primary" />
                                Current Live Portfolio Value: <span class="text-primary">${{ liveBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Increase your margin by **converting your available crypto holdings** instantly into USD fiat for live trading.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-card-foreground">1. Select Source Crypto to Convert:</h4>
                            <div class="flex gap-3 overflow-x-auto pb-2 -mx-6 px-6">
                                <button
                                    v-for="holding in cryptoHoldings"
                                    :key="holding.symbol"
                                    @click="selectFundingToken(holding.symbol)"
                                    :class="['flex-shrink-0 p-3 rounded-lg text-xs border transition-all', fundingSourceToken === holding.symbol ? 'bg-primary border-primary text-primary-foreground' : 'bg-background border-border text-muted-foreground hover:bg-muted/50']">
                                    {{ holding.symbol }} ({{ holding.balance.toFixed(4) }})
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-card-foreground">2. Amount to Convert:</h4>
                            <div class="relative">
                                <input
                                    v-model.number.lazy="fundingAmount" type="number"
                                    step="any"
                                    min="0.00000001"
                                    :max="fundingSourceBalance"
                                    placeholder="Amount"
                                    class="w-full p-3 pr-20 bg-muted/50 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all"
                                    :class="{ 'border-destructive ring-destructive': fundingError }"
                                    @change="validateFunding" />
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

                        <button
                            :disabled="!isWalletConnected || isOrderProcessing || !validateFunding()"
                            @click="performFunding"
                            :class="['w-full py-3 font-bold rounded-lg transition-opacity text-sm flex items-center justify-center gap-2', !isWalletConnected || isOrderProcessing || !validateFunding() ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                            <Loader2Icon v-if="isOrderProcessing" class="w-4 h-4 animate-spin" />
                            <span>{{ isOrderProcessing ? 'Converting...' : `Convert ${fundingSourceToken} & Fund Live Account` }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="isWithdrawalModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4 bg-black/50 backdrop-blur-sm" @click.self="isWithdrawalModalOpen = false">
                <div class="w-full h-full sm:h-auto sm:max-w-md bg-card border border-border rounded-none sm:rounded-2xl overflow-y-auto">
                    <div class="p-4 border-b border-border flex items-center justify-between flex-shrink-0">
                        <h3 class="text-lg font-bold text-card-foreground">Withdraw / Convert to Crypto</h3>
                        <button @click="isWithdrawalModalOpen = false" class="p-2 hover:bg-muted rounded-lg">
                            <XIcon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div v-if="withdrawalError" class="p-3 rounded-lg bg-red-100 border border-red-400 text-red-700 text-sm font-semibold flex items-center gap-2">
                            <XIcon class="w-4 h-4" />
                            {{ withdrawalError }}
                        </div>

                        <div class="bg-primary/20 p-3 rounded-lg space-y-2">
                            <p class="text-sm font-semibold text-card-foreground flex items-center gap-2">
                                <BriefcaseIcon class="w-4 h-4 text-primary" />
                                Available USD Value: <span class="text-primary">${{ liveBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Convert your available USD value into your preferred crypto holding.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-card-foreground">1. Amount of USD to Convert:</h4>
                            <div class="relative">
                                <input
                                    v-model.number.lazy="withdrawalAmountUSD" type="number"
                                    step="any"
                                    min="0.01"
                                    :max="liveBalance"
                                    placeholder="Amount in USD"
                                    class="w-full p-3 pr-20 bg-muted/50 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-primary transition-all"
                                    :class="{ 'border-destructive ring-destructive': withdrawalError }"
                                    @change="validateWithdrawal" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">USD</span>
                            </div>
                            <button @click="withdrawalAmountUSD = liveBalance; validateWithdrawal();" class="text-xs font-medium text-primary hover:underline">Use Max ($ {{ liveBalance.toFixed(2) }})</button>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-card-foreground">2. Select Target Crypto:</h4>
                            <div class="flex gap-3 overflow-x-auto pb-2 -mx-6 px-6">
                                <button
                                    v-for="holding in cryptoHoldings"
                                    :key="holding.symbol"
                                    @click="selectWithdrawalToken(holding.symbol)"
                                    :class="['flex-shrink-0 p-3 rounded-lg text-xs border transition-all', withdrawalTargetToken === holding.symbol ? 'bg-primary border-primary text-primary-foreground' : 'bg-background border-border text-muted-foreground hover:bg-muted/50']">
                                    {{ withdrawalTargetToken === holding.symbol ? 'Selected' : holding.symbol }}
                                </button>
                            </div>
                            <div class="flex justify-between items-center text-xs text-muted-foreground bg-muted/50 p-2 rounded-lg">
                                <span>Current {{ withdrawalTargetToken }} Balance: {{ holdings.find(h => h.symbol === withdrawalTargetToken)?.balance.toFixed(4) || '0.0000' }}</span>
                                <span>Rate: 1 {{ withdrawalTargetToken }} = ${{ fundingTargetTokenRate.toLocaleString() }} USD</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-secondary border border-border rounded-lg">
                            <div class="text-sm font-medium text-card-foreground flex items-center gap-2">
                                <ArrowUpRightIcon class="w-4 h-4 text-primary" />
                                Estimated {{ withdrawalTargetToken }} to Receive:
                            </div>
                            <span class="text-lg font-bold text-primary">{{ estimatedCryptoToReceive }} {{ withdrawalTargetToken }}</span>
                        </div>

                        <button
                            :disabled="!isWalletConnected || isOrderProcessing || !validateWithdrawal()"
                            @click="performWithdrawal"
                            :class="['w-full py-3 font-bold rounded-lg transition-opacity text-sm flex items-center justify-center gap-2', !isWalletConnected || isOrderProcessing || !validateWithdrawal() ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                            <Loader2Icon v-if="isOrderProcessing" class="w-4 h-4 animate-spin" />
                            <span>{{ isOrderProcessing ? 'Processing Conversion...' : `Convert to ${withdrawalTargetToken} & Withdraw` }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

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
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
    }
    ::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }
    @media (min-width: 1024px) {
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
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
    button:focus-visible,
    input:focus-visible,
    select:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }
</style>
