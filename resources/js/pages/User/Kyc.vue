<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import KycStatusCard from '@/components/layout/user/kyc/KycStatusCard.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';

    // Define the prop coming from your Laravel controller with defaults
   defineProps({
        kycData: {
            type: Object,
        }
   });

    const page = usePage();
    const user = computed(() => page.props.auth.user);

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

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Kyc Verification' }
    ];
</script>

<template>
    <Head title="Kyc Verification" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="flex-1 flex lg:items-center justify-center lg:mt-8">
                <KycStatusCard
                    :status="kycData?.status || 'unverified'"
                    :title="kycData?.title || 'KYC Verification Required'"
                    :static-message="kycData?.staticMessage || 'Complete your KYC verification to access all features.'"
                    :dynamic-message="kycData?.dynamicMessage || ''"
                    :action="kycData?.action || { href: '/kyc/verify', text: 'Start Verification' }"
                    :contact-email="kycData?.contactEmail || 'support@example.com'"
                    :submitted-at="kycData?.submittedAt || ''"
                    :reviewed-at="kycData?.reviewedAt || ''"
                    :document-types="kycData?.documentTypes || []"
                    :rejection-reason="kycData?.rejectionReason || ''"
                    :estimated-review-time="kycData?.estimatedReviewTime || '24-48 hours'"
                />
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
