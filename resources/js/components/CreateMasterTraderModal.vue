<script setup lang="ts">
    import { ref, computed, watch } from 'vue';
    import { router } from '@inertiajs/vue3';
    import {
        X,
        Loader2 as Loader2Icon,
        UserPlus as UserPlusIcon,
    } from 'lucide-vue-next';
    import InputError from '@/components/InputError.vue';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        profile?: {
            profile_photo_path?: string;
        };
    }

    const props = defineProps<{
        isOpen: boolean;
        users: User[];
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
    }>();

    const formData = ref({
        user_id: '',
        expertise: 'Newcomer',
        risk_score: 5,
        gain_percentage: 0,
        commission_rate: '',
        total_profit: 0,
        total_loss: 0,
        is_active: true,
        bio: '',
        total_trades: 0,
        win_rate: 0
    });

    const isProcessing = ref(false);
    const validationErrors = ref<Record<string, string>>({});

    const expertiseOptions = [
        { value: 'Newcomer', label: 'Newcomer', color: 'gray' },
        { value: 'Growing talent', label: 'Growing Talent', color: 'blue' },
        { value: 'High achiever', label: 'High Achiever', color: 'green' },
        { value: 'Expert', label: 'Expert', color: 'cyan' },
        { value: 'Legend', label: 'Legend', color: 'orange' }
    ];

    const userOptions = computed(() => {
        return props.users.map(user => ({
            value: String(user.id),
            label: `${user.first_name} ${user.last_name} (${user.email})`,
            ...user
        }));
    });

    const validateForm = (): boolean => {
        validationErrors.value = {};
        let isValid = true;

        if (!formData.value.user_id) {
            validationErrors.value.user_id = 'Please select a user';
            isValid = false;
        }

        if (formData.value.risk_score < 1 || formData.value.risk_score > 10) {
            validationErrors.value.risk_score = 'Risk score must be between 1 and 10';
            isValid = false;
        }

        if (formData.value.win_rate < 0 || formData.value.win_rate > 100) {
            validationErrors.value.win_rate = 'Win rate must be between 0 and 100';
            isValid = false;
        }

        return isValid;
    };

    const handleSubmit = () => {
        if (!validateForm()) return;

        isProcessing.value = true;

        router.post(route('admin.network.store'), {
            ...formData.value,
            commission_rate: formData.value.commission_rate || null
        }, {
            onSuccess: () => {
                emit('close');
                resetForm();
            },
            onError: (errors) => {
                validationErrors.value = errors;
            },
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    };

    const resetForm = () => {
        formData.value = {
            user_id: '',
            expertise: 'Newcomer',
            risk_score: 5,
            gain_percentage: 0,
            commission_rate: '',
            total_profit: 0,
            total_loss: 0,
            is_active: true,
            bio: '',
            total_trades: 0,
            win_rate: 0
        };
        validationErrors.value = {};
    };

    const handleClose = () => {
        if (!isProcessing.value) {
            resetForm();
            emit('close');
        }
    };

    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
            resetForm();
        }
    });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm sm:p-4"
            >
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-if="isOpen"
                        class="bg-card w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] sm:max-w-3xl flex flex-col rounded-none sm:rounded-2xl overflow-hidden border-border sm:border relative"
                    >
                        <!-- Header -->
                        <div class="px-4 sm:px-6 py-4 border-b border-border shrink-0 bg-card">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                        <UserPlusIcon class="w-5 h-5 text-primary" />
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-card-foreground">Create Master Trader</h2>
                                        <p class="text-sm text-muted-foreground">Add a new master trader to the platform</p>
                                    </div>
                                </div>
                                <button
                                    @click="handleClose"
                                    :disabled="isProcessing"
                                    class="p-2 hover:bg-muted rounded-lg transition-colors disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">
                                    <X class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto no-scrollbar px-4 sm:px-6 py-6">
                            <form @submit.prevent="handleSubmit" class="space-y-6">
                                <!-- User Selection -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                        Select User *
                                    </label>

                                    <div class="space-y-2">
                                        <CustomSelectDropdown
                                            v-model="formData.user_id"
                                            :options="userOptions"
                                            placeholder="Select a user">
                                            <template #default="{ selectedOption }">
                                                <template v-if="selectedOption">
                                                    <div class="flex items-center gap-3">
                                                        <img
                                                            v-if="selectedOption.profile?.profile_photo_path"
                                                            :src="selectedOption.profile.profile_photo_path"
                                                            loading="lazy"
                                                            :alt="selectedOption.first_name"
                                                            class="h-8 w-8 rounded-full object-cover"
                                                            @error="(e) => (e.target as HTMLImageElement).src = 'https://ui-avatars.com/api/?name=' + selectedOption.first_name + '+' + selectedOption.last_name"
                                                        />
                                                        <div v-else class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-xs font-semibold text-primary">
                                                            {{ selectedOption.first_name?.charAt(0) }}{{ selectedOption.last_name?.charAt(0) }}
                                                        </div>
                                                        <div class="text-left">
                                                            <div class="font-medium text-card-foreground">{{ selectedOption.first_name }} {{ selectedOption.last_name }}</div>
                                                            <div class="text-xs text-muted-foreground">{{ selectedOption.email }}</div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <span class="text-muted-foreground">Select a user</span>
                                                </template>
                                            </template>

                                            <template #option="{ option }">
                                                <div class="flex items-center gap-3">
                                                    <img
                                                        v-if="option.profile?.profile_photo_path"
                                                        :src="option.profile.profile_photo_path"
                                                        loading="lazy"
                                                        :alt="option.first_name"
                                                        class="h-8 w-8 rounded-full object-cover"
                                                        @error="(e) => (e.target as HTMLImageElement).src = 'https://ui-avatars.com/api/?name=' + option.first_name + '+' + option.last_name"
                                                    />
                                                    <div v-else class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-xs font-semibold text-primary">
                                                        {{ option.first_name?.charAt(0) }}{{ option.last_name?.charAt(0) }}
                                                    </div>
                                                    <div class="text-left">
                                                        <div class="font-medium text-card-foreground">{{ option.first_name }} {{ option.last_name }}</div>
                                                        <div class="text-xs text-muted-foreground">{{ option.email }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </CustomSelectDropdown>
                                        <InputError :message="validationErrors.user_id" />
                                    </div>
                                </div>

                                <!-- Expertise Level -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                        Expertise Level *
                                    </label>
                                    <CustomSelectDropdown
                                        v-model="formData.expertise"
                                        :options="expertiseOptions"
                                        placeholder="Select Action Type"
                                    />
                                </div>

                                <!-- Risk Score -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                        Risk Score (1-10) *
                                    </label>
                                    <input v-model.number="formData.risk_score" type="number" min="1" max="10" class="w-full px-4 py-3 bg-background border rounded-lg text-sm input-crypto" />
                                    <InputError :message="validationErrors.risk_score" />
                                </div>

                                <!-- Two Column Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Gain Percentage -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                            Gain Percentage
                                        </label>
                                        <input v-model.number="formData.gain_percentage" type="number" step="0.01" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                    </div>

                                    <!-- Commission Rate -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                            Commission Rate
                                        </label>
                                        <input v-model="formData.commission_rate" type="number" step="0.01" placeholder="Leave empty for free" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                    </div>

                                    <!-- Total Profit -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Profit</label>
                                        <input v-model.number="formData.total_profit" type="number" step="0.01" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                    </div>

                                    <!-- Total Loss -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Loss</label>
                                        <input v-model.number="formData.total_loss" type="number" step="0.01" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                    </div>

                                    <!-- Total Trades -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                                            Total Trades
                                        </label>
                                        <input v-model.number="formData.total_trades" type="number" class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto" />
                                    </div>

                                    <!-- Win Rate -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Win Rate (%)</label>
                                        <input v-model.number="formData.win_rate" type="number" step="0.01" min="0" max="100" class="w-full px-4 py-3 bg-background border rounded-lg text-sm input-crypto" />
                                        <InputError :message="validationErrors.win_rate" />
                                    </div>
                                </div>

                                <!-- Bio -->
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Bio</label>
                                    <textarea v-model="formData.bio" rows="4" placeholder="Enter trader bio..." class="w-full px-4 py-3 bg-background border border-border rounded-lg text-sm input-crypto resize-none"></textarea>
                                </div>

                                <!-- Active Status -->
                                <div class="flex items-center gap-3 p-4 bg-muted/30 border border-border rounded-lg">
                                    <input v-model="formData.is_active" type="checkbox" id="is_active" class="w-5 h-5 text-primary bg-background border-border rounded cursor-pointer" />
                                    <label for="is_active" class="text-sm font-medium text-card-foreground cursor-pointer flex-1">
                                        Set as Active Trader
                                    </label>
                                </div>
                            </form>
                        </div>

                        <!-- Footer -->
                        <div class="px-4 sm:px-6 py-4 border-t border-border bg-muted/10 shrink-0 safe-area-pb">
                            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                                <button
                                    @click="handleClose"
                                    :disabled="isProcessing"
                                    type="button"
                                    class="w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 bg-background border border-border text-card-foreground rounded-lg font-semibold hover:bg-muted transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Cancel
                                </button>

                                <button
                                    @click="handleSubmit"
                                    :disabled="isProcessing || !formData.user_id"
                                    type="button"
                                    :class="[
                                        'w-full sm:w-auto px-4 md:px-6 py-3 sm:py-2.5 rounded-lg font-semibold transition-colors inline-flex justify-center items-center gap-2',
                                        isProcessing || !formData.user_id
                                            ? 'bg-muted text-muted-foreground cursor-not-allowed'
                                            : 'bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer'
                                    ]">
                                    <Loader2Icon v-if="isProcessing" class="w-4 h-4 animate-spin" />
                                    <UserPlusIcon v-else class="w-4 h-4" />
                                    {{ isProcessing ? 'Creating...' : 'Create Master Trader' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .safe-area-pb {
        padding-bottom: max(1rem, env(safe-area-inset-bottom));
    }

    ::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    @media (min-width: 1024px) {
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
    }

    ::-webkit-scrollbar-track {
        background: hsl(var(--muted));
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: hsl(var(--muted-foreground) / 0.3);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--muted-foreground) / 0.5);
    }
</style>
