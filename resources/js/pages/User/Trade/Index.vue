<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import {
        ChevronDownIcon,
        UsersIcon,
        WalletIcon,
        PiggyBankIcon,
        BarChartIcon,
        GlobeIcon, ReceiptIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TextLink from '@/components/TextLink.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
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

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);

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

    const notificationCount = computed(() => page.props.auth?.notification_count || 0);
    const currentBalance = computed(() => isLiveMode.value ? liveBalance.value : demoBalance.value);

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
        { title: 'Crypto Trading', icon: WalletIcon, description: 'Trade popular cryptocurrencies.', route: route('user.trade.crypto') },
        { title: 'Investments & Staking', icon: PiggyBankIcon, description: 'Explore fixed-term investment plans and earn APY.', route: route('user.trade.investment') },
        { title: 'Copy Trading Network', icon: UsersIcon, description: 'Automatically copy top-performing traders.', route: route('user.trade.network') },
        { title: 'Transaction History', icon: ReceiptIcon, description: 'View your trading history and past transactions.', route: route('user.transactions.index') },
    ];

    const handleFundingClick = () => {
        if (!isLiveMode.value) {
            return;
        }
        isFundingModalOpen.value = true;
    };

    const handleWithdrawalClick = () => {
        if (!isLiveMode.value) {
            return;
        }
        isWithdrawalModalOpen.value = true;
    };

    watch([isFundingModalOpen, isWithdrawalModalOpen], ([fundingOpen, withdrawalOpen]) => {
        document.body.style.overflow = fundingOpen || withdrawalOpen ? 'hidden' : '';
    });
</script>

<template>
    <Head title="Trading Hub" />

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

            <!-- Quick Trade Navigation -->
            <div class="mt-6 mb-8 sm:mb-0">
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

        <!-- Reusable Components -->
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
