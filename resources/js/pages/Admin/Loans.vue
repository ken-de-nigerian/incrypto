<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        History,
        CheckCircle,
        X,
        Clock,
        DollarSign,
        Briefcase,
        Search,
        Eye,
        SlidersHorizontal,
        RefreshCw,
        User,
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
    import LoanDetailsModal from '@/components/LoanDetailsModal.vue';

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        phone?: string;
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

    interface Loan {
        id: number;
        title: string;
        loan_amount: number;
        monthly_emi: number;
        tenure_months: number;
        interest_rate: number;
        total_payment: number;
        total_interest: number;
        status: 'pending' | 'approved' | 'rejected' | 'completed';
        created_at: string;
        due_date?: string;
        loan_reason: string;
        loan_collateral: string;
        admin_notes?: string;
        rejection_reason?: string;
        user: User;
    }

    interface LoanStats {
        total_borrowed: number;
        active_loans: number;
        total_repaid: number;
        pending_requests: number;
    }

    interface Filters {
        search: string;
        status: string;
        date_from: string;
        date_to: string;
        amount_from: number;
        amount_to: number;
        sort_by: string;
        sort_order: string;
    }

    const props = defineProps<{
        auth: {
            notification_count: number;
        };
        loans: PaginatedData<Loan>
        stats: LoanStats;
        filters: Filters;
    }>();

    const isNotificationsModalOpen = ref(false);
    const isFilterOpen = ref(false);
    const selectedLoan = ref<Loan | null>(null);
    const isLoanDetailsModalOpen = ref(false);

    const page = usePage();
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
        { label: 'Loans & Credit' }
    ];

    // Local filter state
    const localFilters = ref({
        search: props.filters.search || '',
        status: props.filters.status || 'all',
        date_from: props.filters.date_from || '',
        date_to: props.filters.date_to || '',
        amount_from: props.filters.amount_from || '',
        amount_to: props.filters.amount_to || '',
        sort_by: props.filters.sort_by || 'created_at',
        sort_order: props.filters.sort_order || 'desc',
    });

    const applyFilters = () => {
        router.get(route('admin.loans.index'), localFilters.value, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        localFilters.value = {
            search: '',
            status: 'all',
            date_from: '',
            date_to: '',
            amount_from: '',
            amount_to: '',
            sort_by: 'created_at',
            sort_order: 'desc',
        };
        applyFilters();
    };

    const viewLoanDetails = (loan: Loan) => {
        selectedLoan.value = loan;
        isLoanDetailsModalOpen.value = true;
    };

    const getStatusBadgeClass = (status: string) => {
        switch (status) {
            case 'approved': return 'bg-green-100 text-green-700 border-green-200';
            case 'completed': return 'bg-blue-100 text-blue-700 border-blue-200';
            case 'rejected': return 'bg-red-100 text-red-700 border-red-200';
            default: return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved': return CheckCircle;
            case 'completed': return CheckCircle;
            case 'rejected': return X;
            default: return Clock;
        }
    };

    const getUserInitials = (user: User) => {
        return `${user.first_name.charAt(0)}${user.last_name.charAt(0)}`.toUpperCase();
    };

    const goToLoansPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['loans'],
        });
    };

    const hasActiveFilters = computed(() => {
        return localFilters.value.search !== '' ||
            localFilters.value.status !== 'all' ||
            localFilters.value.date_from !== '' ||
            localFilters.value.date_to !== '' ||
            localFilters.value.amount_from !== '' ||
            localFilters.value.amount_to !== '';
    });
</script>

<template>
    <Head title="Loans & Credit" />

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
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">Loans & Credit</h1>
                        <p class="text-muted-foreground">Review and manage all loan applications and requests</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-6">
                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <DollarSign class="w-4 h-4" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Total Borrowed</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground truncate">
                        ${{ props.stats.total_borrowed.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Briefcase class="w-4 h-4" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Active Loans</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground">
                        {{ props.stats.active_loans }}
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircle class="w-4 h-4" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Total Repaid</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold truncate">
                        ${{ props.stats.total_repaid.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Clock class="w-4 h-4" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Pending Review</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground">
                        {{ props.stats.pending_requests }}
                    </p>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="mt-6 space-y-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Search Bar -->
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input v-model="localFilters.search" @keyup.enter="applyFilters" type="text" placeholder="Search by title, user name or email..." class="w-full pl-10 pr-4 py-2.5 bg-card border border-border rounded-lg text-sm transition-all" />
                    </div>

                    <!-- Filter Toggle Button -->
                    <button @click="isFilterOpen = !isFilterOpen" :class="['flex items-center gap-2 px-4 py-2.5 bg-card border rounded-lg font-semibold text-sm transition-all hover:bg-muted cursor-pointer', hasActiveFilters ? 'border-primary text-primary' : 'border-border text-card-foreground']">
                        <SlidersHorizontal class="w-4 h-4" />
                        Filters
                        <span v-if="hasActiveFilters" class="w-2 h-2 rounded-full bg-primary"></span>
                    </button>

                    <!-- Apply Filters Button -->
                    <button @click="applyFilters" class="flex items-center gap-2 px-4 py-2.5 bg-primary text-primary-foreground rounded-lg font-semibold text-sm hover:bg-primary/90 transition-all cursor-pointer">
                        <Search class="w-4 h-4" />
                        Search
                    </button>
                </div>

                <!-- Filter Panel -->
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2">
                    <div v-if="isFilterOpen" class="bg-card border border-border rounded-xl p-4 sm:p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Status Filter -->
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Status</label>
                                <select v-model="localFilters.status" class="w-full px-3 py-2 bg-card border border-border rounded-lg text-sm">
                                    <option value="all">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <!-- Date From -->
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Date From</label>
                                <input v-model="localFilters.date_from" type="date" class="w-full px-3 py-2 bg-card border border-border rounded-lg text-sm" />
                            </div>

                            <!-- Date To -->
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Date To</label>
                                <input v-model="localFilters.date_to" type="date" class="w-full px-3 py-2 bg-card border border-border rounded-lg text-sm" />
                            </div>

                            <!-- Sort By -->
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Sort By</label>
                                <select v-model="localFilters.sort_by" class="w-full px-3 py-2 bg-card border border-border rounded-lg text-sm">
                                    <option value="created_at">Date Created</option>
                                    <option value="loan_amount">Loan Amount</option>
                                    <option value="status">Status</option>
                                </select>
                            </div>

                            <!-- Amount From -->
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Min Amount</label>
                                <input v-model.number="localFilters.amount_from" type="number" placeholder="0" class="w-full px-3 py-2 bg-card border border-border rounded-lg text-sm" />
                            </div>

                            <!-- Amount To -->
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Max Amount</label>
                                <input v-model.number="localFilters.amount_to" type="number" placeholder="100000" class="w-full px-3 py-2 bg-card border border-border rounded-lg text-sm" />
                            </div>

                            <!-- Sort Order -->
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Sort Order</label>
                                <select v-model="localFilters.sort_order" class="w-full px-3 py-2 bg-card border border-border rounded-lg text-sm">
                                    <option value="desc">Descending</option>
                                    <option value="asc">Ascending</option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Actions -->
                        <div class="flex justify-end gap-3 pt-2 border-t border-border">
                            <button @click="resetFilters" class="flex items-center gap-2 px-4 py-2 text-muted-foreground hover:text-card-foreground transition-colors text-sm font-medium cursor-pointer">
                                <RefreshCw class="w-4 h-4" />
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Loans Table -->
            <div class="mt-8 mb-8 sm:mb-0">
                <div class="bg-card border border-border rounded-xl p-0 overflow-hidden">
                    <div v-if="props.loans.data.length > 0">
                        <!-- Desktop Table -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50 border-b border-border">
                                    <tr>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">User</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Title</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Amount</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">EMI</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Tenure</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Status</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Date</th>
                                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-6 py-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="loan in props.loans.data" :key="loan.id" class="hover:bg-muted/20 transition-colors">
                                        <!-- User Info -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-sm font-bold text-primary">{{ getUserInitials(loan.user) }}</span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-card-foreground">{{ loan.user.first_name }} {{ loan.user.last_name }}</p>
                                                    <p class="text-xs text-muted-foreground">{{ loan.user.email }}</p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <p class="text-sm font-bold text-card-foreground">{{ loan.title }}</p>
                                        </td>

                                        <td class="px-6 py-4 text-sm font-semibold text-card-foreground">
                                            ${{ parseFloat(loan.loan_amount.toString()).toLocaleString() }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-primary font-semibold">
                                            ${{ parseFloat(loan.monthly_emi.toString()).toLocaleString() }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-muted-foreground">
                                            {{ loan.tenure_months }} Months
                                        </td>

                                        <td class="px-6 py-4">
                                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold uppercase tracking-wide rounded-full border', getStatusBadgeClass(loan.status)]">
                                                <component :is="getStatusIcon(loan.status)" class="w-3 h-3" />
                                                {{ loan.status }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-sm text-muted-foreground">
                                            {{ new Date(loan.created_at).toLocaleDateString() }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <button
                                                @click="viewLoanDetails(loan)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary/20 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                                                <Eye class="w-3.5 h-3.5" />
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="lg:hidden divide-y divide-border">
                            <div v-for="loan in props.loans.data" :key="loan.id" class="p-4 hover:bg-muted/20">
                                <!-- User Info -->
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-bold text-primary">{{ getUserInitials(loan.user) }}</span>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-card-foreground">{{ loan.user.first_name }} {{ loan.user.last_name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ loan.user.email }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h4 class="font-bold text-card-foreground">{{ loan.title }}</h4>
                                        <span class="text-xs text-muted-foreground">{{ new Date(loan.created_at).toLocaleDateString() }}</span>
                                    </div>

                                    <span :class="['inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full border', getStatusBadgeClass(loan.status)]">
                                        <component :is="getStatusIcon(loan.status)" class="w-3 h-3" />
                                        {{ loan.status }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-sm mt-3">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Amount</p>
                                        <p class="font-semibold text-card-foreground">${{ parseFloat(loan.loan_amount.toString()).toLocaleString() }}</p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-xs text-muted-foreground">Monthly EMI</p>
                                        <p class="font-semibold text-primary">${{ parseFloat(loan.monthly_emi.toString()).toLocaleString() }}</p>
                                    </div>
                                </div>

                                <button
                                    @click="viewLoanDetails(loan)"
                                    class="w-full mt-3 flex items-center justify-center gap-2 px-4 py-2 bg-primary/10 text-primary hover:bg-primary/20 rounded-lg text-sm font-semibold transition-colors cursor-pointer">
                                    <Eye class="w-4 h-4" />
                                    View Details
                                </button>
                            </div>
                        </div>

                        <PaginationControls
                            v-if="props.loans.last_page > 1"
                            :links="props.loans.links"
                            :from="props.loans.from"
                            :to="props.loans.to"
                            :total="props.loans.total"
                            @go-to-page="goToLoansPage"
                            class="p-4 sm:p-6 pt-6 border-t border-border"
                        />
                    </div>

                    <div v-else class="flex flex-col items-center justify-center text-center py-16 px-4">
                        <div class="w-16 h-16 rounded-full bg-muted/50 flex items-center justify-center mb-4">
                            <History class="w-8 h-8 text-muted-foreground/50" />
                        </div>

                        <h3 class="text-lg font-bold text-card-foreground mb-2">No Loans Found</h3>
                        <p class="text-sm text-muted-foreground mb-6 max-w-xs mx-auto">
                            {{ hasActiveFilters ? 'No loans match your filter criteria. Try adjusting your filters.' : 'There are no loan requests yet.' }}
                        </p>

                        <button
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
                            <RefreshCw class="w-4 h-4" />
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />

        <LoanDetailsModal
            v-if="selectedLoan"
            :is-open="isLoanDetailsModalOpen"
            :loan="selectedLoan"
            @close="isLoanDetailsModalOpen = false"
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
