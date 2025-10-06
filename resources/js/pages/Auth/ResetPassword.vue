<script setup lang="ts">
    import { Head, useForm } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import InputError from '@/components/InputError.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import { ref } from 'vue';
    import 'vue-tel-input/vue-tel-input.css';
    import FlashMessages from '@/components/utilities/FlashMessages.vue';

    const props = defineProps<{
        email?: string;
        token?: string;
    }>();

    // Initialize form
    const form = useForm({
        email: props.email || '',
        token: props.token || '',
        password: '',
        password_confirmation: '',
    });

    const showPassword = ref(false);
    const showPasswordConfirmation = ref(false);

    const submit = () => {
        form.post(route('password.update'), {
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
    <Head title="Reset Password" />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md mx-auto">
            <header class="mb-6 text-center">
                <TextLink :href="route('home')" aria-label="Forgot Password" class="inline-flex items-center gap-2 select-none">
                    <img class="w-[110px]" src="/assets/images/logo.png" alt="logo">
                </TextLink>
                <h1 class="mt-4 text-2xl font-semibold">Create New Password</h1>
                <p class="mt-1 text-sm text-muted-foreground">Protect your account with a secure password.</p>
            </header>

            <div v-if="form.errors.email" class="mb-4 text-center text-sm font-medium text-red-600">
                {{ form.errors.email }}
            </div>

            <div v-if="form.errors.token" class="mb-4 text-center text-sm font-medium text-red-600">
                {{ form.errors.token }}
            </div>

            <form method="POST" @submit.prevent="submit" class="space-y-4">
                <input type="hidden" v-model="form.email" />
                <input type="hidden" v-model="form.token" />

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

                <ActionButton :processing="form.processing">Reset Password</ActionButton>
            </form>

            <div class="text-center mt-6">
                <p class="text-muted-foreground text-sm">
                    Already have an account?
                    <TextLink :href="route('login')" class="text-accent hover:underline font-semibold">Sign in</TextLink>
                </p>
            </div>
        </div>
    </div>

    <FlashMessages />
</template>
