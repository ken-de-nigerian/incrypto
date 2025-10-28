<script setup lang="ts">
    import { computed } from 'vue';
    import { usePage, router } from '@inertiajs/vue3';
    import { formatDistanceToNow } from 'date-fns';
    import { Bell, XCircle, CheckCircle } from 'lucide-vue-next';

    const page = usePage();
    const notifications = computed(() => {
        const allNotifications = page.props.auth?.notifications || [];
        return allNotifications
            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
            .slice(0, 2);
    });

    const formatTimeAgo = (dateString: string) => {
        return formatDistanceToNow(new Date(dateString), { addSuffix: true });
    };

    const markAsRead = (notificationId: string) => {
        router.post(route('notifications.read', notificationId), {}, {
            preserveScroll: true,
        });
    };

    const deleteNotification = (notificationId: string) => {
        router.delete(route('notifications.destroy', notificationId), {
            preserveScroll: true,
        });
    };

    const deleteAllNotifications = () => {
        router.delete(route('notifications.destroyAll'), {
            preserveScroll: true,
        });
    };
</script>

<template>
    <div class="card-crypto p-4 sm:p-6 margin-bottom">
        <div v-if="notifications.length > 0" class="flex justify-between items-center mb-8">
            <h3 class="text-card-foreground font-semibold text-sm sm:text-base">Your Notifications</h3>
            <button @click="deleteAllNotifications" class="text-sm text-destructive hover:opacity-80 flex items-center gap-2 cursor-pointer transition-opacity">
                Clear All
            </button>
        </div>

        <div v-if="notifications.length > 0" class="flex flex-col gap-4">
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

                <div class="flex flex-col gap-2 p-4 bg-secondary/20 rounded-lg border border-border">
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

        <div v-else class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
            <Bell class="h-12 w-12 mb-4 text-muted-foreground" />
            <p class="text-lg font-medium mb-2 text-card-foreground">No New Notifications</p>
            <p class="text-sm">It looks like your inbox is empty. Check back later!</p>
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
