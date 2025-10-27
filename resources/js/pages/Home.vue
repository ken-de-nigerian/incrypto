<script setup lang="ts">
    import { ref, defineComponent, h, onMounted } from 'vue'
    import { Head, router } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import { useOnboarding } from '@/composables/useOnboarding';

    const currentSlide = ref(0)
    const { markOnboardingComplete, checkAndRedirect } = useOnboarding()

    onMounted(() => {
        checkAndRedirect()
    })

    const TradingChartGraphic = defineComponent({
        render() {
            return h('div', { class: 'relative flex justify-center items-center mb-8' }, [
                h('div', { class: 'relative' }, [
                    h('div', { class: 'w-32 h-20 bg-muted/20 border-2 border-foreground/30 rounded-lg p-3' }, [
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

                    h('div', {
                        class: 'absolute -top-8 left-4 w-12 h-12 bg-crypto-bitcoin/30 rounded-full flex items-center justify-center animate-float'
                    }, [
                        h('span', { class: 'font-bold text-lg' }, '₿')
                    ]),

                    h('div', {
                        class: 'absolute top-4 -right-8 w-8 h-8 bg-crypto-ethereum/30 rounded-full flex items-center justify-center animate-float',
                        style: 'animation-delay: 1s'
                    }, [
                        h('span', { class: 'font-bold' }, 'Ξ')
                    ]),
                ])
            ])
        }
    })

    const SavingsGraphic = defineComponent({
        render() {
            return h('div', { class: 'relative flex justify-center items-center mb-8' }, [
                h('div', { class: 'relative' }, [
                    h('div', { class: 'w-24 h-24 border-4 border-foreground/30 rounded-lg bg-muted/10 flex items-center justify-center transform skew-y-3' }, [
                        h('span', { class: 'text-2xl font-extrabold text-accent' }, '$')
                    ]),

                    h('div', {
                        class: 'absolute -top-6 -right-6 w-12 h-12 bg-green-500/50 rounded-full flex items-center justify-center animate-pulse'
                    }, [
                        h('span', { class: 'font-extrabold text-2xl' }, '↑')
                    ]),

                    h('div', {
                        class: 'absolute -bottom-4 left-0 w-6 h-6 bg-yellow-400/30 rounded-full animate-float'
                    })
                ])
            ])
        }
    })

    const SecurityShieldGraphic = defineComponent({
        render() {
            return h('div', { class: 'relative flex justify-center items-center mb-8' }, [
                h('div', { class: 'relative' }, [

                    h('div', { class: 'w-32 h-32 bg-accent/20 border-4 border-accent rounded-full flex items-center justify-center' }, [
                        h('div', { class: 'w-16 h-16 bg-accent rounded-full flex items-center justify-center' }, [

                            h('span', { class: 'text-2xl font-bold text-background' }, '🔒')
                        ])
                    ]),

                    h('div', { class: 'absolute -bottom-4 -right-4 w-10 h-10 bg-green-500/80 rounded-full flex items-center justify-center border-2 border-background' }, [
                        h('span', { class: 'text-xl text-background' }, '✓')
                    ])
                ])
            ])
        }
    })

    const slides = [
        {
            title: "Trade & Manage 600+ Cryptocurrencies",
            subtitle: "Buy, sell, and exchange Bitcoin, Ethereum, and hundreds of other assets with low fees and high liquidity.",
            graphic: TradingChartGraphic
        },
        {
            title: "Earn Interest and Grow Your Assets",
            subtitle: "Put your crypto to work with Staking, Savings, and Yield Farming opportunities directly from your secure wallet.",
            graphic: SavingsGraphic
        },
        {
            title: "Security & Control Over Your Funds",
            subtitle: "Benefit from industry-leading security, 2FA, and cold storage protection for maximum peace of mind.",
            graphic: SecurityShieldGraphic
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
