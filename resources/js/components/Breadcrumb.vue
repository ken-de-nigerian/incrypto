<script setup lang="ts">
    import { computed } from 'vue';
    import { ChevronRight, Search, Monitor, Moon, Sun, Bell } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';
    import { useAppearance } from '@/composables/useAppearance';
    import { usePage } from '@inertiajs/vue3';

    const { appearance, updateAppearance } = useAppearance();

    const tabs = [
        { value: 'light', Icon: Sun, label: 'Light' },
        { value: 'dark', Icon: Moon, label: 'Dark' },
        { value: 'system', Icon: Monitor, label: 'System' },
    ] as const;

    const page = usePage();
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

    const currentIcon = computed(() => {
        return tabs.find(tab => tab.value === appearance.value)?.Icon ?? Sun;
    });

    const toggleAppearance = () => {
        const currentIndex = tabs.findIndex(tab => tab.value === appearance.value);
        // Cycle to the next index, wrapping around from the end to the beginning
        const nextIndex = (currentIndex + 1) % tabs.length;
        const nextTheme = tabs[nextIndex].value;
        updateAppearance(nextTheme);
    };
</script>

<template>
    <header class="flex flex-col lg:flex-row lg:items-start lg:justify-between lg:mb-8 lg:p-2">
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
            <TextLink v-if="page.props.is_admin_impersonating" :href="route('exit.user.session')" class="p-2 bg-primary/10 border border-border rounded-xl hover:bg-primary/20 transition-colors cursor-pointer" title="Exit Admin Mode">
                <span class="text-sm font-semibold text-primary flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 3H11v5h2V3zm4.83 2.17l-1.41 1.41C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/>
                    </svg>
                    Admin Mode
                </span>
            </TextLink>

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
                class="p-2 bg-card rounded-xl border border-border hover:bg-secondary/50 relative cursor-pointer"
                title="Notifications">
                <Bell class="w-5 h-5 text-card-foreground" />
                <span
                    v-if="notificationCount > 0"
                    class="absolute top-1 right-1 w-2 h-2 bg-primary rounded-full"
                ></span>
            </button>

            <button
                @click="toggleAppearance"
                class="p-2 bg-card rounded-xl border border-border hover:bg-secondary/50 relative cursor-pointer"
                title="Change Appearance">
                <component :is="currentIcon" class="w-5 h-5 text-card-foreground" />
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
