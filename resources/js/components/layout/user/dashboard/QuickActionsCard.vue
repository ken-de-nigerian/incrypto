<script setup lang="ts">
    import {
        Send,
        Download,
        Repeat,
        Wallet,
        Users,
        CreditCard,
        ShieldCheck, TrendingUp
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';
    import { route } from 'ziggy-js';

    const navigation = [
        { name: "Trading", href: "user.trade.index", icon: TrendingUp },
        { name: "Send", href: "user.send.index", icon: Send },
        { name: "Receive", href: "user.receive.index", icon: Download },
        { name: "Swap", href: "user.swap.index", icon: Repeat },
        { name: "Wallets", href: "user.wallet.index", icon: Wallet },
        { name: "Referrals", href: "user.rewards.index", icon: Users },
        { name: "Payments", href: "user.transactions.index", icon: CreditCard },
        { name: "KYC", href: "user.kyc.index", icon: ShieldCheck },
    ];
</script>

<template>
    <div class="card-crypto p-4 sm:p-6">
        <h3 class="text-card-foreground font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Quick Actions</h3>
        <div class="grid grid-cols-4 gap-2 sm:gap-3">
            <TextLink v-for="item in navigation" :key="item.name" :href="route(item.href)"
                  :class="[
                      'flex flex-col items-center gap-1.5 sm:gap-2 p-2 sm:p-3 rounded-lg sm:rounded-xl cursor-pointer',
                      'bg-secondary/50 hover:bg-secondary/70 group'
                  ]">
                <div
                    :class="[
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
