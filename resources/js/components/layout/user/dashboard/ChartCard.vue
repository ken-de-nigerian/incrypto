<template>
    <div class="card-crypto p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row items-start justify-between mb-4 sm:mb-6 gap-3 sm:gap-0">
            <div>
                <h2 class="text-card-foreground text-base sm:text-lg font-semibold mb-1">BTCUSDT</h2>
                <p class="text-2xl sm:text-3xl font-bold text-card-foreground mb-2">22,228.00</p>
                <div class="flex items-center gap-2">
                    <span class="badge-positive text-xs sm:text-sm">1.76%</span>
                </div>
            </div>
            <div class="text-left sm:text-right text-xs sm:text-sm w-full sm:w-auto">
                <div class="flex justify-between sm:justify-end gap-3 sm:gap-4 text-muted-foreground mb-1">
                    <span>24 High</span>
                    <span class="text-card-foreground">22,391.00</span>
                </div>
                <div class="flex justify-between sm:justify-end gap-3 sm:gap-4 text-muted-foreground">
                    <span>24 Low</span>
                    <span class="text-card-foreground">21,439.40</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-2 mb-4 overflow-x-auto pb-2 scrollbar-hide">
            <button class="p-1.5 sm:p-2 bg-secondary rounded-lg hover:bg-muted transition-colors flex-shrink-0">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 text-secondary-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                    <polyline points="16 7 22 7 22 13"></polyline>
                </svg>
            </button>
            <button
                v-for="tf in timeframes"
                :key="tf"
                @click="selectedTimeframe = tf"
                :class="['px-2 sm:px-3 py-1 rounded-lg text-xs sm:text-sm transition-colors flex-shrink-0',
                         selectedTimeframe === tf ? 'bg-primary text-primary-foreground font-semibold' : 'bg-secondary text-secondary-foreground hover:bg-muted']"
            >
                {{ tf }}
            </button>
            <button class="px-2 sm:px-3 py-1 bg-secondary rounded-lg text-xs sm:text-sm text-secondary-foreground hover:bg-muted transition-colors flex-shrink-0">
                more ▼
            </button>
        </div>

        <div class="h-48 sm:h-56 md:h-64 bg-secondary rounded-lg sm:rounded-xl mb-4 relative overflow-hidden">
            <div class="absolute inset-0 flex items-end justify-around px-2 sm:px-4 pb-6 sm:pb-8">
                <div
                    v-for="(bar, idx) in chartBars"
                    :key="idx"
                    class="flex flex-col items-center justify-end"
                    style="width: 4%"
                >
                    <div
                        :class="['w-full rounded-sm transition-all', bar.color]"
                        :style="{ height: bar.height + '%' }"
                    ></div>
                </div>
            </div>

            <div class="absolute bottom-0 left-0 right-0 h-10 sm:h-12 flex items-end justify-around px-2 sm:px-4 pb-2">
                <div
                    v-for="(vol, idx) in volumeBars"
                    :key="'vol-' + idx"
                    :class="['w-1 bg-crypto-positive/30 rounded-sm']"
                    :style="{ height: vol + 'px' }"
                ></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:gap-3">
            <button class="bg-secondary text-secondary-foreground py-2.5 sm:py-3 rounded-lg sm:rounded-xl text-sm sm:text-base font-semibold hover:bg-muted transition-colors">
                Sell
            </button>
            <button class="btn-crypto py-2.5 sm:py-3 text-sm sm:text-base">
                Buy
            </button>
        </div>
    </div>
</template>

<script lang="ts">
export default {
    name: 'ChartCard',
    data() {
        return {
            selectedTimeframe: '1D',
            timeframes: ['15m', '1h', '4h', '12h', '1D'],
            chartBars: [
                { height: 40, color: 'bg-destructive' },
                { height: 60, color: 'bg-crypto-positive' },
                { height: 45, color: 'bg-destructive' },
                { height: 70, color: 'bg-crypto-positive' },
                { height: 55, color: 'bg-destructive' },
                { height: 80, color: 'bg-crypto-positive' },
                { height: 65, color: 'bg-crypto-positive' },
                { height: 90, color: 'bg-crypto-positive' },
                { height: 75, color: 'bg-destructive' },
                { height: 85, color: 'bg-crypto-positive' },
                { height: 70, color: 'bg-destructive' },
                { height: 95, color: 'bg-crypto-positive' },
                { height: 80, color: 'bg-destructive' },
                { height: 100, color: 'bg-crypto-positive' },
                { height: 85, color: 'bg-crypto-positive' },
                { height: 92, color: 'bg-crypto-positive' },
                { height: 88, color: 'bg-destructive' },
                { height: 96, color: 'bg-crypto-positive' },
                { height: 90, color: 'bg-destructive' },
                { height: 94, color: 'bg-crypto-positive' },
            ],
            volumeBars: [16, 24, 32, 20, 28, 36, 24, 32, 40, 28, 20, 24, 28, 32, 24, 28, 20, 24, 28, 32],
        };
    },
};
</script>

<style scoped>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
