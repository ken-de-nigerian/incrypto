<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import TransactionsCard from '@/components/layout/user/transactions/TransactionsCard.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';

    const props = defineProps({
        crypto_swaps: {
            type: Array as () => Array<any>,
            default: () => []
        },
        received_cryptos: {
            type: Array as () => Array<any>,
            default: () => []
        },
        sent_cryptos: {
            type: Array as () => Array<any>,
            default: () => []
        }
    });

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const isNotificationsModalOpen = ref(false);
    const notificationCount = computed(() => page.props.auth.notification_count);

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Transactions' }
    ];

    // Transform transaction data
    const transactions = computed(() => ({
        swaps: props.crypto_swaps || [],
        received: props.received_cryptos || [],
        sent: props.sent_cryptos || []
    }));

    // Calculate statistics
    const statistics = computed(() => {
        const totalTransactions =
            (props.crypto_swaps?.length || 0) +
            (props.received_cryptos?.length || 0) +
            (props.sent_cryptos?.length || 0);

        const allTx = [
            ...(props.crypto_swaps || []),
            ...(props.received_cryptos || []),
            ...(props.sent_cryptos || [])
        ];

        const completedTransactions = allTx.filter(tx => tx.status === 'completed' || tx.status === 'success').length;
        const pendingTransactions = allTx.filter(tx => tx.status === 'pending' || tx.status === 'processing').length;

        return {
            total: totalTransactions,
            completed: completedTransactions,
            pending: pendingTransactions,
            swaps: props.crypto_swaps?.length || 0,
            received: props.received_cryptos?.length || 0,
            sent: props.sent_cryptos?.length || 0
        };
    });
</script>

<template>
    <Head title="Transactions" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="mt-8">
                <TransactionsCard
                    :transactions="transactions"
                    :statistics="statistics"
                />
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
