<script setup lang="ts">
    import type { Icon as LucideIcon } from 'lucide-vue-next';

    interface AdminStat {
        title: string;
        value: string;
        Icon: LucideIcon;
        color: string;
    }

    const props = defineProps<AdminStat>();
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
                return 'bg-secondary/20';
            default:
                return 'bg-muted/30';
        }
    })();
</script>

<template>
    <div class="card-crypto p-4 sm:p-5 flex flex-col justify-between h-full min-w-[150px]">
        <div class="flex items-start justify-between w-full">
            <div class="truncate pr-4">
                <p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
                <h3 class="text-xl sm:text-2xl font-bold text-card-foreground mt-1">{{ value }}</h3>
            </div>

            <component
                :is="Icon"
                :class="['w-6 h-6 sm:w-7 sm:h-7 shrink-0 p-1.5 rounded-lg', props.color, bgColorClass]"
            />
        </div>
    </div>
</template>
