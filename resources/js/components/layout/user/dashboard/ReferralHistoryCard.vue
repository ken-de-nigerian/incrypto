<template>
    <div class="card-crypto p-4 sm:p-6">
        <h3 class="text-card-foreground font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Recent Referrals History</h3>

        <div class="space-y-2 sm:space-y-3">
            <div class="flex items-center justify-between text-muted-foreground text-xs uppercase font-medium pb-1 border-b border-secondary">
                <span class="flex-1 min-w-0">Referred User</span>
                <span class="w-1/3 text-center hidden sm:block">Status</span>
                <span class="w-1/4 text-right">Joined</span>
            </div>

            <div
                v-for="(referral, index) in recentReferrals"
                :key="referral.id"
                class="flex items-center justify-between py-1"
            >
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 bg-secondary rounded-full flex items-center justify-center text-muted-foreground font-bold text-xs flex-shrink-0">
                        {{ index + 1 }}
                    </div>
                    <span class="text-card-foreground font-medium text-sm sm:text-base truncate">{{ referral.name }}</span>
                </div>

                <div class="w-1/3 text-center hidden sm:block flex-shrink-0">
                    <span
                        class="px-2 py-0.5 rounded-full text-xs font-semibold"
                        :class="getStatusClass(referral.status)"
                    >
                        {{ referral.status }}
                    </span>
                </div>

                <div class="w-1/4 text-right flex-shrink-0">
                    <span class="text-muted-foreground text-sm">{{ formatDate(referral.joined_at) }}</span>
                </div>
            </div>
        </div>

        <div class="mx-[-1rem] sm:mx-[-1.5rem] mt-4 pt-4 border-t border-secondary bg-transparent">
            <div class="text-center">
                <a href="#" class="text-primary text-sm font-medium hover:underline px-4 sm:px-6 py-2 block">
                    View Full Referral List →
                </a>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { ref } from 'vue';

    // Using ref() for mock data for demonstration purposes
    const recentReferrals = ref([
        { id: 1, name: 'Alex M.', status: 'Active', joined_at: '2025-10-08' },
        { id: 2, name: 'Jenna L.', status: 'Pending Deposit', joined_at: '2025-10-06' },
        { id: 3, name: 'Mike T.', status: 'Active', joined_at: '2025-10-01' },
        { id: 4, name: 'Sarah G.', status: 'Inactive', joined_at: '2025-09-29' },
        { id: 5, name: 'David P.', status: 'Active', joined_at: '2025-09-25' },
    ]);

    // Helper function to format the date
    const formatDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
        });
    };

    // Helper function to determine badge style
    const getStatusClass = (status: string): string => {
        if (status === 'Active') return 'bg-green-100 text-green-700';
        if (status === 'Pending Deposit') return 'bg-yellow-100 text-yellow-700';
        return 'bg-gray-100 text-gray-700'; // Inactive
    };
</script>
