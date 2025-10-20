<script setup lang="ts">
    import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
    import { Search } from 'lucide-vue-next';

    interface SelectOption {
        value: string;
        label: string;
        [key: string]: any;
    }

    const props = defineProps<{
        modelValue: string | null | undefined;
        options: SelectOption[];
        placeholder?: string;
    }>();

    const emit = defineEmits(['update:modelValue', 'user-interacted']);
    const showDropdown = ref(false);
    const searchQuery = ref('');
    const dropdownRef = ref<HTMLElement | null>(null);

    const selectedOption = computed(() => {
        if (!props.modelValue && props.modelValue !== '0') {
            return null;
        }

        const modelValue = String(props.modelValue).trim();
        const found = props.options.find(opt => {
            const optValue = String(opt.value).trim();
            return optValue === modelValue;
        });

        return found || null;
    });

    const selectedLabel = computed(() => {
        return selectedOption.value?.label || props.placeholder || 'Select an Option';
    });

    const filteredOptions = computed(() => {
        const query = searchQuery.value.toLowerCase();
        return props.options.filter(option =>
            option.label.toLowerCase().includes(query)
        );
    });

    const selectOption = (option: SelectOption) => {
        const valueToEmit = String(option.value).trim();
        emit('update:modelValue', valueToEmit);
        showDropdown.value = false;
        emit('user-interacted');
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
            @click="showDropdown = !showDropdown; $emit('user-interacted')"
            class="w-full rounded-lg border border-border input-crypto text-sm font-base cursor-pointer">
            <span :class="['flex items-center gap-3 justify-between', { 'text-muted-foreground': !selectedOption }]">
                <slot name="default" :selectedOption="selectedOption" :selectedLabel="selectedLabel">
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
                    type="button"
                    @click="selectOption(option)"
                    class="w-full p-3 flex items-center justify-between cursor-pointer text-left transition-colors text-sm font-base hover:bg-secondary/50">
                    <slot name="option" :option="option">
                        <span>{{ option.label }}</span>
                    </slot>
                </button>
            </div>
        </div>
    </div>
</template>
