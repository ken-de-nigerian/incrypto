<script setup lang="ts">
    import { SearchIcon, BellIcon } from 'lucide-vue-next';
    import WalletCard from '@/components/layout/user/dashboard/WalletCard.vue';
    import ChartCard from '@/components/layout/user/dashboard/ChartCard.vue';
    import CryptoListCard from '@/components/layout/user/dashboard/CryptoListCard.vue';
    import QuickActionsCard from '@/components/layout/user/dashboard/QuickActionsCard.vue';
    import LeaderboardCard from '@/components/layout/user/dashboard/LeaderboardCard.vue';
    import ReferralCard from '@/components/layout/user/dashboard/ReferralCard.vue';
    import NotificationCard from '@/components/layout/user/dashboard/NotificationCard.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import { computed, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import TextLink from '@/components/TextLink.vue';

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

    interface WalletBalances {
        wallets: Wallet[];
        totalUsdValue: number;
    }

    defineProps<{
        wallet_balances?: WalletBalances;
    }>();

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <!-- Main Content -->
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <!-- Header - Hidden on mobile (search moved to mobile header) -->
            <header class="hidden lg:flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-semibold">Dashboard</h1>
                    <p class="text-sm text-zinc-400 mt-1">Track your finances and monitor your portfolio performance.</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="relative">
                        <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" />
                        <input type="text" placeholder="Search" class="bg-zinc-900 border border-zinc-800 rounded-xl pl-10 pr-4 py-2 text-white placeholder-zinc-400 focus:outline-none focus:border-lime-400" />
                    </div>

                    <button @click="openNotificationsModal" class="p-2 bg-zinc-900 rounded-xl border border-zinc-800 hover:bg-zinc-800 relative cursor-pointer" title="Notifications">
                        <BellIcon class="w-5 h-5" />
                        <span v-if="notificationCount > 0" class="absolute top-1 right-1 w-2 h-2 bg-lime-400 rounded-full"></span>
                    </button>

                    <TextLink :href="route('user.profile.index')" class="w-9 h-9 bg-accent rounded-xl relative cursor-pointer overflow-hidden flex items-center justify-center" title="My Profile">
                        <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" alt="Profile picture" class="h-full w-full object-cover" />
                        <span v-else class="text-sm text-accent-foreground font-semibold select-none">
                            {{ initials }}
                        </span>
                    </TextLink>
                </div>
            </header>

            <!-- Mobile Account Settings Title -->
            <div class="lg:hidden mb-6 p-1">
                <h1 class="text-2xl font-semibold">Hi, {{ user.first_name }} {{ user.last_name?.charAt(0) }}.</h1>
                <p class="text-sm text-zinc-400 mt-1">Track your finances and monitor your portfolio performance.</p>
            </div>

            <!-- Grid Layout - Responsive -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
                <!-- Left Column - Full width on mobile, 3 columns on lg -->
                <div class="lg:col-span-3 space-y-4 sm:space-y-6">
                    <WalletCard :hideBalance="hideBalance" :wallet_balances="wallet_balances" />
                    <QuickActionsCard />
                </div>

                <!-- Middle Column - Full width on mobile, 6 columns on lg -->
                <div class="lg:col-span-6 space-y-4 sm:space-y-6">
                    <ChartCard />
                    <CryptoListCard />
                </div>

                <!-- Right Column - Full width on mobile, 3 columns on lg -->
                <div class="lg:col-span-3 space-y-4 sm:space-y-6">
                    <LeaderboardCard />
                    <ReferralCard />
                    <NotificationCard />
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Notifications Modal -->
    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
