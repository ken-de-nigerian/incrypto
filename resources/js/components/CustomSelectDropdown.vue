<script setup lang="ts">
    import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
    import { Search } from 'lucide-vue-next';

    interface SelectOption {
        value: string;
        label: string;
        [key: string]: any;
    }

    const props = defineProps<{
        modelValue: string;
        options: SelectOption[];
        placeholder?: string;
    }>();

    const emit = defineEmits(['update:modelValue']);
    const showDropdown = ref(false);
    const searchQuery = ref('');
    const dropdownRef = ref<HTMLElement | null>(null);

    // Computed property to determine the currently selected option object
    const selectedOption = computed(() => {
        return props.options.find(opt => opt.value === props.modelValue);
    });

    // Computed property to determine the label for the main button
    const selectedLabel = computed(() => {
        return selectedOption.value ? selectedOption.value.label : (props.placeholder || 'Select an Option');
    });

    const filteredOptions = computed(() => {
        const query = searchQuery.value.toLowerCase();
        const optionsAfterSearch = props.options.filter(option =>
            option.label.toLowerCase().includes(query)
        );

        return optionsAfterSearch.filter(option =>
            option.value !== props.modelValue
        );
    });

    const selectOption = (option: SelectOption) => {
        emit('update:modelValue', option.value);
        showDropdown.value = false;
    };

    const handleClickOutside = (event: MouseEvent) => {
        if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
            showDropdown.value = false;
        }
    };

    onMounted(() => {
        document.addEventListener('click', handleClickOutside);
    });

    onBeforeUnmount(() => {
        document.removeEventListener('click', handleClickOutside);
    });

    watch(() => showDropdown.value, (isOpened) => {
        if (isOpened) {
            searchQuery.value = '';
        }
    });
</script>

<template>
    <div class="relative w-full" ref="dropdownRef">
        <button
            type="button"
            @click="showDropdown = !showDropdown"
            class="w-full rounded-lg border border-border input-crypto text-sm font-base cursor-pointer">
            <span :class="['flex items-center gap-3 justify-between', { 'text-muted-foreground': !selectedOption }]">
                <slot name="default" :selectedOption="selectedOption">
                    {{ selectedLabel }}
                </slot>
            </span>
        </button>

        <div v-if="showDropdown" class="absolute top-full left-0 right-0 mt-2 bg-card border border-border rounded-lg shadow-xl z-50 max-h-80 overflow-hidden">

            <div class="p-3 border-b border-border">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Filter options..."
                        class="w-full pl-10 pr-4 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
            </div>

            <div class="max-h-64 overflow-y-auto">
                <div v-if="filteredOptions.length === 0" class="p-4 text-center text-sm text-muted-foreground">No matching options found</div>
                <button
                    v-for="option in filteredOptions"
                    :key="option.value"
                    @click="selectOption(option)"
                    :class="[
                        'w-full p-3 flex items-center justify-between cursor-pointer text-left transition-colors',
                        option.value === props.modelValue
                            ? 'bg-primary/10 text-primary text-sm font-base'
                            : 'hover:bg-secondary/70 text-foreground'
                    ]">
                    <slot name="option" :option="option">
                        <span>{{ option.label }}</span>
                    </slot>
                </button>
            </div>
        </div>
    </div>
</template>
