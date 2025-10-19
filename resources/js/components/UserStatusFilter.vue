<script setup lang="ts">
    import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
    import { ChevronDown, Search, Check } from 'lucide-vue-next';

    interface StatusOption {
        value: 'all' | 'active' | 'suspended';
        label: string;
    }

    const props = defineProps<{
        modelValue: 'active' | 'suspended' | '';
    }>();

    const emit = defineEmits(['update:modelValue']);
    const showStatusDropdown = ref(false);
    const statusSearchQuery = ref('');
    const dropdownRef = ref<HTMLElement | null>(null);

    const statusOptions: StatusOption[] = [
        { value: 'all', label: 'All Users' },
        { value: 'active', label: 'Active Users' },
        { value: 'suspended', label: 'Suspended Users' },
    ];

    const selectedLabel = computed(() => {
        const currentValue = props.modelValue === '' ? 'all' : props.modelValue;
        const current = statusOptions.find(opt => opt.value === currentValue);

        return current ? current.label : 'Select Status';
    });

    const filteredOptions = computed(() => {
        const query = statusSearchQuery.value.toLowerCase();
        const currentSelectedValue = props.modelValue === '' ? 'all' : props.modelValue;
        const optionsAfterSearch = statusOptions.filter(option =>
            option.label.toLowerCase().includes(query)
        );

        // Remove the currently selected option from the dropdown list
        return optionsAfterSearch.filter(option =>
            option.value !== currentSelectedValue
        );
    });

    const selectStatus = (option: StatusOption) => {
        const newValue = option.value === 'all' ? '' : option.value;
        emit('update:modelValue', newValue);
        showStatusDropdown.value = false;
    };

    const handleClickOutside = (event: MouseEvent) => {
        if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
            showStatusDropdown.value = false;
        }
    };

    onMounted(() => {
        document.addEventListener('click', handleClickOutside);
    });

    onBeforeUnmount(() => {
        document.removeEventListener('click', handleClickOutside);
    });

    watch(() => showStatusDropdown.value, (isOpened) => {
        if (isOpened) {
            statusSearchQuery.value = '';
        }
    });
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <button
            @click="showStatusDropdown = !showStatusDropdown"
            class="w-full h-10 px-4 py-2 rounded-lg flex items-center justify-between
            bg-background border border-border text-sm font-base cursor-pointer">

            <span class="flex items-center justify-between w-full">
                <span class="text-foreground">{{ selectedLabel }}</span>

                <div class="flex items-center gap-2">
                    <Check
                        v-if="props.modelValue !== ''"
                        class="w-4 h-4 text-primary"
                    />

                    <ChevronDown
                        :class="['w-4 h-4 text-muted-foreground transition-transform', showStatusDropdown && 'rotate-180']"
                    />
                </div>
            </span>
        </button>

        <div v-if="showStatusDropdown" class="absolute top-full left-0 right-0 mt-2 bg-card border border-border rounded-lg shadow-xl z-50 max-h-80 overflow-hidden">
            <div class="p-3 border-b border-border">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <input
                        v-model="statusSearchQuery"
                        type="text"
                        placeholder="Filter status..."
                        class="w-full pl-10 pr-4 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
            </div>

            <div class="max-h-64 overflow-y-auto">
                <div v-if="filteredOptions.length === 0" class="p-4 text-center text-sm text-muted-foreground">No matching status found</div>
                <button
                    v-for="option in filteredOptions"
                    :key="option.value"
                    @click="selectStatus(option)"
                    :class="[
                        'w-full p-3 flex items-center justify-between cursor-pointer text-left transition-colors',
                        'hover:bg-secondary/70'
                    ]">
                    <span class="text-sm font-semibold text-foreground">{{ option.label }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
