<script setup lang="ts">
    import { ZapIcon } from 'lucide-vue-next';

    type GasPreset = 'low' | 'medium' | 'high';

    defineProps<{
        gasPrices: Record<string, { gwei: number; time: string; usd: number }>;
        selectedPreset: GasPreset;
    }>();
    defineEmits(['update:preset']);
</script>

<template>
    <div class="bg-card border border-border rounded-2xl p-4 margin-bottom">
        <h3 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
            <ZapIcon class="w-4 h-4" />
            Gas Tracker
        </h3>
        <div class="space-y-2">
            <button
                v-for="(preset, key) in gasPrices"
                :key="key"
                @click="$emit('update:preset', key as GasPreset)"
                class="w-full flex items-center justify-between p-2 rounded-lg text-left"
                :class="[selectedPreset === key ? 'bg-primary/10' : 'bg-muted/50 hover:bg-muted']">
                <span class="text-xs text-muted-foreground capitalize">{{ key }}</span>
                <span class="text-xs font-semibold" :class="selectedPreset === key ? 'text-primary' : 'text-card-foreground'">{{ preset.gwei }} Gwei</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
