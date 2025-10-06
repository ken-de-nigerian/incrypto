<template>
    <div class="bg-zinc-900 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-zinc-800">
        <h3 class="text-white font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Quick Actions</h3>
        <div class="grid grid-cols-4 gap-2 sm:gap-3">
            <TextLink v-for="item in navigation" :key="item.name" :href="route(item.href)" class="flex flex-col items-center gap-1.5 sm:gap-2 p-2 sm:p-3 bg-zinc-800/50 rounded-lg sm:rounded-xl hover:bg-zinc-800 transition-colors group">
                <div class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg bg-zinc-700/50 group-hover:bg-lime-400/10 transition-colors">
                    <component :is="item.icon" class="w-4 h-4 sm:w-5 sm:h-5 text-zinc-300 group-hover:text-lime-400 transition-colors" />
                </div>
                <span class="text-white text-[10px] sm:text-xs text-center leading-tight">{{ item.name }}</span>
            </TextLink>
        </div>
    </div>
</template>

<script setup lang="ts">
    import {
        Send,
        Download,
        Repeat,
        CandlestickChart,
        Wallet,
        Users,
        CreditCard,
        ShieldCheck
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';
    import { route } from 'ziggy-js';

    const navigation = [
        { name: "Send", href: "user.send.index", icon: Send },
        { name: "Receive", href: "user.receive.index", icon: Download },
        { name: "Swap", href: "user.swap.index", icon: Repeat },
        { name: "Trade", href: "user.trade.index", icon: CandlestickChart },
        { name: "Connect", href: "user.connect.index", icon: Wallet },
        { name: "Referrals", href: "user.rewards.index", icon: Users },
        { name: "Payments", href: "user.transactions.index", icon: CreditCard },
        { name: "KYC", href: "user.kyc.index", icon: ShieldCheck },
    ];
</script>
