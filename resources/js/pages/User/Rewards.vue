<script setup lang="ts">
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import ReferralsCard from '@/components/layout/user/referrals/ReferralsCard.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import { computed, ref } from 'vue';

    const props = defineProps({
        referralData: {
            type: Object as () => { referral_code: string, referral_link?: string },
            required: true
        },
        referrals: {
            type: Array as () => Array<any>,
            default: () => []
        },
        statistics: {
            type: Object as () => {
                total_referrals: number;
                active_referrals: number;
                conversion_rate: number;
                this_month_referrals: number;
            },
            required: true
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
        { label: 'Referrals & Rewards' }
    ];

    // Transform referral data
    const referralCode = computed(() => {
        return props.referralData?.referral_code || user.value?.profile?.referral_code || '';
    });

    const referralLink = computed(() => {
        return props.referralData?.referral_link ||
            `${window.location.origin}/register?ref=${referralCode.value}`;
    });

    const referredUsers = computed(() => {
        if (!props.referrals || !Array.isArray(props.referrals)) return [];

        return props.referrals.map(referral => ({
            id: referral.id,
            name: `${referral.first_name || ''} ${referral.last_name || ''}`.trim() || 'Anonymous User',
            email: referral.email || 'N/A',
            status: referral.status || 'inactive',
            joined_date: referral.created_at,
            avatar: referral.avatar,
            total_commission_earned: referral.total_commission_earned || 0.00
        }));
    });

    const stats = computed(() => ({
        total_referrals: props.statistics?.total_referrals || referredUsers.value.length || 0,
        active_referrals: props.statistics?.active_referrals || referredUsers.value.filter(r => r.status === 'active').length || 0,
        conversion_rate: props.statistics?.conversion_rate || 0,
        this_month_referrals: props.statistics?.this_month_referrals || 0,
    }));
</script>

<template>
    <Head title="Referrals & Rewards" />

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
                <ReferralsCard
                    :referral-link="referralLink"
                    :referral-code="referralCode"
                    :referred-users="referredUsers"
                    :statistics="stats"
                />
            </div>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>
