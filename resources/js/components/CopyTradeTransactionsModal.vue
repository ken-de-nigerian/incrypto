<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import { Activity, BarChart3, Calendar, Pause, Play, StopCircle, TrendingDown, TrendingUp, X } from 'lucide-vue-next';
    import AlertTriangleIcon from '@/components/utilities/AlertTriangleIcon.vue';

    interface TradeMetadata {
        id?: number;
        user_id?: number;
        category?: string;
        pair?: string;
        pair_name?: string;
        type?: string;
        amount?: number;
        leverage?: number;
        duration?: string;
        entry_price?: number;
        exit_price?: number;
        trading_mode?: string;
        is_demo_forced_win?: boolean;
        status?: string;
        pnl?: number;
        expiry_time?: string;
        opened_at?: string;
        created_at?: string;
        updated_at?: string;
        position_size?: number;
        holding_time_minutes?: number;
        multiplier?: number;
        master_amount?: number;
        master_pnl?: number;
    }

    interface CopyTradeTransaction {
        id: number;
        copy_trade_id: number;
        type: 'up' | 'down';
        amount: number | string;
        description: string | null;
        metadata: TradeMetadata | null;
        created_at: string;
        updated_at: string;
    }

    interface MasterTrader {
        id: number;
        expertise: string;
        user: {
            first_name: string;
            last_name: string;
        } | null;
    }

    interface CopyTrade {
        id: number;
        current_profit: number | string;
        current_loss: number | string;
        total_commission_paid: number | string;
        status: string;
        started_at: string;
        multiplier: number;
        master_trader: MasterTrader | null;
        transactions?: CopyTradeTransaction[];
    }

    const props = defineProps<{
        isOpen: boolean;
        copyTrade: CopyTrade | null;
    }>();

    const emit = defineEmits<{
        (e: 'close'): void;
        (e: 'pause', copyTrade: CopyTrade): void;
        (e: 'resume', copyTrade: CopyTrade): void;
        (e: 'stop', copyTrade: CopyTrade): void;
    }>();

    const filterType = ref<'all' | 'profit' | 'loss'>('all');

    const transactions = computed(() => {
        if (!props.copyTrade?.transactions) return [];

        let filtered = [...props.copyTrade.transactions];

        if (filterType.value === 'profit') {
            filtered = filtered.filter(t => (t.metadata?.pnl ?? 0) > 0);
        } else if (filterType.value === 'loss') {
            filtered = filtered.filter(t => (t.metadata?.pnl ?? 0) < 0);
        }

        return filtered.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
    });

    const stats = computed(() => {
        if (!props.copyTrade) return null;

        const totalTransactions = transactions.value.length;

        const profitTransactions = transactions.value.filter(t => {
            const pnl = t.metadata?.pnl ?? 0;
            return pnl > 0;
        }).length;

        const lossTransactions = transactions.value.filter(t => {
            const pnl = t.metadata?.pnl ?? 0;
            return pnl < 0;
        }).length;

        const totalProfit = transactions.value
            .filter(t => (t.metadata?.pnl ?? 0) > 0)
            .reduce((sum, t) => sum + Math.abs(parseFloat(String(t.metadata?.pnl ?? 0))), 0);

        const totalLoss = transactions.value
            .filter(t => (t.metadata?.pnl ?? 0) < 0)
            .reduce((sum, t) => sum + Math.abs(parseFloat(String(t.metadata?.pnl ?? 0))), 0);

        const netProfit = totalProfit - totalLoss;
        const winRate = totalTransactions > 0 ? (profitTransactions / totalTransactions) * 100 : 0;

        return {
            totalTransactions,
            profitTransactions,
            lossTransactions,
            totalProfit,
            totalLoss,
            netProfit,
            winRate
        };
    });

    const getTraderName = computed(() => {
        if (!props.copyTrade?.master_trader?.user) return 'Unknown Trader';
        return `${props.copyTrade.master_trader.user.first_name} ${props.copyTrade.master_trader.user.last_name}`;
    });

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatNumber = (value: any, decimals: number = 2) => {
        if (value === null || value === undefined || isNaN(Number(value))) {
            return '0.00';
        }
        return Number(value).toFixed(decimals);
    };

    const getDurationDisplay = (transaction: CopyTradeTransaction) => {
        if (transaction.metadata?.duration) {
            return transaction.metadata.duration;
        }

        if (transaction.metadata?.holding_time_minutes) {
            const minutes = transaction.metadata.holding_time_minutes;
            if (minutes < 60) return `${minutes}m`;
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
        }

        return 'N/A';
    };

    const getTransactionPnL = (transaction: CopyTradeTransaction) => {
        return transaction.metadata?.pnl ?? 0;
    };

    const isProfit = (transaction: CopyTradeTransaction) => {
        return getTransactionPnL(transaction) > 0;
    };

    const getMultiplier = (transaction: CopyTradeTransaction) => {
        return transaction.metadata?.multiplier ?? props.copyTrade?.multiplier ?? 1;
    };

    const getMasterAmount = (transaction: CopyTradeTransaction) => {
        return transaction.metadata?.master_amount;
    };

    const getMasterPnL = (transaction: CopyTradeTransaction) => {
        return transaction.metadata?.master_pnl;
    };

    const handleClose = () => {
        emit('close');
    };

    const handlePause = () => {
        if (props.copyTrade) emit('pause', props.copyTrade);
    };

    const handleResume = () => {
        if (props.copyTrade) emit('resume', props.copyTrade);
    };

    const handleStop = () => {
        if (props.copyTrade) emit('stop', props.copyTrade);
    };

    watch(() => props.isOpen, (newValue) => {
        if (newValue) filterType.value = 'all';
    });

    watch(() => props.isOpen, (isOpen) => {
        document.body.style.overflow = isOpen ? 'hidden' : '';
    });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm sm:p-4"
                @click.self="handleClose">
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <div
                        v-if="isOpen && copyTrade"
                        class="bg-card w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] sm:max-w-4xl flex flex-col rounded-none sm:rounded-2xl overflow-hidden border-border sm:border relative">

                        <!-- Header -->
                        <div class="px-4 py-4 border-b border-border flex items-center justify-between bg-muted/30 shrink-0">
                            <div class="pr-4">
                                <h2 class="text-lg sm:text-xl font-bold text-card-foreground truncate">Trade History</h2>
                                <p class="text-xs sm:text-sm text-muted-foreground mt-0.5 truncate">
                                    With {{ getTraderName }} (×{{ copyTrade.multiplier }})
                                </p>
                            </div>

                            <button
                                @click="handleClose"
                                class="p-2 hover:bg-muted rounded-lg cursor-pointer transition-colors touch-manipulation cursor-pointer">
                                <X class="w-5 h-5 text-muted-foreground" />
                            </button>
                        </div>

                        <!-- Stats Overview -->
                        <div v-if="stats" class="px-4 py-4 bg-muted/20 border-b border-border shrink-0">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="bg-card border border-border rounded-lg p-3">
                                    <div class="flex items-center gap-1.5 text-muted-foreground text-xs mb-1">
                                        <Activity class="w-3.5 h-3.5" />
                                        <span>Trades</span>
                                    </div>
                                    <p class="text-lg font-bold text-card-foreground">{{ stats.totalTransactions }}</p>
                                </div>

                                <div class="bg-card border border-border rounded-lg p-3">
                                    <div class="flex items-center gap-1.5 text-muted-foreground text-xs mb-1">
                                        <BarChart3 class="w-3.5 h-3.5" />
                                        <span>Win Rate</span>
                                    </div>
                                    <p class="text-lg font-bold text-green-600">{{ stats.winRate.toFixed(1) }}%</p>
                                </div>

                                <div class="bg-card border border-border rounded-lg p-3">
                                    <div class="flex items-center gap-1.5 text-muted-foreground text-xs mb-1">
                                        <TrendingUp class="w-3.5 h-3.5" />
                                        <span>Profit</span>
                                    </div>
                                    <p class="text-lg font-bold text-green-600 truncate">+${{ stats.totalProfit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                                </div>

                                <div class="bg-card border border-border rounded-lg p-3">
                                    <div class="flex items-center gap-1.5 text-muted-foreground text-xs mb-1">
                                        <TrendingDown class="w-3.5 h-3.5" />
                                        <span>Loss</span>
                                    </div>
                                    <p class="text-lg font-bold text-red-600 truncate">-${{ stats.totalLoss.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="px-4 py-3 border-b border-border bg-muted/10 shrink-0">
                            <div class="flex gap-2 overflow-x-auto pb-1">
                                <button
                                    v-for="type in ['all', 'profit', 'loss'] as const"
                                    :key="type"
                                    @click="filterType = type"
                                    :class="[
                                        'px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors touch-manipulation flex-shrink-0 cursor-pointer',
                                        filterType === type
                                            ? (type === 'profit' ? 'bg-green-600 text-white' : type === 'loss' ? 'bg-red-600 text-white' : 'bg-primary text-primary-foreground')
                                            : 'bg-background text-card-foreground border border-border hover:bg-muted'
                                    ]">
                                    <span v-if="type === 'all'">All Trades ({{ stats?.totalTransactions }})</span>
                                    <span v-else-if="type === 'profit'">Wins ({{ stats?.profitTransactions }})</span>
                                    <span v-else>Losses ({{ stats?.lossTransactions }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Transactions List -->
                        <div class="flex-1 overflow-y-auto no-scrollbar overscroll-contain px-4 py-4">
                            <div v-if="transactions.length > 0" class="space-y-3 pb-4">
                                <div
                                    v-for="transaction in transactions"
                                    :key="transaction.id"
                                    class="bg-background border border-border rounded-lg p-3 sm:p-4 active:bg-muted/20 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <!-- Icon -->
                                        <div :class="[
                                            'w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center flex-shrink-0 mt-1',
                                            isProfit(transaction)
                                                ? 'bg-green-100 text-green-600 dark:bg-green-900/30'
                                                : 'bg-red-100 text-red-600 dark:bg-red-900/30'
                                        ]">
                                            <component
                                                :is="isProfit(transaction) ? TrendingUp : TrendingDown"
                                                class="w-4 h-4 sm:w-5 sm:h-5"
                                            />
                                        </div>

                                        <!-- Details -->
                                        <div class="flex-1 min-w-0">
                                            <!-- Header Section -->
                                            <div class="flex justify-between items-start gap-3 mb-3">
                                                <!-- Left: Title and Date -->
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-semibold text-card-foreground text-sm sm:text-base truncate leading-tight mb-1.5">
                                                        {{ transaction.description || (isProfit(transaction) ? 'Winning Trade' : 'Losing Trade') }}
                                                    </h4>
                                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                        <Calendar class="w-3 h-3 flex-shrink-0" />
                                                        <span class="truncate">{{ formatDate(transaction.created_at) }}</span>
                                                    </div>
                                                </div>

                                                <!-- Right: PnL Amount -->
                                                <div class="text-right flex-shrink-0">
                                                    <div :class="[
                                                        'text-lg sm:text-xl font-bold whitespace-nowrap',
                                                        isProfit(transaction) ? 'text-green-600' : 'text-red-600'
                                                    ]">
                                                        {{ isProfit(transaction) ? '+' : '-' }}${{ Math.abs(getTransactionPnL(transaction)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                                    </div>
                                                    <span v-if="transaction.metadata?.leverage" class="text-xs text-muted-foreground mt-0.5 inline-block">
                                                        {{ transaction.metadata.leverage }}x Leverage
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Metadata Section -->
                                            <div v-if="transaction.metadata" class="pt-3 border-t border-border/50">
                                                <div class="grid grid-cols-2 gap-2 text-xs">
                                                    <!-- Asset -->
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Asset</span>
                                                        <span class="font-medium text-card-foreground truncate">
                                                            {{ transaction.metadata.pair_name || transaction.metadata.pair || transaction.metadata.category || 'N/A' }}
                                                        </span>
                                                    </div>

                                                    <!-- Trade Direction -->
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Direction</span>
                                                        <span class="font-medium text-card-foreground truncate uppercase">
                                                            {{ transaction.type }}
                                                        </span>
                                                    </div>

                                                    <!-- Duration -->
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Duration</span>
                                                        <span class="font-medium text-card-foreground truncate">
                                                            {{ getDurationDisplay(transaction) }}
                                                        </span>
                                                    </div>

                                                    <!-- Copy Multiplier -->
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Multiplier</span>
                                                        <span class="font-medium text-primary">
                                                            ×{{ getMultiplier(transaction) }}
                                                        </span>
                                                    </div>

                                                    <!-- Your Trade Amount -->
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Your Amount</span>
                                                        <span class="font-medium text-card-foreground">
                                                            ${{ parseFloat(transaction.amount as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                                        </span>
                                                    </div>

                                                    <!-- Master's Amount -->
                                                    <div v-if="getMasterAmount(transaction)" class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Master Amount</span>
                                                        <span class="font-medium text-muted-foreground">
                                                            ${{ parseFloat(String(getMasterAmount(transaction))).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                                        </span>
                                                    </div>

                                                    <!-- Entry Price -->
                                                    <div v-if="transaction.metadata.entry_price !== undefined" class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Entry Price</span>
                                                        <span class="font-medium text-card-foreground">
                                                            ${{ formatNumber(transaction.metadata.entry_price, 5) }}
                                                        </span>
                                                    </div>

                                                    <!-- Exit Price -->
                                                    <div v-if="transaction.metadata.exit_price !== undefined" class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Exit Price</span>
                                                        <span class="font-medium text-card-foreground">
                                                            ${{ formatNumber(transaction.metadata.exit_price, 5) }}
                                                        </span>
                                                    </div>

                                                    <!-- Status (if no exit price) -->
                                                    <div v-else class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Status</span>
                                                        <span class="font-medium capitalize truncate" :class="transaction.metadata.status === 'Closed' ? 'text-muted-foreground' : 'text-primary'">
                                                            {{ transaction.metadata.status || 'Completed' }}
                                                        </span>
                                                    </div>

                                                    <!-- Master's PnL (if available) -->
                                                    <div v-if="getMasterPnL(transaction) !== undefined" class="flex flex-col gap-0.5">
                                                        <span class="text-muted-foreground">Master PnL</span>
                                                        <span class="font-medium" :class="getMasterPnL(transaction) >= 0 ? 'text-green-600' : 'text-red-600'">
                                                            {{ getMasterPnL(transaction) >= 0 ? '+' : '' }}${{ Math.abs(getMasterPnL(transaction)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="flex flex-col items-center justify-center text-center py-12 px-4">
                                <Activity class="w-12 h-12 text-muted-foreground/30 mb-4" />
                                <h3 class="text-lg font-semibold text-card-foreground mb-2">No Trades Yet</h3>
                                <p class="text-sm text-muted-foreground max-w-[250px]">Trades will appear here once trading begins.</p>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="p-4 border-t border-border bg-muted/10 shrink-0 safe-area-pb">
                            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                                <template v-if="copyTrade.status === 'active'">
                                    <button
                                        @click="handlePause"
                                        class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-3 sm:py-2 bg-yellow-600 text-white rounded-lg text-sm font-semibold hover:bg-yellow-700 active:scale-[0.98] transition-transform touch-manipulation cursor-pointer">
                                        <Pause class="w-4 h-4" />
                                        Pause Copying
                                    </button>

                                    <button
                                        @click="handleStop"
                                        class="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 px-4 py-3 sm:py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 active:scale-[0.98] transition-transform touch-manipulation cursor-pointer">
                                        <StopCircle class="w-4 h-4" />
                                        Stop Copying
                                    </button>
                                </template>

                                <template v-else-if="copyTrade.status === 'paused'">
                                    <button
                                        @click="handleResume"
                                        class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 py-3 sm:py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 active:scale-[0.98] transition-transform touch-manipulation cursor-pointer"
                                    >
                                        <Play class="w-4 h-4" />
                                        Resume Copying
                                    </button>
                                </template>

                                <template v-else-if="copyTrade.status === 'stopped'">
                                    <div class="w-full p-3 bg-red-50 border border-red-100 rounded-lg flex items-start gap-3">
                                        <AlertTriangleIcon class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                        <div class="text-left">
                                            <p class="text-sm font-bold text-red-900">Copy Trading Stopped</p>
                                            <p class="text-xs text-red-700 mt-0.5 leading-relaxed">
                                                This copy trading relationship has been permanently terminated. To copy this trader again, you must start a new copy trade from the network page.
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    /* Handle Safe Area for iPhone Home Indicator */
    .safe-area-pb {
        padding-bottom: max(1rem, env(safe-area-inset-bottom));
    }
</style>
