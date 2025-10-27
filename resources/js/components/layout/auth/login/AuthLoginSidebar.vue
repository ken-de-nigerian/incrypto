<script setup lang="ts">
    import { defineComponent, h } from 'vue';

    const NFTGraphics = defineComponent({
        render() {
            return h('div', { class: 'flex justify-center items-center' }, [
                h('div', { class: 'relative' }, [
                    h('div', { class: 'w-32 h-32 border-4 border-muted rounded-full bg-muted/20 flex items-center justify-center' }, [
                        h('div', { class: 'w-16 h-16 border-2 border-foreground rounded-full' }),
                        h('div', { class: 'absolute bottom-4 right-4 w-8 h-8 border-2 border-foreground rotate-45' })
                    ]),

                    h('div', { class: 'absolute top-8 -right-8 w-20 h-20 bg-muted/30 rounded-lg flex items-center justify-center border border-border' }, [
                        h('span', { class: 'text-sm font-bold text-muted-foreground' }, 'NFT')
                    ]),

                    h('div', { class: 'absolute -top-4 left-8 w-16 h-16 bg-crypto-ethereum/20 rounded-full flex items-center justify-center border-2 border-crypto-ethereum/50' }, [
                        h('span', { class: 'text-xs font-bold' }, 'Ξ')
                    ])
                ])
            ])
        }
    })
</script>

<template>
    <div class="hidden lg:flex lg:w-1/2 auth-card p-8 flex-col justify-center items-center relative">
        <div class="text-center z-10 max-w-md">
            <div class="mb-8">
                <NFTGraphics />
            </div>

            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-foreground to-muted-foreground bg-clip-text text-transparent">
                Access Your Digital Assets
            </h1>

            <h2 class="text-3xl font-bold mb-6">
                Cryptocurrency and Arts
            </h2>

            <p class="text-muted-foreground text-lg leading-relaxed">
                Log in to manage your Bitcoin and other cryptocurrencies securely.
                Explore and trade exclusive digital collectibles with InCrypto.
            </p>
        </div>
    </div>
</template>
