<script setup lang="ts">
    import { ActivityIcon } from 'lucide-vue-next';

    defineProps<{
        transactionCount: number;
        slippage: number;
    }>();
</script>

<template>
    <!-- Quick Stats -->
    <div class="bg-card border border-border rounded-2xl p-4">
        <h3 class="text-xs sm:text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
            <ActivityIcon class="w-4 h-4" />
            Quick Stats
        </h3>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs sm:text-sm text-muted-foreground">Total Swaps</span>
                <span class="text-xs sm:text-sm font-semibold text-card-foreground">{{ transactionCount }}</span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs sm:text-sm text-muted-foreground">Avg. Slippage</span>
                <span class="text-xs sm:text-sm font-semibold text-primary">{{ slippage.toFixed(2) }}%</span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs sm:text-sm text-muted-foreground">Gas Saved</span>
                <span class="text-xs sm:text-sm font-semibold text-primary">$127.50</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* Responsive text sizes */
    @media (max-width: 320px) {
        .rounded-2xl {
            padding: 0.75rem;
        }
        .text-xs {
            font-size: 0.7rem;
        }
    }
</style>
