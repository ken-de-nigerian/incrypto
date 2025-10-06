<template>
    <Head title="Verify Your Account" />

    <AuthLayout>
        <template #sidebar>
            <AuthVerifySidebar/>
        </template>

        <template #form>
            <AuthVerifyForm/>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import AuthLayout from '@/components/layout/auth/AuthLayout.vue';
    import AuthVerifySidebar from '@/components/layout/auth/verify/AuthVerifySidebar.vue';
    import AuthVerifyForm from '@/components/layout/auth/verify/AuthVerifyForm.vue';
</script>
