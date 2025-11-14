<script setup lang="ts">
    import { computed } from 'vue';
    import { ArrowDown, ArrowUp, ArrowUpDownIcon, Loader2Icon, X } from 'lucide-vue-next';

    interface TradingPair {
        pair: string
        price: number
        change: number
        high: number
        low: number
        volume: string
    }

    interface TradeFormData {
        duration: string
        amount: number
        type: 'Up' | 'Down' | ''
        leverage: number
    }

    interface Props {
        selectedPair: TradingPair | null
        high: string
        low: string
        volume: string
        tradeFormData: TradeFormData
        durations: string[]
        availableMargin: number
        isExecutingTrade: boolean
        tradeError: string
        availableLeverages: number[]
    }

    const props = defineProps<Props>()

    const emit = defineEmits<{
        'update:duration': [duration: string]
        'update:amount': [amount: number]
        'update:type': [type: 'Up' | 'Down']
        'update:leverage': [leverage: number]
        'execute-trade': []
        'set-max-amount': []
        'clear-error': []
    }>()

    const isFormValid = computed(() => {
        return props.tradeFormData.amount > 0 &&
            props.tradeFormData.type !== '' &&
            props.tradeFormData.duration !== ''
    })

    const setTradeType = (type: 'Up' | 'Down') => {
        emit('update:type', type)
    }

    const setMaxAmount = () => {
        emit('set-max-amount')
    }

    const executeTrade = () => {
        if (isFormValid.value && !props.isExecutingTrade) {
            emit('execute-trade')
        }
    }

    const updateDuration = (duration: string) => {
        emit('update:duration', duration)
    }

    const updateAmount = (event: Event) => {
        const value = parseFloat((event.target as HTMLInputElement).value) || 0
        emit('update:amount', value)
    }

    const updateLeverage = (leverage: number) => {
        emit('update:leverage', leverage)
    }

    const clearError = () => {
        emit('clear-error')
    }
</script>

<template>
    <div class="hidden lg:block w-80 bg-muted/20 border-l border-border p-4 flex-shrink-0">
        <div v-if="!selectedPair" class="text-center text-muted-foreground text-sm py-4 h-full flex flex-col justify-center items-center">
            <div class="flex justify-center mb-3">
                <ArrowUpDownIcon class="h-10 w-10 text-muted-foreground" />
            </div>
            <p class="text-base font-medium mb-1 text-card-foreground">No Market Pairs Found</p>
            <p class="text-xs">Select a pair to start trading</p>
        </div>

        <div v-else class="space-y-4">
            <Transition name="fade">
                <div
                    v-if="tradeError"
                    class="p-3 rounded-lg flex items-start justify-between bg-destructive/10 border border-destructive/20">
                    <p class="text-xs font-semibold text-destructive flex-1">{{ tradeError }}</p>
                    <button @click="clearError" class="text-destructive hover:text-destructive-foreground ml-2 p-0.5 cursor-pointer">
                        <X class="w-3 h-3" />
                    </button>
                </div>
            </Transition>

            <div class="grid grid-cols-3 gap-2 text-xs bg-background p-3 rounded-lg border border-border">
                <div>
                    <p class="text-muted-foreground mb-1">High</p>
                    <p class="font-semibold text-card-foreground">{{ parseFloat(high).toFixed(2) }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground mb-1">Low</p>
                    <p class="font-semibold text-card-foreground">{{ parseFloat(low).toFixed(2) }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground mb-1">Vol</p>
                    <p class="font-semibold text-card-foreground">{{ parseFloat(volume).toFixed(2) }}</p>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold text-card-foreground">Duration</label>
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="duration in durations"
                        :key="duration"
                        @click="updateDuration(duration)"
                        :class="[
                            'py-1.5 rounded-lg font-bold text-xs transition cursor-pointer border',
                            tradeFormData.duration === duration
                                ? 'bg-accent text-accent-foreground border-accent'
                                : 'bg-muted/30 border-border text-card-foreground'
                        ]">
                        {{ duration }}
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-card-foreground">Amount (USD)</label>
                <div class="relative">
                    <input
                        :value="tradeFormData.amount"
                        @input="updateAmount"
                        type="number"
                        step="0.01"
                        min="0"
                        :max="availableMargin"
                        placeholder="Enter amount"
                        class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm input-crypto"
                    />
                    <button
                        @click="setMaxAmount"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-primary text-xs font-bold px-2 py-1 bg-primary/10 rounded cursor-pointer">
                        MAX
                    </button>
                </div>
                <p class="text-[10px] text-muted-foreground">Available Margin: ${{ availableMargin.toFixed(2) }}</p>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold text-card-foreground">Leverage</label>
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="leverage in availableLeverages"
                        :key="leverage"
                        @click="updateLeverage(leverage)"
                        :class="[
                            'py-1.5 rounded-lg font-bold text-[10px] transition cursor-pointer border flex items-center justify-center overflow-hidden text-ellipsis whitespace-nowrap',
                            tradeFormData.leverage === leverage
                                ? 'bg-accent text-accent-foreground border-accent'
                                : 'bg-muted/30 border-border text-card-foreground'
                        ]">
                        {{ leverage }}x
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button
                    @click="setTradeType('Up')"
                    :class="[
                        'py-3 rounded-lg font-semibold text-sm transition cursor-pointer border flex items-center justify-center gap-2',
                        tradeFormData.type === 'Up'
                            ? 'bg-emerald-500 text-white border-emerald-600'
                            : 'bg-background border-border text-card-foreground hover:border-emerald-500/50'
                    ]">
                    <ArrowUp class="w-4 h-4" />
                    Up
                </button>
                <button
                    @click="setTradeType('Down')"
                    :class="[
                        'py-3 rounded-lg font-semibold text-sm transition cursor-pointer border flex items-center justify-center gap-2',
                        tradeFormData.type === 'Down'
                            ? 'bg-rose-500 text-white border-rose-600'
                            : 'bg-background border-border text-card-foreground hover:border-rose-500/50'
                    ]">
                    <ArrowDown class="w-4 h-4" />
                    Down
                </button>
            </div>

            <button
                :disabled="isExecutingTrade || !isFormValid"
                @click="executeTrade"
                :class="[
                    'w-full py-3 font-bold rounded-lg transition text-sm flex items-center justify-center gap-2 cursor-pointer',
                    isExecutingTrade || !isFormValid
                        ? 'bg-muted text-muted-foreground cursor-not-allowed'
                        : tradeFormData.type === 'Up'
                        ? 'bg-emerald-500 hover:bg-emerald-600 text-white'
                        : 'bg-rose-500 hover:bg-rose-600 text-white'
                ]">
                <Loader2Icon v-if="isExecutingTrade" class="w-4 h-4 animate-spin" />
                <span>{{ isExecutingTrade ? 'Executing...' : `Execute ${tradeFormData.type || 'Trade'}` }}</span>
            </button>

            <div class="mt-3 pt-3 border-t border-border/50">
                <p class="text-[10px] text-muted-foreground leading-relaxed">
                    <span class="font-semibold">Note:</span> Trades execute at current market price and expire after the selected duration. Leverage amplifies both gains and losses trade responsibly.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .fade-enter-active, .fade-leave-active {
        transition: opacity 0.3s ease;
    }
    .fade-enter-from, .fade-leave-to {
        opacity: 0;
    }

    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type="number"] { -moz-appearance: textfield; }
    input[type="range"] { accent-color: hsl(var(--primary)); }
</style>
