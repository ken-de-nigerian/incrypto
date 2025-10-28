<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { ZapIcon, DollarSignIcon } from 'lucide-vue-next';

    interface Props {
        isLiveMode: boolean;
        liveBalance: number;
        demoBalance: number;
        isOrderProcessing?: boolean;
    }

    interface Emits {
        (e: 'update:isLiveMode', value: boolean): void;
        (e: 'mode-changed', mode: 'live' | 'demo'): void;
        (e: 'show-alert', message: string, type: 'success' | 'error'): void;
    }

    const props = withDefaults(defineProps<Props>(), {
        isOrderProcessing: false
    });

    const emit = defineEmits<Emits>();

    const isProcessing = ref(false);

    const updateTradingMode = (mode: 'live' | 'demo') => {
        if (props.isLiveMode === (mode === 'live')) return;

        isProcessing.value = true;
        router.put(route('user.profile.update.trading.status'), { status: mode }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                emit('update:isLiveMode', mode === 'live');
                emit('mode-changed', mode);
                emit('show-alert', `Successfully switched to ${mode === 'live' ? 'Live Trading' : 'Demo Mode'}.`, 'success');
                isProcessing.value = false;
            },
            onError: (errors) => {
                console.error("Failed to change trading mode:", errors);
                emit('show-alert', errors?.status || "Failed to change trading mode.", 'error');
                isProcessing.value = false;
            }
        });
    };

    const isDisabled = computed(() => props.isOrderProcessing || isProcessing.value);
</script>

<template>
    <div class="relative inline-flex rounded-xl p-1 shadow-inner w-full sm:w-auto bg-primary/20">
        <button
            @click="updateTradingMode('live')"
            :disabled="isDisabled"
            :class="['flex-1 sm:flex-none px-3 py-1.5 text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-1 cursor-pointer', isLiveMode ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted/70', isDisabled ? 'opacity-70' : '']">
            <ZapIcon class="w-4 h-4" />
            Live
        </button>

        <button
            @click="updateTradingMode('demo')"
            :disabled="isDisabled"
            :class="['flex-1 sm:flex-none px-3 py-1.5 text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-1 cursor-pointer', !isLiveMode ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted/70', isDisabled ? 'opacity-70' : '']">
            <DollarSignIcon class="w-4 h-4" />
            Demo
        </button>
    </div>
</template>

<style scoped>
    button:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }
</style>
