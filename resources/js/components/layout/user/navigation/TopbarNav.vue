<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { useAppearance } from '@/composables/useAppearance';
    import {
        Menu,
        Search,
        X,
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
        Check,
        Monitor,
        Sun, Users
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import { usePage } from '@inertiajs/vue3';

    const page = usePage();
    const searchQuery = ref('');
    const isAccountModalOpen = ref(false);
    const isNotificationsModalOpen = ref(false);
    const emailCopied = ref(false);
    const { appearance, updateAppearance } = useAppearance();

    const tabs = [
        { value: 'light', Icon: Sun, label: 'Light' },
        { value: 'dark', Icon: Moon, label: 'Dark' },
        { value: 'system', Icon: Monitor, label: 'System' },
    ] as const;

    const currentIcon = computed(() => {
        return tabs.find(tab => tab.value === appearance.value)?.Icon ?? Sun;
    });

    const toggleAppearance = () => {
        const currentIndex = tabs.findIndex(tab => tab.value === appearance.value);
        const nextIndex = (currentIndex + 1) % tabs.length;
        const nextTheme = tabs[nextIndex].value;
        updateAppearance(nextTheme);
    };

    const notificationCount = computed(() => page.props.auth.notification_count);
    const user = computed(() => page.props.auth.user);

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
        { name: "Wallet Connect", href: "user.wallet.index", icon: Wallet },
        { name: "Referrals", href: "user.rewards.index", icon: Users },
    ];

    const accountLinks = [
        { name: "KYC Verification", href: "user.kyc.index", icon: LifeBuoy, description: "Verify your identity" },
        { name: "Transaction History", href: "user.dashboard", icon: History, description: "View all transactions" },
        { name: "Security", href: "user.profile.index", params: { tab: 'security' }, icon: Shield, description: "Password & 2FA" },
        { name: "Payment Methods", href: "user.dashboard", icon: CreditCard, description: "Manage payment options" },
    ];

    const supportLinks = [
        { name: "Privacy Policy", href: "user.support.privacy", icon: HelpCircle },
        { name: "Terms of Service", href: "user.support.terms", icon: FileText },
        { name: "Contact Support", href: "user.support.index", icon: Mail },
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
                return `${baseClasses} bg-success/20 text-success border-success/30`;
            case 'Pending':
                return `${baseClasses} bg-warning/20 text-warning border-warning/30`;
            case 'Rejected':
            case 'Unverified':
            default:
                return `${baseClasses} bg-destructive/20 text-destructive border-destructive/30`;
        }
    });

    const clearSearch = () => {
        searchQuery.value = '';
    };

    const closeAccountModal = () => {
        isAccountModalOpen.value = false;
    };

    const handleAccountBackdropClick = (event: MouseEvent) => {
        if (event.target === event.currentTarget) {
            closeAccountModal();
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

    watch(isAccountModalOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
</script>

<template>
    <div class="lg:hidden sticky top-0 inset-x-0 z-40 px-3 xs:px-4 py-3 xs:py-4 flex items-center gap-2 xs:gap-3 bg-background border-b border-border">
        <button
            class="w-8 h-8 xs:w-9 xs:h-9 flex items-center justify-center rounded-md bg-accent text-accent-foreground flex-shrink-0"
            aria-label="Menu"
            @click="isAccountModalOpen = true"
        >
            <Menu class="w-5 h-5 xs:w-6 xs:h-6" />
        </button>

        <div class="flex items-center flex-1 min-w-0 bg-secondary rounded-lg xs:rounded-xl px-2 xs:px-3 py-2 xs:py-3">
            <Search class="w-3.5 h-3.5 text-muted-foreground flex-shrink-0" />
            <input
                id="globalSearch"
                v-model="searchQuery"
                type="text"
                class="flex-1 min-w-0 bg-transparent border-0 focus:ring-0 focus:outline-none text-xs text-foreground placeholder:text-muted-foreground ml-2"
                placeholder="Search"
            />
            <X
                v-if="searchQuery"
                class="w-4 h-4 text-muted-foreground cursor-pointer hover:text-foreground flex-shrink-0"
                @click="clearSearch"
            />
        </div>

        <button
            @click="isNotificationsModalOpen = true"
            class="p-1.5 xs:p-2 bg-card rounded-lg xs:rounded-xl border border-border hover:bg-secondary relative cursor-pointer transition-colors flex-shrink-0"
            title="Notifications"
        >
            <BellIcon class="w-4 h-4 xs:w-5 xs:h-5 text-card-foreground" />
            <span
                v-if="notificationCount > 0"
                class="absolute top-0.5 right-0.5 xs:top-1 xs:right-1 w-1.5 h-1.5 xs:w-2 xs:h-2 bg-primary rounded-full"
            ></span>
        </button>

        <button
            @click="toggleAppearance"
            class="p-1.5 xs:p-2 bg-card rounded-lg xs:rounded-xl border border-border hover:bg-secondary relative cursor-pointer transition-colors flex-shrink-0"
            title="Change Appearance"
        >
            <component :is="currentIcon" class="w-4 h-4 xs:w-5 xs:h-5 text-card-foreground" />
        </button>

        <TextLink
            :href="route('user.profile.index')"
            class="w-8 h-8 xs:w-9 xs:h-9 bg-accent rounded-lg xs:rounded-xl relative cursor-pointer overflow-hidden flex items-center justify-center flex-shrink-0"
            title="My Profile"
        >
            <img
                v-if="user.profile?.profile_photo_path"
                :src="user.profile.profile_photo_path"
                alt="Profile picture"
                class="h-full w-full object-cover"
            />
            <span
                v-else
                class="text-xs xs:text-sm text-accent-foreground font-semibold select-none"
            >
                {{ initials }}
            </span>
        </TextLink>
    </div>

    <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isAccountModalOpen"
            class="fixed inset-0 z-[100] flex lg:items-center lg:justify-center bg-black/50 backdrop-blur-sm lg:pt-4 lg:pb-24"
            @click="handleAccountBackdropClick"
        >
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="isAccountModalOpen"
                    class="bg-card text-card-foreground w-full h-full lg:w-[80%] lg:max-w-md lg:rounded-lg flex flex-col rounded-none shadow-lg overflow-y-auto no-scrollbar border border-border relative"
                    @click.stop
                >
                    <div class="sticky top-0 bg-card z-10 px-4 xs:px-5 pt-4 xs:pt-6 pb-3 xs:pb-4 border-b border-border">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg xs:text-xl font-semibold text-card-foreground">Account Menu</h3>
                                <p class="text-xs xs:text-sm text-muted-foreground mt-1">Manage your account and preferences</p>
                            </div>
                            <button
                                @click="closeAccountModal"
                                class="p-1.5 xs:p-2 hover:bg-muted rounded-lg transition-colors flex-shrink-0 cursor-pointer"
                                title="Close"
                            >
                                <X class="h-4 w-4 xs:h-5 xs:w-5 text-muted-foreground" />
                            </button>
                        </div>
                    </div>

                    <div class="px-4 xs:px-5 pt-4 xs:pt-6 pb-3 xs:pb-4">
                        <div class="flex items-start gap-3 xs:gap-4 p-3 xs:p-4 rounded-xl xs:rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-transparent border border-border">
                            <div class="relative flex-shrink-0">
                                <div class="rounded-full h-12 w-12 xs:h-16 xs:w-16 object-cover border border-border overflow-hidden bg-secondary flex items-center justify-center">
                                    <img
                                        v-if="user.profile?.profile_photo_path"
                                        :src="user.profile.profile_photo_path"
                                        :alt="`${user.first_name} ${user.last_name}`"
                                        class="h-full w-full object-cover"
                                    >
                                    <span
                                        v-else
                                        class="text-base xs:text-xl font-bold text-muted-foreground"
                                    >
                                        {{ initials }}
                                    </span>
                                </div>
                                <div class="absolute -bottom-0.5 -right-0.5 xs:-bottom-1 xs:-right-1 w-4 h-4 xs:w-5 xs:h-5 bg-success rounded-full border-2 border-card"></div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h2 class="font-bold text-base xs:text-lg leading-tight truncate">
                                    {{ user.first_name }} {{ user.last_name?.charAt(0) }}.
                                </h2>
                                <div class="flex items-center gap-1.5 xs:gap-2 mt-1">
                                    <p class="text-xs xs:text-sm text-muted-foreground truncate">{{ user.email }}</p>
                                    <button
                                        @click="copyEmail"
                                        class="p-1 hover:bg-secondary rounded transition-colors flex-shrink-0"
                                        :title="emailCopied ? 'Copied!' : 'Copy email'"
                                    >
                                        <Check v-if="emailCopied" class="w-3 h-3 xs:w-3.5 xs:h-3.5 text-success" />
                                        <Copy v-else class="w-3 h-3 xs:w-3.5 xs:h-3.5 text-muted-foreground" />
                                    </button>
                                </div>
                                <span :class="kycStatusClass" class="mt-1.5 xs:mt-2">
                                    {{ kycStatus }}
                                </span>
                            </div>

                            <TextLink
                                :href="route('user.profile.index')"
                                class="p-1.5 xs:p-2 hover:bg-secondary rounded-lg transition-colors flex-shrink-0"
                                title="Edit Profile"
                            >
                                <Settings class="w-4 h-4 xs:w-5 xs:h-5 text-muted-foreground" />
                            </TextLink>
                        </div>
                    </div>

                    <div class="px-4 xs:px-5 pb-4 xs:pb-6 space-y-4 xs:space-y-6 flex-1">
                        <div>
                            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Quick Actions</h3>
                            <div class="grid grid-cols-3 gap-1.5 xs:gap-2.5">
                                <TextLink
                                    v-for="item in navigation"
                                    :key="item.name"
                                    :href="route(item.href)"
                                    @click="closeAccountModal"
                                    class="flex flex-col items-center justify-center p-2 xs:p-3 rounded-lg xs:rounded-xl bg-secondary/50 hover:bg-secondary active:scale-95 border border-border hover:border-border/60 transition-all group"
                                >
                                    <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg xs:rounded-xl bg-primary/10 flex items-center justify-center mb-1.5 xs:mb-2 group-hover:bg-primary/20 transition-colors">
                                        <component :is="item.icon" class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                    </div>
                                    <span class="text-[10px] xs:text-xs font-medium text-center leading-tight">{{ item.name }}</span>
                                </TextLink>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Account Settings</h3>
                            <div class="space-y-0.5 xs:space-y-1">
                                <TextLink
                                    v-for="item in accountLinks"
                                    :key="item.name"
                                    :href="route(item.href, item.params)"
                                    @click="closeAccountModal"
                                    class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl hover:bg-secondary active:bg-secondary/80 transition-all group"
                                >
                                    <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors flex-shrink-0">
                                        <component :is="item.icon" class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-xs xs:text-sm">{{ item.name }}</p>
                                        <p class="text-[10px] xs:text-xs text-muted-foreground truncate">{{ item.description }}</p>
                                    </div>
                                    <ChevronRight class="w-3.5 h-3.5 xs:w-4 xs:h-4 text-muted-foreground/50 group-hover:text-muted-foreground transition-colors flex-shrink-0" />
                                </TextLink>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Preferences</h3>
                            <div class="space-y-0.5 xs:space-y-1">
                                <div class="p-2.5 xs:p-3">
                                    <div class="flex items-center gap-1 xs:gap-1.5 p-1 rounded-lg bg-secondary">
                                        <button
                                            v-for="{ value, Icon, label } in tabs"
                                            :key="value"
                                            @click="updateAppearance(value as 'light' | 'dark' | 'system')"
                                            :class="[
                                                'flex-1 flex items-center justify-center gap-1 xs:gap-2 rounded-md px-2 xs:px-3 py-1.5 text-xs xs:text-sm transition-colors',
                                                appearance === value
                                                    ? 'bg-primary text-primary-foreground shadow'
                                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                            ]"
                                        >
                                            <component :is="Icon" class="h-3.5 w-3.5 xs:h-4 xs:w-4" />
                                            <span class="hidden xs:inline">{{ label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <button class="w-full flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl hover:bg-secondary active:bg-secondary/80 transition-all group">
                                    <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors flex-shrink-0">
                                        <Globe class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                    </div>
                                    <div class="flex-1 text-left min-w-0">
                                        <p class="font-medium text-xs xs:text-sm">Language</p>
                                        <p class="text-[10px] xs:text-xs text-muted-foreground">English (US)</p>
                                    </div>
                                    <ChevronRight class="w-3.5 h-3.5 xs:w-4 xs:h-4 text-muted-foreground/50 group-hover:text-muted-foreground transition-colors flex-shrink-0" />
                                </button>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Support & Legal</h3>
                            <div class="space-y-0.5 xs:space-y-1">
                                <TextLink
                                    v-for="item in supportLinks"
                                    :key="item.name"
                                    :href="route(item.href)"
                                    @click="closeAccountModal"
                                    class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl hover:bg-secondary active:bg-secondary/80 transition-all group"
                                >
                                    <component :is="item.icon" class="w-4 h-4 xs:w-5 xs:h-5 text-muted-foreground flex-shrink-0" />
                                    <span class="font-medium text-xs xs:text-sm flex-1 text-left">{{ item.name }}</span>
                                    <ChevronRight class="w-3.5 h-3.5 xs:w-4 xs:h-4 text-muted-foreground/50 group-hover:text-muted-foreground transition-colors flex-shrink-0" />
                                </TextLink>
                            </div>
                        </div>

                        <TextLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="w-full flex items-center justify-center gap-2 p-3 xs:p-4 rounded-lg xs:rounded-xl bg-destructive/10 hover:bg-destructive/20 active:bg-destructive/30 border border-destructive/30 hover:border-destructive/50 transition-all group cursor-pointer"
                        >
                            <LogOut class="w-4 h-4 xs:w-5 xs:h-5 text-destructive" />
                            <span class="font-semibold text-sm xs:text-base text-destructive">Logout</span>
                        </TextLink>

                        <div class="text-center pt-2 pb-2 xs:pb-4">
                            <p class="text-[10px] xs:text-xs text-muted-foreground/50">Version 2.1.0</p>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style scoped>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
