<script setup lang="ts">
    import { Home, User2, Send, Download, Wallet } from 'lucide-vue-next';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';

    const navigation = [
        { name: 'Home', href: 'user.dashboard', icon: Home },
        { name: "Send", href: "user.send.index", icon: Send },
        { name: "Receive", href: "user.receive.index", icon: Download },
        { name: 'Connect', href: 'user.connect.index', icon: Wallet },
        { name: 'Account', href: 'user.profile.index', icon: User2 }
    ];
</script>

<template>
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-card z-50 border-t border-border">
        <div class="flex items-center justify-around py-2">
            <TextLink v-for="item in navigation" :key="item.name" :href="route(item.href)" class="flex flex-col items-center justify-center py-2 px-4 rounded-lg transition-all duration-200"
                      :class="{
                    'bg-primary/10 text-primary': route().current(item.href),
                    'text-muted-foreground hover:text-card-foreground': !route().current(item.href),
                }">
                <div class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-lg mb-1"
                     :class="{
                        'bg-primary/20': route().current(item.href),
                        'bg-secondary': !route().current(item.href),
                    }">
                    <component :is="item.icon" class="h-5 w-5" />
                </div>
                <span class="text-xs font-medium">{{ item.name }}</span>
            </TextLink>
        </div>
    </div>
</template>
