<script setup lang="ts">
    import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
    import {
        UsersIcon, CheckCircleIcon, CopyIcon, CheckIcon, Loader2Icon,
        SearchIcon, ArrowUpDownIcon, SortAscIcon, SortDescIcon, FilterIcon,
        XIcon, GiftIcon, TrendingUpIcon, DollarSignIcon, CalendarIcon,
        ShareIcon, MailIcon, MessageCircleIcon, ExternalLinkIcon,
        InfoIcon, SparklesIcon, ZapIcon, TrophyIcon, LinkIcon, Twitter
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    const props = defineProps({
        referralLink: {
            type: String,
            required: true
        },
        referralCode: {
            type: String,
            required: true
        },
        referredUsers: {
            type: Array as () => Array<{
                id: number;
                name: string;
                email: string;
                status: string;
                joined_date: string;
                avatar?: string;
            }>,
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

    const copiedText = ref<string | null>(null);
    const searchQuery = ref('');
    const sortOrder = ref<'asc' | 'desc' | 'default'>('default');
    const filterByStatus = ref<string>('all');
    const showFilters = ref(false);
    const displayCount = ref(8);
    const isLoadingMore = ref(false);
    const scrollContainer = ref<HTMLElement | null>(null);
    const itemsPerLoad = 8;
    const loadBuffer = 300;

    const copyToClipboard = (text: string, type: string = 'link') => {
        navigator.clipboard.writeText(text);
        copiedText.value = type;
        setTimeout(() => {
            copiedText.value = null;
        }, 2000);
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getStatusColor = (status: string) => {
        const colors = {
            active: 'text-primary bg-primary/10 border-primary/30',
            inactive: 'text-muted-foreground bg-muted border-border',
            pending: 'text-warning bg-warning/10 border-warning/30',
            suspended: 'text-destructive bg-destructive/10 border-destructive/30'
        };
        return colors[status as keyof typeof colors] || colors.inactive;
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return 'Recently';

        const date = new Date(dateString);
        const now = new Date();
        const diffInDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

        if (diffInDays === 0) return 'Today';
        if (diffInDays === 1) return 'Yesterday';
        if (diffInDays < 7) return `${diffInDays} days ago`;
        if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`;
        if (diffInDays < 365) return `${Math.floor(diffInDays / 30)} months ago`;

        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const filteredReferrals = computed(() => {
        let filtered = [...props.referredUsers];

        if (searchQuery.value.trim()) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(user =>
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query)
            );
        }

        if (filterByStatus.value !== 'all') {
            filtered = filtered.filter(user => user.status === filterByStatus.value);
        }

        if (sortOrder.value === 'asc') {
            filtered.sort((a, b) => a.name.localeCompare(b.name));
        } else if (sortOrder.value === 'desc') {
            filtered.sort((a, b) => b.name.localeCompare(a.name));
        } else {
            filtered.sort((a, b) => new Date(b.joined_date).getTime() - new Date(a.joined_date).getTime());
        }

        return filtered;
    });

    const displayedReferrals = computed(() => {
        return filteredReferrals.value.slice(0, displayCount.value);
    });

    const hasMoreReferrals = computed(() => {
        return displayCount.value < filteredReferrals.value.length;
    });

    const totalReferralsCount = computed(() => filteredReferrals.value.length);

    const hasActiveFilters = computed(() => {
        return searchQuery.value.trim() !== '' || sortOrder.value !== 'default' || filterByStatus.value !== 'all';
    });

    const toggleSortOrder = () => {
        if (sortOrder.value === 'default') {
            sortOrder.value = 'asc';
        } else if (sortOrder.value === 'asc') {
            sortOrder.value = 'desc';
        } else {
            sortOrder.value = 'default';
        }
    };

    const clearSearch = () => {
        searchQuery.value = '';
    };

    const clearFilters = () => {
        searchQuery.value = '';
        sortOrder.value = 'default';
        filterByStatus.value = 'all';
    };

    const loadMore = () => {
        if (isLoadingMore.value || !hasMoreReferrals.value) return;

        isLoadingMore.value = true;

        setTimeout(() => {
            displayCount.value = Math.min(
                displayCount.value + itemsPerLoad,
                totalReferralsCount.value
            );
            isLoadingMore.value = false;
        }, 500);
    };

    const handleScroll = (event: Event) => {
        const target = event.target as HTMLElement;
        const scrollTop = target.scrollTop;
        const scrollHeight = target.scrollHeight;
        const clientHeight = target.clientHeight;

        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);

        if (distanceFromBottom < loadBuffer && hasMoreReferrals.value && !isLoadingMore.value) {
            loadMore();
        }
    };

    // Kept as component constants since they are used in the template and are presentational/static
    const referralBenefits = [
        { icon: DollarSignIcon, title: 'Earn Commission', description: 'Get 10% commission on every trade your referrals make' },
        { icon: TrendingUpIcon, title: 'Passive Income', description: 'Build a steady stream of passive income from your network' },
        { icon: GiftIcon, title: 'Bonus Rewards', description: 'Unlock special bonuses when you reach referral milestones' },
        { icon: TrophyIcon, title: 'Leaderboard Prizes', description: 'Compete for top spots and win exclusive prizes monthly' }
    ];

    // Kept as component constants since they are used in the template and are presentational/static
    const shareOptions = [
        { icon: MailIcon, label: 'Email', action: 'email' },
        { icon: MessageCircleIcon, label: 'WhatsApp', action: 'whatsapp' },
        { icon: Twitter, label: 'Twitter', action: 'twitter' },
        { icon: LinkIcon, label: 'Copy Link', action: 'copy' }
    ];

    const handleShare = (action: string) => {
        const message = `Join me on this amazing platform! Use my referral code: ${props.referralCode}`;

        switch(action) {
            case 'email':
                window.location.href = `mailto:?subject=Join me on this platform&body=${message} ${props.referralLink}`;
                break;
            case 'whatsapp':
                window.open(`https://wa.me/?text=${encodeURIComponent(message + ' ' + props.referralLink)}`, '_blank');
                break;
            case 'twitter':
                window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}&url=${encodeURIComponent(props.referralLink)}`, '_blank');
                break;
            case 'copy':
                copyToClipboard(props.referralLink, 'link');
                break;
        }
    };

    watch([searchQuery, sortOrder, filterByStatus], () => {
        displayCount.value = Math.min(itemsPerLoad, totalReferralsCount.value);
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = 0;
        }
    });

    watch(() => props.referredUsers.length, (newLength) => {
        if (newLength < displayCount.value) {
            displayCount.value = Math.min(itemsPerLoad, newLength);
        }
    });

    onMounted(() => {
        displayCount.value = Math.min(itemsPerLoad, totalReferralsCount.value);
    });

    onUnmounted(() => {
        if (copiedText.value !== null) {
            copiedText.value = null;
        }
        scrollContainer.value = null;
        searchQuery.value = '';
        sortOrder.value = 'default';
        filterByStatus.value = 'all';
        displayCount.value = itemsPerLoad;
        isLoadingMore.value = false;
    });
</script>

<template>
    <div class="w-full mx-auto">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-gradient-to-br from-primary/10 via-primary/5 to-transparent rounded-2xl border border-primary/20 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                <GiftIcon class="w-7 h-7 text-primary" />
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl sm:text-3xl font-bold text-card-foreground mb-2">Referrals & Rewards</h2>
                                <p class="text-muted-foreground text-sm sm:text-base">
                                    Share your unique referral link and earn rewards when your friends join and trade. The more you share, the more you earn!
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Total Referrals</p>
                                <p class="text-2xl font-bold text-primary">{{ statistics.total_referrals }}</p>
                            </div>
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Active</p>
                                <p class="text-2xl font-bold text-card-foreground">{{ statistics.active_referrals }}</p>
                            </div>
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">This Month</p>
                                <p class="text-2xl font-bold text-primary">{{ statistics.this_month_referrals }}</p>
                            </div>
                            <div class="bg-card/50 backdrop-blur-sm rounded-xl p-4 border border-border">
                                <p class="text-xs text-muted-foreground mb-1">Conversions</p>
                                <p class="text-sm font-semibold text-primary flex items-center gap-1">
                                    {{ statistics.conversion_rate }}%
                                    <TrendingUpIcon class="w-4 h-4" />
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border border-border overflow-hidden">
                    <div class="bg-muted/30 px-6 py-4 border-b border-border flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <ShareIcon class="w-5 h-5 text-primary" />
                            <h3 class="text-lg font-semibold text-card-foreground">Your Referral Link</h3>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary border border-primary/30">
                            Share & Earn
                        </span>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-2">Referral Link</label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 px-4 py-3 bg-muted/50 border border-border rounded-xl font-mono text-sm text-card-foreground truncate">
                                    {{ referralLink }}
                                </div>
                                <button @click="copyToClipboard(referralLink, 'link')" class="px-4 py-3 bg-primary hover:bg-primary/90 text-primary-foreground rounded-xl font-semibold transition-all flex items-center gap-2 cursor-pointer">
                                    <CheckIcon v-if="copiedText === 'link'" class="w-4 h-4" />
                                    <CopyIcon v-else class="w-4 h-4" />
                                    {{ copiedText === 'link' ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-3">Quick Share</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <button v-for="option in shareOptions" :key="option.action" @click="handleShare(option.action)" class="px-4 py-3 bg-muted/50 hover:bg-muted border border-border rounded-xl font-medium text-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                                    <component :is="option.icon" class="w-4 h-4" />
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border border-border overflow-hidden margin-bottom">
                    <div class="bg-muted/30 px-6 py-4 border-b border-border">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <UsersIcon class="w-5 h-5 text-muted-foreground" />
                                <h3 class="text-lg font-semibold text-card-foreground">Your Referrals</h3>
                            </div>

                            <div class="flex items-center gap-2">
                                <button @click="showFilters = !showFilters" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted hover:bg-muted/80 text-muted-foreground border border-border flex items-center gap-2 cursor-pointer" :class="{ 'bg-primary/10 text-primary border-primary/30': hasActiveFilters }">
                                    <FilterIcon class="w-3.5 h-3.5" />
                                    Filters
                                    <span v-if="hasActiveFilters" class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                </button>

                                <span class="px-3 py-1.5 rounded-lg text-xs font-medium bg-muted text-muted-foreground border border-border">
                                    {{ displayedReferrals.length }} of {{ totalReferralsCount }}
                                </span>
                            </div>
                        </div>

                        <div v-if="showFilters" class="mt-4 space-y-3 pt-4 border-t border-border">
                            <div class="relative">
                                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <input v-model="searchQuery" type="text" placeholder="Search by name or email..." class="w-full pl-10 pr-10 py-2.5 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all cursor-pointer" />
                                <button v-if="searchQuery" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 hover:bg-muted rounded">
                                    <XIcon class="w-4 h-4 text-muted-foreground" />
                                </button>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-muted-foreground mb-1.5">Sort By Name</label>
                                    <button @click="toggleSortOrder" class="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm hover:bg-muted flex items-center justify-between cursor-pointer">
                                        <span>
                                            {{ sortOrder === 'asc' ? 'A → Z' : sortOrder === 'desc' ? 'Z → A' : 'Recent First' }}
                                        </span>
                                        <SortAscIcon v-if="sortOrder === 'asc'" class="w-4 h-4 text-primary" />
                                        <SortDescIcon v-else-if="sortOrder === 'desc'" class="w-4 h-4 text-primary" />
                                        <ArrowUpDownIcon v-else class="w-4 h-4 text-muted-foreground" />
                                    </button>
                                </div>
                            </div>

                            <button v-if="hasActiveFilters" @click="clearFilters" class="w-full px-4 py-2 border border-border bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium flex items-center justify-center gap-2 cursor-pointer">
                                <XIcon class="w-4 h-4" />
                                Clear All Filters
                            </button>
                        </div>
                    </div>

                    <div ref="scrollContainer" @scroll="handleScroll" class="p-6 max-h-[800px] overflow-y-auto custom-scrollbar">
                        <div v-if="props.referredUsers.length === 0 && !hasActiveFilters" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-muted mx-auto mb-4 flex items-center justify-center">
                                <UsersIcon class="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">No Referrals Yet</h4>
                            <p class="text-sm text-muted-foreground mb-6">Start sharing your referral link to earn rewards!</p>
                            <button @click="copyToClipboard(referralLink, 'link')" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-semibold inline-flex items-center gap-2 cursor-pointer">
                                <ShareIcon class="w-4 h-4" />
                                Share Referral Link
                            </button>
                        </div>

                        <div v-else-if="displayedReferrals.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-muted mx-auto mb-4 flex items-center justify-center">
                                <SearchIcon class="w-8 h-8 text-muted-foreground" />
                            </div>
                            <h4 class="text-lg font-semibold text-card-foreground mb-2">No Referrals Found</h4>
                            <p class="text-sm text-muted-foreground mb-4">Try adjusting your search or filter criteria.</p>
                            <button @click="clearFilters" class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium cursor-pointer">
                                Clear Filters
                            </button>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="user in displayedReferrals" :key="user.id" class="group bg-gradient-to-br from-card to-muted/20 border border-border rounded-xl p-5 transition-all duration-200 hover:border-primary/30">
                                <div class="flex flex-col lg:flex-row items-start justify-between gap-4">
                                    <div class="flex items-start gap-4 flex-1">
                                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border">
                                            <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="w-12 h-12 rounded-xl object-cover" />
                                            <span v-else class="text-sm font-bold text-primary">{{ getInitials(user.name) }}</span>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <h4 class="text-base font-semibold text-card-foreground">{{ user.name }}</h4>
                                                <span class="px-2 py-0.5 text-xs rounded-full border capitalize" :class="getStatusColor(user.status)">
                                                    {{ user.status }}
                                                </span>
                                            </div>

                                            <p class="text-sm text-muted-foreground mb-3">{{ user.email }}</p>

                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                                <div class="bg-muted/50 rounded-lg p-2">
                                                    <p class="text-xs text-muted-foreground mb-0.5 flex items-center gap-1">
                                                        <CalendarIcon class="w-3 h-3" />
                                                        Joined
                                                    </p>
                                                    <p class="text-xs font-semibold text-card-foreground">{{ formatDate(user.joined_date) }}</p>
                                                </div>

                                                <div class="bg-muted/50 rounded-lg p-2">
                                                    <p class="text-xs text-muted-foreground mb-0.5">Commission Earned</p>
                                                    <p class="text-sm font-bold text-primary">$0.00</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="isLoadingMore" class="flex items-center justify-center py-8">
                            <Loader2Icon class="w-8 h-8 text-primary animate-spin" />
                        </div>
                        <div v-else-if="!hasMoreReferrals && displayedReferrals.length > itemsPerLoad" class="text-center py-8">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-muted/50 rounded-full text-sm text-muted-foreground">
                                <CheckCircleIcon class="w-4 h-4" />
                                You've reached the end of the list
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block">
                <div class="xl:col-span-1 space-y-6 sticky top-6">
                    <div class="bg-gradient-to-br from-primary/5 to-transparent border border-primary/20 rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                            <SparklesIcon class="w-5 h-5 text-primary" />
                            Referral Benefits
                        </h5>
                        <ul class="space-y-4">
                            <li v-for="(benefit, index) in referralBenefits" :key="index" class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <component :is="benefit.icon" class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <h6 class="text-sm font-semibold text-card-foreground mb-1">{{ benefit.title }}</h6>
                                    <p class="text-xs text-muted-foreground">{{ benefit.description }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-warning/5 border border-warning/20 rounded-2xl p-6">
                        <h5 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                            <ZapIcon class="w-5 h-5" />
                            Pro Tips
                        </h5>
                        <ul class="space-y-2 text-xs text-muted-foreground">
                            <li class="flex items-start gap-2">
                                <span class="text-warning mt-0.5">•</span>
                                <span>Share your link on social media for maximum reach</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-warning mt-0.5">•</span>
                                <span>Engage with your referrals to boost their activity</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-warning mt-0.5">•</span>
                                <span>Track your stats regularly to optimize performance</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-warning mt-0.5">•</span>
                                <span>Reach higher tiers for better commission rates</span>
                            </li>
                        </ul>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-card to-muted/20 border border-border rounded-2xl">
                        <div class="w-12 h-12 rounded-full bg-primary/10 mx-auto mb-3 flex items-center justify-center">
                            <InfoIcon class="w-6 h-6 text-primary" />
                        </div>
                        <h5 class="text-sm font-semibold text-card-foreground mb-2">Need Help?</h5>
                        <p class="text-xs text-muted-foreground mb-4">
                            Have questions about the referral program? Our team is here to help!
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
</template>

<style scoped>
    .custom-scrollbar {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
