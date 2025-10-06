<script setup lang="ts">
    import { Head, useForm } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import InputError from '@/components/InputError.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import { ref } from 'vue';
    import { VueTelInput } from 'vue-tel-input';
    import 'vue-tel-input/vue-tel-input.css';

    const props = defineProps<{
        email?: string;
    }>();

    // Initialize form
    const form = useForm({
        first_name: '',
        last_name: '',
        email: props.email || '',
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
            form.country = country.name;
        }
    };

    const submit = () => {
        form.post(route('onboarding.store'), {
            onFinish: () => {
                form.reset('password', 'password_confirmation');
            },
        });
    };

    const clearError = (field: keyof typeof form.errors) => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };
</script>

<template>
    <Head title="Create Account" />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md mx-auto">
            <header class="mb-6 text-center">
                <TextLink :href="route('home')" aria-label="Onboarding" class="inline-flex items-center gap-2 select-none">
                    <img class="w-[110px]" src="/assets/images/logo.png" alt="logo">
                </TextLink>
                <h1 class="mt-4 text-2xl font-semibold">Create your account</h1>
                <p class="mt-1 text-sm text-muted-foreground">Start your crypto journey today</p>
            </header>

            <form method="POST" @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="first_name" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                            First Name
                        </label>
                        <input id="first_name" type="text" autofocus autocomplete="given-name" @focus="clearError('first_name')" v-model="form.first_name" placeholder="John" class="input-crypto w-full text-sm" />
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <div class="space-y-2">
                        <label for="last_name" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                            Last Name
                        </label>
                        <input id="last_name" type="text" autocomplete="family-name" @focus="clearError('last_name')" v-model="form.last_name" placeholder="Doe" class="input-crypto w-full text-sm" />
                        <InputError :message="form.errors.last_name" />
                    </div>
                </div>

                <input type="hidden" v-model="form.email" />
                <InputError :message="form.errors.email" />

                <div class="space-y-2">
                    <label for="phone_number" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        Phone Number
                    </label>
                    <VueTelInput v-model="form.phone_number" @country-changed="handleCountryChange" v-bind="vueTelInputOptions" id="phone_number" class="vue-tel-input-custom" />
                    <InputError :message="form.errors.phone_number" />
                </div>

                <input type="hidden" v-model="form.country" />
                <InputError :message="form.errors.country" />

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        Password
                    </label>
                    <div class="relative">
                        <input id="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" @focus="clearError('password')" v-model="form.password" placeholder="••••••••" class="input-crypto w-full text-sm pr-10" />
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-accent cursor-pointer" aria-label="Toggle password visibility">
                            <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="space-y-2">
                    <label for="password_confirmation" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" autocomplete="new-password" @focus="clearError('password_confirmation')" v-model="form.password_confirmation" placeholder="••••••••" class="input-crypto w-full text-sm pr-10" />
                        <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-accent cursor-pointer" aria-label="Toggle password confirmation visibility">
                            <svg v-if="showPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <ActionButton :processing="form.processing">Create Account</ActionButton>
            </form>

            <div class="text-center mt-6">
                <p class="text-muted-foreground text-sm">
                    Already have an account?
                    <TextLink :href="route('login')" class="text-accent hover:underline font-semibold">Sign in</TextLink>
                </p>
            </div>
        </div>
    </div>
</template>

<style>
    /* Custom styling for vue-tel-input to match input-crypto */
    .vue-tel-input-custom {
        border-radius: 0.75rem;
        border: 1px solid hsl(var(--border));
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
        background-color: hsl(var(--muted));
    }

    .vue-tel-input-custom .vti__dropdown-list {
        background-color: hsl(var(--card));
        border: 1px solid hsl(var(--border));
        border-radius: 0.75rem;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: 0 4px 20px -2px hsl(0 0% 0% / 0.5);
        margin-top: 0.25rem;
    }

    .vue-tel-input-custom .vti__dropdown-item {
        color: hsl(var(--foreground));
        padding: 0.75rem 1rem;
        transition: background-color 0.15s;
    }

    .vue-tel-input-custom .vti__dropdown-item:hover,
    .vue-tel-input-custom .vti__dropdown-item.highlighted {
        background-color: hsl(var(--muted));
    }

    .vue-tel-input-custom .vti__dropdown-item strong {
        color: hsl(var(--foreground));
    }

    .vue-tel-input-custom .vti__dropdown-arrow {
        color: hsl(var(--muted-foreground));
    }

    /* Scrollbar styling for dropdown */
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
