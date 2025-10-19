<script setup lang="ts">
    import { ref, watch } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import CheckCircleIcon from '@/components/utilities/CheckCircleIcon.vue';
    import XCircleIcon from '@/components/utilities/XCircleIcon.vue';
    import InformationCircleIcon from '@/components/utilities/InformationCircleIcon.vue';

    const notifications = ref([])
    const page = usePage()
    let nextId = 1;

    const getIconComponent = (type: string) => {
        switch (type) {
            case 'success': return CheckCircleIcon;
            case 'error': return XCircleIcon;
            default: return InformationCircleIcon;
        }
    };

    const getTypeClasses = (type: string) => {
        switch (type) {
            case 'success':
                return 'bg-success/80 border-success/50 text-success-foreground';
            case 'error':
                return 'bg-destructive/80 border-destructive/50 text-destructive-foreground';
            default:
                return 'bg-muted/80 border-border/50 text-foreground';
        }
    };

    const removeNotification = (id: number) => {
        notifications.value = notifications.value.filter(n => n.id !== id);
    }
    watch(() => page.props.flash, (flash) => {
        if (!flash) return;
        let type = 'info';
        let message = '';
        if (flash.success) {
            type = 'success';
            message = flash.success;
        } else if (flash.error) {
            type = 'error';
            message = flash.error;
        } else if (flash.info) {
            type = 'info';
            message = flash.info
        }
        if (message) {
            const id = nextId++;
            notifications.value.push({ id, type, message });
            setTimeout(() => removeNotification(id), 5000);
        }
    }, { deep: true, immediate: true });
</script>

<template>
    <TransitionGroup
        tag="div"
        class="fixed top-4 left-4 right-4 z-50 space-y-3 sm:left-auto sm:w-full sm:max-w-sm sm:right-5"
        enter-active-class="animate-[flip-in-x_0.5s_ease-in-out]"
        leave-active-class="animate-[flip-out-x_0.5s_ease-in-out]">
        <div
            v-for="notification in notifications"
            :key="notification.id"
            class="pointer-events-auto"
            style="perspective: 1000px; transform-style: preserve-3d;">
            <div
                class="group relative py-2 flex min-h-12 w-full items-center justify-between gap-4 overflow-hidden rounded-xl pr-2 pl-3 shadow-xl backdrop-blur-md"
                :class="getTypeClasses(notification.type)">
                <div class="relative flex flex-1 items-center gap-3">
                    <div class="relative z-20 h-6 shrink-0">
                        <component :is="getIconComponent(notification.type)" />
                    </div>
                    <div class="text-sm font-medium" v-html="notification.message"></div>
                </div>

                <button
                    @click="removeNotification(notification.id)"
                    type="button"
                    class="relative isolate inline-flex items-center justify-center rounded-md p-1 font-medium whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 cursor-pointer">
                    <span class="sr-only">Close</span>
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="absolute bottom-0 left-0 w-full h-1 overflow-hidden rounded-b-xl">
                    <div class="absolute bottom-0 left-0 h-full bg-white/50 animate-[progress-bar-timer_5s_linear_forwards] group-hover:animate-pause"></div>
                </div>
            </div>
        </div>
    </TransitionGroup>
</template>
