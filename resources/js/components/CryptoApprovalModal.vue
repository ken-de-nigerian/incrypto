<script setup lang="ts">
    import { ref, watch } from 'vue';
    import { router } from '@inertiajs/vue3';
    import {
        X,
        Loader2 as Loader2Icon,
        ThumbsUp as ThumbsUpIcon,
        ThumbsDown as ThumbsDownIcon,
        AlertTriangle as AlertTriangleIcon,
        Info as InfoIcon,
        Calendar,
        Hash,
        User,
        DollarSign as DollarSignIcon
    } from 'lucide-vue-next';

    interface Transaction {
        id: number;
        user_name?: string;
        type?: string;
        token_symbol?: string;
        amount?: number;
        transaction_hash?: string;
        status: string;
        created_at: string;
    }

    const props = defineProps<{
        isOpen: boolean;
        transaction: Transaction | null;
        actionType: 'approve' | 'reject';
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
    }>();

    const activeTab = ref<'details' | 'action'>('details');
    const approvalAmount = ref('');
    const isProcessing = ref(false);
    const validationErrors = ref<Record<string, string>>({});

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const truncateHash = (hash: string | undefined) => {
        if (!hash) return 'N/A';
        return `${hash.slice(0, 6)}...${hash.slice(-4)}`;
    };

    const validateForm = (): boolean => {
        validationErrors.value = {};

        if (props.actionType === 'approve' && props.transaction?.type === 'received') {
            if (!approvalAmount.value || approvalAmount.value.trim() === '') {
                validationErrors.value.amount = 'Amount is required';
                return false;
            }

            const amount = parseFloat(approvalAmount.value);

            if (isNaN(amount)) {
                validationErrors.value.amount = 'Please enter a valid amount';
                return false;
            }

            if (amount <= 0) {
                validationErrors.value.amount = 'Amount must be greater than 0';
                return false;
            }

            if (amount > 999999999) {
                validationErrors.value.amount = 'Amount is too large';
                return false;
            }
        }

        return true;
    };

    const confirmAction = () => {
        if (!props.transaction) return;

        if (!validateForm()) {
            return;
        }

        isProcessing.value = true;

        const endpoint = props.actionType === 'approve'
            ? route('admin.transaction.approve')
            : route('admin.transaction.reject');

        const payload: any = {
            transaction_id: props.transaction.id,
            transaction_type: props.transaction.type
        };

        if (props.actionType === 'approve' && props.transaction.type === 'received' && approvalAmount.value) {
            payload.amount = parseFloat(approvalAmount.value);
        }

        router.post(endpoint, payload, {
            onSuccess: () => {
                emit('close');
                resetForm();
            },
            onError: (errors) => {
                validationErrors.value = errors;
            },
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    };

    const resetForm = () => {
        activeTab.value = 'details';
        approvalAmount.value = '';
        validationErrors.value = {};
    };

    const handleClose = () => {
        if (!isProcessing.value) {
            resetForm();
            emit('close');
        }
    };

    watch(() => props.isOpen, (newValue) => {
        if (newValue) {
            resetForm();
        } else {
            document.body.style.overflow = '';
        }
    });

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
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm sm:p-4"
            >
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-if="isOpen && transaction"
                        class="bg-card w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] sm:max-w-2xl flex flex-col rounded-none sm:rounded-2xl overflow-hidden border-border sm:border relative"
                    >
                        <!-- Header -->
                        <div class="px-4 sm:px-4 py-4 border-b border-border bg-muted/30 shrink-0">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3 sm:gap-4 overflow-hidden">
                                    <div
                                        v-if="actionType === 'approve'"
                                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-success/20 flex-shrink-0 flex items-center justify-center"
                                    >
                                        <ThumbsUpIcon class="w-5 h-5 sm:w-6 sm:h-6 text-success" />
                                    </div>
                                    <div
                                        v-else
                                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-destructive/20 flex-shrink-0 flex items-center justify-center"
                                    >
                                        <ThumbsDownIcon class="w-5 h-5 sm:w-6 sm:h-6 text-destructive" />
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-lg sm:text-xl font-bold text-card-foreground">
                                            {{ actionType === 'approve' ? 'Approve Transaction' : 'Reject Transaction' }}
                                        </h2>
                                        <p class="text-sm text-muted-foreground truncate">
                                            {{ actionType === 'approve' ? 'Verify and confirm approval' : 'Confirm rejection' }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="handleClose"
                                    :disabled="isProcessing"
                                    class="p-2 -mr-2 hover:bg-muted rounded-lg cursor-pointer transition-colors disabled:opacity-50"
                                >
                                    <X class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>

                            <div class="flex gap-2 border-b border-border/50 -mb-4 pb-0 overflow-x-auto">
                                <button
                                    @click="activeTab = 'details'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'details'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Transaction Details
                                </button>
                                <button
                                    @click="activeTab = 'action'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'action'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    {{ actionType === 'approve' ? 'Approval' : 'Rejection' }}
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto no-scrollbar overscroll-contain px-4 sm:px-4 py-4 bg-background">
                            <!-- Details Tab -->
                            <div v-if="activeTab === 'details'" class="space-y-6 pb-6">
                                <div class="bg-muted/50 rounded-lg">
                                    <h3 class="text-sm font-semibold text-card-foreground mb-3 uppercase tracking-wide">Transaction Summary</h3>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <DollarSignIcon class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">Transaction Type</p>
                                            </div>
                                            <span class="px-2 py-1 text-xs font-semibold text-card-foreground capitalize rounded bg-primary/10 border border-primary/20 inline-block">
                                                {{ transaction.type }}
                                            </span>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <User class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">User</p>
                                            </div>
                                            <p class="text-sm font-semibold text-card-foreground truncate">{{ transaction.user_name }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <Calendar class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">Transaction Date</p>
                                            </div>
                                            <p class="text-sm font-semibold text-card-foreground">{{ formatDate(transaction.created_at) }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <Hash class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">Transaction ID</p>
                                            </div>
                                            <p class="text-sm font-mono text-card-foreground">{{ truncateHash(transaction.transaction_hash) }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3 sm:col-span-2">
                                            <div class="flex items-center gap-2 mb-1">
                                                <AlertTriangleIcon class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">Current Status</p>
                                            </div>
                                            <span class="px-2 py-0.5 text-xs rounded-full border capitalize bg-warning/10 text-warning border-warning/30 inline-block">
                                                {{ transaction.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Tab -->
                            <div v-if="activeTab === 'action'" class="space-y-6 pb-6">
                                <!-- Amount Input for Received Transactions (Approve only) -->
                                <div v-if="actionType === 'approve' && transaction?.type === 'received'" class="space-y-3">
                                    <div class="p-4 bg-card border border-border rounded-lg flex gap-3">
                                        <InfoIcon class="w-5 h-5 text-secondary-foreground mt-0.5 flex-shrink-0" />
                                        <div>
                                            <p class="text-sm font-semibold text-secondary-foreground mb-1">Received Transaction</p>
                                            <p class="text-sm text-secondary-foreground">
                                                Enter the actual amount received from the user. This will be credited to their account.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-6">
                                        <h4 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">
                                            Enter Amount Received *
                                        </h4>
                                        <div class="relative">
                                            <input
                                                v-model="approvalAmount"
                                                type="text"
                                                placeholder="0.00"
                                                class="w-full pl-4 pr-16 py-3 input-crypto border border-border rounded-lg text-sm transition-all"
                                                :class="{ 'border-destructive/50': validationErrors.amount }"
                                            />
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">
                                                {{ transaction.token_symbol }}
                                            </span>
                                        </div>
                                        <p v-if="validationErrors.amount" class="text-xs text-red-600 font-semibold mt-2 flex items-center gap-1">
                                            <AlertTriangleIcon class="w-3 h-3" />
                                            {{ validationErrors.amount }}
                                        </p>
                                        <p v-else class="text-xs text-muted-foreground mt-2">
                                            This is the amount the user actually transferred. Ensure accuracy before confirming.
                                        </p>
                                    </div>
                                </div>

                                <!-- Approval Ready -->
                                <div v-else-if="actionType === 'approve'" class="p-4 border border-border rounded-lg flex gap-3">
                                    <ThumbsUpIcon class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p class="text-sm font-semibold text-green-600 mb-1">Ready to Approve</p>
                                        <p class="text-sm text-green-600">
                                            This transaction will be marked as approved and completed. This action cannot be undone.
                                        </p>
                                    </div>
                                </div>

                                <!-- Rejection Warning -->
                                <div v-if="actionType === 'reject'" class="p-4 border border-border rounded-lg flex gap-3">
                                    <AlertTriangleIcon class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p class="text-sm font-semibold text-red-600 mb-1">Rejection Warning</p>
                                        <p class="text-sm text-red-600">
                                            Once rejected, this transaction will be marked as failed and cannot be recovered.
                                            The user will be notified of the rejection.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-4 sm:px-6 py-4 border-t border-border bg-muted/10 shrink-0 safe-area-pb">
                            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                                <button
                                    @click="handleClose"
                                    :disabled="isProcessing"
                                    class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-background border border-border text-card-foreground rounded-lg font-semibold hover:bg-muted transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Cancel
                                </button>

                                <button
                                    v-if="activeTab === 'details'"
                                    @click="activeTab = 'action'"
                                    class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-primary text-primary-foreground rounded-lg font-semibold hover:bg-primary/90 transition-colors cursor-pointer"
                                >
                                    Continue
                                </button>

                                <button
                                    v-if="activeTab === 'action'"
                                    @click="confirmAction"
                                    :disabled="isProcessing || (actionType === 'approve' && transaction?.type === 'received' && !approvalAmount)"
                                    :class="[
                                        'w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 rounded-lg font-semibold transition-colors inline-flex justify-center items-center gap-2',
                                        isProcessing || (actionType === 'approve' && transaction?.type === 'received' && !approvalAmount)
                                            ? 'bg-muted text-muted-foreground cursor-not-allowed'
                                            : actionType === 'approve'
                                            ? 'bg-success text-white hover:bg-success/90 cursor-pointer'
                                            : 'bg-destructive text-white hover:bg-destructive/90 cursor-pointer'
                                    ]"
                                >
                                    <Loader2Icon v-if="isProcessing" class="w-4 h-4 animate-spin" />
                                    <component :is="actionType === 'approve' ? ThumbsUpIcon : ThumbsDownIcon" v-else class="w-4 h-4" />
                                    {{ isProcessing ? 'Processing...' : actionType === 'approve' ? 'Approve Transaction' : 'Reject Transaction' }}
                                </button>
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

    .safe-area-pb {
        padding-bottom: max(1rem, env(safe-area-inset-bottom));
    }

    ::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    @media (min-width: 1024px) {
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
    }

    ::-webkit-scrollbar-track {
        background: hsl(var(--muted));
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: hsl(var(--muted-foreground) / 0.3);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--muted-foreground) / 0.5);
    }
</style>
