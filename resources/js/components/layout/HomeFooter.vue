<script setup lang="ts">
    import { computed } from 'vue'
    import TextLink from '@/components/TextLink.vue';
    import { Rotate3DIcon } from 'lucide-vue-next';

    interface FooterLink {
        label: string
        href: string
    }

    interface SocialLink {
        label: string
        href: string
        icon: string
    }

    const footerLinks: FooterLink[] = [
        { label: 'About', href: '#about' },
        { label: 'Features', href: '#features' },
        { label: 'Pricing', href: '#pricing' },
        { label: 'Blog', href: '#blogs' },
        { label: 'Support', href: '#contact' },
        { label: 'Terms', href: '#terms' },
        { label: 'Privacy', href: '#privacy' },
    ]

    const socialLinks: SocialLink[] = [
        {
            label: 'Twitter',
            href: 'https://twitter.com',
            icon: 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z'
        },
        {
            label: 'Telegram',
            href: 'https://telegram.org',
            icon: 'M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z'
        },
        {
            label: 'GitHub',
            href: 'https://github.com',
            icon: 'M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z'
        },
    ]

    const currentYear = computed(() => new Date().getFullYear())
</script>

<template>
    <footer class="bg-card border-t border-border">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
                <!-- Logo Section -->
                <TextLink :href="route('home')" class="justify-center md:justify-start flex-shrink-0 z-50 flex items-center gap-2.5 group">
                    <div class="relative">
                        <div class="absolute -inset-1 rounded-full bg-primary/30 blur-sm group-hover:bg-primary/50 transition-all duration-500"></div>
                        <div class="relative flex items-center justify-center w-11 h-11 bg-card border-2 border-primary/20 rounded-full shadow-sm overflow-hidden group-hover:border-primary duration-300">
                            <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-primary/10 rounded-full blur-sm"></div>
                            <Rotate3DIcon class="relative w-6 h-6 text-primary transition-transform duration-500 group-hover:rotate-180" />
                        </div>
                    </div>

                    <div class="flex flex-col justify-center">
                        <span class="font-black text-2xl tracking-tighter text-secondary-foreground group-hover:tracking-normal transition-all duration-300">
                            coin<span class="font-light text-primary">pay.</span>
                        </span>
                    </div>
                </TextLink>

                <!-- Navigation Links -->
                <nav class="flex justify-center" aria-label="Footer navigation">
                    <ul class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm">
                        <li v-for="link in footerLinks" :key="link.label">
                            <TextLink :href="link.href" class="text-muted-foreground hover:text-primary duration-200">
                                {{ link.label }}
                            </TextLink>
                        </li>
                    </ul>
                </nav>

                <!-- Social Links & Copyright -->
                <div class="flex flex-col items-center md:items-end gap-4">
                    <!-- Social Icons -->
                    <div class="flex gap-4" role="list" aria-label="Social media links">
                        <TextLink v-for="social in socialLinks"
                            :key="social.label"
                            :href="social.href"
                            :aria-label="social.label"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-muted-foreground hover:text-primary duration-200">
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path :d="social.icon" />
                            </svg>
                        </TextLink>
                    </div>

                    <!-- Copyright -->
                    <p class="text-sm text-muted-foreground text-center md:text-right">
                        © {{ currentYear }} CryptoWallet. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
</template>
