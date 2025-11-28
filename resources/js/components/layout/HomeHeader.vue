<script setup lang="ts">
    import { computed, ref, onMounted, onUnmounted } from 'vue'
    import { ChevronDown, Menu, MessageCircle, Monitor, Moon, Sun, X } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue'
    import { useAppearance } from '@/composables/useAppearance'
    import { usePage } from '@inertiajs/vue3';
    import SiteLogo from '@/components/SiteLogo.vue';
    import GoogleTranslateSelect from '@google-translate-select/vue3';

    const page = usePage();

    interface NavItem {
        label: string
        href: string
    }

    interface AppearanceTab {
        value: 'light' | 'dark' | 'system'
        Icon: any
        label: string
    }

    const isMobileMenuOpen = ref(false)
    const isScrolled = ref(false)
    const { appearance, updateAppearance } = useAppearance()

    const navItems: NavItem[] = [
        { label: 'Home', href: '#home' },
        { label: 'Features', href: '#features' },
        { label: 'FAQ\'s', href: '#faq' },
        { label: 'Blog', href: '#blogs' },
        { label: 'Contact', href: '#contact' },
    ]

    const appearanceTabs: AppearanceTab[] = [
        { value: 'light', Icon: Sun, label: 'Light' },
        { value: 'dark', Icon: Moon, label: 'Dark' },
        { value: 'system', Icon: Monitor, label: 'System' },
    ]

    const openWhatsApp = () => {
        window.open(`https://wa.me/${page.props.phone}`, '_blank');
    }

    const currentYear = computed(() => new Date().getFullYear())

    const currentIcon = computed(() => {
        return appearanceTabs.find(tab => tab.value === appearance.value)?.Icon ?? Sun
    })

    const handleScroll = () => {
        isScrolled.value = window.scrollY > 20
    }

    const toggleMobileMenu = () => {
        isMobileMenuOpen.value = !isMobileMenuOpen.value;
        if (isMobileMenuOpen.value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    };

    const closeMenu = () => {
        isMobileMenuOpen.value = false;
        document.body.style.overflow = '';
    }

    const handleGoogleTranslateSelect = (language: any) => {
        console.log(language)
    }

    const toggleAppearance = () => {
        const currentIndex = appearanceTabs.findIndex(tab => tab.value === appearance.value)
        const nextIndex = (currentIndex + 1) % appearanceTabs.length
        updateAppearance(appearanceTabs[nextIndex].value)
    }

    onMounted(() => {
        window.addEventListener('scroll', handleScroll)
    })

    onUnmounted(() => {
        document.body.style.overflow = '';
        window.removeEventListener('scroll', handleScroll)
    })
</script>

<template>
    <header
        :class="[
            'fixed top-0 left-0 right-0 z-50 transition-all duration-300 border-b',
            isScrolled
                ? 'bg-background/80 backdrop-blur-md border-border'
                : 'bg-transparent border-transparent'
        ]">
        <nav>
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <SiteLogo class="flex-shrink-0 flex items-center gap-2.5 group" />

                    <div class="hidden lg:flex flex-1 items-center justify-center">
                        <ul class="flex items-center gap-6">
                            <li v-for="(item, index) in navItems" :key="item.label">
                                <TextLink :href="item.href" :class="[
                                    'font-medium text-sm duration-200',
                                    index === 0 ? 'text-foreground hover:text-primary' : 'text-muted-foreground hover:text-primary'
                                ]">
                                    {{ item.label }}
                                </TextLink>
                            </li>
                        </ul>
                    </div>

                    <div class="hidden lg:flex items-center gap-3">
                        <button @click="openWhatsApp" class="flex items-center gap-2 px-3 py-2 rounded-xl border border-border bg-card cursor-pointer hover:bg-secondary/50 duration-200 text-sm font-medium mr-2" title="Chat on WhatsApp">
                            <MessageCircle :size="16" />
                            <span>WhatsApp</span>
                        </button>

                        <button type="button" @click="toggleAppearance" class="p-2 rounded-xl border border-border bg-card cursor-pointer hover:bg-secondary/50 duration-200" :title="`Switch to ${appearanceTabs[(appearanceTabs.findIndex(t => t.value === appearance) + 1) % appearanceTabs.length].label} mode`">
                            <component :is="currentIcon" class="w-5 h-5 text-card-foreground" />
                        </button>
                    </div>

                    <div class="flex items-center gap-3 lg:hidden">
                        <button type="button" @click="toggleAppearance" class="p-2 rounded-xl border border-border bg-transparentcursor-pointer hover:bg-secondary/50 duration-200" :title="`Switch to ${appearanceTabs[(appearanceTabs.findIndex(t => t.value === appearance) + 1) % appearanceTabs.length].label} mode`">
                            <component :is="currentIcon" :size="22" class="text-card-foreground" />
                        </button>

                        <button type="button" @click="toggleMobileMenu" id="hamburger" class="p-2 rounded-xl border border-border bg-transparent cursor-pointer duration-200 relative z-50" :aria-label="isMobileMenuOpen ? 'Close menu' : 'Open menu'" :aria-expanded="isMobileMenuOpen">
                            <X v-if="isMobileMenuOpen" :size="22" class="text-foreground" />
                            <Menu v-else :size="22" class="text-foreground" />
                        </button>
                    </div>
                </div>

                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-[-10px]"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-[-10px]"
                >
                    <div
                        v-show="isMobileMenuOpen"
                        id="mainNav"
                        class="lg:hidden absolute left-0 right-0 top-full border-t border-border bg-background backdrop-blur-xl h-[calc(100vh-65px)] overflow-y-auto">
                        <div class="container mx-auto px-4 py-6 flex flex-col gap-6">
                            <ul class="flex flex-col gap-2">
                                <li v-for="(item, index) in navItems" :key="item.label">
                                    <TextLink
                                        :href="item.href"
                                        :class="[
                                            'flex items-center justify-between py-4 px-4 rounded-xl font-semibold text-lg duration-200',
                                            index === 0
                                              ? 'bg-primary/5 text-primary'
                                              : 'text-muted-foreground hover:text-foreground hover:bg-secondary/50'
                                          ]"
                                        @click="closeMenu">
                                        {{ item.label }}
                                        <ChevronDown :size="16" class="-rotate-90 opacity-50" />
                                    </TextLink>
                                </li>
                            </ul>

                            <hr class="border-border/50">

                            <div class="space-y-4">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground px-4">Preferences</h3>
                                <button
                                    @click="toggleAppearance"
                                    class="w-full flex items-center justify-between p-4 rounded-xl bg-card border border-border">
                                    <span class="flex items-center gap-3 font-medium">
                                        <component :is="currentIcon" :size="18" />
                                        Appearance
                                    </span>
                                    <span class="text-sm text-muted-foreground capitalize">{{ appearance }}</span>
                                </button>

                                <GoogleTranslateSelect
                                    default-language-code="en"
                                    default-page-language-code="en"
                                    :fetch-browser-language="false"
                                    trigger="click"
                                    @select="handleGoogleTranslateSelect"
                                />
                            </div>

                            <div class="mt-4">
                                <button @click="openWhatsApp" class="w-full py-4 rounded-xl bg-primary text-primary-foreground font-bold flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">
                                    <MessageCircle :size="20" class="text-primary-foreground" />
                                    Chat on WhatsApp
                                </button>
                            </div>

                            <div class="pb-8 text-center">
                                <p class="text-xs text-muted-foreground">© {{ currentYear }} {{ page.props.name }}. All rights reserved.</p>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </nav>
    </header>
</template>
