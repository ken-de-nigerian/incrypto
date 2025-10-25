<script setup lang="ts">
    import { Loader2 } from 'lucide-vue-next';

    interface WalletItem {
        key: string;
        name: string;
        symbol: string;
        image: string;
        network: string | null;
        is_visible: boolean;
        is_updating: boolean;
    }

    defineProps<{
        wallets: WalletItem[];
    }>();

    const emit = defineEmits(['toggleVisibility']);

    const toggle = (key: string) => {
        emit('toggleVisibility', key);
    };
</script>

<template>
    <div class="card-crypto border rounded-xl p-0 overflow-hidden">
        <div class="flex flex-col flex-1">
            <div class="px-4 pt-4 pb-3 border-b border-border">
                <h3 class="text-xs font-bold text-card-foreground uppercase tracking-wider mb-2 px-1">Wallet Visibility</h3>
                <p class="text-xs text-muted-foreground mb-1 px-1">Control which wallets are displayed.</p>
            </div>

            <div class="px-3 sm:px-4 flex-1 overflow-y-auto no-scrollbar max-h-[400px]">
                <div class="space-y-2 py-3">
                    <div
                        v-for="wallet in wallets"
                        :key="wallet.key"
                        class="flex items-center justify-between p-3 rounded-xl border border-border bg-secondary/30">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div class="w-8 h-8 flex-shrink-0 rounded-full overflow-hidden bg-background border border-border">
                                <img
                                    :src="`https://coin-images.coingecko.com${wallet.image}.png`"
                                     :alt="`${wallet.name} icon`"
                                     class="h-full w-full object-cover"
                                     @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                                />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-sm truncate leading-tight">{{ wallet.name }}</p>
                                <p class="text-[10px] text-muted-foreground mt-0.5 truncate">
                                    {{ wallet.symbol }}
                                    <span v-if="wallet.network" class="ml-1 text-[9px] font-medium px-1 py-0.5 rounded-full bg-border text-foreground">{{ wallet.network }}</span>
                                    <span class="ml-1 text-[9px] text-primary font-medium" v-if="wallet.is_visible">(Visible)</span>
                                    <span class="ml-1 text-[9px] text-muted-foreground italic font-medium" v-else>(Hidden)</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex-shrink-0 relative w-11 h-6 ml-2">
                            <label :for="`wallet-toggle-${wallet.key}`" class="absolute inset-0 inline-flex items-center cursor-pointer transition-opacity" :class="{ 'opacity-0 pointer-events-none': wallet.is_updating }">
                                <input type="checkbox" :id="`wallet-toggle-${wallet.key}`" :checked="wallet.is_visible" @change="toggle(wallet.key)" class="sr-only peer" :disabled="wallet.is_updating">
                                <div class="w-11 h-6 bg-border rounded-full peer peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-border after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                <span class="sr-only">Toggle Wallet Visibility</span>
                            </label>

                            <div v-if="wallet.is_updating" class="absolute inset-0 flex items-center justify-center">
                                <Loader2 class="animate-spin h-4 w-4 text-primary" />
                            </div>
                        </div>
                    </div>

                    <p v-if="wallets.length === 0" class="text-center text-xs text-muted-foreground py-10">
                        No wallets to manage.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
