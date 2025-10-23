<script setup lang="ts">
    import { Home, User2, Send, Download, Wallet } from 'lucide-vue-next';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { computed } from 'vue';

    const navigation = [
        { name: 'Home', href: 'user.dashboard', icon: Home, group: 'user.dashboard', isDefault: true },
        { name: "Send", href: "user.send.index", icon: Send, group: 'user.send.' },
        { name: "Receive", href: "user.receive.index", icon: Download, group: 'user.receive.' },
        { name: 'Wallets', href: 'user.wallet.index', icon: Wallet, group: 'user.wallet.' },
        { name: 'Account', href: 'user.profile.index', icon: User2, group: 'user.profile.' }
    ];

    const isAnyItemActive = computed(() => {
        return navigation.some(item => {
            if (item.isDefault) return false;
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
