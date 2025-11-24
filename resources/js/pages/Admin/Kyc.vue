<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage, router, useForm } from '@inertiajs/vue3';
    import debounce from 'lodash/debounce';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import {
        Search, Circle, Mail, CheckCircle, XCircle, Eye, FileText, AlertCircle, ArrowUpDown
    } from 'lucide-vue-next';
    import PaginationControls from '@/components/PaginationControls.vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';
    import { useFlash } from '@/composables/useFlash';

    const page = usePage();
    const { notify } = useFlash();
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

    interface KycSubmission {
        id: number;
        user_id: number;
        status: 'pending' | 'rejected' | 'verified' | 'unverified';
        first_name: string;
        last_name: string;
        phone_number: string;
        date_of_birth: string;
        country: string;
        state: string;
        city: string;
        address: string;
        id_proof_type: string;
        id_front_proof_url: string;
        id_back_proof_url: string | null;
        address_proof_type: string;
        address_front_proof_url: string;
        rejection_reason: string | null;
        user: {
            id: number;
            first_name: string;
            last_name: string;
            email: string;
            profile: { profile_photo_path: string | null } | null;
        };
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
        users: PaginatedData<KycSubmission>;
        metrics: {
            kyc_unverified: number;
            kyc_rejected: number;
        };
        filters: {
            status: 'pending' | 'rejected' | 'verified' | 'unverified' | null;
        };
    }

    const props = defineProps<Props>();

    const isNotificationsModalOpen = ref(false);
    const isKycDetailsModalOpen = ref(false);
    const selectedKyc = ref<KycSubmission | null>(null);
    const sortBy = ref<'status' | 'date' | 'name'>('date');
    const sortOrder = ref<'asc' | 'desc'>('desc');

    const openNotificationsModal = () => { isNotificationsModalOpen.value = true; };
    const closeNotificationsModal = () => { isNotificationsModalOpen.value = false; };

    const openKycDetailsModal = (kyc: KycSubmission) => {
        selectedKyc.value = kyc;
        isKycDetailsModalOpen.value = true;
    };

    const closeKycDetailsModal = () => {
        isKycDetailsModalOpen.value = false;
        selectedKyc.value = null;
    };

    const approveForm = useForm({});
    const rejectForm = useForm({
        rejection_reason: '',
    });

    const approveKyc = (kycId: number) => {
        notify('warning', 'Are you sure you want to approve this kyc?', {
            title: 'Approve Kyc',
            duration: 0,
            dismissible: true,
            action: {
                label: 'Confirm',
                callback: () => {
                    router.post(route('admin.kyc.approve', kycId), {}, {
                        preserveScroll: true,
                        onSuccess: () => {
                            closeKycDetailsModal();
                        },
                        onError: (errors) => {
                            console.error('Failed to approve kyc:', errors);
                        }
                    });
                }
            }
        });
    };

    const rejectKyc = (kycId: number) => {
        rejectForm.post(route('admin.kyc.reject', kycId), {
            preserveScroll: true,
            onSuccess: () => {
                closeKycDetailsModal();
                rejectForm.reset();
            },
        });
    };

    const form = ref({
        search: '',
        status: props.filters.status || '',
    });

    const filteredAndPagedKyc = computed(() => props.users.data);

    const performFilter = debounce(() => {
        router.get(
            route('admin.kyc.index'),
            {
                search: form.value.search,
                status: form.value.status,
                sort_by: sortBy.value,
                sort_order: sortOrder.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }, 300);

    watch(() => form.value.search, performFilter);
    watch(() => form.value.status, performFilter);
    watch(() => sortBy.value, performFilter);
    watch(() => sortOrder.value, performFilter);

    const goToPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getStatusClass = (status: KycSubmission['status']) => {
        switch (status) {
            case 'pending':
                return 'bg-yellow-500/20 border border-yellow-500/30 text-yellow-600';
            case 'verified':
                return 'bg-success/20 border border-success/30 text-success';
            case 'rejected':
                return 'bg-destructive/20 border border-destructive/30 text-destructive';
            case 'unverified':
                return 'bg-gray-500/20 border border-gray-500/30 text-gray-600';
            default:
                return 'bg-secondary text-secondary-foreground border border-border';
        }
    };

    const getStatusIcon = (status: KycSubmission['status']) => {
        switch (status) {
            case 'pending':
                return AlertCircle;
            case 'verified':
                return CheckCircle;
            case 'rejected':
                return XCircle;
            case 'unverified':
                return Circle;
            default:
                return Circle;
        }
    };

    const status = ref([
        { value: 'pending', label: 'Pending Submissions' },
        { value: 'verified', label: 'Verified Submissions' },
        { value: 'rejected', label: 'Rejected Submissions'},
        { value: 'unverified', label: 'Unverified Submissions' },
    ]);

    const hasActiveFilters = computed(() => form.value.search || form.value.status);

    const clearFilters = () => {
        form.value.search = '';
        form.value.status = '';
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: 'KYC Management' }
    ];

    const toggleSort = (field: 'status' | 'date' | 'name') => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'desc';
        }
    };

    const clearError = (rejectForm: any, field: string) => {
        if (rejectForm.errors[field]) {
            rejectForm.clearErrors(field);
        }
    };
</script>

<template>
    <Head title="KYC Submissions Management" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="mt-8 space-y-6">
                <!-- Header Section -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">KYC Verification Center</h1>
                        <p class="text-muted-foreground">Review and manage user identity verification requests</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                <div class="p-4 bg-card border border-border rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pending Submissions</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ metrics.kyc_unverified }}</p>
                        </div>
                        <AlertCircle class="w-12 h-12 text-yellow-500 opacity-20" />
                    </div>
                </div>

                <div class="p-4 bg-card border border-border rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Rejected Submissions</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ metrics.kyc_rejected }}</p>
                        </div>
                        <XCircle class="w-12 h-12 text-destructive opacity-20" />
                    </div>
                </div>

                <div class="p-4 bg-card border border-border rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Submissions</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ props.users.total }}</p>
                        </div>
                        <FileText class="w-12 h-12 text-primary opacity-20" />
                    </div>
                </div>
            </div>

            <div class="mt-8 space-y-4 p-4 bg-card border border-border rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                            <Search class="w-4 h-4 text-muted-foreground" />
                        </div>
                        <input v-model="form.search" type="text" placeholder="Search by name or email..." class="w-full rounded-lg border border-border input-crypto text-sm font-medium pl-8 h-10 lg:h-auto" />
                    </div>

                    <div>
                        <CustomSelectDropdown
                            v-model="form.status"
                            :options="status"
                            placeholder="All Statuses"
                        />
                    </div>

                    <div class="flex gap-2 col-span-1 sm:col-span-2 lg:col-span-1">
                        <button @click="toggleSort('date')" class="flex-1 flex items-center justify-center gap-2 px-3 py-1 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg text-sm font-medium transition-colors h-10 lg:h-auto cursor-pointer">
                            <ArrowUpDown class="w-4 h-4" />
                            Date {{ sortBy === 'date' ? (sortOrder === 'asc' ? '↑' : '↓') : '' }}
                        </button>
                        <button @click="toggleSort('name')" class="flex-1 flex items-center justify-center gap-2 px-3 py-1 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg text-sm font-medium transition-colors h-10 lg:h-auto cursor-pointer">
                            <ArrowUpDown class="w-4 h-4" />
                            Name {{ sortBy === 'name' ? (sortOrder === 'asc' ? '↑' : '↓') : '' }}
                        </button>
                    </div>

                    <div class="flex gap-2 col-span-1 sm:col-span-2 lg:col-span-1">
                        <button @click="toggleSort('status')" class="flex-1 flex items-center justify-center gap-2 px-3 py-1 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg text-sm font-medium transition-colors h-10 lg:h-auto cursor-pointer">
                            <ArrowUpDown class="w-4 h-4" />
                            Status {{ sortBy === 'status' ? (sortOrder === 'asc' ? '↑' : '↓') : '' }}
                        </button>
                        <button v-if="hasActiveFilters" @click="clearFilters" class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium transition-colors cursor-pointer h-10 lg:h-auto">
                            <XCircle class="w-4 h-4" />
                            <span>Clear</span>
                        </button>
                    </div>

                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-8" :class="{ 'margin-bottom': props.users.last_page <= 1 }">
                <template v-if="filteredAndPagedKyc.length">
                    <div v-for="kyc in filteredAndPagedKyc" :key="kyc.id" class="card-crypto relative">
                        <div class="p-4">
                            <div class="text-center space-y-2">
                                <div class="w-20 h-20 rounded-full mx-auto bg-secondary/70 overflow-hidden flex items-center justify-center border border-border">
                                    <img v-if="kyc.user.profile?.profile_photo_path" :src="kyc.user.profile?.profile_photo_path" :alt="kyc.first_name" loading="lazy" class="h-full w-full object-cover">
                                    <span v-else class="text-3xl font-bold text-foreground">{{ kyc.first_name.charAt(0) }}{{ kyc.last_name.charAt(0) }}</span>
                                </div>

                                <h6 class="text-lg font-semibold text-foreground">
                                    {{ kyc.first_name }} {{ kyc.last_name }}
                                </h6>

                                <div class="flex flex-wrap justify-center gap-2">
                                    <span class="inline-flex items-center space-x-1 text-xs font-semibold rounded-full px-3 py-1" :class="getStatusClass(kyc.status)">
                                        <component :is="getStatusIcon(kyc.status)" class="w-3 h-3" />
                                        <span>{{ kyc.status.charAt(0).toUpperCase() + kyc.status.slice(1) }}</span>
                                    </span>
                                </div>

                                <span class="block text-sm font-medium text-muted-foreground pt-1">
                                    <Mail class="w-3 h-3 inline-block mr-1 align-sub" />{{ kyc.user.email }}
                                </span>

                                <div class="text-xs text-muted-foreground pt-1">
                                    <p>{{ kyc.country }} • {{ kyc.city }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center gap-2">
                            <button @click="openKycDetailsModal(kyc)" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium transition-colors cursor-pointer">
                                <Eye class="w-4 h-4" />
                                <span>Review</span>
                            </button>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="lg:col-span-4 sm:col-span-2 col-span-1">
                        <div class="card-crypto p-10 text-center border-dashed border-border flex flex-col items-center justify-center">
                            <div class="w-24 h-24 mx-auto mb-4">
                                <FileText class="w-full h-full text-muted-foreground" />
                            </div>
                            <h6 class="text-lg font-semibold text-foreground">No KYC submissions found</h6>
                            <p class="text-muted-foreground mt-1">Try adjusting your search terms or status filter.</p>
                        </div>
                    </div>
                </template>
            </div>

            <PaginationControls
                class="margin-bottom"
                v-if="props.users.last_page > 1"
                :links="props.users.links"
                :from="props.users.from"
                :to="props.users.to"
                :total="props.users.total"
                @go-to-page="goToPage"
            />
        </div>
    </AppLayout>

    <QuickActionModal
        :is-open="isKycDetailsModalOpen"
        title="KYC Submission Details"
        subtitle="Review and manage the KYC submission"
        @close="closeKycDetailsModal">

        <div v-if="selectedKyc" class="space-y-6">
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-foreground uppercase tracking-wider">Personal Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-muted/50 rounded-lg">
                    <div>
                        <p class="text-xs text-muted-foreground">First Name</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.first_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Last Name</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.last_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Email</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.user.email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Phone</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.phone_number }}</p>
                    </div>
                    <div class="sm:col-span-2"> <p class="text-xs text-muted-foreground">Date of Birth</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.date_of_birth }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-foreground uppercase tracking-wider">Address Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-muted/50 rounded-lg">
                    <div>
                        <p class="text-xs text-muted-foreground">Country</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.country }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">State</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.state }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">City</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.city }}</p>
                    </div>
                    <div class="col-span-1 sm:col-span-2">
                        <p class="text-xs text-muted-foreground">Address</p>
                        <p class="text-sm font-medium text-foreground">{{ selectedKyc.address }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-foreground uppercase tracking-wider">Documents</h3>

                <div class="p-3 bg-muted/50 rounded-lg space-y-2">
                    <p class="text-xs font-semibold text-foreground">ID Proof ({{ selectedKyc.id_proof_type }})</p>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <a :href="selectedKyc.id_front_proof_url" target="_blank" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg text-xs font-medium transition-colors">
                            <FileText class="w-4 h-4" />
                            Front
                        </a>

                        <a :href="selectedKyc.id_back_proof_url" target="_blank" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg text-xs font-medium transition-colors">
                            <FileText class="w-4 h-4" />
                            Back
                        </a>
                    </div>
                </div>

                <div class="p-3 bg-muted/50 rounded-lg space-y-2">
                    <p class="text-xs font-semibold text-foreground">Address Proof ({{ selectedKyc.address_proof_type }})</p>
                    <div class="flex gap-2">
                        <a :href="selectedKyc.address_front_proof_url" target="_blank" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg text-xs font-medium transition-colors">
                            <FileText class="w-4 h-4" />
                            View Document
                        </a>
                    </div>
                </div>
            </div>

            <div v-if="selectedKyc.status === 'rejected' && selectedKyc.rejection_reason" class="p-3 bg-destructive/10 border border-destructive/30 rounded-lg space-y-2">
                <p class="text-xs font-semibold text-foreground">Rejection Reason</p>
                <p class="text-sm text-foreground">{{ selectedKyc.rejection_reason }}</p>
            </div>

            <div class="p-3 bg-muted/50 rounded-lg">
                <p class="text-xs text-muted-foreground mb-2">Current Status</p>
                <span class="inline-flex items-center space-x-2 text-xs font-semibold rounded-full px-3 py-1" :class="getStatusClass(selectedKyc.status)">
                <component :is="getStatusIcon(selectedKyc.status)" class="w-4 h-4" />
                <span>{{ selectedKyc.status.charAt(0).toUpperCase() + selectedKyc.status.slice(1) }}</span>
            </span>
            </div>

            <div v-if="selectedKyc.status === 'pending'" class="space-y-3 margin-bottom">
                <div class="flex flex-col sm:flex-row gap-2">
                    <ActionButton @click="approveKyc(selectedKyc.id)" :processing="approveForm.processing">
                        Approve
                    </ActionButton>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Rejection Reason (if rejecting)</label>
                    <textarea v-model="rejectForm.rejection_reason" @focus="clearError(rejectForm, 'rejection_reason')" placeholder="Enter reason for rejection..." class="input-crypto w-full resize-none" rows="3" />
                    <InputError :message="rejectForm.errors.rejection_reason" />
                    <ActionButton @click="rejectKyc(selectedKyc.id)" :processing="rejectForm.processing" class="w-full bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Reject
                    </ActionButton>
                </div>
            </div>
        </div>
    </QuickActionModal>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal" />
</template>

<style>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
