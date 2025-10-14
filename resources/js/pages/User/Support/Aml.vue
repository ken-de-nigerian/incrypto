<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import TextLink from '@/components/TextLink.vue';
    import { computed, ref } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import { GavelIcon, UserCheckIcon, ScanIcon, AlertTriangleIcon, ExternalLinkIcon, InfoIcon, ListOrderedIcon, ArrowRightIcon, DollarSignIcon } from 'lucide-vue-next';

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
        { label: 'AML/KYC Policy' }
    ];

    const effectiveDate = 'October 14, 2025';

    // Detailed AML/KYC policy sections
    const policySections = [
        {
            id: 'section-1',
            title: '1. AML/KYC Policy Statement',
            content: `**${siteNameDisplay.value}** is fully committed to preventing money laundering, terrorist financing, and all forms of illicit financial activity. This Anti-Money Laundering (AML) and Know Your Customer (KYC) Policy outlines the mandatory procedures we implement to comply with global regulatory standards and protect our platform from criminal abuse. Compliance with this policy is a prerequisite for using our service.`
        },
        {
            id: 'section-2',
            title: '2. Know Your Customer (KYC) Procedures',
            content: `To establish and verify the identity of our users, we require the submission of mandatory documentation, which may vary based on risk level and jurisdiction. This typically includes: (a) **Proof of Identity** (e.g., valid government-issued ID, passport); (b) **Proof of Residence** (e.g., utility bill, bank statement); and (c) **Facial Verification** (e.g., a "selfie" or video verification). Identity data is securely handled and stored in accordance with our <a href="${route('user.support.privacy')}" class="text-primary font-medium hover:underline">Privacy Policy</a>.`
        },
        {
            id: 'section-3',
            title: '3. Risk-Based Approach (RBA)',
            content: `We adopt a Risk-Based Approach to assess and mitigate potential risks associated with each user. This involves: (a) **Initial Risk Scoring** based on geographic location, service utilization, and public sanctions lists; (b) **Ongoing Due Diligence** for higher-risk users or sudden changes in activity; and (c) **Enhanced Due Diligence (EDD)** for Politically Exposed Persons (PEPs) or users in high-risk jurisdictions. This tiered approach ensures compliance efforts are proportional to the identified risk.`
        },
        {
            id: 'section-4',
            title: '4. Transaction Monitoring and Reporting',
            content: `All transactions are subject to real-time and post-execution monitoring. Our system automatically scans for suspicious activity, including: (a) large, unusual transactions outside a user's normal pattern; (b) multiple transactions structured to circumvent reporting thresholds (**structuring**); and (c) transactions involving known suspicious wallet addresses. If suspicious activity is detected, we are obligated to file a **Suspicious Activity Report (SAR)** with the relevant financial intelligence unit without notifying the user.`
        },
        {
            id: 'section-5',
            title: '5. Non-Custodial Compliance Disclaimer',
            content: `As a non-custodial wallet service, **${siteNameDisplay.value} does not have control over your digital assets or your private keys.** Our AML/KYC efforts focus solely on verifying the identity of the account holder accessing our platform interface and monitoring transaction data as it relates to the account. We cannot block or reverse transactions once they are broadcast to the blockchain, but we can suspend or terminate access to our interface for non-compliant users.`
        },
        {
            id: 'section-6',
            title: '6. Sanctions Compliance and Screening',
            content: `We rigorously screen all new and existing users against global sanctions lists, including those maintained by the United Nations, the US Office of Foreign Assets Control (OFAC), and the European Union. Any discovered matches or associations with sanctioned individuals, entities, or jurisdictions will result in the immediate and permanent **termination of service access** and required reporting to regulatory bodies.`
        },
        {
            id: 'section-7',
            title: '7. Compliance Officer',
            content: `**${siteNameDisplay.value}** has appointed a dedicated **AML Compliance Officer** who is responsible for overseeing the effective implementation and enforcement of this policy. The Compliance Officer is responsible for staff training, maintaining records, coordinating with law enforcement, and updating procedures to reflect new regulations.`
        },
    ];
</script>

<template>
    <Head title="AML/KYC Policy" />

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
                            <div class="bg-gradient-to-br from-primary/10 via-primary/5 to-transparent rounded-2xl border border-primary/20 overflow-hidden p-6 sm:p-8">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                        <GavelIcon class="w-6 h-6 sm:w-7 sm:h-7 text-primary" />
                                    </div>
                                    <div class="flex-1">
                                        <h2 class="text-xl sm:text-3xl font-bold text-card-foreground mb-1 sm:mb-2">AML/KYC Policy</h2>
                                        <p class="text-xs sm:text-base text-muted-foreground">
                                            Our commitment to global anti-money laundering (AML) and counter-terrorist financing (CTF) standards through mandatory **Know Your Customer (KYC)** procedures.
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-2">
                                            Effective Date: <span class="font-medium text-card-foreground">{{ effectiveDate }}</span>
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
                                       class="flex items-center group text-xs sm:text-sm font-medium text-muted-foreground transition-colors hover:text-primary hover:bg-primary/5 p-2 rounded-lg -ml-2">
                                        <ArrowRightIcon class="w-3 h-3 text-primary flex-shrink-0 mr-2 opacity-75 group-hover:opacity-100" />
                                        <span class="truncate">{{ section.title }}</span>
                                    </a>
                                </div>
                            </div>

                            <div class="bg-card rounded-2xl border border-border overflow-hidden p-6 sm:p-8">
                                <div class="space-y-10">
                                    <div v-for="(section, index) in policySections" :key="index" :id="section.id" class="space-y-2">
                                        <h3 class="text-lg sm:text-xl font-semibold text-primary">{{ section.title }}</h3>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed" v-html="section.content.replaceAll('[SITE_NAME]', siteNameDisplay)"></p>
                                    </div>

                                    <div class="mt-8 pt-6 border-t border-border">
                                        <h3 class="text-lg sm:text-xl font-semibold text-card-foreground mb-2">Compliance and Enforcement</h3>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed mb-4">
                                            Failure to comply with our KYC requirements or any involvement in suspicious activity will result in the immediate **suspension or termination** of your access to the {{ siteNameDisplay }} interface. We cooperate fully with law enforcement and regulatory inquiries.
                                        </p>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                                            For questions regarding our AML/KYC policies or procedures, please contact our Compliance Officer:
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
                                <div class="bg-gradient-to-br from-primary/5 to-transparent border border-primary/20 rounded-2xl p-6">
                                    <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                                        <UserCheckIcon class="w-5 h-5 text-primary" />
                                        Key Compliance Principles
                                    </h5>
                                    <ul class="space-y-4">
                                        <li class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                <ScanIcon class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <h6 class="text-sm font-semibold text-card-foreground mb-1">Mandatory Verification</h6>
                                                <p class="text-xs text-muted-foreground">All users must complete identity verification (KYC) before accessing full platform services.</p>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                <DollarSignIcon class="w-5 h-5 text-primary" />
                                            </div>
                                            <div>
                                                <h6 class="text-sm font-semibold text-card-foreground mb-1">Transaction Monitoring</h6>
                                                <p class="text-xs text-muted-foreground">We use automated systems to detect and flag suspicious transaction patterns for human review.</p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <div class="bg-warning/5 border border-warning/20 rounded-2xl p-6 mt-6">
                                    <h5 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                                        <AlertTriangleIcon class="w-5 h-5" />
                                        Important Warning
                                    </h5>
                                    <p class="text-xs text-muted-foreground">
                                        **Attempting to bypass KYC or engaging in illicit activities will lead to the irreversible suspension of your account and mandatory reporting to law enforcement.** We have zero tolerance for financial crime.
                                    </p>
                                </div>

                                <div class="text-center p-6 bg-gradient-to-br from-card to-muted/20 border border-border rounded-2xl mt-6 margin-bottom">
                                    <div class="w-12 h-12 rounded-full bg-primary/10 mx-auto mb-3 flex items-center justify-center">
                                        <InfoIcon class="w-6 h-6 text-primary" />
                                    </div>
                                    <h5 class="text-sm font-semibold text-card-foreground mb-2">Questions about compliance?</h5>
                                    <p class="text-xs text-muted-foreground mb-4">
                                        Our Compliance Team can answer questions regarding our verification and monitoring procedures.
                                    </p>
                                    <TextLink :href="route('user.support.index')" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                                        Contact Compliance
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
