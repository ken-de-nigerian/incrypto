<script setup lang="ts">
    import {
        AlertTriangleIcon,
        DollarSignIcon,
        WalletIcon
    } from 'lucide-vue-next';
    import TradingModeSwitcher from '@/components/TradingModeSwitcher.vue';

    defineProps<{
        currentBalance: number;
        isLiveMode: boolean;
        liveBalance: number;
        demoBalance: number;
        warningMessage?: string;
    }>();

    defineEmits<{
        (e: 'update:isLiveMode', value: boolean): void;
        (e: 'deposit'): void;
        (e: 'withdraw'): void;
    }>();
</script>

<template>
    <div class="grid grid-cols-1 gap-6 mt-8 sm:mt-6">
        <div class="bg-card border border-border rounded-2xl p-5 sm:p-6 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5 sm:gap-6">
            <div class="w-full lg:w-auto">
                <h2 class="text-sm font-semibold text-muted-foreground mb-1.5">Wallet Balance</h2>

                <div class="flex flex-wrap items-baseline gap-3">
                    <span class="text-3xl sm:text-4xl font-extrabold text-card-foreground tracking-tight">
                        ${{ currentBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </span>
                </div>

                <div class="text-sm font-medium text-muted-foreground mt-2 flex items-center gap-2">
                    <span>Status:</span>
                    <span
                        class="font-bold px-2 py-0.5 rounded-md text-xs uppercase tracking-wide"
                        :class="isLiveMode ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                    >
                        {{ isLiveMode ? 'Live' : 'Demo' }}
                    </span>
                </div>

                <div
                    v-if="!isLiveMode && warningMessage"
                    class="flex items-start gap-2 mt-3 text-xs bg-yellow-500/10 text-yellow-600 dark:text-yellow-500 border border-yellow-500/20 rounded-lg p-2.5 max-w-md">
                    <AlertTriangleIcon class="w-4 h-4 flex-shrink-0" />
                    <span>{{ warningMessage }}</span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row w-full lg:w-auto lg:items-end gap-3 sm:gap-4">
                <div class="flex gap-3 w-full sm:w-auto">
                    <button
                        v-if="isLiveMode"
                        @click="$emit('deposit')"
                        class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-muted active:scale-[0.98] transition-all touch-manipulation cursor-pointer">
                        <WalletIcon class="w-4 h-4" />
                        Deposit
                    </button>

                    <button
                        v-if="isLiveMode"
                        @click="$emit('withdraw')"
                        class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-muted active:scale-[0.98] transition-all touch-manipulation cursor-pointer">
                        <DollarSignIcon class="w-4 h-4" />
                        Withdraw
                    </button>
                </div>

                <TradingModeSwitcher
                    :is-live-mode="isLiveMode"
                    :live-balance="liveBalance"
                    :demo-balance="demoBalance"
                    @update:is-live-mode="$emit('update:isLiveMode', $event)"
                    class="w-full sm:w-auto"
                />
            </div>
        </div>
    </div>
</template>
