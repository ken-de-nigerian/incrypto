<script setup lang="ts">
import { ref, defineComponent, h, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useOnboarding } from '@/composables/useOnboarding';

const currentSlide = ref(0)
const { markOnboardingComplete, checkAndRedirect } = useOnboarding()

// Check if onboarding was already completed on mount
onMounted(() => {
    checkAndRedirect()
})

const FloatingCoins = defineComponent({
    render() {
        return h('div', { class: 'relative flex justify-center items-center mb-8' }, [
            h('div', { class: 'relative' }, [
                // Floating Coins: bg-crypto-bitcoin/30, bg-crypto-ethereum/30, bg-accent/30 (all compliant)
                h('div', {
                    class: 'absolute -top-8 left-4 w-16 h-16 bg-crypto-bitcoin/30 rounded-full flex items-center justify-center animate-float'
                }, [
                    h('span', { class: 'font-bold text-lg' }, '₿')
                ]),
                h('div', {
                    class: 'absolute top-4 -right-8 w-12 h-12 bg-crypto-ethereum/30 rounded-full flex items-center justify-center animate-float',
                    style: 'animation-delay: 1s'
                }, [
                    h('span', { class: 'font-bold' }, 'Ξ')
                ]),
                h('div', {
                    class: 'absolute -bottom-4 left-8 w-14 h-14 bg-accent/30 rounded-full flex items-center justify-center animate-float',
                    style: 'animation-delay: 2s'
                }, [
                    h('span', { class: 'font-bold' }, 'Ł')
                ]),

                // Stars (Compliant)
                h('div', {
                    class: 'absolute -top-4 -left-8 w-4 h-4 text-accent animate-pulse'
                }, '✦'),
                h('div', {
                    class: 'absolute top-12 -right-4 w-4 h-4 text-accent animate-pulse',
                    style: 'animation-delay: 0.5s'
                }, '✦'),
                h('div', {
                    class: 'absolute -bottom-8 right-4 w-4 h-4 text-accent animate-pulse',
                    style: 'animation-delay: 1.5s'
                }, '✦'),

                // Central area
                h('div', { class: 'w-32 h-32' })
            ])
        ])
    }
})

const NFTGraphics = defineComponent({
    render() {
        return h('div', { class: 'relative flex justify-center items-center mb-8' }, [
            h('div', { class: 'relative' }, [
                // Magnifying Glass: bg-muted/10 is compliant.
                h('div', { class: 'w-24 h-24 border-4 border-foreground/30 rounded-full bg-muted/10 flex items-center justify-center' }, [
                    h('div', { class: 'w-12 h-12 border-2 border-foreground rounded-full' })
                ]),
                // Magnifying Glass Handle: bg-background is compliant.
                h('div', {
                    class: 'absolute bottom-2 right-2 w-8 h-8 border-4 border-foreground bg-background rotate-45',
                    style: 'border-radius: 0 50% 50% 50%'
                }),

                // NFT Hexagon: bg-muted/40 is compliant.
                h('div', { class: 'absolute -top-4 -right-12 w-16 h-16 bg-muted/40 flex items-center justify-center border border-border transform rotate-45' }, [
                    h('span', { class: 'text-sm font-bold transform -rotate-45' }, 'NFT')
                ])
            ])
        ])
    }
})

const ChartGraphics = defineComponent({
    render() {
        return h('div', { class: 'relative flex justify-center items-center mb-8' }, [
            h('div', { class: 'relative' }, [
                // Computer Monitor: bg-muted/20 is compliant.
                h('div', { class: 'w-32 h-20 bg-muted/20 border-2 border-foreground/30 rounded-lg p-3' }, [
                    // Chart Bars
                    h('div', { class: 'flex items-end justify-between h-full' }, [
                        h('div', { class: 'w-1 h-4 bg-foreground/50 rounded-sm' }),
                        h('div', { class: 'w-1 h-6 bg-foreground/50 rounded-sm' }),
                        h('div', { class: 'w-1 h-8 bg-accent rounded-sm' }),
                        h('div', { class: 'w-1 h-3 bg-foreground/50 rounded-sm' }),
                        h('div', { class: 'w-1 h-7 bg-foreground/50 rounded-sm' }),
                        h('div', { class: 'w-1 h-5 bg-foreground/50 rounded-sm' }),
                        h('div', { class: 'w-1 h-9 bg-foreground/50 rounded-sm' })
                    ])
                ]),

                // Monitor Stands: bg-muted/30 is compliant.
                h('div', { class: 'w-6 h-3 bg-muted/30 mx-auto' }),
                h('div', { class: 'w-12 h-2 bg-muted/30 mx-auto' }),

                // Arrows (Compliant)
                h('div', { class: 'absolute -top-6 -left-6 w-6 h-6 border-l-2 border-t-2 border-accent rotate-45' }),
                h('div', { class: 'absolute top-2 -right-6 w-6 h-6 border-r-2 border-t-2 border-accent -rotate-45' })
            ])
        ])
    }
})

const slides = [
    {
        title: "Exchange, Buy & Sell Cryptocurrency",
        subtitle: "Easily buy Bitcoin and other cryptocurrencies using a wide range of payment options.",
        graphic: FloatingCoins
    },
    {
        title: "Collect, Sell & Buy Digital Arts",
        subtitle: "Discover exclusive digital collectibles and their non-fungible tokens using InCrypto today.",
        graphic: NFTGraphics
    },
    {
        title: "Track Value Change Each Digital Currency",
        subtitle: "For each digital currency, there is information about its current market cap, price, 24-hour trading volume.",
        graphic: ChartGraphics
    }
]

const nextSlide = () => {
    if (currentSlide.value < slides.length - 1) {
        currentSlide.value++
    }
}

const handleGetStarted = () => {
    markOnboardingComplete()
    router.visit(route('register'))
}

const handleSkip = () => {
    markOnboardingComplete()
    router.visit(route('login'))
}
</script>

<template>
    <Head title="Onboarding" />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md mx-auto">
            <div class="text-center">
                <component :is="slides[currentSlide].graphic" />

                <h1 class="text-3xl font-bold mb-4 leading-tight">
                    {{ slides[currentSlide].title }}
                </h1>

                <p class="text-muted-foreground text-lg leading-relaxed mb-8">
                    {{ slides[currentSlide].subtitle }}
                </p>
            </div>

            <div class="flex justify-center space-x-2 mb-8">
                <button v-for="(_, index) in slides" :key="index" @click="currentSlide = index" :class="`w-2 h-2 rounded-full transition-all duration-300 ${ index === currentSlide ? 'bg-accent w-6' : 'bg-muted' }`" :aria-label="`Go to slide ${index + 1}`" />
            </div>

            <div class="space-y-4">
                <button v-if="currentSlide < slides.length - 1" @click="nextSlide" class="btn-crypto w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold mb-auto cursor-pointer">
                    Continue
                </button>

                <button v-else @click="handleGetStarted" class="btn-crypto w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold mb-auto cursor-pointer">
                    Get Started
                </button>

                <div class="text-center">
                    <button @click="handleSkip" class="text-muted-foreground hover:text-foreground bg-transparent border-none cursor-pointer">
                        Skip
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
