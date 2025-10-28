<script setup lang="ts">
    import { computed, nextTick, ref, watch } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { RefreshCcwIcon, Loader2Icon, AlertCircleIcon, BriefcaseIcon } from 'lucide-vue-next';
    import QuickActionModal from '@/components/QuickActionModal.vue';

    interface Props {
        isOpen: boolean;
        liveBalance: number;
        cryptoHoldings: Array<any>;
        pricesMap: Record<string, number>;
    }

    interface Emits {
        (e: 'close'): void;
        (e: 'show-alert', message: string, type: 'success' | 'error'): void;
    }

    const props = defineProps<Props>();
    const emit = defineEmits<Emits>();

    const fundingAmount = ref<number | null>(1);
    const fundingSourceToken = ref('BTC');
    const fundingError = ref('');
    const isOrderProcessing = ref(false);
    const isWalletConnected = ref(true);

    const fundingSourceBalance = computed(() => props.cryptoHoldings.find(h => h.symbol === fundingSourceToken.value)?.balance || 0);
    const fundingConversionRate = computed(() => props.pricesMap[fundingSourceToken.value] || 0);
    const estimatedUSDFunds = computed(() => {
        const amount = parseFloat(fundingAmount.value?.toString() || '0') || 0;
        return (amount * fundingConversionRate.value).toFixed(2);
    });

    const validateFunding = () => {
        const sourceAmount = parseFloat(fundingAmount.value?.toString() || '0') || 0;
        let error = '';

        if (sourceAmount <= 0) {
            error = 'Amount must be greater than zero.';
        } else if (sourceAmount > fundingSourceBalance.value) {
            error = `Insufficient ${fundingSourceToken.value} balance`;
        } else if (fundingConversionRate.value <= 0) {
            error = `No market price available for ${fundingSourceToken.value}.`;
        }

        fundingError.value = error;
        return error === '';
    };

    const selectFundingToken = (symbol: string) => {
        fundingSourceToken.value = symbol;
        const maxAmount = fundingSourceBalance.value;
        fundingAmount.value = maxAmount > 0 ? Math.min(1, maxAmount) : null;
        fundingError.value = '';
        nextTick(() => validateFunding());
    };

    const performFunding = async () => {
        if (!validateFunding()) return;

        const amountUSD = parseFloat(estimatedUSDFunds.value);
        const sourceAmount = parseFloat(fundingAmount.value?.toString() || '0') || 0;

        isOrderProcessing.value = true;

        router.post(route('user.trade.fund.account'), {
            source_symbol: fundingSourceToken.value,
            source_amount: sourceAmount,
            estimated_funds: amountUSD,
        }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isOrderProcessing.value = false;
                emit('show-alert', `Successfully funded ${amountUSD} USD to your live trading account.`, 'success');
                handleClose();
            },
            onError: (errors) => {
                fundingError.value = Object.values(errors)[0] || 'An unexpected error occurred.';
                emit('show-alert', fundingError.value, 'error');
                isOrderProcessing.value = false;
            }
        });
    };

    const handleClose = () => {
        fundingAmount.value = 1;
        fundingError.value = '';
        emit('close');
    };

    watch(() => props.isOpen, (newVal) => {
        if (newVal && props.cryptoHoldings.length > 0) {
            fundingSourceToken.value = props.cryptoHoldings[0].symbol;
            nextTick(() => validateFunding());
        }
    });
</script>

<template>
    <QuickActionModal
        :is-open="isOpen"
        title="Fund Live Trading Account"
        subtitle="Convert your crypto holdings into USD to boost your live trading balance"
        @close="handleClose">

        <div class="space-y-4">
            <div v-if="fundingError" class="p-4 rounded-xl flex items-start gap-3 bg-destructive/10 border border-destructive/20">
                <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5 text-destructive" />
                <p class="text-sm font-semibold text-destructive">{{ fundingError }}</p>
            </div>

            <div class="bg-muted/30 p-3 rounded-lg space-y-2">
                <p class="text-sm font-semibold text-card-foreground flex items-center gap-2">
                    <BriefcaseIcon class="w-4 h-4 text-primary" />
                    Current Live Portfolio Value: <span class="text-primary">${{ liveBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-card-foreground">Select Source Crypto:</h4>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <button
                        v-for="holding in cryptoHoldings"
                        :key="holding.symbol"
                        @click="selectFundingToken(holding.symbol)"
                        :class="['flex-shrink-0 px-4 py-2 rounded-lg text-xs border transition-all flex items-center gap-2 cursor-pointer', fundingSourceToken === holding.symbol ? 'bg-primary border-primary text-primary-foreground' : 'bg-background border-border text-muted-foreground hover:bg-muted/50']">
                        <img
                            :src="holding.logo"
                            loading="lazy"
                            :alt="holding.symbol"
                            class="h-6 w-6 object-contain flex-shrink-0"
                            onerror="this.src='https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                        />
                        <div class="text-left">
                            <div class="text-xs font-medium">{{ holding.symbol }}</div>
                            <div class="text-xs opacity-75">{{ holding.balance.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 8 }) }}</div>
                        </div>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-card-foreground">Amount to Convert:</h4>
                <div class="relative">
                    <input
                        v-model.number.lazy="fundingAmount"
                        type="number"
                        step="any"
                        min="0.00000001"
                        :max="fundingSourceBalance"
                        placeholder="Amount"
                        class="input-crypto w-full"
                        :class="{ 'border-destructive ring-destructive': fundingError }"
                        @change="validateFunding" />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">{{ fundingSourceToken }}</span>
                </div>
                <div class="flex justify-between items-center text-xs text-muted-foreground bg-muted/50 p-2 rounded-lg">
                    <span>Balance: {{ fundingSourceBalance.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 8 }) }} {{ fundingSourceToken }}</span>
                    <button @click="fundingAmount = fundingSourceBalance; validateFunding();" class="text-primary font-medium hover:underline cursor-pointer">Use Max</button>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-primary/20 border border-primary/50 rounded-lg">
                <div class="text-sm font-medium text-card-foreground flex items-center gap-2">
                    <RefreshCcwIcon class="w-4 h-4 text-primary" />
                    Estimated USD Funds:
                </div>
                <span class="text-lg font-bold text-primary">${{ estimatedUSDFunds }}</span>
            </div>

            <button
                :disabled="!isWalletConnected || isOrderProcessing || !validateFunding()"
                @click="performFunding"
                :class="['w-full py-3 font-bold rounded-lg transition-opacity text-sm flex items-center justify-center gap-2 cursor-pointer', !isWalletConnected || isOrderProcessing || !validateFunding() ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                <Loader2Icon v-if="isOrderProcessing" class="w-4 h-4 animate-spin" />
                <span>{{ isOrderProcessing ? 'Converting...' : `Convert ${fundingSourceToken} & Fund Live Account` }}</span>
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
