<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        Search,
        UsersIcon,
        XIcon,
        ActivityIcon,
        CheckCircleIcon,
        XCircleIcon,
        EyeIcon,
        BarChart3Icon,
        PlusIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
    import CreateMasterTraderModal from '@/components/CreateMasterTraderModal.vue';
    import ViewMasterTraderModal from '@/components/ViewMasterTraderModal.vue';

    interface UserProfile {
        live_trading_balance: number | string;
        demo_trading_balance: number | string;
        trading_status: 'live' | 'demo';
    }

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        profile: UserProfile;
    }

    interface MasterTrader {
        id: number;
        user_id: number;
        expertise: 'Newcomer' | 'Growing talent' | 'High achiever' | 'Expert' | 'Legend';
        risk_score: number | string;
        gain_percentage: number | string;
        copiers_count: number | string;
        commission_rate: number | string | null;
        total_profit: number | string;
        total_loss: number | string;
        is_active: boolean;
        bio: string | null;
        total_trades: number | string;
        win_rate: number | string;
        created_at: string;
        user: User | null;
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
        total_traders: number;
        active_traders: number;
        inactive_traders: number;
        total_copiers: number;
        total_copy_trades: number;
    }

    const props = defineProps<{
        masterTraders: PaginatedData<MasterTrader>;
        statistics: Statistics;
        availableUsers: User[];
        auth: {
            user: User;
            notification_count: number;
        };
    }>();

    const isNotificationsModalOpen = ref(false);
    const isTraderDetailsModalOpen = ref(false);
    const isCreateModalOpen = ref(false);
    const selectedMasterTrader = ref<MasterTrader | null>(null);

    const page = usePage();

    const urlParams = new URLSearchParams(window.location.search);
    const sortFilter = ref<string>(urlParams.get('sort') || 'risk');
    const expertiseFilter = ref<string>(urlParams.get('expertise') || 'all');
    const statusFilter = ref<string>(urlParams.get('status') || 'active');
    const searchQuery = ref(urlParams.get('search') || '');
    const showFreeTrial = ref(urlParams.get('free_trial') === '1');

    const user = computed(() => page.props.auth?.user as User);
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
        { label: 'Copy Trading Network' }
    ];

    const hasActiveFilters = computed(() => {
        return sortFilter.value !== 'risk' ||
            expertiseFilter.value !== 'all' ||
            statusFilter.value !== 'active' ||
            searchQuery.value !== '' ||
            showFreeTrial.value;
    });

    const getTraderName = (trader: MasterTrader) => {
        if (!trader.user) return 'Unknown Trader';
        return `${trader.user.first_name} ${trader.user.last_name}`;
    };

    const getTraderInitials = (trader: MasterTrader) => {
        if (!trader.user) return '';
        const first = trader.user.first_name?.charAt(0) || '';
        const last = trader.user.last_name?.charAt(0) || '';
        return `${first}${last}`.toUpperCase();
    };

    const getExpertiseColor = (expertise: string) => {
        const colors = {
            'Newcomer': 'bg-gray-100 text-gray-800 border-gray-200',
            'Growing talent': 'bg-blue-100 text-blue-800 border-blue-200',
            'High achiever': 'bg-green-100 text-green-800 border-green-200',
            'Expert': 'bg-cyan-100 text-cyan-800 border-cyan-200',
            'Legend': 'bg-orange-100 text-orange-800 border-orange-200'
        };
        return colors[expertise as keyof typeof colors] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const viewTraderDetails = (trader: MasterTrader) => {
        selectedMasterTrader.value = trader;
        isTraderDetailsModalOpen.value = true;
    };

    const openCreateModal = () => {
        isCreateModalOpen.value = true;
    };

    const applyFilters = () => {
        router.get(route('admin.network.index'), {
            sort: sortFilter.value,
            expertise: expertiseFilter.value,
            status: statusFilter.value,
            search: searchQuery.value,
            free_trial: showFreeTrial.value ? '1' : '0'
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['masterTraders', 'statistics'],
        });
    };

    const clearAllFilters = () => {
        sortFilter.value = 'risk';
        expertiseFilter.value = 'all';
        statusFilter.value = 'active';
        searchQuery.value = '';
        showFreeTrial.value = false;

        router.get(route('admin.network.index'), {}, {
            preserveState: true,
            preserveScroll: false,
            only: ['masterTraders', 'statistics'],
        });
    };

    const goToMasterTradersPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['masterTraders', 'statistics'],
        });
    };

    let searchTimeout: NodeJS.Timeout;
    watch(searchQuery, () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    });

    watch([sortFilter, expertiseFilter, statusFilter, showFreeTrial], () => {
        applyFilters();
    });

    const sortOptions = [
        { value: 'risk', label: 'Lowest Risk' },
        { value: 'gain', label: 'Highest Gain' },
        { value: 'copiers', label: 'Most Copied' },
        { value: 'newest', label: 'Newest First' },
        { value: 'oldest', label: 'Oldest First' }
    ];

    const expertiseOptions = [
        { value: 'all', label: 'All Levels' },
        { value: 'Newcomer', label: 'Newcomer' },
        { value: 'Growing talent', label: 'Growing Talent' },
        { value: 'High achiever', label: 'High Achiever' },
        { value: 'Expert', label: 'Expert' },
        { value: 'Legend', label: 'Legend' }
    ];

    const statusOptions = [
        { value: 'all', label: 'All Status' },
        { value: 'active', label: 'Active Only' },
        { value: 'inactive', label: 'Inactive Only' }
    ];
</script>

<template>
    <Head title="Copy Trading Network" />

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
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">Copy Trading Network</h1>
                        <p class="text-muted-foreground">Monitor and manage all master traders and their activities</p>
                    </div>

                    <button
                        @click="openCreateModal"
                        class="flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl font-semibold hover:bg-primary/90 transition-colors cursor-pointer touch-manipulation whitespace-nowrap"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Add Master Trader
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-8 mb-6">
                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-muted-foreground uppercase">Total Traders</span>
                        <UsersIcon class="w-4 h-4" />
                    </div>
                    <p class="text-2xl font-bold text-card-foreground">{{ props.statistics.total_traders }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-muted-foreground uppercase">Active</span>
                        <CheckCircleIcon class="w-4 h-4" />
                    </div>
                    <p class="text-2xl font-bold text-green-600">{{ props.statistics.active_traders }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-muted-foreground uppercase">Inactive</span>
                        <XCircleIcon class="w-4 h-4" />
                    </div>
                    <p class="text-2xl font-bold text-red-600">{{ props.statistics.inactive_traders }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-muted-foreground uppercase">Total Copiers</span>
                        <ActivityIcon class="w-4 h-4" />
                    </div>
                    <p class="text-2xl font-bold text-card-foreground">{{ props.statistics.total_copiers }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-muted-foreground uppercase">Copy Trades</span>
                        <BarChart3Icon class="w-4 h-4" />
                    </div>
                    <p class="text-2xl font-bold text-card-foreground">{{ props.statistics.total_copy_trades }}</p>
                </div>
            </div>

            <div class="mt-6 mb-8 sm:mb-0">
                <!-- Filters -->
                <div class="bg-card border border-border rounded-xl p-4 mb-6">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col md:flex-row gap-3">
                            <div class="flex-1">
                                <div class="relative">
                                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search traders by name or email..."
                                        class="w-full pl-11 pr-4 py-3 sm:py-2.5 bg-background border border-border rounded-lg text-base sm:text-sm text-card-foreground placeholder:text-muted-foreground transition-all"
                                    />
                                </div>
                            </div>

                            <button
                                v-if="hasActiveFilters"
                                @click="clearAllFilters"
                                class="flex items-center justify-center gap-2 px-4 py-3 sm:py-2.5 bg-background border border-border text-card-foreground rounded-lg text-sm font-semibold hover:bg-destructive hover:text-destructive-foreground hover:border-destructive transition-colors whitespace-nowrap cursor-pointer touch-manipulation">
                                <XIcon class="w-4 h-4" />
                                Clear Filters
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <CustomSelectDropdown
                                v-model="sortFilter"
                                :options="sortOptions"
                                placeholder="Sort by"
                                class="w-full"
                            />

                            <CustomSelectDropdown
                                v-model="expertiseFilter"
                                :options="expertiseOptions"
                                placeholder="Expertise"
                                class="w-full"
                            />

                            <CustomSelectDropdown
                                v-model="statusFilter"
                                :options="statusOptions"
                                placeholder="Status"
                                class="w-full"
                            />

                            <div class="flex items-center gap-3 px-4 py-3 sm:py-2 bg-background border border-border rounded-lg touch-manipulation">
                                <input
                                    v-model="showFreeTrial"
                                    type="checkbox"
                                    id="freeTrial"
                                    class="w-5 h-5 sm:w-4 sm:h-4 text-primary bg-background border-border rounded cursor-pointer"
                                />
                                <label for="freeTrial" class="text-sm font-medium text-card-foreground cursor-pointer whitespace-nowrap flex-1">
                                    Free/Low Commission
                                </label>
                            </div>
                        </div>

                        <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 pt-3 border-t border-border/60">
                            <span class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Active:</span>

                            <span v-if="sortFilter !== 'risk'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                {{ sortOptions.find(o => o.value === sortFilter)?.label }}
                            </span>

                            <span v-if="expertiseFilter !== 'all'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                {{ expertiseOptions.find(o => o.value === expertiseFilter)?.label }}
                            </span>

                            <span v-if="statusFilter !== 'active'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                {{ statusOptions.find(o => o.value === statusFilter)?.label }}
                            </span>

                            <span v-if="searchQuery" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                "{{ searchQuery }}"
                            </span>

                            <span v-if="showFreeTrial" class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium border border-primary/10">
                                Free Trial
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Traders Grid -->
                <div v-if="props.masterTraders.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    <div
                        v-for="trader in props.masterTraders.data"
                        :key="trader.id"
                        class="group bg-card border rounded-2xl overflow-hidden flex flex-col transition-all"
                    >

                        <div class="p-5 flex-1 flex flex-col">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-12 h-12 rounded-full bg-secondary flex-shrink-0 flex items-center justify-center text-lg font-extrabold text-primary relative">
                                        {{ getTraderInitials(trader) }}
                                        <div
                                            class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full border-2 border-card"
                                            :class="trader.is_active ? 'bg-green-500' : 'bg-red-500'"
                                        ></div>
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="font-bold text-card-foreground text-base truncate leading-tight mb-1">
                                            {{ getTraderName(trader) }}
                                        </h3>
                                        <p class="text-xs text-muted-foreground truncate">{{ trader.user?.email }}</p>
                                        <span :class="['inline-block text-[10px] px-2 py-0.5 rounded-full border font-bold uppercase tracking-wide mt-1', getExpertiseColor(trader.expertise)]">
                                            {{ trader.expertise }}
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right flex-shrink-0 pl-2">
                                    <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider mb-0.5">Gain</p>
                                    <p class="text-lg font-black leading-none" :class="parseFloat(trader.gain_percentage as string) >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ parseFloat(trader.gain_percentage as string) >= 0 ? '+' : '' }}{{ parseFloat(trader.gain_percentage as string).toFixed(2) }}%
                                    </p>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div class="bg-muted/30 rounded-xl p-3 grid grid-cols-3 gap-2 mb-4 border border-border/50">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <span class="text-[10px] text-muted-foreground font-semibold mb-1">Risk</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-background border border-border text-card-foreground">
                                        {{ trader.risk_score }}/10
                                    </span>
                                </div>

                                <div class="flex flex-col items-center justify-center text-center border-l border-border/50">
                                    <span class="text-[10px] text-muted-foreground font-semibold mb-1">Copiers</span>
                                    <span class="text-sm font-bold text-card-foreground flex items-center gap-1">
                                        <UsersIcon class="w-3 h-3 text-muted-foreground" />
                                        {{ trader.copiers_count }}
                                    </span>
                                </div>

                                <div class="flex flex-col items-center justify-center text-center border-l border-border/50">
                                    <span class="text-[10px] text-muted-foreground font-semibold mb-1">Fee</span>
                                    <span class="text-sm font-bold" :class="!trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'text-green-600' : 'text-card-foreground'">
                                         {{ !trader.commission_rate || parseFloat(trader.commission_rate as string) === 0 ? 'FREE' : `$${parseFloat(trader.commission_rate as string).toFixed(2)}` }}
                                    </span>
                                </div>
                            </div>

                            <!-- Profit/Loss Bar -->
                            <div class="mt-auto">
                                <div class="flex items-end justify-between text-xs mb-2 px-0.5">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-muted-foreground font-semibold">Profit</span>
                                        <span class="font-bold text-green-600">${{ parseFloat(trader.total_profit as string).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}</span>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] text-muted-foreground font-semibold">Loss</span>
                                        <span class="font-bold text-red-600">${{ parseFloat(trader.total_loss as string).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}</span>
                                    </div>
                                </div>

                                <div class="w-full h-1 bg-muted rounded-full overflow-hidden flex">
                                    <div
                                        class="h-full bg-green-500"
                                        :style="{ width: `${Math.round((parseFloat(trader.total_profit as string) / (parseFloat(trader.total_profit as string) + parseFloat(trader.total_loss as string)) * 100) || 50 )}%` }">
                                    </div>
                                    <div class="h-full w-px bg-background"></div>
                                    <div class="h-full bg-red-500"
                                         :style="{ width: `${Math.round((parseFloat(trader.total_loss as string) / (parseFloat(trader.total_profit as string) + parseFloat(trader.total_loss as string)) * 100) || 50 )}%` }">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Action Buttons -->
                        <div class="p-4 pt-0 mt-1 grid grid-cols-1 gap-2">
                            <button
                                @click="viewTraderDetails(trader)"
                                class="flex items-center justify-center gap-1.5 py-2.5 rounded-lg font-semibold text-xs transition-all bg-primary/10 text-primary border border-border touch-manipulation cursor-pointer hover:bg-primary/20">
                                <EyeIcon class="w-3.5 h-3.5" />
                                <span>View & Manage</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-card border border-border rounded-xl p-12 text-center">
                    <UsersIcon class="w-16 h-16 text-muted-foreground/30 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-card-foreground mb-2">No Master Traders Found</h3>
                    <p class="text-sm text-muted-foreground mb-6 max-w-xs mx-auto">Try adjusting your filters to see more traders.</p>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearAllFilters"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:bg-primary/90 transition-colors cursor-pointer touch-manipulation">
                        <XIcon class="w-4 h-4" />
                        Clear All Filters
                    </button>
                </div>

                <!-- Pagination -->
                <PaginationControls
                    v-if="props.masterTraders.last_page > 1"
                    :links="props.masterTraders.links"
                    :from="props.masterTraders.from"
                    :to="props.masterTraders.to"
                    :total="props.masterTraders.total"
                    @go-to-page="goToMasterTradersPage"
                    class="mt-8 pt-6 border-t border-border"
                />
            </div>
        </div>

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />

        <ViewMasterTraderModal
            :is-open="isTraderDetailsModalOpen"
            :master-trader="selectedMasterTrader"
            @close="isTraderDetailsModalOpen = false;
            selectedMasterTrader = null"
        />

        <CreateMasterTraderModal
            :is-open="isCreateModalOpen"
            :users="props.availableUsers"
            @close="isCreateModalOpen = false"
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
