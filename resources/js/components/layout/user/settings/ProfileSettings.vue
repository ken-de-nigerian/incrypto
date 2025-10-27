<script setup lang="ts">
    import { useForm, usePage } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import { VueTelInput } from 'vue-tel-input';
    import 'vue-tel-input/vue-tel-input.css';
    import { computed, ref } from 'vue';

    import InputError from '@/components/InputError.vue';
    import ActionButton from '@/components/ActionButton.vue';

    const page = usePage();
    const user = computed(() => page.props.auth.user);

    const photoPreview = ref(null);
    const photoInput = ref(null);

    const form = useForm({
        first_name: user.value.first_name || '',
        last_name: user.value.last_name || '',
        phone_number: user.value.phone_number || '',
        country: user.value.profile?.country || '',
        address: user.value.profile?.address || '',
        avatar: null,
    });

    const selectNewPhoto = () => {
        photoInput.value.click();
    };

    const updatePhotoPreview = () => {
        const photo = photoInput.value.files[0];
        if (!photo) return;

        form.avatar = photo;

        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };
        reader.readAsDataURL(photo);
    };

    const removePhoto = () => {
        photoPreview.value = null;
        form.avatar = null;
        form.clearErrors('avatar');
    };

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
            form.country = country.name;
        }
    };

    const clearError = (field: keyof typeof form.errors) => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };

    const submit = () => {
        form.post(route('user.profile.update.profile'), {
            preserveScroll: true,
            onSuccess: () => removePhoto(),
        });
    };

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });
</script>

<template>
    <div class="space-y-6 margin-bottom">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col space-y-1.5 p-6">
                <div class="text-2xl font-semibold">Profile Picture</div>
                <p class="text-sm text-muted-foreground">Upload a picture to personalize your account.</p>
            </div>
            <div class="p-6 pt-0">
                <input ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview" accept="image/*" />

                <div class="flex items-center flex-wrap gap-4">
                    <div class="rounded-xl h-20 w-20 object-cover border border-border overflow-hidden bg-secondary/20 flex items-center justify-center">
                        <img
                            v-if="photoPreview || user.profile?.profile_photo_path"
                            :src="photoPreview || user.profile?.profile_photo_path"
                            loading="lazy"
                            :alt="`${user.first_name} ${user.last_name}`"
                            class="h-full w-full object-cover">
                        <span v-else class="text-3xl font-bold text-secondary-foreground">{{ initials }}</span>
                    </div>

                    <div class="space-y-2">
                        <div class="flex space-x-2">
                            <button @click.prevent="selectNewPhoto" type="button" class="btn-crypto-outline h-9 px-3 text-sm inline-flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera mr-2 h-4 w-4" aria-hidden="true">
                                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                                    <circle cx="12" cy="13" r="3"></circle>
                                </svg>
                                Change
                            </button>

                            <button v-if="photoPreview" @click.prevent="removePhoto" type="button" class="btn-crypto-outline h-9 px-3 text-sm inline-flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 lucide-trash-2 mr-2 h-4 w-4" aria-hidden="true">
                                    <path d="M3 6h18"></path>
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                    <line x1="10" x2="10" y1="11" y2="17"></line>
                                    <line x1="14" x2="14" y1="11" y2="17"></line>
                                </svg>
                                Remove
                            </button>
                        </div>
                        <p class="text-xs text-muted-foreground">JPG, PNG or GIF. Max size 5MB.</p>
                    </div>
                </div>
                <InputError :message="form.errors.avatar" class="mt-2" />
            </div>
        </div>

        <form @submit.prevent="submit" class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col space-y-1.5 p-6">
                <div class="text-2xl font-semibold">Personal Information</div>
                <p class="text-sm text-muted-foreground">Update your personal details and contact information.</p>
            </div>

            <div class="p-6 pt-0 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="firstName" class="text-sm font-medium">First Name</label>
                        <input id="firstName" v-model="form.first_name" @focus="clearError('first_name')" type="text" placeholder="John" class="input-crypto w-full" />
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <div class="space-y-2">
                        <label for="lastName" class="text-sm font-medium">Last Name</label>
                        <input id="lastName" v-model="form.last_name" @focus="clearError('last_name')" type="text" placeholder="Doe" class="input-crypto w-full" />
                        <InputError :message="form.errors.last_name" />
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium">Email Address</label>
                        <input id="email" :value="user.email" disabled type="email" class="input-crypto w-full disabled:cursor-not-allowed disabled:opacity-60" autocomplete="email"/>
                    </div>

                    <div class="space-y-2">
                        <label for="phone_number" class="text-sm font-medium">Phone Number</label>
                        <VueTelInput v-model="form.phone_number" @country-changed="handleCountryChange" @focus="clearError('phone_number')" v-bind="vueTelInputOptions" id="phone_number" class="vue-tel-input-custom" />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <input type="hidden" v-model="form.country" />

                    <div class="space-y-2">
                        <label for="address" class="text-sm font-medium">Address</label>
                        <input id="address" v-model="form.address" @focus="clearError('address')" type="text" placeholder="e.g., 123 Main St, New York, America" class="input-crypto w-full" autocomplete="address" />
                        <InputError :message="form.errors.address" />
                    </div>
                </div>
            </div>

            <div class="flex items-center p-6 pt-0 gap-4">
                <ActionButton :processing="form.processing">Save Changes</ActionButton>
            </div>
        </form>
    </div>
</template>

<style>
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
        border-color: hsl(var(--accent));
        box-shadow: 0 0 0 1px hsl(var(--accent));
    }

    .vue-tel-input-custom .vti__dropdown {
        background-color: hsl(var(--input));
        border: 1px solid hsl(var(--border));
        border-radius: 0.75rem 0 0 0.75rem;
        border-right: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .vue-tel-input-custom .vti__dropdown:hover {
        /* UPDATED: Change to explicit opacity utility hsl(var(--muted) / .9) */
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
        /* UPDATED: Change to explicit opacity utility hsl(var(--muted) / .9) */
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

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
