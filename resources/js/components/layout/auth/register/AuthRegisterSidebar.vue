<script setup lang="ts">
    import { defineComponent, h } from 'vue'

    const FloatingCrypto = defineComponent({
        render() {
            return h('div', { class: 'absolute inset-0 overflow-hidden pointer-events-none' }, [
                h('div', {
                    class: 'absolute top-1/4 left-1/4 w-24 h-24 bg-crypto-bitcoin/20 rounded-full animate-float',
                    style: 'animation-delay: 0s'
                }),
                h('div', {
                    class: 'absolute top-1/3 right-1/4 w-16 h-16 bg-crypto-ethereum/20 rounded-full animate-float',
                    style: 'animation-delay: 1s'
                }),
                h('div', {
                    class: 'absolute bottom-1/3 left-1/3 w-20 h-20 bg-accent/20 rounded-full animate-float',
                    style: 'animation-delay: 2s'
                })
            ])
        }
    })
</script>

<template>
    <div class="hidden lg:flex lg:w-1/2 auth-card p-8 flex-col justify-center items-center relative">
        <FloatingCrypto />
        <div class="text-center z-10 max-w-md">
            <div class="mb-8 flex justify-center">
                <div class="relative">
                    <div class="w-32 h-32 bg-crypto-bitcoin rounded-full opacity-20 animate-pulse-slow" />
                    <div class="absolute top-4 left-4 w-24 h-24 bg-crypto-ethereum rounded-full opacity-30 animate-float" />
                    <div class="absolute top-8 left-8 w-16 h-16 bg-accent rounded-full animate-crypto-glow" />
                </div>
            </div>

            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-foreground to-muted-foreground bg-clip-text text-transparent">
                Exchange, Sell & Buy
            </h1>

            <h2 class="text-3xl font-bold mb-6">
                Cryptocurrency and Digital Arts
            </h2>

            <p class="text-muted-foreground text-lg leading-relaxed">
                Easily buy Bitcoin and other cryptocurrencies using a wide range of payment options.
                Discover exclusive digital collectibles using InCrypto today
            </p>
        </div>
    </div>
</template>
