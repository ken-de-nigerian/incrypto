<script setup lang="ts">
    import { DefineComponent } from 'vue';
    import { AlertTriangle, CheckCircle2, Copy, RotateCw } from 'lucide-vue-next';
    import { ref, computed } from 'vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import ActionButton from '@/components/ActionButton.vue';
    import { useForm } from '@inertiajs/vue3';
    import InputError from '@/components/InputError.vue';
    import "@vueup/vue-quill/dist/vue-quill.snow.css";
    import { QuillEditor } from "@vueup/vue-quill";

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
    const isLoginAsUserModalOpen = ref(false);
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

    const loginAsUserForm = useForm({
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
        confirm: '',
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
            copyFeedback.value = String(err);
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
        else if (modalName === '#loginAsUserModal') isLoginAsUserModalOpen.value = true;
        else if (modalName === '#unsuspendModal') isUnsuspendModalOpen.value = true;
        else if (modalName === '#deleteModal') isDeleteModalOpen.value = true;
    };

    const closeAllModals = () => {
        isFundsModalOpen.value = false;
        isSendEmailModalOpen.value = false;
        isUserPasswordModalOpen.value = false;
        isLoginAsUserModalOpen.value = false;
        isSuspendModalOpen.value = false;
        isUnsuspendModalOpen.value = false;
        isDeleteModalOpen.value = false;
    };

    const resetFormData = () => {
        fundsForm.reset();
        emailForm.reset();
        passwordForm.reset();
        loginAsUserForm.reset();
        suspendForm.reset();
        unsuspendForm.reset();
        deleteForm.reset();
        generatedPassword.value = '';
    };

    const manageFunds = () => {
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

    const loginAsUserDirect = () => {
        window.open(route('admin.users.login', { user: props.user.id }), '_blank');
        closeAllModals();
    };

    const manageSuspend = () => {
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
            image: wallet.image,
            id: wallet.id,
        }));
    });

    const actionTypeOptions = ref([
        { value: 'credit', label: 'Credit (Add Funds)' },
        { value: 'debit', label: 'Debit (Remove Funds)' },
    ]);

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
                        <button @click="button.modal ? openModal(button.modal) : null" :class="[
                            'flex flex-col items-center gap-1.5 sm:gap-2 p-2 sm:p-3 bg-secondary/90 rounded-lg sm:rounded-xl group w-full cursor-pointer']">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg bg-white/10 group-hover:bg-primary/10">
                                <component :is="button.icon" class="w-6 h-6" :class="button.class" />
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
                                    :src="`https://coin-images.coingecko.com${selectedOption.image}.png`"
                                    loading="lazy"
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
                                :src="`https://coin-images.coingecko.com${option.image}.png`"
                                loading="lazy"
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
                <InputError :message="fundsForm.errors.wallet_symbol" />
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
                <p class="text-xs text-muted-foreground">Provide a clear explanation for this adjustment.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton :processing="fundsForm.processing">Confirm Adjustment</ActionButton>
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
                <label for="subject" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Subject</label>
                <input id="subject" v-model="emailForm.subject" @focus="clearError(emailForm, 'subject')" type="text" placeholder="Important Account Notice" class="input-crypto w-full text-sm" />
                <InputError :message="emailForm.errors.subject" />
            </div>

            <div class="space-y-2">
                <label for="body" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Email Body</label>
                <QuillEditor v-model:content="emailForm.body" contentType="html" theme="snow" placeholder="Dear user, ..." toolbar="full" />
                <InputError :message="emailForm.errors.body" />
                <p class="text-xs text-muted-foreground">Compose a professional and clear message for the user.</p>
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
            <div class="space-y-2">
                <label for="generated-password" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Generated Password</label>
                <div class="flex gap-2">
                    <input id="generated-password" v-model="generatedPassword" type="text" placeholder="Generate a password" readonly class="input-crypto w-full text-sm" />
                    <button type="button" @click="copyToClipboard" :disabled="!generatedPassword" class="px-3 py-2 bg-secondary hover:bg-secondary/70 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center gap-2 cursor-pointer">
                        <Copy class="w-4 h-4" />
                        <span class="text-xs font-medium" v-if="!copyFeedback">Copy</span>
                        <span class="text-xs font-medium text-success" v-else>{{ copyFeedback }}</span>
                    </button>
                </div>
            </div>

            <button type="button" @click="generatePassword" class="w-full px-4 py-2 bg-secondary hover:bg-secondary/70 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm font-medium cursor-pointer">
                <RotateCw class="w-4 h-4" />
                Generate New Password
            </button>

            <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                <span class="font-semibold block mb-1 flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-destructive" /> Warning
                </span>
                <span class="text-xs block">The user will be instantly logged out and must use the new password to log back in.</span>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton type="submit" :processing="passwordForm.processing" :disabled="!generatedPassword || passwordForm.processing">
                    Confirm Reset
                </ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isLoginAsUserModalOpen"
        title="Login as User"
        subtitle="Access the account as this user to provide support or troubleshoot issues."
        @close="isLoginAsUserModalOpen = false">

        <div class="space-y-4">
            <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                <span class="font-semibold block mb-2 flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-destructive" /> Warning
                </span>
                <ul class="text-xs space-y-1 list-disc list-inside">
                    <li>You will be logged in as this user in a new session.</li>
                    <li>Any actions performed will be associated with this user account.</li>
                    <li>This action is monitored and logged for audit purposes.</li>
                    <li>Use this feature only for legitimate support or troubleshooting.</li>
                </ul>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" @click="loginAsUserDirect" class="btn-crypto w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold cursor-pointer">
                    Login as User
                </button>
            </div>
        </div>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isSuspendModalOpen"
        title="Suspend Account"
        subtitle="Temporarily disable this user's account access."
        @close="isSuspendModalOpen = false">

        <form @submit.prevent="manageSuspend" class="space-y-4">
            <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                <span class="font-semibold block mb-2 flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-destructive" /> Warning
                </span>
                <ul class="text-xs space-y-1 list-disc list-inside">
                    <li>The user will be immediately logged out.</li>
                    <li>They will be unable to access their account or conduct any transactions.</li>
                    <li>A notification email will be sent with the suspension reason.</li>
                    <li>Include a clear reason for this action.</li>
                </ul>
            </div>

            <div class="space-y-2">
                <label for="suspension-reason" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Reason for Suspension</label>
                <textarea id="suspension-reason" v-model="suspendForm.reason" @focus="clearError(suspendForm, 'reason')" rows="4" placeholder="e.g., Suspicious trading activity detected - account under review" class="input-crypto w-full text-sm"></textarea>
                <InputError :message="suspendForm.errors.reason" />
                <p class="text-xs text-muted-foreground">Be specific and professional. This message will be sent to the user in the suspension notification email.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton :processing="suspendForm.processing">Suspend Account</ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isUnsuspendModalOpen"
        title="Unsuspend Account"
        subtitle="Restore this user's account access."
        @close="isUnsuspendModalOpen = false">

        <form @submit.prevent="manageUnsuspend" class="space-y-4">
            <div class="p-3 bg-success/10 border border-success/30 rounded-lg text-sm text-success-foreground">
                <span class="font-semibold block mb-1 flex items-center gap-2">
                    <CheckCircle2 class="w-5 h-5 text-success" /> Confirmation
                </span>
                <span class="text-xs block mb-2">Once unsuspended, the user will immediately regain full access to their account.</span>
                <span class="text-xs">An email notification will be sent to {{ user.email }} confirming the restoration of their account.</span>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton type="submit" :processing="unsuspendForm.processing">Confirm Unsuspend</ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isDeleteModalOpen"
        title="Delete Account"
        subtitle="This action is irreversible. Proceed with extreme caution."
        @close="isDeleteModalOpen = false">

        <form @submit.prevent="manageDelete" class="space-y-4">
            <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                <span class="font-semibold block mb-1 flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-destructive" /> Warning
                </span>
                <span class="text-xs block">This action will permanently remove all user data and cannot be recovered.</span>
            </div>

            <div class="space-y-2">
                <label for="delete-confirmation" class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Confirmation</label>
                <input id="delete-confirmation" v-model="deleteForm.confirm" @focus="clearError(deleteForm, 'confirm')" placeholder="DELETE" class="input-crypto w-full text-sm">
                <InputError :message="deleteForm.errors.confirm" />
                <p class="text-xs text-muted-foreground">Type DELETE to confirm this irreversible action.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton :processing="deleteForm.processing">Permanently Delete</ActionButton>
            </div>
        </form>
    </QuickActionModal>
</template>

<style>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }

    .ql-editor {
        min-height: 200px !important;
    }

    /* Customize the editor appearance */
    .ql-toolbar.ql-snow {
        border-radius: 0.75rem !important;
        background-color: hsl(var(--input)) !important;
        border: 1px solid hsl(var(--border)) !important;
    }

    .ql-container.ql-snow {
        border-radius: 0.75rem !important;
        font-family: inherit !important;
        background-color: hsl(var(--input)) !important;
        border: 1px solid hsl(var(--border)) !important;
    }

    .ql-editor.ql-blank::before {
        color: hsl(var(--foreground)) !important;
    }
</style>
