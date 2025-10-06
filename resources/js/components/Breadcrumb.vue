<script setup lang="ts">
    import { computed } from 'vue';
    import { ChevronRight, Search, Bell } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    const props = defineProps({
        items: {
            type: Array,
            required: true,
        },
        user: {
            type: Object,
            required: true
        },
        notificationCount: {
            type: Number,
            default: 0
        },
        initials: {
            type: String,
            required: true
        }
    });

    const emit = defineEmits(['openNotifications']);

    const breadcrumbItems = computed(() => props.items);

    const openNotificationsModal = () => {
        emit('openNotifications');
    };
</script>

<template>
    <header class="flex flex-col lg:flex-row lg:items-start lg:justify-between mb-6 lg:mb-8 p-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-muted-foreground">
                    <template v-for="(item, index) in breadcrumbItems" :key="index">
                        <li>
                            <TextLink
                                v-if="item.href"
                                :href="item.href"
                                class="transition-colors hover:text-foreground"
                            >
                                {{ item.label }}
                            </TextLink>
                            <span v-else class="font-medium text-foreground">
                                {{ item.label }}
                            </span>
                        </li>

                        <li v-if="index < breadcrumbItems.length - 1">
                            <ChevronRight class="h-4 w-4" />
                        </li>
                    </template>
                </ol>
            </nav>
        </div>

        <div class="hidden lg:flex items-center gap-4">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
                <input
                    type="text"
                    placeholder="Search"
                    class="input-crypto pl-10 pr-4 py-2"
                />
            </div>

            <button
                @click="openNotificationsModal"
                class="p-2 bg-card rounded-xl border border-border hover:bg-secondary relative cursor-pointer transition-colors"
                title="Notifications"
            >
                <Bell class="w-5 h-5 text-card-foreground" />
                <span
                    v-if="notificationCount > 0"
                    class="absolute top-1 right-1 w-2 h-2 bg-primary rounded-full"
                ></span>
            </button>

            <TextLink
                :href="route('user.profile.index')"
                class="w-9 h-9 bg-accent rounded-xl relative cursor-pointer overflow-hidden flex items-center justify-center"
                title="My Profile"
            >
                <img
                    v-if="user.profile?.profile_photo_path"
                    :src="user.profile.profile_photo_path"
                    alt="Profile picture"
                    class="h-full w-full object-cover"
                />
                <span v-else class="text-sm text-accent-foreground font-semibold select-none">
                    {{ initials }}
                </span>
            </TextLink>
        </div>
    </header>
</template>
