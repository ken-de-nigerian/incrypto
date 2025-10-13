<script setup lang="ts">
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { usePage } from '@inertiajs/vue3';
    import { computed } from 'vue';

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });
</script>

<template>
    <header class="w-full bg-background/90 backdrop-blur-sm fixed top-0 left-0 z-10 lg:hidden border-b border-border/50">
        <div class="flex justify-between items-center p-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-secondary flex items-center justify-center overflow-hidden">
                    <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" alt="Profile picture" loading="lazy" class="h-full w-full object-cover" />
                    <span v-else class="text-accent-foreground font-bold text-lg">
                        {{ initials }}
                    </span>
                </div>

                <div>
                    <p class="text-sm font-semibold text-foreground truncate">{{  user.first_name }} {{  user.last_name }}</p>
                    <p class="text-xs text-muted-foreground truncate">{{  user.email }}</p>
                </div>
            </div>

            <TextLink :href="route('logout')" method="post" as="button" class="ml-4 p-2 text-sm text-foreground hover:text-accent transition-colors" title="Logout">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </TextLink>
        </div>
    </header>
</template>
