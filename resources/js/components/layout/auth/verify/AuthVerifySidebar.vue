<script setup lang="ts">
    import { defineComponent, h } from 'vue'

    const ChartGraphics = defineComponent({
        render() {
            return h('div', { class: 'flex justify-center items-center' }, [
                h('div', { class: 'relative' }, [
                    h('div', { class: 'w-48 h-32 bg-muted/20 rounded-lg border border-muted p-4' }, [
                        h('div', { class: 'flex items-end justify-center h-full space-x-1' }, [
                            h('div', { class: 'w-2 h-4 bg-muted rounded-sm' }),
                            h('div', { class: 'w-2 h-6 bg-muted rounded-sm' }),
                            h('div', { class: 'w-2 h-8 bg-foreground rounded-sm' }),
                            h('div', { class: 'w-2 h-3 bg-muted rounded-sm' }),
                            h('div', { class: 'w-2 h-7 bg-muted rounded-sm' }),
                            h('div', { class: 'w-2 h-5 bg-muted rounded-sm' }),
                            h('div', { class: 'w-2 h-9 bg-muted rounded-sm' }),
                            h('div', { class: 'w-2 h-4 bg-muted rounded-sm' })
                        ])
                    ]),
                    h('div', { class: 'w-8 h-4 bg-muted/30 mx-auto relative -top-1' }),
                    h('div', { class: 'w-16 h-2 bg-muted/30 mx-auto relative -top-1' }),
                    h('div', { class: 'absolute -top-4 -left-8 w-8 h-8 border-l-4 border-t-4 border-accent rotate-45' }),
                    h('div', { class: 'absolute top-4 -right-8 w-8 h-8 border-r-4 border-t-4 border-accent -rotate-45' })
                ])
            ])
        }
    })
</script>

<template>
    <div class="hidden lg:flex lg:w-1/2 auth-card p-8 flex-col justify-center items-center relative">
        <div class="text-center z-10 max-w-md">
            <div class="mb-8">
                <ChartGraphics />
            </div>

            <h1 class="text-4xl font-bold mb-6">
                Track Value Change Each Digital Currency
            </h1>

            <p class="text-muted-foreground text-lg leading-relaxed">
                Easily buy Bitcoin and other cryptocurrencies using a wide range of payment options.
                Discover exclusive digital collectibles using InCrypto today
            </p>
        </div>
    </div>
</template>
