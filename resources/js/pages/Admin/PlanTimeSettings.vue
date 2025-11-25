<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        CalendarIcon,
        ClockIcon,
        PlusIcon,
        SearchIcon,
        EditIcon,
        TrashIcon,
        PiggyBankIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
    import TimeSettingModal from '@/components/TimeSettingModal.vue';
    import { useFlash } from '@/composables/useFlash';

    interface PlanTimeSetting {
        id: number;
        name: string;
        period: number;
        plans_count?: number;
        created_at?: string;
        updated_at?: string;
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

    interface Statistics {
        total: number;
        in_use: number;
        unused: number;
    }

    const props = defineProps<{
        time_settings: PaginatedData<PlanTimeSetting>;
        statistics: Statistics;
        filters: {
            search?: string;
        };
    }>();

    const isNotificationsModalOpen = ref(false);
    const isTimeSettingModalOpen = ref(false);
    const editingTimeSetting = ref<PlanTimeSetting | null>(null);

    const searchQuery = ref(props.filters.search || '');

    const page = usePage();
    const { notify } = useFlash();
    const user = computed(() => page.props.auth?.user);
    const notificationCount = computed(() => page.props.auth?.notification_count || 0);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: 'Investments', href: route('admin.plans.index') },
        { label: 'Time Settings' }
    ];

    const openCreateModal = () => {
        editingTimeSetting.value = null;
        isTimeSettingModalOpen.value = true;
    };

    const openEditModal = (timeSetting: PlanTimeSetting) => {
        editingTimeSetting.value = timeSetting;
        isTimeSettingModalOpen.value = true;
    };

    const closeModal = () => {
        isTimeSettingModalOpen.value = false;
        editingTimeSetting.value = null;
    };

    const deleteTimeSetting = (timeSettingId: number) => {
        notify('error', 'Are you sure you want to delete this time setting?', {
            title: 'Confirm Delete',
            duration: 0,
            dismissible: true,
            action: {
                label: 'Confirm',
                callback: () => {
                    router.delete(route('admin.time.destroy', timeSettingId), {}, {
                        preserveScroll: true,
                        only: ['time_settings'],
                        onSuccess: () => {},
                        onError: (errors) => {
                            console.error('Failed to delete time settings:', errors);
                        }
                    });
                }
            }
        });
    };

    const applyFilters = () => {
        router.get(route('admin.time.index'), {
            search: searchQuery.value
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const clearFilters = () => {
        searchQuery.value = '';
        router.get(route('admin.time.index'), {}, {
            preserveState: true,
            preserveScroll: true
        });
    };

    watch([searchQuery], () => {
        const timeout = setTimeout(() => {
            applyFilters();
        }, 500);
        return () => clearTimeout(timeout);
    });

    const goToPage = (url: string) => {
        router.get(url, {
            search: searchQuery.value
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['time_settings'],
        });
    };
</script>

<template>
    <Head title="Time Settings" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="isNotificationsModalOpen = true"
            />

            <div class="mt-8 space-y-6">
                <!-- Header Section -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">Time Settings</h1>
                        <p class="text-muted-foreground">Manage investment plan duration settings</p>
                    </div>

                    <button
                        @click="openCreateModal"
                        class="inline-flex justify-center items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:bg-primary/90 cursor-pointer transition-colors">
                        <PlusIcon class="w-5 h-5" />
                        <span>Create Time Setting</span>
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-card border border-border rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">Total Settings</p>
                                <p class="text-2xl font-bold text-card-foreground">{{ statistics.total }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center">
                                <CalendarIcon class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-card border border-border rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">In Use</p>
                                <p class="text-2xl font-bold text-card-foreground">{{ statistics.in_use }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center">
                                <PiggyBankIcon class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-card border border-border rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">Unused</p>
                                <p class="text-2xl font-bold text-card-foreground">{{ statistics.unused }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center">
                                <ClockIcon class="w-6 h-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search -->
                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1 relative">
                            <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search time settings..."
                                class="w-full pl-10 pr-4 py-2 bg-background border border-border rounded-lg text-card-foreground input-crypto"
                            />
                        </div>

                        <button
                            v-if="searchQuery"
                            @click="clearFilters"
                            class="flex items-center justify-center gap-2 px-4 py-2 bg-background border border-border text-card-foreground rounded-lg hover:bg-muted cursor-pointer transition-colors">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 mb-8 sm:mb-0">
                <div v-if="time_settings.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    <div
                        v-for="timeSetting in time_settings.data"
                        :key="timeSetting.id"
                        class="bg-card border border-border rounded-xl p-5 hover:border-primary/50 transition-all duration-200 group">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center">
                                    <CalendarIcon class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-card-foreground">{{ timeSetting.name }}</h3>
                                </div>
                            </div>
                        </div>

                        <div v-if="timeSetting.plans_count !== undefined" class="mb-4 pb-4 border-b border-border/50">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground">Plans using this</span>
                                <span class="bg-secondary/70 text-card-foreground px-2 sm:px-3 py-1 rounded-lg text-xs border border-border">{{ timeSetting.plans_count }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button
                                @click="openEditModal(timeSetting)"
                                class="flex items-center justify-center gap-2 py-2 bg-background border border-border text-card-foreground rounded-lg text-sm font-semibold hover:bg-muted transition-all cursor-pointer">
                                <EditIcon class="w-4 h-4" />
                                Edit
                            </button>

                            <button
                                @click="deleteTimeSetting(timeSetting.id)"
                                class="flex items-center justify-center gap-2 py-2 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-100 transition-all cursor-pointer">
                                <TrashIcon class="w-4 h-4" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center text-center py-16 bg-card border border-border rounded-2xl">
                    <CalendarIcon class="w-16 h-16 text-muted-foreground/30 mb-4" />
                    <h3 class="text-lg font-semibold text-card-foreground mb-2">No Time Settings Available</h3>
                    <p class="text-sm text-muted-foreground mb-4">Create your first time setting to get started.</p>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
                        <PlusIcon class="w-5 h-5" />
                        Create Time Setting
                    </button>
                </div>

                <PaginationControls
                    v-if="time_settings.last_page > 1"
                    :links="time_settings.links"
                    :from="time_settings.from"
                    :to="time_settings.to"
                    :total="time_settings.total"
                    @go-to-page="goToPage"
                    class="mt-8 pt-6 border-t border-border"
                />
            </div>
        </div>

        <TimeSettingModal
            :is-open="isTimeSettingModalOpen"
            :time-setting="editingTimeSetting"
            @close="closeModal"
        />

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />
    </AppLayout>
</template>
