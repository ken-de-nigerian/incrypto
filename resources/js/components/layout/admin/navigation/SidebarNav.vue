<script setup lang="ts">
    import {
        LayoutDashboard,
        Users,
        Shield,
        FileText,
        UserIcon,
        LogOut,
        CreditCard,
        Wallet,
        User, ExternalLink, Moon, PiggyBank
    } from 'lucide-vue-next';
        import { route } from 'ziggy-js';
        import TextLink from '@/components/TextLink.vue';
        import { computed } from 'vue';
        import { usePage } from '@inertiajs/vue3';
    import SiteLogo from '@/components/SiteLogo.vue';

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    const navigation = [
        { name: "Dashboard", href: "admin.dashboard", icon: LayoutDashboard },
    ];

    const gatewaysNavigation = [
        { name: "Wallets", href: "admin.wallet.index", icon: Wallet },
        { name: "Payment Methods", href: "admin.method.index", icon: Shield },
        { name: "Investment Plans", href: "admin.plans.index", icon: PiggyBank },
    ];

    const userNavigation = [
        { name: "Manage Users", href: "admin.users.index", icon: Users },
    ];

    const transactionRoutes = [
        { name: "Transactions", href: "admin.transaction.index", icon: CreditCard },
    ];

    const adminToolsNavigation = [
        { name: "KYC Submissions", href: "admin.kyc.index", icon: FileText }
    ];

    const bottomNavigation = [
        { name: "Profile", href: "admin.profile.index", icon: User, query: "" }, // Default tab, no query string
        { name: "Security", href: "admin.profile.index", icon: Shield, query: "?tab=security" },
        { name: "Appearance", href: "admin.profile.index", icon: Moon, query: "?tab=appearance" },
        { name: "Connections", href: "admin.profile.index", icon: ExternalLink, query: "?tab=connections" },
    ];

    const isKycActive = computed(() => route().current('admin.kyc.*'));
    const isConnectedWalletsActive = computed(() => route().current('admin.wallet.*'));
    const isUsersActive = computed(() => route().current('admin.users.*') || route().current('admin.network.*'));
    const isPlansActive = computed(() => route().current('admin.plans.*') || route().current('admin.time.*'));
    const isTransactionsActive = computed(() => route().current('admin.transaction.*') || route().current('admin.loans.*'));

    const isActive = (href: string) => route().current(href);
    const isProfileIndexActive = computed(() => {
        return route().current('admin.profile.index');
    });

    const isBottomLinkActive = (item: typeof bottomNavigation[0]) => {
        if (!isProfileIndexActive.value) {
            return false;
        }

        const currentUrl = page.url;

        if (item.query === "") {
            return isProfileIndexActive.value && !currentUrl.includes('?tab=');
        } else {
            return isProfileIndexActive.value && currentUrl.includes(item.query);
        }
    };

    const getBottomLinkHref = (item: typeof bottomNavigation[0]) => {
        return route(item.href) + (item.query.startsWith('?') ? item.query : '');
    };
</script>

<template>
    <div class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0">
        <div class="flex flex-col flex-grow bg-sidebar p-3 border-r border-sidebar-border">
            <div class="flex items-center px-4 py-4 pb-10">
                <div class="flex items-center">
                    <SiteLogo class="inline-flex items-center gap-2 select-none" />
                </div>
            </div>

            <nav class="flex-1 px-3 py-2 space-y-4">
                <template v-for="item in navigation" :key="item.name">
                    <TextLink
                        :href="route(item.href)"
                        class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                        :class="[
                            (isActive(item.href) ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30')
                        ]">
                        <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                        {{ item.name }}
                    </TextLink>
                </template>

                <li class="menu-title mt-4 mb-1">
                    <span data-key="t-gateways" class="text-xs font-semibold uppercase text-muted-foreground">Gateways</span>
                </li>
                <div class="space-y-1">
                    <template v-for="item in gatewaysNavigation" :key="item.name">
                        <TextLink
                            :href="route(item.href)"
                            class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="[
                                (item.name === 'Connected Wallets' && isConnectedWalletsActive) ||
                                (item.name === 'Investment Plans' && isPlansActive) ||
                                (isActive(item.href))
                                    ? 'bg-sidebar-accent text-sidebar-foreground'
                                    : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30'
                            ]">
                            <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                            {{ item.name }}
                        </TextLink>
                    </template>
                </div>

                <li class="menu-title mt-4 mb-1"><span data-key="t-users" class="text-xs font-semibold uppercase text-muted-foreground">Users</span></li>
                <div class="space-y-1">
                    <template v-for="item in userNavigation" :key="item.name">
                        <TextLink
                            :href="route(item.href)"
                            class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="[
                                isUsersActive || isActive(item.href)
                                    ? 'bg-sidebar-accent text-sidebar-foreground'
                                    : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30'
                            ]">
                            <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                            {{ item.name }}
                        </TextLink>
                    </template>
                </div>

                <li class="menu-title mt-4 mb-1">
                    <span data-key="t-financial" class="text-xs font-semibold uppercase text-muted-foreground">Financial Logs</span>
                </li>

                <div class="space-y-1">
                    <TextLink
                        v-for="item in transactionRoutes"
                        :key="item.name"
                        :href="route(item.href)"
                        class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                        :class="[
                            isTransactionsActive || isActive(item.href)
                                ? 'bg-sidebar-accent text-sidebar-foreground'
                                : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30'
                        ]">
                        <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                        {{ item.name }}
                    </TextLink>
                </div>

                <li class="menu-title mt-4 mb-1">
                    <span data-key="t-tools" class="text-xs font-semibold uppercase text-muted-foreground">Admin Tools</span>
                </li>

                <div class="space-y-1">
                    <template v-for="item in adminToolsNavigation" :key="item.name">
                        <TextLink
                            :href="route(item.href)"
                            class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="[
                                (item.name === 'KYC Submissions' && isKycActive.value) ||
                                (isActive(item.href) ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30')
                            ]">
                            <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                            {{ item.name }}
                        </TextLink>
                    </template>
                </div>
            </nav>

            <div class="px-3 py-2 space-y-1 mt-auto">
                <template v-for="item in bottomNavigation" :key="item.name">
                    <TextLink
                        :href="getBottomLinkHref(item)"
                        class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                        :class="[
                            isBottomLinkActive(item)
                                ? 'bg-sidebar-accent text-sidebar-foreground'
                                : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30'
                        ]">
                        <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                        {{ item.name }}
                    </TextLink>
                </template>

                <TextLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 text-sidebar-foreground/70 hover:bg-sidebar-accent/30 cursor-pointer">
                    <LogOut class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                    Logout
                </TextLink>
            </div>

            <div class="px-3 py-3">
                <TextLink :href="route('admin.profile.index')">
                    <div class="flex items-center p-2 rounded-lg bg-sidebar-accent transition-all duration-200">
                        <div class="w-7 h-7 bg-primary/50 rounded-full flex items-center justify-center">
                            <UserIcon class="h-4 w-4 text-accent-foreground" />
                        </div>
                        <span v-if="user" class="ml-2 text-sm font-medium text-sidebar-foreground">{{ user.first_name }} {{ user.last_name?.charAt(0) }}.</span>
                    </div>
                </TextLink>
            </div>
        </div>
    </div>
</template>

<style>
    /* Styles remain the same */
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
    .animate-fadeIn {
        animation: fadeIn 0.2s ease-out;
    }
    .max-h-80vh {
        max-height: 80vh;
    }
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .menu-title {
        list-style: none;
        padding: 0 12px;
    }
    .menu-title:first-child {
        margin-top: 0;
    }
</style>
