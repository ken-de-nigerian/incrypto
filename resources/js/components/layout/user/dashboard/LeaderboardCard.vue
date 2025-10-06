<template>
    <div class="card-crypto p-4 sm:p-6">
        <h3 class="text-card-foreground font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Leaderboard</h3>
        <div class="space-y-2 sm:space-y-3">
            <div
                v-for="leader in leaders"
                :key="leader.rank"
                class="flex items-center justify-between"
            >
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 bg-primary rounded-full flex items-center justify-center text-primary-foreground font-bold text-xs sm:text-sm flex-shrink-0">
                        {{ leader.rank }}
                    </div>
                    <span class="text-card-foreground font-medium text-sm sm:text-base truncate">{{ leader.name }}</span>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0 ml-2">
                    <span class="text-muted-foreground text-xs sm:text-sm hidden sm:inline">Wallet Increase</span>
                    <span class="badge-positive text-xs">
                        {{ leader.increase }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    export default {
        name: 'LeaderboardCard',
        data() {
            return {
                leaders: [
                    { name: 'Merkulove', increase: 84.6, rank: 1 },
                    { name: 'CfTrader', increase: 35.9, rank: 2 },
                    { name: 'Singh', increase: 28.8, rank: 3 },
                    { name: 'Cryptodeter', increase: 28.3, rank: 4 },
                    { name: 'Codecrypto', increase: 28.2, rank: 5 },
                    { name: 'Bitcoinbutler', increase: 23.3, rank: 6 },
                    { name: 'Coinunlike', increase: 19.6, rank: 7 },
                    { name: 'Coinfizzle', increase: 16.3, rank: 8 },
                ],
            };
        },
    };
</script>
