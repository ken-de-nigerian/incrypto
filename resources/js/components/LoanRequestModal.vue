<script setup lang="ts">
    import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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

    const { success, error } = useFlash();

    // Form state
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

    // Refs for chart
    const chartCanvas = ref<HTMLCanvasElement | null>(null);
    let chartInstance: any = null;

    // EMI Calculation
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

        // Monthly interest rate
        const monthlyRate = annualRate / 12 / 100;

        let emi: number;
        if (monthlyRate === 0) {
            // If interest rate is 0%, simple division
            emi = principal / months;
        } else {
            // EMI formula: [P x R x (1+R)^N] / [(1+R)^N-1]
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

    // Format currency
    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };

    // Update form values when calculations change
    watch(emiCalculation, (calc) => {
        form.monthly_emi = calc.emi;
        form.total_interest = calc.totalInterest;
        form.total_payment = calc.totalPayment;
    });

    // Sync slider with input
    watch(loanAmount, (val) => {
        form.loan_amount = val;
    });

    watch(tenureMonths, (val) => {
        form.tenure_months = val;
    });

    watch(interestRate, (val) => {
        form.interest_rate = val;
    });

    // Initialize/update chart
    const updateChart = async () => {
        if (!chartCanvas.value) return;

        // Dynamically import Chart.js
        const Chart = (await import('chart.js/auto')).default;

        const calc = emiCalculation.value;

        // Destroy existing chart
        if (chartInstance) {
            chartInstance.destroy();
        }

        const ctx = chartCanvas.value.getContext('2d');
        if (!ctx) return;

        chartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Principal', 'Interest'],
                datasets: [{
                    data: [calc.principal, calc.totalInterest],
                    backgroundColor: [
                        'hsl(var(--primary))',
                        'hsl(var(--primary) / 0.3)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                return `${label}: ${formatCurrency(value)}`;
                            }
                        }
                    }
                }
            }
        });
    };

    // Watch for chart updates
    watch([loanAmount, tenureMonths, interestRate], () => {
        updateChart();
    }, { immediate: false });

    // Initialize chart when modal opens
    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            setTimeout(() => {
                updateChart();
            }, 100);
        }
    });

    onMounted(() => {
        if (props.isOpen) {
            updateChart();
        }
    });

    onUnmounted(() => {
        if (chartInstance) {
            chartInstance.destroy();
        }
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

        form.post(route('user.loan.store'), {
            preserveScroll: true,
            onSuccess: () => {
                success('Your loan request has been submitted successfully!', 'Loan Request Submitted');
                emit('close');
                form.reset();
                loanAmount.value = props.loanSettings.min_amount;
                tenureMonths.value = 1;
                interestRate.value = props.loanSettings.interest_rate;
            },
            onError: () => {
                error('Failed to submit loan request. Please try again.', 'Error');
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

                        <form @submit.prevent="submit" class="flex-1 overflow-y-auto no-scrollbar px-4 sm:px-6 py-6">
                            <div class="grid lg:grid-cols-2 gap-8 mb-8">
                                <!-- Left Column: Inputs -->
                                <div class="space-y-6">
                                    <!-- Loan Amount -->
                                    <div>
                                        <label class="block text-sm font-medium text-card-foreground mb-2">
                                            <DollarSign class="w-4 h-4 inline mr-1" />
                                            Loan Amount
                                        </label>

                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-sm font-semibold text-muted-foreground">$</span>
                                            <input v-model.number="loanAmount" type="number" :min="loanSettings.min_amount" :max="loanSettings.max_amount" class="flex-1 px-4 py-2.5 input-crypto border border-border rounded-lg text-sm font-semibold" />
                                        </div>

                                        <input v-model.number="loanAmount" type="range" :min="loanSettings.min_amount" :max="loanSettings.max_amount" :step="100" class="w-full h-2 bg-muted rounded-lg appearance-none cursor-pointer slider" />
                                        <p class="text-xs text-muted-foreground mt-2">
                                            Range: {{ formatCurrency(loanSettings.min_amount) }} - {{ formatCurrency(loanSettings.max_amount) }}
                                        </p>
                                    </div>

                                    <!-- Tenure -->
                                    <div>
                                        <label class="block text-sm font-medium text-card-foreground mb-2">
                                            <Calendar class="w-4 h-4 inline mr-1" />
                                            Loan Tenure (Months)
                                        </label>

                                        <div class="flex items-center gap-2 mb-2">
                                            <input v-model.number="tenureMonths" type="number" min="1" :max="loanSettings.repayment_period" class="flex-1 px-4 py-2.5 input-crypto border border-border rounded-lg text-sm font-semibold" />
                                            <span class="text-sm font-semibold text-muted-foreground">Months</span>
                                        </div>

                                        <input v-model.number="tenureMonths" type="range" min="1" :max="loanSettings.repayment_period" class="w-full h-2 bg-muted rounded-lg appearance-none cursor-pointer slider" />
                                        <p class="text-xs text-muted-foreground mt-2">
                                            Range: 1 - {{ loanSettings.repayment_period }} months
                                        </p>
                                    </div>

                                    <!-- Interest Rate -->
                                    <div>
                                        <label class="block text-sm font-medium text-card-foreground mb-2">
                                            <Percent class="w-4 h-4 inline mr-1" />
                                            Annual Interest Rate
                                        </label>

                                        <div class="flex items-center gap-2 mb-2">
                                            <input v-model.number="interestRate" type="number" min="0" max="50" step="0.1" class="flex-1 px-4 py-2.5 input-crypto border border-border rounded-lg text-sm font-semibold" />
                                            <span class="text-sm font-semibold text-muted-foreground">%</span>
                                        </div>

                                        <input v-model.number="interestRate" type="range" min="0" max="50" step="0.1" class="w-full h-2 bg-muted rounded-lg appearance-none cursor-pointer slider" />
                                        <p class="text-xs text-muted-foreground mt-2">
                                            Range: 0% - 50%
                                        </p>
                                    </div>
                                </div>

                                <!-- Right Column: EMI Display -->
                                <div class="space-y-6">
                                    <!-- Chart -->
                                    <div class="relative">
                                        <div class="w-full max-w-xs mx-auto" style="height: 200px;">
                                            <canvas ref="chartCanvas"></canvas>
                                        </div>
                                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-card-foreground">
                                                    {{ formatCurrency(emiCalculation.totalPayment) }}
                                                </p>
                                                <p class="text-xs text-muted-foreground">Total Repayment</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- EMI Amount -->
                                    <div class="text-center p-6 bg-primary/5 border border-primary/20 rounded-xl">
                                        <p class="text-sm text-muted-foreground mb-2">Your EMI will be</p>
                                        <p class="text-4xl font-bold text-primary mb-1">
                                            {{ formatCurrency(emiCalculation.emi) }}
                                        </p>
                                        <p class="text-sm text-muted-foreground">/month</p>
                                    </div>

                                    <!-- Breakdown -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="text-center p-4 bg-card border border-border rounded-xl">
                                            <div class="flex items-center justify-center gap-2 mb-2">
                                                <span class="w-3 h-3 rounded-full bg-primary"></span>
                                                <p class="text-xs text-muted-foreground">Principal</p>
                                            </div>
                                            <p class="text-lg font-bold text-card-foreground">
                                                {{ formatCurrency(emiCalculation.principal) }}
                                            </p>
                                        </div>

                                        <div class="text-center p-4 bg-card border border-border rounded-xl">
                                            <div class="flex items-center justify-center gap-2 mb-2">
                                                <span class="w-3 h-3 rounded-full bg-primary/30"></span>
                                                <p class="text-xs text-muted-foreground">Interest</p>
                                            </div>
                                            <p class="text-lg font-bold text-card-foreground">
                                                {{ formatCurrency(emiCalculation.totalInterest) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Loan Information -->
                            <div class="border-t border-border pt-6 mb-6">
                                <h3 class="text-lg font-bold text-card-foreground mb-2">Loan Information</h3>
                                <p class="text-sm text-muted-foreground mb-6">
                                    Loan approval is subject to credit assessment and verification. We reserve the right to approve or decline your application.
                                </p>

                                <div class="space-y-4">
                                    <!-- Loan Title -->
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-card-foreground mb-2">
                                            Loan Title
                                        </label>

                                        <input id="title" v-model="form.title" @focus="clearError('title')" type="text" class="w-full px-4 py-2.5 input-crypto border border-border rounded-lg text-sm" placeholder="e.g., Business Expansion Loan" />
                                        <InputError :message="form.errors.title" />
                                    </div>

                                    <!-- Loan Reason and Collateral -->
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="loan_reason" class="block text-sm font-medium text-card-foreground mb-2">
                                                Loan Reason
                                            </label>
                                            <textarea id="loan_reason" v-model="form.loan_reason" @focus="clearError('loan_reason')" rows="4" class="w-full px-4 py-2.5 input-crypto border border-border rounded-lg text-sm resize-none" placeholder="Describe why you need this loan..."></textarea>
                                            <InputError :message="form.errors.loan_reason" />
                                        </div>

                                        <div>
                                            <label for="loan_collateral" class="block text-sm font-medium text-card-foreground mb-2">
                                                Collateral Information
                                            </label>
                                            <textarea id="loan_collateral" v-model="form.loan_collateral" @focus="clearError('loan_collateral')" rows="4" class="w-full px-4 py-2.5 input-crypto border border-border rounded-lg text-sm resize-none" placeholder="Describe collateral assets..."></textarea>
                                            <InputError :message="form.errors.loan_collateral" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirmation -->
                            <div class="flex items-start gap-3 p-4 bg-muted/30 border border-border rounded-xl mb-6">
                                <input id="confirmCheck" v-model="form.confirmed" type="checkbox" class="mt-1 w-4 h-4 rounded border-border text-primary focus:ring-primary cursor-pointer" />
                                <label for="confirmCheck" class="text-sm text-card-foreground cursor-pointer">
                                    I confirm that all provided information is accurate and I understand that loan approval is subject to verification.
                                </label>
                            </div>

                            <!-- Disclaimer -->
                            <div class="flex items-start gap-3 p-4 bg-warning/5 border border-warning/20 rounded-xl mb-6">
                                <AlertCircle class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" />
                                <p class="text-sm text-muted-foreground">
                                    <strong class="text-card-foreground">Important:</strong> You cannot modify loan details after submission. Please review all information carefully before proceeding.
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button type="button" @click="handleClose" :disabled="form.processing" class="flex-1 px-6 py-3 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-xl font-semibold transition-colors disabled:opacity-50 cursor-pointer">
                                    Cancel
                                </button>

                                <ActionButton :processing="form.processing" class="flex-1">
                                    Submit Loan Request
                                </ActionButton>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
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
