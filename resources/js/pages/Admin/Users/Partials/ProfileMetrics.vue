<script setup lang="ts">
    import { computed, DefineComponent } from 'vue';

    interface Metric {
        label: string;
        value: string | number;
        icon: DefineComponent | any;
        color: string;
    }

    const props = defineProps<{
        metrics: Metric[];
        isLiveMode: boolean;
        liveBalance: number;
        currentBalance: number | string;
    }>();

    defineEmits<{
        (e: 'update:isLiveMode', value: boolean): void;
        (e: 'deposit'): void;
        (e: 'withdraw'): void;
    }>();

    const combinedTotalValue = computed(() => {
        const walletTotal = Number(props.currentBalance) || 0;
        return walletTotal + Number(props.liveBalance);
    });
</script>

<template>
    <div class="grid grid-cols-1 gap-6">
        <div class="card-crypto rounded-xl border p-4 sm:p-6">
            <!-- Main Total Assets Section -->
            <div class="mb-4 sm:mb-5">
                <h2 class="text-sm font-semibold text-muted-foreground mb-2">Total Assets</h2>

                <div class="flex items-baseline gap-2 sm:gap-3 mb-3">
                <span class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-card-foreground tracking-tight">
                    {{ '$' + combinedTotalValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
                    <span
                        class="font-bold px-2 py-1 rounded-md text-xs uppercase tracking-wide whitespace-nowrap"
                        :class="isLiveMode ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                    >
                    {{ isLiveMode ? 'Live' : 'Demo' }}
                </span>
                </div>
            </div>

            <!-- Balance Breakdown -->
            <div class="pt-4 border-t space-y-2.5">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs sm:text-sm font-medium text-muted-foreground">Crypto:</span>
                    <span class="bg-secondary/70 text-card-foreground px-2.5 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold border border-border whitespace-nowrap">
                    {{ '$' + Number(currentBalance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs sm:text-sm font-medium text-muted-foreground">Trading Balance:</span>
                    <span class="bg-secondary/70 text-card-foreground px-2.5 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold border border-border whitespace-nowrap">
                    {{ '$' + liveBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card-crypto rounded-xl border p-0 grid grid-cols-2 md:grid-cols-4 divide-x divide-border divide-y md:divide-y-0">
        <div v-for="(metric, index) in metrics" :key="metric.label"
             :class="[
                'p-3 sm:p-4 flex flex-col h-full',
                'md:border-b-0',
                (index % 2 !== 0) && 'border-l-0',
                (index < 2 && metrics.length > 2) && 'border-b',
             ]">
            <div class="flex justify-between items-center mb-2">
                <h5 class="text-[10px] sm:text-xs font-medium uppercase text-muted-foreground">{{ metric.label }}</h5>
                <component :is="metric.icon" class="w-4 h-4 sm:w-5 sm:h-5" :class="metric.color" />
            </div>
            <h3 class="text-lg sm:text-xl font-bold text-foreground mt-auto">{{ metric.value }}</h3>
        </div>
    </div>
</template>
