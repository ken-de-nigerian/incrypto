<script setup lang="ts">
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import { computed, ref } from 'vue';
    import {
        ShieldCheckIcon,
        AlertCircleIcon,
        InfoIcon,
        ClockIcon,
        CheckCircleIcon,
        FileTextIcon,
        LockIcon,
        ExternalLinkIcon
    } from 'lucide-vue-next';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import KycEditForm from '@/pages/User/Kyc/Partials/KycEditForm.vue';
    import TextLink from '@/components/TextLink.vue';

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    defineProps<{
        submission: object;
    }>();

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const isNotificationsModalOpen = ref(false);
    const notificationCount = computed(() => page.props.auth.notification_count);

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };

    const verificationSteps = [
        { step: 1, title: 'Fill Basic Details', desc: 'Provide accurate personal information', completed: false },
        { step: 2, title: 'Upload ID Documents', desc: 'Government-issued identification', completed: false },
        { step: 3, title: 'Verify Address', desc: 'Proof of residential address', completed: false },
        { step: 4, title: 'Review & Submit', desc: 'Confirm all details are correct', completed: false }
    ];

    const importantNotes = [
        { icon: AlertCircleIcon, text: 'All documents must be valid and not expired', color: 'text-warning' },
        { icon: FileTextIcon, text: 'Ensure documents are clear and readable', color: 'text-muted-foreground' },
        { icon: ClockIcon, text: 'Verification typically takes 24-48 hours', color: 'text-muted-foreground' },
        { icon: LockIcon, text: 'Your data is encrypted and secure', color: 'text-primary' }
    ];

    const dataProtection = [
        'All personal data is encrypted with AES-256 encryption',
        'We comply with GDPR and international data protection laws',
        'Your documents are stored on secure, isolated servers',
        'Access is restricted to authorized compliance officers only',
        'Data is automatically deleted after verification if not approved'
    ];

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Resubmit KYC Verification' }
    ];
</script>

<template>
    <Head title="Resubmit KYC Verification" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="flex-1">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mx-auto">
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-card border border-border rounded-xl p-5 top-6">
                            <div class="flex items-center gap-2 mb-4">
                                <ShieldCheckIcon class="w-5 h-5 text-primary" />
                                <h3 class="text-base font-semibold text-card-foreground">Verification Steps</h3>
                            </div>
                            <div class="space-y-4">
                                <div v-for="step in verificationSteps" :key="step.step" class="flex gap-3">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center border-2 flex-shrink-0"
                                            :class="step.completed ? 'bg-primary/10 border-primary text-primary' : 'bg-secondary border-border text-muted-foreground'"
                                        >
                                            <CheckCircleIcon v-if="step.completed" class="w-4 h-4" />
                                            <span v-else class="text-xs font-semibold">{{ step.step }}</span>
                                        </div>
                                        <div v-if="step.step < 4" class="w-0.5 h-8 bg-border mt-1"></div>
                                    </div>
                                    <div class="flex-1 pb-2">
                                        <p class="text-sm font-medium text-card-foreground">{{ step.title }}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5">{{ step.desc }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-card border border-border rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <InfoIcon class="w-5 h-5 text-muted-foreground" />
                                <h3 class="text-base font-semibold text-card-foreground">Important Notes</h3>
                            </div>
                            <ul class="space-y-3">
                                <li v-for="(note, index) in importantNotes" :key="index" class="flex items-start gap-3">
                                    <component :is="note.icon" class="w-4 h-4 flex-shrink-0 mt-0.5" :class="note.color" />
                                    <span class="text-xs text-muted-foreground">{{ note.text }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="bg-gradient-to-br from-primary/5 to-transparent border border-primary/20 rounded-xl p-5">
                            <h3 class="text-base font-semibold text-card-foreground mb-3">Why KYC is Required?</h3>
                            <p class="text-xs text-muted-foreground leading-relaxed mb-3">
                                KYC (Know Your Customer) verification is a regulatory requirement to prevent fraud, money laundering, and ensure platform security.
                            </p>
                            <div class="space-y-2">
                                <div class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                                    <span class="text-xs text-muted-foreground">Protects your account from unauthorized access</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                                    <span class="text-xs text-muted-foreground">Enables higher transaction limits</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <CheckCircleIcon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                                    <span class="text-xs text-muted-foreground">Complies with global regulations</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-card border border-border rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <LockIcon class="w-5 h-5 text-primary" />
                                <h3 class="text-base font-semibold text-card-foreground">Data Protection</h3>
                            </div>
                            <ul class="space-y-2">
                                <li v-for="(item, index) in dataProtection" :key="index" class="flex items-start gap-2">
                                    <span class="text-primary mt-1">•</span>
                                    <span class="text-xs text-muted-foreground">{{ item }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="bg-muted/10 border border-border rounded-xl p-5 text-center">
                            <InfoIcon class="w-8 h-8 text-muted-foreground mx-auto mb-3" />
                            <h3 class="text-sm font-semibold text-card-foreground mb-2">Need Help?</h3>
                            <p class="text-xs text-muted-foreground mb-3">
                                Our support team is available 24/7 to assist you with the verification process.
                            </p>
                            <TextLink :href="route('user.support.index')" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                                Contact Support
                                <ExternalLinkIcon class="w-4 h-4" />
                            </TextLink>
                        </div>
                    </div>

                    <div class="lg:col-span-3">
                        <KycEditForm :user="user" :submission="submission" />
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
