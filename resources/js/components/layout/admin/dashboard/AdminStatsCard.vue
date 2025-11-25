<script setup lang="ts">
    import type { Icon as LucideIcon } from 'lucide-vue-next';

    interface AdminStat {
        title: string;
        value: string;
        Icon: LucideIcon;
    }

    defineProps<AdminStat>();
</script>

<template>
    <div class="card-crypto p-4 sm:p-5 flex flex-col justify-between h-full min-w-[150px]">
        <div class="flex items-start justify-between w-full">
            <div class="truncate pr-4">
                <p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
                <h3 class="text-xl sm:text-2xl font-bold text-card-foreground mt-1">{{ value }}</h3>
            </div>

            <component
                :is="Icon" :class="['w-8 h-8 bg-secondary shrink-0 p-1.5 rounded-lg']" />
        </div>
    </div>
</template>
