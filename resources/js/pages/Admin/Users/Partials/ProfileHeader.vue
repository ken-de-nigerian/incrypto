<script setup lang="ts">
    import TextLink from '@/components/TextLink.vue';
    import { MapPin, Star, Circle, Lock, ArrowLeftRight } from 'lucide-vue-next';
    import { route } from 'ziggy-js';

    defineProps<{
        user: {
            id: string | number;
            first_name: string;
            last_name: string;
            email: string;
            phone_number: string | null;
            status: 'active' | 'suspended';
            created_at: string;
            profile: {
                profile_photo_path: string | null;
                referral_code: string;
                country: string;
            };
            kyc: {
                status: 'pending' | 'verified' | 'rejected' | 'unverified';
            } | null;
        };
    }>();

    const getStatusBadgeClass = (status: string) => {
        switch (status) {
            case 'active': return 'bg-success/20 text-success';
            case 'suspended': return 'bg-destructive/20 text-destructive';
            case 'frozen': return 'bg-accent/20 text-accent';
            default: return 'bg-secondary/20 text-secondary-foreground';
        }
    };

    const getKycBadgeClass = (status: string) => {
        switch (status) {
            case 'verified': return 'bg-success/20 text-success';
            case 'pending': return 'bg-warning/20 text-warning';
            default: return 'bg-destructive/20 text-destructive';
        }
    };

    const capitalizeFirstLetter = (str: string | null): string => {
        if (!str) return 'N/A';
        str = str.toLowerCase();
        return str.charAt(0).toUpperCase() + str.slice(1);
    };
</script>

<template>
    <div class="p-4 card-crypto rounded-b-xl mt-8 relative z-10">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
            <div class="w-20 h-20 sm:w-28 sm:h-28 flex-shrink-0 md:mt-0 rounded-full bg-secondary/70 overflow-hidden border border-border">
                <img v-if="user.profile?.profile_photo_path" :src="user.profile?.profile_photo_path" :alt="user.first_name" class="h-full w-full object-cover">
                <span v-else class="text-3xl lg:text-4xl font-bold text-foreground h-full w-full flex items-center justify-center">
                    {{ (user.first_name || '').charAt(0) }}{{ (user.last_name || '').charAt(0) }}
                </span>
            </div>

            <div class="flex-grow pt-2 min-w-0">
                <h3 class="text-xl sm:text-2xl font-bold text-foreground mb-1 truncate">{{ user.first_name }} {{ user.last_name }}</h3>
                <p class="text-muted-foreground mb-2 text-sm truncate">{{ user.email }}</p>

                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="inline-flex items-center space-x-1 text-xs font-semibold rounded-full py-1 px-1 bg-accent/20 text-accent">
                        <Star class="w-3 h-3" />
                        <span>{{ user.profile.referral_code }}</span>
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-1 text-xs">
                    <div class="text-muted-foreground inline-flex items-center">
                        <MapPin class="w-3 h-3 mr-1 align-middle" />
                        <span>{{ user.profile?.country ?? 'N/A' }}</span>
                    </div>

                    <span :class="['inline-flex items-center space-x-1 font-semibold rounded-full px-2 py-0.5', getKycBadgeClass(user.kyc?.status || 'unverified')]">
                        <Circle class="w-2 h-2 fill-current" />
                        <span>KYC: {{ capitalizeFirstLetter(user.kyc?.status ?? 'unverified') }}</span>
                    </span>

                    <span :class="['inline-flex items-center space-x-1 font-semibold rounded-full px-2 py-0.5', getStatusBadgeClass(user.status)]">
                        <Circle class="w-2 h-2 fill-current" />
                        <span>Status: {{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}</span>
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-2 w-full md:w-auto md:flex-row pt-4 md:pt-0 border-t md:border-t-0 border-border flex-shrink-0">
                <TextLink :href="route('admin.users.index')"
                          class="border-2 border-border rounded-full text-xs sm:text-sm px-3 py-1.5 flex items-center justify-center hover:bg-secondary/20 transition-colors">
                    <ArrowLeftRight class="w-4 h-4 mr-1" />
                    <span class="hidden sm:inline">Back to Users</span>
                    <span class="sm:hidden">Back</span>
                </TextLink>

                <TextLink :href="route('admin.users.edit', user.id)"
                          class="btn-crypto bg-accent rounded-full hover:bg-accent/90 text-accent-foreground text-xs sm:text-sm px-3 py-1.5 flex items-center justify-center">
                    <Lock class="w-4 h-4 mr-1" />
                    <span class="hidden sm:inline">Edit Profile</span>
                    <span class="sm:hidden">Edit</span>
                </TextLink>
            </div>
        </div>
    </div>
</template>
