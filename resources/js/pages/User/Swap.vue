<script setup lang="ts">
    import { ref, computed } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import PortfolioCard from '@/components/layout/user/swap/PortfolioCard.vue';
    import TopHoldingsCard from '@/components/layout/user/swap/TopHoldingsCard.vue';
    import QuickStatsCard from '@/components/layout/user/swap/QuickStatsCard.vue';
    import WalletConnection from '@/components/layout/user/swap/WalletConnection.vue';
    import SwapCard from '@/components/layout/user/swap/swapCard.vue';
    import MarketInfoCard from '@/components/layout/user/swap/MarketInfoCard.vue';
    import TransactionHistoryCard from '@/components/layout/user/swap/TransactionHistoryCard.vue';
    import GasTrackerCard from '@/components/layout/user/swap/GasTrackerCard.vue';

    const props = defineProps<{
        tokens: Array<{
            symbol: string;
            name: string;
            address: string;
            logo: string;
            decimals: number;
            chain: string;
            price_change_24h: number;
        }>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        transactionHistory: Array<{
            id: number;
            date: string;
            from: string;
            to: string;
            amount: string;
            status: 'success' | 'failed' | 'pending';
            hash: string;
        }>;
        portfolioChange24h: number;
        gasPrices: Record<string, { gwei: number; time: string; usd: number }>;
        popularTokens: string[];
    }>();

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
        { label: 'Swap Crypto' },
    ];

    const isWalletConnected = ref(false);
    const walletAddress = ref('');
    const selectedChain = ref('Ethereum');
    const fromToken = ref(props.tokens[0] || null);
    const toToken = ref(props.tokens[1] || null);
    const fromAmount = ref('');
    const toAmount = ref('');
    const slippage = ref(0.5);
    const deadline = ref(20);
    const gasPreset = ref<'low' | 'medium' | 'high'>('medium');
    const isSwapping = ref(false);
    const needsApproval = ref(true);
    const isApproving = ref(false);
    const errorMessage = ref('');

    const totalPortfolioValue = computed(() => {
        if (!props.userBalances || !props.prices) return 0;
        return Object.keys(props.userBalances).reduce((total, symbol) => {
            const balance = props.userBalances[symbol] || 0;
            const price = props.prices[symbol] || 0;
            return total + balance * price;
        }, 0);
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

            <!-- Three Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-6">
                <!-- Left Column - Portfolio & Stats -->
                <div class="lg:col-span-3 space-y-4">
                    <PortfolioCard :portfolio-value="totalPortfolioValue" :portfolio-change24h="props.portfolioChange24h" />
                    <TopHoldingsCard :tokens="props.tokens" :user-balances="props.userBalances" :prices="props.prices" />
                    <QuickStatsCard :transaction-count="props.transactionHistory.length" :slippage="slippage" />
                </div>

                <!-- Center Column - Swap Interface -->
                <div class="lg:col-span-6">
                    <WalletConnection v-model:is-wallet-connected="isWalletConnected" v-model:wallet-address="walletAddress" />

                    <SwapCard
                        :tokens="props.tokens"
                        :user-balances="props.userBalances"
                        :prices="props.prices"
                        :gas-prices="props.gasPrices"
                        :popular-tokens="props.popularTokens"
                        v-model:from-token="fromToken"
                        v-model:to-token="toToken"
                        v-model:from-amount="fromAmount"
                        v-model:to-amount="toAmount"
                        v-model:is-swapping="isSwapping"
                        v-model:needs-approval="needsApproval"
                        v-model:is-approving="isApproving"
                        v-model:error-message="errorMessage"
                        v-model:slippage="slippage"
                        v-model:deadline="deadline"
                        v-model:gas-preset="gasPreset"
                        v-model:selected-chain="selectedChain"

                        :is-wallet-connected="isWalletConnected"
                        :wallet-address="walletAddress"
                    />
                </div>

                <div class="md:col-span-4 lg:col-span-3 space-y-4">
                    <MarketInfoCard :from-token="fromToken" :to-token="toToken" :prices="props.prices" />
                    <TransactionHistoryCard :transactions="props.transactionHistory" />
                    <GasTrackerCard :gas-prices="props.gasPrices" :selected-preset="gasPreset" @update:preset="gasPreset = $event" />
                </div>
            </div>
        </div>

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="closeNotificationsModal"
        />
    </AppLayout>
</template>

<style scoped>
    /* Ensure minimum width for small screens */
    @media (max-width: 320px) {
        .min-h-screen {
            min-width: 320px;
        }
    }
</style>
