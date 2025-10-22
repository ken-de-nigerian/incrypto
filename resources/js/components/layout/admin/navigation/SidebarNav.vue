<script setup lang="ts">
    import {
        LayoutDashboard,
        Users,
        Shield,
        FileText,
        UserIcon,
        Mail,
        LogOut,
        CreditCard,
        Send,
        Download,
        Repeat,
        ChevronDown,
        ChevronUp,
        Wallet,
        Settings,
    } from 'lucide-vue-next';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { computed, defineAsyncComponent, ref } from 'vue';
    import { useForm, usePage } from '@inertiajs/vue3';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';
    import("@vueup/vue-quill/dist/vue-quill.snow.css");

    const QuillEditor = defineAsyncComponent(() =>
        import("@vueup/vue-quill").then(module => {
            return module.QuillEditor;
        })
    );

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    const isTransactionsOpen = ref(false);
    const isCreateCampaignModalOpen = ref(false);

    const deliveryMethods = [
        { value: 'email', label: 'Email' },
        { value: 'db', label: 'In-App' },
        { value: 'both', label: 'Both' },
    ];

    const openModal = (modalName: string) => {
        resetFormData();
        campaignForm.clearErrors();
        if (modalName === 'createCampaignModalOpen') {
            isCreateCampaignModalOpen.value = true;
        }
    };

    const closeAllModals = () => {
        isCreateCampaignModalOpen.value = false;
    };

    const campaignForm = useForm({
        subject: '',
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
                closeAllModals();
                resetFormData();
            },
        });
    };

    const navigation = [
        { name: "Dashboard", href: "admin.dashboard", icon: LayoutDashboard },
    ];

    const gatewaysNavigation = [
        { name: "Connected Wallets", href: "admin.wallet.index", icon: Wallet },
        { name: "Crypto Methods", href: "admin.dashboard", icon: Shield },
    ];

    const userNavigation = [
        { name: "Manage Users", href: "admin.users.index", icon: Users },
    ];

    const transactionSubRoutes = [
        { name: "All Transactions", href: "admin.dashboard", icon: CreditCard },
        { name: "Send Operations", href: "admin.send.index", icon: Send },
        { name: "Receive Operations", href: "admin.receive.index", icon: Download },
        { name: "Swap/Trade Logs", href: "admin.swap.index", icon: Repeat }
    ];

    const adminToolsNavigation = [
        { name: "KYC Submissions", href: "admin.kyc.index", icon: FileText },
        { name: "Send Notifications", href: "admin.notifications.index", icon: Mail }
    ];

    const bottomNavigation = [
        { name: "Settings", href: "admin.profile.index", icon: Settings },
    ];

    const isSettingsActive = computed(() => route().current('admin.profile.*'));
    const isKycActive = computed(() => route().current('admin.kyc.*'));
    const isConnectedWalletsActive = computed(() => route().current('admin.wallet.*'));
    const isUsersActive = computed(() => route().current('admin.users.*'));

    const isActive = (href: string) => route().current(href);

    const isTransactionGroupActive = computed(() => {
        return transactionSubRoutes.some(item => route().current(item.href));
    });

    if (isTransactionGroupActive.value) {
        isTransactionsOpen.value = true;
    }

    const clearError = (campaignForm: any, field: string) => {
        if (campaignForm.errors[field]) {
            campaignForm.clearErrors(field);
        }
    };
</script>

<template>
    <div class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0">
        <div class="flex flex-col flex-grow bg-sidebar p-3 border-r border-sidebar-border">
            <div class="flex items-center px-4 py-4 pb-10">
                <div class="flex items-center">
                    <TextLink :href="route('admin.dashboard')" aria-label="Admin Dashboard" class="inline-flex items-center gap-2 select-none">
                        <img class="w-[150px]" src="/assets/images/logo.png" alt="Admin logo">
                    </TextLink>
                </div>
            </div>

            <nav class="flex-1 px-3 py-2 space-y-6">
                <div class="margin-top">
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
                </div>

                <li class="menu-title pt-1">
                    <span data-key="t-gateways" class="text-xs font-semibold uppercase text-muted-foreground">Gateways</span>
                </li>
                <div class="margin-top">
                    <template v-for="item in gatewaysNavigation" :key="item.name">
                        <TextLink
                            :href="route(item.href)"
                            class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="[
                                (item.name === 'Connected Wallets' && isConnectedWalletsActive) ||
                                (isActive(item.href) ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30')
                            ]">
                            <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                            {{ item.name }}
                        </TextLink>
                    </template>
                </div>

                <li class="menu-title pt-1"><span data-key="t-users" class="text-xs font-semibold uppercase text-muted-foreground">Users</span></li>
                <div class="margin-top">
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

                <li class="menu-title pt-1">
                    <span data-key="t-financial" class="text-xs font-semibold uppercase text-muted-foreground">Financial Logs</span>
                </li>

                <div class="margin-top">
                    <button
                        @click="isTransactionsOpen = !isTransactionsOpen"
                        class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 cursor-pointer"
                        :class="[
                            isTransactionGroupActive ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30'
                        ]">
                        <CreditCard class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                        Transactions
                        <component :is="isTransactionsOpen ? ChevronUp : ChevronDown" class="ml-auto h-4 w-4 transition-transform duration-200" />
                    </button>

                    <div v-if="isTransactionsOpen" class="mt-2 space-y-1 pl-6 animate-fadeIn">
                        <TextLink
                            v-for="subItem in transactionSubRoutes"
                            :key="subItem.name"
                            :href="route(subItem.href)"
                            class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200"
                            :class="[
                                isActive(subItem.href)
                                    ? 'bg-sidebar-accent text-sidebar-foreground font-semibold'
                                    : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30'
                            ]">
                            <component :is="subItem.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                            {{ subItem.name }}
                        </TextLink>
                    </div>
                </div>

                <li class="menu-title pt-1">
                    <span data-key="t-tools" class="text-xs font-semibold uppercase text-muted-foreground">Admin Tools</span>
                </li>

                <div class="margin-top">
                    <template v-for="item in adminToolsNavigation" :key="item.name">
                        <button
                            v-if="item.name === 'Send Notifications'"
                            @click="openModal('createCampaignModalOpen')"
                            class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 cursor-pointer text-sidebar-foreground/70 hover:bg-sidebar-accent/30">
                            <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                            {{ item.name }}
                        </button>
                        <TextLink
                            v-else
                            :href="route(item.href)"
                            class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="[
                                (item.name === 'KYC Submissions' && isKycActive) ||
                                (isActive(item.href) ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30')
                            ]">
                            <component :is="item.icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                            {{ item.name }}
                        </TextLink>
                    </template>
                </div>
            </nav>

            <div class="px-3 py-2 space-y-3 mt-auto">
                <TextLink
                    :href="route('admin.profile.index')"
                    class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                    :class="[
                        isSettingsActive ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/30'
                    ]">
                    <component :is="bottomNavigation[0].icon" class="mr-3 h-4 w-4 text-sidebar-foreground/70" />
                    {{ bottomNavigation[0].name }}
                </TextLink>

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
                    <div class="flex items-center p-2 rounded-lg bg-sidebar-accent/20 hover:bg-sidebar-accent/40 transition-all duration-200">
                        <div class="w-7 h-7 bg-accent rounded-full flex items-center justify-center">
                            <UserIcon class="h-4 w-4 text-accent-foreground" />
                        </div>
                        <span v-if="user" class="ml-2 text-sm font-medium text-sidebar-foreground">{{ user.first_name }} {{ user.last_name?.charAt(0) }}.</span>
                    </div>
                </TextLink>
            </div>
        </div>
    </div>

    <!-- Create Campaign Modal -->
    <QuickActionModal
        :is-open="isCreateCampaignModalOpen"
        title="Create Newsletter Campaign"
        subtitle="Set up and schedule your newsletter broadcast"
        @close="closeAllModals">

        <form @submit.prevent="createCampaign" class="space-y-4">
            <!-- Subject -->
            <div class="space-y-2">
                <label for="subject" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Email Subject</label>
                <input id="subject" v-model="campaignForm.subject" @focus="clearError(emailForm, 'subject')" type="text" placeholder="Important Account Notice" class="input-crypto w-full text-sm" />
                <InputError :message="campaignForm.errors.subject" />
            </div>

            <!-- Content -->
            <div class="space-y-2">
                <label for="content" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Content</label>
                <QuillEditor id="content" v-model:content="campaignForm.content" contentType="html" theme="snow" placeholder="Your newsletter content..." toolbar="full" />
                <InputError :message="campaignForm.errors.content" />
            </div>

            <!-- Delivery Method -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Delivery Method</label>
                <div class="grid grid-cols-3 gap-2">
                    <button v-for="method in deliveryMethods" :key="method.value" type="button" @click="campaignForm.deliveryMethod = method.value as any" :class="`p-3 rounded-lg border-2 text-center cursor-pointer transition text-xs font-medium ${campaignForm.deliveryMethod === method.value ? 'border-primary bg-primary/10' : 'border-border hover:border-border/60'}`">
                        {{ method.label }}
                    </button>
                </div>
                <InputError :message="campaignForm.errors.deliveryMethod" />
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton :processing="campaignForm.processing">
                    Create Campaign
                </ActionButton>
            </div>
        </form>
    </QuickActionModal>
</template>

<style>
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
        margin-top: 1.5rem;
        margin-bottom: 0;
    }
    .menu-title:first-child {
        margin-top: 0;
    }
    .margin-top{
        margin-top: 10px !important;
    }
    .ql-editor {
        min-height: 200px !important;
    }

    /* Customize the editor appearance */
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
</style>
