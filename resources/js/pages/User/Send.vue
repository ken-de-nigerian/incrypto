<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage, router } from '@inertiajs/vue3';
    import axios from 'axios';

    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import PortfolioSummary from '@/components/layout/user/send/PortfolioSummary.vue';
    import SendForm from '@/components/layout/user/send/SendForm.vue';
    import InfoPanels from '@/components/layout/user/send/InfoPanels.vue';
    import TransactionHistory from '@/components/layout/user/send/TransactionHistory.vue';
    import ConfirmationModal from '@/components/layout/user/send/ConfirmationModal.vue';

    type Token = {
        symbol: string; name: string; address: string; logo: string;
        decimals: number; price_change_24h: number;
    };
    type Transaction = {
        id: number; token_symbol: string; recipient_address: string; amount: string;
        status: 'pending' | 'completed' | 'failed'; transaction_hash: string | null;
        fee: string | null; created_at: string; updated_at: string;
    };

    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        portfolioChange24h: number;
        sentTransactions: Array<Transaction>;
        networkFee: number;
    }>();

    const isConfirmModalOpen = ref(false);
    const isSending = ref(false);
    const transactionDetails = ref<object | null>(null);
    const message = ref<{ type: 'error'; text: string; } | null>(null);

    const sendFormRef = ref(null);

    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const initials = computed(() => user.value ? `${user.value.first_name?.charAt(0) || ''}${user.value.last_name?.charAt(0) || ''}`.toUpperCase() : '');
    const isNotificationsModalOpen = ref(false);
    const notificationCount = computed(() => page.props.auth?.notification_count || 0);
    const breadcrumbItems = [ { label: 'Dashboard', href: route('user.dashboard') }, { label: 'Send Crypto' } ];

    const totalPortfolioValue = computed(() => {
        if (!props.userBalances || !props.prices) return 0;
        return Object.keys(props.userBalances).reduce((total, symbol) => {
            return Number(total + ((props.userBalances[symbol] || 0) * (props.prices[symbol] || 0)));
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

    const availableAssets = computed(() => {
        if (!props.tokens || !props.userBalances) return [];
        return props.tokens
            .map(token => ({
                ...token,
                balance: props.userBalances[token.symbol] || 0,
                value: (props.userBalances[token.symbol] || 0) * (props.prices[token.symbol] || 0),
                price: props.prices[token.symbol] || 0
            }));
    });

    const ethBalance = computed(() => props.userBalances['ETH'] || 0);

    const handleReviewTransaction = (details: object) => {
        transactionDetails.value = details;
        isConfirmModalOpen.value = true;
    };

    const handleSendCrypto = async () => {
        if (!transactionDetails.value || isSending.value) return;

        isSending.value = true;
        message.value = null;

        try {
            await axios.post(route('user.send.store'), transactionDetails.value);
            closeConfirmModal();
            router.reload({ only: ['userBalances', 'sentTransactions'] });

            if (sendFormRef.value && typeof sendFormRef.value.resetForm === 'function') {
                sendFormRef.value.resetForm();
            }
        } catch (error: any) {
            message.value = {
                type: 'error',
                text: error.response?.data?.message || 'An unknown error occurred while sending the transaction.',
            };
        } finally {
            isSending.value = false;
        }
    };

    const closeConfirmModal = () => {
        isConfirmModalOpen.value = false;
        setTimeout(() => {
            isSending.value = false;
            message.value = null;
            transactionDetails.value = null;
        }, 300);
    };

    watch(isConfirmModalOpen, (isOpen) => {
        document.body.style.overflow = isOpen ? 'hidden' : '';
    });
</script>

<template>
    <Head title="Send Crypto" />
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
                        :available-assets-count="availableAssets.length"
                    />
                </div>

                <div class="lg:col-span-6">
                    <SendForm
                        ref="sendFormRef"
                        :available-assets="availableAssets"
                        :prices="props.prices"
                        :network-fee="props.networkFee"
                        :eth-balance="ethBalance"
                        @review-transaction="handleReviewTransaction"
                    />
                </div>

                <div class="lg:col-span-3 space-y-4">
                    <InfoPanels />
                    <TransactionHistory :transactions="props.sentTransactions" />
                </div>
            </div>
        </div>

        <ConfirmationModal
            :is-open="isConfirmModalOpen"
            :is-sending="isSending"
            :transaction-details="transactionDetails"
            :error-message="message?.text"
            @close="closeConfirmModal"
            @confirm-send="handleSendCrypto"
        />

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />
    </AppLayout>
</template>
