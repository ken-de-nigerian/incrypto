<script setup lang="ts">
    import { Sun, Moon, Monitor, SearchIcon, BellIcon } from 'lucide-vue-next';
    import WalletCard from '@/components/layout/user/dashboard/WalletCard.vue';
    import ChartCard from '@/components/layout/user/dashboard/ChartCard.vue';
    import CryptoListCard from '@/components/layout/user/dashboard/CryptoListCard.vue';
    import QuickActionsCard from '@/components/layout/user/dashboard/QuickActionsCard.vue';
    import ReferralHistoryCard from '@/components/layout/user/dashboard/ReferralHistoryCard.vue';
    import ReferralCard from '@/components/layout/user/dashboard/ReferralCard.vue';
    import NotificationCard from '@/components/layout/user/dashboard/NotificationCard.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import { computed, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import TextLink from '@/components/TextLink.vue';
    import { useAppearance } from '@/composables/useAppearance';

    const { appearance, updateAppearance } = useAppearance();

    const tabs = [
        { value: 'light', Icon: Sun, label: 'Light' },
        { value: 'dark', Icon: Moon, label: 'Dark' },
        { value: 'system', Icon: Monitor, label: 'System' },
    ] as const;

    const currentIcon = computed(() => {
        return tabs.find(tab => tab.value === appearance.value)?.Icon ?? Sun;
    });

    const toggleAppearance = () => {
        const currentIndex = tabs.findIndex(tab => tab.value === appearance.value);
        const nextIndex = (currentIndex + 1) % tabs.length;
        const nextTheme = tabs[nextIndex].value;
        updateAppearance(nextTheme);
    };

    // Define reactive state
    const page = usePage();
    const hideBalance = ref(false);
    const isNotificationsModalOpen = ref(false);

    const notificationCount = computed(() => page.props.auth.notification_count);
    const user = computed(() => page.props.auth.user);

    // Computes user's initials as a fallback for the avatar
    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    interface Wallet {
        name: string;
        symbol: string;
        balance: number;
        usd_value: number;
        profit_loss: number;
        price_change_percentage: number;
        is_profit: boolean;
        image: string;
    }

    interface ReferredUsers {
        id: number;
        first_name: string;
        last_name: string;
        created_at: string;
    }

    interface WalletBalances {
        wallets: Wallet[];
        totalUsdValue: number;
    }

    interface Token {
        symbol: string;
        name: string;
        logo: string;
        decimals: number;
        price_change_24h: number;
    }

    interface ChartToken {
        symbol: string;
        name: string;
        logo: string;
        decimals: number;
        price_change_24h: number;
        price: number;
        balance: number;
        value: number;
    }

    defineProps<{
        wallet_balances?: WalletBalances;
        referred_users?: ReferredUsers;
        tokens?: Token;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        portfolioChange24h: number;
    }>();

    const selectedToken = ref<ChartToken | null>(null);
    const handleTokenSelect = (token: ChartToken) => {
        selectedToken.value = token;
    };

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <header class="hidden lg:flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-semibold text-foreground">Dashboard</h1>
                    <p class="text-sm text-muted-foreground mt-1">Track your finances and monitor your portfolio performance.</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="relative">
                        <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input type="text" placeholder="Search" class="input-crypto pl-10 pr-4 py-2" />
                    </div>

                    <button @click="openNotificationsModal" class="p-2 bg-card rounded-xl border border-border hover:bg-secondary relative cursor-pointer" title="Notifications">
                        <BellIcon class="w-5 h-5 text-card-foreground" />
                        <span v-if="notificationCount > 0" class="absolute top-1 right-1 w-2 h-2 bg-primary rounded-full"></span>
                    </button>

                    <button
                        @click="toggleAppearance"
                        class="p-2 bg-card rounded-xl border border-border hover:bg-secondary relative cursor-pointer"
                        title="Change Appearance">
                        <component :is="currentIcon" class="w-5 h-5 text-card-foreground" />
                    </button>

                    <TextLink :href="route('user.profile.index')" class="w-9 h-9 bg-accent rounded-xl relative cursor-pointer overflow-hidden flex items-center justify-center" title="My Profile">
                        <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" alt="Profile picture" class="h-full w-full object-cover" />
                        <span v-else class="text-sm text-accent-foreground font-semibold select-none">
                            {{ initials }}
                        </span>
                    </TextLink>
                </div>
            </header>

            <div class="lg:hidden mb-6 p-1">
                <h1 class="text-2xl font-semibold text-foreground">Hi, {{ user.first_name }} {{ user.last_name?.charAt(0) }}.</h1>
                <p class="text-sm text-muted-foreground mt-1">Track your finances and monitor your portfolio performance.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
                <div class="lg:col-span-3 space-y-4 sm:space-y-6">
                    <WalletCard :hideBalance="hideBalance" :wallet_balances="wallet_balances" />
                    <QuickActionsCard />
                </div>

                <div class="lg:col-span-6 space-y-4 sm:space-y-6">
                    <ChartCard :selected-token="selectedToken" />
                    <CryptoListCard
                        :portfolio-change24h="portfolioChange24h"
                        :prices="prices"
                        :tokens="tokens"
                        :user-balances="userBalances"
                        @select-token="handleTokenSelect"
                    />
                </div>

                <div class="lg:col-span-3 space-y-4 sm:space-y-6">
                    <ReferralHistoryCard :referred_users="referred_users" />
                    <ReferralCard />
                    <NotificationCard />
                </div>
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
