<script setup lang="ts">
    import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
    import { Loader2, Wallet, Wallet2 } from 'lucide-vue-next';

    interface WalletBalance {
        name: string;
        symbol: string;
        balance: number;
        usd_value: number;
        profit_loss: number;
        price_change_percentage: number;
        is_profit: boolean;
        image: string;
    }

    interface WalletBalances {
        wallets: WalletBalance[];
        totalUsdValue: number;
    }

    interface UserProfile {
        live_trading_balance: number;
    }

    // Define props
    const props = defineProps<{
        wallet_balances?: WalletBalances;
        user_profile?: UserProfile;
    }>();

    const WALLETS_PER_PAGE = 5;
    const observer = ref<IntersectionObserver | null>(null);
    const sentinelRef = ref<HTMLElement | null>(null);

    const currentPage = ref(1);
    const isFetchingMore = ref(false);

    const liveTradingBalance = computed(() => {
        return Number(props.user_profile?.live_trading_balance || 0);
    });

    const combinedTotalValue = computed(() => {
        const walletTotal = props.wallet_balances?.totalUsdValue || 0;
        return Number(walletTotal) + Number(liveTradingBalance.value);
    });

    const allFilteredWallets = computed<WalletBalance[]>(() => {
        if (!props.wallet_balances) return [];
        return props.wallet_balances.wallets || [];
    });

    const paginatedWalletData = computed<WalletBalance[]>(() => {
        const limit = currentPage.value * WALLETS_PER_PAGE;
        return allFilteredWallets.value.slice(0, limit);
    });

    const hasMoreWallets = computed(() => {
        return paginatedWalletData.value.length < allFilteredWallets.value.length;
    });

    const loadMoreWallets = () => {
        if (!hasMoreWallets.value || isFetchingMore.value) return;

        isFetchingMore.value = true;
        setTimeout(() => {
            currentPage.value += 1;
            isFetchingMore.value = false;
            nextTick(setupObserver);
        }, 1000);
    };

    const setupObserver = () => {
        if (observer.value) {
            observer.value.disconnect();
        }

        if (sentinelRef.value && hasMoreWallets.value) {
            observer.value = new IntersectionObserver((entries) => {
                const [entry] = entries;
                if (entry.isIntersecting) {
                    loadMoreWallets();
                }
            }, {
                root: sentinelRef.value.parentElement,
                rootMargin: '0px',
                threshold: 0.1,
            });

            observer.value.observe(sentinelRef.value);
        }
    };

    watch(allFilteredWallets, () => {
        currentPage.value = 1;
        nextTick(setupObserver);
    });

    onMounted(setupObserver);

    onUnmounted(() => {
        if (observer.value) {
            observer.value.disconnect();
        }
    });
</script>

<template>
    <div class="card-crypto p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-secondary/70 rounded-lg flex items-center justify-center">
                    <Wallet class="w-5 h-5 sm:w-6 sm:h-6 text-card-foreground" />
                </div>
                <h3 class="text-base sm:text-lg font-semibold text-card-foreground">Wallets</h3>
            </div>
        </div>

        <div class="relative bg-secondary/50 rounded-lg sm:rounded-xl p-4 sm:p-6 mb-4 overflow-hidden">
            <div class="absolute right-0 bottom-0 opacity-20">
                <div class="w-24 h-24 sm:w-32 sm:h-32 bg-primary rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10">
                <p class="text-muted-foreground text-xs sm:text-sm mb-2">Total Assets</p>

                <div class="flex items-center gap-2 sm:gap-3 mb-2 flex-wrap">
                    <span class="text-2xl sm:text-4xl font-bold text-card-foreground">
                        {{ '$' + combinedTotalValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </span>
                </div>

                <!-- Breakdown of balances -->
                <div class="flex flex-col gap-1 text-xs text-muted-foreground mt-3 pt-3 border-t">
                    <div class="flex items-center justify-between">
                        <span>Crypto:</span>
                        <span class="bg-secondary/70 text-card-foreground px-2 sm:px-3 py-1 rounded-lg text-xs border border-border">
                            {{ '$' + props.wallet_balances?.totalUsdValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1">
                            Trading Balance:
                        </span>
                        <span class="bg-secondary/70 text-card-foreground px-2 sm:px-3 py-1 rounded-lg text-xs border border-border">
                            {{ '$' + liveTradingBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="wallet-list-container no-scrollbar space-y-2 sm:space-y-3 max-h-96 overflow-y-auto">
            <div v-for="(wallet, idx) in paginatedWalletData" :key="idx" class="flex items-center justify-between p-3 bg-muted/30 border border-border rounded-lg group hover:bg-muted/50 transition-colors">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                    <img
                        :src="`https://coin-images.coingecko.com${wallet.image}.png`"
                        loading="lazy"
                        :alt="`${wallet.name} icon`" class="w-8 h-8 sm:w-8 sm:h-8 border border-border rounded-full flex-shrink-0 object-cover bg-secondary/70"
                        @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                    />
                    <div class="min-w-0">
                        <p class="text-card-foreground font-medium text-sm sm:text-base truncate">{{ wallet.name }}</p>
                        <p class="text-muted-foreground text-xs sm:text-sm">{{ wallet.symbol }}</p>
                    </div>
                </div>

                <div class="text-right flex-shrink-0 ml-2">
                    <p class="text-card-foreground font-medium text-sm sm:text-base">
                        {{ wallet.balance.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 8 }) }}
                    </p>

                    <p class="text-muted-foreground text-xs sm:text-sm">
                        {{ '$' + wallet.usd_value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </p>

                    <p :class="['text-xs sm:text-sm', wallet.is_profit ? 'text-crypto-positive' : 'text-destructive']">
                        {{ (wallet.is_profit ? '+' : '') + '$' + Math.abs(wallet.profit_loss).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + wallet.price_change_percentage.toFixed(2) + '%)' }}
                    </p>
                </div>
            </div>

            <div ref="sentinelRef" v-if="hasMoreWallets" class="flex justify-center py-4">
                <Loader2 :class="['w-6 h-6 text-primary', isFetchingMore ? 'animate-spin' : '']" />
            </div>

            <div v-if="!hasMoreWallets && paginatedWalletData.length > 0 && !isFetchingMore" class="text-center text-sm text-muted-foreground py-4">
                End of wallets list.
            </div>
            <div v-else-if="paginatedWalletData.length === 0" class="text-center text-sm text-muted-foreground py-10 px-4">
                <div class="flex justify-center mb-4">
                    <Wallet2 class="h-12 w-12 text-muted-foreground" />
                </div>
                <p class="text-lg font-medium mb-2 text-card-foreground">No Active Wallets Found</p>
                <p class="text-sm">We couldn't find any wallets that match your current visibility settings or filters.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* WebKit browsers (Chrome, Safari) */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    /* MS Edge and Firefox */
    .no-scrollbar {
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
    }

    /* Add a max-height to the container to enable scrolling */
    .max-h-96 {
        max-height: 24rem; /* 384px */
    }

    /* Added class for smooth scrolling */
    .wallet-list-container {
        scroll-behavior: smooth;
    }
</style>
