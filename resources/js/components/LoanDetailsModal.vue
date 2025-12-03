<script setup lang="ts">
import { ref, computed, watch } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import {
        X,
        User,
        Mail,
        Phone,
        DollarSign,
        Calendar,
        Percent,
        CheckCircle,
        XCircle,
        AlertCircle,
        FileText,
    } from 'lucide-vue-next';
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        phone?: string;
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
        loan_reason: string;
        loan_collateral: string;
        remarks?: string;
        user: User;
    }

    interface Props {
        isOpen: boolean;
        loan: Loan;
    }

    const props = defineProps<Props>();
    const emit = defineEmits(['close']);

    const activeTab = ref<'details' | 'approve' | 'reject'>('details');

    const approveForm = useForm({
        admin_notes: props.loan.admin_notes || ''
    });

    const rejectForm = useForm({
        rejection_reason: props.loan.rejection_reason || ''
    });

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };

    const getUserInitials = (user: User) => {
        return `${user.first_name.charAt(0)}${user.last_name.charAt(0)}`.toUpperCase();
    };

    const handleApprove = () => {
        approveForm.post(route('admin.loans.approve', props.loan.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            }
        });
    };

    const handleReject = () => {
        rejectForm.post(route('admin.loans.reject', props.loan.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            }
        });
    };

    const handleClose = () => {
        if (!approveForm.processing && !rejectForm.processing) {
            emit('close');
            approveForm.reset();
            rejectForm.reset();
            activeTab.value = 'details';
        }
    };

    const canTakeAction = computed(() => props.loan.status === 'pending');

    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm sm:p-4">
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div
                        v-if="isOpen"
                        class="bg-card w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] sm:max-w-4xl flex flex-col rounded-none sm:rounded-2xl overflow-hidden border-border sm:border relative">
                        <!-- Header -->
                        <div class="px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-card">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                        <FileText class="w-5 h-5 text-primary" />
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-card-foreground">Loan Details</h2>
                                        <p class="text-sm text-muted-foreground">Review and manage loan application</p>
                                    </div>
                                </div>

                                <button
                                    @click="handleClose"
                                    :disabled="approveForm.processing || rejectForm.processing"
                                    class="p-2 hover:bg-muted rounded-lg transition-colors disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">
                                    <X class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>

                            <!-- Tabs -->
                            <div class="flex gap-2 mt-4">
                                <button
                                    @click="activeTab = 'details'"
                                    :class="['px-4 py-2 rounded-lg font-semibold text-sm transition-all border border-border cursor-pointer', activeTab === 'details' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted']">
                                    Details
                                </button>

                                <button
                                    v-if="canTakeAction"
                                    @click="activeTab = 'approve'"
                                    :class="['px-4 py-2 rounded-lg font-semibold text-sm transition-all border border-border cursor-pointer', activeTab === 'approve' ? 'bg-green-600 text-white' : 'text-muted-foreground hover:bg-muted']">
                                    <CheckCircle class="w-4 h-4 inline mr-1" />
                                    Approve
                                </button>

                                <button
                                    v-if="canTakeAction"
                                    @click="activeTab = 'reject'"
                                    :class="['px-4 py-2 rounded-lg font-semibold text-sm transition-all border border-border cursor-pointer', activeTab === 'reject' ? 'bg-red-600 text-white' : 'text-muted-foreground hover:bg-muted']">
                                    <XCircle class="w-4 h-4 inline mr-1" />
                                    Reject
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto no-scrollbar">
                            <!-- Details Tab -->
                            <div v-if="activeTab === 'details'" class="px-4 sm:px-6 py-6 space-y-6">
                                <!-- User Information -->
                                <div class="bg-muted/30 rounded-xl p-4">
                                    <h3 class="text-sm font-bold text-card-foreground mb-3 flex items-center gap-2">
                                        <User class="w-4 h-4" />
                                        User Information
                                    </h3>
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xl font-bold text-primary">{{ getUserInitials(loan.user) }}</span>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="font-semibold text-card-foreground">{{ loan.user.first_name }} {{ loan.user.last_name }}</p>
                                            <p class="text-sm text-muted-foreground flex items-center gap-2">
                                                <Mail class="w-3.5 h-3.5" />
                                                {{ loan.user.email }}
                                            </p>
                                            <p v-if="loan.user.phone" class="text-sm text-muted-foreground flex items-center gap-2">
                                                <Phone class="w-3.5 h-3.5" />
                                                {{ loan.user.phone }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loan Summary -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-card border border-border rounded-xl p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <DollarSign class="w-4 h-4 text-primary" />
                                            <p class="text-xs text-muted-foreground">Loan Amount</p>
                                        </div>
                                        <p class="text-2xl font-bold text-card-foreground">
                                            {{ formatCurrency(loan.loan_amount) }}
                                        </p>
                                    </div>

                                    <div class="bg-card border border-border rounded-xl p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Calendar class="w-4 h-4 text-primary" />
                                            <p class="text-xs text-muted-foreground">Tenure</p>
                                        </div>
                                        <p class="text-2xl font-bold text-card-foreground">
                                            {{ loan.tenure_months }} Months
                                        </p>
                                    </div>

                                    <div class="bg-card border border-border rounded-xl p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Percent class="w-4 h-4 text-primary" />
                                            <p class="text-xs text-muted-foreground">Interest Rate</p>
                                        </div>
                                        <p class="text-2xl font-bold text-card-foreground">
                                            {{ loan.interest_rate }}%
                                        </p>
                                    </div>
                                </div>

                                <!-- Payment Breakdown -->
                                <div class="bg-muted/30 rounded-xl p-4 space-y-3">
                                    <h3 class="text-sm font-bold text-card-foreground">Payment Breakdown</h3>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-xs text-muted-foreground mb-1">Monthly EMI</p>
                                            <p class="text-lg font-bold text-primary">{{ formatCurrency(loan.monthly_emi) }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-muted-foreground mb-1">Total Interest</p>
                                            <p class="text-lg font-bold text-card-foreground">{{ formatCurrency(loan.total_interest) }}</p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-muted-foreground mb-1">Total Repayment</p>
                                            <p class="text-lg font-bold text-card-foreground">{{ formatCurrency(loan.total_payment) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loan Information -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-bold text-card-foreground mb-2">Loan Title</label>
                                        <p class="text-sm text-muted-foreground bg-muted/30 rounded-lg p-3">
                                            {{ loan.title }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-card-foreground mb-2">Loan Reason</label>
                                        <p class="text-sm text-muted-foreground bg-muted/30 rounded-lg p-3 whitespace-pre-wrap">
                                            {{ loan.loan_reason }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-card-foreground mb-2">Collateral Information</label>
                                        <p class="text-sm text-muted-foreground bg-muted/30 rounded-lg p-3 whitespace-pre-wrap">
                                            {{ loan.loan_collateral }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Status Information -->
                                <div v-if="loan.status !== 'pending'" class="border-t border-border pt-4">
                                    <div v-if="loan.remarks" class="mb-4">
                                        <label class="block text-sm font-bold text-card-foreground mb-2">Admin Notes</label>
                                        <p class="text-sm text-muted-foreground bg-green-50 dark:bg-green-950/20 rounded-lg p-3 whitespace-pre-wrap">
                                            {{ loan.remarks }}
                                        </p>
                                    </div>

                                    <div v-if="loan.remarks" class="mb-4">
                                        <label class="block text-sm font-bold text-card-foreground mb-2">Rejection Reason</label>
                                        <p class="text-sm text-muted-foreground bg-red-50 dark:bg-red-950/20 rounded-lg p-3 whitespace-pre-wrap">
                                            {{ loan.remarks }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Approve Tab -->
                            <div v-if="activeTab === 'approve'" class="px-4 sm:px-6 py-6">
                                <form @submit.prevent="handleApprove" class="space-y-6">
                                    <div class="flex items-start gap-3 p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded-xl">
                                        <CheckCircle class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <p class="text-sm font-semibold text-green-900 dark:text-green-100 mb-1">Approve This Loan</p>
                                            <p class="text-xs text-green-700 dark:text-green-300">
                                                Approving this loan will activate it and the borrower will be notified immediately.
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="admin_notes" class="block text-sm font-medium text-card-foreground mb-2">
                                            Admin Notes (Optional)
                                        </label>
                                        <textarea id="admin_notes" v-model="approveForm.admin_notes" rows="6" class="w-full px-4 py-3 bg-card border border-border rounded-lg text-sm resize-none input-crypto transition-all" placeholder="Add any notes or conditions for this approval..."></textarea>
                                        <InputError :message="approveForm.errors.admin_notes" />
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <button type="button" @click="activeTab = 'details'" :disabled="approveForm.processing" class="flex-1 px-6 py-3 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-xl font-semibold transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                            Cancel
                                        </button>

                                        <ActionButton :processing="approveForm.processing" class="flex-1 bg-green-600">
                                            Approve Loan
                                        </ActionButton>
                                    </div>
                                </form>
                            </div>

                            <!-- Reject Tab -->
                            <div v-if="activeTab === 'reject'" class="px-4 sm:px-6 py-6">
                                <form @submit.prevent="handleReject" class="space-y-6">
                                    <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-xl">
                                        <AlertCircle class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <p class="text-sm font-semibold text-red-900 dark:text-red-100 mb-1">Reject This Loan</p>
                                            <p class="text-xs text-red-700 dark:text-red-300">
                                                Rejecting this loan will permanently decline it. Please provide a clear reason for the rejection.
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="rejection_reason" class="block text-sm font-medium text-card-foreground mb-2">
                                            Rejection Reason <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="rejection_reason" v-model="rejectForm.rejection_reason" rows="6" class="w-full px-4 py-3 bg-card border border-border rounded-lg text-sm resize-none input-crypto transition-all" placeholder="Provide a detailed reason for rejecting this loan..."></textarea>
                                        <InputError :message="rejectForm.errors.rejection_reason" />
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <button type="button" @click="activeTab = 'details'" :disabled="rejectForm.processing" class="flex-1 px-6 py-3 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-xl font-semibold transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                            Cancel
                                        </button>

                                        <ActionButton :processing="rejectForm.processing" class="flex-1 bg-red-600">
                                            Reject Loan
                                        </ActionButton>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
