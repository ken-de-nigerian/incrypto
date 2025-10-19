<script setup lang="ts">
import { Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';

interface Props {
    href: string;
    tabindex?: number;
    method?: Method;
    as?: string;
}

defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        :tabindex="tabindex"
        :method="method"
        :as="as"
        class="text-foreground [text-decoration-color:hsl(var(--border))] underline-offset-4 duration-300 ease-out hover:decoration-current"
    >
        <slot />
    </Link>
</template>
