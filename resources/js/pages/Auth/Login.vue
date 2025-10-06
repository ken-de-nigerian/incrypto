<template>
    <Head title="Login To Your Account" />

    <AuthLayout>
        <template #sidebar>
            <AuthLoginSidebar/>
        </template>

        <template #form>
            <AuthLoginForm can-reset-password/>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import AuthLayout from '@/components/layout/auth/AuthLayout.vue';
    import AuthLoginSidebar from '@/components/layout/auth/login/AuthLoginSidebar.vue';
    import AuthLoginForm from '@/components/layout/auth/login/AuthLoginForm.vue';
</script>
