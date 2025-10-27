<script setup lang="ts">
    import AuthCard from '@/components/layout/auth/AuthCard.vue';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';
    import { useForm } from '@inertiajs/vue3';
    import { useFlash } from '@/composables/useFlash';
    import { ref, onMounted, onUnmounted, computed } from 'vue';

    const { notify } = useFlash();

    const form = useForm({
        otp: '',
    });

    const countdown = ref(0);
    const isResending = ref(false);
    let countdownInterval: number | null = null;

    // Load countdown from localStorage on the component mount
    onMounted(() => {
        loadCountdownFromStorage();
        startCountdown();
    });

    onUnmounted(() => {
        stopCountdown();
    });

    // Load countdown from localStorage
    const loadCountdownFromStorage = () => {
        const savedCountdown = localStorage.getItem('otp_resend_countdown');
        const savedTimestamp = localStorage.getItem('otp_resend_timestamp');

        if (savedCountdown && savedTimestamp) {
            const elapsed = Math.floor((Date.now() - parseInt(savedTimestamp)) / 1000);
            const remaining = parseInt(savedCountdown) - elapsed;

            if (remaining > 0) {
                countdown.value = remaining;
            } else {
                clearCountdownStorage();
            }
        }
    };

    // Save countdown to localStorage
    const saveCountdownToStorage = (seconds: number) => {
        localStorage.setItem('otp_resend_countdown', seconds.toString());
        localStorage.setItem('otp_resend_timestamp', Date.now().toString());
    };

    // Clear countdown from localStorage
    const clearCountdownStorage = () => {
        localStorage.removeItem('otp_resend_countdown');
        localStorage.removeItem('otp_resend_timestamp');
    };

    // Start a countdown timer
    const startCountdown = () => {
        stopCountdown(); // Clear any existing interval

        if (countdown.value > 0) {
            countdownInterval = setInterval(() => {
                countdown.value--;

                if (countdown.value <= 0) {
                    stopCountdown();
                    clearCountdownStorage();
                } else {
                    saveCountdownToStorage(countdown.value);
                }
            }, 1000);
        }
    };

    // Stop countdown timer
    const stopCountdown = () => {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    };

    // Format countdown to MM:SS
    const formattedCountdown = computed(() => {
        const minutes = Math.floor(countdown.value / 60);
        const seconds = countdown.value % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    });

    // Check if resend is available
    const canResend = computed(() => countdown.value <= 0 && !isResending.value);

    const submit = () => {
        form.post(route('verify.store'), {
            onFinish: () => {
                form.reset('otp');
            },
        });
    };

    const clearError = () => {
        if (form.errors.otp) form.clearErrors('otp');
    };

    // Handle resend OTP
    const resendOtp = async () => {
        if (!canResend.value) return;

        isResending.value = true;

        try {
            const response = await fetch(route('verify.resend'), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                // Start countdown based on server response
                if (data.retryTime) {
                    const retryTime = new Date(data.retryTime).getTime();
                    const now = Date.now();
                    countdown.value = Math.max(0, Math.floor((retryTime - now) / 1000));
                } else {
                    // Default to 2 minutes if no retryTime provided
                    countdown.value = 2 * 60;
                }

                saveCountdownToStorage(countdown.value);
                startCountdown();
                notify('success', data.message, {
                    duration: 5000,
                    title: data.title || 'Success'
                });
            } else {
                notify('error', 'Failed to resend OTP. Please try again.', {
                    duration: 5000,
                    title: 'Error'
                });
            }
        } catch (error) {
            notify('error', error, { duration: 5000 });
        } finally {
            isResending.value = false;
        }
    };
</script>

<template>
    <AuthCard>
        <template #header>
            <header class="mb-6 text-center">
                <TextLink :href="route('home')" aria-label="Verify" class="inline-flex items-center gap-2 select-none">
                    <img class="w-[110px]" src="/assets/images/logo.png" loading="lazy" alt="logo">
                </TextLink>
                <h1 class="mt-4 text-2xl font-semibold">Verify Email</h1>
                <p class="mt-1 text-sm text-muted-foreground">Enter the 6-digit code sent to your email.</p>
            </header>
        </template>

        <template #form>
            <form method="POST" @submit.prevent="submit" class="space-y-3">
                <div class="space-y-2">
                    <label for="otp" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        Code
                    </label>
                    <input id="otp" type="text" autofocus autocomplete="otp" @focus="clearError" v-model="form.otp" placeholder="••••••••" class="input-crypto w-full text-sm" maxlength="6" inputmode="text" pattern="[A-Z0-9]*" />
                    <InputError :message="form.errors.otp" />
                </div>

                <ActionButton :processing="form.processing">Submit</ActionButton>

                <div class="mt-4 mb-4 flex justify-center gap-3" aria-hidden="true">
                    <span class="h-1 w-12 rounded-full bg-accent"></span>
                    <span class="h-1 w-12 rounded-full bg-accent"></span>
                    <span class="h-1 w-12 rounded-full bg-accent/30"></span>
                </div>
            </form>
        </template>

        <template #footer>
            <div class="text-center mt-6">
                <p class="text-muted-foreground text-sm">
                    Don't receive your code?
                    <span v-if="isResending" class="ml-1">Sending...</span>
                    <button v-else-if="canResend" @click="resendOtp" class="text-accent hover:underline font-semibold cursor-pointer ml-1" :disabled="!canResend">
                        Resend
                    </button>
                    <span v-else class="text-muted-foreground ml-1">
                        Resend in {{ formattedCountdown }}
                    </span>
                </p>

                <p class="text-center text-sm mt-4">
                    <TextLink :href="route('register')" class="mx-auto block text-sm text-muted-foreground hover:underline">
                        Back to Register
                    </TextLink>
                </p>
            </div>
        </template>
    </AuthCard>
</template>
