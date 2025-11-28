<script setup lang="ts">
    import { ref, defineComponent, h, onMounted } from 'vue'
    import { Head, router } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import { useOnboarding } from '@/composables/useOnboarding';

    // Import Lucide Icons
    import {
        BarChart3,
        Bitcoin,
        ArrowRightLeft,
        Wallet,
        TrendingUp,
        Coins,
        ShieldCheck,
        Lock,
        CheckCircle2
    } from 'lucide-vue-next';

    const currentSlide = ref(0)
    const { markOnboardingComplete, checkAndRedirect } = useOnboarding()

    onMounted(() => {
        checkAndRedirect()
    })

    // Graphic 1: Trading (Bar Chart + Bitcoin + Swap Icon)
    const TradingChartGraphic = defineComponent({
        render() {
            return h('div', { class: 'relative flex justify-center items-center h-40 w-full mb-4' }, [
                // Background Glow
                h('div', { class: 'absolute w-32 h-32 bg-primary/10 rounded-full blur-2xl' }),

                // Main Icon (Chart)
                h('div', { class: 'relative z-10 bg-card border border-border p-4 rounded-2xl' }, [
                    h(BarChart3, { size: 64, class: 'text-primary' })
                ]),

                // Floating Element 1 (Bitcoin)
                h('div', {
                    class: 'absolute top-2 right-1/4 bg-background border border-border p-2 rounded-full animate-bounce',
                    style: 'animation-duration: 3s;'
                }, [
                    h(Bitcoin, { size: 24, class: 'text-orange-500' })
                ]),

                // Floating Element 2 (Swap/Exchange)
                h('div', {
                    class: 'absolute bottom-2 left-1/4 bg-background border border-border p-2 rounded-full animate-bounce',
                    style: 'animation-duration: 4s; animation-delay: 1s;'
                }, [
                    h(ArrowRightLeft, { size: 20, class: 'text-blue-500' })
                ]),
            ])
        }
    })

    // Graphic 2: Savings (Wallet + Trending Up + Coins)
    const SavingsGraphic = defineComponent({
        render() {
            return h('div', { class: 'relative flex justify-center items-center h-40 w-full mb-4' }, [
                h('div', { class: 'absolute w-32 h-32 bg-green-500/10 rounded-full blur-2xl' }),

                // Main Icon (Wallet)
                h('div', { class: 'relative z-10 bg-card border border-border p-4 rounded-2xl' }, [
                    h(Wallet, { size: 64, class: 'text-green-600' })
                ]),

                // Floating Element 1 (Trending Up)
                h('div', {
                    class: 'absolute -top-2 left-1/3 bg-background border border-border p-2 rounded-full animate-bounce',
                    style: 'animation-duration: 3.5s;'
                }, [
                    h(TrendingUp, { size: 24, class: 'text-emerald-500' })
                ]),

                // Floating Element 2 (Coins)
                h('div', {
                    class: 'absolute -bottom-1 right-1/3 bg-background border border-border p-2 rounded-full animate-bounce',
                    style: 'animation-duration: 4.5s; animation-delay: 0.5s;'
                }, [
                    h(Coins, { size: 20, class: 'text-yellow-500' })
                ])
            ])
        }
    })

    // Graphic 3: Security (Shield + Lock)
    const SecurityShieldGraphic = defineComponent({
        render() {
            return h('div', { class: 'relative flex justify-center items-center h-40 w-full mb-4' }, [
                h('div', { class: 'absolute w-32 h-32 bg-blue-500/10 rounded-full blur-2xl' }),

                // Main Icon (Shield)
                h('div', { class: 'relative z-10 bg-card border border-border p-4 rounded-2xl' }, [
                    h(ShieldCheck, { size: 64, class: 'text-blue-600' })
                ]),

                // Floating Element 1 (Lock)
                h('div', {
                    class: 'absolute top-0 right-1/3 bg-background border border-border p-2 rounded-full animate-bounce',
                    style: 'animation-duration: 5s;'
                }, [
                    h(Lock, { size: 20, class: 'text-indigo-500' })
                ]),

                // Floating Element 2 (Checkmark)
                h('div', {
                    class: 'absolute bottom-2 left-1/3 bg-background border border-border p-2 rounded-full animate-bounce',
                    style: 'animation-duration: 4s; animation-delay: 1.5s;'
                }, [
                    h(CheckCircle2, { size: 20, class: 'text-teal-500' })
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
    <Head title="Welcome" />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md mx-auto">
            <div class="text-center">
                <div class="mb-6 transform transition-all duration-500 ease-in-out">
                    <component :is="slides[currentSlide].graphic" />
                </div>

                <h1 class="text-3xl font-bold mb-4 leading-tight text-foreground">
                    {{ slides[currentSlide].title }}
                </h1>

                <p class="text-muted-foreground text-lg leading-relaxed mb-8">
                    {{ slides[currentSlide].subtitle }}
                </p>
            </div>

            <div class="flex justify-center space-x-2 mb-8">
                <button
                    v-for="(_, index) in slides"
                    :key="index"
                    @click="currentSlide = index"
                    :class="[
                        'rounded-full transition-all duration-300',
                        index === currentSlide ? 'bg-primary w-8 h-2' : 'bg-muted h-2 w-2 hover:bg-primary/50'
                    ]"
                    :aria-label="`Go to slide ${index + 1}`"
                />
            </div>

            <div class="space-y-4">
                <button
                    v-if="currentSlide < slides.length - 1"
                    @click="nextSlide"
                    class="w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold mb-auto cursor-pointer bg-primary text-primary-foreground hover:bg-primary/90 transition-colors">
                    Continue
                </button>

                <button
                    v-else
                    @click="handleGetStarted"
                    class="w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold mb-auto cursor-pointer bg-primary text-primary-foreground hover:bg-primary/90 transition-colors">
                    Get Started
                </button>

                <div class="text-center">
                    <button @click="handleSkip" class="text-muted-foreground hover:text-foreground bg-transparent border-none cursor-pointer text-sm font-medium transition-colors">
                        Skip
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
