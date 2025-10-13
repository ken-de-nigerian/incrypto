<script setup lang="ts">
    import { WalletIcon, TrendingUpIcon, TrendingDownIcon } from 'lucide-vue-next';

    defineProps<{
        portfolioValue: number;
        portfolioChange24h: number;
    }>();
</script>

<template>
    <div class="hidden sm:block">
        <div class="bg-gradient-to-br from-primary/10 to-primary/5 border border-border rounded-2xl p-6">
            <div class="flex items-center gap-2 mb-2">
                <WalletIcon class="w-5 h-5 text-primary" />
                <span class="text-xs sm:text-sm font-medium text-muted-foreground">Portfolio Value</span>
            </div>

            <div class="text-2xl sm:text-3xl font-bold text-card-foreground mb-1">
                ${{ portfolioValue.toFixed(2) }}
            </div>

            <div :class="['flex items-center gap-1 text-xs sm:text-sm', portfolioChange24h >= 0 ? 'text-primary' : 'text-destructive']">
                <TrendingUpIcon v-if="portfolioChange24h >= 0" class="w-4 h-4" />
                <TrendingDownIcon v-else class="w-4 h-4" />
                <span>{{ portfolioChange24h >= 0 ? '+' : '' }}{{ portfolioChange24h.toFixed(2) }}% (24h)</span>
            </div>
        </div>
    </div>
</template>
