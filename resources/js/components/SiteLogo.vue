<script setup lang="ts">
    import { computed } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import { Zap } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    const page = usePage();
    const siteName = computed(() => page.props.site_name);
    const siteTagline = computed(() => page.props.site_tagline);
</script>

<template>
    <TextLink :href="route('home')" class="flex items-center gap-2.5 group">
        <div class="relative">
            <div class="absolute -inset-1 rounded-full bg-primary/30 blur-sm group-hover:bg-primary/50 transition-all duration-500"></div>
            <div class="relative flex items-center justify-center w-11 h-11 bg-card border-2 border-primary/20 rounded-full shadow-sm overflow-hidden group-hover:border-primary duration-300">
                <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-primary/10 rounded-full blur-sm"></div>
                <Zap class="relative w-6 h-6 text-primary fill-primary transition-transform duration-500 group-hover:scale-110" />
            </div>
        </div>

        <div class="flex flex-col justify-center">
            <span class="font-black text-2xl tracking-tighter text-secondary-foreground group-hover:tracking-normal transition-all duration-300">
                {{ siteName }}<span class="font-light text-primary">{{ siteTagline }}.</span>
            </span>
        </div>
    </TextLink>
</template>
