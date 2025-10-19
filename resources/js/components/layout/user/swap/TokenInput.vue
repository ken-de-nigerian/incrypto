<script setup lang="ts">
    import { ChevronDownIcon } from 'lucide-vue-next';

    defineProps<{
        label: string;
        token: { symbol: string; logo: string } | null;
        balance: number;
        amount: string;
        price: number;
        readonly?: boolean;
    }>();

    // FIX: Assigned the result of defineEmits to the 'emit' constant
    const emit = defineEmits(['update:amount', 'open-modal', 'set-max']);

    // Function to format the token symbol
    const formatSymbol = (symbol: string): string => {
        if (!symbol) return '';

        // Regex to find USDT_ followed by BEP20, ERC20, or TRC20 (case-insensitive)
        const formatted = symbol.replace(/USDT_(BEP20|ERC20|TRC20)/i, (match) => {
            // Replace the underscore with a space only in the matched segment
            return match.replace('_', ' ');
        });

        return formatted.toUpperCase();
    };
</script>

<template>
    <div class="bg-muted/50 rounded-xl p-3 sm:p-4 border border-border">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-muted-foreground">{{ label }}</span>
            <span class="text-xs text-muted-foreground">Balance: {{ balance.toFixed(4) }} {{ formatSymbol(token?.symbol) }}</span>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <button
                @click="emit('open-modal')"
                class="flex items-center gap-2 px-2 sm:px-3 py-1 sm:py-2 bg-background border border-border rounded-lg hover:bg-muted/70 cursor-pointer">
                <img v-if="token" :src="token.logo" :alt="token.symbol" class="w-6 h-6 rounded-full" />
                <span class="font-semibold text-card-foreground text-xs sm:text-sm">{{ formatSymbol(token?.symbol) || 'Select' }}</span>
                <ChevronDownIcon class="w-4 h-4 text-muted-foreground" />
            </button>

            <div class="flex-1 flex flex-col items-end">
                <input
                    :value="amount"
                    :readonly="readonly"
                    type="text"
                    step="any"
                    placeholder="0.0"
                    class="w-full text-right text-xl sm:text-2xl font-semibold bg-transparent border-none outline-none text-card-foreground"
                    @input="emit('update:amount', ($event.target as HTMLInputElement).value)"
                />
                <span class="text-xs text-muted-foreground mt-1">
                  ≈ ${{ (parseFloat(amount || '0') * price).toFixed(2) }}
                </span>
            </div>
        </div>

        <button
            v-if="label === 'From' && token"
            @click="emit('set-max')"
            class="mt-2 px-2 py-1 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-semibold rounded">
            MAX
        </button>
    </div>
</template>

<style scoped>
    /* Ensure input fits on small screens */
    @media (max-width: 320px) {
        .text-xl {
            font-size: 1rem;
        }
        .text-xs {
            font-size: 0.7rem;
        }
    }
</style>
