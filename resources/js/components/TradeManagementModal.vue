<script setup lang="ts">
    import { ref, computed, watch } from 'vue';
    import { router } from '@inertiajs/vue3';
    import {
        X,
        Loader2 as Loader2Icon,
        XCircle as XCircleIcon,
        TrendingUp as TrendingUpIcon,
        TrendingDown as TrendingDownIcon,
        AlertTriangle as AlertTriangleIcon,
        BarChart3
    } from 'lucide-vue-next';

    interface Trade {
        id: number;
        user_name?: string;
        pair?: string;
        pair_name?: string;
        trade_direction?: string;
        amount?: number;
        leverage?: number;
        entry_price?: number;
        trading_mode?: string;
        status: string;
    }

    const props = defineProps<{
        isOpen: boolean;
        trade: Trade | null;
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
    }>();

    const activeTab = ref<'details' | 'close'>('details');
    const pnl = ref('');
    const exitPriceAdjustment = ref('0');
    const isProcessing = ref(false);
    const validationErrors = ref<Record<string, string>>({});

    const calculatedExitPrice = computed(() => {
        if (!props.trade || !pnl.value) return null;

        const pnlValue = parseFloat(pnl.value);
        const entryPrice = parseFloat(props.trade.entry_price) || 0;
        const amount = parseFloat(props.trade.amount) || 0;
        const leverage = parseFloat(props.trade.leverage) || 1;
        const adjustment = parseFloat(exitPriceAdjustment.value) || 0;

        if (isNaN(pnlValue) || entryPrice === 0 || amount === 0) return null;

        const positionSize = amount * leverage;
        const priceChange = pnlValue / positionSize;

        let exitPrice;
        if (props.trade.trade_direction === 'Up') {
            exitPrice = entryPrice + priceChange + adjustment;
        } else {
            exitPrice = entryPrice - priceChange + adjustment;
        }

        return exitPrice > 0 ? exitPrice : null;
    });

    const formatAmount = (amount: number | undefined) => {
        if (!amount) return '0.00';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 8
        }).format(amount);
    };

    const validateForm = (): boolean => {
        validationErrors.value = {};

        if (!pnl.value || pnl.value.trim() === '') {
            validationErrors.value.pnl = 'P&L is required';
            return false;
        }

        const pnlNum = parseFloat(pnl.value);
        if (isNaN(pnlNum)) {
            validationErrors.value.pnl = 'P&L must be a valid number';
            return false;
        }

        if (!calculatedExitPrice.value || calculatedExitPrice.value <= 0) {
            validationErrors.value.general = 'Unable to calculate valid exit price. Please check P&L value.';
            return false;
        }

        return true;
    };

    const confirmClose = () => {
        if (!props.trade) return;

        if (!validateForm()) {
            return;
        }

        isProcessing.value = true;

        router.patch(route('admin.transaction.trade.close', props.trade.id), {
            exit_price: calculatedExitPrice.value,
            pnl: parseFloat(pnl.value),
            closed_at: new Date().toISOString(),
            is_auto_close: false
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
        pnl.value = '';
        exitPriceAdjustment.value = '0';
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
                        v-if="isOpen && trade"
                        class="bg-card w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] sm:max-w-3xl flex flex-col rounded-none sm:rounded-2xl overflow-hidden border-border sm:border relative"
                    >
                        <!-- Header -->
                        <div class="px-4 sm:px-6 py-4 border-b border-border bg-muted/30 shrink-0">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3 sm:gap-4 overflow-hidden">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-warning/20 flex-shrink-0 flex items-center justify-center">
                                        <XCircleIcon class="w-5 h-5 sm:w-6 sm:h-6 text-warning" />
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-lg sm:text-xl font-bold text-card-foreground">Close Trade</h2>
                                        <p class="text-sm text-muted-foreground truncate">{{ trade.pair_name || trade.pair }}</p>
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
                                    Trade Details
                                </button>
                                <button
                                    @click="activeTab = 'close'"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold transition-all whitespace-nowrap cursor-pointer border-b-2',
                                        activeTab === 'close'
                                            ? 'text-primary border-primary'
                                            : 'text-muted-foreground border-transparent hover:text-card-foreground'
                                    ]"
                                >
                                    Close Position
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto no-scrollbar overscroll-contain px-4 sm:px-6 py-4 bg-background">
                            <!-- Details Tab -->
                            <div v-if="activeTab === 'details'" class="space-y-6 pb-6">
                                <div class="bg-muted/50 rounded-lg">
                                    <h3 class="text-sm font-semibold text-card-foreground mb-3 uppercase tracking-wide">Trade Summary</h3>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">User</p>
                                            <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">{{ trade.user_name }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Trading Pair</p>
                                            <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">{{ trade.pair_name || trade.pair }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Position Type</p>
                                            <div class="flex items-center gap-1">
                                                <component
                                                    :is="trade.trade_direction === 'Up' ? TrendingUpIcon : TrendingDownIcon"
                                                    class="w-3.5 h-3.5"
                                                    :class="trade.trade_direction === 'Up' ? 'text-success' : 'text-destructive'"
                                                />
                                                <p class="text-sm font-semibold" :class="trade.trade_direction === 'Up' ? 'text-success' : 'text-destructive'">
                                                    {{ trade.trade_direction }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Entry Price</p>
                                            <p class="text-sm font-semibold text-primary">${{ formatAmount(trade.entry_price) }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Amount</p>
                                            <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">${{ formatAmount(trade.amount) }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Leverage</p>
                                            <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">{{ trade.leverage }}x</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Trading Mode</p>
                                            <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider capitalize">{{ trade.trading_mode }}</p>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Status</p>
                                            <span class="px-2 py-0.5 text-xs rounded-full border capitalize bg-warning/10 text-warning border-warning/30 inline-block">
                                                {{ trade.status }}
                                            </span>
                                        </div>

                                        <div class="bg-background border border-border rounded-lg p-3">
                                            <p class="text-xs text-muted-foreground mb-1">Position Size</p>
                                            <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">${{ formatAmount((trade.amount || 0) * (trade.leverage || 1)) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Close Tab -->
                            <div v-if="activeTab === 'close'" class="space-y-6 pb-6">
                                <!-- General Error -->
                                <div v-if="validationErrors.general" class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex gap-3">
                                        <AlertTriangleIcon class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <h4 class="font-semibold text-red-900 mb-1">Error</h4>
                                            <p class="text-sm text-red-800">{{ validationErrors.general }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- P&L Input -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider flex items-center gap-2">
                                        <TrendingUpIcon class="w-4 h-4" />
                                        Profit/Loss (P&L) *
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">$</span>
                                        <input
                                            v-model="pnl"
                                            type="text"
                                            placeholder="0.00 (can be negative)"
                                            class="w-full pl-8 pr-4 py-3 input-crypto border border-border rounded-lg text-sm transition-all"
                                            :class="{ 'border-destructive/50': validationErrors.pnl }"
                                        />
                                    </div>
                                    <p v-if="validationErrors.pnl" class="text-xs text-red-600 font-semibold flex items-center gap-1">
                                        <AlertTriangleIcon class="w-3 h-3" />
                                        {{ validationErrors.pnl }}
                                    </p>
                                    <p v-else class="text-xs text-muted-foreground">
                                        Enter the profit or loss amount. Use negative values for losses (e.g., -50.00)
                                    </p>
                                </div>

                                <!-- P&L Preview -->
                                <div v-if="pnl && !isNaN(parseFloat(pnl))" class="p-4 rounded-lg border"
                                     :class="parseFloat(pnl) >= 0 ? 'bg-success/10 border-success/30' : 'bg-destructive/10 border-destructive/30'">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-muted-foreground">P&L Result:</span>
                                        <span class="text-base font-bold" :class="parseFloat(pnl) >= 0 ? 'text-success' : 'text-destructive'">
                                            {{ parseFloat(pnl) >= 0 ? '+' : '' }}${{ formatAmount(Math.abs(parseFloat(pnl))) }}
                                        </span>
                                    </div>
                                    <div v-if="calculatedExitPrice" class="flex items-center justify-between pt-2 border-t border-border/30">
                                        <span class="text-xs font-medium text-muted-foreground">Calculated Exit Price:</span>
                                        <span class="text-sm font-bold text-primary">
                                            ${{ formatAmount(calculatedExitPrice) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Exit Price Adjustment (Optional) -->
                                <div v-if="calculatedExitPrice" class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider flex items-center gap-2">
                                        <BarChart3 class="w-4 h-4" />
                                        Exit Price Adjustment (Optional)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">$</span>
                                        <input
                                            v-model="exitPriceAdjustment"
                                            type="text"
                                            placeholder="0.00"
                                            class="w-full pl-8 pr-4 py-3 input-crypto border border-border rounded-lg text-sm transition-all"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Fine-tune the exit price if needed (can be positive or negative)
                                    </p>
                                </div>

                                <!-- Warning -->
                                <div class="p-4 bg-warning/10 border border-warning/30 rounded-lg flex gap-3">
                                    <AlertTriangleIcon class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-1">Confirm Trade Closure</p>
                                        <p class="text-sm text-muted-foreground">
                                            This action will close the trade and update the user's balance.
                                            <strong>This cannot be undone.</strong>
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
                                    @click="activeTab = 'close'"
                                    class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-primary text-primary-foreground rounded-lg font-semibold hover:bg-primary/90 transition-colors cursor-pointer"
                                >
                                    Continue
                                </button>

                                <button
                                    v-if="activeTab === 'close'"
                                    @click="confirmClose"
                                    :disabled="isProcessing || !pnl"
                                    :class="[
                                        'w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 rounded-lg font-semibold transition-colors inline-flex justify-center items-center gap-2',
                                        isProcessing || !pnl
                                            ? 'bg-muted text-muted-foreground cursor-not-allowed'
                                            : 'bg-warning text-white hover:bg-warning/90 cursor-pointer'
                                    ]"
                                >
                                    <Loader2Icon v-if="isProcessing" class="w-4 h-4 animate-spin" />
                                    <XCircleIcon v-else class="w-4 h-4" />
                                    {{ isProcessing ? 'Closing...' : 'Close Trade' }}
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
