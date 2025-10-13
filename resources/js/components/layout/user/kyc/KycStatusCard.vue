<script setup lang="ts">
    import { computed, ref } from 'vue';
    import {
        CheckCircleIcon, ClockIcon, AlertCircleIcon, XCircleIcon, ArrowRightIcon, InfoIcon, CalendarIcon, FileTextIcon,
        ShieldCheckIcon, ShieldIcon, LockIcon, CheckIcon, CopyIcon, HelpCircleIcon
    } from 'lucide-vue-next';
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
        submissionId: { type: String, default: 'KYC-8B3D-9A2C' },
        submittedAt: { type: String, default: '' },
        reviewedAt: { type: String, default: '' },
        documentTypes: { type: Array as () => string[], default: () => [] },
        rejectionReason: { type: String, default: '' },
        estimatedReviewTime: { type: String, default: '24-48 hours' }
    });

    const copiedId = ref(false);

    const copySubmissionId = () => {
        if (!props.submissionId) return;
        navigator.clipboard.writeText(props.submissionId);
        copiedId.value = true;
        setTimeout(() => {
            copiedId.value = false;
        }, 2000);
    };

    const statusDetails = computed(() => {
        switch (props.status) {
            case 'verified':
                return {
                    icon: CheckCircleIcon,
                    iconContainerClass: 'bg-primary/10 text-primary',
                    borderClass: 'border-primary/20',
                    badgeClass: 'bg-primary/10 text-primary border-primary/30',
                    buttonClass: 'bg-primary hover:opacity-90 text-primary-foreground',
                    statusText: 'Verified'
                };
            case 'rejected':
                return {
                    icon: XCircleIcon,
                    iconContainerClass: 'bg-destructive/10 text-destructive',
                    borderClass: 'border-destructive/20',
                    badgeClass: 'bg-destructive/10 text-destructive border-destructive/30',
                    buttonClass: 'bg-destructive hover:opacity-90 text-destructive-foreground',
                    statusText: 'Rejected'
                };
            case 'unverified':
                return {
                    icon: AlertCircleIcon,
                    iconContainerClass: 'bg-warning/10 text-warning',
                    borderClass: 'border-warning/20',
                    badgeClass: 'bg-warning/10 text-warning border-warning/30',
                    buttonClass: 'bg-primary hover:opacity-90 text-primary-foreground',
                    statusText: 'Not Started'
                };
            case 'pending':
            default:
                return {
                    icon: ClockIcon,
                    iconContainerClass: 'bg-blue-500/10 text-blue-500',
                    borderClass: 'border-blue-500/20',
                    badgeClass: 'bg-blue-500/10 text-blue-500 border-blue-500/30',
                    buttonClass: 'bg-secondary hover:bg-muted text-secondary-foreground',
                    statusText: 'Under Review'
                };
        }
    });

    const statusSteps = computed(() => {
        // Define the base steps
        const steps = [
            {
                id: 1,
                label: 'Documents Submitted',
                description: 'Provide the required documents for review.',
                status: 'upcoming',
                isError: false
            },
            {
                id: 2,
                label: 'Under Review',
                description: 'Our team is currently reviewing your submission.',
                status: 'upcoming',
                isError: false
            },
            {
                id: 3,
                label: 'Verification Complete',
                description: 'Your account has been successfully verified.',
                status: 'upcoming',
                isError: false
            },
        ];

        if (props.status === 'verified') {
            steps.forEach(step => (step.status = 'complete'));
        } else if (props.status === 'pending') {
            steps[0].status = 'complete';
            steps[1].status = 'active';
        } else if (props.status === 'rejected') {
            steps[0].status = 'complete';
            steps[1].status = 'complete';
            steps[2].status = 'active';
            steps[2].isError = true;
            steps[2].label = 'Action Required';
            steps[2].description = 'Please review the feedback and resubmit your documents.';
        } else {
            steps[0].status = 'active';
        }

        return steps;
    });

    const kycBenefits = [
        { icon: ShieldIcon, text: 'Enhanced account security and fraud protection' },
        { icon: LockIcon, text: 'Higher deposit and withdrawal limits' },
        { icon: CheckIcon, text: 'Access to premium trading features' }
    ];

    const faqs = [
        { q: 'How long does verification take?', a: `Typically, reviews are completed within ${props.estimatedReviewTime}. You will receive an email notification upon completion.` },
        { q: 'Why was my document rejected?', a: 'Common reasons include blurry images, expired documents, or information mismatch. Please check the specific rejection reason provided.' },
        { q: 'Can I cancel my submission?', a: 'Once submitted, the verification process cannot be canceled. If you made a mistake, please wait for the review outcome to resubmit.' }
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
                        'Our compliance team is carefully reviewing your submission.',
                        'You\'ll receive an email notification once completed.',
                        'No need to contact support unless the estimated time has passed.',
                        'Ensure you can receive emails from our domain.'
                    ]
                };
            case 'rejected':
                return {
                    title: 'Steps to Resolve Rejection',
                    items: [
                        'Review the rejection reason carefully.',
                        'Ensure all documents are clear, legible, and not expired.',
                        'Verify that your name and details match your documents exactly.',
                        'Resubmit with the corrected information and documents.'
                    ]
                };
            case 'unverified':
            default:
                return {
                    title: 'How to Complete Verification',
                    items: [
                        'Prepare your Government-issued ID and Proof of Address.',
                        'Ensure good lighting for photo and selfie submissions.',
                        'Fill out all required information accurately.',
                        'Click "Begin Verification" to start the process.'
                    ]
                };
        }
    });
</script>

<template>
    <div class="w-full mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-5 gap-6 xl:gap-8">

            <div class="lg:col-span-1 xl:col-span-1 space-y-6">
                <div class="bg-card border border-border rounded-xl p-5">
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

                <div class="bg-card border border-border rounded-xl p-5">
                    <h5 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                        <HelpCircleIcon class="w-4 h-4 text-primary" />
                        Common Questions
                    </h5>

                    <div class="space-y-2">
                        <details v-for="(faq, index) in faqs" :key="index" class="text-xs group">
                            <summary class="list-none flex items-center justify-between cursor-pointer text-muted-foreground hover:text-card-foreground font-medium py-1">
                                {{ faq.q }}
                                <CheckCircleIcon class="w-4 h-4 transition-transform duration-200" />
                            </summary>
                            <p class="pt-2 pb-2 text-muted-foreground/80 border-t border-border">{{ faq.a }}</p>
                        </details>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 xl:col-span-3 order-first lg:order-none lg:mt-0 mt-8">
                <div class="bg-background rounded-xl sm:rounded-2xl border border-border overflow-hidden shadow-sm h-full">
                    <div class="bg-card px-4 sm:px-6 py-4 border-b border-border flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <ShieldCheckIcon class="w-5 h-5 text-muted-foreground" />
                            <h3 class="text-base sm:text-lg font-semibold text-card-foreground">KYC Verification Status</h3>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium border" :class="statusDetails.badgeClass">
                            {{ statusDetails.statusText }}
                        </span>
                    </div>

                    <div class="p-6 sm:p-8 text-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full mx-auto mb-6 flex items-center justify-center ring-4 ring-card" :class="statusDetails.iconContainerClass">
                            <component :is="statusDetails.icon" class="w-10 h-10 sm:w-12 sm:h-12" />
                        </div>

                        <h4 class="text-xl sm:text-2xl font-bold text-card-foreground mb-3">{{ title }}</h4>
                        <p class="text-sm sm:text-base text-muted-foreground max-w-xl mx-auto">{{ staticMessage }}</p>
                        <p v-if="dynamicMessage" class="text-xs sm:text-sm text-muted-foreground mt-3 max-w-lg mx-auto">{{ dynamicMessage }}</p>

                        <div v-if="status === 'rejected' && rejectionReason" class="mt-6 p-4 bg-destructive/5 border border-destructive/20 rounded-lg text-left">
                            <div class="flex items-start gap-3">
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
                            :class="statusDetails.buttonClass">
                            {{ action.text }}
                            <ArrowRightIcon class="w-4 h-4 ml-2" />
                        </TextLink>
                    </div>

                    <div class="px-6 sm:px-8 pb-8">
                        <div class="bg-card border border-border rounded-xl p-4 sm:p-6">
                            <h5 class="text-sm font-semibold text-card-foreground mb-6">Verification Process</h5>
                            <ol class="space-y-5 list-decimal list-inside">
                                <li
                                    v-for="step in statusSteps"
                                    :key="step.id"
                                    class="pl-2">
                                    <span
                                        class="font-semibold text-sm"
                                        :class="{
                                            'text-card-foreground': step.status === 'active' || (step.status === 'complete' && !step.isError),
                                            'line-through text-muted-foreground': step.status === 'complete',
                                            'text-muted-foreground': step.status === 'upcoming',
                                            'text-destructive': step.isError
                                        }">
                                        {{ step.label }}
                                    </span>
                                    <p class="text-xs text-muted-foreground mt-1 ml-5">{{ step.description }}</p>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 xl:col-span-1 space-y-6 margin-bottom">
                <div v-if="status !== 'unverified'" class="bg-card border border-border rounded-xl p-5">
                    <p class="text-xs text-muted-foreground mb-2 font-medium">Submission ID</p>
                    <div class="flex items-center gap-2 p-2 bg-muted rounded-md">
                        <code class="text-sm text-card-foreground font-mono flex-1 truncate">{{ submissionId }}</code>
                        <button @click="copySubmissionId" class="p-1.5 hover:bg-background rounded-md" :title="copiedId ? 'Copied!' : 'Copy ID'">
                            <CheckIcon v-if="copiedId" class="w-4 h-4 text-primary" />
                            <CopyIcon v-else class="w-4 h-4 text-muted-foreground" />
                        </button>
                    </div>
                </div>

                <div v-if="submittedAt || reviewedAt" class="bg-card border border-border rounded-xl p-5">
                    <h5 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                        <CalendarIcon class="w-4 h-4 text-primary" /> Timeline
                    </h5>
                    <div class="space-y-2 text-xs">
                        <p v-if="submittedAt"><strong class="font-medium text-muted-foreground">Submitted:</strong> {{ submittedAt }}</p>
                        <p v-if="reviewedAt"><strong class="font-medium text-muted-foreground">Reviewed:</strong> {{ reviewedAt }}</p>
                    </div>
                </div>

                <div v-if="documentTypes.length > 0" class="bg-card border border-border rounded-xl p-5">
                    <h5 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                        <FileTextIcon class="w-4 h-4 text-primary" /> Documents Submitted
                    </h5>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="(doc, index) in documentTypes" :key="index" class="px-2 py-1 bg-muted text-muted-foreground text-xs rounded-md border border-border">
                            {{ doc }}
                        </span>
                    </div>
                </div>

                <div class="bg-card border border-border rounded-xl p-5">
                    <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                        <InfoIcon class="w-4 h-4 text-primary" /> {{ verificationInstructions.title }}
                    </h5>
                    <ul class="space-y-2.5">
                        <li v-for="(item, index) in verificationInstructions.items" :key="index" class="flex items-start gap-2 text-xs text-muted-foreground">
                            <span class="text-primary mt-0.5">•</span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
