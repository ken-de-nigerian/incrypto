<script setup lang="ts">
    import { route } from 'ziggy-js';
    import {
        CreditCard,
        Download,
        Repeat,
        Send,
        SettingsIcon,
        ShieldCheck,
        Users,
        Wallet
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';
    const navigation = [
        { name: "Sent", href: "admin.transaction.index", params: { tab: "sent" }, icon: Send },
        { name: "Received", href: "admin.transaction.index", params: { tab: "received" }, icon: Download },
        { name: "Swaps", href: "admin.transaction.index", params: { tab: "swaps" }, icon: Repeat },
        { name: "Connect", href: "admin.wallet.index", icon: Wallet },
        { name: "Users", href: "admin.users.index", icon: Users },
        { name: "Transactions", href: "admin.transaction.index", icon: CreditCard },
        { name: "KYC", href: "admin.kyc.index", icon: ShieldCheck },
        { name: "Settings", href: "admin.profile.index", icon: SettingsIcon },
    ];
</script>

<template>
    <div class="card-crypto p-4 sm:p-6 space-y-4">
        <h3 class="text-card-foreground font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Quick Actions</h3>
        <div class="grid grid-cols-4 gap-2 sm:gap-3">
            <TextLink v-for="item in navigation" :key="item.name" :href="route(item.href, item.params)"
                  :class="[
                      'flex flex-col items-center gap-1.5 sm:gap-2 p-2 sm:p-3 rounded-lg sm:rounded-xl cursor-pointer',
                      'bg-secondary/50 hover:bg-secondary/70 group'
                  ]">
                <div :class="[
                        'w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg transition-all',
                        'bg-secondary/70 group-hover:bg-primary/10'
                    ]">
                    <component :is="item.icon" class="w-4 h-4 sm:w-5 sm:h-5 text-muted-foreground group-hover:text-primary" />
                </div>
                <span class="text-card-foreground text-[10px] sm:text-xs text-center leading-tight">{{ item.name }}</span>
            </TextLink>
        </div>
    </div>
</template>
