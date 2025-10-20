<script setup lang="ts">
    import { DefineComponent } from 'vue';
    import { AlertTriangle, CheckCircle2, Copy, RotateCw } from 'lucide-vue-next';
    import { ref, computed } from 'vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import { useForm } from '@inertiajs/vue3';
    import InputError from '@/components/InputError.vue';

    interface ActionButton {
        label: string;
        icon: DefineComponent | any;
        modal?: string;
        form?: string;
        class: string;
    }

    interface ActionGroup {
        title?: string;
        buttons: ActionButton[];
    }

    interface WalletItem {
        key: string;
        id: string;
        name: string;
        symbol: string;
        network: string | null;
        balance: number;
        image: string;
        status: string;
        is_visible: boolean;
        is_updating: boolean;
        usd_value: number;
        profit_loss: number;
        price_change_percentage: number;
        is_profit: boolean;
    }

    const props = defineProps<{
        actionGroups: ActionGroup[];
        user: {
            id: string | number;
            first_name: string;
            last_name: string;
            email: string;
            phone_number: string | null;
            status: 'active' | 'suspended';
            created_at: string;
            profile: {
                profile_photo_path: string | null;
                referral_code: string;
                country: string;
            };
        };
        wallet_balances?: {
            wallets: Array<Omit<WalletItem, 'key' | 'id' | 'network' | 'is_visible' | 'is_updating'>>;
            totalUsdValue: number | string;
        };
    }>();

    const isFundsModalOpen = ref(false);
    const isSendEmailModalOpen = ref(false);
    const isUserPasswordModalOpen = ref(false);
    const isSuspendModalOpen = ref(false);
    const isUnsuspendModalOpen = ref(false);
    const isDeleteModalOpen = ref(false);
    const copyFeedback = ref('');

    const fundsForm = useForm({
        amount: '',
        actionType: 'credit' as string | null,
        reason: '',
        wallet_id: null as string | null,
        wallet_symbol: '',
    });

    const emailForm = useForm({
        subject: '',
        body: '',
        user_id: props.user.id,
    });

    const passwordForm = useForm({
        password: '',
        user_id: props.user.id,
    });

    const suspendForm = useForm({
        reason: '',
        user_id: props.user.id,
    });

    const unsuspendForm = useForm({
        user_id: props.user.id,
    });

    const deleteForm = useForm({
        confirmation: '',
        user_id: props.user.id,
    });

    const generatedPassword = ref('');

    const generatePassword = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 16; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        generatedPassword.value = password;
        passwordForm.password = password;
    };

    const copyToClipboard = async () => {
        try {
            await navigator.clipboard.writeText(generatedPassword.value);
            copyFeedback.value = 'Copied!';
            setTimeout(() => {
                copyFeedback.value = '';
            }, 2000);
        } catch (err) {
            copyFeedback.value = err;
        }
    };

    const openModal = (modalName: string) => {
        if (modalName === '#fundsModal') isFundsModalOpen.value = true;
        else if (modalName === '#sendEmailModal') isSendEmailModalOpen.value = true;
        else if (modalName === '#userPasswordModal') {
            generatedPassword.value = '';
            passwordForm.password = '';
            isUserPasswordModalOpen.value = true;
        }
        else if (modalName === '#suspendModal') isSuspendModalOpen.value = true;
        else if (modalName === '#unsuspendModal') isUnsuspendModalOpen.value = true;
        else if (modalName === '#deleteModal') isDeleteModalOpen.value = true;
    };

    const closeAllModals = () => {
        isFundsModalOpen.value = false;
        isSendEmailModalOpen.value = false;
        isUserPasswordModalOpen.value = false;
        isSuspendModalOpen.value = false;
        isUnsuspendModalOpen.value = false;
        isDeleteModalOpen.value = false;
    };

    const resetFormData = () => {
        fundsForm.reset();
        emailForm.reset();
        passwordForm.reset();
        suspendForm.reset();
        unsuspendForm.reset();
        deleteForm.reset();
        generatedPassword.value = '';
    };

    const manageFunds = () => {
        const isWalletSelected = fundsForm.wallet_id !== null && String(fundsForm.wallet_id).trim() !== '';

        if (!isWalletSelected) {
            fundsForm.errors.wallet_id = 'Please select a wallet.';
            return;
        }

        const selectedWallet = walletOptions.value.find(w => w.value === fundsForm.wallet_id);
        if (selectedWallet) {
            fundsForm.wallet_symbol = selectedWallet.symbol;
        }

        fundsForm.post(route('admin.users.adjust.balance', { user: props.user.id }), {
            preserveScroll: true,
            onSuccess: () => {
                closeAllModals();
                resetFormData();
            },
        });
    };

    const sendEmail = () => {
        emailForm.post(route('admin.users.send.email', { user: props.user.id }), {
            preserveScroll: true,
            onSuccess: () => {
                closeAllModals();
                resetFormData();
            },
        });
    };

    const resetPassword = () => {
        passwordForm.put(route('admin.users.reset.password', { user: props.user.id }), {
            preserveScroll: true,
            onSuccess: () => {
                closeAllModals();
                resetFormData();
            },
        });
    };

    const manageSuspend = () => {
        if (!suspendForm.reason.trim()) {
            suspendForm.errors.reason = 'A reason for suspension is required.';
            return;
        }

        suspendForm.put(route('admin.users.suspend', { user: props.user.id }), {
            preserveScroll: true,
            onSuccess: () => {
                closeAllModals();
                resetFormData();
            },
        });
    };

    const manageUnsuspend = () => {
        unsuspendForm.put(route('admin.users.unsuspend', { user: props.user.id }), {
            preserveScroll: true,
            onSuccess: () => {
                closeAllModals();
                resetFormData();
            },
        });
    };

    const manageDelete = () => {
        if (deleteForm.confirmation !== 'DELETE') {
            deleteForm.errors.confirmation = 'You must type "DELETE" to confirm.';
            return;
        }

        deleteForm.delete(route('admin.users.destroy', { user: props.user.id }), {
            preserveScroll: true,
            onSuccess: () => {
                closeAllModals();
                resetFormData();
            },
        });
    };

    const walletOptions = computed(() => {
        if (!props.wallet_balances || !props.wallet_balances.wallets.length) {
            return [];
        }

        return props.wallet_balances.wallets.map((wallet, index) => ({
            value: String(wallet.id || index),
            label: `${wallet.name}`,
            balance: wallet.balance.toFixed(4),
            usd_value: wallet.usd_value.toFixed(2),
            symbol: wallet.symbol,
            id: wallet.id,
        }));
    });

    const actionTypeOptions = ref([
        { value: 'credit', label: 'Credit (Add Funds)' },
        { value: 'debit', label: 'Debit (Remove Funds)' },
    ]);

    const getIconSymbol = (symbol: string) => {
        const lowerSymbol = symbol.toLowerCase();
        if (lowerSymbol.includes('usdt')) {
            return 'usdt';
        }
        return lowerSymbol;
    };

    const clearError = (form: any, field: string) => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };
</script>

<template>
    <div class="card-crypto border rounded-xl p-0 overflow-hidden margin-bottom">
        <div class="flex flex-col flex-1">
            <div class="px-4 pt-4 pb-3 border-b border-border">
                <h3 class="text-xs font-bold text-card-foreground uppercase tracking-wider mb-2 px-1">Quick Actions</h3>
                <p class="text-xs text-muted-foreground mb-1 px-1">Perform administrative tasks.</p>
            </div>

            <div v-for="group in actionGroups" :key="group.title ?? 'default'" class="py-3">
                <div class="grid grid-cols-3 xs:grid-cols-3 gap-4 px-4">
                    <template v-for="button in group.buttons" :key="button.label">
                        <form v-if="button.form" method="POST" class="w-full">
                            <button type="submit" :class="[
                                'flex flex-col items-center gap-1.5 sm:gap-2 p-2 sm:p-3 bg-secondary rounded-lg sm:rounded-xl group w-full cursor-pointer']">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg bg-white/10 group-hover:bg-primary/10">
                                    <component :is="button.icon" class="w-4 h-4" :class="button.class" />
                                </div>
                                <span class="text-card-foreground text-[10px] text-center leading-tight font-medium">{{ button.label }}</span>
                            </button>
                        </form>

                        <button v-else @click="button.modal ? openModal(button.modal) : null" :class="[
                            'flex flex-col items-center gap-1.5 sm:gap-2 p-2 sm:p-3 bg-secondary rounded-lg sm:rounded-xl group w-full cursor-pointer']">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg bg-white/10 group-hover:bg-primary/10">
                                <component :is="button.icon" class="w-4 h-4" :class="button.class" />
                            </div>
                            <span class="text-card-foreground text-[10px] text-center leading-tight font-medium">{{ button.label }}</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <QuickActionModal
        :is-open="isFundsModalOpen"
        title="Adjust User Balance"
        subtitle="Debit or credit funds to the user's account."
        @close="isFundsModalOpen = false">

        <form @submit.prevent="manageFunds" class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Select Wallet</label>
                <CustomSelectDropdown
                    v-model="fundsForm.wallet_id"
                    @user-interacted="clearError(fundsForm, 'wallet_id')"
                    :options="walletOptions"
                    placeholder="Choose Wallet to Adjust">
                    <template #default="{ selectedOption }">
                        <template v-if="selectedOption">
                            <div class="flex items-center gap-3">
                                <img
                                    :src="`https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/${getIconSymbol(selectedOption.symbol)}.png`"
                                    :alt="selectedOption.symbol"
                                    class="h-8 w-8 object-cover"
                                    @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                                />
                                <div class="text-left">
                                    <div class="font-medium text-card-foreground">{{ selectedOption.label }}</div>
                                    <div class="text-xs text-muted-foreground">{{ selectedOption.symbol }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-card-foreground">{{ selectedOption.balance }}</div>
                                <div class="text-xs text-muted-foreground">${{ selectedOption.usd_value }}</div>
                            </div>
                        </template>
                        <template v-else>
                            <span class="text-muted-foreground">Choose Wallet to Adjust</span>
                        </template>
                    </template>

                    <template #option="{ option }">
                        <div class="flex items-center gap-3">
                            <img
                                :src="`https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/${getIconSymbol(option.symbol)}.png`"
                                :alt="option.symbol"
                                class="h-8 w-8 object-cover"
                                @error="(e) => (e.target as HTMLImageElement).src = 'https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1a63530be6e374711a8554f31b17e4cb92c25fa5/128/color/generic.png'"
                            />
                            <div class="text-left">
                                <div class="font-medium text-card-foreground">{{ option.label }}</div>
                                <div class="text-xs text-muted-foreground">{{ option.symbol }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-card-foreground">{{ option.balance }}</div>
                            <div class="text-xs text-muted-foreground">${{ option.usd_value }}</div>
                        </div>
                    </template>
                </CustomSelectDropdown>
                <InputError :message="fundsForm.errors.wallet_id" />
                <p v-if="walletOptions.length === 0" class="text-sm text-warning p-2 rounded-lg bg-warning/10 border border-warning/30">
                    No wallets found for this user.
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Action Type</label>
                <CustomSelectDropdown
                    v-model="fundsForm.actionType"
                    @user-interacted="clearError(fundsForm, 'actionType')"
                    :options="actionTypeOptions"
                    placeholder="Select Action Type"
                />
                <InputError :message="fundsForm.errors.actionType" />
            </div>

            <div class="space-y-2">
                <label for="amount" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Amount</label>
                <input id="amount" v-model="fundsForm.amount" @focus="clearError(fundsForm, 'amount')" type="text" placeholder="0.0001" class="input-crypto w-full text-sm" />
                <InputError :message="fundsForm.errors.amount" />
            </div>

            <div class="space-y-2">
                <label for="reason" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Reason/Note</label>
                <textarea id="reason" v-model="fundsForm.reason" @focus="clearError(fundsForm, 'reason')" rows="3" placeholder="Admin adjustment for bonus" class="input-crypto w-full text-sm"></textarea>
                <InputError :message="fundsForm.errors.reason" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton :processing="fundsForm.processing">Confirm</ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isSendEmailModalOpen"
        title="Send Custom Email"
        :subtitle="`Send an email to ${user.email}'s registered address.`"
        @close="isSendEmailModalOpen = false">

        <form @submit.prevent="sendEmail" class="space-y-4">
            <div class="space-y-2">
                <label for="email-subject" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Subject</label>
                <input id="email-subject" v-model="emailForm.subject" @focus="clearError(emailForm, 'subject')" type="text" placeholder="Important Account Notice" class="input-crypto w-full text-sm" />
                <InputError :message="emailForm.errors.subject" />
            </div>

            <div class="space-y-2">
                <label for="email-body" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Email Body</label>
                <textarea id="email-body" v-model="emailForm.body" @focus="clearError(emailForm, 'body')" rows="5" placeholder="Dear user, ..." class="input-crypto w-full text-sm"></textarea>
                <InputError :message="emailForm.errors.body" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton :processing="emailForm.processing">Send Email</ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isUserPasswordModalOpen"
        title="Reset User Password"
        subtitle="Generate and set a new temporary password for the user."
        @close="isUserPasswordModalOpen = false">

        <form @submit.prevent="resetPassword" class="space-y-4">
            <p class="text-sm text-muted-foreground leading-relaxed">
                Generate a new password for user <span class="font-semibold text-card-foreground">{{ user.first_name }}</span>. They will use this to log back in.
            </p>

            <div class="space-y-2">
                <label for="generated-password" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Generated Password</label>
                <div class="flex gap-2">
                    <input id="generated-password" v-model="generatedPassword" type="text" placeholder="Generate a password" readonly class="input-crypto w-full text-sm" />
                    <button type="button" @click="copyToClipboard" :disabled="!generatedPassword" class="px-3 py-2 bg-secondary hover:bg-secondary/70 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center gap-2">
                        <Copy class="w-4 h-4" />
                        <span class="text-xs font-medium" v-if="!copyFeedback">Copy</span>
                        <span class="text-xs font-medium text-success" v-else>{{ copyFeedback }}</span>
                    </button>
                </div>
            </div>

            <button type="button" @click="generatePassword" class="w-full px-4 py-2 bg-secondary hover:bg-secondary/70 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm font-medium">
                <RotateCw class="w-4 h-4" />
                Generate New Password
            </button>

            <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                <span class="font-semibold block mb-1 flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-destructive" /> Warning
                </span>
                The user will be instantly logged out and must use the new password to log back in.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton type="submit" :processing="passwordForm.processing" :disabled="!generatedPassword || passwordForm.processing">
                    Confirm Reset
                </ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isSuspendModalOpen"
        title="Suspend Account"
        subtitle="Temporarily disable this user's account access."
        @close="isSuspendModalOpen = false">

        <form @submit.prevent="manageSuspend" class="space-y-4">
            <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                <span class="font-semibold block mb-1 flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-destructive" /> Warning
                </span>
                Enter a reason for suspension. The user will be immediately logged out.
            </div>

            <div class="space-y-2">
                <label for="suspension-reason" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Reason for Suspension</label>
                <textarea id="suspension-reason" v-model="suspendForm.reason" @focus="clearError(suspendForm, 'reason')" rows="3" placeholder="Provide a reason for suspension..." class="input-crypto w-full text-sm"></textarea>
                <InputError :message="suspendForm.errors.reason" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton :processing="suspendForm.processing">Suspend</ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isUnsuspendModalOpen"
        title="Unsuspend Account"
        subtitle="Restore this user's account access."
        @close="isUnsuspendModalOpen = false">

        <form @submit.prevent="manageUnsuspend" class="space-y-4">
            <p class="text-sm text-muted-foreground leading-relaxed">
                Are you sure you want to
                <span class="font-semibold text-card-foreground">unsuspend</span> the account for user
                <span class="font-semibold text-card-foreground">{{ user.first_name }}</span>?
            </p>

            <div class="p-3 bg-success/10 border border-success/30 rounded-lg text-sm text-success-foreground">
                <span class="font-semibold block mb-1 flex items-center gap-2">
                    <CheckCircle2 class="w-5 h-5 text-success" /> Note
                </span>
                The user will regain full access upon completion of this action.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton type="submit" :processing="unsuspendForm.processing">Unsuspend</ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isDeleteModalOpen"
        title="Delete Account"
        subtitle="This action is irreversible. Proceed with extreme caution."
        @close="isDeleteModalOpen = false">

        <form @submit.prevent="manageDelete" class="space-y-4">
            <p class="text-sm text-muted-foreground leading-relaxed">
                To confirm permanent deletion of account
                <span class="font-semibold text-card-foreground">{{ user.first_name }}</span>, please type
                <span class="bg-destructive/10 text-destructive font-mono text-xs px-2 py-1 rounded">DELETE</span> below.
            </p>

            <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                <span class="font-semibold block mb-1 flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-destructive" /> Warning
                </span>
                This action will permanently remove all user data and cannot be recovered.
            </div>

            <div class="space-y-2">
                <label for="delete-confirmation" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Confirmation</label>
                <input id="delete-confirmation" v-model="deleteForm.confirmation" @focus="clearError(deleteForm, 'confirmation')" placeholder="DELETE" class="input-crypto w-full text-sm">
                <InputError :message="deleteForm.errors.confirmation" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton :processing="deleteForm.processing">Permanently Delete</ActionButton>
            </div>
        </form>
    </QuickActionModal>
</template>

<style scoped>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
