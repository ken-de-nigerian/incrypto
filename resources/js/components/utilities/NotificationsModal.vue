<script setup lang="ts">
    import { computed, defineProps, watch } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import { formatDistanceToNow } from 'date-fns';
    import { Bell, CheckCircle, XCircle, X } from 'lucide-vue-next';

    // Define props
    const props = defineProps<{
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
    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isOpen" class="fixed inset-0 z-[100] flex lg:items-start lg:justify-end bg-black/50 backdrop-blur-sm" @click="handleBackdropClick">
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 scale-95 lg:translate-x-full lg:opacity-100 lg:scale-100"
                enter-to-class="opacity-100 scale-100 lg:translate-x-0"
                leave-active-class="transition-all duration-300"
                leave-from-class="opacity-100 scale-100 lg:translate-x-0"
                leave-to-class="opacity-0 scale-95 lg:translate-x-full lg:opacity-100 lg:scale-100">
                <div v-if="isOpen" class="bg-card text-card-foreground w-full h-full lg:w-[400px] lg:max-w-md lg:h-screen flex flex-col rounded-none lg:rounded-l-lg shadow-2xl overflow-hidden border-l border-border relative">
                    <div class="sticky top-0 bg-card z-10 px-5 pt-6 pb-4 border-b border-border">
                        <div class="flex items-start justify-between mb-1">
                            <div class="flex-1">
                                <h3 class="text-xl font-semibold text-card-foreground">Notifications</h3>
                                <p class="text-sm text-muted-foreground mt-1">Stay updated with your latest activities</p>
                            </div>
                            <button @click="handleClose" class="p-2 hover:bg-muted rounded-lg flex-shrink-0 cursor-pointer" title="Close">
                                <X class="h-5 w-5 text-muted-foreground" />
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 flex flex-col overflow-y-auto no-scrollbar">
                        <div class="p-6 flex-1 flex flex-col">
                            <button v-if="notifications.length > 0" @click="deleteAllNotifications" class="text-sm mb-4 text-destructive hover:opacity-80 flex items-center gap-2 cursor-pointer transition-opacity self-end">
                                Clear All
                            </button>

                            <div v-if="notifications.length > 0" class="flex flex-col gap-4 flex-1">
                                <div v-for="notification in notifications" :key="notification.id" class="flex flex-col gap-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-muted-foreground text-xs font-light">
                                            {{ formatTimeAgo(notification.created_at) }}
                                        </span>

                                        <div class="flex gap-2">
                                            <button v-if="!notification.read_at" @click="markAsRead(notification.id)" class="text-muted-foreground hover:text-card-foreground cursor-pointer" title="Mark as read">
                                                <CheckCircle class="h-4 w-4" />
                                            </button>

                                            <button @click="deleteNotification(notification.id)" class="text-destructive hover:opacity-80 cursor-pointer transition-opacity" title="Delete notification">
                                                <XCircle class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2 p-4 bg-secondary rounded-lg border border-border">
                                        <h4 class="flex items-center gap-2 text-xs font-medium text-card-foreground">
                                            <span v-if="!notification.read_at" class="w-2 h-2 bg-primary rounded-full"></span>
                                            {{ notification.data.title }}
                                        </h4>

                                        <p class="text-xs font-light text-muted-foreground">
                                            {{ notification.data.content }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground flex-1">
                                <Bell class="h-12 w-12 mb-4 text-muted-foreground" />
                                <p class="text-lg font-medium mb-2 text-card-foreground">No New Notifications</p>
                                <p class="text-sm">It looks like your inbox is empty. Check back later!</p>
                            </div>
                        </div>
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
