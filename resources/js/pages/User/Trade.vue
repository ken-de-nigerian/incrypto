<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import {
        ChevronDownIcon,
        DollarSignIcon,
        UsersIcon,
        XIcon,
        WalletIcon,
        PiggyBankIcon,
        BarChartIcon,
        GlobeIcon,
        ClockIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TextLink from '@/components/TextLink.vue';
    import TradingModeSwitcher from '@/components/TradingModeSwitcher.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';

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
        auth: {
            user: {
                profile: UserProfile;
            }
            notification_count: number;
        };
    }>();

    // --- State Management ---
    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const globalAlert = ref<{ message: string; type: 'success' | 'error' | ''; show: boolean }>({ message: '', type: '', show: false });

    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const userProfile = computed(() => user.value?.profile as UserProfile);
    const isLiveMode = ref(userProfile.value?.trading_status === 'live');
    const liveBalance = computed(() => userProfile.value?.live_trading_balance || 0.00);
    const demoBalance = computed(() => userProfile.value?.demo_trading_balance || 0.00);

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
        { label: 'Trading' }
    ];

    const quickLinks = [
        { title: 'Forex Trading', icon: GlobeIcon, description: 'Trade major and exotic currency pairs with leverage.', route: route('user.trade.forex') },
        { title: 'Stock Trading', icon: BarChartIcon, description: 'Invest in global stocks and ETFs.', route: route('user.trade.stock') },
        { title: 'Crypto/Holdings', icon: WalletIcon, description: 'View and manage your crypto and fiat balances.', route: route('user.trade.crypto') },
        { title: 'Investments & Staking', icon: PiggyBankIcon, description: 'Explore fixed-term investment plans and earn APY.', route: route('user.trade.investment') },
        { title: 'Copy Trading Network', icon: UsersIcon, description: 'Automatically copy top-performing traders.', route: route('user.trade.network') },
        { title: 'Trade History', icon: ClockIcon, description: 'Review all past trades and orders.', route: route('user.trade.history') },
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

    watch([isFundingModalOpen, isWithdrawalModalOpen], ([fundingOpen, withdrawalOpen]) => {
        document.body.style.overflow = fundingOpen || withdrawalOpen ? 'hidden' : '';
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
                <!-- Trading Balance Card -->
                <div class="bg-card border border-border rounded-2xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <h2 class="text-xl font-semibold text-muted-foreground mb-1">Trading Balance</h2>
                        <div class="flex items-end gap-3">
                            <span class="text-3xl sm:text-4xl font-extrabold text-card-foreground">
                                ${{ (isLiveMode ? liveBalance : demoBalance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
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
                                class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-xl text-sm font-semibold transition-colors hover:bg-primary/90 cursor-pointer">
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
                            @show-alert="showAlert"
                        />
                    </div>
                </div>

                <!-- Quick Trade Navigation -->
                <div class="margin-bottom">
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

        <!-- Reusable Components -->
        <FundingModal
            :is-open="isFundingModalOpen"
            :live-balance="liveBalance"
            :crypto-holdings="cryptoHoldings"
            :prices-map="pricesMap"
            @close="isFundingModalOpen = false"
            @show-alert="showAlert" />

        <WithdrawalModal
            :is-open="isWithdrawalModalOpen"
            :live-balance="liveBalance"
            :crypto-holdings="cryptoHoldings"
            :prices-map="pricesMap"
            @close="isWithdrawalModalOpen = false"
            @show-alert="showAlert" />

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

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
