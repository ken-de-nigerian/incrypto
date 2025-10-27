<script setup lang="ts">
import { PieChart } from 'lucide-vue-next';
    import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
    import { Chart, BarController, DoughnutController, ArcElement, BarElement, Tooltip, Legend, CategoryScale, LinearScale } from 'chart.js';

    Chart.register(DoughnutController, BarController, ArcElement, BarElement, Tooltip, Legend, CategoryScale, LinearScale);

    const props = defineProps<{
        userStats: {
            total_users: number;
            total_active_users: number;
            total_suspended_users: number;
        };
    }>();

    const chartType = ref<'doughnut' | 'horizontal' | 'stacked'>('doughnut');
    const chartContainer = ref<HTMLCanvasElement | null>(null);
    let userDistributionChart: Chart | null = null;

    const userDoughnutData = computed(() => {
        const otherUsers = props.userStats.total_users - props.userStats.total_active_users - props.userStats.total_suspended_users;

        return {
            labels: ['Active Users', 'Suspended Users', 'Other Status'],
            datasets: [
                {
                    data: [
                        props.userStats.total_active_users,
                        props.userStats.total_suspended_users,
                        Math.max(0, otherUsers)
                    ],

                    backgroundColor: ['#10b981', '#ef4444', '#8b5cf6'],
                    hoverBackgroundColor: ['#059669', '#dc2626', '#7c3aed'],
                    borderColor: 'transparent',
                    borderWidth: 2,
                },
            ],
        };
    });

    const userHorizontalData = computed(() => {
        const otherUsers = props.userStats.total_users - props.userStats.total_active_users - props.userStats.total_suspended_users;
        return {
            labels: ['Active Users', 'Suspended Users', 'Other Status'],
            datasets: [
                {
                    label: 'User Count',
                    data: [
                        props.userStats.total_active_users,
                        props.userStats.total_suspended_users,
                        Math.max(0, otherUsers)
                    ],

                    backgroundColor: ['#10b981', '#ef4444', '#8b5cf6'],
                    hoverBackgroundColor: ['#059669', '#dc2626', '#7c3aed'],
                    borderRadius: 8,
                },
            ],
        };
    });

    const userStackedData = computed(() => {
        const otherUsers = props.userStats.total_users - props.userStats.total_active_users - props.userStats.total_suspended_users;
        return {
            labels: ['User Distribution'],
            datasets: [
                {
                    label: 'Active Users',
                    data: [props.userStats.total_active_users],
                    backgroundColor: '#10b981',
                    hoverBackgroundColor: '#059669',
                },
                {
                    label: 'Suspended Users',
                    data: [props.userStats.total_suspended_users],
                    backgroundColor: '#ef4444',
                    hoverBackgroundColor: '#dc2626',
                },
                {
                    label: 'Other Status',
                    data: [Math.max(0, otherUsers)],
                    backgroundColor: '#8b5cf6',
                    hoverBackgroundColor: '#7c3aed',
                },
            ],
        };
    });

    const getPercentage = (value: number): number => {
        return props.userStats.total_users > 0 ? (value / props.userStats.total_users) * 100 : 0;
    };

    const renderChart = () => {
        if (!chartContainer.value) return;

        if (userDistributionChart) {
            userDistributionChart.destroy();
        }

        const getChartTextColor = () => {
            return document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#6b7280';
        };

        const chartTextColor = getChartTextColor();

        let chartConfig = {};

        if (chartType.value === 'doughnut') {
            chartConfig = {
                type: 'doughnut',
                data: userDoughnutData.value,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: chartTextColor,
                                font: { size: 13, weight: '500' },
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: (context: any) => {
                                    const value = context.parsed;
                                    const percentage = ((value / props.userStats.total_users) * 100).toFixed(1);
                                    return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            };
        } else if (chartType.value === 'horizontal') {
            chartConfig = {
                type: 'bar',
                data: userHorizontalData.value,
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: (context: any) => {
                                    const value = context.parsed.x;
                                    const percentage = ((value / props.userStats.total_users) * 100).toFixed(1);
                                    return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: chartTextColor },
                            grid: { color: 'rgba(156, 163, 175, 0.1)' },
                        },
                        y: {
                            ticks: { color: chartTextColor },
                            grid: { display: false },
                        }
                    }
                }
            };
        } else if (chartType.value === 'stacked') {
            chartConfig = {
                type: 'bar',
                data: userStackedData.value,
                options: {
                    indexAxis: 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, ticks: { color: chartTextColor } },
                        y: { stacked: true, ticks: { color: chartTextColor }, grid: { color: 'rgba(156, 163, 175, 0.1)' } }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: chartTextColor,
                                font: { size: 13, weight: '500' },
                                padding: 20,
                                usePointStyle: true,
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: (context: any) => {
                                    const value = context.parsed.y;
                                    const percentage = ((value / props.userStats.total_users) * 100).toFixed(1);
                                    return `${context.dataset.label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            };
        }

        userDistributionChart = new Chart(chartContainer.value, chartConfig as any);
    };

    const changeChartType = (type: 'doughnut' | 'horizontal' | 'stacked') => {
        chartType.value = type;
        renderChart();
    };

    onMounted(() => {
        if (props.userStats.total_users > 0) {
            renderChart();
        }
    });

    watch(props.userStats, () => {
        if (props.userStats.total_users > 0) {
            renderChart();
        }
    }, { deep: true });

    watch(chartType, renderChart);


    onUnmounted(() => {
        if (userDistributionChart) {
            userDistributionChart.destroy();
        }
    });
</script>

<template>
    <div class="card-crypto p-4 md:p-6 space-y-6">
        <div v-if="userStats.total_users > 0" class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-card-foreground">User Status Distribution</h2>
                <p class="text-xs text-muted-foreground mt-1">Overview of active and suspended accounts</p>
            </div>
            <PieChart class="w-6 h-6 text-primary" />
        </div>

        <div v-if="userStats.total_users > 0" class="flex flex-wrap gap-2">
            <button
                @click="changeChartType('doughnut')"
                :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all cursor-pointer whitespace-nowrap', chartType === 'doughnut' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-secondary']">
                Doughnut
            </button>

            <button
                @click="changeChartType('horizontal')"
                :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all cursor-pointer whitespace-nowrap', chartType === 'horizontal' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-secondary']">
                Horizontal Bar
            </button>

            <button
                @click="changeChartType('stacked')"
                :class="['px-3 py-1.5 rounded-lg text-sm font-semibold transition-all cursor-pointer whitespace-nowrap', chartType === 'stacked' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-secondary']">
                Stacked Bar
            </button>
        </div>

        <div class="flex flex-col items-center justify-center max-h-80" :style="{ height: chartType === 'horizontal' ? '300px' : chartType === 'stacked' ? '350px' : '280px' }">
            <template v-if="userStats.total_users > 0">
                <canvas ref="chartContainer" class="max-h-full max-w-full"></canvas>
            </template>

            <template v-else>
                <div class="flex flex-col items-center justify-center text-center text-muted-foreground">
                    <PieChart class="h-10 w-10 sm:h-12 sm:w-12 mb-4 text-muted-foreground" />
                    <p class="text-base sm:text-lg font-medium mb-2 text-card-foreground">No User Data</p>
                    <p class="text-sm">The user database is currently empty.</p>
                </div>
            </template>
        </div>

        <div v-if="userStats.total_users > 0" class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t pt-4">
            <div class="flex flex-col items-center p-3 bg-secondary/90 rounded-lg">
                <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Total Users</span>
                <span class="text-2xl font-bold text-card-foreground mt-2">{{ userStats.total_users.toLocaleString() }}</span>
            </div>

            <div class="flex flex-col items-center p-3 bg-success/10 rounded-lg border border-success/20">
                <span class="text-xs font-semibold text-success uppercase tracking-wide">Active</span>
                <span class="text-2xl font-bold text-success mt-2">{{ userStats.total_active_users.toLocaleString() }}</span>
                <span class="text-xs text-muted-foreground mt-1">{{ getPercentage(userStats.total_active_users).toFixed(1) }}%</span>
            </div>

            <div class="flex flex-col items-center p-3 bg-destructive/10 rounded-lg border border-destructive/20">
                <span class="text-xs font-semibold text-destructive uppercase tracking-wide">Suspended</span>
                <span class="text-2xl font-bold text-destructive mt-2">{{ userStats.total_suspended_users.toLocaleString() }}</span>
                <span class="text-xs text-muted-foreground mt-1">{{ getPercentage(userStats.total_suspended_users).toFixed(1) }}%</span>
            </div>
        </div>
    </div>
</template>
