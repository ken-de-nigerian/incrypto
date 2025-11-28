<script setup lang="ts">
    import {
        Wallet,
        TrendingUp,
        RefreshCw,
        Send,
        BarChart3,
        ArrowUpRight
    } from 'lucide-vue-next';
</script>

<template>
    <div class="hidden lg:flex lg:w-1/2 auth-card p-8 flex-col justify-center items-center relative overflow-hidden">

        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary/20 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="text-center z-10 w-full max-w-md">

            <div class="relative h-[360px] w-full mb-8 flex items-center justify-center">

                <svg class="absolute inset-0 w-full h-full pointer-events-none text-border" xmlns="http://www.w3.org/2000/svg">
                    <line x1="50%" y1="50%" x2="20%" y2="20%" stroke="currentColor" stroke-width="1" stroke-dasharray="4 4" class="opacity-30" />
                    <line x1="50%" y1="50%" x2="80%" y2="20%" stroke="currentColor" stroke-width="1" stroke-dasharray="4 4" class="opacity-30" />
                    <line x1="50%" y1="50%" x2="20%" y2="80%" stroke="currentColor" stroke-width="1" stroke-dasharray="4 4" class="opacity-30" />
                    <line x1="50%" y1="50%" x2="80%" y2="80%" stroke="currentColor" stroke-width="1" stroke-dasharray="4 4" class="opacity-30" />
                </svg>

                <div class="relative z-20 animate-float-center">
                    <div class="w-24 h-24 bg-card border-2 border-primary/20 rounded-full flex items-center justify-center shadow-[0_0_30px_-5px_rgba(var(--primary),0.3)]">
                        <Wallet class="w-10 h-10 text-primary" stroke-width="1.5" />
                    </div>
                </div>

                <div class="absolute top-8 left-0 z-30 animate-float-delayed-1">
                    <div class="bg-card/90 backdrop-blur border border-border p-3 rounded-xl shadow-lg flex items-center gap-3 w-40">
                        <div class="bg-emerald-500/10 p-2 rounded-lg text-emerald-500">
                            <TrendingUp class="w-5 h-5" />
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground">Invest</span>
                            <span class="text-xs font-bold flex items-center gap-1 text-emerald-500">
                                +12.5% APY <ArrowUpRight class="w-3 h-3" />
                            </span>
                        </div>
                    </div>
                </div>

                <div class="absolute top-8 right-0 z-30 animate-float-delayed-2">
                    <div class="bg-card/90 backdrop-blur border border-border p-3 rounded-xl shadow-lg flex items-center gap-3 w-36">
                        <div class="bg-blue-500/10 p-2 rounded-lg text-blue-500">
                            <RefreshCw class="w-5 h-5" />
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground">Swap</span>
                            <span class="text-xs font-bold">Any Asset</span>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-8 left-0 z-30 animate-float-delayed-3">
                    <div class="bg-card/90 backdrop-blur border border-border p-3 rounded-xl shadow-lg flex items-center gap-3 w-40">
                        <div class="bg-orange-500/10 p-2 rounded-lg text-orange-500">
                            <Send class="w-5 h-5" />
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground">Transfer</span>
                            <span class="text-xs font-bold">Send & Receive</span>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-8 right-0 z-30 animate-float-delayed-1">
                    <div class="bg-card/90 backdrop-blur border border-border p-3 rounded-xl shadow-lg flex items-center gap-3 w-36">
                        <div class="bg-purple-500/10 p-2 rounded-lg text-purple-500">
                            <BarChart3 class="w-5 h-5" />
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground">Trade</span>
                            <span class="text-xs font-bold">Pro Charts</span>
                        </div>
                    </div>
                </div>

            </div>

            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-foreground to-muted-foreground bg-clip-text text-transparent">
                Trade, Swap & Invest
            </h1>

            <h2 class="text-2xl font-bold mb-6">
                The All-in-One Crypto Wallet
            </h2>

            <p class="text-muted-foreground text-lg leading-relaxed max-w-sm mx-auto">
                Send and receive assets instantly. Access pro trading tools and grow your portfolio with smart investments.
            </p>
        </div>
    </div>
</template>

<style scoped>
    .animate-float-center {
        animation: float 6s ease-in-out infinite;
    }
    .animate-float-delayed-1 {
        animation: float 7s ease-in-out infinite 1s;
    }
    .animate-float-delayed-2 {
        animation: float 5s ease-in-out infinite 0.5s;
    }
    .animate-float-delayed-3 {
        animation: float 8s ease-in-out infinite 1.5s;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
</style>
