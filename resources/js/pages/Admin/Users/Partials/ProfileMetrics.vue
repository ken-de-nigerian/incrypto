<script setup lang="ts">
    import { DefineComponent } from 'vue';

    interface Metric {
        label: string;
        value: string | number;
        icon: DefineComponent | any;
        color: string;
    }

    defineProps<{
        metrics: Metric[];
    }>();
</script>

<template>
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
