<script setup lang="ts">
    import {
        Wallet,
        ShieldCheck,
        RefreshCw,
        TrendingUp,
        ArrowUpRight,
    } from 'lucide-vue-next';
</script>

<template>
    <div class="hidden lg:flex lg:w-1/2 auth-card p-8 flex-col justify-center items-center relative overflow-hidden">

        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary/20 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="text-center z-10 w-full max-w-md">

            <div class="relative h-[320px] w-full mb-8 flex items-center justify-center">

                <svg class="absolute inset-0 w-full h-full pointer-events-none text-border" xmlns="http://www.w3.org/2000/svg">
                    <line x1="50%" y1="50%" x2="80%" y2="25%" stroke="currentColor" stroke-width="1" stroke-dasharray="4 4" class="opacity-30" />
                    <line x1="50%" y1="50%" x2="20%" y2="25%" stroke="currentColor" stroke-width="1" stroke-dasharray="4 4" class="opacity-30" />
                    <line x1="50%" y1="50%" x2="50%" y2="85%" stroke="currentColor" stroke-width="1" stroke-dasharray="4 4" class="opacity-30" />
                </svg>

                <div class="relative z-20 animate-float-center">
                    <div class="w-24 h-24 bg-card border-2 border-primary/20 rounded-full flex items-center justify-center shadow-[0_0_30px_-5px_rgba(var(--primary),0.3)]">
                        <Wallet class="w-10 h-10 text-primary" stroke-width="1.5" />
                    </div>
                </div>

                <div class="absolute top-12 right-4 md:right-0 z-30 animate-float-delayed-1">
                    <div class="bg-card/90 backdrop-blur border border-border p-3 rounded-xl shadow-lg flex items-center gap-3 w-36">
                        <div class="bg-blue-500/10 p-2 rounded-lg text-blue-500">
                            <RefreshCw class="w-5 h-5" />
                        </div>
                        <div class="flex flex-col text-left">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground">Exchange</span>
                            <span class="text-xs font-bold">Instant Swap</span>
                        </div>
                    </div>
                </div>

                <div class="absolute top-12 left-4 md:left-0 z-30 animate-float-delayed-2">
                    <div class="bg-card/90 backdrop-blur border border-border p-3 rounded-xl shadow-lg flex items-center gap-3">
                        <div class="bg-emerald-500/10 p-2 rounded-full text-emerald-500">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-bold">Portfolio</span>
                            <span class="block text-[10px] text-emerald-500 flex items-center gap-1 font-bold">
                                +12.4% APY <ArrowUpRight class="w-3 h-3" />
                            </span>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-30 animate-float-delayed-3">
                    <div class="bg-card/90 backdrop-blur border border-border px-4 py-2 rounded-full shadow-lg flex items-center gap-2 whitespace-nowrap">
                        <ShieldCheck class="w-4 h-4 text-purple-500" />
                        <span class="text-xs font-medium">Bank-Grade Security</span>
                    </div>
                </div>

            </div>

            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-foreground to-muted-foreground bg-clip-text text-transparent">
                Your Gateway to Web3
            </h1>

            <h2 class="text-2xl font-bold mb-6 text-foreground">
                Trade, Store & Grow
            </h2>

            <p class="text-muted-foreground text-lg leading-relaxed max-w-sm mx-auto">
                Manage your portfolio with confidence.
                Experience lightning-fast swaps and military-grade encryption.
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
