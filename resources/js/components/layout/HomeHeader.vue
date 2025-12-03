<script setup lang="ts">
    import { computed, ref, onMounted, onUnmounted } from 'vue'
    import {
        ChevronDown,
        Menu,
        Monitor,
        Moon,
        Sun,
        X,
        Languages,
        Check,
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue'
    import { useAppearance } from '@/composables/useAppearance'
    import { useLanguages } from '@/composables/useLanguages'
    import { usePage } from '@inertiajs/vue3';
    import SiteLogo from '@/components/SiteLogo.vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';

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
    const isTranslateOpen = ref(false)
    const searchQuery = ref('')
    const isChangingLanguage = ref(false)
    const { appearance, updateAppearance } = useAppearance()
    const { languages, defaultLanguage: defaultLang } = useLanguages()

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

    const defaultLanguage = ref(defaultLang)
    const currentLanguage = ref('en')

    const openWhatsApp = () => {
        window.open(`https://wa.me/${page.props.phone}`, '_blank');
    }

    const currentYear = computed(() => new Date().getFullYear())

    const currentIcon = computed(() => {
        return appearanceTabs.find(tab => tab.value === appearance.value)?.Icon ?? Sun
    })

    const filteredLanguages = computed(() => {
        if (!searchQuery.value.trim()) {
            return languages
        }
        const query = searchQuery.value.toLowerCase()
        return languages.filter(lang =>
            lang.name.toLowerCase().includes(query) ||
            lang.nativeName.toLowerCase().includes(query) ||
            lang.code.toLowerCase().includes(query)
        )
    })

    const getCurrentLanguage = () => {
        const hash = window.location.hash;
        if (hash && hash.includes('googtrans')) {
            const match = hash.match(/googtrans\([^|]+\|([^)]+)\)/);
            if (match && match[1]) {
                return match[1];
            }
        }

        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const parts = cookie.trim().split('=');
            if (parts[0] === 'googtrans') {
                const value = decodeURIComponent(parts[1]);
                const langParts = value.split('/');
                if (langParts.length >= 3 && langParts[2]) {
                    return langParts[2];
                }
            }
        }

        return 'en';
    }

    const loadGoogleTranslateScript = () => {
        if (document.getElementById('google-translate-script')) return;

        const script = document.createElement('script');
        script.id = 'google-translate-script';
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.head.appendChild(script);

        window.googleTranslateElementInit = () => {
            new window.google.translate.TranslateElement(
                {
                    pageLanguage: defaultLanguage.value,
                    includedLanguages: languages.map(lang => lang.code).join(','),
                    layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE,
                    autoDisplay: false,
                },
                'google_translate_element'
            );
        };
    };

    const changeLanguage = (lang: string) => {
        if (isChangingLanguage.value) return;

        isChangingLanguage.value = true;
        isTranslateOpen.value = false;

        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname;

        if (lang === 'en') {
            currentLanguage.value = 'en';
            location.reload();
            return;
        }

        const cookieValue = `/en/${lang}`;
        document.cookie = `googtrans=${cookieValue}; path=/`;
        document.cookie = `googtrans=${cookieValue}; path=/; domain=${window.location.hostname}`;

        currentLanguage.value = lang;

        const googleTranslateComboBox = document.querySelector('.goog-te-combo') as HTMLSelectElement;
        if (googleTranslateComboBox) {
            googleTranslateComboBox.value = lang;
            googleTranslateComboBox.dispatchEvent(new Event('change'));

            setTimeout(() => {
                location.reload();
            }, 300);
        } else {
            location.reload();
        }
    };

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

    const toggleTranslateWidget = () => {
        isTranslateOpen.value = !isTranslateOpen.value;
        if (!isTranslateOpen.value) {
            searchQuery.value = ''
        }
    }

    const closeTranslateWidget = () => {
        isTranslateOpen.value = false;
        searchQuery.value = ''
    }

    const toggleAppearance = () => {
        if (isMobileMenuOpen.value) {
            closeMenu()
            setTimeout(() => {
                const currentIndex = appearanceTabs.findIndex(tab => tab.value === appearance.value)
                const nextIndex = (currentIndex + 1) % appearanceTabs.length
                updateAppearance(appearanceTabs[nextIndex].value)
            }, 250)
            return
        }
        const currentIndex = appearanceTabs.findIndex(tab => tab.value === appearance.value)
        const nextIndex = (currentIndex + 1) % appearanceTabs.length
        updateAppearance(appearanceTabs[nextIndex].value)
    }

    onMounted(() => {
        currentLanguage.value = getCurrentLanguage();
        loadGoogleTranslateScript();
        window.addEventListener('scroll', handleScroll);

        const checkTranslateReady = setInterval(() => {
            const googleTranslateComboBox = document.querySelector('.goog-te-combo') as HTMLSelectElement;
            if (googleTranslateComboBox) {
                clearInterval(checkTranslateReady);

                const detectedLang = getCurrentLanguage();
                if (detectedLang !== 'en' && googleTranslateComboBox.value !== detectedLang) {
                    googleTranslateComboBox.value = detectedLang;
                    googleTranslateComboBox.dispatchEvent(new Event('change'));
                }
            }
        }, 100);

        setTimeout(() => clearInterval(checkTranslateReady), 5000);
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
                            <svg class="w-5 h-5 text-primary-foreground group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                <path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"></path>
                            </svg>
                            <span>WhatsApp</span>
                        </button>

                        <button type="button" @click="toggleAppearance" class="p-2 rounded-xl border border-border bg-card cursor-pointer hover:bg-secondary/50 duration-200" :title="`Switch to ${appearanceTabs[(appearanceTabs.findIndex(t => t.value === appearance) + 1) % appearanceTabs.length].label} mode`">
                            <component :is="currentIcon" class="w-5 h-5 text-card-foreground" />
                        </button>
                    </div>

                    <div class="flex items-center gap-3 lg:hidden">
                        <button type="button" @click="toggleAppearance" class="p-2 rounded-xl border border-border bg-transparent cursor-pointer hover:bg-secondary/50 duration-200" :title="`Switch to ${appearanceTabs[(appearanceTabs.findIndex(t => t.value === appearance) + 1) % appearanceTabs.length].label} mode`">
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

                            <div class="mt-4">
                                <button @click="openWhatsApp" class="w-full py-4 rounded-xl bg-primary text-primary-foreground font-bold flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">
                                    <svg class="w-5 h-5 text-primary-foreground group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"></path>
                                    </svg>
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

    <QuickActionModal
        :is-open="isTranslateOpen"
        title="Change Language"
        subtitle="Select your preferred language for the interface"
        @close="closeTranslateWidget">

        <div class="space-y-4">
            <!-- Search Bar -->
            <div class="relative">
                <input
                    type="text"
                    placeholder="Search languages..."
                    v-model="searchQuery"
                    class="w-full px-4 py-2.5 pl-10 bg-secondary/50 border border-border rounded-xl text-sm transition-all input-crypto"
                />
                <Languages :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            </div>

            <!-- Languages List -->
            <div v-if="filteredLanguages.length > 0" class="space-y-2">
                <div
                    v-for="language in filteredLanguages"
                    :key="language.code"
                    @click="() => !isChangingLanguage && changeLanguage(language.code)"
                    :class="[
                    'group flex items-center justify-between p-3 rounded-xl transition-all duration-200',
                    isChangingLanguage
                        ? 'cursor-not-allowed opacity-50'
                        : 'cursor-pointer',
                    currentLanguage === language.code
                        ? 'bg-primary/10 border-2 border-primary/30'
                        : 'border border-border hover:bg-secondary/50 hover:border-border/50'
                ]">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="relative flex-shrink-0">
                            <img
                                class="w-10 h-7 rounded-md object-cover"
                                :src="`https://flagcdn.com/${language.flag}.svg`"
                                :alt="`${language.name} flag`"
                                loading="lazy"
                                @error="(e) => (e.target as HTMLImageElement).style.display = 'none'" />
                            <div
                                v-if="currentLanguage === language.code && !isChangingLanguage"
                                class="absolute -top-1 -right-1 w-4 h-4 bg-primary rounded-full flex items-center justify-center">
                                <Check :size="10" class="text-primary-foreground" />
                            </div>
                            <div
                                v-if="isChangingLanguage && currentLanguage === language.code"
                                class="absolute -top-1 -right-1 w-4 h-4 bg-primary rounded-full flex items-center justify-center">
                                <div class="w-2 h-2 border border-primary-foreground border-t-transparent rounded-full animate-spin"></div>
                            </div>
                        </div>
                        <div class="flex flex-col min-w-0 flex-1">
                        <span class="font-semibold text-sm truncate" :class="currentLanguage === language.code ? 'text-primary' : 'text-foreground'">
                            {{ language.nativeName }}
                        </span>
                            <span class="text-xs text-muted-foreground truncate">{{ language.name }}</span>
                        </div>
                    </div>
                    <ChevronDown
                        :size="16"
                        :class="[
                        'flex-shrink-0 -rotate-90 transition-all duration-200',
                        currentLanguage === language.code
                            ? 'text-primary opacity-100'
                            : 'text-muted-foreground opacity-0 group-hover:opacity-50'
                    ]" />
                </div>
            </div>

            <!-- No Results -->
            <div v-else class="py-12 text-center">
                <Languages :size="48" class="mx-auto text-muted-foreground/30 mb-3" />
                <p class="text-sm text-muted-foreground">No languages found</p>
                <p class="text-xs text-muted-foreground/70 mt-1">Try a different search term</p>
            </div>
        </div>
    </QuickActionModal>

    <!-- Floating Buttons -->
    <div v-if="!isTranslateOpen" class="fixed left-4 bottom-4 md:left-6 md:bottom-6 z-[9999] flex flex-col gap-3">
        <!-- WhatsApp Button -->
        <button @click="openWhatsApp" class="group relative w-12 h-12 rounded-full bg-primary flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 cursor-pointer shadow-lg hover:shadow-xl" aria-label="Chat on WhatsApp">
            <svg class="w-6 h-6 text-primary-foreground group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                <path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"></path>
            </svg>
        </button>

        <!-- Translate Button -->
        <button
            @click="toggleTranslateWidget"
            :class="[
                'group relative w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 cursor-pointer overflow-hidden shadow-lg hover:shadow-xl',
                isTranslateOpen ? 'bg-primary' : 'bg-primary'
            ]"
            :aria-label="isTranslateOpen ? 'Close translate widget' : 'Open translate widget'"
            :aria-expanded="isTranslateOpen">
            <span v-if="!isTranslateOpen" class="absolute inset-0 rounded-full bg-primary"></span>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 rotate-90 scale-50"
                enter-to-class="opacity-100 rotate-0 scale-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 rotate-0 scale-100"
                leave-to-class="opacity-0 rotate-90 scale-50"
                mode="out-in">
                <X v-if="isTranslateOpen" :size="24" class="text-primary-foreground relative z-10" />
                <Languages v-else :size="24" class="text-primary-foreground relative z-10 group-hover:scale-110 transition-transform" />
            </Transition>
        </button>
    </div>

    <!-- Hidden Google Translate Element -->
    <div id="google_translate_element" class="hidden"></div>
</template>

<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .goog-logo-link {
        display: none !important;
    }

    .goog-te-gadget {
        color: transparent !important;
    }

    .goog-te-gadget .goog-te-combo {
        color: #b5b5b5 !important;
    }

    .goog-te-banner-frame.skiptranslate {
        display: none !important;
    }

    .VIpgJd-ZVi9od-l4eHX-hSRGPd,
    .VIpgJd-ZVi9od-l4eHX-hSRGPd:link,
    .VIpgJd-ZVi9od-l4eHX-hSRGPd:visited,
    .VIpgJd-ZVi9od-l4eHX-hSRGPd:hover,
    .VIpgJd-ZVi9od-l4eHX-hSRGPd:active {
        font-size: 12px;
        font-weight: bold;
        color: #444;
        text-decoration: none;
        display: none;
    }

    .goog-te-gadget img {
        display: none !important;
    }

    body > .skiptranslate {
        display: none;
    }

    body {
        top: 0 !important;
    }
</style>
