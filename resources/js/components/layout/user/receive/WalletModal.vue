<script setup lang="ts">
    import { ref, computed, watch } from 'vue';
    import { XIcon, CopyIcon, CheckIcon, WalletIcon, AlertCircleIcon } from 'lucide-vue-next';
    import QRCodeVue3 from 'qrcode-vue3';

    const props = defineProps<{
        isOpen: boolean;
        token: any | null;
        balance: number;
        price: number;
        error?: string | null;
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

    const handleClose = () => {
        emit('close');
    };

    const handleBackdropClick = (event: MouseEvent) => {
        // Only close if the click is directly on the backdrop container
        if (event.target === event.currentTarget) {
            handleClose();
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

    // Extract error message from complex error objects
    const getErrorMessage = (error: any): string => {
        if (!error) return '';
        if (typeof error === 'string') return error;
        if (error.message) return error.message;
        if (error.data?.message) return error.data.message;
        return 'An error occurred';
    };

    const errorMessage = computed(() => getErrorMessage(props.error));
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
                v-if="isOpen && token"
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
                        v-if="isOpen && token"
                        class="bg-card w-full h-full max-h-full lg:w-full lg:max-w-lg lg:h-auto lg:max-h-[90vh] flex flex-col rounded-none lg:rounded-2xl shadow-2xl overflow-hidden border-border relative lg:border"
                    >
                        <div class="p-6 border-b border-border flex-shrink-0">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-card-foreground">Receive {{ formatSymbol(token.symbol) }}</h3>
                                <button @click="handleClose" class="p-2 hover:bg-muted/70 rounded-lg cursor-pointer"><XIcon class="w-5 h-5 text-muted-foreground" /></button>
                            </div>
                            <div class="flex items-center gap-3">
                                <img :src="token.logo" :alt="token.symbol" loading="lazy" class="w-12 h-12 rounded-full flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-card-foreground truncate">{{ token.name }}</div>
                                    <div class="text-sm text-muted-foreground flex items-center gap-2"><span>{{ balance.toFixed(6) }} {{ formatSymbol(token.symbol) }}</span></div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-lg font-bold text-card-foreground">${{ (balance * price).toFixed(2) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Error Display -->
                        <div v-if="errorMessage" class="p-4 bg-destructive/10 border-b border-destructive/20">
                            <div class="flex items-start gap-3">
                                <AlertCircleIcon class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-destructive mb-1">Error</p>
                                    <p class="text-xs text-destructive/80 break-words whitespace-pre-wrap">{{ errorMessage }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-4 overflow-y-auto flex-1 no-scrollbar">
                            <div v-if="walletAddress">
                                <div class="flex justify-center p-4">
                                    <QRCodeVue3 :value="walletAddress" :size="180" :corners-square-options="{ type: 'square', color: '#000000' }" :corners-dot-options="{ type: 'square', color: '#000000' }" :dots-options="{ type: 'square', color: '#000000' }" />
                                </div>
                                <p class="text-xs text-muted-foreground text-center mt-2">Scan this QR code with your sending wallet</p>
                                <p class="text-xs text-muted-foreground text-center mt-2">Only send <span class="font-semibold text-card-foreground">{{ formatSymbol(token.symbol) }}</span> to this address. Sending other assets or using the wrong network may result in permanent loss of funds.</p>
                            </div>

                            <div v-else class="p-8 text-center">
                                <div class="w-16 h-16 bg-muted/50 rounded-full flex items-center justify-center mx-auto mb-4"><WalletIcon class="w-8 h-8 text-muted-foreground" /></div>
                                <h3 class="text-lg font-semibold text-card-foreground mb-2">No Wallet Address Available</h3>
                                <p class="text-sm text-muted-foreground">This token does not have a configured wallet address yet.</p>
                            </div>
                        </div>

                        <div class="p-6 border-t border-border bg-muted/30 flex-shrink-0">
                            <div v-if="walletAddress">
                                <label class="text-sm font-semibold text-card-foreground mb-3 block flex items-center gap-2"><WalletIcon class="w-4 h-4" />Your Wallet Address</label>
                                <div class="p-4 bg-muted/50 border border-border rounded-xl">
                                    <div class="text-sm font-mono text-card-foreground break-all mb-3 leading-relaxed">{{ walletAddress }}</div>
                                    <button @click="copyAddress" :class="['w-full py-3 px-4 rounded-lg font-semibold text-sm flex items-center justify-center gap-2 transition-all cursor-pointer', copiedAddress ? 'bg-primary/20 text-primary border-2 border-primary' : 'bg-primary hover:bg-primary/90 text-primary-foreground']">
                                        <CheckIcon v-if="copiedAddress" class="w-5 h-5" />
                                        <CopyIcon v-else class="w-5 h-5" />
                                        {{ copiedAddress ? 'Address Copied to Clipboard!' : 'Copy Address' }}
                                    </button>
                                </div>
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
