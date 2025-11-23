<script setup lang="ts">
    import { ref, computed, watch } from 'vue';
    import { router } from '@inertiajs/vue3';
    import {
        X,
        Loader2 as Loader2Icon,
        CheckCircle as CheckCircleIcon,
        Wallet as WalletIcon,
        TrendingUp as TrendingUpIcon,
        XCircle as XCircleIcon,
        AlertTriangle as AlertTriangleIcon,
        Ban as BanIcon,
        DollarSign as DollarSignIcon,
        Calendar,
        User,
        Percent
    } from 'lucide-vue-next';

    interface Investment {
        id: number;
        user_name?: string;
        plan_name?: string;
        amount?: number;
        interest?: number;
        totalInterestEarned?: number;
        repeat_time_count?: number;
        repeat_time?: number;
        status: string;
    }

    const props = defineProps<{
        isOpen: boolean;
        investment: Investment | null;
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
    }>();

    const activeTab = ref<'details' | 'cancellation'>('details');
    const payoutOption = ref<'capital_and_interest' | 'capital_only' | 'interest_only' | 'nothing'>('capital_and_interest');
    const cancellationReason = ref('');
    const isProcessing = ref(false);
    const validationErrors = ref<Record<string, string>>({});

    const hasEarnedInterest = computed(() => {
        return (props.investment?.totalInterestEarned || 0) > 0;
    });

    const hasCompletedCycles = computed(() => {
        return (props.investment?.repeat_time_count || 0) > 0;
    });

    const totalPayout = computed(() => {
        if (!props.investment) return 0;

        switch (payoutOption.value) {
            case 'capital_and_interest':
                return (props.investment.amount || 0) + (props.investment.totalInterestEarned || 0);
            case 'capital_only':
                return props.investment.amount || 0;
            case 'interest_only':
                return props.investment.totalInterestEarned || 0;
            case 'nothing':
                return 0;
            default:
                return 0;
        }
    });

    const formatAmount = (amount: number | undefined) => {
        if (!amount) return '0.00';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    };

    const validateForm = (): boolean => {
        validationErrors.value = {};

        if (!payoutOption.value) {
            validationErrors.value.payout_option = 'Please select a payout option';
            return false;
        }

        if (cancellationReason.value.length > 500) {
            validationErrors.value.cancellation_reason = 'Reason must not exceed 500 characters';
            return false;
        }

        return true;
    };

    const confirmCancellation = () => {
        if (!props.investment) return;

        if (!validateForm()) {
            return;
        }

        isProcessing.value = true;

        router.patch(route('admin.investment.cancel', props.investment.id), {
            payout_option: payoutOption.value,
            cancellation_reason: cancellationReason.value || null
        }, {
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
        payoutOption.value = hasEarnedInterest.value ? 'capital_and_interest' : 'capital_only';
        cancellationReason.value = '';
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
                        v-if="isOpen && investment"
                        class="bg-card w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] sm:max-w-3xl flex flex-col rounded-none sm:rounded-2xl overflow-hidden border-border sm:border relative"
                    >
                        <!-- Header -->
                        <div class="px-4 sm:px-6 py-4 border-b border-border bg-muted/30 shrink-0">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3 sm:gap-4 overflow-hidden">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-destructive/20 flex-shrink-0 flex items-center justify-center">
                                        <BanIcon class="w-5 h-5 sm:w-6 sm:h-6 text-destructive" />
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-lg sm:text-xl font-bold text-card-foreground">Cancel Investment</h2>
                                        <p class="text-sm text-muted-foreground truncate">{{ investment.plan_name }}</p>
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
                                    Investment Details
                                </button>
                                <button
                                    @click="activeTab = 'cancellation'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'cancellation'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Cancellation Options
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto no-scrollbar overscroll-contain px-4 sm:px-6 py-4 bg-background">
                            <!-- Details Tab -->
                            <div v-if="activeTab === 'details'" class="space-y-6 pb-6">
                                <div class="bg-muted/50 rounded-lg p-4">
                                    <h3 class="text-sm font-semibold text-card-foreground mb-3 uppercase tracking-wide">Investment Summary</h3>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <User class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">User</p>
                                            </div>
                                            <p class="text-sm font-semibold text-card-foreground truncate">{{ investment.user_name }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <TrendingUpIcon class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">Plan</p>
                                            </div>
                                            <p class="text-sm font-semibold text-card-foreground truncate">{{ investment.plan_name }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <DollarSignIcon class="w-4 h-4 text-primary" />
                                                <p class="text-xs text-muted-foreground">Capital</p>
                                            </div>
                                            <p class="text-sm font-semibold text-primary">${{ formatAmount(investment.amount) }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <Percent class="w-4 h-4 text-success" />
                                                <p class="text-xs text-muted-foreground">Interest Rate</p>
                                            </div>
                                            <p class="text-sm font-semibold text-success">{{ investment.interest }}%</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <TrendingUpIcon class="w-4 h-4 text-success" />
                                                <p class="text-xs text-muted-foreground">Earned Interest</p>
                                            </div>
                                            <p class="text-sm font-semibold text-success">${{ formatAmount(investment.totalInterestEarned) }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <Calendar class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">Cycles</p>
                                            </div>
                                            <p class="text-sm font-semibold text-card-foreground">
                                                {{ investment.repeat_time_count }}/{{ investment.repeat_time }}
                                            </p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <AlertTriangleIcon class="w-4 h-4 text-muted-foreground" />
                                                <p class="text-xs text-muted-foreground">Status</p>
                                            </div>
                                            <span class="px-2 py-0.5 text-xs rounded-full border capitalize bg-success/10 text-success border-success/30 inline-block">
                                                {{ investment.status }}
                                            </span>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3 sm:col-span-2">
                                            <div class="flex items-center gap-2 mb-1">
                                                <DollarSignIcon class="w-4 h-4 text-card-foreground" />
                                                <p class="text-xs text-muted-foreground">Total Value</p>
                                            </div>
                                            <p class="text-lg font-bold text-card-foreground">
                                                ${{ formatAmount((investment.amount || 0) + (investment.totalInterestEarned || 0)) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cancellation Tab -->
                            <div v-if="activeTab === 'cancellation'" class="space-y-6 pb-6">
                                <!-- Contextual Info Banner -->
                                <div v-if="hasEarnedInterest" class="p-4 bg-blue-50 border border-blue-200 rounded-lg flex gap-3">
                                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-semibold text-blue-900 mb-1">Interest Already Earned</h4>
                                        <p class="text-sm text-blue-800">
                                            This investment has completed {{ investment.repeat_time_count }} cycle(s) and earned ${{ formatAmount(investment.totalInterestEarned) }} in interest.
                                            You can choose to return the capital plus earned interest, or select an alternative payout option.
                                        </p>
                                    </div>
                                </div>

                                <div v-else class="p-4 bg-amber-50 border border-amber-200 rounded-lg flex gap-3">
                                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-semibold text-amber-900 mb-1">No Interest Earned Yet</h4>
                                        <p class="text-sm text-amber-800">
                                            This investment has not completed any cycles yet, so no interest has been earned or paid out.
                                            The recommended action is to return the original capital only.
                                        </p>
                                    </div>
                                </div>

                                <!-- Payout Options -->
                                <div class="space-y-3">
                                    <label class="text-sm font-semibold text-card-foreground">Select Payout Option *</label>

                                    <!-- Capital and Interest -->
                                    <label
                                        class="block p-4 rounded-lg border-2 cursor-pointer transition-all hover:border-success/50"
                                        :class="payoutOption === 'capital_and_interest'
                                            ? 'border-success bg-success/5'
                                            : 'border-border bg-card'"
                                    >
                                        <div class="flex items-start gap-3">
                                            <input
                                                type="radio"
                                                value="capital_and_interest"
                                                v-model="payoutOption"
                                                class="mt-1 cursor-pointer"
                                            />
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <CheckCircleIcon class="w-5 h-5 text-success" />
                                                    <span class="font-semibold text-card-foreground">Capital + Interest (Full Payout)</span>
                                                    <span v-if="hasEarnedInterest" class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full bg-success text-white">
                                                        Recommended
                                                    </span>
                                                </div>
                                                <p class="text-sm text-muted-foreground mb-2">
                                                    Return both the original capital and all earned interest
                                                </p>
                                                <div class="flex items-center justify-between p-2 bg-success/10 rounded">
                                                    <span class="text-xs text-muted-foreground">User receives:</span>
                                                    <strong class="text-sm text-success">
                                                        ${{ formatAmount((investment?.amount || 0) + (investment?.totalInterestEarned || 0)) }}
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Capital Only -->
                                    <label
                                        class="block p-4 rounded-lg border-2 cursor-pointer transition-all hover:border-primary/50"
                                        :class="payoutOption === 'capital_only'
                                            ? 'border-primary bg-primary/5'
                                            : 'border-border bg-card'"
                                    >
                                        <div class="flex items-start gap-3">
                                            <input
                                                type="radio"
                                                value="capital_only"
                                                v-model="payoutOption"
                                                class="mt-1 cursor-pointer"
                                            />
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <WalletIcon class="w-5 h-5 text-primary" />
                                                    <span class="font-semibold text-card-foreground">Capital Only</span>
                                                    <span v-if="!hasEarnedInterest" class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full bg-primary text-white">
                                                        Recommended
                                                    </span>
                                                </div>
                                                <p class="text-sm text-muted-foreground mb-2">
                                                    {{ hasEarnedInterest
                                                    ? 'Return only the original capital, forfeit all interest earned'
                                                    : 'Return the original capital (no interest to forfeit)'
                                                    }}
                                                </p>
                                                <div class="flex items-center justify-between p-2 bg-primary/10 rounded">
                                                    <span class="text-xs text-muted-foreground">User receives:</span>
                                                    <strong class="text-sm text-primary">
                                                        ${{ formatAmount(investment?.amount || 0) }}
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Interest Only -->
                                    <label
                                        class="block p-4 rounded-lg border-2 transition-all"
                                        :class="[
                                            payoutOption === 'interest_only'
                                                ? 'border-warning bg-warning/5'
                                                : 'border-border bg-card',
                                            !hasEarnedInterest
                                                ? 'opacity-50 cursor-not-allowed'
                                                : 'cursor-pointer hover:border-warning/50'
                                        ]"
                                    >
                                        <div class="flex items-start gap-3">
                                            <input
                                                type="radio"
                                                value="interest_only"
                                                v-model="payoutOption"
                                                :disabled="!hasEarnedInterest"
                                                class="mt-1"
                                                :class="hasEarnedInterest ? 'cursor-pointer' : 'cursor-not-allowed'"
                                            />
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <TrendingUpIcon class="w-5 h-5 text-warning" />
                                                    <span class="font-semibold text-card-foreground">Interest Only</span>
                                                    <span v-if="!hasEarnedInterest" class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full bg-muted text-muted-foreground">
                                                        Not Available
                                                    </span>
                                                </div>
                                                <p class="text-sm text-muted-foreground mb-2">
                                                    {{ hasEarnedInterest
                                                    ? 'Pay only the earned interest, forfeit original capital'
                                                    : 'No interest earned yet - option unavailable'
                                                    }}
                                                </p>
                                                <div class="flex items-center justify-between p-2 bg-warning/10 rounded">
                                                    <span class="text-xs text-muted-foreground">User receives:</span>
                                                    <strong class="text-sm text-warning">
                                                        ${{ formatAmount(investment?.totalInterestEarned || 0) }}
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Nothing -->
                                    <label
                                        class="block p-4 rounded-lg border-2 cursor-pointer transition-all hover:border-destructive/50"
                                        :class="payoutOption === 'nothing'
                                            ? 'border-destructive bg-destructive/5'
                                            : 'border-border bg-card'"
                                    >
                                        <div class="flex items-start gap-3">
                                            <input
                                                type="radio"
                                                value="nothing"
                                                v-model="payoutOption"
                                                class="mt-1 cursor-pointer"
                                            />
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <XCircleIcon class="w-5 h-5 text-destructive" />
                                                    <span class="font-semibold text-card-foreground">No Payout</span>
                                                </div>
                                                <p class="text-sm text-muted-foreground mb-2">
                                                    Forfeit both capital and interest (penalty cancellation)
                                                </p>
                                                <div class="flex items-center justify-between p-2 bg-destructive/10 rounded">
                                                    <span class="text-xs text-muted-foreground">User receives:</span>
                                                    <strong class="text-sm text-destructive">$0.00</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <p v-if="validationErrors.payout_option" class="text-xs text-red-600 font-semibold flex items-center gap-1">
                                        <AlertTriangleIcon class="w-3 h-3" />
                                        {{ validationErrors.payout_option }}
                                    </p>
                                </div>

                                <!-- Cancellation Reason -->
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-card-foreground">Cancellation Reason (Optional)</label>
                                    <textarea
                                        v-model="cancellationReason"
                                        placeholder="Enter reason for cancellation (visible to user)"
                                        maxlength="500"
                                        rows="4"
                                        class="w-full px-3 py-3 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none"
                                        :class="{ 'border-destructive/50': validationErrors.cancellation_reason }"
                                    ></textarea>
                                    <div class="flex items-center justify-between">
                                        <p v-if="validationErrors.cancellation_reason" class="text-xs text-red-600 font-semibold flex items-center gap-1">
                                            <AlertTriangleIcon class="w-3 h-3" />
                                            {{ validationErrors.cancellation_reason }}
                                        </p>
                                        <span class="text-xs text-muted-foreground ml-auto">{{ cancellationReason.length }} / 500</span>
                                    </div>
                                </div>

                                <!-- Confirmation Warning -->
                                <div v-if="payoutOption" class="p-4 bg-orange-50 border border-orange-200 rounded-lg flex gap-3">
                                    <AlertTriangleIcon class="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" />
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-orange-900 mb-1">Confirm Cancellation</p>
                                        <p class="text-sm text-orange-800">
                                            This action will immediately cancel the investment and
                                            <strong>
                                                {{ payoutOption === 'capital_and_interest' ? `credit $${formatAmount(totalPayout)} to` :
                                                payoutOption === 'capital_only' ? `credit $${formatAmount(investment?.amount || 0)} to` :
                                                    payoutOption === 'interest_only' ? `credit $${formatAmount(investment?.totalInterestEarned || 0)} to` :
                                                        'not credit anything to' }}
                                            </strong>
                                            the user's account. This cannot be undone.
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
                                    @click="activeTab = 'cancellation'"
                                    class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-primary text-primary-foreground rounded-lg font-semibold hover:bg-primary/90 transition-colors cursor-pointer"
                                >
                                    Continue
                                </button>

                                <button
                                    v-if="activeTab === 'cancellation'"
                                    @click="confirmCancellation"
                                    :disabled="isProcessing"
                                    :class="[
                                        'w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 rounded-lg font-semibold transition-colors inline-flex justify-center items-center gap-2',
                                        isProcessing
                                            ? 'bg-muted text-muted-foreground cursor-not-allowed'
                                            : 'bg-destructive text-white hover:bg-destructive/90 cursor-pointer'
                                    ]"
                                >
                                    <Loader2Icon v-if="isProcessing" class="w-4 h-4 animate-spin" />
                                    <BanIcon v-else class="w-4 h-4" />
                                    {{ isProcessing ? 'Cancelling...' : 'Cancel Investment' }}
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
