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
                    iconContainerClass: 'bg-lime-500/10 text-lime-400',
                    borderClass: 'border-lime-500/20',
                    badgeClass: 'bg-lime-500/10 text-lime-400 border-lime-500/30',
                    buttonClass: 'bg-lime-400 hover:bg-lime-500 text-accent-foreground',
                    emailLinkClass: 'text-lime-400 hover:underline',
                    statusText: 'Verified'
                };
            case 'rejected':
                return {
                    icon: XCircleIcon,
                    iconContainerClass: 'bg-red-500/10 text-red-400',
                    borderClass: 'border-red-500/20',
                    badgeClass: 'bg-red-500/10 text-red-400 border-red-500/30',
                    buttonClass: 'bg-red-500 hover:bg-red-600 text-white',
                    emailLinkClass: 'text-red-400 hover:underline',
                    statusText: 'Rejected'
                };
            case 'unverified':
                return {
                    icon: AlertCircleIcon,
                    iconContainerClass: 'bg-yellow-500/10 text-yellow-400',
                    borderClass: 'border-yellow-500/20',
                    badgeClass: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                    buttonClass: 'bg-yellow-500 hover:bg-yellow-600 text-zinc-900',
                    emailLinkClass: 'text-yellow-400 hover:underline',
                    statusText: 'Not Started'
                };
            case 'pending':
            default:
                return {
                    icon: ClockIcon,
                    iconContainerClass: 'bg-sky-500/10 text-sky-400',
                    borderClass: 'border-sky-500/20',
                    badgeClass: 'bg-sky-500/10 text-sky-400 border-sky-500/30',
                    buttonClass: 'bg-sky-500 hover:bg-sky-600 text-white',
                    emailLinkClass: 'text-sky-400 hover:underline',
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
            <!-- Main Status Card - 2 columns -->
            <div class="lg:col-span-2">
                <div class="bg-background rounded-xl sm:rounded-2xl border border-zinc-800 overflow-hidden shadow-xl h-full">
                    <!-- Header with Status Badge -->
                    <div class="bg-zinc-900 px-4 sm:px-6 py-4 border-b border-zinc-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <ShieldCheckIcon class="w-5 h-5 text-zinc-400" />
                            <h3 class="text-base sm:text-lg font-semibold text-white">KYC Verification Status</h3>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium border" :class="statusDetails.badgeClass">
                            {{ statusDetails.statusText }}
                        </span>
                    </div>

                    <!-- Main Content -->
                    <div class="p-6 sm:p-8 text-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full mx-auto mb-6 flex items-center justify-center ring-4 ring-zinc-700/30" :class="statusDetails.iconContainerClass">
                            <component :is="statusDetails.icon" class="w-10 h-10 sm:w-12 sm:h-12" />
                        </div>

                        <h4 class="text-xl sm:text-2xl font-bold text-white mb-3">{{ title }}</h4>
                        <p class="text-sm sm:text-base text-zinc-300 max-w-xl mx-auto">{{ staticMessage }}</p>

                        <p v-if="dynamicMessage" class="text-xs sm:text-sm text-zinc-400 mt-3 max-w-lg mx-auto">
                            {{ dynamicMessage }}
                        </p>

                        <!-- Rejection Reason -->
                        <div v-if="status === 'rejected' && rejectionReason" class="mt-6 p-4 bg-red-500/5 border border-red-500/20 rounded-lg">
                            <div class="flex items-start gap-3 text-left">
                                <InfoIcon class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-red-400 mb-1">Rejection Reason</p>
                                    <p class="text-sm text-zinc-300">{{ rejectionReason }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <TextLink
                            :href="action.href"
                            class="inline-flex items-center justify-center px-6 py-3 mt-8 rounded-lg font-semibold text-sm sm:text-base"
                            :class="statusDetails.buttonClass"
                        >
                            {{ action.text }}
                            <ArrowRightIcon class="w-4 h-4 ml-2" />
                        </TextLink>
                    </div>

                    <!-- Verification Steps -->
                    <div class="px-6 pb-6">
                        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4 sm:p-5">
                            <h5 class="text-sm font-semibold text-white mb-4">Verification Process</h5>
                            <div class="space-y-3">
                                <div
                                    v-for="(step, index) in statusSteps"
                                    :key="index"
                                    class="flex items-center gap-3"
                                >
                                    <div
                                        class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 border-2 transition-all duration-300"
                                        :class="step.completed ? [statusDetails.borderClass, statusDetails.iconContainerClass] : 'border-zinc-600 bg-zinc-700/30'"
                                    >
                                        <CheckCircleIcon v-if="step.completed" class="w-4 h-4" />
                                        <span v-else class="text-xs text-zinc-500">{{ index + 1 }}</span>
                                    </div>
                                    <span
                                        class="text-sm transition-colors duration-300"
                                        :class="step.completed ? 'text-white font-medium' : 'text-zinc-500'"
                                    >
                                        {{ step.label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Information Cards - 1 column -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Timeline & Documents Card -->
                <div class="space-y-4" v-if="submittedAt || status === 'pending'" style="margin-top: 0;">
                    <!-- Timeline Info -->
                    <div v-if="submittedAt || status === 'pending'" class="bg-zinc-800 border border-zinc-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-zinc-700/50 rounded-lg">
                                <CalendarIcon class="w-5 h-5 text-zinc-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-zinc-400 mb-2 font-medium">Timeline</p>
                                <p v-if="submittedAt" class="text-sm text-white mb-1">
                                    <span class="text-zinc-400">Submitted:</span> {{ submittedAt }}
                                </p>
                                <p v-if="reviewedAt" class="text-sm text-white mb-1">
                                    <span class="text-zinc-400">Reviewed:</span> {{ reviewedAt }}
                                </p>
                                <p v-if="status === 'pending' && estimatedReviewTime" class="text-xs text-zinc-400 mt-2 p-2 bg-sky-500/5 rounded border border-sky-500/20">
                                    ⏱️ Est. review: {{ estimatedReviewTime }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Submitted -->
                    <div v-if="documentTypes.length > 0" class="bg-zinc-800 border border-zinc-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-zinc-700/50 rounded-lg">
                                <FileTextIcon class="w-5 h-5 text-zinc-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-zinc-400 mb-3 font-medium">Documents Submitted</p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(doc, index) in documentTypes"
                                        :key="index"
                                        class="px-2 py-1 bg-zinc-700/50 text-zinc-300 text-xs rounded border border-zinc-600"
                                    >
                                        {{ doc }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KYC Benefits Card -->
                <div class="bg-gradient-to-br from-zinc-800 to-zinc-800/50 border border-zinc-700 rounded-lg p-5" style="margin-top: 0;">
                    <h5 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-lime-400" />
                        Benefits of Verification
                    </h5>
                    <ul class="space-y-3">
                        <li v-for="(benefit, index) in kycBenefits" :key="index" class="flex items-start gap-3 text-xs text-zinc-300">
                            <component :is="benefit.icon" class="w-4 h-4 text-lime-400 flex-shrink-0 mt-0.5" />
                            <span>{{ benefit.text }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Instructions Card -->
                <div class="bg-zinc-900 border border-zinc-700 rounded-lg p-5">
                    <h5 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                        <InfoIcon class="w-4 h-4" :class="statusDetails.emailLinkClass" />
                        {{ verificationInstructions.title }}
                    </h5>
                    <ul class="space-y-2.5">
                        <li v-for="(item, index) in verificationInstructions.items" :key="index" class="flex items-start gap-2 text-xs text-zinc-300">
                            <span class="text-zinc-500 mt-0.5">{{ index + 1 }}.</span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Required Documents Card (for unverified status) -->
                <div v-if="status === 'unverified'" class="bg-yellow-500/5 border border-yellow-500/20 rounded-lg p-5">
                    <h5 class="text-sm font-semibold text-yellow-400 mb-4 flex items-center gap-2">
                        <AlertTriangleIcon class="w-4 h-4" />
                        Required Documents
                    </h5>
                    <ul class="space-y-2.5">
                        <li v-for="(doc, index) in requiredDocuments" :key="index" class="flex items-start gap-2 text-xs text-zinc-300">
                            <span class="text-yellow-400 mt-0.5">•</span>
                            <span>{{ doc }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Contact Section -->
                <div class="text-center p-4 bg-zinc-800/50 border border-zinc-700 rounded-lg">
                    <p class="text-xs text-zinc-400">
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
