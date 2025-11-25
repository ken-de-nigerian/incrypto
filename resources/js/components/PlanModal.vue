<script setup lang="ts">
    import { computed, watch } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import CustomSelectDropdown from '@/components/CustomSelectDropdown.vue';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import InputError from '@/components/InputError.vue';
    import { Loader2Icon, PlusIcon, SaveIcon } from 'lucide-vue-next';

    interface PlanTimeSetting {
        id: number;
        name: string;
        period: number;
    }

    interface Plan {
        id: number;
        plan_time_settings_id: number;
        name: string;
        minimum: number | string;
        maximum: number | string;
        interest: number | string;
        period: number;
        status: 'active' | 'inactive';
        capital_back_status: 'yes' | 'no';
        repeat_time: number | string;
    }

    const props = defineProps<{
        isOpen: boolean;
        plan: Plan | null;
        timeSettings: PlanTimeSetting[];
    }>();

    const emit = defineEmits<{
        close: [];
    }>();

    const form = useForm({
        id: null as number | null,
        plan_time_settings_id: '',
        name: '',
        minimum: '',
        maximum: '',
        interest: '',
        period: '',
        status: 'active' as 'active' | 'inactive',
        capital_back_status: 'yes' as 'yes' | 'no',
        repeat_time: ''
    });

    const timeSettingsOptions = computed(() => {
        return (props.timeSettings || []).map(ts => ({
            value: String(ts.id),
            label: `${ts.name}`
        }));
    });

    const statusOptions = [
        { value: 'active', label: 'Active' },
        { value: 'inactive', label: 'Inactive' }
    ];

    const capitalBackOptions = [
        { value: 'yes', label: 'Yes' },
        { value: 'no', label: 'No' }
    ];

    watch(() => props.plan, (newPlan) => {
        if (newPlan) {
            form.id = newPlan.id;
            form.plan_time_settings_id = newPlan.plan_time_settings_id.toString();
            form.name = newPlan.name;
            form.minimum = newPlan.minimum.toString();
            form.maximum = newPlan.maximum.toString();
            form.interest = newPlan.interest.toString();
            form.period = newPlan.period.toString();
            form.status = newPlan.status;
            form.capital_back_status = newPlan.capital_back_status;
            form.repeat_time = newPlan.repeat_time.toString();
        } else {
            form.reset();
        }
    }, { immediate: true });

    const submitForm = () => {
        if (props.plan) {
            form.put(route('admin.plans.update', props.plan.id), {
                preserveScroll: true,
                onSuccess: () => {
                    emit('close');
                    form.reset();
                }
            });
        } else {
            form.post(route('admin.plans.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    emit('close');
                    form.reset();
                }
            });
        }
    };

    const closeModal = () => {
        emit('close');
        form.reset();
        form.clearErrors();
    };

    const clearError = (field: keyof typeof form.errors) => {
        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };
</script>

<template>
    <QuickActionModal
        :is-open="isOpen"
        :title="plan ? 'Edit Plan' : 'Create Plan'"
        :subtitle="plan ? 'Update plan configuration and settings' : 'Configure a new investment plan for your users'"
        @close="closeModal">

        <form @submit.prevent="submitForm" class="space-y-4 margin-bottom">
            <div>
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Plan Name</label>
                <input v-model="form.name" @focus="clearError('name')" type="text" class="w-full px-4 py-2 bg-background border border-border rounded-lg text-card-foreground" placeholder="e.g., Premium Plan" />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Time Setting</label>
                <CustomSelectDropdown
                    v-model="form.plan_time_settings_id"
                    @user-interacted="clearError(form, 'plan_time_settings_id')"
                    :options="timeSettingsOptions"
                    placeholder="Select Time Setting"
                />
                <InputError :message="form.errors.plan_time_settings_id" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Minimum Amount</label>
                    <input v-model="form.minimum" @focus="clearError('minimum')" type="number" step="0.01" class="w-full px-4 py-2 bg-background border border-border rounded-lg text-card-foreground" placeholder="0.00" />
                    <InputError :message="form.errors.maximum" />
                </div>

                <div>
                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Maximum Amount</label>
                    <input v-model="form.maximum" @focus="clearError('maximum')" type="number" step="0.01" class="w-full px-4 py-2 bg-background border border-border rounded-lg text-card-foreground" placeholder="0.00" />
                    <InputError :message="form.errors.minimum" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Interest (%)</label>
                    <input v-model="form.interest" @focus="clearError('interest')" type="number" class="w-full px-4 py-2 bg-background border border-border rounded-lg text-card-foreground" placeholder="0" />
                    <InputError :message="form.errors.interest" />
                </div>

                <div>
                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Repeat Time</label>
                    <input v-model="form.repeat_time" @focus="clearError('repeat_time')" type="number" class="w-full px-4 py-2 bg-background border border-border rounded-lg text-card-foreground" placeholder="1" />
                    <InputError :message="form.errors.repeat_time" />
                </div>

                <div>
                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Status</label>
                    <CustomSelectDropdown
                        v-model="form.status"
                        @user-interacted="clearError(form, 'status')"
                        :options="statusOptions"
                        placeholder="Select Status"
                    />
                </div>

                <div>
                    <label class="text-sm font-medium text-muted-foreground uppercase tracking-wider mb-2">Capital Back Status</label>
                    <CustomSelectDropdown
                        v-model="form.capital_back_status"
                        @user-interacted="clearError(form, 'capital_back_status')"
                        :options="capitalBackOptions"
                        placeholder="Select Capital Back Status"
                    />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                <button
                    :disabled="form.processing"
                    type="submit"
                    :class="[ 'w-full px-4 md:px-6 py-3 sm:py-2.5 rounded-lg font-semibold transition-colors inline-flex justify-center items-center gap-2', form.processing ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer']">
                    <Loader2Icon v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <template v-else>
                        <SaveIcon v-if="plan" class="w-4 h-4" />
                        <PlusIcon v-else class="w-4 h-4" />
                    </template>
                    {{
                        form.processing
                            ? (plan ? 'Updating...' : 'Creating...')
                            : (plan ? 'Update Plan' : 'Create Plan')
                    }}
                </button>
            </div>
        </form>
    </QuickActionModal>
</template>

<style scoped>
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

    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
