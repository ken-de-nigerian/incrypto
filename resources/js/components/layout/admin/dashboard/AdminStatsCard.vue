<script setup lang="ts">
    import { defineProps } from 'vue';
    import type { Icon as LucideIcon } from 'lucide-vue-next';
    import { ArrowUp, ArrowDown, Minus } from 'lucide-vue-next';

    interface AdminStat {
        title: string;
        value: string;
        change: string;
        Icon: LucideIcon;
        color: string;
        trend: 'up' | 'down' | 'flat';
    }

    const props = defineProps<AdminStat>();

    const TrendIcon = props.trend === 'up'
        ? ArrowUp
        : props.trend === 'down'
            ? ArrowDown
            : Minus;

    const trendClass = props.trend === 'up'
        ? 'text-success'
        : props.trend === 'down'
            ? 'text-destructive'
            : 'text-warning';

    const bgColorClass = (() => {
        const baseColor = props.color.replace('text-', '');

        switch (baseColor) {
            case 'primary':
                return 'bg-primary/20';
            case 'success':
                return 'bg-success/10';
            case 'warning':
                return 'bg-warning/20';
            case 'destructive':
                return 'bg-destructive/20';
            case 'secondary':
            default:
                return 'bg-secondary/20';
        }
    })();
</script>

<template>
    <div class="card-crypto p-5 flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
                <h3 class="text-2xl font-bold text-card-foreground mt-1">{{ value }}</h3>
            </div>
            <component
                :is="Icon"
                :class="['w-6 h-6 p-1 rounded-full', props.color, bgColorClass]"
            />
        </div>

        <div class="mt-4 flex items-center text-sm">
            <component :is="TrendIcon" :class="['w-4 h-4 mr-1', trendClass]" />
            <span :class="['font-medium', trendClass]">{{ change }}</span>
            <span class="ml-2 text-muted-foreground">vs Last Period</span>
        </div>
    </div>
</template>
