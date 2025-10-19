<script setup lang="ts">
    import { computed } from 'vue';
    import { Wallet2 } from 'lucide-vue-next';
    import type { PropType } from 'vue';

    interface WalletBalance {
        name: string;
        symbol: string;
        balance: number;
        usd_value: number;
        profit_loss: number;
        price_change_percentage: number;
        is_profit: boolean;
        image: string;
        status: string;
    }

    const props = defineProps({
        visibleWalletData: {
            type: Array as PropType<WalletBalance[]>,
            required: true,
        },
        getIconSymbol: {
            type: Function as PropType<(symbol: string) => string>,
            required: true,
        }
    });

    const activeWallets = computed(() => {
        return props.visibleWalletData.filter(wallet => wallet.status === '1');
    });
</script>

<template>
    <div class="col-span-12 lg:col-span-8 xl:col-span-3 space-y-6 order-3 sm:order-3">
        <div class="card-crypto border rounded-xl p-0 overflow-hidden">
            <div class="flex flex-col flex-1">
                <div class="px-4 pt-4 pb-3 border-b border-border">
                    <h3 class="text-xs font-bold text-card-foreground uppercase tracking-wider mb-2 px-1">Wallet Balances</h3>
                    <p class="text-xs text-muted-foreground mb-1 px-1">View user wallet balances.</p>
                </div>

                <div class="px-3 sm:px-4 flex-1 overflow-y-auto no-scrollbar max-h-96 wallet-list-container">
                    <div class="space-y-2 py-3">
                        <div v-for="(wallet, idx) in activeWallets"
                             :key="idx"
                             class="flex items-center justify-between p-3 bg-muted/30 border border-border rounded-lg group hover:bg-muted/50 transition-colors">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <img
                                    :src="`https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/${getIconSymbol(wallet.symbol)}.png`"
                                    :alt="`${wallet.name} icon`"
                                    class="w-8 h-8 rounded-full flex-shrink-0 object-cover bg-background border border-border"
                                    @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                                />
                                <div class="min-w-0">
                                    <p class="text-card-foreground font-semibold text-sm truncate">{{ wallet.name }}</p>
                                    <p class="text-muted-foreground text-xs truncate">{{ wallet.symbol }}</p>
                                </div>
                            </div>

                            <div class="text-right flex-shrink-0 ml-2 min-w-[100px] sm:min-w-[120px]">
                                <p class="text-card-foreground font-bold text-sm block">
                                    {{ wallet.balance.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 8 }) }}
                                </p>

                                <p class="text-muted-foreground text-xs">
                                    ${{ wallet.usd_value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </p>

                                <div class="flex items-center justify-end gap-1" v-if="wallet.symbol !== 'USD' && wallet.usd_value > 0">
                                    <p :class="['text-xs', wallet.is_profit ? 'text-success' : 'text-destructive']">
                                        {{ (wallet.is_profit ? '↑' : '↓') + ' ' + wallet.price_change_percentage.toFixed(2) + '%' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeWallets.length > 0" class="text-center text-xs text-muted-foreground py-4">
                            End of visible wallets list.
                        </div>

                        <div v-else class="text-center text-sm text-muted-foreground py-8 px-4">
                            <div class="flex justify-center mb-3">
                                <Wallet2 class="h-10 w-10 text-muted-foreground" />
                            </div>
                            <p class="text-base font-medium mb-1 text-card-foreground">No Active Wallets Found</p>
                            <p class="text-xs">Adjust visibility settings or filters.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
