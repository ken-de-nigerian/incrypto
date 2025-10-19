<script setup lang="ts">
    import { DefineComponent } from 'vue';
    import { Mail, Lock, UserCheck, UserX, Trash2 } from 'lucide-vue-next';
    import { ref, computed } from 'vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import ActionButton from '@/components/ActionButton.vue';

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
    const isReactivateModalOpen = ref(false);
    const isDeleteModalOpen = ref(false);

    const formData = ref({
        funds: { amount: '', actionType: 'credit', reason: '', wallet_id: '' },
        email: { subject: '', body: '' },
        delete: { confirmation: '' }
    });

    const openModal = (modalName: string) => {
        if (modalName === '#fundsModal') isFundsModalOpen.value = true;
        else if (modalName === '#sendEmailModal') isSendEmailModalOpen.value = true;
        else if (modalName === '#userPasswordModal') isUserPasswordModalOpen.value = true;
        else if (modalName === '#suspendModal') isSuspendModalOpen.value = true;
        else if (modalName === '#unsuspendModal') isUnsuspendModalOpen.value = true;
        else if (modalName === '#reactivateModal') isReactivateModalOpen.value = true;
        else if (modalName === '#deleteModal') isDeleteModalOpen.value = true;
    };

    const closeAllModals = () => {
        isFundsModalOpen.value = false;
        isSendEmailModalOpen.value = false;
        isUserPasswordModalOpen.value = false;
        isSuspendModalOpen.value = false;
        isUnsuspendModalOpen.value = false;
        isReactivateModalOpen.value = false;
        isDeleteModalOpen.value = false;
    };

    const handleAction = (action: string) => {
        console.log(`${action} for user ${props.user.id}`);
        console.log('Form Data:', formData.value);
        closeAllModals();
        resetFormData();
    };

    const resetFormData = () => {
        formData.value = {
            funds: { amount: '', actionType: 'credit', reason: '', wallet_id: '' },
            email: { subject: '', body: '' },
            delete: { confirmation: '' }
        };
    };

    // Computed property for wallet options to use in CustomSelectDropdown
    const walletOptions = computed(() => {
        if (!props.wallet_balances || !props.wallet_balances.wallets.length) {
            return [];
        }

        return props.wallet_balances.wallets.map(wallet => ({
            value: `${wallet.symbol}-${wallet.name.toLowerCase().replace(/\s/g, '-')}`,
            label: `${wallet.name}`, // Main label
            balance: wallet.balance.toFixed(4),
            usd_value: wallet.usd_value.toFixed(2),
            symbol: wallet.symbol
        }));
    });

    // Options for the Action Type dropdown
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

        <form @submit.prevent="handleAction('Adjusted Balance')" class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Select Wallet</label>
                <CustomSelectDropdown
                    v-model="formData.funds.wallet_id"
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
                <p v-if="walletOptions.length === 0" class="text-sm text-warning p-2 rounded-lg bg-warning/10 border border-warning/30">
                    No wallets found for this user.
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Action Type</label>
                <CustomSelectDropdown
                    v-model="formData.funds.actionType"
                    :options="actionTypeOptions"
                    placeholder="Select Action Type"
                />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Amount</label>
                <input v-model="formData.funds.amount" type="text" placeholder="0.0001" class="input-crypto w-full text-sm" />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Reason/Note</label>
                <textarea v-model="formData.funds.reason" rows="3" placeholder="Admin adjustment for bonus" class="input-crypto w-full text-sm"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <ActionButton type="submit">
                    Confirm
                </ActionButton>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isSendEmailModalOpen"
        title="Send Custom Email"
        :subtitle="`Send an email to ${user.email}'s registered address.`"
        @close="isSendEmailModalOpen = false">

        <form @submit.prevent="handleAction('Sent Email')" class="space-y-4">
            <div class="space-y-2">
                <label class="text-xs font-semibold text-card-foreground block">Subject</label>
                <input
                    v-model="formData.email.subject"
                    type="text"
                    required
                    placeholder="Important Account Notice"
                    class="w-full px-3 py-2.5 text-sm border border-border rounded-lg bg-input placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition-colors" />
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold text-card-foreground block">Email Body</label>
                <textarea
                    v-model="formData.email.body"
                    required
                    rows="5"
                    placeholder="Dear user, ..."
                    class="w-full px-3 py-2.5 text-sm border border-border rounded-lg bg-input placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition-colors resize-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-border">
                <button
                    type="button"
                    @click="isSendEmailModalOpen = false"
                    class="px-4 py-2 text-sm font-medium text-muted-foreground bg-secondary/50 hover:bg-secondary/70 rounded-lg transition-colors">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-accent hover:bg-accent/90 rounded-lg transition-colors flex items-center gap-2">
                    <Mail class="w-4 h-4" />
                    Send Email
                </button>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isUserPasswordModalOpen"
        title="Reset User Password"
        subtitle="This will send the user a new, temporary password."
        @close="isUserPasswordModalOpen = false">

        <form @submit.prevent="handleAction('Reset Password')" class="space-y-4">
            <p class="text-sm text-muted-foreground leading-relaxed">
                Are you sure you want to reset the password for user <span class="font-semibold text-card-foreground">{{ user.id }}</span>?
            </p>

            <div class="p-3 bg-warning/10 border border-warning/30 rounded-lg text-sm text-warning-foreground">
                <span class="font-semibold block mb-1">⚠️ Warning</span>
                The user will be instantly logged out and must use the new password to log back in.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-border">
                <button
                    type="button"
                    @click="isUserPasswordModalOpen = false"
                    class="px-4 py-2 text-sm font-medium text-muted-foreground bg-secondary/50 hover:bg-secondary/70 rounded-lg transition-colors">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-accent hover:bg-accent/90 rounded-lg transition-colors flex items-center gap-2">
                    <Lock class="w-4 h-4" />
                    Confirm Reset
                </button>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isSuspendModalOpen"
        title="Suspend Account"
        subtitle="Temporarily disable this user's account access."
        @close="isSuspendModalOpen = false">

        <form @submit.prevent="handleAction('Suspended Account')" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Enter a reason for suspension. The user will be immediately logged out.
            </p>

            <textarea
                required
                rows="3"
                placeholder="Reason for suspension"
                class="w-full px-3 py-2.5 text-sm border border-border rounded-lg bg-input placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-destructive/50 focus:border-destructive transition-colors resize-none"></textarea>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-border">
                <button
                    type="button"
                    @click="isSuspendModalOpen = false"
                    class="px-4 py-2 text-sm font-medium text-muted-foreground bg-secondary/50 hover:bg-secondary/70 rounded-lg transition-colors">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-destructive hover:bg-destructive/90 rounded-lg transition-colors flex items-center gap-2">
                    <UserX class="w-4 h-4" />
                    Suspend
                </button>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isUnsuspendModalOpen"
        title="Unsuspend Account"
        subtitle="Restore this user's account access."
        @close="isUnsuspendModalOpen = false">

        <form @submit.prevent="handleAction('Unfroze Account')" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Are you sure you want to <span class="font-semibold text-card-foreground">unsuspend</span> the account for user <span class="font-semibold text-card-foreground">{{ user.id }}</span>?
            </p>

            <div class="p-3 bg-success/10 border border-success/30 rounded-lg text-sm text-success-foreground">
                <span class="font-semibold block mb-1">✅ Note</span>
                The user will regain full access upon completion of this action.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-border">
                <button
                    type="button"
                    @click="isUnsuspendModalOpen = false"
                    class="px-4 py-2 text-sm font-medium text-muted-foreground bg-secondary/50 hover:bg-secondary/70 rounded-lg transition-colors">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-success hover:bg-success/90 rounded-lg transition-colors flex items-center gap-2">
                    <UserCheck class="w-4 h-4" />
                    Unsuspend
                </button>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isReactivateModalOpen"
        title="Reactivate Account"
        subtitle="Restore access for this suspended account."
        @close="isReactivateModalOpen = false">

        <form @submit.prevent="handleAction('Reactivated Account')" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Are you sure you want to <span class="font-semibold text-card-foreground">reactivate</span> the account for user <span class="font-semibold text-card-foreground">{{ user.id }}</span>?
            </p>

            <div class="p-3 bg-success/10 border border-success/30 rounded-lg text-sm text-success-foreground">
                <span class="font-semibold block mb-1">✅ Note</span>
                This action will mark the account as <code class="bg-secondary/50 px-2 py-1 rounded text-xs font-mono">active</code>.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-border">
                <button
                    type="button"
                    @click="isReactivateModalOpen = false"
                    class="px-4 py-2 text-sm font-medium text-muted-foreground bg-secondary/50 hover:bg-secondary/70 rounded-lg transition-colors">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-success hover:bg-success/90 rounded-lg transition-colors flex items-center gap-2">
                    <UserCheck class="w-4 h-4" />
                    Reactivate
                </button>
            </div>
        </form>
    </QuickActionModal>

    <QuickActionModal
        :is-open="isDeleteModalOpen"
        title="Delete Account"
        subtitle="This action is irreversible. Proceed with extreme caution."
        @close="isDeleteModalOpen = false">

        <form @submit.prevent="handleAction('Deleted Account')" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                To confirm permanent deletion of account <span class="font-semibold text-card-foreground">{{ user.id }}</span>, please type <span class="bg-destructive/10 text-destructive font-mono text-xs px-2 py-1 rounded">DELETE</span> below.
            </p>

            <input
                v-model="formData.delete.confirmation"
                type="text"
                required
                placeholder="DELETE"
                class="w-full px-3 py-2.5 text-sm border border-destructive/50 bg-destructive/5 placeholder-destructive/50 text-destructive focus:outline-none focus:ring-2 focus:ring-destructive/50 focus:border-destructive rounded-lg transition-colors font-mono" />

            <div class="p-3 bg-destructive/10 border border-destructive/30 rounded-lg text-sm text-destructive-foreground">
                <span class="font-semibold block mb-1">🚨 Warning</span>
                This action will permanently remove all user data and cannot be recovered.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-border">
                <button
                    type="button"
                    @click="isDeleteModalOpen = false"
                    class="px-4 py-2 text-sm font-medium text-muted-foreground bg-secondary/50 hover:bg-secondary/70 rounded-lg transition-colors">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-destructive hover:bg-destructive/90 rounded-lg transition-colors flex items-center gap-2">
                    <Trash2 class="w-4 h-4" />
                    Permanently Delete
                </button>
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
