<script setup lang="ts">
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import TextLink from '@/components/TextLink.vue';
    import { computed, ref } from 'vue';
    import { ShieldIcon, CheckCircleIcon, ExternalLinkIcon, InfoIcon, ListOrderedIcon, ArrowRightIcon, FileTextIcon } from 'lucide-vue-next';
    import { route } from 'ziggy-js';

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
        { label: 'Terms and Conditions' }
    ];

    // The date these terms are effective
    const effectiveDate = 'October 14, 2025';

    // The core terms content (content remains the same as the previous iteration)
    const termsSections = [
        {
            id: 'section-1',
            title: '1. Introduction and Acceptance of Terms',
            content: `By accessing or using the services provided by [SITE_NAME] (hereinafter referred to as "the Service", "we", "us", or "our"), you agree to be bound by these **Terms and Conditions** ("Terms"), our <a href="${route('user.support.privacy')}" class="text-primary font-medium hover:underline">Privacy Policy</a>, <a href="${route('user.support.aml')}" class="text-primary font-medium hover:underline">Anti-Money Laundering (AML) Policy</a>, and any additional policies or agreements referenced herein. If you do not agree to these Terms in their entirety, you must not access or use the Service. These Terms constitute a legally binding agreement between you and [SITE_NAME]. We reserve the right to update or modify these Terms at any time, and such changes will be effective upon posting on our website. Your continued use of the Service after any such changes constitutes your acceptance of the revised Terms.`,
        },
        {
            id: 'section-2',
            title: '2. Eligibility and User Representations',
            content: `To use the Service, you must be at least **18 years of age** or the age of legal majority in your jurisdiction, whichever is greater, and have the legal capacity to enter into this agreement. By using the Service, you represent and warrant that: (a) you are not located in, or a resident of, any country or region subject to comprehensive sanctions or embargoes by the United States, the European Union, or other applicable jurisdictions; (b) you are not listed on any government sanctions list, including but not limited to the U.S. Department of Treasury’s Specially Designated Nationals List; and (c) you will not use the Service for any illegal or unauthorized purpose, including but not limited to money laundering, terrorist financing, or other illicit activities.`,
        },
        {
            id: 'section-3',
            title: '3. Description of Services - Non-Custodial Wallet',
            content: `[SITE_NAME] provides a **non-custodial digital wallet** for storing, sending, receiving, and managing supported cryptocurrencies and digital assets. **[SITE_NAME] does not have custody or control over the digital assets stored in your wallet.** You retain full control over your **private keys** and are solely responsible for their security. The Service is provided "as is" and "as available," and we do not guarantee uninterrupted or error-free operation.`,
        },
        {
            id: 'section-4',
            title: '4. Account Registration, Security, and Private Keys',
            content: `To access certain features, you must create an account by providing accurate, current, and complete information. You are solely responsible for maintaining the confidentiality of your account credentials, including your **password and private keys**. You must notify [SITE_NAME] immediately of any unauthorized access or breach. **[SITE_NAME] is not liable for any losses or damages arising from your failure to safeguard your account information or private keys.** Loss of private keys will result in the permanent, irreversible loss of access to your digital assets, and [SITE_NAME] cannot recover or restore such assets.`,
        },
        {
            id: 'section-5',
            title: '5. Acknowledgment of Cryptocurrency Risks',
            content: `You acknowledge that cryptocurrencies and digital assets are subject to **significant, inherent risks**, including but not limited to: (a) **High price volatility** leading to substantial financial losses; (b) **Regulatory changes** that may affect legality or value; (c) **Security risks** like hacking, phishing, or cyberattacks; (d) **Technological risks**, including software bugs, blockchain forks, or network failures; and (e) **Market risks** such as liquidity shortages. By using the Service, you acknowledge and accept these risks and agree that [SITE_NAME] is not responsible for any losses, damages, or liabilities arising from your use of the Service or the inherent risks of digital assets.`,
        },
        {
            id: 'section-6',
            title: '6. Prohibited Activities and Account Suspension',
            content: `You agree not to engage in any of the following activities while using the Service: (a) violating any applicable laws or regulations; (b) using the Service for fraudulent, illegal, or unauthorized purposes, including **money laundering or terrorist financing**; (c) attempting unauthorized access; (d) interfering with or disrupting the Service (e.g., viruses, malware); (e) engaging in activities that could harm [SITE_NAME]’s reputation. [SITE_NAME] reserves the right to immediately suspend or **terminate your account** and access to the Service if you engage in any prohibited activities, or if required by law or regulation.`,
        },
        {
            id: 'section-7',
            title: '7. Fees, Network Charges, and Transaction Processing',
            content: `Use of the Service may incur fees, including transaction fees, **blockchain network fees** (e.g., "gas fees"), and service fees for premium features. All applicable fees will be clearly disclosed to you prior to completing a transaction. You are responsible for paying all such fees. **We do not control blockchain network fees (gas)**, and they are subject to network congestion and change. [SITE_NAME] reserves the right to modify its fee structure at any time, with changes posted on the website.`,
        },
        {
            id: 'section-8',
            title: '8. Intellectual Property and Limited License',
            content: `All content, trademarks, logos, and intellectual property associated with the Service are the exclusive property of [SITE_NAME] or its licensors. You are granted a limited, **non-exclusive, non-transferable license** to use the Service for personal, non-commercial purposes only. You may not copy, modify, distribute, sell, or otherwise exploit any part of the Service without [SITE_NAME]’s prior written consent.`,
        },
        {
            id: 'section-9',
            title: '9. Third-Party Services and External Links',
            content: `The Service may integrate with or provide links to **third-party services**, such as cryptocurrency exchanges, decentralized applications (dApps), or market data providers. [SITE_NAME] is not responsible for the availability, content, or security of such third-party services. Your use of third-party services is **at your own risk** and subject to the terms and conditions of those providers.`,
        },
        {
            id: 'section-10',
            title: '10. Disclaimer of Warranties and Limitation of Liability',
            content: `THE SERVICE IS PROVIDED ON AN "**AS IS**" AND "**AS AVAILABLE**" BASIS. [SITE_NAME] makes no warranties of any kind, express or implied. To the fullest extent permitted by law, [SITE_NAME], its affiliates, and its agents shall **not be liable** for any direct, indirect, incidental, special, consequential, or punitive damages arising out of or related to your use of the Service. This includes, but is not limited to, losses due to: (a) loss of private keys; (b) unauthorized access to your account; (c) errors, delays, or interruptions in the Service; or (d) fluctuations in the value of digital assets. **[SITE_NAME]’s total liability** to you for any claim arising under these Terms shall not exceed the amount of fees paid by you to [SITE_NAME] in the twelve (12) months preceding the claim.`,
        },
        {
            id: 'section-11',
            title: '11. Indemnification',
            content: `You agree to **indemnify, defend, and hold harmless** [SITE_NAME], its affiliates, officers, directors, employees, and agents from any claims, liabilities, damages, losses, or expenses, including reasonable attorneys’ fees, arising out of or related to: (a) your use of the Service; (b) your violation of these Terms; (c) your violation of any applicable laws or regulations; or (d) your infringement of any third-party rights.`,
        },
        {
            id: 'section-12',
            title: '12. Termination and Suspension',
            content: `[SITE_NAME] reserves the right to suspend or **terminate your access** to the Service at its sole discretion, with or without notice, for any reason, including but not limited to your violation of these Terms, suspected fraudulent activity, or regulatory compliance requirements. Upon termination, your right to use the Service will cease immediately, but these Terms will continue to apply to any prior use of the Service.`,
        },
        {
            id: 'section-13',
            title: '13. Governing Law and Dispute Resolution',
            content: `These Terms shall be governed by and construed in accordance with the laws of the **State of Delaware, United States**. Any disputes arising under or in connection with these Terms shall be resolved through **binding arbitration** in Wilmington, Delaware, in accordance with the rules of the American Arbitration Association. You agree to **waive your right to participate in a class action lawsuit** or class-wide arbitration against [SITE_NAME].`,
        },
        {
            id: 'section-14',
            title: '14. Force Majeure (Unforeseen Events)',
            content: `[SITE_NAME] shall not be liable for any failure or delay in performing its obligations under these Terms due to events beyond its reasonable control, including but not limited to natural disasters, war, terrorism, government actions, or disruptions in internet or blockchain networks.`,
        },
        {
            id: 'section-15',
            title: '15. Miscellaneous and Entire Agreement',
            content: `These Terms constitute the **entire agreement** between you and [SITE_NAME] regarding the use of the Service, superseding any prior agreements. If any provision is found to be invalid or unenforceable, the remaining provisions shall remain in full force and effect. You may not assign or transfer your rights without [SITE_NAME]’s prior written consent.`,
        }
    ];
</script>

<template>
    <Head title="Terms and Conditions" />

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
                                        <FileTextIcon class="w-6 h-6 sm:w-7 sm:h-7 text-primary" />
                                    </div>
                                    <div class="flex-1">
                                        <h2 class="text-xl sm:text-3xl font-bold text-card-foreground mb-1 sm:mb-2">Terms and Conditions</h2>
                                        <p class="text-xs sm:text-base text-muted-foreground">
                                            This legally binding document outlines the rules, risks, and responsibilities governing your use of **{{ siteNameDisplay }}'s** non-custodial digital wallet and related services.
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
                                    Navigate Document (Table of Contents)
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3 pt-2">
                                    <a v-for="(section, index) in termsSections" :key="index" :href="`#${section.id}`"
                                       class="flex items-center group text-xs sm:text-sm font-medium text-muted-foreground transition-colors hover:text-primary hover:bg-primary/10 p-2 rounded-lg -ml-2">
                                        <ArrowRightIcon class="w-3 h-3 text-primary flex-shrink-0 mr-2 opacity-75 group-hover:opacity-100" />
                                        <span class="truncate">{{ section.title }}</span>
                                    </a>
                                </div>
                            </div>
                            <div class="bg-card rounded-2xl border border-border overflow-hidden p-6 sm:p-8">
                                <div class="space-y-10">
                                    <div v-for="(section, index) in termsSections" :key="index" :id="section.id" class="space-y-3">
                                        <h3 class="text-lg sm:text-xl font-semibold text-primary">{{ section.title }}</h3>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed" v-html="section.content.replaceAll('[SITE_NAME]', siteNameDisplay)"></p>
                                    </div>

                                    <div class="mt-8 pt-6 border-t border-border">
                                        <h3 class="text-lg sm:text-xl font-semibold text-card-foreground mb-2">Contact Us</h3>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                                            If you have any questions, concerns, or need clarification about these Terms and Conditions, please contact our dedicated support team.
                                        </p>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed mt-2">
                                            Email Support:
                                            <TextLink :href="`mailto:${siteEmailDisplay}`" class="text-primary hover:underline font-medium">
                                                {{ siteEmailDisplay }}
                                            </TextLink>
                                        </p>
                                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                                            Visit our Support Center:
                                            <TextLink :href="route('user.support.index')" class="text-primary hover:underline font-medium">
                                                Go to Support
                                            </TextLink>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xl:col-span-1 space-y-6">
                            <div class="sticky top-6">
                                <div class="bg-gradient-to-br from-primary/10 to-primary/10 border border-primary/20 rounded-2xl p-6">
                                    <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                                        <ShieldIcon class="w-5 h-5 text-primary" />
                                        Important Legal Documents
                                    </h5>
                                    <ul class="space-y-3">
                                        <li class="text-sm">
                                            <TextLink :href="route('user.support.privacy')" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors">
                                                <InfoIcon class="w-4 h-4" />
                                                Privacy Policy
                                            </TextLink>
                                        </li>

                                        <li class="text-sm">
                                            <TextLink :href="route('user.support.aml')" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors">
                                                <CheckCircleIcon class="w-4 h-4" />
                                                AML/KYC Policy
                                            </TextLink>
                                        </li>
                                    </ul>
                                </div>

                                <div class="bg-warning/10 border border-warning/20 rounded-2xl p-6 mt-6">
                                    <h5 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                                        <InfoIcon class="w-5 h-5" />
                                        Crucial Legal Disclaimer
                                    </h5>
                                    <p class="text-xs text-muted-foreground">
                                        The Service is a technological tool for managing digital assets. It is not a financial institution, bank, or broker. The information provided on this page and the platform is for informational purposes only and does not constitute **legal, financial, or investment advice**. Cryptocurrency investments carry significant inherent risks, and you should consult with a qualified professional before making any financial decisions.
                                    </p>
                                </div>

                                <div class="text-center p-6 bg-gradient-to-br from-card to-muted/20 border border-border rounded-2xl mt-6 margin-bottom">
                                    <div class="w-12 h-12 rounded-full bg-primary/10 mx-auto mb-3 flex items-center justify-center">
                                        <InfoIcon class="w-6 h-6 text-primary" />
                                    </div>
                                    <h5 class="text-sm font-semibold text-card-foreground mb-2">Need Assistance?</h5>
                                    <p class="text-xs text-muted-foreground mb-4">
                                        Our support team is available to answer any general questions about these Terms or the Service's operation.
                                    </p>
                                    <TextLink :href="route('user.support.index')" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                                        Contact Support
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
