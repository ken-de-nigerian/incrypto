<script setup lang="ts">
    import { useForm } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import InputError from '@/components/InputError.vue';
    import ActionButton from '@/components/ActionButton.vue';

    const props = defineProps<{
        userProfile: {
            id: string | number,
            profile: {
                network_fee: number | null,
                charge_network_fee: boolean,
            } | null,
        },
    }>();

    const form = useForm({
        network_fee: props.userProfile.profile?.network_fee || 0,
        charge_network_fee: !!props.userProfile.profile?.charge_network_fee,
    });

    const clearError = (field: keyof typeof form.errors) => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };

    const submit = () => {
        form.post(route('admin.users.network.fee', props.userProfile.id), {
            preserveScroll: true,
        });
    };
</script>

<template>
    <div class="space-y-6 margin-bottom">
        <!-- Network Fee Settings Section -->
        <form @submit.prevent="submit" class="rounded-lg border bg-card text-card-foreground">
            <div class="flex flex-col space-y-1.5 p-6">
                <div class="text-2xl font-semibold">Network Fee Settings</div>
                <p class="text-sm text-muted-foreground">Configure network fee requirements for this user's transactions.</p>
            </div>

            <div class="p-6 pt-0 space-y-6">
                <!-- Charge Network Fee Toggle -->
                <div class="flex items-start justify-between p-4 rounded-lg border border-border bg-muted/30">
                    <div class="flex-1 space-y-1">
                        <label for="charge_network_fee" class="text-sm font-semibold text-card-foreground cursor-pointer">
                            Require Sufficient Balance for Network Fees
                        </label>
                        <p class="text-xs text-muted-foreground">
                            When enabled, user must have sufficient balance to cover network fees before they can send or swap cryptocurrency.
                        </p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="charge_network_fee" v-model="form.charge_network_fee" @change="clearError('charge_network_fee')" class="sr-only peer" />
                            <div class="w-11 h-6 bg-muted rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
                <InputError :message="form.errors.charge_network_fee" />

                <!-- Network Fee Amount -->
                <div class="space-y-2">
                    <label for="network_fee" class="text-sm font-medium flex items-center gap-2">
                        Network Fee Amount
                    </label>
                    <div class="relative">
                        <input id="network_fee" v-model="form.network_fee" @focus="clearError('network_fee')" type="number" step="0.000001" min="0" placeholder="0.001" class="input-crypto w-full pr-16" />
                    </div>
                    <InputError :message="form.errors.network_fee" />
                </div>

                <!-- Warning Message -->
                <div v-if="form.charge_network_fee" class="p-4 bg-warning/10 border border-warning/30 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle text-warning flex-shrink-0 mt-0.5">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                            <path d="M12 9v4"/>
                            <path d="M12 17h.01"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-warning">Important Notice</p>
                            <p class="text-xs text-warning/90 mt-1">
                                When this setting is enabled, the user will be blocked from sending or swapping cryptocurrency until they have sufficient balance.
                                They will see a clear message explaining they need to add more coin to proceed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center p-6 pt-0 gap-4">
                <ActionButton :processing="form.processing">Save Network Fee Settings</ActionButton>
            </div>
        </form>
    </div>
</template>

<style>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }

    button:focus-visible,
    input:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }

    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }

    /* Toggle Switch Styling */
    .peer:checked ~ div {
        background-color: hsl(var(--primary));
    }
</style>
