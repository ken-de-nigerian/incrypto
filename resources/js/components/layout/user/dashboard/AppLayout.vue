<script setup lang="ts">
import TopbarNav from '@/components/layout/user/navigation/TopbarNav.vue';
import SidebarNav from '@/components/layout/user/navigation/SidebarNav.vue';
import MobileNav from '@/components/layout/user/navigation/MobileNav.vue';
import FlashMessages from '@/components/utilities/FlashMessages.vue';
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <SidebarNav />

        <TopbarNav />

        <MobileNav />

        <slot/>
    </div>

    <FlashMessages />
</template>
