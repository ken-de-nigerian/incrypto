<script setup lang="ts">
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import WalletConnectCard from '@/components/layout/user/wallet/WalletConnectCard.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import { computed, ref } from 'vue';

    const props = defineProps({
        userWallet: {
            type: Object,
            required: true
        },
        wallets: {
            type: [Array, Object],
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
        { label: 'Wallet Connect' }
    ];

    const formatDate = (dateString: string) => {
        if (!dateString) return 'Recently';

        const date = new Date(dateString);
        const now = new Date();
        const diffInDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

        if (diffInDays === 0) return 'Today';
        if (diffInDays === 1) return 'Yesterday';
        if (diffInDays < 7) return `${diffInDays} days ago`;
        if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`;
        if (diffInDays < 365) return `${Math.floor(diffInDays / 30)} months ago`;

        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const userWallets = computed(() => {
        const wallets = props.userWallet?.wallets;
        if (!wallets || !Array.isArray(wallets)) return [];

        return wallets.map(wallet => ({
            id: wallet.id,
            wallet_id: wallet.wallet_id || wallet.id,
            wallet_name: wallet.wallet_name || wallet.name || 'Unknown Wallet',
            wallet_security_type: wallet.security_type || 'Personal / Secure',
            wallet_logo: wallet.wallet_logo || wallet.logo,
            connected_at: formatDate(wallet.created_at || wallet.connected_at)
        }));
    });

    const availableWallets = computed(() => {
        let walletsList = props.wallets;

        if (!walletsList) return [];

        if (walletsList.Data && Array.isArray(walletsList.Data)) {
            walletsList = walletsList.Data;
        } else if (!Array.isArray(walletsList)) {
            walletsList = Object.values(walletsList);
        }

        if (!Array.isArray(walletsList)) return [];

        const defaultType = 'Mobile/Desktop Wallet';
        const defaultDescription = 'Click "Connect" to get started!';

        return walletsList.map(wallet => {
            return {
                id: wallet.Id || wallet.id,
                name: wallet.Name || wallet.name || 'Unknown Wallet',
                logo: wallet.LogoUrl || wallet.logo || wallet.image,
                description: defaultDescription,
                type: defaultType,
                is_popular: false
            };
        });
    });
</script>

<template>
    <Head title="Wallet Connect" />

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
                <WalletConnectCard
                    :user-wallets="userWallets"
                    :available-wallets="availableWallets"
                />
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
