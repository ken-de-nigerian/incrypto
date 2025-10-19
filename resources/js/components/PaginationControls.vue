<script setup lang="ts">
    import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
    import { computed } from 'vue';

    interface Link {
        url: string | null;
        label: string;
        active: boolean;
    }

    interface Props {
        links: Link[];
        from: number;
        to: number;
        total: number;
    }

    const props = defineProps<Props>();

    const emit = defineEmits<{
        goToPage: [url: string];
    }>();

    const goToPage = (url: string | null) => {
        if (url) {
            emit('goToPage', url);
        }
    };

    const prevLink = computed(() => {
        return props.links.find(l => l.label.includes('Previous')) || null;
    });

    const nextLink = computed(() => {
        return props.links.find(l => l.label.includes('Next')) || null;
    });
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:justify-between items-center pt-8 pb-4 space-y-4 sm:space-y-0">
        <div class="text-sm text-muted-foreground hidden sm:block">
            Showing <span class="font-semibold text-foreground">{{ props.from }}</span> to <span class="font-semibold text-foreground">{{ props.to }}</span> of <span class="font-semibold text-foreground">{{ props.total }}</span> results
        </div>

        <nav class="flex items-center space-x-2 w-full justify-center sm:w-auto sm:justify-end">
            <button @click="goToPage(prevLink?.url)" :disabled="!prevLink?.url"
                    class="px-4 py-2
                    rounded-xl bg-card border border-border
                    text-muted-foreground
                    hover:bg-secondary/50 hover:text-primary
                    disabled:opacity-50 disabled:cursor-not-allowed
                    transition-colors
                    flex items-center justify-center space-x-1 font-medium cursor-pointer
                ">
                <ChevronLeft class="w-4 h-4" />
                <span class="text-sm">Previous</span>
            </button>

            <button @click="goToPage(nextLink?.url)" :disabled="!nextLink?.url"
                    class="px-4 py-2
                    rounded-xl bg-card border border-border
                    text-muted-foreground
                    hover:bg-secondary/50 hover:text-primary
                    disabled:opacity-50 disabled:cursor-not-allowed
                    transition-colors
                    flex items-center justify-center space-x-1 font-medium cursor-pointer
                ">
                <span class="text-sm">Next</span>
                <ChevronRight class="w-4 h-4" />
            </button>
        </nav>
    </div>
</template>
