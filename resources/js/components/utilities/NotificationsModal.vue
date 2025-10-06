<script setup lang="ts">
    import { computed } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import { formatDistanceToNow } from 'date-fns';
    import { Bell, CheckCircle, XCircle } from 'lucide-vue-next';

    // Define props
    defineProps<{
        isOpen: boolean;
    }>();

    // Define emits
    const emit = defineEmits<{
        close: [];
    }>();

    const page = usePage();
    const notifications = computed(() => page.props.auth?.notifications || []);

    const formatTimeAgo = (dateString: string) => {
        return formatDistanceToNow(new Date(dateString), { addSuffix: true });
    };

    const markAsRead = (notificationId: string) => {
        router.post(route('user.notifications.read', notificationId), {}, {
            preserveScroll: true,
        });
    };

    const deleteNotification = (notificationId: string) => {
        router.delete(route('user.notifications.destroy', notificationId), {
            preserveScroll: true,
        });
    };

    const deleteAllNotifications = () => {
        router.delete(route('user.notifications.destroyAll'), {
            preserveScroll: true,
        });
    };

    const handleClose = () => {
        emit('close');
    };

    const handleBackdropClick = (event: MouseEvent) => {
        if (event.target === event.currentTarget) {
            handleClose();
        }
    };
</script>

<template>
    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click="handleBackdropClick">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="isOpen" class="bg-zinc-900 p-6 text-white w-[80%] max-w-md rounded-lg shadow-lg overflow-y-auto max-h-[80vh] no-scrollbar border border-zinc-800" @click.stop>
                    <h3 class="text-xl font-semibold mb-4 text-center">Your Notifications</h3>
                    <button v-if="notifications.length > 0" @click="deleteAllNotifications" class="text-sm mb-4 text-red-400 hover:text-red-300 flex items-center gap-2 cursor-pointer">
                        Clear All
                    </button>

                    <div v-if="notifications.length > 0" class="flex flex-col gap-4">
                        <div v-for="notification in notifications" :key="notification.id" class="flex flex-col gap-2">
                            <div class="flex justify-between items-center">
                                <span class="text-zinc-400 text-xs font-light">
                                    {{ formatTimeAgo(notification.created_at) }}
                                </span>

                                <div class="flex gap-2">
                                    <button v-if="!notification.read_at" @click="markAsRead(notification.id)" class="text-zinc-400 hover:text-zinc-300 cursor-pointer" title="Mark as read">
                                        <CheckCircle class="h-4 w-4" />
                                    </button>

                                    <button @click="deleteNotification(notification.id)" class="text-red-400 hover:text-red-300 cursor-pointer" title="Delete notification">
                                        <XCircle class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 p-4 bg-zinc-800 rounded-lg border border-zinc-700">
                                <h4 class="flex items-center gap-2 text-xs font-medium">
                                    <span v-if="!notification.read_at" class="w-2 h-2 bg-lime-400 rounded-full"></span>
                                    {{ notification.data.title }}
                                </h4>

                                <p class="text-xs font-light text-zinc-300">
                                    {{ notification.data.content }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-8 text-center text-zinc-400">
                        <Bell class="h-12 w-12 mb-4 text-zinc-500" />
                        <p class="text-lg font-medium mb-2 text-white">No New Notifications</p>
                        <p class="text-sm">It looks like your inbox is empty. Check back later!</p>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
