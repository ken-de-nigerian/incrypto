<script setup lang="ts">
    import { AlertCircleIcon, ArrowRightIcon, SendIcon, XIcon } from 'lucide-vue-next';

    defineProps<{
        isOpen: boolean;
        isSending: boolean;
        transactionDetails: any | null;
        errorMessage?: string | null;
    }>();

    defineEmits(['close', 'confirm-send']);
</script>

<template>
    <Teleport to="body">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="$emit('close')">
            <div class="w-full max-w-lg bg-card border border-border rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-border">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xl font-bold text-card-foreground">{{ errorMessage ? 'Transaction Failed' : 'Confirm Transaction' }}</h3>
                        <button @click="$emit('close')" class="p-2 hover:bg-muted rounded-lg transition-colors cursor-pointer">
                            <XIcon class="w-5 h-5 text-muted-foreground" />
                        </button>
                    </div>
                    <p v-if="!errorMessage" class="text-sm text-muted-foreground">
                        Please review the transaction details carefully before confirming
                    </p>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto">
                    <div v-if="errorMessage" class="p-4 rounded-xl flex items-start gap-3 bg-destructive/10 border border-destructive/20">
                        <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5 text-destructive" />
                        <p class="text-sm font-semibold text-destructive">{{ errorMessage }}</p>
                    </div>

                    <div v-else-if="transactionDetails">
                        <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl flex items-start gap-3">
                            <AlertCircleIcon class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                            <div class="text-sm">
                                <p class="font-semibold text-yellow-700 mb-1">Transaction Cannot Be Reversed</p>
                                <p class="text-muted-foreground">Once confirmed, this transaction cannot be cancelled or reversed. Please verify all details are correct.</p>
                            </div>
                        </div>

                        <div class="text-center py-4">
                            <div class="text-sm text-muted-foreground mb-2">You're sending</div>
                            <div class="flex items-center justify-center gap-3 mb-2">
                                <img :src="transactionDetails.token.logo" :alt="transactionDetails.token.symbol" class="w-12 h-12 rounded-full" />
                                <div class="text-3xl font-bold text-card-foreground">{{ transactionDetails.amount }}</div>
                                <div class="text-2xl font-semibold text-muted-foreground">{{ transactionDetails.token.symbol }}</div>
                            </div>
                            <div class="text-lg text-muted-foreground">≈ ${{ transactionDetails.amountInUSD.toFixed(2) }} USD</div>
                        </div>

                        <div class="flex justify-center">
                            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                <ArrowRightIcon class="w-5 h-5 text-primary" />
                            </div>
                        </div>

                        <div class="p-4 bg-muted/50 border border-border rounded-xl">
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
                                    <div class="text-xs text-muted-foreground">{{ transactionDetails.fee.toFixed(6) }} ETH</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-primary/10 border border-primary/20 rounded-lg mb-2">
                                <span class="text-sm font-semibold text-card-foreground">Total Cost</span>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-card-foreground">${{ transactionDetails.totalCostInUSD.toFixed(2) }}</div>
                                    <div class="text-xs text-muted-foreground">{{ transactionDetails.totalCost.toFixed(6) }} {{ transactionDetails.token.symbol }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Balance After Transaction</span>
                                <span class="text-sm font-semibold text-card-foreground">{{ transactionDetails.balanceAfter.toFixed(6) }} {{ transactionDetails.token.symbol }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-border bg-muted/30">
                    <div v-if="!errorMessage" class="grid grid-cols-2 gap-3">
                        <button @click="$emit('close')" :disabled="isSending" class="py-3 px-4 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-lg font-semibold transition-colors disabled:opacity-50 cursor-pointer">
                            Cancel
                        </button>
                        <button @click="$emit('confirm-send')" :disabled="isSending" class="py-3 px-4 bg-primary hover:opacity-90 text-primary-foreground rounded-lg font-semibold flex items-center justify-center gap-2 transition-opacity disabled:opacity-50 cursor-pointer">
                            <SendIcon v-if="!isSending" class="w-5 h-5" />
                            <div v-else class="w-5 h-5 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
                            {{ isSending ? 'Sending...' : 'Send' }}
                        </button>
                    </div>
                    <div v-else>
                        <button @click="$emit('close')" class="w-full py-3 px-4 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-lg font-semibold transition-colors cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
