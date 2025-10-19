<script setup lang="ts">
    import { ListChecks } from 'lucide-vue-next';

    interface Activity {
        id: number;
        desc: string;
        time: string;
    }

    defineProps<{
        activities: Activity[];
    }>();
</script>

<template>
    <div class="card-crypto p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3 mb-2">
            <h2 class="text-xl font-semibold text-card-foreground">Recent Activity</h2>
            <ListChecks class="w-5 h-5 text-muted-foreground cursor-pointer" title="Activity Logs" />
        </div>

        <ul class="space-y-3">
            <li
                v-for="activity in activities"
                :key="activity.id"
                :class="['text-sm border-l-2 border-primary pl-4 py-1 transition-colors', 'bg-primary/10']"
            >
                <p class="text-card-foreground/90">{{ activity.desc }}</p>
                <span class="text-xs text-muted-foreground">{{ activity.time }}</span>
            </li>
        </ul>
        <div class="text-right pt-2">
            <button class="text-sm text-primary hover:text-primary-foreground/80">View All Logs</button>
        </div>
    </div>
</template>
