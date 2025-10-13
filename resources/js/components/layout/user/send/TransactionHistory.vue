<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { ClockIcon, CheckIcon, XIcon, ChevronDownIcon, ExternalLinkIcon, SendIcon } from 'lucide-vue-next';

    const props = defineProps<{ transactions: Array<any> }>();
    const showTransactionHistory = ref(true);

    const recentTransactions = computed(() => {
        if (!props.transactions) return [];
        return [...props.transactions]
            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
            .slice(0, 10);
    });

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'text-primary';
            case 'pending': return 'text-yellow-500';
            case 'failed': return 'text-destructive';
            default: return 'text-muted-foreground';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'completed': return CheckIcon;
            case 'pending': return ClockIcon;
            case 'failed': return XIcon;
            default: return ClockIcon;
        }
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString();
    };

    // Function to format the token symbol
    const formatSymbol = (symbol: string): string => {
        if (!symbol) return '';

        // Regex to find USDT_ followed by BEP20, ERC20, or TRC20 (case-insensitive)
        const formatted = symbol.replace(/USDT_(BEP20|ERC20|TRC20)/i, (match) => {
            // Replace the underscore with a space only in the matched segment
            return match.replace('_', ' ');
        });

        return formatted.toUpperCase();
    };
</script>

<template>
    <div class="bg-card border border-border rounded-2xl overflow-hidden margin-bottom">
        <button @click="showTransactionHistory = !showTransactionHistory" class="w-full flex items-center justify-between p-4 hover:bg-muted">
            <span class="font-semibold text-card-foreground flex items-center gap-2">
                <ClockIcon class="w-4 h-4" />
                Sent Cryptos
            </span>
            <ChevronDownIcon :class="['w-4 h-4 text-muted-foreground transition-transform', showTransactionHistory && 'rotate-180']"/>
        </button>
        <div v-if="showTransactionHistory" class="border-t border-border">
            <div v-if="recentTransactions.length === 0" class="p-8 text-center">
                <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-3">
                    <SendIcon class="w-6 h-6 text-muted-foreground" />
                </div>
                <p class="text-sm text-muted-foreground">No sent transactions</p>
                <p class="text-xs text-muted-foreground mt-1">Your sent crypto history will appear here</p>
            </div>
            <div v-else>
                <div v-for="tx in recentTransactions" :key="tx.id" class="p-4 border-b border-border last:border-b-0 hover:bg-muted/50">
                    <div class="flex items-start gap-3">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0', tx.status === 'completed' ? 'bg-primary/10' : tx.status === 'pending' ? 'bg-yellow-500/10' : 'bg-destructive/10']">
                            <component :is="getStatusIcon(tx.status)" :class="['w-4 h-4', getStatusColor(tx.status)]" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <div class="text-sm font-medium text-card-foreground">Send {{ formatSymbol(tx.token_symbol) }}</div>
                                <div class="text-xs" :class="getStatusColor(tx.status)">
                                    {{ tx.status.charAt(0).toUpperCase() + tx.status.slice(1) }}
                                </div>
                            </div>
                            <div class="text-xs text-muted-foreground mb-1">{{ formatDate(tx.created_at) }}</div>
                            <div class="text-xs font-semibold text-destructive mb-1">-{{ parseFloat(tx.amount).toFixed(6) }} {{ formatSymbol(tx.token_symbol) }}</div>
                            <div class="text-xs text-muted-foreground mb-1">
                                To: {{ tx.recipient_address.slice(0, 10) }}...{{ tx.recipient_address.slice(-8) }}
                            </div>
                            <div v-if="tx.fee" class="text-xs text-muted-foreground">
                                Fee: {{ parseFloat(tx.fee).toFixed(6) }} {{ formatSymbol(tx.token_symbol) }}
                            </div>
                            <a v-if="tx.transaction_hash && tx.status === 'completed'" :href="`https://etherscan.io/tx/${tx.transaction_hash}`" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1 mt-2">
                                View on Explorer
                                <ExternalLinkIcon class="w-3 h-3" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    @media (max-width: 640px) {
        .margin-bottom {
            margin-bottom: 50px;
        }
    }
</style>
