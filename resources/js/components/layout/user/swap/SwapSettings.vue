<script setup lang="ts">
    import { ref } from 'vue';

    defineProps<{
        slippage: number;
        deadline: number;
        gasPreset: 'low' | 'medium' | 'high';
        gasPrices: Record<string, { gwei: number; time: string; usd: number }>;
    }>();

    const emit = defineEmits(['update:slippage', 'update:deadline', 'update:gasPreset']);

    const customSlippage = ref('');

    const setSlippage = (value: number) => {
        emit('update:slippage', value);
        customSlippage.value = '';
    };

    const setCustomSlippage = () => {
        const value = parseFloat(customSlippage.value);
        if (!isNaN(value) && value >= 0 && value <= 10) {
            emit('update:slippage', value);
        }
    };
</script>

<template>
    <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-muted/50 rounded-xl border border-border space-y-4">
        <div>
            <label class="text-xs font-semibold text-card-foreground mb-2 block">Slippage Tolerance</label>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="s in [0.1, 0.5, 1]"
                    :key="s"
                    @click="setSlippage(s)"
                    :class="[
            'px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg text-xs sm:text-sm font-medium',
            slippage === s ? 'bg-primary text-primary-foreground' : 'bg-background text-muted-foreground hover:bg-muted',
          ]">
                    {{ s }}%
                </button>
                <input
                    v-model="customSlippage"
                    @blur="setCustomSlippage"
                    type="number"
                    step="0.1"
                    placeholder="Custom"
                    class="flex-1 px-2 sm:px-3 py-1 sm:py-1.5 bg-background border border-border rounded-lg text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />
            </div>
        </div>
        <div>
            <label class="text-xs font-semibold text-card-foreground mb-2 block">Transaction Deadline</label>
            <div class="flex items-center gap-2">
                <input
                    :value="deadline"
                    @input="$emit('update:deadline', Number($event.target.value))"
                    type="number"
                    min="1"
                    max="60"
                    class="flex-1 px-2 sm:px-3 py-1 sm:py-1.5 bg-background border border-border rounded-lg text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />
                <span class="text-xs sm:text-sm text-muted-foreground">minutes</span>
            </div>
        </div>

        <div>
            <label class="text-xs font-semibold text-card-foreground mb-2 block">Gas Price</label>
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="preset in ['low', 'medium', 'high']"
                    :key="preset"
                    @click="emit('update:gasPreset', preset)"
                    :class="[
                        'px-2 sm:px-3 py-1 sm:py-2 rounded-lg text-xs sm:text-sm font-medium',
                        gasPreset === preset ? 'bg-primary text-primary-foreground' : 'bg-background text-muted-foreground hover:bg-muted',
                    ]">
                    <div class="capitalize">{{ preset }}</div>
                    <div class="text-xs opacity-70">{{ gasPrices[preset].time }}</div>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
    @media (max-width: 320px) {
        .grid-cols-3 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .text-xs {
            font-size: 0.7rem;
        }
    }
</style>
