<script setup lang="ts">
    import { computed, ref, watch, nextTick } from 'vue';
    import { router } from '@inertiajs/vue3';
    import {
        DollarSignIcon,
        AlertCircleIcon,
        Loader2Icon
    } from 'lucide-vue-next';
    import QuickActionModal from '@/components/QuickActionModal.vue';

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

    interface Props {
        isOpen: boolean;
        plan: Plan | null;
        liveBalance: number;
        isLiveMode: boolean;
    }

    interface Emits {
        (e: 'close'): void;
    }

    const props = defineProps<Props>();
    const emit = defineEmits<Emits>();

    const investmentAmount = ref('');
    const investmentError = ref('');
    const isSubmitting = ref(false);

    const formattedPlan = computed(() => {
        if (!props.plan) return null;

        const minimum = typeof props.plan.minimum === 'string' ? parseFloat(props.plan.minimum) : props.plan.minimum;
        const maximum = typeof props.plan.maximum === 'string' ? parseFloat(props.plan.maximum) : props.plan.maximum;
        const interest = typeof props.plan.interest === 'string' ? parseFloat(props.plan.interest) : props.plan.interest;
        const repeatTime = typeof props.plan.repeat_time === 'string' ? parseInt(props.plan.repeat_time) : props.plan.repeat_time;

        return {
            ...props.plan,
            minimum,
            maximum,
            interest,
            repeatTime,
            periodName: props.plan.plan_time_settings?.name || `${props.plan.period} Days`
        };
    });

    const projectedReturns = computed(() => {
        if (!formattedPlan.value || !investmentAmount.value) return null;

        const amount = parseFloat(investmentAmount.value);
        if (isNaN(amount) || amount < formattedPlan.value.minimum || amount > formattedPlan.value.maximum) return null;

        // Calculate interest per cycle
        const interestPerCycle = (amount * formattedPlan.value.interest) / 100;

        // Calculate total interest for all cycles
        const totalInterest = interestPerCycle * formattedPlan.value.repeatTime;

        // Calculate total return (with or without capital back)
        const capitalBack = formattedPlan.value.capital_back_status === 'yes';
        const totalReturn = capitalBack ? amount + totalInterest : totalInterest;

        return {
            interestPerCycle,
            totalInterest,
            totalReturn,
            repeatTime: formattedPlan.value.repeatTime
        };
    });

    const isInvestmentValid = computed(() => {
        if (!formattedPlan.value || !investmentAmount.value) return false;

        const amount = parseFloat(investmentAmount.value);

        if (isNaN(amount)) return false;
        if (amount < formattedPlan.value.minimum || amount > formattedPlan.value.maximum) return false;
        if (amount > props.liveBalance) return false;
        return props.isLiveMode;
    });

    const validateInvestmentAmount = () => {
        investmentError.value = '';

        if (!props.isLiveMode) {
            investmentError.value = 'Investments can only be made in Live Mode. Please switch to Live Mode to continue.';
            return false;
        }

        if (!investmentAmount.value) {
            investmentError.value = 'Investment amount is required';
            return false;
        }

        const amount = parseFloat(investmentAmount.value);

        if (isNaN(amount)) {
            investmentError.value = 'Please enter a valid number';
            return false;
        }

        if (amount <= 0) {
            investmentError.value = 'Amount must be greater than zero';
            return false;
        }

        if (!formattedPlan.value) {
            investmentError.value = 'Please select a plan';
            return false;
        }

        if (amount < formattedPlan.value.minimum) {
            investmentError.value = `Minimum investment is $${formattedPlan.value.minimum.toLocaleString()}`;
            return false;
        }

        if (amount > formattedPlan.value.maximum) {
            investmentError.value = `Maximum investment is $${formattedPlan.value.maximum.toLocaleString()}`;
            return false;
        }

        if (amount > props.liveBalance) {
            investmentError.value = 'Insufficient live balance. Please deposit funds first.';
            return false;
        }

        return true;
    };

    const submitInvestment = () => {
        if (isSubmitting.value) return;

        if (!validateInvestmentAmount()) {
            return;
        }

        isSubmitting.value = true;

        router.post(route('user.trade.investment.execute'), {
            plan_id: props.plan!.id,
            amount: parseFloat(investmentAmount.value)
        }, {
            preserveScroll: true,
            onSuccess: () => {
                handleClose();
            },
            onError: (errors) => {
                isSubmitting.value = false;
                if (errors.amount) {
                    investmentError.value = errors.amount;
                } else if (errors.plan_id) {
                    investmentError.value = 'Invalid plan selected';
                } else if (errors.trading_status) {
                    investmentError.value = 'Investments can only be made in Live Mode';
                } else {
                    investmentError.value = 'An error occurred. Please try again.';
                }
            },
            onFinish: () => {
                isSubmitting.value = false;
            }
        });
    };

    const handleClose = () => {
        investmentAmount.value = '';
        investmentError.value = '';
        isSubmitting.value = false;
        emit('close');
    };

    const setMaxAmount = () => {
        if (!formattedPlan.value || !props.isLiveMode) return;
        investmentAmount.value = Math.min(props.liveBalance, formattedPlan.value.maximum).toString();
        validateInvestmentAmount();
    };

    // Watch for amount changes to clear errors
    watch(investmentAmount, () => {
        if (investmentError.value && investmentAmount.value) {
            const amount = parseFloat(investmentAmount.value);
            if (!isNaN(amount) && amount > 0) {
                investmentError.value = '';
            }
        }
    });

    // Watch modal open to reset state
    watch(() => props.isOpen, (newVal) => {
        if (newVal && props.plan) {
            nextTick(() => {
                investmentAmount.value = '';
                if (!props.isLiveMode) {
                    investmentError.value = 'Investments can only be made in Live Mode. Please switch to Live Mode to continue.';
                } else {
                    investmentError.value = '';
                }
            });
        }
    });

    // Watch live mode changes
    watch(() => props.isLiveMode, (newVal) => {
        if (!newVal && props.isOpen) {
            investmentError.value = 'Investments can only be made in Live Mode. Please switch to Live Mode to continue.';
        }
    });
</script>

<template>
    <QuickActionModal
        :is-open="isOpen"
        title="Start Investment"
        :subtitle="`Invest in ${plan?.name || 'this plan'} and start earning returns`"
        @close="handleClose">
        <div v-if="formattedPlan" class="space-y-4">
            <!-- Error Alert -->
            <div v-if="investmentError" class="p-4 rounded-xl flex items-start gap-3 bg-destructive/10 border border-destructive/20">
                <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5 text-destructive" />
                <p class="text-sm font-semibold text-destructive">{{ investmentError }}</p>
            </div>

            <!-- Plan Details -->
            <div class="bg-primary/10 border border-primary/20 rounded-lg p-4">
                <h4 class="font-semibold text-card-foreground mb-3">{{ formattedPlan.name }}</h4>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-muted-foreground text-xs">Interest Rate</p>
                        <p class="font-semibold text-primary">{{ formattedPlan.interest }}% ROI</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-xs">Period</p>
                        <p class="font-semibold text-card-foreground">{{ formattedPlan.periodName }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-xs">Repeat Time</p>
                        <p class="font-semibold text-card-foreground">{{ formattedPlan.repeatTime }}x</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-xs">Capital Back</p>
                        <p class="font-semibold" :class="plan?.capital_back_status === 'yes' ? 'text-green-600' : 'text-red-600'">
                            {{ plan?.capital_back_status === 'yes' ? 'Yes' : 'No' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Available Balance -->
            <div class="bg-muted/50 border border-border rounded-lg p-3">
                <p class="text-xs text-muted-foreground mb-1">Available Live Balance</p>
                <p class="text-lg font-bold text-card-foreground">${{ liveBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
            </div>

            <!-- Amount Input -->
            <div class="space-y-2">
                <h4 class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    Investment Amount <span class="text-red-500">*</span>
                </h4>
                <div class="relative">
                    <DollarSignIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                    <input
                        v-model="investmentAmount"
                        type="number"
                        step="0.01"
                        :min="formattedPlan.minimum"
                        :max="formattedPlan.maximum"
                        placeholder="Enter amount"
                        :disabled="!isLiveMode"
                        :class="[
                            'w-full pl-10 pr-4 py-3 input-crypto',
                            !isLiveMode && 'opacity-50 cursor-not-allowed'
                        ]"
                        @input="validateInvestmentAmount"
                    />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">USD</span>
                </div>
                <div class="flex justify-between items-center text-xs text-muted-foreground bg-muted/50 p-2 rounded-lg">
                    <span>Range: ${{ formattedPlan.minimum.toLocaleString() }} - ${{ formattedPlan.maximum.toLocaleString() }}</span>
                    <button
                        @click="setMaxAmount"
                        :disabled="!isLiveMode"
                        class="text-primary font-medium hover:underline cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        Use Max
                    </button>
                </div>
            </div>

            <!-- Projected Returns -->
            <div v-if="projectedReturns && isInvestmentValid" class="bg-gradient-to-br from-green-50 to-green-100/50 border border-green-200 rounded-lg p-4 space-y-2">
                <h5 class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">📊 Projected Returns</h5>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-muted-foreground">Per Cycle</p>
                        <p class="font-semibold text-green-700">${{ projectedReturns.interestPerCycle.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total Interest</p>
                        <p class="font-semibold text-green-700">${{ projectedReturns.totalInterest.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                    </div>
                </div>

                <div class="pt-2 border-t border-green-200">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-muted-foreground">Expected Total Return</span>
                        <span class="text-lg font-bold text-green-700">${{ projectedReturns.totalReturn.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                    </div>
                </div>

                <p class="text-xs text-muted-foreground pt-2">
                    💡 This investment will run for {{ projectedReturns.repeatTime }} cycle{{ projectedReturns.repeatTime > 1 ? 's' : '' }},
                    earning ${{ projectedReturns.interestPerCycle.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} per cycle.
                </p>
            </div>

            <!-- Submit Button -->
            <button
                @click="submitInvestment"
                :disabled="!isInvestmentValid || isSubmitting"
                :class="[
                    'w-full py-3 font-bold rounded-lg transition-opacity text-sm flex items-center justify-center gap-2 cursor-pointer',
                    !isInvestmentValid || isSubmitting ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary hover:opacity-90 text-primary-foreground'
                ]">
                <Loader2Icon v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                <span>{{ isSubmitting ? 'Processing Investment...' : 'Confirm Investment' }}</span>
            </button>
        </div>
    </QuickActionModal>
</template>

<style scoped>
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>
