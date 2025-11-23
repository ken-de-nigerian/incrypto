<script setup lang="ts">
    import { AlertCircleIcon, ArrowDownIcon, SendIcon, XIcon, LoaderCircle } from 'lucide-vue-next';
    import { watch } from 'vue';

    const props = defineProps<{
        isOpen: boolean;
        isSending: boolean;
        transactionDetails: any | null;
        errorMessage?: string | null;
    }>();

    const emit = defineEmits(['close', 'confirm-send']);

    const handleClose = () => {
        emit('close');
    };

    // Keep the body scroll lock logic
    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });

    // Function to format the token symbol
    const formatSymbol = (symbol: string): string => {
        if (!symbol) return '';

        // Regex to find USDT_ followed by BEP20, ERC20, or TRC20 (case-insensitive)
        const formatted = symbol.replace(/USDT_(BEP20|ERC20|TRC20)/i, (match) => {
            // Replace the underscore with a space only in the matched segment
            return match.replace('_', ' ');
        });

        return formatted.toUpperCase();
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
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            >
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-95 lg:scale-100 lg:opacity-100"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95 lg:scale-100 lg:opacity-100"
                >
                    <div
                        v-if="isOpen"
                        class="bg-card w-full h-full max-h-full lg:w-full lg:max-w-lg lg:h-auto lg:max-h-[90vh] flex flex-col rounded-none lg:rounded-2xl shadow-2xl overflow-hidden border-border relative lg:border"
                        @click.stop >
                        <div class="p-6 border-b border-border flex-shrink-0">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xl font-bold text-card-foreground">{{ errorMessage ? 'Transaction Failed' : 'Confirm Transaction' }}</h3>
                                <button @click="handleClose" class="p-2 hover:bg-muted/70 rounded-lg transition-colors cursor-pointer">
                                    <XIcon class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>
                            <p v-if="!errorMessage" class="text-sm text-muted-foreground">
                                Please review the transaction details carefully before confirming
                            </p>
                        </div>

                        <div class="p-6 space-y-6 overflow-y-auto flex-1 no-scrollbar">
                            <div v-if="errorMessage" class="p-4 rounded-xl flex items-start gap-3 bg-destructive/10 border border-destructive/20">
                                <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5 text-destructive" />
                                <p class="text-sm font-semibold text-destructive">{{ errorMessage }}</p>
                            </div>

                            <div v-else-if="transactionDetails">
                                <div class="p-4 bg-warning/10 border border-warning/30 rounded-xl flex items-start gap-3">
                                    <AlertCircleIcon class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" />
                                    <div class="text-sm">
                                        <p class="font-semibold text-warning mb-1">Transaction Cannot Be Reversed</p>
                                        <p class="text-muted-foreground">Once confirmed, this transaction cannot be cancelled or reversed. Please verify all details are correct.</p>
                                    </div>
                                </div>

                                <div class="text-center py-4">
                                    <div class="text-sm text-muted-foreground mb-2">You're sending</div>
                                    <div class="flex items-center justify-center gap-3 mb-2">
                                        <img :src="transactionDetails.token.logo" :alt="transactionDetails.token.symbol" loading="lazy" class="w-12 h-12 rounded-full" />
                                        <div class="text-3xl font-bold text-card-foreground">{{ transactionDetails.amount }}</div>
                                        <div class="text-2xl font-semibold text-muted-foreground">{{ formatSymbol(transactionDetails.token.symbol) }}</div>
                                    </div>
                                    <div class="text-lg text-muted-foreground">≈ ${{ transactionDetails.amountInUSD.toFixed(2) }} USD</div>
                                </div>

                                <div class="flex justify-center mb-4">
                                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                        <ArrowDownIcon class="w-5 h-5 text-primary" />
                                    </div>
                                </div>

                                <div class="p-4 bg-muted/50 border border-border rounded-xl mb-4">
                                    <div class="text-xs text-muted-foreground mb-2 font-semibold uppercase">Recipient Address</div>
                                    <div class="text-sm font-mono text-card-foreground break-all leading-relaxed">{{ transactionDetails.recipient_address }}</div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                                        <span class="text-sm text-muted-foreground">Speed</span>
                                        <span class="text-sm font-semibold text-card-foreground capitalize">{{ transactionDetails.speed }} ({{ transactionDetails.speedTime }})</span>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                                        <span class="text-sm text-muted-foreground">Network Fee</span>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-card-foreground">${{ transactionDetails.feeInUSD.toFixed(2) }}</div>
                                            <div class="text-xs text-muted-foreground">{{ transactionDetails.fee.toFixed(6) }} {{ formatSymbol(transactionDetails.token.symbol) }}</div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between p-4 bg-primary/10 border border-primary/20 rounded-lg mb-2">
                                        <span class="text-sm font-semibold text-card-foreground">Total Cost</span>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-card-foreground">${{ transactionDetails.totalCostInUSD.toFixed(2) }}</div>
                                            <div class="text-xs text-muted-foreground">{{ transactionDetails.totalCost.toFixed(6) }} {{ formatSymbol(transactionDetails.token.symbol) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 bg-accent/10 border border-accent/30 rounded-xl">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-muted-foreground">Balance After Transaction</span>
                                        <span class="text-sm font-semibold text-card-foreground">{{ transactionDetails.balanceAfter.toFixed(6) }} {{ formatSymbol(transactionDetails.token.symbol) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 border-t border-border bg-muted/30 flex-shrink-0">
                            <div v-if="!errorMessage" class="grid grid-cols-2 gap-3">
                                <button @click="handleClose" :disabled="isSending" class="py-3 px-4 bg-muted/70 hover:bg-muted/90 border border-border text-card-foreground rounded-lg font-semibold transition-colors disabled:opacity-50 cursor-pointer">
                                    Cancel
                                </button>

                                <button @click="$emit('confirm-send')" :disabled="isSending" class="py-3 px-4 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-semibold flex items-center justify-center gap-2 transition-opacity disabled:opacity-50 cursor-pointer">
                                    <SendIcon v-if="!isSending" class="w-5 h-5" />
                                    <LoaderCircle v-else class="w-5 h-5 animate-spin" /> {{ isSending ? 'Sending...' : 'Send' }}
                                </button>
                            </div>

                            <div v-else>
                                <button @click="handleClose" class="w-full py-3 px-4 bg-muted/70 hover:bg-muted/90 border border-border text-card-foreground rounded-lg font-semibold transition-colors cursor-pointer">
                                    Close
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
    /* Styling to hide the scrollbar while allowing scrolling */
    .no-scrollbar::-webkit-scrollbar {
        display: none; /* Hide scrollbar for Chrome, Safari and Opera */
    }

    .no-scrollbar {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>
