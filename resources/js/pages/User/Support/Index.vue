<script setup lang="ts">
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import TextLink from '@/components/TextLink.vue';
    import { computed, ref } from 'vue';
    import { MessageSquareIcon, MailIcon, MapPinIcon, PhoneIcon, HelpCircleIcon, ExternalLinkIcon, ClockIcon, ZapIcon, ArrowRightIcon, AlertTriangleIcon } from 'lucide-vue-next';

    const page = usePage();
    const user = computed(() => page.props.auth?.user ?? null);
    const site_email = computed(() => page.props.email);
    const siteEmailDisplay = computed(() => site_email.value || 'support@ourservice.com');

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const isNotificationsModalOpen = ref(false);
    const notificationCount = computed(() => page.props.auth?.notification_count ?? 0);

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Support & Contact' }
    ];

    const supportChannels = [
        {
            icon: MailIcon,
            title: 'General Support',
            description: 'For technical issues, account help, and non-urgent inquiries.',
            actionLabel: 'Email Support',
            href: '#',
            color: 'text-primary',
            bg: 'bg-primary/10',
            border: 'border-primary/20'
        },
        {
            icon: HelpCircleIcon,
            title: 'Help Center & FAQs',
            description: 'Find instant answers, tutorials, and guides in our comprehensive knowledge base.',
            actionLabel: 'Visit Knowledge Base',
            href: "#",
            color: 'text-success',
            bg: 'bg-success/10',
            border: 'border-success/20'
        },
        {
            icon: MessageSquareIcon,
            title: 'Live Chat',
            description: 'Real-time assistance for urgent, account-specific queries (24/7 coverage).',
            actionLabel: 'Start Chat',
            href: '#',
            color: 'text-accent',
            bg: 'bg-accent/10',
            border: 'border-accent/20'
        },
    ];

    const legalContact = [
        { label: 'Compliance & Legal Inquiries', email: siteEmailDisplay.value, icon: ZapIcon },
        { label: 'Registered Office Address', address: '123 Fintech Tower, Wilmington, Delaware, USA', icon: MapPinIcon },
    ];
</script>

<template>
    <Head title="Support & Contact" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="mt-8">
                <div class="w-full mx-auto">
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <div class="xl:col-span-2 space-y-6">
                            <div class="bg-gradient-to-br from-primary/10 via-primary/10 to-transparent rounded-2xl border border-primary/20 overflow-hidden p-6 sm:p-8">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                        <PhoneIcon class="w-6 h-6 sm:w-7 sm:h-7 text-primary" />
                                    </div>
                                    <div class="flex-1">
                                        <h2 class="text-xl sm:text-3xl font-bold text-card-foreground mb-1 sm:mb-2">Contact & Support Center</h2>
                                        <p class="text-xs sm:text-base text-muted-foreground">
                                            We're here to help! Choose the most appropriate channel below to get the assistance you need quickly.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-card rounded-2xl border border-border overflow-hidden p-6 sm:p-8">
                                <h3 class="text-lg sm:text-xl font-semibold text-card-foreground mb-6 border-b border-border/70 pb-3">
                                    How Can We Help You?
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div v-for="(channel, index) in supportChannels" :key="index"
                                         :class="['p-4 rounded-xl space-y-3 transition-shadow', channel.bg, channel.border, 'border']">

                                        <component :is="channel.icon" :class="['w-8 h-8', channel.color]" />

                                        <h4 class="text-base font-semibold text-card-foreground">{{ channel.title }}</h4>

                                        <p class="text-xs text-muted-foreground leading-relaxed">{{ channel.description }}</p>

                                        <div class="pt-2">
                                            <TextLink :href="channel.href" class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                                                {{ channel.actionLabel }}
                                                <ArrowRightIcon class="w-3 h-3 ml-1" />
                                            </TextLink>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-card rounded-2xl border border-border overflow-hidden p-6 sm:p-8">
                                <h3 class="text-lg sm:text-xl font-semibold text-card-foreground mb-6 border-b border-border/70 pb-3">
                                    Business and Legal Inquiries
                                </h3>

                                <div class="space-y-4">
                                    <div v-for="(item, index) in legalContact" :key="index" class="flex items-start gap-4 p-4 rounded-xl bg-muted/20">
                                        <component :is="item.icon" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                                        <div>
                                            <p class="text-sm font-medium text-card-foreground mb-1">{{ item.label }}</p>
                                            <p v-if="item.email" class="text-xs text-muted-foreground">
                                                Email: <a :href="`mailto:${item.email}`" class="text-primary hover:underline">{{ item.email }}</a>
                                            </p>
                                            <p v-else-if="item.address" class="text-xs text-muted-foreground">{{ item.address }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xl:col-span-1 space-y-6">
                            <div class="sticky top-6">
                                <div class="bg-gradient-to-br from-primary/10 to-primary/10 border border-primary/20 rounded-2xl p-6">
                                    <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                                        <ClockIcon class="w-5 h-5 text-primary" />
                                        Support Hours
                                    </h5>
                                    <ul class="space-y-3 text-sm">
                                        <li class="flex justify-between items-center">
                                            <span class="text-muted-foreground">General Email Support:</span>
                                            <span class="font-medium text-card-foreground">24/7</span>
                                        </li>
                                        <li class="flex justify-between items-center">
                                            <span class="text-muted-foreground">Live Chat:</span>
                                            <span class="font-medium text-card-foreground">24/7</span>
                                        </li>
                                        <li class="flex justify-between items-center">
                                            <span class="text-muted-foreground">Compliance Inquiries:</span>
                                            <span class="font-medium text-card-foreground">Mon-Fri, 9am - 5pm EST</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="bg-warning/10 border border-warning/20 rounded-2xl p-6 mt-6">
                                    <h5 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                                        <AlertTriangleIcon class="w-5 h-5" />
                                        Security Reminder
                                    </h5>
                                    <p class="text-xs text-muted-foreground">
                                        **Never share your private keys, seed phrase, or account password with anyone**, including support staff. We will never ask you for this information.
                                    </p>
                                </div>

                                <div class="text-center p-6 bg-gradient-to-br from-card to-muted/20 border border-border rounded-2xl mt-6 margin-bottom">
                                    <h5 class="text-sm font-semibold text-card-foreground mb-3">Quick Legal Links</h5>
                                    <div class="flex flex-col space-y-2">
                                        <TextLink :href="route('user.support.terms')" class="inline-flex items-center justify-center gap-2 text-sm font-medium text-primary hover:underline">
                                            Terms and Conditions
                                            <ExternalLinkIcon class="w-3 h-3" />
                                        </TextLink>

                                        <TextLink :href="route('user.support.privacy')" class="inline-flex items-center justify-center gap-2 text-sm font-medium text-primary hover:underline">
                                            Privacy Policy
                                            <ExternalLinkIcon class="w-3 h-3" />
                                        </TextLink>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
