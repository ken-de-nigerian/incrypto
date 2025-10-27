<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage, router, useForm } from '@inertiajs/vue3';
    import debounce from 'lodash/debounce';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import {
        Search, Circle, Plus, XCircle, Edit, Wallet2
    } from 'lucide-vue-next';
    import PaginationControls from '@/components/PaginationControls.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import InputError from '@/components/InputError.vue';
    import CheckCircleIcon from '@/components/utilities/CheckCircleIcon.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';

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

    interface Crypto {
        id: number;
        symbol: string;
        name: string;
        image: string;
    }

    interface Wallet {
        method_code: number;
        name: string;
        abbreviation: string;
        gateway_parameter: string;
        status: '1' | '0';
        coingecko_id: string
        image: string
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
        gateways: PaginatedData<Wallet>;
        cryptos: Crypto[];
        filters: {
            search: string | null;
            status: '1' | '0' | null;
        };
        metrics: {
            total_gateways: number;
            active_gateways: number;
            deactivated_gateways: number;
        };
    }

    const props = defineProps<Props>();

    const isNotificationsModalOpen = ref(false);
    const isAddMethodModalOpen = ref(false);
    const isEditMethodModalOpen = ref(false);

    const openNotificationsModal = () => { isNotificationsModalOpen.value = true; };
    const closeNotificationsModal = () => { isNotificationsModalOpen.value = false; };

    const methodForm = useForm({
        name: '',
        abbreviation: '',
        gateway_parameter: '',
        coingecko_id: '',
        status: '1',
    });

    const editMethodForm = useForm({
        name: '',
        abbreviation: '',
        gateway_parameter: '',
        coingecko_id: '',
        status: '1',
        method_code: null as number | null,
    });

    const selectedCryptoId = ref<number | null>(null);
    const editingMethodId = ref<number | null>(null);

    const cryptoOptions = computed(() => {
        return (props.cryptos || []).map(crypto => ({
            value: crypto.id.toString(),
            label: `${crypto.name} (${crypto.symbol.toUpperCase()})`,
            name: crypto.name,
            symbol: crypto.symbol,
            image: crypto.image
        }));
    });

    const selectedCryptoData = computed(() => {
        if (!selectedCryptoId.value) return null;
        return cryptoOptions.value.find(c => c.value === selectedCryptoId.value.toString());
    });

    watch(selectedCryptoId, (newId) => {
        if (newId) {
            const crypto = props.cryptos?.find(c => c.id === newId);
            if (crypto) {
                methodForm.name = crypto.name;
                methodForm.abbreviation = crypto.symbol.toUpperCase();
                methodForm.coingecko_id = crypto.id.toString();
            }
        }
    });

    const closeAllModals = () => {
        isAddMethodModalOpen.value = false;
        isEditMethodModalOpen.value = false;
        methodForm.reset();
        editMethodForm.reset();
        selectedCryptoId.value = null;
        editingMethodId.value = null;
    };

    const openAddModal = () => {
        methodForm.reset();
        selectedCryptoId.value = null;
        editingMethodId.value = null;
        isAddMethodModalOpen.value = true;
    };

    const openEditModal = (method: Wallet) => {
        editingMethodId.value = method.method_code;
        editMethodForm.name = method.name;
        editMethodForm.abbreviation = method.abbreviation;
        editMethodForm.gateway_parameter = method.gateway_parameter;
        editMethodForm.coingecko_id = method.coingecko_id;
        editMethodForm.status = method.status;
        editMethodForm.method_code = method.method_code;

        const crypto = props.cryptos?.find(c => c.id == method.coingecko_id);
        if (crypto) {
            selectedCryptoId.value = crypto.id;
        }

        isEditMethodModalOpen.value = true;
    };

    const saveMethod = () => {
        if (editingMethodId.value) {
            editMethodForm.patch(route('admin.method.update', editingMethodId.value), {
                preserveScroll: true,
                onSuccess: () => closeAllModals(),
            });
        } else {
            methodForm.post(route('admin.method.store'), {
                preserveScroll: true,
                onSuccess: () => closeAllModals(),
            });
        }
    };

    const form = ref({
        search: props.filters.search || '',
        status: props.filters.status || '',
    });

    const filteredAndPagedWallets = computed(() => props.gateways.data);
    const totalActiveGateways = computed(() => props.metrics.active_gateways);
    const totalDeactivatedGateways = computed(() => props.metrics.deactivated_gateways);

    const performFilter = debounce(() => {
        router.get(
            route('admin.method.index'),
            {
                search: form.value.search || null,
                status: form.value.status || null,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }, 300);

    watch(() => form.value.search, performFilter);
    watch(() => form.value.status, performFilter);

    const goToPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getStatusClass = (walletStatus: Wallet['status']) => {
        switch (walletStatus) {
            case '1':
                return 'bg-success/20 border border-success/30 text-success';
            default:
                return 'bg-destructive/20 border border-destructive/30 text-destructive';
        }
    };

    const getStatusLabel = (walletStatus: Wallet['status']) => {
        return walletStatus === '1' ? 'Active' : 'Deactivated';
    };

    const hasActiveFilters = computed(() => form.value.search || form.value.status);

    const clearFilters = () => {
        form.value.search = '';
        form.value.status = '';
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: 'Payment Methods' }
    ];

    const actionTypeOptions = ref([
        { value: '1', label: 'Active' },
        { value: '0', label: 'Deactivated' },
    ]);

    const clearError = (field: keyof typeof methodForm.errors, isEdit: boolean = false) => {
        const form = isEdit ? editMethodForm : methodForm;
        if (form.errors[field as keyof typeof form.errors]) {
            form.clearErrors(field as any);
        }
    };

    const currentFormProcessing = computed(() => {
        return editingMethodId.value ? editMethodForm.processing : methodForm.processing;
    });

    const currentForm = computed(() => {
        return editingMethodId.value ? editMethodForm : methodForm;
    });
</script>

<template>
    <Head title="Payment Methods" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                <div class="p-4 bg-card border border-border rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Gateways</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ props.metrics.total_gateways }}</p>
                        </div>
                        <Wallet2 class="w-12 h-12 text-primary opacity-20" />
                    </div>
                </div>

                <div class="p-4 bg-card border border-border rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Active Gateways</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ totalActiveGateways }}</p>
                        </div>
                        <CheckCircleIcon class="w-14 h-14 text-success opacity-20 fill-success" />
                    </div>
                </div>

                <div class="p-4 bg-card border border-border rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Deactivated Gateways</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ totalDeactivatedGateways }}</p>
                        </div>
                        <XCircle class="w-12 h-12 text-destructive opacity-20" />
                    </div>
                </div>
            </div>

            <div class="mt-8 space-y-4 p-4 bg-card border border-border rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                            <Search class="w-4 h-4 text-muted-foreground" />
                        </div>
                        <input v-model="form.search" type="text" placeholder="Search by name or abbreviation..." class="w-full rounded-lg border border-border input-crypto text-sm font-medium pl-8 h-10 lg:h-auto" />
                    </div>

                    <div>
                        <CustomSelectDropdown
                            v-model="form.status"
                            :options="actionTypeOptions"
                            placeholder="Select Status"
                        />
                    </div>

                    <div class="flex gap-2">
                        <button v-if="hasActiveFilters" @click="clearFilters" class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium transition-colors cursor-pointer h-10 lg:h-auto">
                            <XCircle class="w-4 h-4" />
                            <span>Clear</span>
                        </button>
                    </div>

                    <div>
                        <button @click="openAddModal()" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium transition-colors cursor-pointer">
                            <Plus class="w-4 h-4" />
                            <span>Add Method</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mt-8" :class="{ 'margin-bottom': props.gateways.last_page <= 1 }">
                <template v-if="filteredAndPagedWallets.length">
                    <div v-for="method in filteredAndPagedWallets" :key="method.method_code" class="card-crypto relative">
                        <div class="p-4">
                            <div class="text-center space-y-2">
                                <div class="w-20 h-20 rounded-full mx-auto bg-secondary/70 overflow-hidden flex items-center justify-center border border-border">
                                    <img
                                        :src="method.image"
                                        :alt="method.name"
                                        class="h-full w-full object-cover"
                                        @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                                    >
                                </div>

                                <h6 class="text-lg font-semibold">
                                    <span class="text-foreground hover:text-primary transition-colors">{{ method.name }}</span>
                                </h6>

                                <div class="flex flex-wrap justify-center gap-2">
                                    <span class="inline-flex items-center space-x-1 text-xs font-semibold rounded-full px-3 py-1" :class="getStatusClass(method.status)">
                                        <Circle class="w-2 h-2 fill-current" />
                                        <span>{{ getStatusLabel(method.status) }}</span>
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <span class="block text-sm font-medium text-muted-foreground">
                                        <span class="font-semibold text-foreground">{{ method.abbreviation }}</span>
                                    </span>
                                    <span class="block text-xs text-muted-foreground truncate" :title="method.gateway_parameter">
                                        {{ method.gateway_parameter }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center gap-2 pb-4">
                            <button
                                @click="openEditModal(method)"
                                class="flex items-center justify-center gap-2 px-3 py-1 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg text-sm font-medium cursor-pointer">
                                <Edit class="w-4 h-4" />
                                <span>Edit Wallet</span>
                            </button>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="lg:col-span-4 sm:col-span-2 col-span-1">
                        <div class="card-crypto p-10 text-center border-dashed border-border flex flex-col items-center justify-center">
                            <div class="w-24 h-24 mx-auto mb-4">
                                <XCircle class="w-full h-full text-muted-foreground" />
                            </div>
                            <h6 class="text-lg font-semibold text-foreground">No payment methods found</h6>
                            <p class="text-muted-foreground mt-1">Add your first payment method to get started.</p>
                        </div>
                    </div>
                </template>
            </div>

            <PaginationControls
                class="margin-bottom"
                v-if="props.gateways.last_page > 1"
                :links="props.gateways.links"
                :from="props.gateways.from"
                :to="props.gateways.to"
                :total="props.gateways.total"
                @go-to-page="goToPage"
            />
        </div>
    </AppLayout>

    <QuickActionModal
        :is-open="isAddMethodModalOpen || isEditMethodModalOpen"
        :title="editingMethodId ? 'Edit Payment Method' : 'Add Payment Method'"
        :subtitle="editingMethodId ? 'Update the payment method details.' : 'Enter the payment method details.'"
        @close="closeAllModals">

        <div class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Select Cryptocurrency</label>
                <CustomSelectDropdown
                    v-model="selectedCryptoId"
                    :options="cryptoOptions"
                    placeholder="Select a cryptocurrency">
                    <template #default>
                        <template v-if="selectedCryptoData">
                            <div class="flex items-center gap-3">
                                <img
                                    :src="selectedCryptoData.image"
                                    :alt="selectedCryptoData.symbol"
                                    class="h-8 w-8 object-cover rounded-full"
                                    @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                                />
                                <div class="text-left">
                                    <div class="font-medium text-card-foreground">{{ selectedCryptoData.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ selectedCryptoData.symbol.toUpperCase() }}</div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <span class="text-muted-foreground">Select a cryptocurrency</span>
                        </template>
                    </template>

                    <template #option="{ option }">
                        <div class="flex items-center gap-3 w-full">
                            <img
                                :src="option.image"
                                :alt="option.symbol"
                                class="h-8 w-8 object-cover rounded-full flex-shrink-0"
                                @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                            />
                            <div class="text-left flex-1">
                                <div class="font-medium text-card-foreground">{{ option.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ option.symbol.toUpperCase() }}</div>
                            </div>
                        </div>
                    </template>
                </CustomSelectDropdown>
                <InputError :message="currentForm.errors.wallet_symbol" />
                <p v-if="cryptoOptions.length === 0" class="text-sm text-warning p-2 rounded-lg bg-warning/10 border border-warning/30">
                    No cryptos found.
                </p>
            </div>

            <div class="space-y-2">
                <label for="name" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    Method Name
                </label>
                <input
                    id="name"
                    type="text"
                    :value="currentForm.name"
                    @input="currentForm.name = $event.target.value"
                    @focus="clearError('name', editingMethodId !== null)"
                    placeholder="Auto-populated from crypto selection"
                    class="input-crypto w-full text-sm"
                />
                <InputError :message="currentForm.errors.name" />
            </div>

            <div class="space-y-2">
                <label for="abbreviation" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    Abbreviation
                </label>
                <input
                    id="abbreviation"
                    type="text"
                    :value="currentForm.abbreviation"
                    @focus="clearError('abbreviation', editingMethodId !== null)"
                    placeholder="Auto-populated from crypto selection"
                    disabled="disabled"
                    class="input-crypto w-full text-sm"
                />
                <InputError :message="currentForm.errors.abbreviation" />
            </div>

            <input type="hidden" :value="currentForm.coingecko_id" />

            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Status</label>
                <CustomSelectDropdown
                    :model-value="currentForm.status"
                    @update:model-value="currentForm.status = $event"
                    :options="actionTypeOptions"
                    placeholder="Select Status"
                />
            </div>

            <div class="space-y-2">
                <label for="gateway_parameter" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    Wallet Address
                </label>
                <textarea
                    id="gateway_parameter"
                    :value="currentForm.gateway_parameter"
                    @input="currentForm.gateway_parameter = $event.target.value"
                    @focus="clearError('gateway_parameter', editingMethodId !== null)"
                    placeholder="Enter wallet address..."
                    rows="3"
                    class="input-crypto w-full text-sm">
                </textarea>
                <InputError :message="currentForm.errors.gateway_parameter" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <ActionButton :processing="currentFormProcessing" @click="saveMethod">
                    {{ editingMethodId ? 'Update Method' : 'Create Method' }}
                </ActionButton>
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
