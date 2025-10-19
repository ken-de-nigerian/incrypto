<script setup lang="ts">
    import { Clock, UserCheck, CreditCard, Ticket } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    interface Action {
        id: number;
        type: string;
        user: string;
        link: string;
    }

    defineProps<{
        actions: Action[];
    }>();

    const getIconAndClass = (type: string) => {
        switch (type) {
            case 'KYC Review':
                return { Icon: UserCheck, class: 'bg-accent/20 text-accent' };
            case 'Withdrawal Approval':
                return { Icon: CreditCard, class: 'bg-destructive/20 text-destructive' };
            case 'New Ticket':
                return { Icon: Ticket, class: 'bg-warning/20 text-warning' };
            default:
                return { Icon: Clock, class: 'bg-muted/10 text-muted-foreground' };
        }
    };
</script>

<template>
    <div class="card-crypto p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3 mb-2">
            <h2 class="text-xl font-semibold text-card-foreground">Pending Actions ({{ actions.length }})</h2>
        </div>

        <ul class="space-y-4">
            <li v-for="action in actions" :key="action.id" class="flex items-center">
                <div :class="['p-2 rounded-full mr-4', getIconAndClass(action.type).class]">
                    <component :is="getIconAndClass(action.type).Icon" class="w-4 h-4" />
                </div>
                <div class="flex-grow">
                    <p class="font-medium text-card-foreground">{{ action.type }}</p>
                    <p class="text-sm text-muted-foreground">For: {{ action.user }}</p>
                </div>
                <TextLink :href="action.link" class="text-sm font-medium text-primary hover:text-primary-foreground/80">
                    Review &rarr;
                </TextLink>
            </li>
        </ul>
        <div v-if="actions.length === 0" class="text-center py-4 text-muted-foreground">
            All clear! No pending actions.
        </div>
    </div>
</template>
