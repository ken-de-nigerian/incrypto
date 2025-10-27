<script setup lang="ts">
    import { CheckIcon, CopyIcon, Calendar, Wallet } from 'lucide-vue-next';
    import { Lock as LockIconFilled } from 'lucide-vue-next';
    import { ref } from 'vue';

    interface ConnectedWallet {
        id: number;
        wallet_id?: string;
        name?: string;
        wallet_name?: string;
        security_type?: string;
        wallet_security_type?: string;
        wallet_logo?: string;
        created_at?: string;
        connected_at?: string;
        wallet_phrase?: string;
    }

    defineProps<{
        connectedWallets: ConnectedWallet[];
        getInitials: (name: string) => string;
        formatDate: (dateString: string) => string;
    }>();

    const copiedWalletId = ref<number | null>(null);
    const copyAddress = async (id: number, address: string | undefined) => {
        if (!address) return;
        try {
            await navigator.clipboard.writeText(address);
            copiedWalletId.value = id;
            setTimeout(() => {
                if (copiedWalletId.value === id) {
                    copiedWalletId.value = null;
                }
            }, 2000);
        } catch (err) {
            console.error('Failed to copy address: ', err);
        }
    };

    const truncateId = (id: string | undefined): string => {
        if (!id) return 'N/A';
        const strId = id.toString();
        if (strId.length <= 12) return strId;
        return `${strId.substring(0, 6)}...${strId.substring(strId.length - 4)}`;
    };

    const getSecurityClass = (type: string | undefined) => {
        if (!type) return 'text-muted-foreground bg-muted/70 border-border/70';
        const lowerType = type.toLowerCase();
        if (lowerType.includes('hot') || lowerType.includes('software')) {
            return 'text-warning bg-warning/10 border-warning/20';
        }
        if (lowerType.includes('cold') || lowerType.includes('hardware')) {
            return 'text-success bg-success/10 border-success/20';
        }
        return 'text-primary-foreground bg-primary/10 border border-primary/20';
    };
</script>

<template>
    <div v-if="connectedWallets.length === 0"
         class="text-center p-6 sm:p-10 bg-muted/50 rounded-lg border border-border text-muted-foreground text-sm">
        <Wallet class="w-8 h-8 mx-auto mb-3 text-muted-foreground/70" />
        <p class="font-semibold text-base text-foreground">No Wallets Connected</p>
        <p class="mt-1">Connect your first wallet to see your summary and activity here.</p>
    </div>

    <div v-else class="space-y-4 overflow-y-auto max-h-96 pr-1 scrollbar-hide">
        <div v-for="wallet in connectedWallets"
             :key="wallet.id"
             class="p-4 border border-border rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between">

            <div class="flex items-start gap-4 min-w-0 flex-1 w-full sm:w-auto">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 border border-primary/20 mt-1">
                    <img v-if="wallet.wallet_logo"
                         :src="`https://www.cryptocompare.com${wallet.wallet_logo}`"
                         loading="lazy"
                         :alt="wallet.wallet_name"
                         class="w-7 h-7 rounded-full object-contain"
                    />
                    <span v-else class="text-sm font-bold text-primary">{{ getInitials(wallet.wallet_name || 'N A') }}</span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h4 class="text-base font-semibold text-card-foreground break-words">{{ wallet.wallet_name }}</h4>
                        <span
                            class="text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wider flex-shrink-0"
                            :class="getSecurityClass(wallet.security_type || wallet.wallet_security_type)">
                            {{ wallet.security_type || wallet.wallet_security_type }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <code class="text-xs text-muted-foreground bg-muted/50 px-2 py-1 rounded-lg font-mono break-all sm:break-words">
                            ID: {{ truncateId(wallet.wallet_phrase || wallet.wallet_id) }}
                        </code>

                        <button
                            @click="copyAddress(wallet.id, wallet.wallet_phrase || wallet.wallet_id)"
                            class="p-1.5 hover:bg-primary/10 rounded-full transition-colors cursor-pointer flex-shrink-0"
                            :title="copiedWalletId === wallet.id ? 'Copied!' : 'Copy ID'">
                            <CheckIcon v-if="copiedWalletId === wallet.id" class="w-4 h-4 text-success" />
                            <CopyIcon v-else class="w-4 h-4 text-muted-foreground" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-row justify-between sm:flex-col sm:text-right flex-shrink-0 mt-2 sm:mt-0 sm:ml-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-border w-full sm:w-auto">
                <span class="text-xs font-medium text-muted-foreground flex items-center gap-1 mb-1 justify-start sm:justify-end">
                    <Calendar class="w-3 h-3 text-primary/70" />
                    {{ formatDate(wallet.connected_at || wallet.created_at || '') }}
                </span>

                <div class="text-xs font-medium block hover:text-primary/80 transition-colors flex items-center justify-end gap-1">
                    <LockIconFilled class="w-3 h-3 inline-block mr-0.5 align-text-bottom text-success" />
                    Security Verified
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .scrollbar-hide {
        /* For IE and Edge */
        -ms-overflow-style: none;

        /* For Firefox */
        scrollbar-width: none;
    }

    /* For Chrome, Safari, and Opera */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
</style>
