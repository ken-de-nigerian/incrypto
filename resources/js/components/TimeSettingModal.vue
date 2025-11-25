<script setup lang="ts">
    import { watch } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import { Loader2Icon, PlusIcon, SaveIcon } from 'lucide-vue-next';
    import QuickActionModal from '@/components/QuickActionModal.vue';
    import InputError from '@/components/InputError.vue';

    interface PlanTimeSetting {
        id: number;
        name: string;
        period: number;
    }

    const props = defineProps<{
        isOpen: boolean;
        timeSetting: PlanTimeSetting | null;
    }>();

    const emit = defineEmits<{
        close: [];
    }>();

    const form = useForm({
        id: null as number | null,
        name: '',
        period: ''
    });

    watch(() => props.timeSetting, (newTimeSetting) => {
        if (newTimeSetting) {
            form.id = newTimeSetting.id;
            form.name = newTimeSetting.name;
            form.period = newTimeSetting.period.toString();
        } else {
            form.reset();
        }
    }, { immediate: true });

    const submitForm = () => {
        if (props.timeSetting) {
            form.put(route('admin.time.update', props.timeSetting.id), {
                preserveScroll: true,
                onSuccess: () => {
                    emit('close');
                    form.reset();
                }
            });
        } else {
            form.post(route('admin.time.store'), {
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
        :title="timeSetting ? 'Edit Time Setting' : 'Create Time Setting'"
        :subtitle="timeSetting ? 'Modify the time period settings' : 'Create a new time period configuration for your plans'"
        @close="closeModal">

        <form @submit.prevent="submitForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-card-foreground mb-2">Name</label>
                <input v-model="form.name" @focus="clearError('name')" type="text" class="w-full px-4 py-2 bg-background border border-border rounded-lg text-card-foreground" placeholder="e.g., 24 Hours" />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <label class="block text-sm font-medium text-card-foreground mb-2">Period (Hours)</label>
                <input v-model="form.period" @focus="clearError('period')" type="number" min="1" class="w-full px-4 py-2 bg-background border border-border rounded-lg text-card-foreground" placeholder="24" />
                <p v-if="form.errors.period" class="text-red-500 text-sm mt-1">{{ form.errors.period }}</p>
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
                            ? (timeSetting ? 'Updating...' : 'Creating...')
                            : (timeSetting ? 'Update' : 'Create')
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
</style>
