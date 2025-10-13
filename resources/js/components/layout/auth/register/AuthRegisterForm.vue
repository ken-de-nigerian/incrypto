<script setup lang="ts">
    import AuthCard from '@/components/layout/auth/AuthCard.vue';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { ref, computed } from 'vue';
    import InputError from '@/components/InputError.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import { useForm } from '@inertiajs/vue3';

    defineProps<{
        provider?: string;
    }>();

    const form = useForm({
        email: '',
    });

    const showEmailForm = ref(false);
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

    const toggleEmailForm = () => {
        if (form.processing || throttleTimeLeft.value > 0) return;
        showEmailForm.value = !showEmailForm.value;
    };

    const submit = () => {
        if (throttleTimeLeft.value > 0) return;
        form.post(route('register.store'), {
            onFinish: () => {
                form.reset('email');
                if (form.errors.throttle) {
                    throttleTimeLeft.value = Number(form.errors.throttle);
                    startThrottleCountdown();
                }
            },
        });
    };

    const clearError = () => {
        if (form.errors.email) form.clearErrors('email');
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
                <TextLink :href="route('home')" aria-label="Register" class="inline-flex items-center gap-2 select-none">
                    <img class="w-[110px]" src="/assets/images/logo.png" alt="logo">
                </TextLink>
                <h1 class="mt-4 text-2xl font-semibold">Let's Get Started</h1>
                <p v-if="!showEmailForm" class="mt-1 text-sm text-muted-foreground">Sign up with social login or email.</p>
                <p v-if="showEmailForm" class="mt-1 text-sm text-muted-foreground">Enter your email to create your account.</p>
            </header>
        </template>

        <template #form>
            <div v-if="provider" class="mb-4 text-center text-sm font-medium text-destructive">
                {{ provider }}
            </div>

            <div class="space-y-4 mb-8">
                <div v-if="!showEmailForm" class="space-y-4">
                    <a :href="route('social.redirect', 'facebook')" class="btn-social-facebook w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold mb-auto cursor-pointer">
                        Continue with Facebook
                    </a>

                    <a :href="route('social.redirect', 'linkedin')" class="btn-social-apple w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold cursor-pointer">
                        Continue with Linkedin
                    </a>

                    <a :href="route('social.redirect', 'google')" class="btn-social-google w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold cursor-pointer">
                        Continue with Google
                    </a>

                    <button class="btn-crypto w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold cursor-pointer" @click="toggleEmailForm" :disabled="throttleTimeLeft > 0">
                        Sign Up with email
                    </button>
                </div>

                <div v-if="showEmailForm">
                    <form method="POST" @submit.prevent="submit" class="space-y-3">
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                Email
                            </label>
                            <input id="email" type="email" autofocus autocomplete="email" @focus="clearError" v-model="form.email" placeholder="email@example.com" class="input-crypto w-full text-sm" :disabled="throttleTimeLeft > 0" />
                            <InputError :message="throttleErrorMessage" />
                        </div>

                        <ActionButton :processing="form.processing" :disabled="throttleTimeLeft > 0">Continue</ActionButton>

                        <div class="mt-4 mb-4 flex justify-center gap-3" aria-hidden="true">
                            <span class="h-1 w-12 rounded-full bg-accent"></span>
                            <span class="h-1 w-12 rounded-full bg-accent/30"></span>
                            <span class="h-1 w-12 rounded-full bg-accent/20"></span>
                        </div>

                        <div class="text-center">
                            <button type="button" @click="toggleEmailForm" :disabled="form.processing || throttleTimeLeft > 0" class="text-muted-foreground hover:text-foreground cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': form.processing || throttleTimeLeft > 0 }">
                                Go back
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <template #footer v-if="!showEmailForm">
            <div class="text-center mt-6">
                <p class="text-muted-foreground text-sm">
                    Already have an account?
                    <TextLink :href="route('login')" class="text-accent hover:underline font-semibold" :tabindex="5">Sign in</TextLink>
                </p>
            </div>
        </template>
    </AuthCard>
</template>
