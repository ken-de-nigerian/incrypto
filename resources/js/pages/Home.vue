<script setup lang="ts">
    import { ref, onMounted, onUnmounted } from 'vue'
    import { Carousel, Slide } from 'vue3-carousel'
    import 'vue3-carousel/carousel.css'
    import {
        Shield,
        UserPlus,
        Link as LinkIcon,
        Send,
        RefreshCw,
        TrendingUp,
        DollarSign,
        Copy,
        Building2,
        FileText,
        ChevronDown,
        MessageCircle,
        FileQuestion,
        PlayCircle,
        ArrowRight,
        CheckCircle2,
        Users,
        Lock,
        Zap,
        Globe,
        Key,
        HardDrive,
        Fingerprint,
        TrendingDown,
        Headphones
    } from 'lucide-vue-next'
    import HomeHeader from '@/components/layout/HomeHeader.vue'
    import HomeFooter from '@/components/layout/HomeFooter.vue'
    import TextLink from '@/components/TextLink.vue';
    import { Head } from '@inertiajs/vue3';

    interface Crypto {
        name: string
        symbol: string
        price: string
        change: string
        positive: boolean
    }

    interface Feature {
        Icon: any
        title: string
        description: string
    }

    interface Benefit {
        icon: string
        title: string
        description: string
        stats: string
    }

    interface FAQItem {
        question: string
        answer: string
    }

    interface BlogPost {
        date: string
        author: string
        avatar: string
        title: string
        excerpt: string
    }

    interface TrustedPartner {
        name: string
        logo: string
    }

    interface CryptoShowcase {
        name: string
        icon: string
        color: string
    }

    const activeAccordion = ref<number | null>(0)

    const cryptoCarouselSettings = {
        itemsToShow: 'auto',
        wrapAround: true,
        transition: 5000,
        autoplay: 0,
        snapAlign: 'start',
        mouseDrag: true,
        touchDrag: true,
        pauseAutoplayOnHover: true,
        breakpoints: {
            300: { itemsToShow: 1.5, snapAlign: 'center' },
            640: { itemsToShow: 3, snapAlign: 'start' },
            1024: { itemsToShow: 5, snapAlign: 'start' },
            1280: { itemsToShow: 7, snapAlign: 'start' },
        },
    };

    const blogCarouselSettings = ref({
        itemsToShow: 1,
        snapAlign: 'start',
        wrapAround: false,
        breakpoints: {
            768: { itemsToShow: 2 },
            1024: { itemsToShow: 3 },
        },
    })

    const partnerCarouselSettings = ref({
        itemsToShow: 2,
        snapAlign: 'center',
        wrapAround: true,
        autoplay: 3000,
        pauseAutoplayOnHover: true,
        breakpoints: {
            640: { itemsToShow: 3 },
            1024: { itemsToShow: 5 },
            1280: { itemsToShow: 6 },
        },
    })

    const cryptos: Crypto[] = [
        { name: 'Bitcoin', symbol: 'BTC', price: '$43,250.00', change: '+5.24%', positive: true },
        { name: 'Ethereum', symbol: 'ETH', price: '$2,280.50', change: '+3.15%', positive: true },
        { name: 'Binance Coin', symbol: 'BNB', price: '$315.80', change: '-1.20%', positive: false },
        { name: 'Solana', symbol: 'SOL', price: '$98.45', change: '+8.67%', positive: true },
        { name: 'Cardano', symbol: 'ADA', price: '$0.52', change: '+2.34%', positive: true },
        { name: 'Ripple', symbol: 'XRP', price: '$0.61', change: '-0.89%', positive: false },
    ]

    const features: Feature[] = [
        {
            Icon: Send,
            title: 'Send & Receive',
            description: 'Transfer crypto, fiat, and digital assets instantly with low fees and lightning-fast speed worldwide.',
        },
        {
            Icon: RefreshCw,
            title: 'Instant Swaps',
            description: 'Exchange between 500+ assets instantly at the best rates without leaving your wallet.',
        },
        {
            Icon: TrendingUp,
            title: 'Multi-Asset Trading',
            description: 'Trade cryptocurrencies, forex pairs, stocks, and commodities with advanced charting tools.',
        },
        {
            Icon: DollarSign,
            title: 'Smart Investments',
            description: 'Access DeFi staking, yield farming, and automated investment portfolios with competitive APYs.',
        },
        {
            Icon: Copy,
            title: 'Copy Trading',
            description: 'Automatically replicate trades from top-performing traders and grow your portfolio effortlessly.',
        },
        {
            Icon: Building2,
            title: 'Crypto Loans',
            description: 'Get instant loans using your crypto as collateral without selling your assets.',
        },
        {
            Icon: LinkIcon,
            title: 'WalletConnect',
            description: 'Connect seamlessly to DeFi apps, NFT marketplaces, and Web3 platforms with one click.',
        },
        {
            Icon: Shield,
            title: 'Advanced Security',
            description: 'Multi-signature wallets, biometric authentication, and hardware wallet integration for maximum security.',
        },
        {
            Icon: FileText,
            title: 'Portfolio Analytics',
            description: 'Track performance, analyze gains/losses, and get tax reports for all your digital assets.',
        },
    ]

    const trustedPartners: TrustedPartner[] = [
        { name: 'Binance', logo: '/assets/images/binance.png' },
        { name: 'Coinbase', logo: '/assets/images/coinbase.png' },
        { name: 'Uniswap', logo: '/assets/images/uniswap.png' },
        { name: 'MetaMask', logo: '/assets/images/metamask.png' },
        { name: 'Chainlink', logo: '/assets/images/chainlink.png' },
    ]

    const cryptoShowcase: CryptoShowcase[] = [
        { name: 'Bitcoin', icon: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCBTC--big.svg', color: '#F7931A' },
        { name: 'Ethereum', icon: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCETH--big.svg', color: '#627EEA' },
        { name: 'BNB', icon: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCBNB--big.svg', color: '#F3BA2F' },
        { name: 'Solana', icon: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCSOL--big.svg', color: '#14F195' },
        { name: 'Cardano', icon: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCADA--big.svg', color: '#0033AD' },
        { name: 'XRP', icon: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCXRP--big.svg', color: '#23292F' },
        { name: 'Polkadot', icon: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCDOT--big.svg', color: '#E6007A' },
        { name: 'Dogecoin', icon: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCDOGE--big.svg', color: '#C2A633' },
    ]

    const faqItems: FAQItem[] = [
        {
            question: 'What is this crypto wallet?',
            answer: 'Our wallet is a comprehensive digital finance platform that allows you to store, send, receive, trade, and invest in cryptocurrencies, forex, and stocks. It combines the security of a non-custodial wallet with advanced trading features, DeFi access, and copy trading capabilities—all in one place.',
        },
        {
            question: 'How secure is my wallet?',
            answer: 'Security is our top priority. We use military-grade encryption, multi-signature authentication, biometric access, and support hardware wallet integration. Your private keys are encrypted and stored locally on your device—we never have access to them. Additionally, all transactions require your explicit approval.',
        },
        {
            question: 'Can I trade stocks and forex as well as crypto?',
            answer: 'Yes! Our platform supports trading across multiple asset classes. You can trade 500+ cryptocurrencies, major forex pairs, global stocks, and commodities—all from the same interface. This makes it easy to diversify your portfolio and take advantage of opportunities across different markets.',
        },
        {
            question: 'How does copy trading work?',
            answer: 'Copy trading allows you to automatically replicate the trades of experienced, successful traders. Simply browse our leaderboard of top performers, select traders whose strategy matches your goals, and allocate funds. When they make a trade, the same trade is executed in your account proportionally. You maintain full control and can stop copying at any time.',
        },
        {
            question: 'What are the fees for transactions and trading?',
            answer: 'Our fee structure is transparent and competitive. Basic transactions have network fees only (gas fees for blockchain). Trading fees start at 0.1% and decrease with higher subscription tiers and trading volume. Swaps have a small spread, and there are no hidden fees. Premium plans include reduced fees and even zero-fee options for certain transactions.',
        },
    ]

    const blogPosts: BlogPost[] = [
        {
            date: 'December 15, 2024',
            author: 'Marcus Chen',
            avatar: '/assets/images/avatar-1.webp',
            title: 'Ultimate Guide to Crypto Trading for Beginners',
            excerpt: 'Learn the fundamentals of cryptocurrency trading, from understanding market dynamics to executing your first trades with confidence and security.',
        },
        {
            date: 'December 18, 2024',
            author: 'Elena Rodriguez',
            avatar: '/assets/images/avatar-2.webp',
            title: 'How Copy Trading Can Boost Your Portfolio Returns',
            excerpt: 'Discover how copy trading works and why it\'s becoming the preferred method for both new and experienced traders to maximize their investment returns.',
        },
        {
            date: 'December 20, 2024',
            author: 'David Kim',
            avatar: '/assets/images/avatar-3.webp',
            title: 'DeFi Staking: Earn Passive Income on Your Crypto',
            excerpt: 'Unlock the power of decentralized finance. Learn how to stake your crypto assets and earn competitive yields while maintaining full control of your funds.',
        },
        {
            date: 'December 22, 2024',
            author: 'Sophia Anderson',
            avatar: '/assets/images/avatar-4.webp',
            title: 'Crypto Loans: Access Liquidity Without Selling Assets',
            excerpt: 'Need cash but don\'t want to sell your crypto? Learn how collateralized crypto loans work and how to leverage them for financial flexibility.',
        },
        {
            date: 'December 25, 2024',
            author: 'James Wilson',
            avatar: '/assets/images/avatar-5.webp',
            title: 'Multi-Asset Trading: Diversify Beyond Cryptocurrency',
            excerpt: 'Explore the advantages of trading crypto, forex, and stocks from one platform. Learn strategies to build a balanced, diversified portfolio across asset classes.',
        },
        {
            date: 'December 28, 2024',
            author: 'Olivia Martinez',
            avatar: '/assets/images/avatar-6.webp',
            title: 'Wallet Security: Protecting Your Digital Assets',
            excerpt: 'Master the essentials of crypto wallet security. From hardware wallets to multi-signature authentication, learn how to keep your investments safe from threats.',
        },
    ]

    const toggleAccordion = (index: number) => {
        activeAccordion.value = activeAccordion.value === index ? null : index
    }

    const setupScrollAnimation = () => {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px',
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view')
                }
            })
        }, observerOptions)

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el)
        })

        return observer
    }

    let scrollObserver: IntersectionObserver | null = null

    onMounted(() => {
        scrollObserver = setupScrollAnimation()
    })

    onUnmounted(() => {
        if (scrollObserver) scrollObserver.disconnect()
    })
</script>

<template>
    <Head title="Home" />
    <HomeHeader />

    <!-- Hero Section -->
    <section id="home" class="relative pt-48 lg:pt-56 pb-20 overflow-hidden">
        <div class="absolute inset-x-0 bottom-0 top-24 bg-[url(/assets/images/hero-bg.webp)] bg-top bg-no-repeat opacity-50" style="background-size: min(800px, 100%);"></div>

        <div class="container relative z-10 mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 mb-8 rounded-full bg-primary/10 border border-primary/20">
                    <Shield :size="16" class="text-primary" />
                    <span class="text-sm font-medium">Trusted by 500K+ traders worldwide</span>
                    <img src="/assets/images/users.webp" alt="users" class="h-5 w-auto">
                </div>

                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-6 leading-tight">
                    Your All-in-One<br>Digital Finance Wallet
                </h1>

                <p class="text-lg md:text-xl mb-10 text-muted-foreground max-w-2xl mx-auto">
                    Trade crypto, forex, and stocks. Send, receive, swap, invest, and copy top traders all from one secure wallet.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <TextLink :href="route('welcome')" class="px-4 py-3 text-lg font-semibold rounded-xl bg-primary/70 text-primary-foreground hover:bg-primary/90 transition-all inline-flex items-center justify-center gap-2">
                        <UserPlus :size="20" />
                        Get Started
                    </TextLink>

                    <TextLink :href="route('login')" class="px-4 py-3 text-lg font-semibold rounded-xl border border-border text-primary hover:bg-primary hover:text-primary-foreground transition-all inline-flex items-center justify-center gap-2">
                        <LinkIcon :size="20" />
                        Connect Wallet
                    </TextLink>
                </div>
            </div>
        </div>
    </section>

    <div class="w-full bg-background border-y border-border">
        <div class="container-fluid mx-auto">
            <Carousel v-bind="cryptoCarouselSettings" class="w-full flex items-center">
                <Slide v-for="crypto in cryptos" :key="crypto.symbol">
                    <div class="flex items-center justify-between w-full px-6 py-3 border-r border-border/40 hover:bg-muted/30 cursor-pointer group">
                        <div class="flex items-center gap-3 text-left">
                            <div class="w-8 h-8 rounded-full bg-primary/5 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/10">
                                <TrendingUp :size="14" class="text-primary" />
                            </div>
                            <div>
                                <div class="font-bold text-sm tracking-tight flex items-center gap-2">
                                    {{ crypto.symbol }}
                                    <span class="text-[10px] font-normal text-muted-foreground uppercase bg-muted px-1 rounded">CRYPTO</span>
                                </div>

                                <div class="text-xs text-muted-foreground font-medium truncate max-w-[100px]">
                                    {{ crypto.name }}
                                </div>
                            </div>
                        </div>

                        <div class="text-right pl-6">
                            <div class="font-mono font-medium text-sm">{{ crypto.price }}</div>
                            <div :class="crypto.positive ? 'text-emerald-500' : 'text-rose-500'" class="text-xs font-semibold flex items-center justify-end gap-1">
                                <span v-if="crypto.positive">▲</span>
                                <span v-else>▼</span>
                                {{ crypto.change }}
                            </div>
                        </div>
                    </div>
                </Slide>
            </Carousel>
        </div>
    </div>

    <!-- Highlights Section -->
    <section class="py-24 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-2 lg:order-1">
                    <img src="/assets/images/phone_demo_account_new.webp" alt="Trading interface" class="w-full max-w-md mx-auto lg:max-w-full rounded-2xl" loading="lazy">
                </div>

                <div class="order-1 lg:order-2">
                    <span class="text-primary text-sm font-bold uppercase tracking-wider">Multi-Asset Trading</span>
                    <h2 class="text-4xl lg:text-5xl font-bold mt-3 mb-6">
                        Trade Everything from One Wallet
                    </h2>

                    <p class="text-lg text-muted-foreground mb-8">
                        Access global markets with our comprehensive trading platform. From cryptocurrency to traditional assets, we've got you covered.
                    </p>

                    <ul class="space-y-4 mb-8">
                        <li class="flex gap-3">
                            <CheckCircle2 :size="24" class="text-primary flex-shrink-0" />
                            <span>Trade crypto, forex, and stocks in real-time</span>
                        </li>

                        <li class="flex gap-3">
                            <CheckCircle2 :size="24" class="text-primary flex-shrink-0" />
                            <span>Copy successful traders automatically</span>
                        </li>

                        <li class="flex gap-3">
                            <CheckCircle2 :size="24" class="text-primary flex-shrink-0" />
                            <span>Instant swaps with best market rates</span>
                        </li>

                        <li class="flex gap-3">
                            <CheckCircle2 :size="24" class="text-primary flex-shrink-0" />
                            <span>Bank-grade security with hardware wallet support</span>
                        </li>
                    </ul>

                    <TextLink :href="route('welcome')" class="px-8 py-4 text-lg font-semibold rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-all">
                        Start Trading Now
                    </TextLink>
                </div>
            </div>
        </div>
    </section>

    <!-- Supported Cryptocurrencies Showcase -->
    <section class="py-24 bg-muted/30 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold mb-6">
                    500+ Cryptocurrencies Supported
                </h2>
                <p class="text-lg text-muted-foreground">
                    Trade, swap, and invest in all major cryptocurrencies with instant execution and best market rates.
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-6 max-w-6xl mx-auto mb-12">
                <div v-for="crypto in cryptoShowcase" :key="crypto.name" class="flex flex-col items-center gap-3 p-6 rounded-xl bg-card border border-border hover:border-primary/50 transition-all group">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform" :style="{ backgroundColor: crypto.color + '20' }">
                        <img :src="crypto.icon" :alt="crypto.name" class="w-10 h-10 rounded-full" loading="lazy">
                    </div>
                    <span class="text-sm font-semibold text-center">{{ crypto.name }}</span>
                </div>
            </div>

            <div class="text-center">
                <TextLink :href="route('welcome')" class="px-8 py-4 text-lg font-semibold rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-all inline-flex items-center gap-2">
                    View All Supported Assets
                    <ArrowRight :size="20" />
                </TextLink>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold mb-6">
                    Everything You Need in One Wallet
                </h2>
                <p class="text-lg text-muted-foreground">
                    From basic transactions to advanced trading features, we provide all the tools you need to manage your digital assets.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="feature in features" :key="feature.title" class="p-8 rounded-2xl bg-card border hover:border-primary/50 transition-all group">
                    <component :is="feature.Icon" :size="40" class="text-primary mb-4 group-hover:scale-110 transition-transform" />
                    <h3 class="font-bold text-xl mb-3">{{ feature.title }}</h3>
                    <p class="text-muted-foreground mb-4">{{ feature.description }}</p>
                    <TextLink :href="route('welcome')" class="inline-flex items-center gap-2 font-medium text-primary hover:gap-3 transition-all">
                        Learn more <ArrowRight :size="16" />
                    </TextLink>
                </div>
            </div>
        </div>
    </section>

    <!-- Trusted Partners Section -->
    <section class="py-24 bg-muted/30 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl lg:text-4xl font-bold mb-4">Trusted by Leading Platforms</h2>
                <p class="text-muted-foreground">Integrated with the world's top crypto exchanges and DeFi protocols</p>
            </div>

            <Carousel v-bind="partnerCarouselSettings">
                <Slide v-for="partner in trustedPartners" :key="partner.name">
                    <div class="px-3">
                        <div class="bg-card border rounded-xl p-8 h-24 flex items-center justify-center hover:border-primary/50 transition-all">
                            <img :src="partner.logo" :alt="partner.name" class="max-h-12 max-w-full opacity-60 hover:opacity-100 transition-opacity" loading="lazy">
                        </div>
                    </div>
                </Slide>
            </Carousel>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="py-24 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold mb-6">Why Choose Our Platform?</h2>
                <p class="text-lg text-muted-foreground">
                    Experience the best-in-class features that set us apart from other crypto wallets and trading platforms.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 max-w-7xl mx-auto">
                <div class="text-center">
                    <div class="mb-6 flex justify-center">
                        <div class="w-24 h-24 rounded-2xl flex items-center justify-center">
                            <Shield :size="48" class="text-primary" />
                        </div>
                    </div>

                    <div class="text-3xl font-bold text-primary mb-3">99.9% Secure</div>
                    <h3 class="text-xl font-bold mb-3">Bank-Level Security</h3>
                    <p class="text-muted-foreground">Military-grade encryption, multi-sig authentication, and cold storage protection for your assets.</p>
                </div>
                <div class="text-center">
                    <div class="mb-6 flex justify-center">
                        <div class="w-24 h-24 rounded-2xl flex items-center justify-center">
                            <Zap :size="48" class="text-primary" />
                        </div>
                    </div>

                    <div class="text-3xl font-bold text-primary mb-3">&lt;1s Speed</div>
                    <h3 class="text-xl font-bold mb-3">Lightning Fast</h3>
                    <p class="text-muted-foreground">Execute trades in milliseconds with our high-performance infrastructure and instant settlement.</p>
                </div>
                <div class="text-center">
                    <div class="mb-6 flex justify-center">
                        <div class="w-24 h-24 rounded-2xl flex items-center justify-center">
                            <TrendingDown :size="48" class="text-primary" />
                        </div>
                    </div>

                    <div class="text-3xl font-bold text-primary mb-3">0.1% Fees</div>
                    <h3 class="text-xl font-bold mb-3">Lowest Fees</h3>
                    <p class="text-muted-foreground">Save more with our competitive fee structure. Trade with fees as low as 0.1% per transaction.</p>
                </div>
                <div class="text-center">
                    <div class="mb-6 flex justify-center">
                        <div class="w-24 h-24 rounded-2xl flex items-center justify-center">
                            <Headphones :size="48" class="text-primary" />
                        </div>
                    </div>

                    <div class="text-3xl font-bold text-primary mb-3">Always Available</div>
                    <h3 class="text-xl font-bold mb-3">24/7 Support</h3>
                    <p class="text-muted-foreground">Round-the-clock expert assistance available whenever you need help with your transactions.</p>
                </div>
            </div>

            <div class="mt-16 text-center">
                <TextLink :href="route('welcome')" class="px-8 py-4 text-lg font-semibold rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-all inline-flex items-center gap-2">
                    Get Started Today
                    <ArrowRight :size="20" />
                </TextLink>
            </div>
        </div>
    </section>

    <!-- Stats Section with Crypto Background -->
    <section class="py-24 bg-muted/30 animate-on-scroll relative overflow-hidden">
        <div class="container relative z-10 mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="flex justify-center mb-4">
                        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                            <Users :size="32" class="text-primary" />
                        </div>
                    </div>
                    <div class="text-5xl font-bold mb-2">500K+</div>
                    <div class="text-muted-foreground">Active Users</div>
                </div>

                <div>
                    <div class="flex justify-center mb-4">
                        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                            <TrendingUp :size="32" class="text-primary" />
                        </div>
                    </div>
                    <div class="text-5xl font-bold mb-2">$2.5B+</div>
                    <div class="text-muted-foreground">Trading Volume</div>
                </div>

                <div>
                    <div class="flex justify-center mb-4">
                        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                            <Globe :size="32" class="text-primary" />
                        </div>
                    </div>
                    <div class="text-5xl font-bold mb-2">150+</div>
                    <div class="text-muted-foreground">Countries Served</div>
                </div>

                <div>
                    <div class="flex justify-center mb-4">
                        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                            <Zap :size="32" class="text-primary" />
                        </div>
                    </div>
                    <div class="text-5xl font-bold mb-2">99.9%</div>
                    <div class="text-muted-foreground">Uptime</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Integration Section -->
    <section class="py-24 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="text-7xl font-bold mb-3">300+</div>
                    <h2 class="text-4xl font-bold mb-6">DeFi & DApp Integrations</h2>
                    <p class="text-lg text-muted-foreground mb-8">
                        Connect to your favorite decentralized apps, NFT marketplaces, and DeFi protocols with seamless WalletConnect integration.
                    </p>

                    <TextLink :href="route('welcome')" class="px-8 py-4 text-lg font-semibold rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-all">
                        Browse Integrations
                    </TextLink>
                </div>

                <div>
                    <img src="/assets/images/deposit_2.webp" alt="DeFi integrations" class="w-full max-w-2xl mx-auto rounded-2xl" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <!-- Security Showcase Section -->
    <section class="py-24 bg-muted/30 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-2 lg:order-1">
                    <img src="/assets/images/home_img_desktop.webp" alt="Security features" class="w-full max-w-md mx-auto lg:max-w-full rounded-2xl" loading="lazy">
                </div>
                <div class="order-1 lg:order-2">
                    <div class="inline-flex items-center gap-2 px-4 py-2 mb-6 rounded-full bg-primary/10 border border-primary/20">
                        <Lock :size="16" class="text-primary" />
                        <span class="text-sm font-medium">Military-Grade Security</span>
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-bold mb-6">
                        Your Assets, Fully Protected
                    </h2>
                    <p class="text-lg text-muted-foreground mb-8">
                        We employ the highest security standards in the industry to ensure your digital assets are always safe and secure.
                    </p>
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <Key :size="24" class="text-primary" />
                            </div>
                            <div>
                                <h4 class="font-bold mb-1">Multi-Signature Wallets</h4>
                                <p class="text-sm text-muted-foreground">Require multiple approvals for enhanced security on large transactions</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <HardDrive :size="24" class="text-primary" />
                            </div>
                            <div>
                                <h4 class="font-bold mb-1">Cold Storage Integration</h4>
                                <p class="text-sm text-muted-foreground">Keep majority of funds offline in secure cold storage vaults</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <Fingerprint :size="24" class="text-primary" />
                            </div>
                            <div>
                                <h4 class="font-bold mb-1">Biometric Authentication</h4>
                                <p class="text-sm text-muted-foreground">Face ID and fingerprint recognition for secure access</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-24 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold mb-6">Frequently Asked Questions</h2>
                <p class="text-lg text-muted-foreground">
                    Everything you need to know about our crypto wallet and trading platform.
                </p>
            </div>

            <div class="max-w-4xl mx-auto space-y-4">
                <div v-for="(item, index) in faqItems" :key="index" class="border rounded-xl overflow-hidden bg-card">
                    <button @click="toggleAccordion(index)" class="w-full px-6 py-5 flex justify-between items-center text-left hover:bg-muted/50">
                        <span class="text-lg font-medium pr-4">{{ item.question }}</span>
                        <ChevronDown :size="24" class="text-muted-foreground transition-transform flex-shrink-0" :class="{ 'rotate-180': activeAccordion === index }" />
                    </button>

                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-96 opacity-100"
                        leave-active-class="transition-all duration-300 ease-in"
                        leave-from-class="max-h-96 opacity-100"
                        leave-to-class="max-h-0 opacity-0">
                        <div v-show="activeAccordion === index" class="overflow-hidden">
                            <div class="px-6 pb-5 text-muted-foreground">{{ item.answer }}</div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="py-24 bg-muted/30 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <p class="text-2xl lg:text-3xl mb-10 italic leading-relaxed">
                    "This wallet changed how I trade. Having crypto, forex, and stocks in one place with copy trading features has grown my portfolio by 240% this year. The security and ease of use are unmatched."
                </p>
                <div class="w-20 h-px bg-border mx-auto mb-6"></div>
                <div>
                    <div class="font-bold text-lg mb-1">Sarah Chen</div>
                    <div class="text-sm text-muted-foreground">Professional Trader & Investor</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section id="blogs" class="py-24 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <h2 class="text-4xl lg:text-5xl font-bold">Latest Insights & Guides</h2>
            </div>

            <Carousel v-bind="blogCarouselSettings">
                <Slide v-for="post in blogPosts" :key="post.title">
                    <div class="px-3">
                        <div class="bg-card border rounded-2xl p-8 hover:border-primary/50 transition-all h-full flex flex-col">
                            <div class="mb-6">
                                <div class="text-xs text-muted-foreground mb-3">{{ post.date }}</div>
                                <div class="flex items-center gap-3">
                                    <img :src="post.avatar" :alt="post.author" class="w-10 h-10 rounded-full" loading="lazy">
                                    <div class="text-sm font-semibold">{{ post.author }}</div>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold mb-4">{{ post.title }}</h3>
                            <p class="text-muted-foreground mb-6 flex-grow">{{ post.excerpt }}</p>
                            <TextLink :href="route('welcome')" class="font-semibold text-primary hover:gap-2 inline-flex items-center gap-1 transition-all">
                                Read more <ArrowRight :size="16" />
                            </TextLink>
                        </div>
                    </div>
                </Slide>
            </Carousel>
        </div>
    </section>

    <!-- Support Section -->
    <section class="py-24 bg-muted/30 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <img src="/assets/images/section-stocks-bg_new.webp" alt="Customer support" class="w-full max-w-md lg:max-w-full rounded-2xl mx-auto" loading="lazy">
                </div>

                <div>
                    <h2 class="text-4xl lg:text-5xl font-bold mb-6">Expert Support When You Need It</h2>
                    <p class="text-lg text-muted-foreground mb-8">
                        Our dedicated support team is available 24/7 to help you with any questions, from basic setup to advanced trading strategies.
                    </p>
                    <div class="flex flex-wrap items-center gap-6 mb-8">
                        <TextLink :href="route('welcome')" class="hover:text-primary font-medium inline-flex items-center gap-2">
                            <FileQuestion :size="22" /> Knowledge Base
                        </TextLink>

                        <span class="text-border">|</span>

                        <TextLink :href="route('welcome')" class="hover:text-primary font-medium inline-flex items-center gap-2">
                            <PlayCircle :size="22" /> Video Tutorials
                        </TextLink>
                    </div>

                    <TextLink href="#contact" class="px-8 py-4 text-lg font-semibold rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-all inline-flex items-center gap-2">
                        <MessageCircle :size="20" />
                        Contact Support
                    </TextLink>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-24 animate-on-scroll">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold mb-6">Get in Touch</h2>
                <p class="text-lg text-muted-foreground">
                    Have questions about our platform? Want to become a partner? We'd love to hear from you.
                </p>
            </div>

            <form class="max-w-2xl mx-auto">
                <div class="space-y-5">
                    <input type="text" placeholder="Your name" class="w-full px-5 py-4 rounded-xl border bg-background focus:border-primary input-crypto transition-all">
                    <input type="email" placeholder="Email address" class="w-full px-5 py-4 rounded-xl border bg-background focus:border-primary input-crypto transition-all">
                    <textarea placeholder="Your message" class="w-full px-5 py-4 rounded-xl border bg-background focus:border-primary input-crypto transition-all h-40 resize-none"></textarea>
                </div>

                <button type="submit" class="mt-6 w-full sm:w-auto px-8 py-4 cursor-pointer text-lg font-semibold rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-all">
                    Send Message
                </button>
            </form>
        </div>
    </section>

    <HomeFooter />
</template>

<style scoped>
    /* Scroll animations */
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }

    .animate-on-scroll.in-view {
        opacity: 1;
        transform: translateY(0);
    }

    /* Carousel customization */
    :deep(.carousel__prev),
    :deep(.carousel__next) {
        background: hsl(var(--muted));
        border-radius: 50%;
        width: 40px;
        height: 40px;
        transition: all 0.3s ease;
    }

    :deep(.carousel__prev:hover),
    :deep(.carousel__next:hover) {
        background: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }

    :deep(.carousel__icon) {
        fill: currentColor;
    }

    #home {
        background: radial-gradient(
            circle,
            hsl(var(--background)) 0%,
            hsl(var(--background)) 50%,
            hsl(var(--primary) / 0.15) 100%
        );
    }
</style>
