<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import ProfileSettings from '@/components/layout/admin/settings/ProfileSettings.vue';
    import SecuritySettings from '@/components/layout/admin/settings/SecuritySettings.vue';
    import ConnectionsSettings from '@/components/layout/admin/settings/ConnectionsSettings.vue';
    import AppearanceSettings from '@/components/layout/admin/settings/AppearanceSettings.vue';
    import TextLink from '@/components/TextLink.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';

    type Tab = 'profile' | 'security' | 'appearance' | 'connections';

    defineProps({
        activeTab: {
            type: String as () => Tab,
            default: 'profile',
        },
        activeSessions: Array,
        connectedAccounts: String,
    });

    const page = usePage();
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
        { label: 'Account Settings' }
    ];
</script>

<template>
    <Head title="Settings" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="mt-8 md:mb-8 space-y-6">
                <!-- Header Section -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">Profile Settings</h1>
                        <p class="text-muted-foreground">Manage your account information and preferences</p>
                    </div>
                </div>
            </div>

            <div class="flex max-md:flex-col md:space-x-8 min-h-screen w-full">
                <div class="md:w-80 md:shrink-0 bg-muted/10 md:rounded-lg lg:mt-0 mt-8">
                    <nav class="p-2 space-y-1 border border-border rounded-xl mb-4">
                        <TextLink
                            :href="route('admin.profile.index', { tab: 'profile' })" :class="[
                                'inline-flex items-center gap-2 w-full justify-start text-left h-auto p-3 whitespace-nowrap rounded-md text-sm font-medium cursor-pointer text-foreground transition-colors duration-150',
                                activeTab === 'profile' ? 'bg-secondary/70 text-secondary-foreground' : 'hover:bg-accent/10 hover:text-accent'
                            ]">
                            <div class="flex items-start space-x-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user h-5 w-5 mt-0.5 flex-shrink-0">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium">Profile</div>
                                    <div class="text-xs text-muted-foreground mt-1 whitespace-normal line-clamp-2">Update your name, email, and personal details.</div>
                                </div>
                            </div>
                        </TextLink>

                        <TextLink
                            :href="route('admin.profile.index', { tab: 'security' })"
                            :class="[
                                'inline-flex items-center gap-2 w-full justify-start text-left h-auto p-3 whitespace-nowrap rounded-md text-sm font-medium cursor-pointer text-foreground transition-colors duration-150',
                                activeTab === 'security' ? 'bg-secondary/70 text-secondary-foreground' : 'hover:bg-accent/10 hover:text-accent'
                            ]">
                            <div class="flex items-start space-x-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield h-5 w-5 mt-0.5 flex-shrink-0">
                                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium">Security</div>
                                    <div class="text-xs text-muted-foreground mt-1 whitespace-normal line-clamp-2">Change your password and manage two-factor authentication.</div>
                                </div>
                            </div>
                        </TextLink>

                        <TextLink
                            :href="route('admin.profile.index', { tab: 'appearance' })"
                            :class="[
                                'inline-flex items-center gap-2 w-full justify-start text-left h-auto p-3 whitespace-nowrap rounded-md text-sm font-medium cursor-pointer text-foreground transition-colors duration-150',
                                activeTab === 'appearance' ? 'bg-secondary/70 text-secondary-foreground' : 'hover:bg-accent/10 hover:text-accent'
                            ]">
                            <div class="flex items-start space-x-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-moon h-5 w-5 mt-0.5 flex-shrink-0">
                                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium">Appearance</div>
                                    <div class="text-xs text-muted-foreground mt-1 whitespace-normal line-clamp-2">Choose your preferred theme.</div>
                                </div>
                            </div>
                        </TextLink>

                        <TextLink
                            :href="route('admin.profile.index', { tab: 'connections' })"
                            :class="[
                                'inline-flex items-center gap-2 w-full justify-start text-left h-auto p-3 whitespace-nowrap rounded-md text-sm font-medium cursor-pointer text-foreground transition-colors duration-150',
                                activeTab === 'connections' ? 'bg-secondary/70 text-secondary-foreground' : 'hover:bg-accent/10 hover:text-accent'
                            ]">
                            <div class="flex items-start space-x-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-link h-5 w-5 mt-0.5 flex-shrink-0">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium">Connections</div>
                                    <div class="text-xs text-muted-foreground mt-1 whitespace-normal line-clamp-2">Link or unlink third-party applications and services.</div>
                                </div>
                            </div>
                        </TextLink>
                    </nav>
                </div>

                <div class="flex-1 md:mt-8 lg:mt-0">
                    <ProfileSettings v-if="activeTab === 'profile'" />
                    <SecuritySettings v-if="activeTab === 'security'" :active-sessions="activeSessions" />
                    <AppearanceSettings v-if="activeTab === 'appearance'" />
                    <ConnectionsSettings v-if="activeTab === 'connections'" :connected-accounts="connectedAccounts" />
                </div>
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
