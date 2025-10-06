<script setup lang="ts">
    import { defineProps, ref } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';

    defineProps({
        activeSessions: Array as () => any[],
    });

    const showCurrentPassword = ref(false);
    const showNewPassword = ref(false);
    const showConfirmPassword = ref(false);

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword = () => {
        passwordForm.put(route('user.profile.reset.password'), {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const clearError = (field: keyof typeof passwordForm.errors) => {
        if (passwordForm.errors[field]) {
            passwordForm.clearErrors(field);
        }
    };

    const isLogoutModalOpen = ref(false);
    const passwordInput = ref<HTMLInputElement | null>(null);

    const logoutForm = useForm({
        password: '',
    });

    const openLogoutModal = () => {
        isLogoutModalOpen.value = true;
        setTimeout(() => passwordInput.value?.focus(), 250);
    };

    const closeLogoutModal = () => {
        isLogoutModalOpen.value = false;
        logoutForm.reset();
    };

    const logoutOtherDevices = () => {
        logoutForm.post(route('logout.other-devices'), {
            preserveScroll: true,
            onSuccess: () => closeLogoutModal(),
            onError: () => passwordInput.value?.focus(),
            onFinish: () => logoutForm.reset('password'),
        });
    };
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col space-y-1.5 p-6">
                <div class="text-2xl font-semibold leading-none tracking-tight">Password</div>
                <div class="text-sm text-muted-foreground mt-2">Change your account password</div>
            </div>

            <div class="p-6 pt-0">
                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div class="space-y-2">
                        <label for="currentPassword" class="text-sm font-medium">Current Password</label>
                        <div class="relative">
                            <input @focus="clearError('current_password')" v-model="passwordForm.current_password" id="currentPassword" :type="showCurrentPassword ? 'text' : 'password'" placeholder="Enter your current password" class="input-crypto w-full" />
                            <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-accent cursor-pointer" aria-label="Toggle current password visibility">
                                <svg v-if="showCurrentPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>

                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <InputError :message="passwordForm.errors.current_password" />
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-sm font-medium">New Password</label>
                        <div class="relative">
                            <input @focus="clearError('password')" v-model="passwordForm.password" id="password" :type="showNewPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="••••••••" class="input-crypto w-full text-sm pr-10" />
                            <button type="button" @click="showNewPassword = !showNewPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-accent cursor-pointer" aria-label="Toggle new password visibility">
                                <svg v-if="showNewPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <InputError :message="passwordForm.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <label for="password_confirmation" class="text-sm font-medium">Confirm Password</label>
                        <div class="relative">
                            <input @focus="clearError('password_confirmation')" v-model="passwordForm.password_confirmation" id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="••••••••" class="input-crypto w-full text-sm pr-10" />
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-accent cursor-pointer" aria-label="Toggle password confirmation visibility">
                                <svg v-if="showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <InputError :message="passwordForm.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <ActionButton :processing="passwordForm.processing">Change Password</ActionButton>
                    </div>
                </form>
            </div>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col space-y-1.5 p-6">
                <div class="text-2xl font-semibold leading-none tracking-tight">Active Sessions</div>
                <div class="text-sm text-muted-foreground mt-2">Monitor and manage your active login sessions</div>
            </div>

            <div class="space-y-4 p-6 pt-0">
                <div class="space-y-4">
                    <div class="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="font-medium">Browser Sessions</h3>
                            <p class="text-sm text-muted-foreground">
                                If you notice any suspicious activity, you can log out all of your other browser sessions.
                            </p>
                        </div>

                        <button v-if="activeSessions && activeSessions.length > 1" @click="openLogoutModal" class="inline-flex h-10 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md border border-input bg-background px-4 py-2 text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 md:w-auto cursor-pointer" type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out mr-2 h-4 w-4" aria-hidden="true">
                                <path d="m16 17 5-5-5-5"></path>
                                <path d="M21 12H9"></path>
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            </svg>
                            Log Out Other Devices
                        </button>
                    </div>

                    <div v-if="activeSessions && activeSessions.length" class="space-y-3">
                        <div v-for="session in activeSessions" :key="session.id" class="flex flex-col items-start gap-4 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center space-x-2">
                                    <svg v-if="['Windows', 'macOS', 'Linux'].includes(session.platform)" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-muted-foreground">
                                        <path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55A1 1 0 0 1 20.7 20H3.3a1 1 0 0 1-.58-1.45L4 16"/>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-smartphone h-5 w-5 text-muted-foreground" aria-hidden="true">
                                        <rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect>
                                        <path d="M12 18h.01"></path>
                                    </svg>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium">{{ session.device }}</span>
                                        <span v-if="session.is_current" class="text-sm text-green-500">(Current)</span>
                                    </div>
                                    <div class="space-y-1 text-sm text-muted-foreground">
                                        <div>{{ session.browser }} • {{ session.platform }}</div>
                                        <div>{{ session.ip_address }}</div>
                                        <div>Last active: {{ session.last_activity }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        No active sessions found.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="isLogoutModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click="closeLogoutModal">
        <div class="w-full max-w-md p-6 bg-card rounded-lg shadow-xl" @click.stop>
            <h2 class="text-lg font-medium text-foreground">Log Out Other Browser Sessions</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.
            </p>

            <form @submit.prevent="logoutOtherDevices" class="mt-6">
                <div class="space-y-2">
                    <label for="logout-password" class="text-sm font-medium">Password</label>
                    <input ref="passwordInput" v-model="logoutForm.password" id="logout-password" type="password" placeholder="••••••••" class="input-crypto w-full" />
                    <InputError :message="logoutForm.errors.password" />
                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <button type="button" @click="closeLogoutModal" class="btn-social-apple w-50 h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold cursor-pointer">Cancel</button>
                    <ActionButton :processing="logoutForm.processing">Log Out Other Devices</ActionButton>
                </div>
            </form>
        </div>
    </div>
</template>
