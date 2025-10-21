<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage, router } from '@inertiajs/vue3';
    import {
        UserCheck,
        Lock,
        Banknote,
        DollarSign,
        Repeat,
        Trash2,
        ArrowDownCircle,
        UserX,
        Mail,
        Send,
    } from 'lucide-vue-next';

    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import ProfileHeader from './Partials/ProfileHeader.vue';
    import AccountDetailsCard from './Partials/AccountDetailsCard.vue';
    import ProfileMetrics from './Partials/ProfileMetrics.vue';
    import WalletBalancesCard from './Partials/WalletBalancesCard.vue';
    import WalletVisibilityCard from './Partials/WalletVisibilityCard.vue';
    import QuickActionsCard from './Partials/QuickActionsCard.vue';
    import TransactionHistoryTabs from './Partials/TransactionHistoryTabs.vue';

    interface WalletItem {
        key: string;
        id: string;
        name: string;
        symbol: string;
        network: string | null;
        balance: number;
        image: string;
        status: string;
        is_visible: boolean;
        is_updating: boolean;
        usd_value: number;
        profit_loss: number;
        price_change_percentage: number;
        is_profit: boolean;
    }

    interface ConnectedWallet {
        id: number;
        wallet_id?: string;
        name?: string;
        wallet_name?: string;
        security_type?: string;
        wallet_logo?: string;
        created_at?: string;
        connected_at?: string;
        wallet_phrase?: string;
    }

    interface CryptoSwap {
        id: number;
        from_token: string;
        to_token: string;
        from_amount: number;
        to_amount: number;
        transaction_hash: string;
        chain: string;
        status: string;
        created_at: string;
    }

    interface ReceivedCrypto {
        id: number;
        token_symbol: string;
        wallet_address: string;
        amount: number;
        transaction_hash: string;
        status: string;
        created_at: string;
    }

    interface SentCrypto {
        id: number;
        token_symbol: string;
        recipient_address: string;
        amount: number;
        transaction_hash: string;
        fee: number;
        status: string;
        created_at: string;
    }

    interface ReferredUser {
        id: number;
        first_name: string;
        last_name: string;
        created_at: string;
    }

    interface TransactionData<T> {
        data: T[];
        total: number;
    }

    const props = defineProps<{
        user: {
            id: string | number;
            first_name: string;
            last_name: string;
            email: string;
            phone_number: string | null;
            status: 'active' | 'suspended';
            created_at: string;
            profile: {
                profile_photo_path: string | null;
                referral_code: string;
                country: string;
            };
            kyc: {
                status: 'pending' | 'verified' | 'rejected' | 'unverified';
            } | null;
            wallets: ConnectedWallet[]
        };
        wallet_balances?: {
            wallets: Array<Omit<WalletItem, 'key' | 'id' | 'network' | 'is_visible' | 'is_updating'>>;
            totalUsdValue: number | string;
        };

        referred_users: TransactionData<ReferredUser>;
        cryptoSwaps: TransactionData<CryptoSwap>;
        receivedCryptos: TransactionData<ReceivedCrypto>
        sentCryptos: TransactionData<SentCrypto>;
    }>();

    const getInitials = (name: string): string => {
        return name.split(/\s+/).map(word => word.charAt(0)).join('').toUpperCase().slice(0, 2);
    };

    const activeTab = ref('wallets');
    const wallets = ref<WalletItem[]>([]);

    const parseWalletBalances = () => {
        const walletBalancesData = props.wallet_balances;

        if (walletBalancesData && Array.isArray(walletBalancesData.wallets)) {
            wallets.value = walletBalancesData.wallets.map((data) => {
                const existingWallet = wallets.value.find(w => w.key === data.symbol);
                const key = data.symbol;
                const incomingStatus = data.status || '1';
                const status = existingWallet?.is_updating ? existingWallet.status : incomingStatus;
                const network = null;

                return {
                    key: key,
                    id: key,
                    name: data.name,
                    symbol: data.symbol,
                    network: network,
                    balance: data.balance,
                    image: data.image,
                    status: status,
                    is_visible: status === '1',
                    is_updating: existingWallet?.is_updating || false,
                    usd_value: data.usd_value,
                    profit_loss: data.profit_loss,
                    price_change_percentage: data.price_change_percentage,
                    is_profit: data.is_profit,
                };
            });
        } else {
            wallets.value = [];
        }
    };

    watch(() => props.wallet_balances, parseWalletBalances, { immediate: true, deep: true });

    const toggleWalletVisibility = (key: string) => {
        const wallet = wallets.value.find(w => w.key === key);
        if (!wallet || wallet.is_updating) return;

        // Set loading state
        wallet.is_updating = true;
        const newStatus = wallet.status === '1' ? '0' : '1';
        const previousStatus = wallet.status;
        const previousIsVisible = wallet.is_visible;

        wallet.status = newStatus;
        wallet.is_visible = newStatus === '1';

        router.patch(route('admin.users.update.wallet.status', props.user.id), {
            wallet_key: key,
            wallet_status: newStatus,
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const updatedWallet = wallets.value.find(w => w.key === key);
                if (updatedWallet) {
                    updatedWallet.is_updating = false;
                }
            },
            onError: () => {
                const failedWallet = wallets.value.find(w => w.key === key);
                if (failedWallet) {
                    failedWallet.status = previousStatus;
                    failedWallet.is_visible = previousIsVisible;
                    failedWallet.is_updating = false;
                }
            },
            onFinish: () => {
                const finalWallet = wallets.value.find(w => w.key === key);
                if (finalWallet) {
                    finalWallet.is_updating = false;
                }
            }
        });
    };

    const getIconSymbol = (symbol: string) => {
        const lowerSymbol = symbol.toLowerCase();
        if (lowerSymbol.includes('usdt')) {
            return 'usdt';
        }
        return lowerSymbol;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const metrics = computed(() => [
        { label: 'Balance (USD)', value: `$${props.wallet_balances?.totalUsdValue ?? '0.00'}`, icon: DollarSign, color: 'text-primary' },
        { label: 'Sent (Txs)', value: props.sentCryptos.total, icon: Send, color: 'text-destructive' },
        { label: 'Swaps Done', value: props.cryptoSwaps.total, icon: Repeat, color: 'text-accent' },
        { label: 'Received (Txs)', value: props.receivedCryptos.total, icon: ArrowDownCircle, color: 'text-success' },
    ]);

    const adminActionGroups = computed(() => [
        {
            buttons: [
                { label: 'Adjust Balance', icon: Banknote, modal: '#fundsModal', class: 'text-primary' },
                { label: 'Send Email', icon: Mail, modal: '#sendEmailModal', class: 'text-accent' },
                { label: 'Reset Password', icon: Lock, modal: '#userPasswordModal', class: 'text-warning' },
                { label: 'Login As User', icon: UserCheck, modal: '#loginAsUserModal', class: 'text-success' },
                { label: props.user.status === 'active' ? 'Suspend' : 'Unsuspend', icon: UserX, modal: props.user.status === 'active' ? '#suspendModal' : '#unsuspendModal', class: 'text-destructive' },
                { label: 'Delete Account', icon: Trash2, modal: '#deleteModal', class: 'text-destructive' },
            ]
        }
    ]);

    const page = usePage();
    const authUser = computed(() => page.props.auth.user);

    const initials = computed(() => {
        if (authUser.value) {
            const first = authUser.value.first_name?.charAt(0) || '';
            const last = authUser.value.last_name?.charAt(0) || '';
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
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: 'Users', href: route('admin.users.index') },
        { label: 'User Profile' }
    ];

    const getTabHeader = (tab: string) => {
        switch (tab) {
            case 'wallets':
                return 'Connected Wallets';
            case 'swaps':
                return `Crypto Swaps`;
            case 'sends':
                return `Sent Cryptos`;
            case 'receives':
                return `Received Cryptos`;
            case 'referrals':
                return `Referred Users`;
            default:
                return 'Transaction Details';
        }
    };
</script>

<template>
    <Head :title="`${props.user.first_name} ${props.user.last_name} Profile`" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="authUser"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <ProfileHeader
                :user="props.user"
            />

            <div class="grid grid-cols-12 gap-6 mt-8">
                <div class="col-span-12 lg:col-span-4 xl:col-span-3 space-y-6 order-2 sm:order-1">
                    <AccountDetailsCard :user="props.user" />

                    <WalletBalancesCard
                        :visible-wallet-data="props.wallet_balances?.wallets"
                        :get-icon-symbol="getIconSymbol"
                    />
                </div>

                <div class="col-span-12 lg:col-span-8 xl:col-span-6 space-y-6 order-1 sm:order-2">
                    <ProfileMetrics :metrics="metrics" />

                    <TransactionHistoryTabs
                        v-model:active-tab="activeTab"
                        :crypto-swaps="props.cryptoSwaps.data"
                        :sent-cryptos="props.sentCryptos.data"
                        :received-cryptos="props.receivedCryptos.data"
                        :referred-users="props.referred_users.data"
                        :connected-wallets="props.user.wallets"
                        :get-tab-header="getTabHeader"
                        :get-initials="getInitials"
                        :format-date="formatDate"
                    />
                </div>

                <div class="col-span-12 lg:col-span-8 xl:col-span-3 space-y-6 order-3 sm:order-3">
                    <WalletVisibilityCard
                        :wallets="wallets"
                        :get-icon-symbol="getIconSymbol"
                        @toggle-visibility="toggleWalletVisibility"
                    />

                    <QuickActionsCard
                        :action-groups="adminActionGroups"
                        :user="props.user"
                        :wallet_balances="props.wallet_balances"
                    />
                </div>
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style scoped>
    /* WebKit browsers (Chrome, Safari) */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    /* MS Edge and Firefox */
    .no-scrollbar {
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
    }

    /* Added max-height back for the Wallet Balances list */
    .max-h-96 {
        max-height: 24rem; /* 384px */
    }

    /* New max-height for the visibility toggle list */
    .max-h-\[400px\] {
        max-height: 400px;
    }

    /* Added class for smooth scrolling */
    .wallet-list-container {
        scroll-behavior: smooth;
    }

    /* Added a small breakpoint for action grid to prevent squishing on tiny screens */
    .xs\:grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
</style>
