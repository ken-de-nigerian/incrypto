<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Loader2Icon, AlertCircleIcon } from 'lucide-vue-next';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import TradingChart from '@/components/TradingChart.vue';

    interface Props {
        isOpen: boolean;
        type: 'Buy' | 'Sell' | null;
        pair: string;
        price: string;
        change: string;
        high: string;
        low: string;
        volume: string;
        availableMargin: number;
    }

    interface Emits {
        (e: 'close'): void;
        (e: 'execute', data: TradeData): void;
    }

    interface TradeData {
        pair: string;
        type: 'Buy' | 'Sell';
        amount: number;
        leverage: number;
        stopLoss: number;
        takeProfit: number;
    }

    const props = defineProps<Props>();
    const emit = defineEmits<Emits>();

    const tradeFormData = ref({
        amount: 0,
        leverage: 50,
        stopLoss: 0,
        takeProfit: 0
    });

    const tradeError = ref('');
    const isExecutingTrade = ref(false);

    const positionSize = computed(() => {
        return (tradeFormData.value.amount * tradeFormData.value.leverage).toFixed(2);
    });

    const isFormValid = computed(() => {
        return tradeFormData.value.amount > 0 && tradeFormData.value.amount <= props.availableMargin;
    });

    const validateTrade = () => {
        tradeError.value = '';

        if (!tradeFormData.value.amount) {
            tradeError.value = 'Please enter a valid amount';
            return false;
        }

        if (tradeFormData.value.amount > props.availableMargin) {
            tradeError.value = 'Insufficient margin available';
            return false;
        }

        if (tradeFormData.value.stopLoss < 0) {
            tradeError.value = 'Stop loss price cannot be negative';
            return false;
        }

        if (tradeFormData.value.takeProfit < 0) {
            tradeError.value = 'Take profit price cannot be negative';
            return false;
        }

        return true;
    };

    const executeTrade = async () => {
        if (!validateTrade() || !props.type) return;

        isExecutingTrade.value = true;

        try {
            await new Promise(resolve => setTimeout(resolve, 1500));

            const tradeData: TradeData = {
                pair: props.pair,
                type: props.type,
                amount: tradeFormData.value.amount,
                leverage: tradeFormData.value.leverage,
                stopLoss: tradeFormData.value.stopLoss,
                takeProfit: tradeFormData.value.takeProfit
            };

            emit('execute', tradeData);
            handleClose();
        } catch (error) {
            tradeError.value = error;
        } finally {
            isExecutingTrade.value = false;
        }
    };

    const handleClose = () => {
        tradeFormData.value = {
            amount: 0,
            leverage: 50,
            stopLoss: 0,
            takeProfit: 0
        };
        tradeError.value = '';
        emit('close');
    };

    const setMaxAmount = () => {
        tradeFormData.value.amount = props.availableMargin;
    };
</script>

<template>
    <QuickActionModal
        :is-open="isOpen"
        :title="`${type === 'Buy' ? 'Buy' : 'Sell'} ${pair}`"
        :subtitle="`Open a ${type?.toLowerCase()} position`"
        @close="handleClose">

        <div class="space-y-4">
            <!-- Error Alert -->
            <div v-if="tradeError" class="p-4 rounded-xl flex items-start gap-3 bg-destructive/10 border border-destructive/20">
                <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5 text-destructive" />
                <p class="text-sm font-semibold text-destructive">{{ tradeError }}</p>
            </div>

            <!-- Pair Info -->
            <div class="bg-muted/30 p-4 rounded-lg space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Current Price</span>
                    <span class="text-lg font-bold text-primary">{{ price }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div>
                        <p class="text-muted-foreground mb-1">24h Change</p>
                        <p :class="[
                            'font-semibold',
                            parseFloat(change) >= 0 ? 'text-emerald-400' : 'text-rose-400'
                        ]">
                            {{ parseFloat(change) >= 0 ? '+' : '' }}{{ change }}%
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground mb-1">High</p>
                        <p class="font-semibold text-card-foreground">{{ high }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground mb-1">Low</p>
                        <p class="font-semibold text-card-foreground">{{ low }}</p>
                    </div>
                </div>
                <div class="pt-2 border-t border-border">
                    <p class="text-xs text-muted-foreground">Volume: {{ volume }}</p>
                </div>
            </div>

            <!-- Trading Chart -->
            <TradingChart
                :pair="pair"
                :price="price"
                :change="change"
                :low="low"
                :high="high"
                :volume="volume"
            />

            <!-- Trade Amount -->
            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-card-foreground">Amount (USD)</h4>
                <input
                    v-model.number="tradeFormData.amount"
                    type="number"
                    step="0.01"
                    min="0"
                    :max="availableMargin"
                    placeholder="Enter amount"
                    class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm input-crypto"
                />
                <div class="flex justify-between items-center text-xs text-muted-foreground bg-muted/50 p-2 rounded-lg">
                    <span>Available Margin: ${{ availableMargin.toFixed(2) }}</span>
                    <button
                        @click="setMaxAmount"
                        class="text-primary font-medium hover:underline cursor-pointer transition">
                        Use Max
                    </button>
                </div>
            </div>

            <!-- Leverage Slider -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-card-foreground">Leverage</h4>
                    <span class="text-sm font-bold text-primary">1:{{ tradeFormData.leverage }}</span>
                </div>
                <input
                    v-model.number="tradeFormData.leverage"
                    type="range"
                    min="1"
                    max="500"
                    class="w-full cursor-pointer"
                />
                <div class="text-xs text-muted-foreground">Position Size: {{ positionSize }} USD</div>
            </div>

            <!-- Stop Loss -->
            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-card-foreground">Stop Loss (Price)</h4>
                <input
                    v-model.number="tradeFormData.stopLoss"
                    type="number"
                    step="0.0001"
                    min="0"
                    placeholder="Optional"
                    class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm input-crypto"
                />
            </div>

            <!-- Take Profit -->
            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-card-foreground">Take Profit (Price)</h4>
                <input
                    v-model.number="tradeFormData.takeProfit"
                    type="number"
                    step="0.0001"
                    min="0"
                    placeholder="Optional"
                    class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm input-crypto"
                />
            </div>

            <!-- Execute Button -->
            <button
                :disabled="isExecutingTrade || !isFormValid"
                @click="executeTrade"
                :class="[
                    'w-full py-3 font-bold rounded-lg transition-opacity text-sm flex items-center justify-center gap-2 cursor-pointer margin-bottom',
                    isExecutingTrade || !isFormValid
                        ? 'bg-muted text-muted-foreground cursor-not-allowed'
                        : type === 'Buy'
                        ? 'bg-emerald-500 hover:bg-emerald-600 text-white'
                        : 'bg-rose-500 hover:bg-rose-600 text-white'
                ]">
                <Loader2Icon v-if="isExecutingTrade" class="w-4 h-4 animate-spin" />
                <span>{{ isExecutingTrade ? 'Executing...' : `Execute ${type} Trade` }}</span>
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

    input[type="range"] {
        accent-color: hsl(var(--primary));
    }

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
