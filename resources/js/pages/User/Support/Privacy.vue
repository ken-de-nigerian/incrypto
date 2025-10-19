<script setup lang="ts">
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import TextLink from '@/components/TextLink.vue';
    import { computed, ref } from 'vue';
    import { LockIcon, LayersIcon, ZapIcon, ExternalLinkIcon, InfoIcon, ListOrderedIcon, ArrowRightIcon, DatabaseIcon, ShieldCheckIcon } from 'lucide-vue-next';

    const page = usePage();
    const user = computed(() => page.props.auth?.user ?? null);
    const site_name = computed(() => page.props.name);
    const site_email = computed(() => page.props.email);

    const siteNameDisplay = computed(() => site_name.value || 'Our Service');
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
        { label: 'Privacy Policy' }
    ];

    const effectiveDate = 'October 14, 2025';

    // Expanded and detailed policy sections
    const policySections = [
        {
            id: 'section-1',
            title: '1. Information We Collect and Its Purpose',
            content: `We collect two main categories of information: **Personal Identification Data** (such as Name, email address, physical address, and required ID documents for KYC) and **Service Data** (transaction history, wallet addresses, IP address, device type, and login activity). This data is collected to facilitate services, verify your identity as required by law, and ensure the security of your account and the network.`
        },
        {
            id: 'section-2',
            title: '2. How We Use Your Information',
            content: `The data is strictly used to: (a) **Provide and maintain the Service**, including processing non-custodial transactions; (b) **Fulfill Legal and Regulatory Obligations** (AML/KYC, fraud prevention); (c) **Ensure Security** by monitoring for unauthorized access and illegal activities; (d) **Communicate with You** regarding service updates, security alerts, and support inquiries.`
        },
        {
            id: 'section-3',
            title: '3. Data Security and Storage Measures',
            content: `We prioritize the security of your information using industry-standard measures. This includes **Secure Socket Layer (SSL) encryption** for all data transmission, firewalls, and strict access control for our team. Note that because we are a non-custodial wallet, **we never store your private keys**, which remain solely in your possession and control.`
        },
        {
            id: 'section-4',
            title: '4. Sharing and Disclosure of Information',
            content: `We **do not sell or rent** your personally identifiable information. We only share data with trusted third parties under strict confidentiality agreements, including: (a) **KYC/AML Providers** to meet regulatory requirements; (b) **Cloud Infrastructure Providers** to host and operate the service; (c) **Law Enforcement** when legally compelled to do so by valid court order or regulatory request.`
        },
        {
            id: 'section-5',
            title: '5. Cookies and Tracking Technologies',
            content: `We use cookies, web beacons, and similar technologies to enhance your experience, track usage patterns, and secure the service. These are categorized as: (a) **Essential Cookies** (for login/session management); (b) **Performance Cookies** (for analytics like page load speed); and (c) **Marketing Cookies** (where consent is provided). You can manage cookie preferences in your browser settings.`
        },
        {
            id: 'section-6',
            title: '6. Your Data Protection Rights',
            content: `Depending on your location (e.g., GDPR, CCPA), you may have the right to: (a) **Access** your personal data; (b) **Correct** inaccurate data; (c) **Request Deletion** of your data (subject to legal retention requirements); (d) **Object** to the processing of your data. To exercise these rights, please contact our Data Protection Officer via the support links provided.`
        },
    ];
</script>

<template>
    <Head title="Privacy Policy" />

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
                                        <LockIcon class="w-6 h-6 sm:w-7 sm:h-7 text-primary" />
                                    </div>
                                    <div class="flex-1">
                                        <h2 class="text-xl sm:text-3xl font-bold text-card-foreground mb-1 sm:mb-2">Privacy Policy</h2>
                                        <p class="text-xs sm:text-base text-muted-foreground">
                                            This policy details how **{{ siteNameDisplay }}** collects, uses, protects, and discloses your personal information, reflecting our commitment to data security and compliance.
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-2">
                                            Last Updated: <span class="font-medium text-card-foreground">{{ effectiveDate }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-card rounded-2xl border border-border overflow-hidden p-5 sm:p-6">
                                <h3 class="text-base font-semibold text-card-foreground mb-4 flex items-center gap-2 border-b border-border/70 pb-3">
                                    <ListOrderedIcon class="w-5 h-5 text-primary" />
                                    Policy Sections
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3 pt-2">
                                    <a v-for="(section, index) in policySections" :key="index" :href="`#${section.id}`"
                                       class="flex items-center group text-xs sm:text-sm font-medium text-muted-foreground transition-colors hover:text-primary hover:bg-primary/10 p-2 rounded-lg -ml-2">
                                        <ArrowRightIcon class="w-3 h-3 text-primary flex-shrink-0 mr-2 opacity-75 group-hover:opacity-100" />
                                        <span class="truncate">{{ section.title }}</span>
                                    </a>
                                </div>
                            </div>

                            <div class="bg-card rounded-2xl border border-border overflow-hidden p-6 sm:p-8">
                                <div class="space-y-10">
                                    <div v-for="(section, index) in policySections" :key="index" :id="section.id" class="space-y-2">
                                        <h3 class="text-lg sm:text-xl font-semibold text-primary">{{ section.title }}</h3>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">{{ section.content }}</p>
                                    </div>

                                    <div class="mt-8 pt-6 border-t border-border">
                                        <h3 class="text-lg sm:text-xl font-semibold text-card-foreground mb-2">Policy Changes and Contact</h3>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed mb-4">
                                            We may update this policy periodically to reflect changes to our practices or for other operational, legal, or regulatory reasons. We will notify you of any material changes by updating the "Last Updated" date at the top of this policy.
                                        </p>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                                            For questions regarding your data or this policy, please contact our Data Protection Officer:
                                            <TextLink :href="`mailto:${siteEmailDisplay}`" class="text-primary hover:underline font-medium">
                                                {{ siteEmailDisplay }}
                                            </TextLink>.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xl:col-span-1 space-y-6">
                            <div class="sticky top-6">
                                <div class="bg-gradient-to-br from-primary/10 to-primary/10 border border-primary/20 rounded-2xl p-6 shadow-sm">
                                    <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                                        <ShieldCheckIcon class="w-5 h-5 text-primary" />
                                        Our Privacy Commitment
                                    </h5>
                                    <ul class="space-y-4">
                                        <li class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                <DatabaseIcon class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <h6 class="text-sm font-semibold text-card-foreground mb-1">No Private Key Storage</h6>
                                                <p class="text-xs text-muted-foreground">Your wallet keys are yours alone. We do not have access to or store them, ensuring true non-custodial security.</p>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                <LayersIcon class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <h6 class="text-sm font-semibold text-card-foreground mb-1">Data Minimization</h6>
                                                <p class="text-xs text-muted-foreground">We only collect data strictly necessary to provide our service and ensure compliance.</p>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                <ZapIcon class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <h6 class="text-sm font-semibold text-card-foreground mb-1">Global Compliance</h6>
                                                <p class="text-xs text-muted-foreground">Our data practices adhere to global privacy regulations where applicable.</p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <div class="bg-warning/10 border border-warning/20 rounded-2xl p-6 mt-6">
                                    <h5 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                                        <InfoIcon class="w-5 h-5" />
                                        Acceptance
                                    </h5>
                                    <p class="text-xs text-muted-foreground">
                                        By using our service, you acknowledge that you have read and understood this Privacy Policy and agree to the collection and use of information in accordance with it.
                                    </p>
                                </div>

                                <div class="text-center p-6 bg-gradient-to-br from-card to-muted/20 border border-border rounded-2xl mt-6 margin-bottom">
                                    <div class="w-12 h-12 rounded-full bg-primary/10 mx-auto mb-3 flex items-center justify-center">
                                        <InfoIcon class="w-6 h-6 text-primary" />
                                    </div>
                                    <h5 class="text-sm font-semibold text-card-foreground mb-2">Need to discuss your data?</h5>
                                    <p class="text-xs text-muted-foreground mb-4">
                                        For formal data access or deletion requests, please use our dedicated support channel.
                                    </p>
                                    <TextLink :href="route('user.support.index')" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                                        Contact Data Protection Officer
                                        <ExternalLinkIcon class="w-4 h-4" />
                                    </TextLink>
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
