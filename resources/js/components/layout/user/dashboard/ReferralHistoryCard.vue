<script setup lang="ts">
    import TextLink from '@/components/TextLink.vue';
    import { Users } from 'lucide-vue-next';
    import { route } from 'ziggy-js';

    // Define props
    interface ReferredUserItem {
        id: number;
        first_name: string;
        last_name: string;
        created_at: string;
    }

    const props = defineProps<{
        referred_users?: ReferredUserItem[];
    }>();

    const formatDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    };
</script>

<template>
    <div class="card-crypto p-4 sm:p-6">
        <h3 v-if="props.referred_users && props.referred_users.length > 0" class="text-card-foreground font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Recent Referrals</h3>

        <div class="space-y-2 sm:space-y-3">
            <div v-if="props.referred_users && props.referred_users.length > 0" class="flex items-center justify-between text-muted-foreground text-xs uppercase font-medium pb-1 border-b border-border/50">
                <span class="flex-1 min-w-0">Referred User</span>
                <span class="text-right">Joined</span>
            </div>

            <template v-else-if="props.referred_users && props.referred_users.length > 0">
                <div v-for="(referral, index) in props.referred_users"
                     :key="referral.id"
                     class="flex items-center justify-between py-1">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                        <div class="w-6 h-6 sm:w-7 sm:h-7 bg-secondary/70 rounded-full flex items-center justify-center text-muted-foreground font-bold text-xs flex-shrink-0">
                            {{ index + 1 }}
                        </div>
                        <span class="text-card-foreground font-medium text-sm sm:text-base truncate">{{ referral.first_name }} {{ referral.last_name?.charAt(0) }}.</span>
                    </div>

                    <div class="text-right flex-shrink-0">
                        <span class="text-muted-foreground text-sm">{{ formatDate(referral.created_at) }}</span>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
                    <Users class="h-12 w-12 mb-4 text-muted-foreground" />
                    <p class="text-lg font-medium mb-2 text-card-foreground">Ready to start earning?</p>
                    <p class="text-sm">
                        It looks like you haven't referred anyone yet. Share your unique link now to see your first signup here!
                    </p>
                </div>
            </template>
        </div>

        <div v-if="props.referred_users && props.referred_users.length > 0" class="mx-[-1rem] sm:mx-[-1.5rem] mt-4 pt-4 border-t border-border/50 bg-transparent">
            <div class="text-center">
                <TextLink
                    :href="route('user.rewards.index')"
                    :class="[
                       'rounded-lg sm:rounded-xl py-1.5 sm:py-2 cursor-pointer text-sm sm:text-base font-medium',
                       'inline-flex items-center justify-center gap-2 px-6',
                       'bg-secondary/70 text-secondary-foreground hover:bg-muted/90'
                    ]">
                    Referrals List →
                </TextLink>
            </div>
        </div>
    </div>
</template>
