<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import ProfileSettings from './Partials/ProfileSettings.vue';
    import ConnectionsSettings from './Partials/ConnectionsSettings.vue';
    import TextLink from '@/components/TextLink.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';

    type Tab = 'profile' | 'security' | 'connections';

    const props = defineProps<{
        user_profile: {
            id: string | number,
            first_name: string,
            last_name: string,
            email: string,
            phone_number: string | null,
            status: 'active' | 'suspended',
            created_at: string,
            profile: {
                profile_photo_path: string | null,
                referral_code: string,
                country: string,
            },
        },
        activeTab: string,
        connectedAccounts: string | null,
    }>();

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    const currentTab = computed<Tab>(() => (props.activeTab as Tab) || 'profile');

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
        { label: 'Users', href: route('admin.users.index') },
        { label: 'Edit Profile' }
    ];
</script>

<template>
    <Head :title="`${props.user_profile.first_name} ${props.user_profile.last_name} | Edit Profile`" />

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
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">User Profile Settings</h1>
                        <p class="text-muted-foreground">View and manage user account details and configurations</p>
                    </div>
                </div>
            </div>

            <div class="flex max-md:flex-col md:space-x-8 min-h-screen w-full">
                <div class="md:w-80 md:shrink-0 bg-muted/10 md:rounded-lg lg:mt-0 mt-8">
                    <nav class="p-2 space-y-1 border border-border rounded-xl mb-4">
                        <TextLink
                            :href="`${route('admin.users.edit', { id: props.user_profile.id })}?tab=profile`"
                            :class="[
                                'inline-flex items-center gap-2 w-full justify-start text-left h-auto p-3 whitespace-nowrap rounded-md text-sm font-medium cursor-pointer text-foreground transition-colors duration-150',
                                currentTab === 'profile' ? 'bg-secondary/70 text-secondary-foreground' : 'hover:bg-accent/10 hover:text-accent'
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
                            :href="`${route('admin.users.edit', { id: props.user_profile.id })}?tab=connections`"
                            :class="[
                                'inline-flex items-center gap-2 w-full justify-start text-left h-auto p-3 whitespace-nowrap rounded-md text-sm font-medium cursor-pointer text-foreground transition-colors duration-150',
                                currentTab === 'connections' ? 'bg-secondary/70 text-secondary-foreground' : 'hover:bg-accent/10 hover:text-accent'
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
                    <ProfileSettings v-if="currentTab === 'profile'" :user-profile="props.user_profile" />
                    <ConnectionsSettings v-if="currentTab === 'connections'" :connected-accounts="props.connectedAccounts" />
                </div>
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
