<script setup lang="ts">
    import { ref, computed, watch } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import {
        DollarSign,
        Calendar,
        Percent,
        AlertCircle, X,
        CreditCardIcon
    } from 'lucide-vue-next';
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';
    import { useFlash } from '@/composables/useFlash';

    interface LoanSettings {
        min_amount: number;
        max_amount: number;
        interest_rate: number;
        repayment_period: number;
    }

    interface Props {
        isOpen: boolean;
        loanSettings: LoanSettings;
        isLiveMode: boolean;
    }

    const props = defineProps<Props>();
    const emit = defineEmits(['close']);

    const { error } = useFlash();

    const loanAmount = ref(props.loanSettings.min_amount);
    const tenureMonths = ref(1);
    const interestRate = ref(props.loanSettings.interest_rate);

    const form = useForm({
        loan_amount: loanAmount.value,
        tenure_months: tenureMonths.value,
        interest_rate: interestRate.value,
        title: '',
        loan_reason: '',
        loan_collateral: '',
        monthly_emi: 0,
        total_interest: 0,
        total_payment: 0,
        confirmed: false
    });

    const emiCalculation = computed(() => {
        const principal = loanAmount.value;
        const annualRate = interestRate.value;
        const months = tenureMonths.value;

        if (principal <= 0 || months <= 0 || annualRate < 0) {
            return {
                emi: 0,
                totalPayment: 0,
                totalInterest: 0,
                principal: principal
            };
        }

        const monthlyRate = annualRate / 12 / 100;

        let emi: number;
        if (monthlyRate === 0) {
            emi = principal / months;
        } else {
            const power = Math.pow(1 + monthlyRate, months);
            emi = (principal * monthlyRate * power) / (power - 1);
        }

        const totalPayment = emi * months;
        const totalInterest = totalPayment - principal;

        return {
            emi: Math.round(emi * 100) / 100,
            totalPayment: Math.round(totalPayment * 100) / 100,
            totalInterest: Math.round(totalInterest * 100) / 100,
            principal: principal
        };
    });

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };

    watch(emiCalculation, (calc) => {
        form.monthly_emi = calc.emi;
        form.total_interest = calc.totalInterest;
        form.total_payment = calc.totalPayment;
    });

    watch(loanAmount, (val) => {
        form.loan_amount = val;
    });

    watch(tenureMonths, (val) => {
        form.tenure_months = val;
    });

    watch(interestRate, (val) => {
        form.interest_rate = val;
    });

    const clearError = (field: keyof typeof form.errors) => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };

    const submit = () => {
        if (!form.confirmed) {
            error('Please confirm that all provided information is accurate.', 'Confirmation');
            return;
        }

        const calc = emiCalculation.value;
        form.loan_amount = loanAmount.value;
        form.tenure_months = tenureMonths.value;
        form.interest_rate = interestRate.value;
        form.monthly_emi = calc.emi;
        form.total_interest = calc.totalInterest;
        form.total_payment = calc.totalPayment;

        form.post(route('user.trade.loan.execute'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
                form.reset();
                loanAmount.value = props.loanSettings.min_amount;
                tenureMonths.value = 1;
                interestRate.value = props.loanSettings.interest_rate;
            }
        });
    };

    const handleClose = () => {
        if (!form.processing) {
            emit('close');
            form.reset();
            form.clearErrors();
        }
    };

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
                                        <CreditCardIcon class="w-5 h-5 text-primary" />
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-card-foreground">Loan Request & EMI Calculator</h2>
                                        <p class="text-sm text-muted-foreground">Fill in your desired loan details. EMI is calculated automatically</p>
                                    </div>
                                </div>
                                <button
                                    @click="handleClose"
                                    class="p-2 hover:bg-muted rounded-lg transition-colors disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">
                                    <X class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="flex-1 overflow-y-auto no-scrollbar">
                            <div class="px-4 sm:px-6 py-6 space-y-6">
                                <!-- EMI Summary Cards - Mobile First -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                                    <!-- Monthly EMI - Highlighted -->
                                    <div class="sm:col-span-2 lg:col-span-3 p-4 sm:p-6 bg-gradient-to-br from-primary/10 to-primary/5 border-2 border-primary/30 rounded-xl">
                                        <p class="text-xs sm:text-sm text-muted-foreground mb-2">Monthly EMI Payment</p>
                                        <p class="text-3xl sm:text-4xl font-bold text-primary mb-1">
                                            {{ formatCurrency(emiCalculation.emi) }}
                                        </p>
                                        <p class="text-xs sm:text-sm text-muted-foreground">per month for {{ tenureMonths }} months</p>
                                    </div>

                                    <!-- Principal Amount -->
                                    <div class="p-4 bg-card border border-border rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                            <p class="text-xs text-muted-foreground">Principal Amount</p>
                                        </div>
                                        <p class="text-xl sm:text-2xl font-bold text-card-foreground">
                                            {{ formatCurrency(emiCalculation.principal) }}
                                        </p>
                                    </div>

                                    <!-- Total Interest -->
                                    <div class="p-4 bg-card border border-border rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-blue-300"></div>
                                            <p class="text-xs text-muted-foreground">Total Interest</p>
                                        </div>
                                        <p class="text-xl sm:text-2xl font-bold text-card-foreground">
                                            {{ formatCurrency(emiCalculation.totalInterest) }}
                                        </p>
                                    </div>

                                    <!-- Total Repayment -->
                                    <div class="p-4 bg-card border border-border rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                            <p class="text-xs text-muted-foreground">Total Repayment</p>
                                        </div>
                                        <p class="text-xl sm:text-2xl font-bold text-card-foreground">
                                            {{ formatCurrency(emiCalculation.totalPayment) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Loan Parameters -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                                    <!-- Loan Amount -->
                                    <div>
                                        <label class="flex items-center gap-2 text-sm font-medium text-card-foreground mb-3">
                                            <DollarSign class="w-4 h-4" />
                                            Loan Amount
                                        </label>

                                        <div class="flex items-center gap-2 mb-3">
                                            <span class="text-sm font-semibold text-muted-foreground">$</span>
                                            <input v-model.number="loanAmount" type="number" :min="loanSettings.min_amount" :max="loanSettings.max_amount" class="flex-1 px-3 sm:px-4 py-2 sm:py-2.5 input-crypto border border-border rounded-lg text-sm font-semibold transition-all" />
                                        </div>

                                        <input v-model.number="loanAmount" type="range" :min="loanSettings.min_amount" :max="loanSettings.max_amount" :step="100" class="w-full h-2 bg-muted rounded-lg appearance-none cursor-pointer slider" />
                                        <p class="text-xs text-muted-foreground mt-2">
                                            {{ formatCurrency(loanSettings.min_amount) }} - {{ formatCurrency(loanSettings.max_amount) }}
                                        </p>
                                    </div>

                                    <!-- Tenure -->
                                    <div>
                                        <label class="flex items-center gap-2 text-sm font-medium text-card-foreground mb-3">
                                            <Calendar class="w-4 h-4" />
                                            Loan Tenure
                                        </label>

                                        <div class="flex items-center gap-2 mb-3">
                                            <input v-model.number="tenureMonths" type="number" min="1" :max="loanSettings.repayment_period" class="flex-1 px-3 sm:px-4 py-2 sm:py-2.5 input-crypto border border-border rounded-lg text-sm font-semibold transition-all" />
                                            <span class="text-sm font-semibold text-muted-foreground whitespace-nowrap">Months</span>
                                        </div>

                                        <input v-model.number="tenureMonths" type="range" min="1" :max="loanSettings.repayment_period" class="w-full h-2 bg-muted rounded-lg appearance-none cursor-pointer slider" />
                                        <p class="text-xs text-muted-foreground mt-2">
                                            1 - {{ loanSettings.repayment_period }} months
                                        </p>
                                    </div>

                                    <!-- Interest Rate -->
                                    <div>
                                        <label class="flex items-center gap-2 text-sm font-medium text-card-foreground mb-3">
                                            <Percent class="w-4 h-4" />
                                            Annual Interest Rate
                                        </label>

                                        <div class="flex items-center gap-2 mb-3">
                                            <input v-model.number="interestRate" type="number" min="0" max="50" step="0.1" class="flex-1 px-3 sm:px-4 py-2 sm:py-2.5 input-crypto border border-border rounded-lg text-sm font-semibold transition-all" />
                                            <span class="text-sm font-semibold text-muted-foreground">%</span>
                                        </div>

                                        <input v-model.number="interestRate" type="range" min="0" max="50" step="0.1" class="w-full h-2 bg-muted rounded-lg appearance-none cursor-pointer slider" />
                                        <p class="text-xs text-muted-foreground mt-2">
                                            0% - 50%
                                        </p>
                                    </div>
                                </div>

                                <!-- Loan Information Section -->
                                <div class="border-t border-border pt-6">
                                    <h3 class="text-base sm:text-lg font-bold text-card-foreground mb-2">Loan Information</h3>
                                    <p class="text-xs sm:text-sm text-muted-foreground mb-4 sm:mb-6">
                                        Loan approval is subject to credit assessment and verification. We reserve the right to approve or decline your application.
                                    </p>

                                    <div class="space-y-4">
                                        <!-- Loan Title -->
                                        <div>
                                            <label for="title" class="block text-sm font-medium text-card-foreground mb-2">
                                                Loan Title <span class="text-red-500">*</span>
                                            </label>
                                            <input id="title" v-model="form.title" @focus="clearError('title')" type="text" class="w-full px-3 sm:px-4 py-2 sm:py-2.5 input-crypto border border-border rounded-lg text-sm transition-all" placeholder="e.g., Business Expansion Loan" />
                                            <InputError :message="form.errors.title" />
                                        </div>

                                        <!-- Loan Reason and Collateral -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="loan_reason" class="block text-sm font-medium text-card-foreground mb-2">
                                                    Loan Reason <span class="text-red-500">*</span>
                                                </label>
                                                <textarea id="loan_reason" v-model="form.loan_reason" @focus="clearError('loan_reason')" rows="4" class="w-full px-3 sm:px-4 py-2 sm:py-2.5 input-crypto border border-border rounded-lg text-sm resize-none transition-all" placeholder="Describe why you need this loan..."></textarea>
                                                <InputError :message="form.errors.loan_reason" />
                                            </div>

                                            <div>
                                                <label for="loan_collateral" class="block text-sm font-medium text-card-foreground mb-2">
                                                    Collateral Information <span class="text-red-500">*</span>
                                                </label>
                                                <textarea id="loan_collateral" v-model="form.loan_collateral" @focus="clearError('loan_collateral')" rows="4" class="w-full px-3 sm:px-4 py-2 sm:py-2.5 input-crypto border border-border rounded-lg text-sm resize-none transition-all" placeholder="Describe collateral assets..."></textarea>
                                                <InputError :message="form.errors.loan_collateral" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Confirmation -->
                                <div class="flex items-start gap-3 p-3 sm:p-4 bg-muted/30 border border-border rounded-xl">
                                    <input id="confirmCheck" v-model="form.confirmed" type="checkbox" class="mt-0.5 sm:mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary cursor-pointer flex-shrink-0" />
                                    <label for="confirmCheck" class="text-xs sm:text-sm text-card-foreground cursor-pointer">
                                        I confirm that all provided information is accurate and I understand that loan approval is subject to verification.
                                    </label>
                                </div>

                                <!-- Disclaimer -->
                                <div class="flex items-start gap-3 p-3 sm:p-4 bg-warning/5 border border-warning/20 rounded-xl">
                                    <AlertCircle class="w-4 h-4 sm:w-5 sm:h-5 text-warning flex-shrink-0 mt-0.5" />
                                    <p class="text-xs sm:text-sm text-muted-foreground">
                                        <strong class="text-card-foreground">Important:</strong> You cannot modify loan details after submission. Please review all information carefully before proceeding.
                                    </p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                    <button type="button" @click="handleClose" :disabled="form.processing" class="flex-1 px-4 sm:px-6 py-2.5 sm:py-3 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-xl font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm sm:text-base">
                                        Cancel
                                    </button>

                                    <ActionButton :processing="form.processing" class="flex-1">
                                        Submit Loan Request
                                    </ActionButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
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

    /* Custom slider styles */
    .slider::-webkit-slider-thumb {
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: hsl(var(--primary));
        cursor: pointer;
        border: 3px solid hsl(var(--card));
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .slider::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: hsl(var(--primary));
        cursor: pointer;
        border: 3px solid hsl(var(--card));
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .slider::-webkit-slider-thumb:hover {
        transform: scale(1.1);
    }

    .slider::-moz-range-thumb:hover {
        transform: scale(1.1);
    }
</style>
