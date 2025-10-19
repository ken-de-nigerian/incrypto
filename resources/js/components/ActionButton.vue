<script lang="ts" setup>
    import { LoaderCircle } from 'lucide-vue-next';

    defineProps({
        processing: {
            type: Boolean,
            default: false
        }
    })
</script>

<template>
    <button :disabled="processing" class="btn-crypto w-full h-12 px-6 py-3 inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': processing }">
        <span v-if="!processing">
            <slot>Submit</slot>
        </span>
        <LoaderCircle v-if="processing" class="h-6 w-6 animate-spin" />
    </button>
</template>
