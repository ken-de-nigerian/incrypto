<script setup lang="ts">
    import { Sun, Moon, Monitor, SearchIcon, BellIcon, Users, DollarSign, ListChecks, Activity } from 'lucide-vue-next';
    import AdminStatsCard from '@/components/layout/admin/dashboard/AdminStatsCard.vue';
    import SystemHealthCard from '@/components/layout/admin/dashboard/SystemHealthCard.vue';
    import RecentActivityCard from '@/components/layout/admin/dashboard/RecentActivityCard.vue';
    import PendingActionsCard from '@/components/layout/admin/dashboard/PendingActionsCard.vue';
    import UserGrowthChartCard from '@/components/layout/admin/dashboard/UserGrowthChartCard.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';

    import { computed, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import TextLink from '@/components/TextLink.vue';
    import { useAppearance } from '@/composables/useAppearance';
    import { route } from 'ziggy-js';

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

    const adminStats = ref([
        { title: 'Total Users', value: '15,450', change: '+12%', Icon: Users, color: 'text-primary', trend: 'up' },
        { title: 'Total Revenue', value: '$850k', change: '-3%', Icon: DollarSign, color: 'text-destructive', trend: 'down' },
        { title: 'Pending Withdrawals', value: '45', change: '24 hr delay', Icon: ListChecks, color: 'text-warning', trend: 'flat' },
        { title: 'System Load', value: '65%', change: 'Normal', Icon: Activity, color: 'text-muted-foreground', trend: 'up' },
    ]);

    const pendingActions = ref([
        { id: 1, type: 'KYC Review', user: 'Jane Doe', link: '#' },
        { id: 2, type: 'Withdrawal Approval', user: 'John Smith', link: '#' },
        { id: 3, type: 'New Ticket', user: 'Alex Johnson', link: '#' },
    ]);

    const recentActivity = ref([
        { id: 1, desc: 'User #101 created new account.', time: '2 mins ago' },
        { id: 2, desc: 'System backup initiated.', time: '1 hour ago' },
        { id: 3, desc: 'Admin ABC updated fees structure.', time: '3 hours ago' },
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
                        <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" alt="Admin picture" class="h-full w-full object-cover" />
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

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 mb-8">
                <AdminStatsCard
                    v-for="stat in adminStats"
                    :key="stat.title"
                    :title="stat.title"
                    :value="stat.value"
                    :change="stat.change"
                    :Icon="stat.Icon"
                    :color="stat.color"
                    :trend="stat.trend"
                />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
                <div class="lg:col-span-4 space-y-4 sm:space-y-6">
                    <UserGrowthChartCard />
                    <SystemHealthCard />
                </div>

                <div class="lg:col-span-5 space-y-4 sm:space-y-6">
                    <PendingActionsCard :actions="pendingActions" />
                    <div class="card-crypto p-6">
                        <h2 class="text-xl font-semibold text-card-foreground mb-4">Users Overview</h2>
                        <p class="text-sm text-muted-foreground">Detailed list of recent user registrations and their status.</p>
                        <ul class="mt-4 space-y-3">
                            <li class="flex justify-between text-sm text-card-foreground/80"><span>User ID 1045 - Verified</span><span class="font-medium text-muted-foreground">10 min ago</span></li>
                            <li class="flex justify-between text-sm text-card-foreground/80"><span>User ID 1046 - Pending KYC</span><span class="font-medium text-muted-foreground">3 hours ago</span></li>
                            <li class="flex justify-between text-sm text-card-foreground/80"><span>User ID 1047 - Active</span><span class="font-medium text-muted-foreground">1 day ago</span></li>
                        </ul>
                    </div>
                </div>

                <div class="lg:col-span-3 space-y-4 sm:space-y-6">
                    <RecentActivityCard :activities="recentActivity" />
                    <div class="card-crypto p-6">
                        <h2 class="text-xl font-semibold text-card-foreground mb-4">Admin Links</h2>
                        <ul class="space-y-2">
                            <li><TextLink :href="route('admin.profile.index')" class="text-sm text-primary hover:text-primary/80">System Settings</TextLink></li>
                            <li><TextLink :href="route('admin.dashboard')" class="text-sm text-primary hover:text-primary/80">Financial Reports</TextLink></li>
                            <li><TextLink :href="route('admin.users.index')" class="text-sm text-primary hover:text-primary/80">Manage Users</TextLink></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
