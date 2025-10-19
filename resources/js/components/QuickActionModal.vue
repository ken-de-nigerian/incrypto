<script setup lang="ts">
    import { XIcon } from 'lucide-vue-next';
    import { watch } from 'vue';

    const props = defineProps<{
        isOpen: boolean;
        title: string;
        subtitle?: string;
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
    }>();

    const handleBackdropClick = (event: MouseEvent) => {
        if (event.target === event.currentTarget) {
            emit('close');
        }
    };

    const handleClose = () => {
        emit('close');
    };

    // Disable body scroll when modal is open
    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">

            <div v-if="isOpen"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                 @click="handleBackdropClick">

                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-95 lg:scale-100 lg:opacity-100"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95 lg:scale-100 lg:opacity-100">

                    <div v-if="isOpen"
                         class="bg-card w-full h-full max-h-full lg:w-full lg:max-w-lg lg:h-auto lg:max-h-[90vh] flex flex-col rounded-none lg:rounded-2xl shadow-2xl overflow-hidden border-border relative lg:border" @click.stop>
                        <div class="p-6 border-b border-border flex-shrink-0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-card-foreground">{{ props.title }}</h3>
                                    <p v-if="props.subtitle" class="text-sm text-muted-foreground mt-1">{{ props.subtitle }}</p>
                                </div>

                                <button @click="handleClose" class="p-2 hover:bg-muted/70 rounded-lg transition-colors cursor-pointer">
                                    <XIcon class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>
                        </div>

                        <div class="p-6 space-y-6 overflow-y-auto flex-1 no-scrollbar">
                            <slot></slot>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
    /* Styling to hide the scrollbar while allowing scrolling */
    .no-scrollbar::-webkit-scrollbar {
        display: none; /* Hide scrollbar for Chrome, Safari and Opera */
    }

    .no-scrollbar {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>
