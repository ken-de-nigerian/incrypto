<script setup lang="ts">
    import { Home, User2, Users2, CreditCard, FilesIcon } from 'lucide-vue-next';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { computed } from 'vue';

    const navigation = [
        { name: 'Home', href: 'admin.dashboard', icon: Home, isDefault: true },
        { name: "Users", href: "admin.users.index", icon: Users2, group: 'admin.users.' },
        { name: "Logs", href: 'admin.dashboard', icon: FilesIcon, group: 'admin.logs.' },
        { name: 'Wallets', href: 'admin.wallet.index', icon: CreditCard, group: 'admin.wallet.' },
        { name: 'Account', href: 'admin.profile.index', icon: User2, group: 'admin.profile.' }
    ];

    const isAnyItemActive = computed(() => {
        return navigation.some(item => {
            if (item.group) {
                return route().current(item.group + '*');
            }
            return route().current(item.href);
        });
    });


    const isActive = (item: typeof navigation[0]) => {
        let active: boolean;
        if (item.group) {
            active = route().current(item.group + '*');
        } else {
            active = route().current(item.href);
        }
        if (!active && item.isDefault && !isAnyItemActive.value) {
            active = true;
        }

        return active;
    }
</script>

<template>
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-card z-50 border-t border-border">
        <div class="flex items-center justify-around py-2">
            <TextLink
                v-for="item in navigation"
                :key="item.name"
                :href="route(item.href)"
                class="flex flex-col items-center justify-center py-2 px-4 rounded-lg transition-all duration-200"
                :class="{
                    'bg-primary/10 text-primary': isActive(item),
                    'text-muted-foreground hover:text-card-foreground': !isActive(item),
                }">

                <div class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-lg mb-1"
                     :class="{
                        'bg-primary/20': isActive(item),
                        'bg-secondary/20': !isActive(item),
                    }">
                    <component :is="item.icon" class="h-5 w-5" />
                </div>
                <span class="text-xs font-medium">{{ item.name }}</span>
            </TextLink>
        </div>
    </div>
</template>
