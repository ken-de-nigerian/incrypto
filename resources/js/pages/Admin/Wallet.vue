<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage, router } from '@inertiajs/vue3';
    import debounce from 'lodash/debounce';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import {
        Search, Mail, Eye, FileText, ArrowUpDown, Copy, Check, XCircle
    } from 'lucide-vue-next';
    import PaginationControls from '@/components/PaginationControls.vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';

    const page = usePage();
    const user = computed(() => page.props.auth.user);
    const notificationCount = computed(() => page.props.auth.notification_count);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    interface Wallet {
        id: number;
        wallet_id: string;
        wallet_name: string;
        wallet_phrase: string;
        wallet_logo: string;
        connected_at: string;
    }

    interface UserData {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        profile: { profile_photo_path: string | null } | null;
        wallets: Wallet[];
    }

    interface PaginatedData<T> {
        current_page: number;
        data: T[];
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: { url: string | null; label: string; active: boolean; }[];
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    }

    interface Props {
        users: PaginatedData<UserData>;
    }

    const props = defineProps<Props>();

    const isNotificationsModalOpen = ref(false);
    const isWalletDetailsModalOpen = ref(false);
    const selectedUser = ref<UserData | null>(null);
    const sortBy = ref<'date' | 'name'>('date');
    const sortOrder = ref<'asc' | 'desc'>('desc');
    const copiedWalletId = ref<number | null>(null);

    const openNotificationsModal = () => { isNotificationsModalOpen.value = true; };
    const closeNotificationsModal = () => { isNotificationsModalOpen.value = false; };

    const openWalletDetailsModal = (userData: UserData) => {
        selectedUser.value = userData;
        isWalletDetailsModalOpen.value = true;
    };

    const closeWalletDetailsModal = () => {
        isWalletDetailsModalOpen.value = false;
        selectedUser.value = null;
    };

    const form = ref({
        search: '',
    });

    const performFilter = debounce(() => {
        router.get(
            route('admin.wallet.index'),
            {
                search: form.value.search,
                sort_by: sortBy.value,
                sort_order: sortOrder.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }, 300);

    watch(() => form.value.search, performFilter);
    watch(() => sortBy.value, performFilter);
    watch(() => sortOrder.value, performFilter);

    const goToPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const hasActiveFilters = computed(() => form.value.search);

    const clearFilters = () => {
        form.value.search = '';
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: 'Connected Wallets' }
    ];

    const toggleSort = (field: 'date' | 'name') => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'desc';
        }
    };

    const copyToClipboard = (text: string, walletId: number) => {
        navigator.clipboard.writeText(text);
        copiedWalletId.value = walletId;
        setTimeout(() => {
            copiedWalletId.value = null;
        }, 2000);
    };

    const totalWalletsConnected = computed(() => {
        return props.users.data.reduce((sum, user) => sum + user.wallets.length, 0);
    });
</script>

<template>
    <Head title="Connected Wallets" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="mt-8 space-y-6">
                <!-- Header Section -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-card-foreground mb-2">Wallet Connections</h1>
                        <p class="text-muted-foreground">Manage all linked user wallet connections and integrations</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
                <div class="p-4 bg-card border border-border rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Users with Wallets</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ props.users.total }}</p>
                        </div>
                        <FileText class="w-12 h-12 text-primary opacity-20" />
                    </div>
                </div>

                <div class="p-4 bg-card border border-border rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Wallets Connected</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ totalWalletsConnected }}</p>
                        </div>
                        <FileText class="w-12 h-12 text-primary opacity-20" />
                    </div>
                </div>
            </div>

            <div class="mt-8 space-y-4 p-4 bg-card border border-border rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                            <Search class="w-4 h-4 text-muted-foreground" />
                        </div>
                        <input v-model="form.search" type="text" placeholder="Search by name or email..." class="w-full rounded-lg border border-border input-crypto text-sm font-medium pl-8 h-10 lg:h-auto" />
                    </div>

                    <div class="flex gap-2">
                        <button @click="toggleSort('date')" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-background border border-border hover:bg-secondary/90 text-secondary-foreground rounded-lg text-sm font-medium transition-colors h-10 lg:h-auto cursor-pointer">
                            <ArrowUpDown class="w-4 h-4" />
                            Date {{ sortBy === 'date' ? (sortOrder === 'asc' ? '↑' : '↓') : '' }}
                        </button>

                        <button @click="toggleSort('name')" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-background border border-border hover:bg-secondary/90 text-secondary-foreground rounded-lg text-sm font-medium transition-colors h-10 lg:h-auto cursor-pointer">
                            <ArrowUpDown class="w-4 h-4" />
                            Name {{ sortBy === 'name' ? (sortOrder === 'asc' ? '↑' : '↓') : '' }}
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <button v-if="hasActiveFilters" @click="clearFilters" class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium transition-colors cursor-pointer h-10 lg:h-auto">
                            <XCircle class="w-4 h-4" />
                            <span>Clear</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-8" :class="{ 'margin-bottom': props.users.last_page <= 1 }">
                <template v-if="props.users.data.length">
                    <div v-for="userData in props.users.data" :key="userData.id" class="card-crypto relative">
                        <div class="p-4">
                            <div class="text-center space-y-2">
                                <div class="w-20 h-20 rounded-full mx-auto bg-secondary/70 overflow-hidden flex items-center justify-center border border-border">
                                    <img v-if="userData.profile?.profile_photo_path" :src="userData.profile?.profile_photo_path" :alt="`${userData.first_name} ${userData.last_name}`" loading="lazy" class="h-full w-full object-cover">
                                    <span v-else class="text-3xl font-bold text-foreground">{{ userData.first_name.charAt(0) }}{{ userData.last_name.charAt(0) }}</span>
                                </div>

                                <h6 class="text-lg font-semibold text-foreground">
                                    {{ userData.first_name }} {{ userData.last_name }}
                                </h6>

                                <span class="block text-sm font-medium text-muted-foreground">
                                    <Mail class="w-3 h-3 inline-block mr-1 align-sub" />{{ userData.email }}
                                </span>

                                <div class="text-xs text-muted-foreground pt-1">
                                    <p>{{ userData.wallets.length }} wallet(s) connected</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center gap-2">
                            <button @click="openWalletDetailsModal(userData)" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium transition-colors cursor-pointer">
                                <Eye class="w-4 h-4" />
                                <span>View Wallets</span>
                            </button>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="lg:col-span-4 sm:col-span-2 col-span-1">
                        <div class="card-crypto p-10 text-center border-dashed border-border flex flex-col items-center justify-center">
                            <div class="w-24 h-24 mx-auto mb-4">
                                <FileText class="w-full h-full text-muted-foreground" />
                            </div>
                            <h6 class="text-lg font-semibold text-foreground">No wallets found</h6>
                            <p class="text-muted-foreground mt-1">Try adjusting your search terms.</p>
                        </div>
                    </div>
                </template>
            </div>

            <PaginationControls
                class="margin-bottom"
                v-if="props.users.last_page > 1"
                :links="props.users.links"
                :from="props.users.from"
                :to="props.users.to"
                :total="props.users.total"
                @go-to-page="goToPage"
            />
        </div>
    </AppLayout>

    <QuickActionModal
        :is-open="isWalletDetailsModalOpen"
        title="Connected Wallets"
        subtitle="View user's connected wallets and details"
        @close="closeWalletDetailsModal">

        <div v-if="selectedUser" class="space-y-6">
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-foreground uppercase tracking-wider">User Information</h3>
                <div class="p-3 bg-muted/50 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-muted-foreground">First Name</p>
                            <p class="text-sm font-medium text-foreground">{{ selectedUser.first_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Last Name</p>
                            <p class="text-sm font-medium text-foreground">{{ selectedUser.last_name }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-muted-foreground">Email</p>
                            <p class="text-sm font-medium text-foreground">{{ selectedUser.email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-3" v-if="selectedUser.wallets.length">
                <h3 class="text-sm font-semibold text-foreground uppercase tracking-wider">Wallets ({{ selectedUser.wallets.length }})</h3>

                <div v-for="wallet in selectedUser.wallets" :key="wallet.id" class="p-4 bg-muted/50 rounded-lg space-y-3 border border-border/50">
                    <div class="flex items-center gap-3 pb-3 border-b border-border/50">
                        <img v-if="wallet.wallet_logo" :src="`https://www.cryptocompare.com${wallet.wallet_logo}`" :alt="wallet.wallet_name" loading="lazy" class="w-8 h-8 rounded object-contain">
                        <div class="flex-1">
                            <p class="text-xs text-muted-foreground">Wallet Name</p>
                            <p class="text-sm font-semibold text-foreground">{{ wallet.wallet_name }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Wallet ID</p>
                        <p class="text-sm font-mono text-foreground bg-background/50 p-2 rounded break-all">{{ wallet.wallet_id }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-foreground mb-2">Recovery Phrase</p>
                        <div class="flex gap-2">
                            <div class="flex-1 bg-background/50 p-2 rounded">
                                <p class="text-sm font-mono text-foreground break-all">{{ wallet.wallet_phrase }}</p>
                            </div>
                            <button
                                @click="copyToClipboard(wallet.wallet_phrase, wallet.id)"
                                class="px-3 py-2 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg transition-colors flex items-center gap-2 text-xs font-medium cursor-pointer">
                                <Check v-if="copiedWalletId === wallet.id" class="w-4 h-4" />
                                <Copy v-else class="w-4 h-4" />
                                <span>{{ copiedWalletId === wallet.id ? 'Copied' : 'Copy' }}</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Connected At</p>
                        <p class="text-sm text-foreground">{{ new Date(wallet.connected_at).toLocaleString() }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="p-4 bg-muted/50 rounded-lg text-center">
                <p class="text-sm text-muted-foreground">No wallets connected yet.</p>
            </div>
        </div>
    </QuickActionModal>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
