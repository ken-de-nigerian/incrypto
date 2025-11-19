<script setup lang="ts">
    import { computed, watch, ref } from 'vue';
    import { X, TrendingUp, TrendingDown, Calendar, DollarSign, BarChart3, Activity, Pause, Play, StopCircle } from 'lucide-vue-next';

    interface CopyTradeTransaction {
        id: number;
        copy_trade_id: number;
        type: 'up' | 'down';
        amount: number | string;
        description: string | null;
        metadata: {
            asset_pair?: string;
            strategy?: string;
            entry_price?: number;
            exit_price?: number;
            position_size?: number;
            leverage?: number;
            holding_time_minutes?: number;
        } | null;
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

    const filterType = ref<'all' | 'up' | 'down'>('all');

    const transactions = computed(() => {
        if (!props.copyTrade?.transactions) return [];

        let filtered = [...props.copyTrade.transactions];

        if (filterType.value !== 'all') {
            filtered = filtered.filter(t => t.type === filterType.value);
        }

        return filtered.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
    });

    const stats = computed(() => {
        if (!props.copyTrade) return null;

        const totalTransactions = transactions.value.length;
        const profitTransactions = transactions.value.filter(t => t.type === 'up').length;
        const lossTransactions = transactions.value.filter(t => t.type === 'down').length;
        const totalProfit = transactions.value
            .filter(t => t.type === 'up')
            .reduce((sum, t) => sum + parseFloat(t.amount as string), 0);
        const totalLoss = transactions.value
            .filter(t => t.type === 'down')
            .reduce((sum, t) => sum + parseFloat(t.amount as string), 0);
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

    const formatDuration = (minutes: number) => {
        if (minutes < 60) return `${minutes}m`;
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
    };

    const handleClose = () => {
        emit('close');
    };

    const handlePause = () => {
        if (props.copyTrade) {
            emit('pause', props.copyTrade);
        }
    };

    const handleResume = () => {
        if (props.copyTrade) {
            emit('resume', props.copyTrade);
        }
    };

    const handleStop = () => {
        if (props.copyTrade) {
            emit('stop', props.copyTrade);
        }
    };

    watch(() => props.isOpen, (newValue) => {
        if (newValue) {
            filterType.value = 'all';
        }
    });

    // Disable body scroll when modal is open
    watch(() => props.isOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
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
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                @click.self="handleClose"
            >
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-95 lg:scale-100 lg:opacity-100"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95 lg:scale-100 lg:opacity-100"
                >
                    <div
                        v-if="isOpen && copyTrade"
                        class="bg-card w-full h-full max-h-full lg:w-full lg:max-w-4xl lg:h-auto lg:max-h-[90vh] flex flex-col rounded-none lg:rounded-2xl shadow-2xl overflow-hidden border-border relative lg:border"
                    >
                        <!-- Header -->
                        <div class="px-4 md:px-6 lg:px-6 py-4 border-b border-border flex items-center justify-between bg-muted/30">
                            <div>
                                <h2 class="text-xl font-bold text-card-foreground">Transaction History</h2>
                                <p class="text-sm text-muted-foreground mt-0.5">
                                    Copy trading with {{ getTraderName }}
                                </p>
                            </div>
                            <button
                                @click="handleClose"
                                class="p-2 hover:bg-muted rounded-lg"
                            >
                                <X class="w-5 h-5 text-muted-foreground" />
                            </button>
                        </div>

                        <!-- Stats Overview -->
                        <div v-if="stats" class="px-4 md:px-6 lg:px-6 py-4 bg-muted/20 border-b border-border">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-card border border-border rounded-lg p-3">
                                    <div class="flex items-center gap-2 text-muted-foreground text-xs mb-1">
                                        <Activity class="w-3.5 h-3.5" />
                                        <span>Total Trades</span>
                                    </div>
                                    <p class="text-lg font-bold text-card-foreground">{{ stats.totalTransactions }}</p>
                                </div>

                                <div class="bg-card border border-border rounded-lg p-3">
                                    <div class="flex items-center gap-2 text-muted-foreground text-xs mb-1">
                                        <BarChart3 class="w-3.5 h-3.5" />
                                        <span>Win Rate</span>
                                    </div>
                                    <p class="text-lg font-bold text-green-600">{{ stats.winRate.toFixed(1) }}%</p>
                                </div>

                                <div class="bg-card border border-border rounded-lg p-3">
                                    <div class="flex items-center gap-2 text-muted-foreground text-xs mb-1">
                                        <TrendingUp class="w-3.5 h-3.5" />
                                        <span>Total Profit</span>
                                    </div>
                                    <p class="text-lg font-bold text-green-600">+${{ stats.totalProfit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                                </div>

                                <div class="bg-card border border-border rounded-lg p-3">
                                    <div class="flex items-center gap-2 text-muted-foreground text-xs mb-1">
                                        <TrendingDown class="w-3.5 h-3.5" />
                                        <span>Total Loss</span>
                                    </div>
                                    <p class="text-lg font-bold text-red-600">-${{ stats.totalLoss.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="px-4 md:px-6 lg:px-6 py-3 border-b border-border bg-muted/10">
                            <div class="flex gap-2">
                                <button
                                    @click="filterType = 'all'"
                                    :class="[
                                        'px-4 md:px-6 lg:px-6 py-2 rounded-lg text-sm font-semibold',
                                        filterType === 'all'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-background text-card-foreground hover:bg-muted'
                                    ]"
                                >
                                    All ({{ transactions.length }})
                                </button>
                                <button
                                    @click="filterType = 'up'"
                                    :class="[
                                        'px-4 md:px-6 lg:px-6 py-2 rounded-lg text-sm font-semibold',
                                        filterType === 'up'
                                            ? 'bg-green-600 text-white'
                                            : 'bg-background text-card-foreground hover:bg-muted'
                                    ]"
                                >
                                    Profits ({{ stats?.profitTransactions }})
                                </button>
                                <button
                                    @click="filterType = 'down'"
                                    :class="[
                                        'px-4 md:px-6 lg:px-6 py-2 rounded-lg text-sm font-semibold',
                                        filterType === 'down'
                                            ? 'bg-red-600 text-white'
                                            : 'bg-background text-card-foreground hover:bg-muted'
                                    ]"
                                >
                                    Losses ({{ stats?.lossTransactions }})
                                </button>
                            </div>
                        </div>

                        <!-- Transactions List -->
                        <div class="flex-1 overflow-y-auto no-scrollbar px-4 md:px-6 lg:px-6 py-4">
                            <div v-if="transactions.length > 0" class="space-y-3">
                                <div
                                    v-for="transaction in transactions"
                                    :key="transaction.id"
                                    class="bg-background border border-border rounded-lg p-4 hover:border-primary/30"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <!-- Left Section -->
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div :class="[
                                                    'w-10 h-10 rounded-full flex items-center justify-center',
                                                    transaction.type === 'up'
                                                        ? 'bg-green-100 text-green-600'
                                                        : 'bg-red-100 text-red-600'
                                                ]">
                                                    <component
                                                        :is="transaction.type === 'up' ? TrendingUp : TrendingDown"
                                                        class="w-5 h-5"
                                                    />
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="font-semibold text-card-foreground">
                                                        {{ transaction.description || (transaction.type === 'up' ? 'Profit Trade' : 'Loss Trade') }}
                                                    </h4>
                                                    <div class="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                                                        <Calendar class="w-3 h-3" />
                                                        <span>{{ formatDate(transaction.created_at) }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Metadata -->
                                            <div v-if="transaction.metadata" class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-3">
                                                <div v-if="transaction.metadata.asset_pair" class="text-xs">
                                                    <span class="text-muted-foreground">Asset:</span>
                                                    <span class="ml-1 font-semibold text-card-foreground">{{ transaction.metadata.asset_pair }}</span>
                                                </div>
                                                <div v-if="transaction.metadata.strategy" class="text-xs">
                                                    <span class="text-muted-foreground">Strategy:</span>
                                                    <span class="ml-1 font-semibold text-card-foreground">{{ transaction.metadata.strategy }}</span>
                                                </div>
                                                <div v-if="transaction.metadata.leverage" class="text-xs">
                                                    <span class="text-muted-foreground">Leverage:</span>
                                                    <span class="ml-1 font-semibold text-card-foreground">{{ transaction.metadata.leverage }}x</span>
                                                </div>
                                                <div v-if="transaction.metadata.entry_price" class="text-xs">
                                                    <span class="text-muted-foreground">Entry:</span>
                                                    <span class="ml-1 font-semibold text-card-foreground">${{ transaction.metadata.entry_price.toFixed(2) }}</span>
                                                </div>
                                                <div v-if="transaction.metadata.exit_price" class="text-xs">
                                                    <span class="text-muted-foreground">Exit:</span>
                                                    <span class="ml-1 font-semibold text-card-foreground">${{ transaction.metadata.exit_price.toFixed(2) }}</span>
                                                </div>
                                                <div v-if="transaction.metadata.holding_time_minutes" class="text-xs">
                                                    <span class="text-muted-foreground">Duration:</span>
                                                    <span class="ml-1 font-semibold text-card-foreground">{{ formatDuration(transaction.metadata.holding_time_minutes) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Section - Amount -->
                                        <div class="text-right">
                                            <div class="flex items-center gap-1">
                                                <DollarSign class="w-4 h-4" :class="transaction.type === 'up' ? 'text-green-600' : 'text-red-600'" />
                                                <span :class="[
                                                    'text-lg font-bold',
                                                    transaction.type === 'up' ? 'text-green-600' : 'text-red-600'
                                                ]">
                                                    {{ transaction.type === 'up' ? '+' : '-' }}${{ parseFloat(transaction.amount as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                                </span>
                                            </div>
                                            <span v-if="transaction.metadata?.position_size" class="text-xs text-muted-foreground">
                                                Size: {{ parseFloat(transaction.metadata.position_size as number).toFixed(2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="flex flex-col items-center justify-center text-center py-12">
                                <Activity class="w-16 h-16 text-muted-foreground/50 mb-4" />
                                <h3 class="text-lg font-semibold text-card-foreground mb-2">No Transactions Yet</h3>
                                <p class="text-sm text-muted-foreground">Transactions will appear here once trading begins.</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-4 md:px-6 lg:px-6 py-4 border-t border-border bg-muted/10">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <!-- Action Buttons based on status -->
                                    <template v-if="copyTrade.status === 'active'">
                                        <button
                                            @click="handlePause"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm font-semibold hover:bg-yellow-700 cursor-pointer"
                                        >
                                            <Pause class="w-4 h-4" />
                                            Pause
                                        </button>
                                        <button
                                            @click="handleStop"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 cursor-pointer"
                                        >
                                            <StopCircle class="w-4 h-4" />
                                            Stop
                                        </button>
                                    </template>
                                    <template v-else-if="copyTrade.status === 'paused'">
                                        <button
                                            @click="handleResume"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 cursor-pointer"
                                        >
                                            <Play class="w-4 h-4" />
                                            Resume
                                        </button>
                                    </template>
                                    <template v-else-if="copyTrade.status === 'stopped'">
                                        <span class="text-sm text-red-600 font-semibold">Copy Trading Stopped</span>
                                    </template>
                                </div>
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
</style>
