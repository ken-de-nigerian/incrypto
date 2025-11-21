<script setup lang="ts">
    import { computed, watch, ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import {
        X,
        TrendingUp,
        AlertTriangle as AlertTriangleIcon,
        DollarSign as DollarSignIcon,
        Users as UsersIcon,
        BarChart3,
        Activity
    } from 'lucide-vue-next';

    interface User {
        id: number;
        first_name: string;
        last_name: string;
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
        user: User | null;
    }

    const props = defineProps<{
        isOpen: boolean;
        masterTrader: MasterTrader | null;
        currentBalance: number;
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
    }>();

    const activeTab = ref<'details' | 'terms' | 'form'>('details');
    const agreeToTerms = ref(false);
    const isSubmitting = ref(false);
    const errors = ref({
        terms: '',
        balance: '',
        general: ''
    });

    const copyAmount = computed(() => {
        return props.masterTrader?.commission_rate ? parseFloat(props.masterTrader.commission_rate as string) : 0;
    });

    const getTraderName = computed(() => {
        if (!props.masterTrader?.user) return 'Unknown Trader';
        return `${props.masterTrader.user.first_name} ${props.masterTrader.user.last_name}`;
    });

    const getTraderInitials = computed(() => {
        if (!props.masterTrader?.user) return '';
        const first = props.masterTrader.user.first_name?.charAt(0) || '';
        const last = props.masterTrader.user.last_name?.charAt(0) || '';
        return `${first}${last}`.toUpperCase();
    });

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

    const handleClose = () => {
        emit('close');
        // Small timeout to reset state after animation
        setTimeout(() => {
            activeTab.value = 'details';
            agreeToTerms.value = false;
            isSubmitting.value = false;
            errors.value = { terms: '', balance: '', general: '' };
        }, 300);
    };

    const submitCopyTrade = async () => {
        errors.value = { terms: '', balance: '', general: '' };

        if (!props.masterTrader) return;

        if (!agreeToTerms.value) {
            errors.value.terms = 'You must agree to the terms and conditions to continue';
            return;
        }

        if (copyAmount.value <= 0) {
            errors.value.general = 'Invalid copy trade amount';
            return;
        }

        if (copyAmount.value > props.currentBalance) {
            errors.value.balance = `Insufficient balance. You need $${copyAmount.value.toFixed(2)} but only have $${props.currentBalance.toFixed(2)}`;
            return;
        }

        isSubmitting.value = true;

        router.post(route('user.trade.copy.start', props.masterTrader.id), {
            amount: copyAmount.value
        }, {
            preserveScroll: true,
            onSuccess: () => {
                handleClose();
            },
            onError: (formErrors) => {
                if (formErrors.amount) {
                    errors.value.general = formErrors.amount;
                } else if (formErrors.message) {
                    errors.value.general = formErrors.message;
                } else {
                    errors.value.general = 'Failed to start copy trading. Please try again.';
                }
            },
            onFinish: () => {
                isSubmitting.value = false;
            }
        });
    };

    watch(() => props.isOpen, (newValue) => {
        if (newValue) {
            activeTab.value = 'details';
            agreeToTerms.value = false;
            errors.value = { terms: '', balance: '', general: '' };
        }
    });

    watch(() => agreeToTerms.value, (newValue) => {
        if (newValue && errors.value.terms) {
            errors.value.terms = '';
        }
    });

    // Disable body scroll when modal is open
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
                @click.self="handleClose"
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
                        v-if="isOpen && masterTrader"
                        class="bg-card w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] sm:max-w-4xl flex flex-col rounded-none sm:rounded-2xl overflow-hidden border-border sm:border relative"
                    >
                        <div class="px-4 sm:px-6 py-4 border-b border-border bg-muted/30 shrink-0">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3 sm:gap-4 overflow-hidden">
                                    <div class="w-10 h-10 sm:w-16 sm:h-16 rounded-full bg-primary/20 flex-shrink-0 flex items-center justify-center text-base sm:text-xl font-bold text-primary">
                                        {{ getTraderInitials }}
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-lg sm:text-2xl font-bold text-card-foreground truncate">{{ getTraderName }}</h2>
                                        <span :class="['inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full border mt-1 whitespace-nowrap', getExpertiseColor(masterTrader.expertise)]">
                                            {{ masterTrader.expertise }}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    @click="handleClose"
                                    class="p-2 -mr-2 hover:bg-muted rounded-lg cursor-pointer transition-colors"
                                >
                                    <X class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>

                            <div class="flex gap-2 border-b border-border/50 -mb-4 pb-0 overflow-x-auto no-scrollbar">
                                <button
                                    @click="activeTab = 'details'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'details'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Details
                                </button>
                                <button
                                    @click="activeTab = 'terms'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'terms'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Terms & Conditions
                                </button>
                                <button
                                    @click="activeTab = 'form'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'form'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Start Copying
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto overscroll-contain px-4 sm:px-6 py-4 bg-background">
                            <div v-if="activeTab === 'details'" class="space-y-6 pb-6">
                                <div v-if="masterTrader.bio" class="bg-muted/50 rounded-lg p-4">
                                    <h3 class="text-sm font-semibold text-card-foreground mb-2">About</h3>
                                    <p class="text-sm text-muted-foreground">{{ masterTrader.bio }}</p>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                                    <div class="bg-background border border-border rounded-lg p-3 sm:p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <TrendingUp class="w-4 h-4 text-green-600" />
                                            <p class="text-xs text-muted-foreground">Gain %</p>
                                        </div>
                                        <p class="text-lg sm:text-2xl font-bold text-green-600">+{{ parseFloat(masterTrader.gain_percentage as string).toFixed(2) }}%</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-3 sm:p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <AlertTriangleIcon class="w-4 h-4 text-orange-600" />
                                            <p class="text-xs text-muted-foreground">Risk Score</p>
                                        </div>
                                        <p class="text-lg sm:text-2xl font-bold text-card-foreground">{{ masterTrader.risk_score }}/10</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-3 sm:p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <UsersIcon class="w-4 h-4 text-primary" />
                                            <p class="text-xs text-muted-foreground">Copiers</p>
                                        </div>
                                        <p class="text-lg sm:text-2xl font-bold text-card-foreground">{{ masterTrader.copiers_count }}</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-3 sm:p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <DollarSignIcon class="w-4 h-4 text-green-600" />
                                            <p class="text-xs text-muted-foreground">Total Profit</p>
                                        </div>
                                        <p class="text-base sm:text-lg font-bold text-green-600 truncate">${{ parseFloat(masterTrader.total_profit as string).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-3 sm:p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <DollarSignIcon class="w-4 h-4 text-red-600" />
                                            <p class="text-xs text-muted-foreground">Total Loss</p>
                                        </div>
                                        <p class="text-base sm:text-lg font-bold text-red-600 truncate">${{ parseFloat(masterTrader.total_loss as string).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-3 sm:p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <BarChart3 class="w-4 h-4 text-primary" />
                                            <p class="text-xs text-muted-foreground">Win Rate</p>
                                        </div>
                                        <p class="text-lg sm:text-2xl font-bold text-card-foreground">{{ parseFloat(masterTrader.win_rate as string).toFixed(1) }}%</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-3 sm:p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Activity class="w-4 h-4 text-muted-foreground" />
                                            <p class="text-xs text-muted-foreground">Total Trades</p>
                                        </div>
                                        <p class="text-lg sm:text-2xl font-bold text-card-foreground">{{ masterTrader.total_trades }}</p>
                                    </div>

                                    <div class="bg-background border border-border rounded-lg p-3 sm:p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <DollarSignIcon class="w-4 h-4 text-primary" />
                                            <p class="text-xs text-muted-foreground">Copy Amount</p>
                                        </div>
                                        <p class="text-lg sm:text-2xl font-bold text-primary">${{ masterTrader.commission_rate ? parseFloat(masterTrader.commission_rate as string).toFixed(2) : '0.00' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'terms'" class="space-y-4 pb-6">
                                <div class="bg-muted/50 rounded-lg p-4">
                                    <h3 class="text-lg font-bold text-card-foreground mb-4">Copy Trading Terms & Conditions</h3>

                                    <div class="space-y-4 text-sm text-muted-foreground">
                                        <div>
                                            <h4 class="font-semibold text-card-foreground mb-2">1. Service Overview</h4>
                                            <p>By participating in copy trading, you agree to automatically replicate the trades of the selected Master Trader. Your account will execute trades proportionally based on your chosen multiplier.</p>
                                        </div>

                                        <div>
                                            <h4 class="font-semibold text-card-foreground mb-2">2. Fixed Copy Amount</h4>
                                            <p>The copy amount of ${{ masterTrader.commission_rate ? parseFloat(masterTrader.commission_rate as string).toFixed(2) : '0.00' }} is fixed and will be used for each trade the Master Trader executes. You cannot modify this amount as it is set by the Master Trader.</p>
                                        </div>

                                        <div>
                                            <h4 class="font-semibold text-card-foreground mb-2">3. How It Works</h4>
                                            <p>When the Master Trader opens a position, your account will automatically copy that trade using the fixed amount of ${{ masterTrader.commission_rate ? parseFloat(masterTrader.commission_rate as string).toFixed(2) : '0.00' }}. Ensure you maintain sufficient balance to cover multiple trades.</p>
                                        </div>

                                        <div>
                                            <h4 class="font-semibold text-card-foreground mb-2">4. Risk Disclosure</h4>
                                            <p class="text-red-600 font-semibold">Trading involves substantial risk of loss. Past performance does not guarantee future results. You may lose some or all of your invested capital. The risk score of {{ masterTrader.risk_score }}/10 indicates the volatility level of this trader's strategy.</p>
                                        </div>

                                        <div>
                                            <h4 class="font-semibold text-card-foreground mb-2">5. Control & Termination</h4>
                                            <p>You can pause, resume, or stop copy trading at any time. Pausing will halt new trade replication but maintain existing positions. Stopping will close all positions and end the copy trading relationship.</p>
                                        </div>

                                        <div>
                                            <h4 class="font-semibold text-card-foreground mb-2">6. No Guarantee</h4>
                                            <p>Neither the platform nor the Master Trader guarantees profits. All trading decisions remain your responsibility. Ensure you understand the risks before proceeding.</p>
                                        </div>

                                        <div>
                                            <h4 class="font-semibold text-card-foreground mb-2">7. Monitoring & Responsibility</h4>
                                            <p>You are responsible for monitoring your copy trading positions and managing your account balance. Ensure sufficient funds are available to support copied trades.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'form'" class="space-y-6 pb-6">
                                <div :class="[
                                    'rounded-lg p-4 border',
                                    parseInt(masterTrader.risk_score as string) <= 3
                                        ? 'bg-green-50 border-green-200'
                                        : parseInt(masterTrader.risk_score as string) <= 6
                                        ? 'bg-yellow-50 border-yellow-200'
                                        : 'bg-red-50 border-red-200'
                                ]">
                                    <div class="flex gap-3">
                                        <AlertTriangleIcon :class="[
                                            'w-5 h-5 flex-shrink-0 mt-0.5',
                                            parseInt(masterTrader.risk_score as string) <= 3
                                                ? 'text-green-600'
                                                : parseInt(masterTrader.risk_score as string) <= 6
                                                ? 'text-yellow-600'
                                                : 'text-red-600'
                                        ]" />
                                        <div>
                                            <h4 :class="[
                                                'font-semibold mb-1',
                                                parseInt(masterTrader.risk_score as string) <= 3
                                                    ? 'text-green-900'
                                                    : parseInt(masterTrader.risk_score as string) <= 6
                                                    ? 'text-yellow-900'
                                                    : 'text-red-900'
                                            ]">
                                                {{ parseInt(masterTrader.risk_score as string) <= 3
                                                ? 'Low Risk'
                                                : parseInt(masterTrader.risk_score as string) <= 6
                                                    ? 'Moderate Risk'
                                                    : 'High Risk' }}
                                            </h4>
                                            <p :class="[
                                                'text-sm',
                                                parseInt(masterTrader.risk_score as string) <= 3
                                                    ? 'text-green-800'
                                                    : parseInt(masterTrader.risk_score as string) <= 6
                                                    ? 'text-yellow-800'
                                                    : 'text-red-800'
                                            ]">
                                                {{ parseInt(masterTrader.risk_score as string) <= 3
                                                ? `This trader has a low risk score of ${masterTrader.risk_score}/10, indicating a conservative trading strategy. Review the terms before proceeding.`
                                                : parseInt(masterTrader.risk_score as string) <= 6
                                                    ? `This trader has a moderate risk score of ${masterTrader.risk_score}/10. Please review all terms and understand the risks involved.`
                                                    : `This trader has a high risk score of ${masterTrader.risk_score}/10. Please review all terms carefully and invest responsibly.` }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-card-foreground mb-2">
                                        Copy Trade Amount (Fixed)
                                    </label>
                                    <div class="relative">
                                        <DollarSignIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                                        <input
                                            :value="copyAmount.toFixed(2)"
                                            type="text"
                                            disabled
                                            class="w-full pl-10 pr-4 py-3 bg-muted/50 border border-border rounded-lg text-card-foreground font-semibold cursor-not-allowed"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-1">
                                        This amount will be used for each trade the Master Trader executes
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Available balance: ${{ currentBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </p>
                                    <p v-if="errors.balance" class="text-xs text-red-600 font-semibold mt-1 flex items-center gap-1">
                                        <AlertTriangleIcon class="w-3 h-3" />
                                        {{ errors.balance }}
                                    </p>
                                </div>

                                <div v-if="errors.general" class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex gap-3">
                                        <AlertTriangleIcon class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <h4 class="font-semibold text-red-900 mb-1">Error</h4>
                                            <p class="text-sm text-red-800">{{ errors.general }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-muted/50 rounded-lg p-4">
                                    <h4 class="font-semibold text-card-foreground mb-3">Summary</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">Amount Per Trade:</span>
                                            <span class="font-semibold text-card-foreground">${{ copyAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                                        </div>
                                        <div class="flex justify-between pt-2 border-t border-border">
                                            <span class="font-semibold text-card-foreground">Required Balance:</span>
                                            <span class="font-bold text-primary">${{ copyAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">Risk Level:</span>
                                            <span class="font-semibold" :class="parseInt(masterTrader.risk_score as string) > 7 ? 'text-red-600' : parseInt(masterTrader.risk_score as string) > 4 ? 'text-yellow-600' : 'text-green-600'">
                                                {{ masterTrader.risk_score }}/10
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div :class="[
                                        'flex items-start gap-3 bg-background rounded-lg p-4 border-2',
                                        errors.terms ? 'border-red-300' : 'border-border'
                                    ]">
                                        <input
                                            v-model="agreeToTerms"
                                            type="checkbox"
                                            id="agreeTerms"
                                            :class="[
                                                'mt-1 w-5 h-5 sm:w-4 sm:h-4 rounded cursor-pointer flex-shrink-0',
                                                errors.terms ? 'border-red-300 text-red-600' : 'border-border text-primary'
                                            ]"
                                        />
                                        <label for="agreeTerms" class="text-sm text-muted-foreground cursor-pointer select-none">
                                            I have read and agree to the <button @click.prevent="activeTab = 'terms'" class="text-primary hover:underline font-semibold">Terms & Conditions</button>. I understand the risks involved in copy trading and accept full responsibility for my investment decisions.
                                        </label>
                                    </div>
                                    <p v-if="errors.terms" class="text-xs text-red-600 font-semibold mt-2 flex items-center gap-1">
                                        <AlertTriangleIcon class="w-3 h-3" />
                                        {{ errors.terms }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="px-4 sm:px-6 py-4 border-t border-border bg-muted/10 shrink-0 safe-area-pb">
                            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                                <button @click="handleClose"
                                        class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-background border border-border text-card-foreground rounded-lg font-semibold hover:bg-muted transition-colors cursor-pointer">
                                    Cancel
                                </button>

                                <button v-if="activeTab === 'details'"
                                        @click="activeTab = 'form'"
                                        class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-primary text-primary-foreground rounded-lg font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
                                    Continue
                                </button>

                                <button v-if="activeTab === 'terms'"
                                        @click="activeTab = 'form'"
                                        class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-primary text-primary-foreground rounded-lg font-semibold hover:bg-primary/90 transition-colors cursor-pointer">
                                    Proceed to Form
                                </button>

                                <button v-if="activeTab === 'form'"
                                        @click="submitCopyTrade"
                                        :disabled="!agreeToTerms || copyAmount <= 0 || copyAmount > currentBalance || isSubmitting"
                                        :class="[
                                        'w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 rounded-lg font-semibold transition-colors inline-flex justify-center items-center gap-2',
                                        !agreeToTerms || copyAmount <= 0 || copyAmount > currentBalance || isSubmitting
                                            ? 'bg-muted text-muted-foreground cursor-not-allowed'
                                            : 'bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer'
                                    ]">
                                    <svg v-if="isSubmitting" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isSubmitting ? 'Copying...' : 'Copy Strategy' }}
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
    /* Ensures the modal doesn't overlap with iPhone home bar */
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
