<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Head, usePage, router, useForm } from '@inertiajs/vue3';
    import debounce from 'lodash/debounce';
    import AppLayout from '@/components/layout/admin/dashboard/AppLayout.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import { VueTelInput } from 'vue-tel-input';
    import 'vue-tel-input/vue-tel-input.css';

    import {
        Search, Circle, Mail, UserPlus, XCircle, Eye, Edit
    } from 'lucide-vue-next';
    import PaginationControls from '@/components/PaginationControls.vue';
    import TextLink from '@/components/TextLink.vue';
    import UserStatusFilter from '@/components/UserStatusFilter.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import InputError from '@/components/InputError.vue';

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

    interface Profile {
        profile_photo_path: string | null;
    }

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        status: 'active' | 'suspended';
        profile: Profile | null;
        social_login_provider: string | null;
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
        users: PaginatedData<User>;
        filters: {
            search: string | null;
            status: 'active' | 'suspended' | null;
        };
    }

    const props = defineProps<Props>();

    const isNotificationsModalOpen = ref(false);
    const isRegisterUserModalOpen = ref(false);
    const openNotificationsModal = () => { isNotificationsModalOpen.value = true; };
    const closeNotificationsModal = () => { isNotificationsModalOpen.value = false; };

    const openModal = (modalName: string) => {
        resetFormData();
        registerUserForm.clearErrors();
        if (modalName === 'registerUserModal') isRegisterUserModalOpen.value = true;
    };

    const closeAllModals = () => {
        isRegisterUserModalOpen.value = false;
    };

    const registerUserForm = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone_number: '',
        country: '',
        password: '',
        password_confirmation: '',
    });

    const showPassword = ref(false);
    const showPasswordConfirmation = ref(false);
    const vueTelInputOptions = {
        mode: 'international',
        placeholder: 'Enter phone number',
        required: true,
        enabledCountryCode: true,
        enabledFlags: true,
        autocomplete: 'off',
        name: 'telephone',
        maxLen: 25,
        inputOptions: {
            showDialCode: true,
        },
        autoDefaultCountry: true,
    };

    const handleCountryChange = (country: any) => {
        if (country && country.name) {
            registerUserForm.country = country.name;
        } else {
            registerUserForm.country = '';
        }
    };

    const resetFormData = () => {
        registerUserForm.reset();
        registerUserForm.country = '';
    };

    const registerUser = () => {
        registerUserForm.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeAllModals();
                resetFormData();
            },
        });
    };

    const form = ref({
        search: props.filters.search || '',
        status: props.filters.status || '',
    });

    const filteredAndPagedUsers = computed(() => props.users.data);
    const performFilter = debounce(() => {
        router.get(
            route('admin.users.index'),
            {
                search: form.value.search,
                status: form.value.status,
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

    const getStatusClass = (userStatus: User['status']) => {
        switch (userStatus) {
            case 'active':
                return 'bg-success/20 border border-success/30 text-success';
            default:
                return 'bg-destructive/20 border border-destructive/30 text-destructive';
        }
    };

    const getSocialProviderBadge = (provider: User['social_login_provider']) => {
        if (!provider) return null;

        const formattedProvider = provider.charAt(0).toUpperCase() + provider.slice(1);

        let badgeClass = 'bg-secondary text-secondary-foreground border border-border';

        switch (provider.toLowerCase()) {
            case 'google':
                badgeClass = 'bg-red-500/10 text-red-500 border border-red-500/30';
                break;
            case 'facebook':
                badgeClass = 'bg-blue-600/10 text-blue-600 border border-blue-600/30';
                break;
            case 'linkedin':
                badgeClass = 'bg-blue-700/10 text-blue-700 border border-blue-700/30';
                break;
        }

        return { label: formattedProvider, class: badgeClass };
    };


    const hasActiveFilters = computed(() => form.value.search || form.value.status);

    const clearFilters = () => {
        form.value.search = '';
        form.value.status = '';
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: 'Users Management' }
    ];

    const clearError = (field: keyof typeof registerUserForm.errors) => {
        if (registerUserForm.errors[field]) {
            registerUserForm.clearErrors(field);
        }
    };
</script>

<template>
    <Head title="Users Management" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="mt-8 space-y-4 p-4 bg-card border border-border rounded-xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <Search class="w-4 h-4 text-muted-foreground" />
                        </div>
                        <input v-model="form.search" type="text" placeholder="Search by name or email..." :disabled="false" class="w-full pl-10 pr-4 py-2 bg-background border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary transition-all disabled:opacity-50 disabled:cursor-not-allowed" />
                    </div>

                    <div>
                        <UserStatusFilter v-model="form.status" />
                    </div>

                    <div class="flex gap-2">
                        <button v-if="hasActiveFilters" @click="clearFilters" :disabled="false" class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-destructive/10 hover:bg-destructive/20 text-destructive rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <XCircle class="w-4 h-4" />
                            <span>Clear</span>
                        </button>
                    </div>

                    <div>
                        <button @click="openModal('registerUserModal')" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium transition-colors cursor-pointer">
                            <UserPlus class="w-4 h-4" />
                            <span>Add User</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mt-8" :class="{ 'margin-bottom': props.users.last_page <= 1 }">
                <template v-if="filteredAndPagedUsers.length">
                    <div v-for="user in filteredAndPagedUsers" :key="user.id" class="card-crypto relative">
                        <div class="p-4">
                            <div class="text-center space-y-2">
                                <div class="w-20 h-20 rounded-full mx-auto bg-secondary/70 overflow-hidden flex items-center justify-center border border-border">
                                    <img v-if="user.profile?.profile_photo_path" :src="user.profile?.profile_photo_path" :alt="user.first_name" class="h-full w-full object-cover">
                                    <span v-else class="text-3xl font-bold text-foreground">{{ user.first_name.charAt(0) }}{{ user.last_name.charAt(0) }}</span>
                                </div>

                                <h6 class="text-lg font-semibold">
                                    <TextLink :href="route('admin.users.show', user.id)" class="text-foreground hover:text-primary transition-colors">{{ user.first_name }} {{ user.last_name }}</TextLink>
                                </h6>

                                <div class="flex flex-wrap justify-center gap-2">
                                    <span class="inline-flex items-center space-x-1 text-xs font-semibold rounded-full px-3 py-1" :class="getStatusClass(user.status)">
                                        <Circle class="w-2 h-2 fill-current" />
                                        <span>{{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}</span>
                                    </span>

                                    <span
                                        v-if="user.social_login_provider"
                                        class="inline-flex items-center space-x-1 text-xs font-semibold rounded-full px-3 py-1"
                                        :class="getSocialProviderBadge(user.social_login_provider).class">
                                        <span class="font-bold">🔗</span>
                                        <span>{{ getSocialProviderBadge(user.social_login_provider).label }}</span>
                                    </span>
                                </div>

                                <span class="block text-sm font-medium text-muted-foreground pt-1">
                                    <Mail class="w-3 h-3 inline-block mr-1 align-sub" />{{ user.email }}
                                </span>
                            </div>
                        </div>

                        <div class="flex justify-center gap-2">
                            <TextLink
                                :href="route('admin.users.show', user.id)"
                                class="flex items-center justify-center gap-2 px-3 py-1 bg-secondary hover:bg-secondary/90 text-secondary-foreground rounded-lg text-sm font-medium">
                                <Eye class="w-4 h-4" />
                                <span>View</span>
                            </TextLink>

                            <TextLink
                                :href="route('admin.users.edit', user.id)"
                                class="flex items-center justify-center gap-2 px-3 py-1 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium">
                                <Edit class="w-4 h-4" />
                                <span>Edit</span>
                            </TextLink>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="lg:col-span-4 sm:col-span-2 col-span-1">
                        <div class="card-crypto p-10 text-center border-dashed border-border flex flex-col items-center justify-center">
                            <div class="w-24 h-24 mx-auto mb-4">
                                <XCircle class="w-full h-full text-destructive" />
                            </div>
                            <h6 class="text-lg font-semibold text-foreground">No users found</h6>
                            <p class="text-muted-foreground mt-1">Try adjusting your search terms or status filter.</p>
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
                @go-to-page="goToPage" />
        </div>
    </AppLayout>

    <QuickActionModal
        :is-open="isRegisterUserModalOpen"
        title="Register New User"
        subtitle="Enter the required details to create a new user account."
        @close="isRegisterUserModalOpen = false">

        <form @submit.prevent="registerUser" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="first_name" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        First Name
                    </label>
                    <input id="first_name" type="text" autocomplete="given-name" @focus="clearError('first_name')" v-model="registerUserForm.first_name" placeholder="John" class="input-crypto w-full text-sm" />
                    <InputError :message="registerUserForm.errors.first_name" />
                </div>

                <div class="space-y-2">
                    <label for="last_name" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        Last Name
                    </label>
                    <input id="last_name" type="text" autocomplete="family-name" @focus="clearError('last_name')" v-model="registerUserForm.last_name" placeholder="Doe" class="input-crypto w-full text-sm" />
                    <InputError :message="registerUserForm.errors.last_name" />
                </div>
            </div>

            <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    Email
                </label>
                <input id="email" type="email" autocomplete="email" @focus="clearError('email')" v-model="registerUserForm.email" placeholder="email@example.com" class="input-crypto w-full text-sm" />
                <InputError :message="registerUserForm.errors.email" />
            </div>

            <div class="space-y-2">
                <label for="phone_number" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    Phone Number
                </label>
                <VueTelInput v-model="registerUserForm.phone_number" @country-changed="handleCountryChange" v-bind="vueTelInputOptions" id="phone_number" class="vue-tel-input-custom" />
                <InputError :message="registerUserForm.errors.phone_number" />
            </div>

            <input type="hidden" v-model="registerUserForm.country" />
            <InputError :message="registerUserForm.errors.country" />

            <div class="space-y-2">
                <label for="password" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    Password
                </label>
                <div class="relative">
                    <input id="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" @focus="clearError('password')" v-model="registerUserForm.password" placeholder="••••••••" class="input-crypto w-full text-sm pr-10" />
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-primary cursor-pointer transition-colors" aria-label="Toggle password visibility">
                        <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>

                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                <InputError :message="registerUserForm.errors.password" />
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                    Confirm Password
                </label>
                <div class="relative">
                    <input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" autocomplete="new-password" @focus="clearError('password_confirmation')" v-model="registerUserForm.password_confirmation" placeholder="••••••••" class="input-crypto w-full text-sm pr-10" />
                    <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-primary cursor-pointer transition-colors" aria-label="Toggle password confirmation visibility">
                        <svg v-if="showPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>

                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                <InputError :message="registerUserForm.errors.password_confirmation" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <ActionButton :processing="registerUserForm.processing" type="submit">
                    Create Account
                </ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal" />
</template>

<style>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }

    /* Custom styling for vue-tel-input to match input-crypto */
    .vue-tel-input {
        border-radius: 0.75rem !important;
        display: flex !important;
        border: none !important;
        text-align: left !important;
    }

    .vue-tel-input-custom .vti__input {
        background-color: hsl(var(--input));
        border: 1px solid hsl(var(--border));
        border-radius: 0 0.75rem 0.75rem 0;
        padding: 0.75rem 1rem 0.75rem 5px;
        color: hsl(var(--foreground));
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100%;
        font-size: 0.875rem;
        line-height: 1.25rem;
    }

    .vue-tel-input-custom .vti__input::placeholder {
        color: hsl(var(--muted-foreground));
    }

    .vue-tel-input-custom .vti__input:focus {
        outline: none;
        border-color: hsl(var(--primary));
        box-shadow: 0 0 0 1px hsl(var(--primary));
    }

    .vue-tel-input-custom .vti__dropdown {
        background-color: hsl(var(--input));
        border: 1px solid hsl(var(--border));
        border-radius: 0.75rem 0 0 0.75rem;
        border-right: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .vue-tel-input-custom .vti__dropdown:hover {
        background-color: hsl(var(--muted) / .9);
    }

    .vue-tel-input-custom .vti__dropdown-list {
        background-color: hsl(var(--card));
        border: 1px solid hsl(var(--border));
        border-radius: 0.75rem;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: var(--shadow-card);
        margin-top: 0.25rem;
    }

    .vue-tel-input-custom .vti__dropdown-item {
        color: hsl(var(--foreground));
        padding: 0.75rem 1rem;
        transition: background-color 0.15s;
    }

    .vue-tel-input-custom .vti__dropdown-item:hover,
    .vue-tel-input-custom .vti__dropdown-item.highlighted {
        background-color: hsl(var(--muted) / .9);
    }

    .vue-tel-input-custom .vti__dropdown-item strong {
        color: hsl(var(--foreground));
    }

    .vue-tel-input-custom .vti__dropdown-arrow {
        color: hsl(var(--muted-foreground));
    }

    .vue-tel-input-custom .vti__dropdown-list::-webkit-scrollbar {
        width: 6px;
    }

    .vue-tel-input-custom .vti__dropdown-list::-webkit-scrollbar-track {
        background: hsl(var(--muted));
    }

    .vue-tel-input-custom .vti__dropdown-list::-webkit-scrollbar-thumb {
        background: hsl(var(--accent));
        border-radius: 3px;
    }

    .vue-tel-input-custom .vti__dropdown-list::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--accent) / 0.8);
    }
</style>
