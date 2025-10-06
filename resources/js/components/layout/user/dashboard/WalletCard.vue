<script setup lang="ts">
    import { ref, computed } from 'vue';
    import { Wallet, Eye, EyeOff, RefreshCw } from 'lucide-vue-next';
    import { router } from '@inertiajs/vue3';

    // Define props
    const props = defineProps<{
        wallet_balances?: {
            wallets: {
                name: string;
                symbol: string;
                balance: number;
                usd_value: number;
                profit_loss: number;
                price_change_percentage: number;
                is_profit: boolean;
                image: string;
            }[];
            totalUsdValue: number;
        };
        hideBalance?: boolean;
    }>();

    // Define reactive state
    const hideZeroBalances = ref(false);
    const isBalanceHidden = ref(false);
    const isRefreshing = ref(false);

    // Check if data is loading (an initial load or refreshing)
    const isLoading = computed(() => !props.wallet_balances || isRefreshing.value);

    // Computed property for filtered wallet data
    const filteredWalletData = computed(() => {
        if (!props.wallet_balances) return [];

        if (hideZeroBalances.value) {
            return Object.values(props.wallet_balances.wallets).filter(wallet => wallet.balance > 0);
        }
        return Object.values(props.wallet_balances.wallets);
    });

    // Skeleton loader count
    const skeletonCount = 6;

    // Helper function to get the correct symbol for icon
    const getIconSymbol = (symbol: string) => {
        const lowerSymbol = symbol.toLowerCase();
        if (lowerSymbol.includes('usdt')) {
            return 'usdt';
        }
        return lowerSymbol;
    };

    // Toggle balance visibility
    const toggleBalanceVisibility = () => {
        isBalanceHidden.value = !isBalanceHidden.value;
    };

    // Refresh wallet data
    const refreshWalletData = () => {
        isRefreshing.value = true;

        router.reload({
            only: ['wallet_balances'],
            onFinish: () => {
                isRefreshing.value = false;
            }
        });
    };
</script>

<template>
    <div class="bg-zinc-900 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-zinc-800">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-zinc-800 rounded-lg flex items-center justify-center">
                    <Wallet class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                </div>
                <h3 class="text-base sm:text-lg font-semibold text-white">Wallets</h3>
            </div>

            <div class="flex items-center gap-2">
                <button @click="toggleBalanceVisibility" class="p-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors cursor-pointer" :title="isBalanceHidden ? 'Show balance' : 'Hide balance'">
                    <EyeOff v-if="isBalanceHidden" class="w-4 h-4 sm:w-5 sm:h-5 text-zinc-400" />
                    <Eye v-else class="w-4 h-4 sm:w-5 sm:h-5 text-zinc-400" />
                </button>

                <button @click="refreshWalletData" :disabled="isRefreshing || isLoading" class="p-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" title="Refresh wallet data">
                    <RefreshCw
                        :class="[
                            'w-4 h-4 sm:w-5 sm:h-5 text-zinc-400 transition-transform',
                            isRefreshing && 'animate-spin'
                        ]"
                    />
                </button>
            </div>
        </div>

        <div class="relative bg-gradient-to-br from-zinc-800 to-zinc-900 rounded-lg sm:rounded-xl p-4 sm:p-6 mb-4 overflow-hidden">
            <div class="absolute right-0 bottom-0 opacity-20">
                <div class="w-24 h-24 sm:w-32 sm:h-32 bg-yellow-400 rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10">
                <p class="text-zinc-400 text-xs sm:text-sm mb-2">Total Assets</p>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center gap-2 sm:gap-3 mb-2 flex-wrap">
                    <div class="h-8 sm:h-12 w-40 sm:w-48 bg-zinc-700 rounded-lg animate-pulse"></div>
                </div>

                <!-- Loaded State -->
                <div v-else class="flex items-center gap-2 sm:gap-3 mb-2 flex-wrap">
                    <span class="text-2xl sm:text-4xl font-bold text-white">
                        {{ isBalanceHidden ? '****' : '$' + props.wallet_balances!.totalUsdValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </span>

                    <span class="bg-zinc-800 text-white px-2 sm:px-3 py-1 rounded-lg text-xs sm:text-sm border border-zinc-700">
                        USD
                    </span>
                </div>
            </div>
        </div>

        <div v-if="!isLoading" class="flex items-center gap-2 mb-4 text-xs sm:text-sm text-zinc-400">
            <div class="flex items-center space-x-2">
                <input type="checkbox" v-model="hideZeroBalances" id="hideZeroBalances" class="h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background cursor-pointer" />
                <label for="hideZeroBalances" class="text-sm text-muted-foreground leading-relaxed cursor-pointer">
                    Hide zero balances
                </label>
            </div>
        </div>

        <div class="space-y-2 sm:space-y-3">
            <!-- Skeleton Loader -->
            <template v-if="isLoading">
                <div v-for="i in skeletonCount" :key="`skeleton-${i}`" class="flex items-center justify-between py-2 sm:py-3 border-b border-zinc-800 last:border-0">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                        <div class="w-8 h-8 sm:w-8 sm:h-8 rounded-full bg-zinc-700 animate-pulse flex-shrink-0"></div>
                        <div class="min-w-0 flex-1">
                            <div class="h-4 w-24 sm:w-32 bg-zinc-700 rounded animate-pulse mb-2"></div>
                            <div class="h-3 w-16 sm:w-20 bg-zinc-700 rounded animate-pulse"></div>
                        </div>
                    </div>

                    <div class="text-right flex-shrink-0 ml-2">
                        <div class="h-4 w-20 sm:w-24 bg-zinc-700 rounded animate-pulse mb-2 ml-auto"></div>
                        <div class="h-3 w-16 sm:w-20 bg-zinc-700 rounded animate-pulse mb-2 ml-auto"></div>
                        <div class="h-3 w-20 sm:w-24 bg-zinc-700 rounded animate-pulse ml-auto"></div>
                    </div>
                </div>
            </template>

            <!-- Actual Wallet Data -->
            <template v-else>
                <div v-for="(wallet, idx) in filteredWalletData" :key="idx" class="flex items-center justify-between py-2 sm:py-3 border-b border-zinc-800 last:border-0">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                        <img :src="`https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/${getIconSymbol(wallet.symbol)}.png`" :alt="`${wallet.name} icon`" class="w-8 h-8 sm:w-8 sm:h-8 rounded-full flex-shrink-0 object-cover bg-zinc-800" @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'" />
                        <div class="min-w-0">
                            <p class="text-white font-medium text-sm sm:text-base truncate">{{ wallet.name }}</p>
                            <p class="text-zinc-400 text-xs sm:text-sm">{{ wallet.symbol }}</p>
                        </div>
                    </div>

                    <div class="text-right flex-shrink-0 ml-2">
                        <p class="text-white font-medium text-sm sm:text-base">
                            {{ isBalanceHidden ? '****' : wallet.balance.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 8 }) }}
                        </p>

                        <p class="text-zinc-400 text-xs sm:text-sm">
                            {{ isBalanceHidden ? '****' : '$' + wallet.usd_value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </p>

                        <p :class="['text-xs sm:text-sm', wallet.is_profit ? 'text-green-500' : 'text-red-500']">
                            {{ isBalanceHidden ? '****' : (wallet.is_profit ? '+' : '') + '$' + Math.abs(wallet.profit_loss).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + wallet.price_change_percentage.toFixed(2) + '%)' }}
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
