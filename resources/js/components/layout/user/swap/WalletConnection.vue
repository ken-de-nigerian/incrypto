<script setup lang="ts">
    import { WalletIcon, CheckIcon, CopyIcon, XIcon } from 'lucide-vue-next';
    import { ref } from 'vue';

    defineProps<{
        isWalletConnected: boolean;
        walletAddress: string;
    }>();

    const emit = defineEmits(['update:isWalletConnected', 'update:walletAddress']);

    const copiedAddress = ref(false);

    const connectWallet = () => {
        setTimeout(() => {
            emit('update:isWalletConnected', true);
            emit('update:walletAddress', '0x742d35Cc6634C0532925a3b844Bc9e3a4f');
        }, 100);
    };

    const disconnectWallet = () => {
        emit('update:isWalletConnected', false);
        emit('update:walletAddress', '');
    };

    const copyAddress = async () => {
        try {
            await navigator.clipboard.writeText('0x742d35Cc6634C0532925a3b844Bc9e3a4f');
            copiedAddress.value = true;
            setTimeout(() => (copiedAddress.value = false), 2000);
        } catch (err) {
            console.error('Failed to copy address:', err);
        }
    };
</script>

<template>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-0 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-card-foreground">Swap Tokens</h1>
            <p class="text-sm text-muted-foreground mt-1">Trade tokens instantly across chains</p>
        </div>

        <div class="flex items-center gap-2">
            <button
                v-if="!isWalletConnected"
                @click="connectWallet"
                class="flex items-center gap-2 px-3 py-2 bg-primary hover:opacity-90 text-primary-foreground rounded-lg font-semibold text-xs sm:text-sm cursor-pointer">
                <WalletIcon class="w-4 h-4" />
                Connect
            </button>

            <div v-else class="flex items-center gap-2">
                <div class="px-3 py-2 bg-card border border-border rounded-lg flex items-center gap-2">
                    <div class="w-2 h-2 bg-primary rounded-full"></div>
                    <span class="text-xs sm:text-sm font-mono text-card-foreground truncate max-w-[120px] sm:max-w-[150px]">{{ walletAddress }}</span>
                    <button @click="copyAddress" class="p-1 hover:bg-muted rounded cursor-pointer">
                        <CheckIcon v-if="copiedAddress" class="w-3 h-3 text-primary" />
                        <CopyIcon v-else class="w-3 h-3 text-muted-foreground" />
                    </button>
                </div>

                <button @click="disconnectWallet" class="p-2 bg-card border border-border hover:bg-muted rounded-lg">
                    <XIcon class="w-4 h-4 text-muted-foreground" />
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* Truncate address on small screens */
    @media (max-width: 320px) {
        .max-w-\[120px\] {
            max-width: 100px;
        }
        .text-xs {
            font-size: 0.7rem;
        }
    }
</style>
