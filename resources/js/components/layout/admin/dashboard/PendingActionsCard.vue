<script setup lang="ts">
    import { Clock, UserCheck, Send, Download, Clock2 } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';
    import type { Icon as LucideIcon } from 'lucide-vue-next';

    interface Action {
        id: number;
        type: string;
        user: string;
        link: string;
    }

    defineProps<{
        actions: Action[];
    }>();

    const getIconAndClass = (type: string): { Icon: LucideIcon, class: string } => {
        switch (type) {
            case 'KYC Review':
                return { Icon: UserCheck, class: 'bg-accent/20 text-accent' };
            case 'Sent Crypto Approval':
                return { Icon: Send, class: 'bg-destructive/20 text-destructive' };
            case 'Received Crypto Approval':
                return { Icon: Download, class: 'bg-warning/20 text-warning' };
            default:
                return { Icon: Clock, class: 'bg-muted/10 text-muted-foreground' };
        }
    };
</script>

<template>
    <div class="card-crypto p-4 md:p-6 space-y-4">
        <div v-if="actions.length > 0" class="flex items-center justify-between border-b border-border pb-3 mb-2">
            <h2 class="text-lg sm:text-xl font-semibold text-card-foreground">Pending Actions</h2>
        </div>

        <ul v-if="actions.length > 0" class="space-y-4">
            <li v-for="action in actions" :key="action.id" class="flex items-center transition-colors hover:bg-card/50 rounded-lg p-2 -mx-2">
                <div :class="['p-2 rounded-full mr-3 sm:mr-4 shrink-0', getIconAndClass(action.type).class]">
                    <component :is="getIconAndClass(action.type).Icon" class="w-4 h-4" />
                </div>

                <div class="flex-grow min-w-0 pr-2">
                    <p class="font-medium text-card-foreground truncate">{{ action.type }}</p>
                    <p class="text-xs sm:text-sm text-muted-foreground truncate">For: {{ action.user }}</p>
                </div>

                <TextLink :href="action.link" class="text-sm font-semibold text-primary hover:text-primary/70 shrink-0 whitespace-nowrap">
                    Review &rarr;
                </TextLink>
            </li>
        </ul>

        <div v-else class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
            <Clock2 class="h-10 w-10 sm:h-12 sm:w-12 mb-4 text-muted-foreground" />
            <p class="text-base sm:text-lg font-medium mb-2 text-card-foreground">No Pending Actions</p>
            <p class="text-sm">Your administrative action inbox is empty. Excellent work!</p>
        </div>
    </div>
</template>
