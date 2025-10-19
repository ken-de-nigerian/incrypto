<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage, router } from '@inertiajs/vue3';
    import debounce from 'lodash/debounce';

    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';

    import {
        Search, Circle, Mail, UserPlus, XCircle
    } from 'lucide-vue-next';
    import PaginationControls from '@/components/PaginationControls.vue';
    import TextLink from '@/components/TextLink.vue';
    import UserStatusFilter from '@/components/UserStatusFilter.vue';

    const page = usePage();
    const user = computed(() => page.props.auth.user);
    const notificationCount = computed(() => page.props.auth.notification_count);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    interface Profile {
        profile_photo_path: string | null;
    }

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        status: 'active' | 'suspended';
        profile: Profile | null;
    }

    interface PaginatedData<T> {
        current_page: number;
        data: T[];
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: { url: string | null; label: string; active: boolean; }[];
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    }

    interface Props {
        users: PaginatedData<User>;
        filters: {
            search: string | null;
            status: 'active' | 'suspended' | null;
        };
    }

    const props = defineProps<Props>();

    const isNotificationsModalOpen = ref(false);
    const openNotificationsModal = () => { isNotificationsModalOpen.value = true; };
    const closeNotificationsModal = () => { isNotificationsModalOpen.value = false; };

    const form = ref({
        search: props.filters.search || '',
        status: props.filters.status || '',
    });

    const filteredAndPagedUsers = computed(() => props.users.data);
    const performFilter = debounce(() => {
        router.get(
            route('admin.users.index'),
            {
                search: form.value.search,
                status: form.value.status,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }, 300);

    watch(() => form.value.search, performFilter);
    watch(() => form.value.status, performFilter);

    const goToPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getStatusClass = (userStatus: User['status']) => {
        switch (userStatus) {
            case 'active':
                return 'bg-success/20 border border-success/30 text-success';
            default:
                return 'bg-destructive/20 border border-destructive/30 text-destructive';
        }
    };

    const hasActiveFilters = computed(() => form.value.search || form.value.status);

    const clearFilters = () => {
        form.value.search = '';
        form.value.status = '';
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: 'Users Management' }
    ];
</script>

<template>
    <Head title="Users Management" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="mt-8 space-y-4 p-4 bg-card border border-border rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <Search class="w-4 h-4 text-muted-foreground" />
                        </div>
                        <input v-model="form.search" type="text" placeholder="Search by name or email..." :disabled="false" class="w-full pl-10 pr-4 py-2 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary transition-all disabled:opacity-50 disabled:cursor-not-allowed" />
                    </div>

                    <div>
                        <UserStatusFilter v-model="form.status" />
                    </div>

                    <div class="flex gap-2">
                        <button v-if="hasActiveFilters" @click="clearFilters" :disabled="false" class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <XCircle class="w-4 h-4" />
                            <span>Clear</span>
                        </button>
                    </div>

                    <div>
                        <TextLink :href="route('admin.users.create')" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium transition-colors">
                            <UserPlus class="w-4 h-4" />
                            <span>Add User</span>
                        </TextLink>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mt-8">
                <template v-if="filteredAndPagedUsers.length">
                    <div v-for="user in filteredAndPagedUsers" :key="user.id" class="card-crypto relative">
                        <div class="p-4">
                            <div class="text-center space-y-2">
                                <div class="w-20 h-20 rounded-full mx-auto bg-secondary/70 overflow-hidden flex items-center justify-center border border-border">
                                    <img v-if="user.profile?.profile_photo_path" :src="user.profile?.profile_photo_path" :alt="user.first_name" class="h-full w-full object-cover">
                                    <span v-else class="text-3xl font-bold text-foreground">{{ user.first_name.charAt(0) }}{{ user.last_name.charAt(0) }}</span>
                                </div>

                                <h6 class="text-lg font-semibold">
                                    <TextLink :href="route('admin.users.show', user.id)" class="text-foreground hover:text-primary transition-colors">{{ user.first_name }} {{ user.last_name }}</TextLink>
                                </h6>

                                <span class="inline-flex items-center space-x-1 text-xs font-semibold rounded-full px-3 py-1" :class="getStatusClass(user.status)">
                                    <Circle class="w-2 h-2 fill-current" />
                                    <span>{{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}</span>
                                </span>

                                <span class="block text-sm font-medium text-muted-foreground pt-1">
                                    <Mail class="w-3 h-3 inline-block mr-1 align-sub" />{{ user.email }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="lg:col-span-4 sm:col-span-2 col-span-1">
                        <div class="card-crypto p-10 text-center border-dashed border-border flex flex-col items-center justify-center">
                            <div class="w-24 h-24 mx-auto mb-4">
                                <XCircle class="w-full h-full text-destructive" />
                            </div>
                            <h6 class="text-lg font-semibold text-foreground">No users found</h6>
                            <p class="text-muted-foreground mt-1">Try adjusting your search terms or status filter.</p>
                        </div>
                    </div>
                </template>
            </div>

            <PaginationControls
                v-if="props.users.last_page > 1"
                :links="props.users.links"
                :from="props.users.from"
                :to="props.users.to"
                :total="props.users.total"
                @go-to-page="goToPage" />
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal" />
</template>

<style scoped>
    input[type="date"] {
        color-scheme: var(--foreground) !important;
    }

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
