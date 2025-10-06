<script setup lang="ts">
    import iziToast from 'izitoast'
    import 'izitoast/dist/css/iziToast.min.css'
    import { usePage } from '@inertiajs/vue3'
    import { watch } from 'vue'

    const page = usePage()

    // Configuration
    iziToast.settings({
        timeout: 5000,
        resetOnHover: true,
        position: 'topRight',
        transitionIn: 'flipInX',
        transitionOut: 'flipOutX'
    } as any)

    // Handle both initial load and subsequent navigations
    watch(() => page.props.flash, (flash) => {
        if (!flash) return

        if (flash.success) iziToast.success({ message: flash.success })
        if (flash.error) iziToast.error({ message: flash.error })

        // Clear flash after display
        page.props.flash = null
    }, { immediate: true })
</script>

<template>
    <div>
        <!-- Content, even if empty for now -->
    </div>
</template>
