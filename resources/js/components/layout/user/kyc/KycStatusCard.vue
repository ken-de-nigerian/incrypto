<script setup lang="ts">
    import { computed } from 'vue';
    import { CheckCircleIcon, ClockIcon, AlertCircleIcon, XCircleIcon, ArrowRightIcon, InfoIcon, CalendarIcon, FileTextIcon, ShieldCheckIcon, ShieldIcon, LockIcon, AlertTriangleIcon, CheckIcon } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    // Define the component's props
    const props = defineProps({
        status: {
            type: String,
            required: true,
            validator: (value: string) => ['verified', 'pending', 'rejected', 'unverified'].includes(value)
        },
        title: { type: String, required: true },
        staticMessage: { type: String, required: true },
        dynamicMessage: { type: String, default: '' },
        action: {
            type: Object as () => { href: string; text: string },
            required: true
        },
        contactEmail: { type: String, required: true },
        submittedAt: { type: String, default: '' },
        reviewedAt: { type: String, default: '' },
        documentTypes: { type: Array as () => string[], default: () => [] },
        rejectionReason: { type: String, default: '' },
        estimatedReviewTime: { type: String, default: '24-48 hours' }
    });

    const statusDetails = computed(() => {
        switch (props.status) {
            case 'verified':
                return {
                    icon: CheckCircleIcon,
                    iconContainerClass: 'bg-primary/10 text-primary',
                    borderClass: 'border-primary/20',
                    badgeClass: 'bg-primary/10 text-primary border-primary/30',
                    buttonClass: 'bg-primary hover:opacity-90 text-primary-foreground',
                    emailLinkClass: 'text-primary hover:underline',
                    statusText: 'Verified'
                };
            case 'rejected':
                return {
                    icon: XCircleIcon,
                    iconContainerClass: 'bg-destructive/10 text-destructive',
                    borderClass: 'border-destructive/20',
                    badgeClass: 'bg-destructive/10 text-destructive border-destructive/30',
                    buttonClass: 'bg-destructive hover:opacity-90 text-destructive-foreground',
                    emailLinkClass: 'text-destructive hover:underline',
                    statusText: 'Rejected'
                };
            case 'unverified':
                return {
                    icon: AlertCircleIcon,
                    iconContainerClass: 'bg-warning/10 text-warning',
                    borderClass: 'border-warning/20',
                    badgeClass: 'bg-warning/10 text-warning border-warning/30',
                    buttonClass: 'bg-warning hover:opacity-90 text-warning-foreground',
                    emailLinkClass: 'text-warning hover:underline',
                    statusText: 'Not Started'
                };
            case 'pending':
            default:
                return {
                    icon: ClockIcon,
                    iconContainerClass: 'bg-muted/50 text-muted-foreground',
                    borderClass: 'border-border',
                    badgeClass: 'bg-muted/50 text-muted-foreground border-border',
                    buttonClass: 'bg-secondary hover:bg-muted text-secondary-foreground',
                    emailLinkClass: 'text-muted-foreground hover:underline',
                    statusText: 'Under Review'
                };
        }
    });

    const statusSteps = computed(() => [
        { label: 'Submit Documents', completed: props.status !== 'unverified' },
        { label: 'Under Review', completed: props.status === 'verified' || props.status === 'pending' || props.status === 'rejected' },
        { label: props.status === 'rejected' ? 'Rejected' : 'Verified', completed: props.status === 'verified' }
    ]);

    const kycBenefits = [
        { icon: ShieldIcon, text: 'Enhanced account security and fraud protection' },
        { icon: LockIcon, text: 'Higher deposit and withdrawal limits' },
        { icon: CheckIcon, text: 'Access to premium trading features' },
        { icon: ShieldCheckIcon, text: 'Compliance with global regulations' }
    ];

    const requiredDocuments = [
        'Government-issued ID (Passport, Driver\'s License, or National ID)',
        'Proof of address (Utility bill, Bank statement - not older than 3 months)',
        'Selfie with ID document for identity verification',
        'Additional documents may be required based on account level'
    ];

    const verificationInstructions = computed(() => {
        switch (props.status) {
            case 'verified':
                return {
                    title: 'What You Can Do Now',
                    items: [
                        'Make unlimited deposits and withdrawals',
                        'Access all trading pairs and features',
                        'Participate in staking and yield programs',
                        'Enjoy priority customer support'
                    ]
                };
            case 'pending':
                return {
                    title: 'While We Review Your Documents',
                    items: [
                        'Our compliance team is reviewing your submission',
                        'You\'ll receive an email notification once completed',
                        'Typical review time: ' + props.estimatedReviewTime,
                        'Check your email for any additional requirements'
                    ]
                };
            case 'rejected':
                return {
                    title: 'Steps to Resolve Rejection',
                    items: [
                        'Review the rejection reason carefully',
                        'Ensure all documents are clear and legible',
                        'Verify that documents are not expired',
                        'Resubmit with corrected information'
                    ]
                };
            case 'unverified':
            default:
                return {
                    title: 'How to Complete Verification',
                    items: [
                        'Prepare your identification documents',
                        'Ensure good lighting for photo submissions',
                        'Fill out all required information accurately',
                        'Submit and wait for approval (typically 24-48 hours)'
                    ]
                };
        }
    });
</script>

<template>
    <div class="w-full mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-background rounded-xl sm:rounded-2xl border border-border overflow-hidden shadow-sm h-full">
                    <div class="bg-card px-4 sm:px-6 py-4 border-b border-border flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <ShieldCheckIcon class="w-5 h-5 text-muted-foreground" />
                            <h3 class="text-base sm:text-lg font-semibold text-card-foreground">KYC Verification Status</h3>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium border" :class="statusDetails.badgeClass">
                            {{ statusDetails.statusText }}
                        </span>
                    </div>

                    <div class="p-6 sm:p-8 text-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full mx-auto mb-6 flex items-center justify-center ring-4 ring-border" :class="statusDetails.iconContainerClass">
                            <component :is="statusDetails.icon" class="w-10 h-10 sm:w-12 sm:h-12" />
                        </div>

                        <h4 class="text-xl sm:text-2xl font-bold text-card-foreground mb-3">{{ title }}</h4>
                        <p class="text-sm sm:text-base text-muted-foreground max-w-xl mx-auto">{{ staticMessage }}</p>

                        <p v-if="dynamicMessage" class="text-xs sm:text-sm text-muted-foreground mt-3 max-w-lg mx-auto">
                            {{ dynamicMessage }}
                        </p>

                        <div v-if="status === 'rejected' && rejectionReason" class="mt-6 p-4 bg-destructive/5 border border-destructive/20 rounded-lg">
                            <div class="flex items-start gap-3 text-left">
                                <InfoIcon class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-destructive mb-1">Rejection Reason</p>
                                    <p class="text-sm text-muted-foreground">{{ rejectionReason }}</p>
                                </div>
                            </div>
                        </div>

                        <TextLink
                            :href="action.href"
                            class="inline-flex items-center justify-center px-6 py-3 mt-8 rounded-lg font-semibold text-sm sm:text-base transition-opacity"
                            :class="statusDetails.buttonClass"
                        >
                            {{ action.text }}
                            <ArrowRightIcon class="w-4 h-4 ml-2" />
                        </TextLink>
                    </div>

                    <div class="px-6 pb-6">
                        <div class="bg-card border border-border rounded-lg p-4 sm:p-5">
                            <h5 class="text-sm font-semibold text-card-foreground mb-4">Verification Process</h5>
                            <div class="space-y-3">
                                <div
                                    v-for="(step, index) in statusSteps"
                                    :key="index"
                                    class="flex items-center gap-3"
                                >
                                    <div
                                        class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 border-2 transition-all duration-300"
                                        :class="step.completed ? [statusDetails.borderClass, statusDetails.iconContainerClass] : 'border-border bg-secondary'"
                                    >
                                        <CheckCircleIcon v-if="step.completed" class="w-4 h-4" />
                                        <span v-else class="text-xs text-muted-foreground">{{ index + 1 }}</span>
                                    </div>
                                    <span
                                        class="text-sm transition-colors duration-300"
                                        :class="step.completed ? 'text-card-foreground font-medium' : 'text-muted-foreground'"
                                    >
                                        {{ step.label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="space-y-4" v-if="submittedAt || status === 'pending'" style="margin-top: 0;">
                    <div v-if="submittedAt || status === 'pending'" class="bg-secondary border border-border rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-muted rounded-lg">
                                <CalendarIcon class="w-5 h-5 text-muted-foreground" />
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-muted-foreground mb-2 font-medium">Timeline</p>
                                <p v-if="submittedAt" class="text-sm text-card-foreground mb-1">
                                    <span class="text-muted-foreground">Submitted:</span> {{ submittedAt }}
                                </p>
                                <p v-if="reviewedAt" class="text-sm text-card-foreground mb-1">
                                    <span class="text-muted-foreground">Reviewed:</span> {{ reviewedAt }}
                                </p>
                                <p v-if="status === 'pending' && estimatedReviewTime" class="text-xs text-muted-foreground mt-2 p-2 bg-muted/50 rounded border-border">
                                    ⏱️ Est. review: {{ estimatedReviewTime }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="documentTypes.length > 0" class="bg-secondary border border-border rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-muted rounded-lg">
                                <FileTextIcon class="w-5 h-5 text-muted-foreground" />
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-muted-foreground mb-3 font-medium">Documents Submitted</p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(doc, index) in documentTypes"
                                        :key="index"
                                        class="px-2 py-1 bg-muted text-muted-foreground text-xs rounded border border-border"
                                    >
                                        {{ doc }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-secondary border border-border rounded-lg p-5" style="margin-top: 0;">
                    <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-primary" />
                        Benefits of Verification
                    </h5>
                    <ul class="space-y-3">
                        <li v-for="(benefit, index) in kycBenefits" :key="index" class="flex items-start gap-3 text-xs text-muted-foreground">
                            <component :is="benefit.icon" class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                            <span>{{ benefit.text }}</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-card border border-border rounded-lg p-5">
                    <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                        <InfoIcon class="w-4 h-4" :class="statusDetails.emailLinkClass" />
                        {{ verificationInstructions.title }}
                    </h5>
                    <ul class="space-y-2.5">
                        <li v-for="(item, index) in verificationInstructions.items" :key="index" class="flex items-start gap-2 text-xs text-muted-foreground">
                            <span class="text-muted-foreground mt-0.5">{{ index + 1 }}.</span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <div v-if="status === 'unverified'" class="bg-warning/5 border border-warning/20 rounded-lg p-5">
                    <h5 class="text-sm font-semibold text-warning mb-4 flex items-center gap-2">
                        <AlertTriangleIcon class="w-4 h-4" />
                        Required Documents
                    </h5>
                    <ul class="space-y-2.5">
                        <li v-for="(doc, index) in requiredDocuments" :key="index" class="flex items-start gap-2 text-xs text-muted-foreground">
                            <span class="text-warning mt-0.5">•</span>
                            <span>{{ doc }}</span>
                        </li>
                    </ul>
                </div>

                <div class="text-center p-4 bg-secondary border border-border rounded-lg">
                    <p class="text-xs text-muted-foreground">
                        Need help?
                        <a :href="`mailto:${contactEmail}`" :class="statusDetails.emailLinkClass" class="font-medium block mt-1">
                            {{ contactEmail }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
