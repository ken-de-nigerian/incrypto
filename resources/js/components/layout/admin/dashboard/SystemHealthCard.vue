<script setup lang="ts">
    import { Settings, CheckCircle, AlertTriangle } from 'lucide-vue-next';

    const healthMetrics = [
        { name: 'API Server', status: 'Operational', latency: '25ms', Icon: CheckCircle, class: 'text-success' },
        { name: 'Database Status', status: 'Healthy', latency: '4ms', Icon: CheckCircle, class: 'text-success' },
        { name: 'Queue Processor', status: 'Warning', backlog: '100+ jobs', Icon: AlertTriangle, class: 'text-warning' },
        { name: 'Storage Access', status: 'Operational', latency: '30ms', Icon: CheckCircle, class: 'text-success' },
    ];

    const getBgClass = (textClass: string) => {
        switch (textClass) {
            case 'text-success':
                return 'bg-success/10';
            case 'text-warning':
                return 'bg-warning/20';
            case 'text-destructive':
                return 'bg-destructive/20';
            default:
                return 'bg-transparent';
        }
    };
</script>

<template>
    <div class="card-crypto p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3 mb-2">
            <h2 class="text-xl font-semibold text-card-foreground">System Health</h2>
            <Settings class="w-5 h-5 text-muted-foreground cursor-pointer" title="System Settings" />
        </div>

        <ul class="space-y-3">
            <li
                v-for="metric in healthMetrics"
                :key="metric.name"
                :class="[
                    'flex items-center justify-between text-sm p-2 rounded-lg transition-colors',
                    getBgClass(metric.class)
                ]"
            >
                <div class="flex items-center">
                    <component :is="metric.Icon" :class="['w-4 h-4 mr-3', metric.class]" />
                    <span class="font-medium text-card-foreground">{{ metric.name }}</span>
                </div>
                <span :class="[metric.class, 'font-semibold']">
                    {{ metric.status }}
                    <span v-if="metric.latency" class="text-xs text-muted-foreground ml-1">({{ metric.latency }})</span>
                    <span v-if="metric.backlog" class="text-xs text-muted-foreground ml-1">({{ metric.backlog }})</span>
                </span>
            </li>
        </ul>
    </div>
</template>
