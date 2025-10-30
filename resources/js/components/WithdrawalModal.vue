<script setup lang="ts">
    import { computed, nextTick, ref, watch } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { ArrowUpRightIcon, Loader2Icon, AlertCircleIcon, BriefcaseIcon } from 'lucide-vue-next';
    import QuickActionModal from '@/components/QuickActionModal.vue';

    interface Props {
        isOpen: boolean;
        liveBalance: number;
        cryptoHoldings: Array<any>;
        pricesMap: Record<string, number>;
    }

    interface Emits {
        (e: 'close'): void;
    }

    const props = defineProps<Props>();
    const emit = defineEmits<Emits>();

    const withdrawalAmountUSD = ref<number | null>(100);
    const withdrawalTargetToken = ref('BTC');
    const withdrawalError = ref('');
    const isOrderProcessing = ref(false);
    const isWalletConnected = ref(true);

    const fundingTargetTokenRate = computed(() => props.pricesMap[withdrawalTargetToken.value] || 0);
    const estimatedCryptoToReceive = computed(() => {
        const amountUSD = parseFloat(withdrawalAmountUSD.value?.toString() || '0') || 0;
        const rate = fundingTargetTokenRate.value;
        return rate > 0 ? (amountUSD / rate).toFixed(8) : '0.00000000';
    });

    const validateWithdrawal = () => {
        const amountUSD = parseFloat(withdrawalAmountUSD.value?.toString() || '0') || 0;
        let error = '';

        if (amountUSD <= 0.01) {
            error = 'Amount must be greater than $0.01.';
        } else if (amountUSD > props.liveBalance) {
            error = `Insufficient Live Balance`;
        } else if (fundingTargetTokenRate.value <= 0) {
            error = `No market price available for ${withdrawalTargetToken.value}.`;
        }

        withdrawalError.value = error;
        return error === '';
    };

    const selectWithdrawalToken = (symbol: string) => {
        withdrawalTargetToken.value = symbol;
        withdrawalError.value = '';
        nextTick(() => validateWithdrawal());
    };

    const performWithdrawal = async () => {
        if (!validateWithdrawal()) return;

        const amountUSD = parseFloat(withdrawalAmountUSD.value?.toString() || '0') || 0;
        const amountCrypto = estimatedCryptoToReceive.value;

        isOrderProcessing.value = true;

        router.post(route('user.trade.withdraw.account'), {
            target_symbol: withdrawalTargetToken.value,
            usd_amount: amountUSD,
            estimated_crypto: amountCrypto
        }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isOrderProcessing.value = false;
                handleClose();
            },
            onError: (errors) => {
                withdrawalError.value = Object.values(errors)[0] || 'An unexpected error occurred.';
                isOrderProcessing.value = false;
            }
        });
    };

    const handleClose = () => {
        withdrawalAmountUSD.value = 100;
        withdrawalError.value = '';
        emit('close');
    };

    watch(() => props.isOpen, (newVal) => {
        if (newVal) {
            withdrawalAmountUSD.value = props.liveBalance;
            if (!props.cryptoHoldings.some(h => h.symbol === withdrawalTargetToken.value)) {
                withdrawalTargetToken.value = props.cryptoHoldings[0]?.symbol || 'BTC';
            }
            nextTick(() => validateWithdrawal());
        }
    });
</script>

<template>
    <QuickActionModal
        :is-open="isOpen"
        title="Withdraw / Convert to Crypto"
        subtitle="Convert your USD trading balance back into your preferred cryptocurrency"
        @close="handleClose">

        <div class="space-y-4">
            <div v-if="withdrawalError" class="p-4 rounded-xl flex items-start gap-3 bg-destructive/10 border border-destructive/20">
                <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5 text-destructive" />
                <p class="text-sm font-semibold text-destructive">{{ withdrawalError }}</p>
            </div>

            <div class="bg-primary/20 p-3 rounded-lg space-y-2">
                <p class="text-sm font-semibold text-card-foreground flex items-center gap-2">
                    <BriefcaseIcon class="w-4 h-4 text-primary" />
                    Available USD Value: <span class="text-primary">${{ liveBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                </p>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-card-foreground">Amount of USD to Convert:</h4>
                <div class="relative">
                    <input
                        v-model.number.lazy="withdrawalAmountUSD"
                        type="number"
                        step="any"
                        min="0.01"
                        :max="liveBalance"
                        placeholder="Amount in USD"
                        class="input-crypto w-full"
                        :class="{ 'border-destructive ring-destructive': withdrawalError }"
                        @change="validateWithdrawal" />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs">USD</span>
                </div>
                <div class="flex justify-between items-center text-xs text-muted-foreground bg-muted/50 p-2 rounded-lg">
                    <span>Available: ${{ liveBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                    <button @click="withdrawalAmountUSD = liveBalance; validateWithdrawal();" class="text-primary font-medium hover:underline cursor-pointer">Use Max</button>
                </div>
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-card-foreground">Select Target Crypto:</h4>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <button
                        v-for="holding in cryptoHoldings"
                        :key="holding.symbol"
                        @click="selectWithdrawalToken(holding.symbol)"
                        :class="['flex-shrink-0 px-4 py-2 rounded-lg text-xs border transition-all flex items-center gap-2 cursor-pointer', withdrawalTargetToken === holding.symbol ? 'bg-primary border-primary text-primary-foreground' : 'bg-background border-border text-muted-foreground hover:bg-muted/50']">
                        <img
                            :src="holding.logo"
                            loading="lazy"
                            :alt="holding.symbol"
                            class="h-6 w-6 object-contain flex-shrink-0"
                            onerror="this.src='https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                        />
                        <div class="text-left">
                            <div class="text-xs font-medium">{{ withdrawalTargetToken === holding.symbol ? 'Selected' : holding.symbol }}</div>
                        </div>
                    </button>
                </div>
                <div class="flex justify-between items-center text-xs text-muted-foreground bg-muted/50 p-2 rounded-lg">
                    <span>Current {{ withdrawalTargetToken }} Balance: {{ cryptoHoldings.find(h => h.symbol === withdrawalTargetToken)?.balance.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 8 }) || '0.0000' }}</span>
                    <span>Rate: 1 {{ withdrawalTargetToken }} = ${{ fundingTargetTokenRate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-secondary border border-border rounded-lg">
                <div class="text-sm font-medium text-card-foreground flex items-center gap-2">
                    <ArrowUpRightIcon class="w-4 h-4 text-primary" />
                    Estimated {{ withdrawalTargetToken }} to Receive:
                </div>
                <span class="text-lg font-bold text-primary">{{ estimatedCryptoToReceive }} {{ withdrawalTargetToken }}</span>
            </div>

            <button
                :disabled="!isWalletConnected || isOrderProcessing || !validateWithdrawal()"
                @click="performWithdrawal"
                :class="['w-full py-3 font-bold rounded-lg transition-opacity text-sm flex items-center justify-center gap-2 cursor-pointer', !isWalletConnected || isOrderProcessing || !validateWithdrawal() ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                <Loader2Icon v-if="isOrderProcessing" class="w-4 h-4 animate-spin" />
                <span>{{ isOrderProcessing ? 'Processing Conversion...' : `Convert to ${withdrawalTargetToken} & Withdraw` }}</span>
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
