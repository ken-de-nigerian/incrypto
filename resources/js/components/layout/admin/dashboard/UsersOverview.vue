<script setup lang="ts">
    import { defineProps } from 'vue';
    import { Users, Circle, Eye } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    interface RecentUser {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        status: string;
        registered_at: string;
        profile?: {
            profile_photo_path: string | null;
        } | null;
    }

    // Define the component props
    const props = defineProps<{
        users: RecentUser[];
    }>();

    const getStatusClass = (userStatus: RecentUser['status']) => {
        switch (userStatus) {
            case 'active':
                return 'bg-success/20 border border-success/30 text-success';
            default:
                return 'bg-destructive/20 border border-destructive/30 text-destructive';
        }
    };
</script>

<template>
    <div class="card-crypto p-4 md:p-6 space-y-4">
        <template v-if="users.length > 0">
            <h2 class="text-lg sm:text-xl font-semibold text-card-foreground mb-4">Users Overview</h2>
            <p class="text-xs sm:text-sm text-muted-foreground">Detailed list of recent user registrations and their status.</p>
        </template>

        <template v-if="users.length > 0">
            <div
                v-for="user in props.users"
                :key="user.id"
                class="p-3 bg-muted/50 border border-border rounded-lg hover:bg-muted/70 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-secondary/70 overflow-hidden flex items-center justify-center border border-border flex-shrink-0">
                        <img v-if="user.profile?.profile_photo_path" :src="user.profile?.profile_photo_path" :alt="user.first_name" loading="lazy" class="h-full w-full object-cover">
                        <span v-else class="text-base sm:text-lg font-bold text-card-foreground">
                        {{ (user.first_name?.charAt(0) ?? '').toUpperCase() }}{{ (user.last_name?.charAt(0) ?? '').toUpperCase() }}
                    </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h6 class="font-semibold text-card-foreground truncate text-sm sm:text-base">{{ user.first_name }} {{ user.last_name }}</h6>
                        <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>

                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <span class="inline-flex items-center space-x-1 text-xs font-semibold rounded-full px-2 py-0.5" :class="getStatusClass(user.status)">
                                <Circle class="w-1.5 h-1.5 fill-current" />
                                <span>{{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}</span>
                            </span>

                            <span class="text-xs text-muted-foreground whitespace-nowrap">{{ user.registered_at }}</span>
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                        <TextLink
                            :href="route('admin.users.show', user.id)"
                            class="flex items-center justify-center w-8 h-8 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg transition-colors shadow-sm">
                            <Eye class="w-4 h-4" />
                        </TextLink>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
                <Users class="h-10 w-10 sm:h-12 sm:w-12 mb-4 text-muted-foreground" />
                <p class="text-base sm:text-lg font-medium mb-2 text-card-foreground">No New Registrations Yet</p>
                <p class="text-sm">We're all caught up! New user registrations will appear here.</p>
            </div>
        </template>
    </div>
</template>
