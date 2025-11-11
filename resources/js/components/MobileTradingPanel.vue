<script setup lang="ts">
    import { computed } from 'vue';
    import { ArrowUp, ArrowDown, Loader2Icon, X } from 'lucide-vue-next';

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
            props.tradeFormData.duration !== '' &&
            props.tradeFormData.leverage > 0
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
    <div v-if="selectedPair" class="lg:hidden bg-card border border-border rounded-b-2xl margin-bottom">
        <div class="p-3 space-y-3 max-h-[70vh] overflow-y-auto">
            <Transition name="fade">
                <div
                    v-if="tradeError"
                    class="p-2.5 rounded-lg flex items-start justify-between bg-destructive/10 border border-destructive/20">
                    <p class="text-xs font-semibold text-destructive flex-1">{{ tradeError }}</p>
                    <button @click="clearError" class="text-destructive hover:text-destructive-foreground ml-2 p-0.5 cursor-pointer">
                        <X class="w-3 h-3" />
                    </button>
                </div>
            </Transition>

            <div class="grid grid-cols-3 gap-2 text-xs bg-muted/90 p-2.5 rounded-lg">
                <div class="text-center">
                    <p class="text-muted-foreground text-[10px] mb-0.5">High</p>
                    <p class="font-bold text-card-foreground">{{ parseFloat(high).toFixed(3) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-muted-foreground text-[10px] mb-0.5">Low</p>
                    <p class="font-bold text-card-foreground">{{ parseFloat(low).toFixed(3) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-muted-foreground text-[10px] mb-0.5">Vol</p>
                    <p class="font-bold text-card-foreground">{{ parseFloat(volume).toFixed(3) }}</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-card-foreground">Duration</label>
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="duration in durations"
                        :key="duration"
                        @click="updateDuration(duration)"
                        :class="[
                            'py-2 rounded-lg font-bold text-xs transition cursor-pointer border',
                            tradeFormData.duration === duration
                                ? 'bg-accent text-accent-foreground border-accent'
                                : 'bg-muted/30 border-border text-card-foreground'
                        ]">
                        {{ duration }}
                    </button>
                </div>
            </div>

            <div class="space-y-1.5">
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
                        class="w-full px-3 py-2.5 bg-muted/30 border border-border rounded-lg text-sm input-crypto"
                    />
                    <button
                        @click="setMaxAmount"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-primary text-xs font-bold px-2 py-1 bg-primary/10 rounded cursor-pointer">
                        MAX
                    </button>
                </div>
                <p class="text-[10px] text-muted-foreground">Available Margin: ${{ availableMargin.toFixed(2) }}</p>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-card-foreground">Leverage</label>
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="leverage in availableLeverages"
                        :key="leverage"
                        @click="updateLeverage(leverage)"
                        :class="[
                            'py-2 rounded-lg font-bold text-xs transition cursor-pointer border',
                            tradeFormData.leverage === leverage
                                ? 'bg-accent text-accent-foreground border-accent'
                                : 'bg-muted/30 border-border text-card-foreground'
                        ]">
                        {{ leverage }}x
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
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

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 150px;
        }
    }
</style>
