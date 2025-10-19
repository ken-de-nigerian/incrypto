<script setup lang="ts">
    import {
        ActivityIcon,
        BarChart3Icon,
        TrendingDownIcon,
        TrendingUpIcon,
        WalletIcon,
        Wallet
    } from 'lucide-vue-next';

    defineProps<{
        totalPortfolioValue: number;
        portfolioChange24h: number;
        topTokens: Array<{
            symbol: string;
            logo: string;
            balance: number;
            value: number;
        }>;
        totalAssets: number;
        availableTokensCount: number;
    }>();

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
    <div class="hidden sm:block">
        <div class="bg-gradient-to-br from-primary/10 to-primary/10 border border-border rounded-2xl p-6 mb-4">
            <div class="flex items-center gap-2 mb-2">
                <WalletIcon class="w-5 h-5 text-primary" />
                <span class="text-sm font-medium text-muted-foreground">Portfolio Value</span>
            </div>

            <div class="text-2xl sm:text-3xl font-bold text-card-foreground mb-1">
                ${{ totalPortfolioValue.toFixed(2) }}
            </div>

            <div class="flex items-center gap-1 text-sm" :class="portfolioChange24h >= 0 ? 'text-primary' : 'text-destructive'">
                <TrendingUpIcon v-if="portfolioChange24h >= 0" class="w-4 h-4" />
                <TrendingDownIcon v-else class="w-4 h-4" />
                <span>{{ portfolioChange24h >= 0 ? '+' : '' }}{{ portfolioChange24h.toFixed(2) }}% (24h)</span>
            </div>
        </div>

        <div class="bg-card border border-border rounded-2xl p-4 mb-4">
            <div v-if="topTokens.length > 0">
                <h3 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                    <BarChart3Icon class="w-4 h-4" />
                    Top Holdings
                </h3>

                <div class="space-y-3">
                    <div v-for="token in topTokens" :key="token.symbol" class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <img :src="token.logo" :alt="token.symbol" class="w-6 h-6 rounded-full" />
                            <div>
                                <div class="text-sm font-medium text-card-foreground">{{ formatSymbol(token.symbol) }}</div>
                                <div class="text-xs text-muted-foreground">{{ token.balance.toFixed(4) }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-card-foreground">${{ token.value.toFixed(2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center text-sm text-muted-foreground py-8">
                <div class="flex justify-center mb-3">
                    <Wallet class="h-10 w-10 text-muted-foreground/60" />
                </div>
                <p class="text-base font-medium mb-1 text-card-foreground">No Holdings</p>
                <p class="text-xs">Your portfolio will be tracked here once you acquire tokens.</p>
            </div>
        </div>

        <div class="bg-card border border-border rounded-2xl p-4">
            <h3 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                <ActivityIcon class="w-4 h-4" />
                Quick Stats
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Total Assets</span>
                    <span class="text-sm font-semibold text-card-foreground">{{ totalAssets }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Available Tokens</span>
                    <span class="text-sm font-semibold text-primary">{{ availableTokensCount }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Network Status</span>
                    <span class="text-sm font-semibold text-primary flex items-center gap-1">
                        <div class="w-2 h-2 bg-primary rounded-full"></div>
                        Active
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
