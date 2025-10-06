<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ActionButton from '@/components/ActionButton.vue';
import InputError from '@/components/InputError.vue';

const form = useForm({
    confirm: ''
});

// Function to handle deletion
const submit = () => {
    form.delete(route('user.profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            accountForm.reset();
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
    <div class="space-y-6">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col space-y-1.5 p-6">
                <div class="text-2xl font-semibold leading-none tracking-tight flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database h-5 w-5 mt-0.5 flex-shrink-0" aria-hidden="true">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M3 5V19A9 3 0 0 0 21 19V5"></path>
                        <path d="M3 12A9 3 0 0 0 21 12"></path>
                    </svg>
                    <span>Account Deletion</span>
                </div>
                <div class="text-sm text-muted-foreground mt-2">
                    This action will schedule your account for permanent deletion.
                </div>
            </div>
            <div class="p-6 pt-0 space-y-4">
                <div role="alert" class="relative w-full rounded-lg border p-4 [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:text-foreground bg-background text-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert h-4 w-4" aria-hidden="true">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                    </svg>

                    <div class="text-sm [&_p]:leading-relaxed">
                        You have a 30-day grace period to restore your account by logging back in. After this period, your account and all its data will be permanently and irreversibly removed.
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <div class="space-y-2">
                        <h4 class="font-medium">Final Confirmation</h4>
                        <p class="text-sm text-muted-foreground">To proceed, please type DELETE below. This action cannot be undone after 30 days.</p>
                        <form @submit.prevent="submit" class="space-y-2">
                            <input v-model="form.confirm" @focus="clearError('confirm')" type="text" placeholder="Type DELETE to confirm" class="input-crypto w-full" />
                            <InputError :message="form.errors.confirm" />
                            <ActionButton :processing="form.processing" class="bg-destructive text-destructive-foreground hover:opacity-90">Permanently Delete Account</ActionButton>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
