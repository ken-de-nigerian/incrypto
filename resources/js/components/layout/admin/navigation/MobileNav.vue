<script setup lang="ts">
import { Home, User2, Users2, CreditCard, FilesIcon } from 'lucide-vue-next';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { computed } from 'vue';

    const navigation = [
        { name: 'Home', href: 'admin.dashboard', icon: Home },
        { name: "Users", href: "admin.users.index", icon: Users2 },
        { name: "Logs", href: "admin.dashboard", icon: FilesIcon },
        { name: 'Connect', href: 'admin.wallet.index', icon: CreditCard, isWallet: true },
        { name: 'Account', href: 'admin.profile.index', icon: User2 }
    ];

    const isWalletActive = computed(() => {
        return route().current('user.wallet.index')
            || route().current('user.wallet.create');
    });

    const isActive = (item: typeof navigation[0]) => {
        if (item.isWallet) {
            return isWalletActive.value;
        }
        return route().current(item.href);
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
