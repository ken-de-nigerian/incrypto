<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import InputError from '@/components/InputError.vue';
    import AuthCard from '@/components/layout/auth/AuthCard.vue';
    import ActionButton from '@/components/ActionButton.vue';

    defineProps<{
        status?: string;
        canResetPassword: boolean;
        errors?: object;
    }>();

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const showPassword = ref(false);
    const throttleTimeLeft = ref<number>(0);
    const throttleInterval = ref<NodeJS.Timeout | null>(null);

    const startThrottleCountdown = () => {
        if (throttleInterval.value) {
            clearInterval(throttleInterval.value);
            throttleInterval.value = null;
        }
        throttleInterval.value = setInterval(() => {
            throttleTimeLeft.value -= 1;
            if (throttleTimeLeft.value <= 0) {
                clearInterval(throttleInterval.value!);
                throttleInterval.value = null;
                form.clearErrors('throttle');
            }
        }, 1000);
    };

    if (form.errors.throttle) {
        throttleTimeLeft.value = Number(form.errors.throttle);
        startThrottleCountdown();
    }

    const submit = () => {
        form.post(route('login'), {
            onFinish: () => {
                form.reset('password');
                if (form.errors.throttle) {
                    throttleTimeLeft.value = Number(form.errors.throttle);
                    startThrottleCountdown();
                }
            },
        });
    };

    const clearError = () => {
        if (form.errors.email) form.clearErrors('email');
        if (form.errors.password) form.clearErrors('password');
    };

    const throttleErrorMessage = computed(() => {
        if (throttleTimeLeft.value <= 0) return form.errors.email || '';
        const minutes = Math.floor(throttleTimeLeft.value / 60);
        const seconds = throttleTimeLeft.value % 60;
        if (minutes > 0) {
            return `Please wait ${minutes} minute${minutes > 1 ? 's' : ''} and ${seconds} second${seconds !== 1 ? 's' : ''} before retrying.`;
        }
        return `Please wait ${seconds} second${seconds !== 1 ? 's' : ''} before retrying.`;
    });
</script>

<template>
    <AuthCard>
        <template #header>
            <header class="mb-6 text-center">
                <TextLink :href="route('home')" aria-label="Login" class="inline-flex items-center gap-2 select-none">
                    <img class="w-[110px]" src="/assets/images/logo.png" loading="lazy" alt="logo">
                </TextLink>
                <h1 class="mt-4 text-2xl font-semibold">Log in to your account</h1>
                <p class="mt-1 text-sm text-muted-foreground">Welcome back! Please enter your details.</p>
            </header>
        </template>

        <template #form>
            <div v-if="status" class="mb-4 text-center text-sm font-medium text-success">
                {{ status }}
            </div>

            <form method="POST" @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        Email
                    </label>
                    <input id="email" type="email" autofocus autocomplete="email" @focus="clearError" v-model="form.email" placeholder="email@example.com" class="input-crypto w-full text-sm" :disabled="throttleTimeLeft > 0" />
                    <InputError :message="throttleErrorMessage" />
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        Password
                    </label>
                    <div class="relative">
                        <input id="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" @focus="clearError" v-model="form.password" placeholder="••••••••" class="input-crypto w-full text-sm pr-10" :disabled="throttleTimeLeft > 0" />
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-accent cursor-pointer" aria-label="Toggle password visibility">
                            <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between space-x-2">
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="remember" class="h-4 w-4 shrink-0" v-model="form.remember" :disabled="throttleTimeLeft > 0" />
                        <label for="remember" class="text-sm text-muted-foreground leading-relaxed">
                            Remember Me
                        </label>
                    </div>

                    <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm text-accent hover:underline font-semibold" :tabindex="5">
                        Forgot Password?
                    </TextLink>
                </div>

                <ActionButton :processing="form.processing" :disabled="throttleTimeLeft">Login</ActionButton>
            </form>
        </template>

        <template #footer>
            <div class="text-center mt-6">
                <p class="text-muted-foreground text-sm">
                    Don't have an account?
                    <TextLink :href="route('register')" class="text-accent hover:underline font-semibold" :tabindex="5">Sign up</TextLink>
                </p>
            </div>
        </template>
    </AuthCard>
</template>
