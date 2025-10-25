<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Head, usePage, router } from '@inertiajs/vue3';
    import axios from 'axios';
    import { route } from 'ziggy-js';

    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import PortfolioSummary from '@/components/layout/user/receive/PortfolioSummary.vue';
    import TokenSelector from '@/components/layout/user/receive/TokenSelector.vue';
    import InfoPanels from '@/components/layout/user/receive/InfoPanels.vue';
    import TransactionHistory from '@/components/layout/user/receive/TransactionHistory.vue';
    import WalletModal from '@/components/layout/user/receive/WalletModal.vue';

    // Define types for props
    type Token = {
        symbol: string;
        name: string;
        address: string;
        logo: string;
        decimals: number;
        price_change_24h: number;
    };
    type Transaction = {
        id: number;
        token_symbol: string;
        wallet_address: string;
        amount: string | null;
        status: 'pending' | 'completed' | 'failed';
        transaction_hash: string | null;
        created_at: string;
        updated_at: string;
    };

    // Props from Inertia
    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        portfolioChange24h: number;
        popularTokens: string[];
        pendingTransactions: Array<Transaction>;
    }>();

    // State Management
    const selectedToken = ref<Token | null>(null);
    const isWalletModalOpen = ref(false);
    const isCreatingTransaction = ref(false);
    const transactionError = ref<string | null>(null);

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
    const notificationCount = computed(() => page.props.auth?.notification_count || 0);
    const isNotificationsModalOpen = ref(false);

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Receive Crypto' }
    ];

    // Computed properties to pass down to children
    const totalPortfolioValue = computed(() => {
        if (!props.userBalances || !props.prices) return 0;
        return Object.keys(props.userBalances).reduce((total, symbol) => {
            return total + ((props.userBalances[symbol] || 0) * (props.prices[symbol] || 0));
        }, 0);
    });

    const topTokens = computed(() => {
        if (!props.tokens || !props.userBalances || !props.prices) return [];
        return props.tokens
            .map(token => ({
                ...token,
                balance: props.userBalances[token.symbol] || 0,
                value: (props.userBalances[token.symbol] || 0) * (props.prices[token.symbol] || 0)
            }))
            .filter(token => token.balance > 0)
            .slice(0, 5);
    });

    const totalAssets = computed(() => {
        if (!props.userBalances) return 0;
        return Object.keys(props.userBalances).filter(symbol => props.userBalances[symbol] > 0).length;
    });

    // Methods to handle events from children
    const handleTokenSelected = (token: Token) => {
        selectedToken.value = token;
        transactionError.value = null; // Clear any previous errors
        isWalletModalOpen.value = true;
    };

    const handleCloseModal = () => {
        isWalletModalOpen.value = false;
        transactionError.value = null; // Clear error when closing
    };

    const handleCreatePendingTransaction = async () => {
        if (!selectedToken.value || isCreatingTransaction.value) return;
        isCreatingTransaction.value = true;
        transactionError.value = null;

        try {
            await axios.post(route('user.receive.store'), {
                token_symbol: selectedToken.value.symbol,
                wallet_address: selectedToken.value.address,
            });
            router.reload({ only: ['pendingTransactions'] });
            isWalletModalOpen.value = false;
        } catch (error: any) {
            console.error('Failed to create pending transaction:', error);

            // Extract error message from various error formats
            if (error.response?.data?.message) {
                transactionError.value = error.response.data.message;
            } else if (error.response?.data?.error) {
                transactionError.value = error.response.data.error;
            } else if (error.message) {
                transactionError.value = error.message;
            } else {
                transactionError.value = 'An unexpected error occurred while creating the transaction.';
            }
        } finally {
            isCreatingTransaction.value = false;
        }
    };
</script>

<template>
    <Head title="Receive Crypto" />
    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="isNotificationsModalOpen = true"
            />

            <div class="grid grid-cols-1 lg:grid-cols-12 lg:gap-6 mt-6">
                <div class="lg:col-span-3 space-y-4">
                    <PortfolioSummary
                        :total-portfolio-value="totalPortfolioValue"
                        :portfolio-change24h="props.portfolioChange24h"
                        :top-tokens="topTokens"
                        :total-assets="totalAssets"
                        :available-tokens-count="props.tokens?.length || 0"
                    />
                </div>

                <div class="lg:col-span-6">
                    <TokenSelector
                        :tokens="props.tokens"
                        :user-balances="props.userBalances"
                        :prices="props.prices"
                        :popular-tokens-list="props.popularTokens"
                        @token-selected="handleTokenSelected"
                    />
                </div>

                <div class="lg:col-span-3 space-y-4">
                    <InfoPanels />
                    <TransactionHistory :transactions="props.pendingTransactions" />
                </div>
            </div>
        </div>

        <WalletModal
            :is-open="isWalletModalOpen"
            :token="selectedToken"
            :balance="selectedToken ? (props.userBalances[selectedToken.symbol] || 0) : 0"
            :price="selectedToken ? (props.prices[selectedToken.symbol] || 0) : 0"
            :error="transactionError"
            @close="handleCloseModal"
            @create-pending-transaction="handleCreatePendingTransaction"
        />

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />
    </AppLayout>
</template>
