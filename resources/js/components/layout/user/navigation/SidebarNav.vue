<script setup lang="ts">
    import { usePage } from '@inertiajs/vue3';
    import {
        LayoutDashboard,
        LifeBuoy,
        Bell,
        UserIcon,
        Settings,
        LogOut,
        Send,
        Download,
        Repeat, Users, TrendingUp
    } from 'lucide-vue-next';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';

    import { computed, ref } from 'vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import SiteLogo from '@/components/SiteLogo.vue';

    const page = usePage();
    const isNotificationsModalOpen = ref(false);

    const notificationCount = computed(() => page.props.auth.notification_count);
    const user = computed(() => page.props.auth.user);

    const navigation = [
        { name: "Dashboard", href: "user.dashboard", icon: LayoutDashboard, group: 'user.dashboard' },
        { name: "Send", href: "user.send.index", icon: Send, group: 'user.send.' },
        { name: "Receive", href: "user.receive.index", icon: Download, group: 'user.receive.' },
        { name: "Swap", href: "user.swap.index", icon: Repeat, group: 'user.swap.' },
        { name: "Trading", href: "user.trade.index", icon: TrendingUp, group: 'user.trade.', additionalGroups: ['user.transactions.'] },
        { name: "Referrals", href: "user.rewards.index", icon: Users, group: 'user.rewards.' },
    ];

    const bottomNavigation = [
        { name: "KYC", href: "user.kyc.index", icon: LifeBuoy, group: 'user.kyc.' },
        { name: "Settings", href: "user.profile.index", icon: Settings, group: 'user.profile.' },
    ];

    const isAnyItemActive = computed(() => {
        return navigation.some(item => {
            if (item.isDefault) return false;
            if (item.group) {
                const mainGroupActive = route().current(item.group + '*');
                if (mainGroupActive) return true;

                if (item.additionalGroups) {
                    return item.additionalGroups.some(group => route().current(group + '*'));
                }
            }
            return route().current(item.href);
        });
    });

    const isActive = (item: typeof navigation[0]) => {
        let active: boolean;

        if (item.group) {
            active = route().current(item.group + '*');

            if (!active && item.additionalGroups) {
                active = item.additionalGroups.some(group => route().current(group + '*'));
            }
        } else {
            active = route().current(item.href);
        }

        if (!active && item.isDefault && !isAnyItemActive.value) {
            active = true;
        }

        return active;
    }

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };
</script>

<template>
    <div class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0">
        <div class="flex flex-col flex-grow bg-sidebar p-3 border-r border-sidebar-border">
            <div class="flex items-center px-4 py-4 pb-[50px]">
                <div class="flex items-center">
                    <SiteLogo class="inline-flex items-center gap-2 select-none" />
                </div>
            </div>

            <nav class="flex-1 px-3 py-2 space-y-6">
                <TextLink
                    v-for="item in navigation"
                    :key="item.name"
                    :href="route(item.href)"
                    class="flex items-center px-3 py-2 text-md font-sm rounded-lg transition-all duration-200"
                    :class="{
                        'bg-sidebar-accent text-sidebar-foreground': isActive(item),
                        'text-sidebar-foreground/70 hover:bg-sidebar-accent/30': !isActive(item)
                    }">
                    <component :is="item.icon" class="mr-5 h-5 w-5 text-sidebar-foreground/70" />
                    {{ item.name }}
                </TextLink>
            </nav>

            <div class="px-3 py-2 space-y-6">
                <TextLink
                    v-for="item in bottomNavigation"
                    :key="item.name"
                    :href="route(item.href)"
                    class="flex items-center px-3 py-2 text-md font-sm rounded-lg transition-all duration-200"
                    :class="{
                        'bg-sidebar-accent text-sidebar-foreground': isActive(item),
                        'text-sidebar-foreground/70 hover:bg-sidebar-accent/30': !isActive(item)
                    }">
                    <component :is="item.icon" class="mr-5 h-5 w-5 text-sidebar-foreground/70" />
                    {{ item.name }}
                </TextLink>

                <button
                    @click="openNotificationsModal"
                    class="w-full flex items-center px-3 py-2 text-md font-sm rounded-lg transition-all duration-200 text-sidebar-foreground/70 hover:bg-sidebar-accent/30 cursor-pointer">
                    <Bell class="mr-5 h-5 w-5 text-sidebar-foreground/70" />
                    Notification
                    <div v-if="notificationCount > 0" class="ml-auto bg-destructive text-destructive-foreground text-xs font-semibold rounded-full px-2 py-0.5 flex items-center justify-center">
                        {{ notificationCount }}
                    </div>
                </button>

                <TextLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center px-3 py-2 text-md font-sm rounded-lg transition-all duration-200 text-sidebar-foreground/70 hover:bg-sidebar-accent/30 cursor-pointer">
                    <LogOut class="mr-5 h-5 w-5 text-sidebar-foreground/70" />
                    Logout
                </TextLink>
            </div>

            <div class="px-3 py-3">
                <TextLink :href="route('user.profile.index')">
                    <div class="flex items-center p-2 rounded-lg bg-sidebar-accent transition-all duration-200">
                        <div class="w-7 h-7 bg-primary/50 rounded-full flex items-center justify-center">
                            <UserIcon class="h-4 w-4 text-accent-foreground" />
                        </div>
                        <span v-if="user" class="ml-2 text-sm font-medium text-sidebar-foreground">{{ user.first_name }} {{ user.last_name?.charAt(0) }}.</span>
                    </div>
                </TextLink>
            </div>
        </div>
    </div>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    .animate-fadeIn {
        animation: fadeIn 0.2s ease-out;
    }
    .max-h-80vh {
        max-height: 80vh;
    }
    /* Hide scrollbar for the Notifications Modal */
    .no-scrollbar::-webkit-scrollbar {
        display: none; /* Hide scrollbar for Chrome, Safari, and Edge */
    }
    .no-scrollbar {
        -ms-overflow-style: none; /* Hide scrollbar for IE and Edge */
        scrollbar-width: none; /* Hide scrollbar for Firefox */
    }
</style>
