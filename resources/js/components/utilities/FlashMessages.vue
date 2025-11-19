<script setup lang="ts">
    import { watch } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import { useFlash } from '@/composables/useFlash';
    import CheckCircleIcon from '@/components/utilities/CheckCircleIcon.vue';
    import XCircleIcon from '@/components/utilities/XCircleIcon.vue';
    import AlertTriangleIcon from '@/components/utilities/AlertTriangleIcon.vue';
    import AlertCircleIcon from '@/components/utilities/AlertCircleIcon.vue';

    interface Notification {
        id: number;
        type: 'success' | 'error' | 'info' | 'warning';
        message: string;
        title?: string;
        duration?: number;
        dismissible?: boolean;
        action?: {
            label: string;
            callback: () => void;
        };
    }

    const page = usePage();
    const { notifications, removeNotification, notify } = useFlash();

    const getIconComponent = (type: string) => {
        switch (type) {
            case 'success': return CheckCircleIcon;
            case 'error': return XCircleIcon;
            case 'warning': return AlertTriangleIcon;
            default: return AlertCircleIcon;
        }
    };

    const getTypeClasses = (type: string) => {
        switch (type) {
            case 'success':
                return {
                    bg: 'bg-green-500/90',
                    border: 'border-green-400/50',
                    text: 'text-white',
                    icon: 'text-white',
                    iconBg: 'bg-white/20',
                };
            case 'error':
                return {
                    bg: 'bg-red-500/90',
                    border: 'border-red-400/50',
                    text: 'text-white',
                    icon: 'text-white',
                    iconBg: 'bg-white/20',
                };
            case 'warning':
                return {
                    bg: 'bg-yellow-500/90',
                    border: 'border-yellow-400/50',
                    text: 'text-white',
                    icon: 'text-white',
                    iconBg: 'bg-white/20',
                };
            default:
                return {
                    bg: 'bg-blue-500/90',
                    border: 'border-blue-400/50',
                    text: 'text-white',
                    icon: 'text-white',
                    iconBg: 'bg-white/20',
                };
        }
    };

    const handleAction = (notification: Notification) => {
        if (notification.action?.callback) {
            notification.action.callback();
        }
        removeNotification(notification.id);
    };

    // Watch flash messages from Inertia
    watch(() => page.props.flash, (flash) => {
        if (!flash) return;

        let type: 'success' | 'error' | 'info' | 'warning' = 'info';
        let message = '';

        if (flash.success) {
            type = 'success';
            message = flash.success;
        } else if (flash.error) {
            type = 'error';
            message = flash.error;
        } else if (flash.warning) {
            type = 'warning';
            message = flash.warning;
        } else if (flash.info) {
            type = 'info';
            message = flash.info;
        }

        if (message) {
            notify(type, message, {
                title: flash.title,
                duration: flash.duration ?? 5000,
                dismissible: flash.dismissible !== false,
            });
        }
    }, { deep: true, immediate: true });
</script>

<template>
    <TransitionGroup
        tag="div"
        class="fixed top-0 sm:top-4 left-0 right-0 z-[9999] flex flex-col gap-3 sm:left-auto sm:w-full sm:max-w-md sm:right-4"
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 translate-x-full"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition-all duration-300"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-full">
        <div
            v-for="notification in notifications"
            :key="notification.id"
            class="group pointer-events-auto">
            <div
                class="relative overflow-hidden rounded-none sm:rounded-lg border shadow-2xl backdrop-blur-md transition-all duration-300"
                :class="[
                    getTypeClasses(notification.type).bg,
                    getTypeClasses(notification.type).border,
                    getTypeClasses(notification.type).text,
                ]"
                role="alert"
                :aria-live="notification.type === 'error' ? 'assertive' : 'polite'">

                <div class="flex gap-4 p-4">
                    <div class="flex-shrink-0 pt-0.5">
                        <div
                            class="h-12 w-12 rounded-full flex items-center justify-center flex-shrink-0"
                            :class="getTypeClasses(notification.type).iconBg">
                            <component
                                :is="getIconComponent(notification.type)"
                                class="h-7 w-7 flex-shrink-0"
                                :class="getTypeClasses(notification.type).icon"
                                aria-hidden="true" />
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div
                            v-if="notification.title"
                            class="font-semibold text-sm mb-1">
                            {{ notification.title }}
                        </div>
                        <div
                            class="text-sm font-medium opacity-95"
                            v-html="notification.message"></div>
                    </div>

                    <div class="flex-shrink-0 flex items-center gap-2">
                        <button
                            v-if="notification.action"
                            @click="handleAction(notification)"
                            type="button"
                            class="px-3 py-1 text-xs font-semibold rounded-md bg-white/20 hover:bg-white/30 transition-colors duration-200 whitespace-nowrap cursor-pointer">
                            {{ notification.action.label }}
                        </button>

                        <button
                            v-if="notification.dismissible"
                            @click="removeNotification(notification.id)"
                            type="button"
                            class="inline-flex items-center justify-center p-1 rounded-md hover:bg-white/20 transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 cursor-pointer">
                            <span class="sr-only">Close notification</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TransitionGroup>
</template>
