<script setup lang="ts">
    import {
        Sun,
        Moon,
        Monitor,
        SearchIcon,
        BellIcon,
        Users,
        Send, ArrowLeftRight, CandlestickChart, Briefcase, Download
    } from 'lucide-vue-next';
    import AdminStatsCard from '@/components/layout/admin/dashboard/AdminStatsCard.vue';
    import RecentActivityCard from '@/components/layout/admin/dashboard/RecentActivityCard.vue';
    import PendingActionsCard from '@/components/layout/admin/dashboard/PendingActionsCard.vue';
    import UserGrowthChartCard from '@/components/layout/admin/dashboard/UserGrowthChartCard.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import QuickActionsCard from '@/components/layout/admin/dashboard/QuickActionsCard.vue';
    import UsersOverview from '@/components/layout/admin/dashboard/UsersOverview.vue';

    import { computed, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import TextLink from '@/components/TextLink.vue';
    import { useAppearance } from '@/composables/useAppearance';
    import { route } from 'ziggy-js';

    const props = defineProps<{
        adminStatsData: {
            total_users: number;
            total_active_users: number;
            total_suspended_users: number;
            total_sent: number;
            total_received: number;
            total_swaps: number;
            total_trades: number;
            total_investments: number;
        };
        pendingActions: Array<{
            id: string | number;
            type: string;
            user: string;
            link: string;
        }>;
        recentUsers: Array<{
            id: number;
            first_name: string;
            last_name: string;
            email: string;
            status: string;
            registered_at: string;
        }>;
    }>();

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

    // Helper function to format large numbers
    const formatNumber = (num: number): string => {
        return new Intl.NumberFormat('en-US', { notation: 'compact', minimumFractionDigits: 0, maximumFractionDigits: 1 }).format(num);
    };

    const adminStats = computed(() => [
        {
            title: 'Total Users',
            value: formatNumber(props.adminStatsData.total_users),
            Icon: Users
        },
        {
            title: 'Total Sent',
            value: formatNumber(props.adminStatsData.total_sent),
            Icon: Send
        },
        {
            title: 'Total Received',
            value: formatNumber(props.adminStatsData.total_received),
            Icon: Download
        },
        {
            title: 'Crypto Swaps',
            value: formatNumber(props.adminStatsData.total_swaps),
            Icon: ArrowLeftRight
        },
        {
            title: 'Total Trades',
            value: formatNumber(props.adminStatsData.total_trades),
            Icon: CandlestickChart
        },
        {
            title: 'Investments',
            value: formatNumber(props.adminStatsData.total_investments),
            Icon: Briefcase
        },
    ]);

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <header class="hidden lg:flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-semibold text-foreground">Admin Dashboard</h1>
                    <p class="text-sm text-muted-foreground mt-1">System overview and administrative control panel.</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="relative">
                        <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                        <input type="text" placeholder="Search Admin..." class="input-crypto pl-10 pr-4 py-2" />
                    </div>

                    <button @click="openNotificationsModal" class="p-2 bg-card rounded-xl border border-border hover:bg-secondary/50 relative cursor-pointer" title="Notifications">
                        <BellIcon class="w-5 h-5 text-card-foreground" />
                        <span v-if="notificationCount > 0" class="absolute top-1 right-1 w-2 h-2 bg-primary rounded-full"></span>
                    </button>

                    <button
                        @click="toggleAppearance"
                        class="p-2 bg-card rounded-xl border border-border hover:bg-secondary/50 relative cursor-pointer"
                        title="Change Appearance">
                        <component :is="currentIcon" class="w-5 h-5 text-card-foreground" />
                    </button>

                    <TextLink :href="route('admin.profile.index')" class="w-9 h-9 bg-accent rounded-xl relative cursor-pointer overflow-hidden flex items-center justify-center" title="Admin Profile">
                        <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" alt="Admin picture" loading="lazy" class="h-full w-full object-cover" />
                        <span v-else class="text-sm text-accent-foreground font-semibold select-none">
                            {{ initials }}
                        </span>
                    </TextLink>
                </div>
            </header>

            <div class="lg:hidden mb-6 p-1">
                <h1 class="text-2xl font-semibold text-foreground">Hello, {{ user.first_name }}.</h1>
                <p class="text-sm text-muted-foreground mt-1">Review the system overview.</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-8">
                <AdminStatsCard
                    v-for="stat in adminStats"
                    :key="stat.title"
                    :title="stat.title"
                    :value="stat.value"
                    :change="stat.change"
                    :Icon="stat.Icon"
                />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
                <div class="lg:col-span-4 space-y-4 sm:space-y-6">
                    <UserGrowthChartCard :user-stats="props.adminStatsData" />
                    <PendingActionsCard :actions="props.pendingActions" />
                </div>

                <div class="lg:col-span-5 space-y-4 sm:space-y-6">
                    <UsersOverview :users="props.recentUsers" />
                </div>

                <div class="lg:col-span-3 space-y-4 sm:space-y-6">
                    <QuickActionsCard />
                    <RecentActivityCard />
                </div>
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
