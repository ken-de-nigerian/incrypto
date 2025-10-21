<script setup lang="ts">
    import { ref, useAttrs, computed } from 'vue';
    import FlatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';

    const attrs = useAttrs();

    const props = defineProps({
        modelValue: { type: [String, Date, Array, null], default: null },
        placeholder: { type: String, default: 'Select date' },
        name: String,
        disabled: Boolean,
        config: { type: Object, default: () => ({}) },
    });

    const emit = defineEmits(['update:modelValue', 'change', 'open', 'close', 'focus']);

    const defaultConfig = {
        altInput: true,
        altFormat: 'F j, Y',
        dateFormat: 'Y-m-d',
        allowInput: true,
        clickOpens: true,
        disableMobile: true,
        ...props.config,
    };

    const flatpickrConfig = ref(defaultConfig);

    const displayValue = computed(() => props.modelValue);

    const handleInput = (value) => {
        const normalizedValue = value ?? null;
        emit('update:modelValue', normalizedValue);
    };

    const handleChange = (selectedDates, dateStr, instance) => {
        const value = props.config?.mode === 'range' ? selectedDates : selectedDates[0] || null;
        emit('change', { selectedDates, dateStr, instance, value });
    };

    const handleOpen = (...args) => emit('open', ...args);
    const handleClose = (...args) => emit('close', ...args);
    const handleFocus = (event) => emit('focus', event);
</script>

<template>
    <flat-pickr
        :modelValue="displayValue"
        @update:modelValue="handleInput"
        :config="flatpickrConfig" :placeholder="placeholder"
        :name="name"
        :disabled="disabled"
        :id="attrs.id"
        :class="['input-crypto', 'w-full']"
        v-bind="attrs"
        @on-change="handleChange"
        @on-open="handleOpen"
        @on-close="handleClose"
        @focus="handleFocus"
    />
</template>

<style>
    .flatpickr-calendar {
        /* background: card, border: border, box-shadow: shadow-card */
        background: hsl(var(--card));
        border: 1px solid hsl(var(--border));
        border-radius: var(--radius);
        box-shadow: var(--shadow-card);
        padding: 0 .5rem;
        width: 325px;
        z-index: 50 !important; /* Ensure it's above other elements */
    }

    .flatpickr-calendar:after, .flatpickr-calendar:before {
        display: none
    }

    .flatpickr-innerContainer {
        padding-bottom: 1rem
    }

    .flatpickr-months {
        padding: .75rem 0
    }

    /* Month arrows */
    .flatpickr-months .flatpickr-next-month svg, .flatpickr-months .flatpickr-prev-month svg {
        fill: hsl(var(--foreground)) !important; /* Use foreground for headings */
    }

    /* Day names and time separator */
    .flatpickr-time .flatpickr-time-separator, span.flatpickr-weekday {
        color: hsl(var(--muted-foreground)); /* Use muted-foreground for secondary text */
    }

    /* Month and year dropdowns */
    .flatpickr-current-month .flatpickr-monthDropdown-months, .numInput, .numInputWrapper {
        color: hsl(var(--foreground)) !important;
    }

    .flatpickr-current-month .flatpickr-monthDropdown-months:hover, .numInputWrapper:hover {
        background-color: hsl(var(--secondary)); /* Use secondary for hover state */
    }

    .numInput:hover {
        background-color: transparent!important
    }

    /* Calendar Day appearance */
    .flatpickr-day {
        border-radius: var(--radius);
        color: hsl(var(--foreground));
        height: 38px;
        line-height: 37px;
        font-weight: 500;
    }

    /* Hover/Focus day */
    .flatpickr-day:focus:not(.flatpickr-disabled):not(.today):not(.selected):not(.startRange):not(.endRange),
    .flatpickr-day:hover:not(.flatpickr-disabled):not(.today):not(.selected):not(.startRange):not(.endRange) {
        background-color: hsl(var(--accent) / 0.1) !important; /* accent/10 for hover */
        border-color: hsl(var(--accent) / 0.1) !important;
        color: hsl(var(--foreground));
    }

    /* Today marker */
    .flatpickr-day.today {
        background-color: transparent!important;
        border: 1px solid hsl(var(--primary)) !important; /* Use primary for border today marker */
        font-weight: 600;
        color: hsl(var(--primary)) !important;
    }

    .flatpickr-day.today.selected {
        color: hsl(var(--primary-foreground))!important; /* Foreground color on primary background */
    }

    /* Selected day */
    .flatpickr-day.selected {
        background-color: hsl(var(--primary))!important;
        border-color: hsl(var(--primary))!important;
        color: hsl(var(--primary-foreground))!important;
        font-weight: 600;
    }

    /* Disabled day */
    .flatpickr-day.flatpickr-disabled {
        color: hsl(var(--muted-foreground))!important;
        text-decoration: line-through
    }

    .flatpickr-day.nextMonthDay, .flatpickr-day.prevMonthDay {
        color: hsl(var(--muted-foreground));
    }

    /* Range styles */
    .flatpickr-day.inRange {
        background-color: hsl(var(--accent) / 0.2)!important; /* accent/20 for range background */
        border-color: hsl(var(--accent) / 0.2)!important;
        box-shadow: -5px 0 0 hsl(var(--accent) / 0.2), 5px 0 0 hsl(var(--accent) / 0.2);
    }

    .flatpickr-day.startRange, .flatpickr-day.endRange {
        background-color: hsl(var(--primary))!important;
        border-color: hsl(var(--primary))!important;
        color: hsl(var(--primary-foreground))!important;
        font-weight: 600;
    }

    /* Time picker */
    .flatpickr-time .flatpickr-am-pm {
        color: hsl(var(--foreground));
    }

    .flatpickr-calendar.hasTime .flatpickr-innerContainer+.flatpickr-time {
        border-top: 1px solid hsl(var(--border));
    }

    /* Time number input arrows */
    .numInputWrapper span {
        border-color: hsl(var(--border));
    }

    .numInputWrapper span.arrowUp:after {
        border-bottom-color: hsl(var(--foreground))!important;
    }

    .numInputWrapper span.arrowDown:after {
        border-top-color: hsl(var(--foreground))!important;
    }

    .numInputWrapper span:hover {
        background: hsl(var(--border));
    }

    /* Dark Theme Calendar Styles */
    .dark .flatpickr-calendar {
        background: hsl(var(--card));
        border-color: hsl(var(--border));
        box-shadow: 0 .5rem 1.875rem -.25rem hsl(0 0% 0% / 0.35); /* Custom dark shadow */
    }
</style>
