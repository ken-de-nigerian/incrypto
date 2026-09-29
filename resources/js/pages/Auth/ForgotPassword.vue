<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { useForm, Head } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import InputError from '@/components/InputError.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import FlashMessages from '@/components/utilities/FlashMessages.vue';
    import SiteLogo from '@/components/SiteLogo.vue';
    import LiveSupport from '@/components/LiveSupport.vue';

    defineProps<{
        errors?: object;
    }>();

    const form = useForm({
        email: '',
    });

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
        if (throttleTimeLeft.value > 0) return;
        form.post(route('password.email'), {
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
    <Head title="Forgot Password" />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md mx-auto">
            <header class="mb-6 text-center">
                <SiteLogo class="inline-flex items-center gap-2 select-none" />
                <h1 class="mt-4 text-2xl font-semibold">Forgot Password</h1>
                <p class="mt-1 text-sm text-muted-foreground">Enter your email to reset your password.</p>
            </header>

            <form method="POST" @submit.prevent="submit" class="space-y-3">
                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        Email
                    </label>
                    <input id="email" type="email" autofocus autocomplete="email" @focus="clearError" v-model="form.email" placeholder="email@example.com" class="input-crypto w-full text-sm" :disabled="throttleTimeLeft > 0" />
                    <InputError :message="throttleErrorMessage" />
                </div>

                <ActionButton :processing="form.processing" :disabled="throttleTimeLeft > 0">Send Reset Link</ActionButton>
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
    <LiveSupport />
</template>
