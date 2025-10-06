<script setup lang="ts">
    import { computed, ref, onMounted, watchEffect } from 'vue';
    import {
        Menu,
        Search,
        X,
        CandlestickChart,
        Wallet,
        LifeBuoy,
        Settings,
        LogOut,
        Send,
        Download,
        Repeat,
        BellIcon,
        HomeIcon,
        ChevronRight,
        Moon,
        Globe,
        Shield,
        CreditCard,
        History,
        HelpCircle,
        FileText,
        Mail,
        Copy,
        Check
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import { usePage } from '@inertiajs/vue3';

    const page = usePage();
    const searchQuery = ref('');
    const isAccountModalOpen = ref(false);
    const isNotificationsModalOpen = ref(false);
    const emailCopied = ref(false);

    const isDarkMode = ref(false);

    const toggleTheme = () => {
        isDarkMode.value = !isDarkMode.value;
        localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light');
    };

    watchEffect(() => {
        const root = document.documentElement;
        if (isDarkMode.value) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
    });

    onMounted(() => {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            isDarkMode.value = savedTheme === 'dark';
        } else {
            isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
    });


    const notificationCount = computed(() => page.props.auth.notification_count);

    const user = computed(() => page.props.auth.user);

    // Computes user's initials as a fallback for the avatar
    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const navigation = [
        { name: "Home", href: "user.dashboard", icon: HomeIcon },
        { name: "Send", href: "user.send.index", icon: Send },
        { name: "Receive", href: "user.receive.index", icon: Download },
        { name: "Swap", href: "user.swap.index", icon: Repeat },
        { name: "Trade", href: "user.trade.index", icon: CandlestickChart },
        { name: "Wallet Connect", href: "user.connect.index", icon: Wallet },
    ];

    const accountLinks = [
        { name: "KYC Verification", href: "user.kyc.index", icon: LifeBuoy, description: "Verify your identity" },
        { name: "Transaction History", href: "user.dashboard", icon: History, description: "View all transactions" },
        { name: "Security", href: "user.dashboard", icon: Shield, description: "Password & 2FA" },
        { name: "Payment Methods", href: "user.dashboard", icon: CreditCard, description: "Manage payment options" },
    ];

    const supportLinks = [
        { name: "Help Center", href: "user.dashboard", icon: HelpCircle },
        { name: "Terms of Service", href: "user.dashboard", icon: FileText },
        { name: "Contact Support", href: "user.dashboard", icon: Mail },
    ];

    const kycStatus = computed(() => {
        const status = page.props.auth.user?.kyc?.status;

        if (status === 'approved') return 'Verified';
        if (status === 'pending') return 'Pending';
        if (status === 'rejected') return 'Rejected';

        return 'Unverified';
    });

    const kycStatusClass = computed(() => {
        const baseClasses = 'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border';

        switch (kycStatus.value) {
            case 'Verified':
                return `${baseClasses} bg-green-500/20 text-green-400 border-green-500/30`;
            case 'Pending':
                return `${baseClasses} bg-yellow-500/20 text-yellow-400 border-yellow-500/30`;
            case 'Rejected':
            case 'Unverified':
            default:
                return `${baseClasses} bg-red-500/20 text-red-400 border-red-500/30`;
        }
    });

    const clearSearch = () => {
        searchQuery.value = '';
    };

    const closeAccountModal = (event: MouseEvent) => {
        if (event.target === event.currentTarget) {
            isAccountModalOpen.value = false;
        }
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };

    const copyEmail = async () => {
        if (user.value?.email) {
            await navigator.clipboard.writeText(user.value.email);
            emailCopied.value = true;
            setTimeout(() => {
                emailCopied.value = false;
            }, 2000);
        }
    };
</script>

<template>
    <div class="lg:hidden top-0 inset-x-0 z-40 px-4 py-4 flex items-center gap-3 shadow-md bg-background">
        <button class="w-8 h-8 flex items-center justify-center rounded-md bg-accent text-accent-foreground" aria-label="Menu" @click="isAccountModalOpen = true">
            <Menu class="w-7 h-7" />
        </button>

        <div class="flex items-center flex-1 bg-sidebar-accent rounded-xl px-3 py-3">
            <Search class="w-3.5 h-3.5 text-sidebar-foreground/60" />
            <input id="globalSearch" v-model="searchQuery" type="text" class="flex-1 bg-transparent border-0 focus:ring-0 focus:outline-none text-xs text-sidebar-foreground placeholder:text-sidebar-foreground/50 ml-2" placeholder="Search" />
            <X v-if="searchQuery" class="w-4 h-4 text-sidebar-foreground/60 cursor-pointer hover:text-sidebar-foreground" @click="clearSearch" />
        </div>

        <button @click="isNotificationsModalOpen = true" class="p-2 bg-zinc-900 rounded-xl border border-zinc-800 hover:bg-zinc-800 relative cursor-pointer" title="Notifications">
            <BellIcon class="w-5 h-5" />
            <span v-if="notificationCount > 0" class="absolute top-1 right-1 w-2 h-2 bg-lime-400 rounded-full"></span>
        </button>

        <TextLink :href="route('user.profile.index')" class="w-9 h-9 bg-accent rounded-xl relative cursor-pointer overflow-hidden flex items-center justify-center" title="My Profile">
            <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" alt="Profile picture" class="h-full w-full object-cover" />
            <span v-else class="text-sm text-accent-foreground font-semibold select-none">
                {{ initials }}
            </span>
        </TextLink>
    </div>

    <div v-if="isAccountModalOpen" class="fixed inset-0 z-50 flex items-end lg:items-center justify-center bg-black/50 backdrop-blur-sm pt-4 pb-24" @click="closeAccountModal">
        <div class="bg-card text-sidebar-foreground w-full lg:w-[90%] lg:max-w-md rounded-t-3xl lg:rounded-2xl shadow-2xl animate-slideUp lg:animate-fadeIn max-h-[calc(100vh-10rem)] overflow-y-auto no-scrollbar" @click.stop>

            <!-- Handle bar for mobile -->
            <div class="lg:hidden pt-2 pb-3 flex justify-center">
                <div class="w-12 h-1 bg-sidebar-foreground/20 rounded-full"></div>
            </div>

            <!-- Close button for desktop -->
            <button @click="isAccountModalOpen = false" class="hidden lg:block absolute top-4 right-4 p-2 hover:bg-sidebar-accent rounded-lg transition-colors">
                <X class="w-5 h-5" />
            </button>

            <!-- Profile Header -->
            <div class="px-5 pb-4">
                <div class="relative">
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-transparent border border-sidebar-accent">
                        <div class="relative">
                            <div class="rounded-full h-16 w-16 object-cover ring-2 ring-primary/20 overflow-hidden bg-sidebar-accent flex items-center justify-center">
                                <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" :alt="`${user.first_name} ${user.last_name}`" class="h-full w-full object-cover">
                                <span v-else class="text-xl font-bold text-accent-foreground">{{ initials }}</span>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-2 border-card"></div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h2 class="font-bold text-lg leading-tight">{{ user.first_name }} {{ user.last_name }}</h2>
                            <div class="flex items-center gap-2 mt-1">
                                <p class="text-sm text-sidebar-foreground/60 truncate">{{ user.email }}</p>
                                <button @click="copyEmail" class="p-1 hover:bg-sidebar-accent rounded transition-colors flex-shrink-0" :title="emailCopied ? 'Copied!' : 'Copy email'">
                                    <Check v-if="emailCopied" class="w-3.5 h-3.5 text-green-500" />
                                    <Copy v-else class="w-3.5 h-3.5 text-sidebar-foreground/60" />
                                </button>
                            </div>
                            <span :class="kycStatusClass" class="mt-2">
                                {{ kycStatus }}
                            </span>
                        </div>

                        <TextLink :href="route('user.profile.index')" class="p-2 hover:bg-sidebar-accent rounded-lg transition-colors flex-shrink-0" title="Edit Profile">
                            <Settings class="w-5 h-5 text-sidebar-foreground/60" />
                        </TextLink>
                    </div>
                </div>
            </div>

            <div class="px-5 pb-6 space-y-6">
                <!-- Quick Actions -->
                <div>
                    <h3 class="text-xs font-semibold text-sidebar-foreground/60 uppercase tracking-wider mb-3 px-1">Quick Actions</h3>
                    <div class="grid grid-cols-3 gap-2.5">
                        <TextLink v-for="item in navigation" :key="item.name" :href="route(item.href)" @click="isAccountModalOpen = false" class="flex flex-col items-center justify-center p-3 rounded-xl bg-sidebar-accent/40 hover:bg-sidebar-accent active:scale-95 border border-sidebar-accent hover:border-sidebar-accent/60 transition-all group">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center mb-2 group-hover:bg-primary/20 transition-colors">
                                <component :is="item.icon" class="w-5 h-5 text-primary" />
                            </div>
                            <span class="text-xs font-medium text-center leading-tight">{{ item.name }}</span>
                        </TextLink>
                    </div>
                </div>

                <!-- Account Settings -->
                <div>
                    <h3 class="text-xs font-semibold text-sidebar-foreground/60 uppercase tracking-wider mb-3 px-1">Account Settings</h3>
                    <div class="space-y-1">
                        <TextLink v-for="item in accountLinks" :key="item.name" :href="route(item.href)" @click="isAccountModalOpen = false" class="flex items-center gap-3 p-3 rounded-xl hover:bg-sidebar-accent active:bg-sidebar-accent/80 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors flex-shrink-0">
                                <component :is="item.icon" class="w-5 h-5 text-primary" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm">{{ item.name }}</p>
                                <p class="text-xs text-sidebar-foreground/60">{{ item.description }}</p>
                            </div>
                            <ChevronRight class="w-4 h-4 text-sidebar-foreground/40 group-hover:text-sidebar-foreground/60 transition-colors flex-shrink-0" />
                        </TextLink>
                    </div>
                </div>

                <!-- Preferences -->
                <div>
                    <h3 class="text-xs font-semibold text-sidebar-foreground/60 uppercase tracking-wider mb-3 px-1">Preferences</h3>
                    <div class="space-y-1">
                        <button @click="toggleTheme" class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-sidebar-accent active:bg-sidebar-accent/80 transition-all group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                                    <Moon class="w-5 h-5 text-primary" />
                                </div>
                                <div class="text-left">
                                    <p class="font-medium text-sm">Dark Mode</p>
                                    <p class="text-xs text-sidebar-foreground/60">{{ isDarkMode ? 'Enabled' : 'Disabled' }}</p>
                                </div>
                            </div>
                            <div :class="isDarkMode ? 'bg-primary' : 'bg-sidebar-accent'" class="w-11 h-6 rounded-full relative flex-shrink-0 transition-colors">
                                <div :class="{ 'translate-x-5': isDarkMode, 'translate-x-0.5': !isDarkMode }" class="w-5 h-5 bg-white rounded-full absolute top-0.5 shadow-sm transition-transform"></div>
                            </div>
                        </button>

                        <!-- Language (example) -->
                        <button class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-sidebar-accent active:bg-sidebar-accent/80 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors flex-shrink-0">
                                <Globe class="w-5 h-5 text-primary" />
                            </div>
                            <div class="flex-1 text-left">
                                <p class="font-medium text-sm">Language</p>
                                <p class="text-xs text-sidebar-foreground/60">English (US)</p>
                            </div>
                            <ChevronRight class="w-4 h-4 text-sidebar-foreground/40 group-hover:text-sidebar-foreground/60 transition-colors flex-shrink-0" />
                        </button>
                    </div>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="text-xs font-semibold text-sidebar-foreground/60 uppercase tracking-wider mb-3 px-1">Support & Legal</h3>
                    <div class="space-y-1">
                        <TextLink v-for="item in supportLinks" :key="item.name" :href="route(item.href)" @click="isAccountModalOpen = false" class="flex items-center gap-3 p-3 rounded-xl hover:bg-sidebar-accent active:bg-sidebar-accent/80 transition-all group">
                            <component :is="item.icon" class="w-5 h-5 text-sidebar-foreground/60 flex-shrink-0" />
                            <span class="font-medium text-sm">{{ item.name }}</span>
                            <ChevronRight class="w-4 h-4 text-sidebar-foreground/40 group-hover:text-sidebar-foreground/60 transition-colors flex-shrink-0 ml-auto" />
                        </TextLink>
                    </div>
                </div>

                <!-- Logout Button -->
                <TextLink :href="route('logout')" method="post" as="button" class="w-full flex items-center justify-center gap-2 p-4 rounded-xl bg-red-500/10 hover:bg-red-500/20 active:bg-red-500/30 border border-red-500/30 hover:border-red-500/50 transition-all group cursor-pointer">
                    <LogOut class="w-5 h-5 text-red-500" />
                    <span class="font-semibold text-red-500">Logout</span>
                </TextLink>

                <!-- App Version -->
                <div class="text-center pt-2 pb-4">
                    <p class="text-xs text-sidebar-foreground/40">Version 2.1.0</p>
                </div>
            </div>
        </div>
    </div>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(100%);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeIn {
        animation: fadeIn 0.2s ease-out;
    }

    .animate-slideUp {
        animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Hide scrollbar for Chrome, Safari and Opera */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    /* Hide scrollbar for IE, Edge and Firefox */
    .no-scrollbar {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>
