<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        CalculatorIcon,
        CalendarIcon,
        ClockIcon,
        DollarSignIcon,
        PercentIcon,
        PiggyBankIcon,
        PlusIcon,
        SearchIcon,
        FilterIcon,
        EditIcon,
        TrashIcon,
        XIcon,
        SettingsIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
    import TextLink from '@/components/TextLink.vue';
    import PlanModal from '@/components/PlanModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import { useFlash } from '@/composables/useFlash';

    interface PlanTimeSetting {
        id: number;
        name: string;
        period: number;
    }

    interface Plan {
        id: number;
        plan_time_settings_id: number;
        name: string;
        minimum: number | string;
        maximum: number | string;
        interest: number | string;
        period: number;
        status: 'active' | 'inactive';
        capital_back_status: 'yes' | 'no';
        repeat_time: number | string;
        plan_time_settings?: PlanTimeSetting;
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
        active: number;
        inactive: number;
    }

    const props = defineProps<{
        plans: PaginatedData<Plan>;
        plan_time_settings: PlanTimeSetting[];
        statistics: Statistics;
        filters: {
            search?: string;
            status?: string;
            time_setting?: string;
        };
    }>();

    const isNotificationsModalOpen = ref(false);
    const isPlanModalOpen = ref(false);
    const editingPlan = ref<Plan | null>(null);
    const showFilters = ref(false);

    const searchQuery = ref(props.filters.search || '');
    const statusFilter = ref(props.filters.status || '');
    const timeSettingFilter = ref(props.filters.time_setting || '');

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
        { label: 'Investments' }
    ];

    const formattedPlans = computed(() => {
        return props.plans.data.map(plan => {
            const minimum = typeof plan.minimum === 'string' ? parseFloat(plan.minimum) : plan.minimum;
            const maximum = typeof plan.maximum === 'string' ? parseFloat(plan.maximum) : plan.maximum;
            const interest = typeof plan.interest === 'string' ? parseFloat(plan.interest) : plan.interest;
            const repeatTime = typeof plan.repeat_time === 'string' ? parseInt(plan.repeat_time) : plan.repeat_time;
            const capitalBack = plan.capital_back_status === 'yes';

            return {
                ...plan,
                minimum,
                maximum,
                interest,
                repeatTime,
                capitalBack,
                periodName: plan.plan_time_settings?.name || `${plan.period} Days`
            };
        });
    });

    const openCreatePlanModal = () => {
        editingPlan.value = null;
        isPlanModalOpen.value = true;
    };

    const openEditPlanModal = (plan: Plan) => {
        editingPlan.value = plan;
        isPlanModalOpen.value = true;
    };

    const closePlanModal = () => {
        isPlanModalOpen.value = false;
        editingPlan.value = null;
    };

    const deletePlan = (planId: number) => {
        notify('error', 'Are you sure you want to delete this plan?', {
            title: 'Confirm Delete',
            duration: 0,
            dismissible: true,
            action: {
                label: 'Confirm',
                callback: () => {
                    router.delete(route('admin.plans.destroy', planId), {}, {
                        preserveScroll: true,
                        only: ['plans'],
                        onSuccess: () => {},
                        onError: (errors) => {
                            console.error('Failed to delete plan:', errors);
                        }
                    });
                }
            }
        });
    };

    const applyFilters = () => {
        router.get(route('admin.plans.index'), {
            search: searchQuery.value,
            status: statusFilter.value,
            time_setting: timeSettingFilter.value
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const clearFilters = () => {
        searchQuery.value = '';
        statusFilter.value = '';
        timeSettingFilter.value = '';
        router.get(route('admin.plans.index'), {}, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const statusFilterOptions = [
        { value: '', label: 'All Status' },
        { value: 'active', label: 'Active' },
        { value: 'inactive', label: 'Inactive' },
    ];

    const timeSettingsOptions = computed(() => {
        return [
            { value: '', label: 'All Time Settings' },
            ...(props.plan_time_settings || []).map(time => ({
                value: String(time.id),
                label: time.name
            }))
        ];
    });

    watch([searchQuery], () => {
        const timeout = setTimeout(() => {
            applyFilters();
        }, 500);
        return () => clearTimeout(timeout);
    });

    const goToPlansPage = (url: string) => {
        router.get(url, {
            search: searchQuery.value,
            status: statusFilter.value,
            time_setting: timeSettingFilter.value
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['plans'],
        });
    };
</script>

<template>
    <Head title="Investment Plans" />

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
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">Investment Plans</h1>
                        <p class="text-muted-foreground">Manage investment plans and configurations</p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-card border border-border rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">Total Plans</p>
                                <p class="text-2xl font-bold text-card-foreground">{{ statistics.total }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center">
                                <PiggyBankIcon class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-card border border-border rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">Active Plans</p>
                                <p class="text-2xl font-bold text-card-foreground">{{ statistics.active }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center">
                                <CalculatorIcon class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-card border border-border rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">Inactive Plans</p>
                                <p class="text-2xl font-bold text-card-foreground">{{ statistics.inactive }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-secondary flex items-center justify-center">
                                <XIcon class="w-6 h-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1 relative">
                            <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search plans..."
                                class="w-full pl-10 pr-4 py-2 bg-background border border-border rounded-lg text-card-foreground"
                            />
                        </div>

                        <button
                            @click="showFilters = !showFilters"
                            class="flex items-center justify-center gap-2 px-4 py-2 bg-background border border-border text-card-foreground rounded-lg hover:bg-muted transition-colors cursor-pointer">
                            <FilterIcon class="w-5 h-5" />
                            <span>Filters</span>
                        </button>
                    </div>

                    <div v-if="showFilters" class="mt-4 pt-4 border-t border-border grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Status</h4>
                            <CustomSelectDropdown
                                v-model="statusFilter"
                                @user-interacted="applyFilters"
                                :options="statusFilterOptions"
                                placeholder="Select Status"
                            />
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Time Setting</h4>
                            <CustomSelectDropdown
                                v-model="timeSettingFilter"
                                @user-interacted="applyFilters"
                                :options="timeSettingsOptions"
                                placeholder="Select Time Settings"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <button
                                @click="clearFilters"
                                class="w-full sm:w-auto px-6 py-2 bg-muted text-card-foreground rounded-lg hover:bg-muted/80 transition-colors cursor-pointer">
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 mb-8 sm:mb-0">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                    <h2 class="text-xl sm:text-2xl font-bold text-card-foreground">
                        Investment Plans
                    </h2>

                    <div class="flex flex-wrap gap-3">
                        <TextLink
                            :href="route('admin.time.index')"
                            class="inline-flex justify-center items-center gap-2 px-4 py-2 bg-secondary border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-secondary/80 transition-colors">
                            <SettingsIcon class="w-4 h-4" />
                            <span>Time Settings</span>
                        </TextLink>

                        <button
                            @click="openCreatePlanModal"
                            class="inline-flex justify-center items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
                            <PlusIcon class="w-4 h-4" />
                            <span>Create Plan</span>
                        </button>
                    </div>
                </div>

                <div v-if="formattedPlans.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <div
                        v-for="plan in formattedPlans"
                        :key="plan.id"
                        class="bg-card border border-border rounded-2xl p-5 hover:border-primary/50 transition-all duration-200 group flex flex-col">

                        <div class="flex items-start justify-between mb-4 pb-4 border-b border-border/50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-secondary flex items-center justify-center">
                                    <PiggyBankIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-card-foreground">{{ plan.name }}</h3>
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full border"
                                    :class="plan.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'">
                                    {{ plan.status }}
                                </span>

                                <span
                                    class="inline-block px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full border"
                                    :class="plan.capitalBack ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'">
                                    {{ plan.capitalBack ? 'Capital Back' : 'No Return' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-x-4 gap-y-5 mb-6">
                            <div>
                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 mb-1">
                                    <DollarSignIcon class="w-3.5 h-3.5" />
                                    Range (Min - Max)
                                </p>
                                <p class="text-sm font-bold text-card-foreground truncate">
                                    ${{ plan.minimum.toLocaleString() }} - ${{ plan.maximum.toLocaleString() }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 mb-1">
                                    <PercentIcon class="w-3.5 h-3.5" />
                                    Interest ROI
                                </p>
                                <p class="text-sm font-extrabold">{{ plan.interest }}%</p>
                            </div>

                            <div>
                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 mb-1">
                                    <CalendarIcon class="w-3.5 h-3.5" />
                                    Duration
                                </p>
                                <p class="text-sm font-semibold text-card-foreground">{{ plan.periodName }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 mb-1">
                                    <ClockIcon class="w-3.5 h-3.5" />
                                    Repeat Time
                                </p>
                                <p class="text-sm font-semibold text-card-foreground">{{ plan.repeatTime }}x</p>
                            </div>
                        </div>

                        <div class="mt-auto grid grid-cols-2 gap-3">
                            <button
                                @click="openEditPlanModal(plan)"
                                class="flex items-center justify-center gap-2 py-3 bg-primary/10 border border-border text-card-foreground rounded-xl font-semibold hover:bg-muted transition-all active:scale-[0.98] cursor-pointer touch-manipulation">
                                <EditIcon class="w-4 h-4" />
                                Edit
                            </button>

                            <button
                                @click="deletePlan(plan.id)"
                                class="flex items-center justify-center gap-2 py-3 bg-red-50 border border-red-200 text-red-600 rounded-xl font-semibold cursor-pointer touch-manipulation">
                                <TrashIcon class="w-4 h-4" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center text-center py-16 bg-card border border-border rounded-2xl">
                    <PiggyBankIcon class="w-16 h-16 text-muted-foreground/30 mb-4" />
                    <h3 class="text-lg font-semibold text-card-foreground mb-2">No Plans Available</h3>
                    <p class="text-sm text-muted-foreground mb-4">Create your first investment plan to get started.</p>
                    <button
                        @click="openCreatePlanModal"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
                        <PlusIcon class="w-5 h-5" />
                        Create Plan
                    </button>
                </div>

                <PaginationControls
                    v-if="plans.last_page > 1"
                    :links="plans.links"
                    :from="plans.from"
                    :to="plans.to"
                    :total="plans.total"
                    @go-to-page="goToPlansPage"
                    class="mt-8 pt-6 border-t border-border"
                />
            </div>
        </div>

        <PlanModal
            :is-open="isPlanModalOpen"
            :plan="editingPlan"
            :time-settings="plan_time_settings"
            @close="closePlanModal"
        />

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />
    </AppLayout>
</template>

<style scoped>
    button:focus-visible,
    input:focus-visible,
    select:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }
</style>
