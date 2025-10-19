<script setup lang="ts">
    import { TrendingUp } from 'lucide-vue-next';

    const userGrowthData = {
        totalUsers: '15,450',
        newUsersMonth: '+1,200',
        growthRate: '8.4%',
    };
</script>

<template>
    <div class="card-crypto p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-card-foreground">User Growth</h2>
            <TrendingUp class="w-6 h-6 text-primary" />
        </div>

        <div class="flex items-end justify-between">
            <div>
                <p class="text-3xl font-bold text-card-foreground">{{ userGrowthData.totalUsers }}</p>
                <p class="text-sm text-muted-foreground mt-1">Total Registered Users</p>
            </div>
            <div class="text-right">
                <p class="text-lg font-semibold text-success">{{ userGrowthData.newUsersMonth }}</p>
                <p class="text-xs text-muted-foreground">New users this month</p>
            </div>
        </div>

        <div class="h-40 bg-muted/20 rounded-lg flex items-center justify-center text-muted-foreground border border-dashed border-border">
            [ Placeholder: Area Chart of Weekly Sign-ups ]
        </div>

        <p class="text-sm text-center pt-2">
            <span class="font-semibold text-success">{{ userGrowthData.growthRate }}</span>
            growth from last month.
        </p>
    </div>
</template>
