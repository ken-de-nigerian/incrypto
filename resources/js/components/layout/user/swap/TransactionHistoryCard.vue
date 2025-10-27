<script setup lang="ts">
    import { ref } from 'vue';
    import { ClockIcon, CheckCircleIcon, XIcon, ExternalLinkIcon, ChevronDownIcon } from 'lucide-vue-next';
    import { formatDistanceToNow } from 'date-fns';

    defineProps<{
        transactions: Array<{
            id: number;
            date: string;
            from: string;
            to: string;
            amount: string;
            status: 'success' | 'failed' | 'pending';
            hash: string;
        }>;
    }>();

    const showHistory = ref(true);
    const formatTxTime = (dateString: string) => {
        return formatDistanceToNow(new Date(dateString), { addSuffix: true });
    };

    // Function to format the token symbol
    const formatSymbol = (symbol: string): string => {
        if (!symbol) return '';
        const formatted = symbol.replace(/USDT_(BEP20|ERC20|TRC20)/i, (match) => {
            return match.replace('_', ' ');
        });

        return formatted.toUpperCase();
    };
</script>

<template>
    <div class="bg-card border border-border rounded-2xl overflow-hidden">
        <button @click="showHistory = !showHistory" class="w-full flex items-center justify-between p-4 hover:bg-muted/70">
            <span class="font-semibold text-card-foreground flex items-center gap-2">
                <ClockIcon class="w-4 h-4" />
                Recent Transactions
            </span>
            <ChevronDownIcon :class="['w-4 h-4 text-muted-foreground transition-transform', showHistory && 'rotate-180']" />
        </button>

        <div v-if="showHistory" class="border-t border-border">
            <div v-if="!transactions.length" class="p-4 text-sm text-muted-foreground text-center">
                No recent transactions.
            </div>

            <div v-else v-for="tx in transactions" :key="tx.id" class="p-4 border-b border-border last:border-b-0 hover:bg-muted/50">
                <div class="flex items-start gap-3">
                    <div
                        :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
                            tx.status === 'success' ? 'bg-primary/10' : tx.status === 'pending' ? 'bg-warning/10' : 'bg-destructive/10',
                        ]">
                        <CheckCircleIcon v-if="tx.status === 'success'" class="w-4 h-4 text-primary" />
                        <ClockIcon v-else-if="tx.status === 'pending'" class="w-4 h-4 text-warning animate-pulse" />
                        <XIcon v-else class="w-4 h-4 text-destructive" />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-card-foreground truncate">
                            {{ tx.amount }} {{ formatSymbol(tx.from) }} → {{ formatSymbol(tx.to) }}
                        </div>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ formatTxTime(tx.date) }}
                        </div>
                        <a :href="`https://etherscan.io/tx/${tx.hash}`"
                           target="_blank"
                           class="text-xs text-primary hover:underline flex items-center gap-1 mt-1">
                            View on Etherscan
                            <ExternalLinkIcon class="w-3 h-3" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
