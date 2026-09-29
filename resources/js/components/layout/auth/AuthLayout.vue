<script setup lang="ts">
    import FlashMessages from '@/components/utilities/FlashMessages.vue';
    import LiveSupport from '@/components/LiveSupport.vue';
</script>

<template>
    <div class="min-h-screen bg-background flex flex-col lg:flex-row">
        <slot name="sidebar" />
        <slot name="form" />
    </div>

    <FlashMessages />
    <LiveSupport />
</template>
