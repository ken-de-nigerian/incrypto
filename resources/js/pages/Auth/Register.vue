<template>
    <Head title="Let's Get Started" />

    <AuthLayout>
        <template #sidebar>
            <AuthRegisterSidebar/>
        </template>

        <template #form>
            <AuthRegisterForm :provider="provider"/>
        </template>
    </AuthLayout>
</template>

<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import AuthLayout from '@/components/layout/auth/AuthLayout.vue';
    import AuthRegisterSidebar from '@/components/layout/auth/register/AuthRegisterSidebar.vue';
    import AuthRegisterForm from '@/components/layout/auth/register/AuthRegisterForm.vue';

    defineProps<{
        provider?: string;
    }>();
</script>

