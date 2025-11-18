<script setup lang="ts">
    import { computed, ref, watch, nextTick } from 'vue';
    import {
        DollarSignIcon,
        CalculatorIcon,
        AlertCircleIcon
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
    }

    interface Emits {
        (e: 'close'): void;
    }

    const props = defineProps<Props>();
    const emit = defineEmits<Emits>();

    const calculatorAmount = ref('');
    const calculatorError = ref('');

    const formattedPlan = computed(() => {
        if (!props.plan) return null;

        const minimum = typeof props.plan.minimum === 'string' ? parseFloat(props.plan.minimum) : props.plan.minimum;
        const maximum = typeof props.plan.maximum === 'string' ? parseFloat(props.plan.maximum) : props.plan.maximum;
        const interest = typeof props.plan.interest === 'string' ? parseFloat(props.plan.interest) : props.plan.interest;
        const repeatTime = typeof props.plan.repeat_time === 'string' ? parseInt(props.plan.repeat_time) : props.plan.repeat_time;
        const capitalBack = props.plan.capital_back_status === 'yes';

        return {
            ...props.plan,
            minimum,
            maximum,
            interest,
            repeatTime,
            capitalBack,
            periodName: props.plan.plan_time_settings?.name || `${props.plan.period} Days`
        };
    });

    const calculatedReturn = computed(() => {
        if (!formattedPlan.value || !calculatorAmount.value) return null;

        const amount = parseFloat(calculatorAmount.value);
        if (isNaN(amount) || amount < formattedPlan.value.minimum || amount > formattedPlan.value.maximum) return null;

        // Calculate interest per cycle
        const interestPerCycle = (amount * formattedPlan.value.interest) / 100;

        // Calculate total interest for all cycles
        const totalInterest = interestPerCycle * formattedPlan.value.repeatTime;

        // Calculate total return (with or without capital back)
        const total = formattedPlan.value.capitalBack ? amount + totalInterest : totalInterest;

        return {
            principal: amount,
            interestPerCycle,
            totalInterest,
            total,
            period: formattedPlan.value.periodName,
            repeatTime: formattedPlan.value.repeatTime
        };
    });

    const isCalculatorValid = computed(() => {
        if (!formattedPlan.value || !calculatorAmount.value) return false;

        const amount = parseFloat(calculatorAmount.value);

        if (isNaN(amount)) return false;
        return !(amount < formattedPlan.value.minimum || amount > formattedPlan.value.maximum);
    });

    const validateCalculatorAmount = () => {
        calculatorError.value = '';

        if (!calculatorAmount.value) {
            calculatorError.value = 'Amount is required for calculation';
            return false;
        }

        const amount = parseFloat(calculatorAmount.value);

        if (isNaN(amount)) {
            calculatorError.value = 'Please enter a valid number';
            return false;
        }

        if (amount <= 0) {
            calculatorError.value = 'Amount must be greater than zero';
            return false;
        }

        if (!formattedPlan.value) {
            calculatorError.value = 'Please select a plan';
            return false;
        }

        if (amount < formattedPlan.value.minimum) {
            calculatorError.value = `Minimum amount is $${formattedPlan.value.minimum.toLocaleString()}`;
            return false;
        }

        if (amount > formattedPlan.value.maximum) {
            calculatorError.value = `Maximum amount is $${formattedPlan.value.maximum.toLocaleString()}`;
            return false;
        }

        return true;
    };

    const handleClose = () => {
        calculatorAmount.value = '';
        calculatorError.value = '';
        emit('close');
    };

    // Watch for amount changes to clear errors
    watch(calculatorAmount, () => {
        if (calculatorError.value && calculatorAmount.value) {
            const amount = parseFloat(calculatorAmount.value);
            if (!isNaN(amount) && amount > 0) {
                calculatorError.value = '';
            }
        }
    });

    // Watch modal open to reset state
    watch(() => props.isOpen, (newVal) => {
        if (newVal && props.plan) {
            nextTick(() => {
                calculatorAmount.value = '';
                calculatorError.value = '';
            });
        }
    });
</script>

<template>
    <QuickActionModal
        :is-open="isOpen"
        title="Earnings Calculator"
        :subtitle="`Calculate potential returns for ${plan?.name || 'this plan'}`"
        @close="handleClose">
        <div v-if="formattedPlan" class="space-y-4">
            <!-- Error Alert -->
            <div v-if="calculatorError" class="p-4 rounded-xl flex items-start gap-3 bg-destructive/10 border border-destructive/20">
                <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5 text-destructive" />
                <p class="text-sm font-semibold text-destructive">{{ calculatorError }}</p>
            </div>

            <!-- Plan Details -->
            <div class="bg-primary/10 border border-primary/20 rounded-lg p-4">
                <h4 class="font-semibold text-card-foreground mb-3 flex items-center gap-2">
                    <CalculatorIcon class="w-5 h-5 text-primary" />
                    {{ formattedPlan.name }}
                </h4>
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
                        <p class="font-semibold" :class="formattedPlan.capitalBack ? 'text-green-600' : 'text-red-600'">
                            {{ formattedPlan.capitalBack ? 'Yes' : 'No' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Amount Input -->
            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-card-foreground">
                    Investment Amount <span class="text-red-500">*</span>
                </h4>
                <div class="relative">
                    <DollarSignIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                    <input
                        v-model="calculatorAmount"
                        type="number"
                        step="0.01"
                        :min="formattedPlan.minimum"
                        :max="formattedPlan.maximum"
                        placeholder="Enter amount to calculate"
                        class="w-full pl-10 pr-4 py-3 input-crypto"
                        @input="validateCalculatorAmount"
                    />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">USD</span>
                </div>
                <p class="text-xs text-muted-foreground">
                    Valid range: ${{ formattedPlan.minimum.toLocaleString() }} -
                    ${{ formattedPlan.maximum.toLocaleString() }}
                </p>
            </div>

            <!-- Calculation Results -->
            <div v-if="calculatedReturn && isCalculatorValid" class="bg-gradient-to-br from-primary/10 to-primary/5 border border-primary/20 rounded-lg p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Principal Amount</span>
                    <span class="text-lg font-semibold text-card-foreground">${{ calculatedReturn.principal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Interest Per Cycle</span>
                    <span class="text-lg font-semibold text-primary">${{ calculatedReturn.interestPerCycle.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Total Interest ({{ calculatedReturn.repeatTime }}x)</span>
                    <span class="text-lg font-semibold text-green-600">${{ calculatedReturn.totalInterest.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                </div>

                <div class="border-t border-primary/20 pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-card-foreground">Total Return</span>
                        <span class="text-2xl font-bold text-primary">${{ calculatedReturn.total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        After {{ calculatedReturn.repeatTime }} cycle{{ calculatedReturn.repeatTime > 1 ? 's' : '' }} of {{ calculatedReturn.period }}
                    </p>
                </div>

                <!-- Breakdown Info -->
                <div class="bg-muted/50 border border-border rounded-lg p-3 mt-3">
                    <p class="text-xs text-muted-foreground">
                        💡 This investment will run for {{ calculatedReturn.repeatTime }} cycle{{ calculatedReturn.repeatTime > 1 ? 's' : '' }},
                        earning ${{ calculatedReturn.interestPerCycle.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        per cycle{{ formattedPlan.capitalBack ? ', with your capital returned at the end' : '' }}.
                    </p>
                </div>
            </div>

            <div v-else class="bg-muted/50 border border-border rounded-lg p-5 text-center">
                <p class="text-sm text-muted-foreground">Enter a valid amount to calculate potential returns</p>
            </div>
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
