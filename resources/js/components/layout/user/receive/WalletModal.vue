<script setup lang="ts">
    import { ref, computed, watch } from 'vue';
    import { XIcon, CopyIcon, CheckIcon, QrCodeIcon, WalletIcon, AlertCircleIcon, TrendingUpIcon, TrendingDownIcon } from 'lucide-vue-next';
    import QRCodeVue3 from 'qrcode-vue3';

    const props = defineProps<{
        isOpen: boolean;
        token: any | null;
        balance: number;
        price: number;
    }>();

    const emit = defineEmits(['close', 'create-pending-transaction']);

    const copiedAddress = ref(false);

    const walletAddress = computed(() => props.token?.address || '');

    const copyAddress = async () => {
        if (!walletAddress.value) return;
        try {
            await navigator.clipboard.writeText(walletAddress.value);
            copiedAddress.value = true;

            // Tell the parent to create the pending transaction record
            emit('create-pending-transaction');

            setTimeout(() => (copiedAddress.value = false), 2000);
        } catch (err) {
            console.error('Failed to copy address:', err);
        }
    };

    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
            copiedAddress.value = false; // Reset on open
        } else {
            document.body.style.overflow = '';
        }
    });
</script>

<template>
    <Teleport to="body">
        <div v-if="isOpen && token" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="$emit('close')">
            <div class="w-full max-w-lg bg-card border border-border rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-card-foreground">Receive {{ token.symbol }}</h3>
                        <button @click="$emit('close')" class="p-2 hover:bg-muted rounded-lg transition-colors"><XIcon class="w-5 h-5 text-muted-foreground" /></button>
                    </div>
                    <div class="flex items-center gap-3">
                        <img :src="token.logo" :alt="token.symbol" class="w-12 h-12 rounded-full" />
                        <div class="flex-1">
                            <div class="font-semibold text-card-foreground">{{ token.name }}</div>
                            <div class="text-sm text-muted-foreground flex items-center gap-2"><span>{{ balance.toFixed(6) }} {{ token.symbol }}</span></div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-card-foreground">${{ (balance * price).toFixed(2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto">
                    <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl flex items-start gap-3">
                        <AlertCircleIcon class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                        <div class="text-sm">
                            <p class="font-semibold text-yellow-700 mb-1">Important: Network Warning</p>
                            <p class="text-muted-foreground">Only send <span class="font-semibold text-card-foreground">{{ token.symbol }}</span> to this address. Sending other assets or using the wrong network may result in permanent loss of funds.</p>
                        </div>
                    </div>

                    <div v-if="walletAddress">
                        <label class="text-sm font-semibold text-card-foreground mb-3 block flex items-center gap-2"><QrCodeIcon class="w-4 h-4" />Scan QR Code</label>
                        <div class="flex justify-center p-4 sm:p-6 bg-white rounded-xl border-2 border-border">
                            <QRCodeVue3 :value="walletAddress" :size="240" :corners-square-options="{ type: 'square', color: '#000000' }" :corners-dot-options="{ type: 'square', color: '#000000' }" :dots-options="{ type: 'square', color: '#000000' }" />
                        </div>
                        <p class="text-xs text-muted-foreground text-center mt-3">Scan this QR code with your sending wallet</p>
                    </div>

                    <div v-if="walletAddress">
                        <label class="text-sm font-semibold text-card-foreground mb-3 block flex items-center gap-2"><WalletIcon class="w-4 h-4" />Your Wallet Address</label>
                        <div class="p-4 bg-muted/50 border border-border rounded-xl">
                            <div class="text-sm font-mono text-card-foreground break-all mb-3 leading-relaxed">{{ walletAddress }}</div>
                            <button @click="copyAddress" :class="['w-full py-3 px-4 rounded-lg font-semibold text-sm flex items-center justify-center gap-2 transition-all', copiedAddress ? 'bg-primary/20 text-primary border-2 border-primary' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                                <CheckIcon v-if="copiedAddress" class="w-5 h-5" />
                                <CopyIcon v-else class="w-5 h-5" />
                                {{ copiedAddress ? 'Address Copied to Clipboard!' : 'Copy Address' }}
                            </button>
                        </div>
                    </div>

                    <div v-else class="p-8 text-center">
                        <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4"><WalletIcon class="w-8 h-8 text-muted-foreground" /></div>
                        <h3 class="text-lg font-semibold text-card-foreground mb-2">No Wallet Address Available</h3>
                        <p class="text-sm text-muted-foreground">This token does not have a configured wallet address yet.</p>
                    </div>

                    <div v-if="walletAddress" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-muted/30 rounded-xl">
                            <div class="text-xs text-muted-foreground mb-1">Symbol</div>
                            <div class="text-sm font-semibold text-card-foreground">{{ token.symbol }}</div>
                        </div>
                        <div class="p-4 bg-muted/30 rounded-xl">
                            <div class="text-xs text-muted-foreground mb-1">Current Price</div>
                            <div class="text-sm font-semibold text-card-foreground">${{ price.toFixed(2) }}</div>
                        </div>
                        <div class="p-4 bg-muted/30 rounded-xl">
                            <div class="text-xs text-muted-foreground mb-1">Balance</div>
                            <div class="text-sm font-semibold text-card-foreground">{{ balance.toFixed(6) }}</div>
                        </div>
                        <div class="p-4 bg-muted/30 rounded-xl">
                            <div class="text-xs text-muted-foreground mb-1">24h Change</div>
                            <div :class="['text-sm font-semibold flex items-center gap-1', token.price_change_24h >= 0 ? 'text-primary' : 'text-destructive']">
                                <TrendingUpIcon v-if="token.price_change_24h >= 0" class="w-3 h-3" />
                                <TrendingDownIcon v-else class="w-3 h-3" />
                                {{ token.price_change_24h >= 0 ? '+' : '' }}{{ token.price_change_24h.toFixed(2) }}%
                            </div>
                        </div>
                    </div>

                    <div v-if="walletAddress" class="p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                        <div class="flex items-start gap-3">
                            <AlertCircleIcon class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                            <div class="text-xs text-muted-foreground">
                                <p class="font-semibold text-blue-700 mb-1">Processing Time</p>
                                <p>Deposits are usually credited within 10-30 minutes depending on network congestion. You can track your transaction status in the "Received Cryptos" section.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-border bg-muted/30">
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <button @click="$emit('close')" class="w-full py-3 px-4 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-lg font-semibold text-sm transition-colors">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
