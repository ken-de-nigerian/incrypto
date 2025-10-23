<script setup lang="ts">
    import { computed, defineAsyncComponent, ref, watch } from 'vue';
    import { useAppearance } from '@/composables/useAppearance';
    import {
        BellIcon,
        Check,
        ChevronRight,
        Copy,
        CreditCard,
        Download,
        FileText,
        LogOut,
        Menu,
        Monitor,
        Moon,
        Repeat,
        Search,
        Send,
        Settings,
        Shield,
        Sun,
        Users,
        Wallet,
        X,
        LayoutDashboard, Globe, HelpCircle, Mail
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import { useForm, usePage } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';

    const page = usePage();
    const searchQuery = ref('');
    const isAccountModalOpen = ref(false);
    const isNotificationsModalOpen = ref(false);
    const activeTab = ref<'menu' | 'notifications'>('menu');
    const emailCopied = ref(false);
    const { appearance, updateAppearance } = useAppearance();
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';
    import("@vueup/vue-quill/dist/vue-quill.snow.css");

    const QuillEditor = defineAsyncComponent(() =>
        import("@vueup/vue-quill").then(module => {
            return module.QuillEditor;
        })
    );

    const tabs = [
        { value: 'light', Icon: Sun, label: 'Light' },
        { value: 'dark', Icon: Moon, label: 'Dark' },
        { value: 'system', Icon: Monitor, label: 'System Preference' },
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
        { name: "Dashboard", href: "admin.dashboard", icon: LayoutDashboard },
    ];

    const gatewaysNavigation = [
        { name: "Connected Wallets", href: "admin.wallet.index", icon: Wallet, description: "Manage all linked user wallet connections" },
        { name: "Crypto Gateways", href: "admin.method.index", icon: Shield, description: "Configure cryptocurrency processing methods" },
    ];

    const userNavigation = [
        { name: "Manage Users", href: "admin.users.index", icon: Users },
    ];

    const transactionSubRoutes = [
        { name: "All Transactions", href: "admin.dashboard", icon: CreditCard, description: "Comprehensive view of all financial activities" },
        { name: "Sent Cryptos", href: "admin.send.index", icon: Send, description: "Detailed records of cryptos sent out" },
        { name: "Received Cryptos", href: "admin.receive.index", icon: Download, description: "Records of funds received" },
        { name: "Swap & Trade Logs", href: "admin.swap.index", icon: Repeat, description: "Logs for all currency exchange and trade activities" }
    ];

    const supportLinks = [
        { name: "Privacy Policy", href: "admin.dashboard", icon: HelpCircle },
        { name: "Terms of Service", href: "admin.dashboard", icon: FileText },
        { name: "Contact & Helpdesk", href: "admin.dashboard", icon: Mail },
    ];

    const adminToolsNavigation = [
        { name: "KYC Submissions", href: "admin.kyc.index", icon: FileText },
    ];

    const securityNavigation = [
        { name: "Security & Access", href: "admin.profile.index", params: { tab: 'security' }, icon: Shield, description: "Manage password and two-factor authentication (2FA)" }
    ];

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

    const switchToMenuTab = () => {
        activeTab.value = 'menu';
    };

    const switchToNotificationsTab = () => {
        activeTab.value = 'notifications';
    };

    watch(isAccountModalOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            setTimeout(() => {
                document.body.style.overflow = '';
                if (activeTab.value !== 'menu') {
                    activeTab.value = 'menu';
                }
            }, 250);
        }
    });

    const deliveryMethods = [
        { value: 'email', label: 'Email Only' },
        { value: 'db', label: 'In-App Only' },
        { value: 'both', label: 'Email & In-App' },
    ];

    const campaignForm = useForm({
        title: '',
        content: '',
        deliveryMethod: 'both' as 'both' | 'email' | 'db',
    });

    const resetFormData = () => {
        campaignForm.reset();
        campaignForm.deliveryMethod = 'both';
    };

    const createCampaign = () => {
        campaignForm.post(route('admin.notifications.broadcast'), {
            preserveScroll: true,
            onSuccess: () => {
                resetFormData();
                switchToMenuTab();
            },
        });
    };

    const clearError = (form: typeof campaignForm, field: 'title' | 'content' | 'deliveryMethod') => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };
</script>

<template>
    <div class="lg:hidden sticky top-0 inset-x-0 z-40 px-3 xs:px-4 py-3 xs:py-4 flex items-center gap-2 xs:gap-3 bg-background border-b border-border">
        <button
            class="w-8 h-8 xs:w-9 xs:h-9 flex items-center justify-center rounded-md bg-accent text-accent-foreground flex-shrink-0"
            aria-label="Admin Menu"
            @click="isAccountModalOpen = true">
            <Menu class="w-5 h-5 xs:w-6 xs:h-6" />
        </button>

        <div class="flex items-center flex-1 min-w-0 bg-secondary rounded-lg xs:rounded-xl px-2 xs:px-3 py-2 xs:py-3">
            <Search class="w-3.5 h-3.5 text-muted-foreground flex-shrink-0" />
            <input
                id="globalSearch"
                v-model="searchQuery"
                type="text"
                class="flex-1 min-w-0 bg-transparent border-0 focus:ring-0 focus:outline-none text-xs text-foreground placeholder:text-muted-foreground ml-2"
                placeholder="Search Administrative Panel"
            />
            <X v-if="searchQuery" class="w-4 h-4 text-muted-foreground cursor-pointer hover:text-foreground flex-shrink-0" @click="clearSearch" />
        </div>

        <button
            @click="isNotificationsModalOpen = true"
            class="p-1.5 xs:p-2 bg-card rounded-lg xs:rounded-xl border border-border hover:bg-secondary relative cursor-pointer flex-shrink-0"
            title="Notifications">
            <BellIcon class="w-4 h-4 xs:w-5 xs:h-5 text-card-foreground" />
            <span
                v-if="notificationCount > 0"
                class="absolute top-0.5 right-0.5 xs:top-1 xs:right-1 w-1.5 h-1.5 xs:w-2 xs:h-2 bg-primary rounded-full"
            ></span>
        </button>

        <button
            @click="toggleAppearance"
            class="p-1.5 xs:p-2 bg-card rounded-lg xs:rounded-xl border border-border hover:bg-secondary relative cursor-pointer flex-shrink-0"
            title="Toggle Theme"> <component :is="currentIcon" class="w-4 h-4 xs:w-5 xs:h-5 text-card-foreground" />
        </button>

        <TextLink
            :href="route('admin.profile.index')"
            class="w-8 h-8 xs:w-9 xs:h-9 bg-accent rounded-lg xs:rounded-xl relative cursor-pointer overflow-hidden flex items-center justify-center flex-shrink-0"
            title="Admin Profile Settings">
            <img v-if="user.profile?.profile_photo_path"
                 :src="user.profile.profile_photo_path"
                 alt="Profile picture"
                 class="h-full w-full object-cover" />
            <span v-else class="text-xs xs:text-sm text-accent-foreground font-semibold select-none">
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
        leave-to-class="opacity-0">
        <div
            v-if="isAccountModalOpen"
            class="fixed inset-0 z-[100] flex lg:items-center lg:justify-center bg-black/50 backdrop-blur-sm lg:pt-4 lg:pb-24"
            @click="handleAccountBackdropClick">
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95">
                <div v-if="isAccountModalOpen"
                     class="bg-card text-card-foreground w-full h-full lg:w-[80%] lg:max-w-md lg:rounded-lg flex flex-col rounded-none shadow-lg overflow-y-auto no-scrollbar border border-border relative"
                     @click.stop>
                    <div class="sticky top-0 bg-card z-10 px-4 xs:px-5 pt-4 xs:pt-6 pb-3 xs:pb-4 border-b border-border">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg xs:text-xl font-semibold text-card-foreground">Administrative Panel</h3>
                                <p class="text-xs xs:text-sm text-muted-foreground mt-1">Quick access to dashboard navigation and system controls</p>
                            </div>
                            <button
                                @click="closeAccountModal"
                                class="p-1.5 xs:p-2 hover:bg-muted rounded-lg flex-shrink-0 cursor-pointer"
                                title="Close">
                                <X class="h-4 w-4 xs:h-5 xs:w-5 text-muted-foreground" />
                            </button>
                        </div>

                        <div class="flex gap-1 p-1 rounded-lg bg-secondary/70">
                            <button
                                @click="switchToMenuTab"
                                :class="[
                                    'flex-1 px-3 py-2 text-xs xs:text-sm font-medium rounded-md transition-all',
                                    activeTab === 'menu'
                                        ? 'bg-primary text-primary-foreground shadow'
                                        : 'text-muted-foreground hover:text-foreground'
                                ]">
                                Navigation
                            </button>
                            <button
                                @click="switchToNotificationsTab"
                                :class="[
                                    'flex-1 px-3 py-2 text-xs xs:text-sm font-medium rounded-md transition-all',
                                    activeTab === 'notifications'
                                    ? 'bg-primary text-primary-foreground shadow'
                                    : 'text-muted-foreground hover:text-foreground'
                                ]">
                                Broadcast Center
                            </button>
                        </div>
                    </div>

                    <div v-if="activeTab === 'menu'" class="flex flex-col flex-1">
                        <div class="px-4 xs:px-5 pt-4 xs:pt-6 pb-3 xs:pb-4">
                            <div class="flex items-start gap-3 xs:gap-4 p-3 xs:p-4 rounded-xl xs:rounded-2xl bg-gradient-to-br from-primary/10 via-primary/10 to-transparent border border-border">
                                <div class="relative flex-shrink-0">
                                    <div class="rounded-full h-12 w-12 xs:h-16 xs:w-16 object-cover border border-border overflow-hidden bg-secondary flex items-center justify-center">
                                        <img v-if="user.profile?.profile_photo_path" :src="user.profile.profile_photo_path" :alt="`${user.first_name} ${user.last_name}`" class="h-full w-full object-cover">
                                        <span v-else class="text-base xs:text-xl font-bold text-muted-foreground">
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
                                            class="p-1 hover:bg-secondary rounded flex-shrink-0"
                                            :title="emailCopied ? 'Email Copied!' : 'Copy email address'"> <Check v-if="emailCopied" class="w-3 h-3 xs:w-3.5 xs:h-3.5 text-success" />
                                            <Copy v-else class="w-3 h-3 xs:w-3.5 xs:h-3.5 text-muted-foreground" />
                                        </button>
                                    </div>
                                    <span class="mt-1.5 xs:mt-2 inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border bg-success/20 text-success border-success/30">
                                        System Administrator
                                    </span>
                                </div>

                                <TextLink
                                    :href="route('admin.profile.index')"
                                    @click="closeAccountModal"
                                    class="p-1.5 xs:p-2 hover:bg-secondary rounded-lg flex-shrink-0"
                                    title="View Profile Settings"> <Settings class="w-4 h-4 xs:w-5 xs:h-5 text-muted-foreground" />
                                </TextLink>
                            </div>
                        </div>

                        <div class="px-4 xs:px-5 pb-4 xs:pb-6 space-y-4 xs:space-y-6 flex-1 overflow-y-auto">
                            <div>
                                <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Core Actions</h3>
                                <div class="grid grid-cols-2 gap-1.5 xs:gap-2.5">
                                    <TextLink
                                        :href="route(navigation[0].href)"
                                        @click="closeAccountModal"
                                        class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl bg-secondary/50 border border-border hover:bg-secondary hover:border-border/60 active:bg-secondary/90 transition-all group">
                                        <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg xs:rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20">
                                            <component :is="navigation[0].icon" class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs xs:text-sm font-medium leading-tight">{{ navigation[0].name }}</p>
                                        </div>
                                    </TextLink>

                                    <TextLink
                                        :href="route(userNavigation[0].href)"
                                        @click="closeAccountModal"
                                        class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl bg-secondary/50 border border-border hover:bg-secondary hover:border-border/60 active:bg-secondary/90 transition-all group">
                                        <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg xs:rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20">
                                            <component :is="userNavigation[0].icon" class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs xs:text-sm font-medium leading-tight">{{ userNavigation[0].name }}</p>
                                        </div>
                                    </TextLink>

                                    <TextLink
                                        :href="route(adminToolsNavigation.find(i => i.name === 'KYC Submissions')?.href || 'admin.dashboard')"
                                        @click="closeAccountModal"
                                        class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl bg-secondary/50 border border-border hover:bg-secondary hover:border-border/60 active:bg-secondary/90 transition-all group">
                                        <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg xs:rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20">
                                            <FileText class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs xs:text-sm font-medium leading-tight">KYC Submissions</p>
                                        </div>
                                    </TextLink>

                                    <TextLink
                                        :href="route(securityNavigation[0].href, securityNavigation[0].params)"
                                        @click="closeAccountModal"
                                        class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl bg-secondary/50 border border-border hover:bg-secondary hover:border-border/60 active:bg-secondary/90 transition-all group">
                                        <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg xs:rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20">
                                            <Shield class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs xs:text-sm font-medium leading-tight">Security & Access</p>
                                        </div>
                                    </TextLink>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Payment Infrastructure</h3>
                                <div class="space-y-0.5 xs:space-y-1">
                                    <template v-for="item in gatewaysNavigation" :key="item.name">
                                        <TextLink
                                            :href="route(item.href)"
                                            @click="closeAccountModal"
                                            class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl hover:bg-secondary active:bg-secondary/90 transition-all group">
                                            <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 flex-shrink-0">
                                                <component :is="item.icon" class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-xs xs:text-sm">{{ item.name }}</p>
                                                <p class="text-[10px] xs:text-xs text-muted-foreground truncate">{{ item.description }}</p>
                                            </div>
                                            <ChevronRight class="w-3.5 h-3.5 xs:w-4 xs:h-4 text-muted-foreground/50 group-hover:text-muted-foreground flex-shrink-0" />
                                        </TextLink>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Financial Operations</h3>
                                <div class="space-y-0.5 xs:space-y-1">
                                    <TextLink
                                        v-for="subItem in transactionSubRoutes"
                                        :key="subItem.name"
                                        :href="route(subItem.href)"
                                        @click="closeAccountModal"
                                        class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl hover:bg-secondary active:bg-secondary/90 transition-all group">
                                        <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 flex-shrink-0">
                                            <component :is="subItem.icon" class="h-4 w-4 xs:h-5 xs:w-5 text-primary" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-xs xs:text-sm">{{ subItem.name }}</p>
                                            <p class="text-[10px] xs:text-xs text-muted-foreground truncate">{{ subItem.description }}</p>
                                        </div>
                                        <ChevronRight class="w-3.5 h-3.5 xs:w-4 xs:h-4 text-muted-foreground/50 group-hover:text-muted-foreground flex-shrink-0" />
                                    </TextLink>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">User Preferences</h3>
                                <div class="space-y-0.5 xs:space-y-1">
                                    <div class="p-2.5 xs:p-3">
                                        <div class="flex items-center gap-1 xs:gap-1.5 p-1 rounded-lg bg-secondary/70">
                                            <button
                                                v-for="{ value, Icon, label } in tabs"
                                                :key="value"
                                                @click="updateAppearance(value as 'light' | 'dark' | 'system')"
                                                :class="[
                                                        'flex-1 flex items-center justify-center gap-1 xs:gap-2 rounded-md px-2 xs:px-3 py-1.5 text-xs xs:text-sm',
                                                        appearance === value
                                                            ? 'bg-primary text-primary-foreground shadow'
                                                            : 'text-muted-foreground hover:bg-muted/70 hover:text-foreground',
                                                    ]"
                                            >
                                                <component :is="Icon" class="h-3.5 w-3.5 xs:h-4 xs:w-4" />
                                                <span class="hidden xs:inline">{{ label }}</span>
                                            </button>
                                        </div>
                                    </div>

                                    <button class="w-full flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl hover:bg-secondary/70 active:bg-secondary/90 transition-all group">
                                        <div class="w-8 h-8 xs:w-10 xs:h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 flex-shrink-0">
                                            <Globe class="w-4 h-4 xs:w-5 xs:h-5 text-primary" />
                                        </div>
                                        <div class="flex-1 text-left min-w-0">
                                            <p class="font-medium text-xs xs:text-sm">Interface Language</p> <p class="text-[10px] xs:text-xs text-muted-foreground">English (US)</p>
                                        </div>
                                        <ChevronRight class="w-3.5 h-3.5 xs:w-4 xs:h-4 text-muted-foreground/50 group-hover:text-muted-foreground flex-shrink-0" />
                                    </button>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Support & Documentation</h3>
                                <div class="space-y-0.5 xs:space-y-1">
                                    <TextLink
                                        v-for="item in supportLinks"
                                        :key="item.name"
                                        :href="route(item.href)"
                                        @click="closeAccountModal"
                                        class="flex items-center gap-2.5 xs:gap-3 p-2.5 xs:p-3 rounded-lg xs:rounded-xl hover:bg-secondary/70 active:bg-secondary/90 transition-all group"
                                    >
                                        <component :is="item.icon" class="w-4 h-4 xs:w-5 xs:h-5 text-muted-foreground flex-shrink-0" />
                                        <span class="font-medium text-xs xs:text-sm flex-1 text-left">{{ item.name }}</span>
                                        <ChevronRight class="w-3.5 h-3.5 xs:w-4 xs:h-4 text-muted-foreground/50 group-hover:text-muted-foreground flex-shrink-0" />
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
                                <span class="font-semibold text-sm xs:text-base text-destructive">Secure Logout</span>
                            </TextLink>

                            <div class="text-center pt-2 pb-2 xs:pb-4">
                                <p class="text-[10px] xs:text-xs text-muted-foreground/50">System Version 2.1.0</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeTab === 'notifications'" class="flex flex-col flex-1">
                        <div class="px-4 xs:px-5 pt-4 xs:pt-6 pb-3 xs:pb-4">
                            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 xs:mb-3 px-1">Mass Notification Campaign</h3>
                            <p class="text-xs text-muted-foreground mb-3 xs:mb-4 px-1">Compose and broadcast a message to platform users via email and/or in-app notification.</p>
                        </div>

                        <div class="px-4 xs:px-5 flex-1 overflow-y-auto no-scrollbar pb-4 xs:pb-6">
                            <form @submit.prevent="createCampaign" class="space-y-4">
                                <div class="space-y-2">
                                    <label for="title" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Subject Line</label>
                                    <input id="title" v-model="campaignForm.title" @focus="clearError(campaignForm, 'title')" type="text" placeholder="Important System Update Notice" class="input-crypto w-full text-sm" />
                                    <InputError :message="campaignForm.errors.title" />
                                </div>

                                <div class="space-y-2">
                                    <label for="content" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Message Content</label>
                                    <QuillEditor id="content" v-model:content="campaignForm.content" contentType="html" theme="snow" placeholder="Type your full message content here..." toolbar="full" />
                                    <InputError :message="campaignForm.errors.content" />
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Campaign Delivery Channel</label> <div class="grid grid-cols-3 gap-2">
                                    <button v-for="method in deliveryMethods" :key="method.value" type="button" @click="campaignForm.deliveryMethod = method.value as any" :class="`p-3 rounded-lg border-2 text-center cursor-pointer transition text-xs font-medium ${campaignForm.deliveryMethod === method.value ? 'border-primary bg-primary/10' : 'border-border hover:border-border/60'}`">
                                        {{ method.label }}
                                    </button>
                                </div>
                                    <InputError :message="campaignForm.errors.deliveryMethod" />
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-2 mt-auto margin-bottom">
                                    <ActionButton :processing="campaignForm.processing">
                                        Launch Campaign
                                    </ActionButton>
                                </div>
                            </form>
                        </div>

                        <div class="sticky bottom-0 bg-card z-10 p-4 xs:p-5 border-t border-border text-center">
                            <p class="text-xs text-muted-foreground">The campaign will be sent to all active users upon successful creation.</p>
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

<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .animate-fadeIn {
        animation: fadeIn 0.2s ease-out;
    }
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px) scale(1);
        }
        to {
            opacity: 1;
            transform: translateY(0px) scale(1);
        }
    }

    /* Quill Editor Customizations */
    .ql-editor {
        min-height: 200px !important;
    }

    .ql-toolbar.ql-snow {
        border-radius: 0.75rem !important;
        background-color: hsl(var(--input)) !important;
        border: 1px solid hsl(var(--border)) !important;
    }

    .ql-container.ql-snow {
        border-radius: 0.75rem !important;
        font-family: inherit !important;
        background-color: hsl(var(--input)) !important;
        border: 1px solid hsl(var(--border)) !important;
    }

    .ql-editor.ql-blank::before {
        color: hsl(var(--foreground)) !important;
    }

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
