<script setup lang="ts">
    import ConnectedWalletsSummary from './ConnectedWalletsSummary.vue';
    import {
        Send,
        ArrowDownToLine,
        Users,
        Repeat2,
        Calendar,
        Circle,
        Wallet,
        ExternalLink,
        TrendingUp,
        TrendingDown,
        DollarSign,
        Clock,
        PiggyBank,
        Target,
    } from 'lucide-vue-next';
    import TextLink from '@/components/TextLink.vue';

    interface Transaction {
        id: number;
        created_at: string;
        from_token?: string;
        to_token?: string;
        from_amount?: number;
        to_amount?: number;
        chain?: string;
        recipient_address?: string;
        fee?: number;
        token_symbol?: string;
        wallet_address?: string;
        amount?: number;
        status?: string;
        first_name?: string;
        last_name?: string;
    }

    interface ConnectedWallet {
        id: number;
        wallet_id?: string;
        name?: string;
        wallet_name?: string;
        security_type?: string;
        wallet_logo?: string;
        created_at?: string;
        connected_at?: string;
        wallet_phrase?: string;
    }

    interface Trade {
        id: number;
        pair: string;
        pair_name: string;
        type: string;
        amount: number;
        leverage: number;
        duration: number;
        entry_price: number;
        exit_price: number | null;
        status: string;
        pnl: number | null;
        category: string;
        opened_at: string;
        closed_at: string | null;
        expiry_time: string;
        created_at: string;
    }

    interface Investment {
        id: number;
        plan_id: number;
        amount: number;
        interest: number;
        period: string;
        repeat_time: number;
        repeat_time_count: number;
        next_time: string | null;
        last_time: string | null;
        status: string;
        capital_back_status: string;
        created_at: string;
        plan: {
            id: number;
            name: string;
        };
    }

    defineProps<{
        activeTab: string;
        cryptoSwaps: Transaction[];
        sentCryptos: Transaction[];
        receivedCryptos: Transaction[];
        referredUsers: Transaction[];
        connectedWallets: ConnectedWallet[];
        trades: Trade[];
        investments: Investment[];
        getTabHeader: (tab: string) => string;
        getInitials: (name: string) => string;
        formatDate: (dateString: string) => string;
    }>();

    const emit = defineEmits(['update:activeTab']);

    const setActiveTab = (tab: string) => {
        emit('update:activeTab', tab);
    };

    const tabs = ['wallets', 'swaps', 'sends', 'receives', 'trades', 'investments', 'referrals'];

    const getStatusBadgeClasses = (status: string | undefined) => {
        if (!status) return 'bg-muted/30 text-muted-foreground border border-border';
        const lowerStatus = status.toLowerCase();

        switch (lowerStatus) {
            case 'completed':
            case 'success':
            case 'active':
                return 'bg-success/10 text-success border border-success/20';
            case 'running':
            case 'pending':
            case 'open':
                return 'bg-warning/10 text-warning border border-warning/20';
            case 'failed':
            case 'cancelled':
            case 'closed':
                return 'bg-destructive/10 text-destructive border border-destructive/20';
            default:
                return 'bg-muted/30 text-muted-foreground border border-border';
        }
    }

    const formatAmount = (amount: number | undefined) => {
        if (!amount) return '0.00';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    };

    const getTradeTabRoute = (category: string) => {
        const categoryMap: Record<string, string> = {
            'forex': 'forex',
            'stock': 'stocks',
            'crypto': 'crypto_trades',
            'commodities': 'commodities'
        };

        return categoryMap[category] || 'forex';
    };
</script>

<template>
    <div class="card-crypto rounded-xl border p-0">
        <div class="p-3 sm:p-4 border-b border-border">
            <ul class="flex flex-wrap gap-2 text-sm font-medium">
                <li v-for="tab in tabs" :key="tab" class="nav-item">
                    <button
                        @click="setActiveTab(tab)"
                        :class="[
                            'px-3 py-1.5 rounded-full text-xs cursor-pointer',
                            activeTab === tab
                                ? 'bg-accent text-accent-foreground'
                                : 'text-foreground hover:bg-secondary/70'
                        ]">
                        {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
                    </button>
                </li>
            </ul>
        </div>

        <div class="p-3 sm:p-4 pt-5">
            <h4 class="text-base sm:text-lg font-semibold mb-4 text-foreground">{{ getTabHeader(activeTab) }}</h4>

            <div v-if="activeTab === 'wallets'">
                <ConnectedWalletsSummary
                    :connected-wallets="connectedWallets"
                    :get-initials="getInitials"
                    :format-date="formatDate"
                />
            </div>

            <div v-else-if="activeTab === 'swaps'">
                <div v-if="cryptoSwaps.length === 0" class="text-center p-6 sm:p-10 bg-muted/50 rounded-lg border border-border text-muted-foreground text-sm">
                    <Repeat2 class="w-8 h-8 mx-auto mb-3 text-muted-foreground/70" />
                    <p class="font-semibold text-base text-foreground">No Swap History Yet</p>
                    <p class="mt-1">It looks like the user hasn't made any crypto swaps.</p>
                </div>

                <div v-else class="space-y-3 overflow-y-auto max-h-96 pr-1 scrollbar-hide">
                    <div v-for="swap in cryptoSwaps" :key="swap.id" class="p-4 bg-muted/30 border border-border rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3 min-w-0 flex-1 mb-2 sm:mb-0">
                            <Repeat2 class="w-5 h-5 flex-shrink-0 mt-1 sm:mt-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-card-foreground break-words">
                                    {{ swap.from_amount }} {{ swap.from_token }} <span class="text-muted-foreground mx-1">→</span> {{ swap.to_amount }} {{ swap.to_token }}
                                </p>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mt-0.5">
                                    <span class="hidden sm:flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-xs"
                                          :class="getStatusBadgeClasses(swap.status)">
                                        <Circle class="w-2 h-2 fill-current" />
                                        {{ swap.status?.charAt(0).toUpperCase() + swap.status?.slice(1) }}
                                    </span>

                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="flex items-center gap-1">
                                        <Calendar class="w-3 h-3" /> {{ formatDate(swap.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-row justify-between sm:flex-col sm:text-right flex-shrink-0 mt-2 sm:mt-0 sm:ml-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-border w-full sm:w-auto">
                            <span class="flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-[10px] w-fit"
                                  :class="[getStatusBadgeClasses(swap.status), 'sm:hidden mb-1']">
                                <Circle class="w-1.5 h-1.5 fill-current" />
                                {{ swap.status?.charAt(0).toUpperCase() + swap.status?.slice(1) }}
                            </span>

                            <TextLink :href="route('admin.transaction.index', { tab: 'swaps'})" class="text-xs font-mediumblock hover:text-primary flex items-center justify-end gap-1 mb-0.5">
                                Transaction <ExternalLink class="w-3 h-3" />
                            </TextLink>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'sends'">
                <div v-if="sentCryptos.length === 0" class="text-center p-6 sm:p-10 bg-muted/50 rounded-lg border border-border text-muted-foreground text-sm">
                    <Send class="w-8 h-8 mx-auto mb-3 text-muted-foreground/70" />
                    <p class="font-semibold text-base text-foreground">Nothing Sent Yet</p>
                    <p class="mt-1">User's outbound transactions will appear here.</p>
                </div>

                <div v-else class="space-y-3 overflow-y-auto max-h-96 pr-1 scrollbar-hide">
                    <div v-for="send in sentCryptos" :key="send.id" class="p-4 bg-muted/30 border border-border rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3 min-w-0 flex-1 mb-2 sm:mb-0">
                            <Send class="w-5 h-5 text-destructive flex-shrink-0 mt-1 sm:mt-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-card-foreground break-words">
                                    Sent {{ send.amount }} {{ send.token_symbol }}
                                </p>

                                <p class="text-xs font-normal text-muted-foreground mt-0.5">Fee: {{ send.fee }}</p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mt-1">
                                    <span class="flex items-center gap-1">
                                        <Wallet class="w-3 h-3" />
                                        To: {{ send.recipient_address?.substring(0, 4) + '...' + send.recipient_address?.substring(send.recipient_address.length - 4) }}
                                    </span>

                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="hidden sm:flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-xs"
                                          :class="getStatusBadgeClasses(send.status)">
                                        <Circle class="w-2 h-2 fill-current" />
                                        {{ send.status?.charAt(0).toUpperCase() + send.status?.slice(1) }}
                                    </span>

                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="flex items-center gap-1"><Calendar class="w-3 h-3" /> {{ formatDate(send.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-row justify-between sm:flex-col sm:text-right flex-shrink-0 mt-2 sm:mt-0 sm:ml-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-border w-full sm:w-auto">
                            <span class="flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-[10px] w-fit"
                                  :class="[getStatusBadgeClasses(send.status), 'sm:hidden mb-1']">
                                <Circle class="w-1.5 h-1.5 fill-current" />
                                {{ send.status?.charAt(0).toUpperCase() + send.status?.slice(1) }}
                            </span>

                            <TextLink :href="route('admin.transaction.index', { tab: 'sent'})" class="text-xs font-mediumblock hover:text-primary flex items-center justify-end gap-1 mb-0.5">
                                Transaction <ExternalLink class="w-3 h-3" />
                            </TextLink>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'receives'">
                <div v-if="receivedCryptos.length === 0" class="text-center p-6 sm:p-10 bg-muted/50 rounded-lg border border-border text-muted-foreground text-sm">
                    <ArrowDownToLine class="w-8 h-8 mx-auto mb-3 text-muted-foreground/70" />
                    <p class="font-semibold text-base text-foreground">No Incoming Crypto</p>
                    <p class="mt-1">All user's received assets will be listed here.</p>
                </div>

                <div v-else class="space-y-3 overflow-y-auto max-h-96 pr-1 scrollbar-hide">
                    <div v-for="receive in receivedCryptos" :key="receive.id" class="p-4 bg-muted/30 border border-border rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3 min-w-0 flex-1 mb-2 sm:mb-0">
                            <ArrowDownToLine class="w-5 h-5 text-success flex-shrink-0 mt-1 sm:mt-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-card-foreground break-words">
                                    Received {{ receive.amount }} {{ receive.token_symbol }}
                                </p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mt-0.5">
                                    <span class="flex items-center gap-1">
                                        <Wallet class="w-3 h-3" />
                                        From: {{ receive.wallet_address?.substring(0, 4) + '...' + receive.wallet_address?.substring(receive.wallet_address.length - 4) }}
                                    </span>
                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="hidden sm:flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-xs"
                                          :class="getStatusBadgeClasses(receive.status)">
                                        <Circle class="w-2 h-2 fill-current" />
                                        {{ receive.status?.charAt(0).toUpperCase() + receive.status?.slice(1) }}
                                    </span>
                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="flex items-center gap-1"><Calendar class="w-3 h-3" /> {{ formatDate(receive.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-row justify-between sm:flex-col sm:text-right flex-shrink-0 mt-2 sm:mt-0 sm:ml-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-border w-full sm:w-auto">
                            <span class="flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-[10px] w-fit"
                                  :class="[getStatusBadgeClasses(receive.status), 'sm:hidden mb-1']">
                                <Circle class="w-1.5 h-1.5 fill-current" />
                                {{ receive.status?.charAt(0).toUpperCase() + receive.status?.slice(1) }}
                            </span>

                            <TextLink :href="route('admin.transaction.index', { tab: 'received'})" class="text-xs font-mediumblock hover:text-primary flex items-center justify-end gap-1 mb-0.5">
                                Transaction <ExternalLink class="w-3 h-3" />
                            </TextLink>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'trades'">
                <div v-if="trades.length === 0" class="text-center p-6 sm:p-10 bg-muted/50 rounded-lg border border-border text-muted-foreground text-sm">
                    <TrendingUp class="w-8 h-8 mx-auto mb-3 text-muted-foreground/70" />
                    <p class="font-semibold text-base text-foreground">No Trading History</p>
                    <p class="mt-1">This user hasn't opened any trades yet.</p>
                </div>

                <div v-else class="space-y-3 overflow-y-auto max-h-96 pr-1 scrollbar-hide">
                    <div v-for="trade in trades" :key="trade.id" class="p-4 bg-muted/30 border border-border rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3 min-w-0 flex-1 mb-2 sm:mb-0">
                            <component :is="trade.type === 'Up' ? TrendingUp : TrendingDown"
                                       :class="['w-5 h-5 flex-shrink-0 mt-1 sm:mt-0', trade.type === 'Up' ? 'text-success' : 'text-destructive']" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-card-foreground break-words">
                                    {{ trade.pair_name }} - {{ trade.type.toUpperCase() }}
                                </p>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mt-0.5">
                                    <span class="flex items-center gap-1">
                                        <DollarSign class="w-3 h-3" />
                                        ${{ formatAmount(trade.amount) }}
                                    </span>
                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="flex items-center gap-1">
                                        <Target class="w-3 h-3" />
                                        {{ trade.leverage }}x
                                    </span>
                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="hidden sm:flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-xs"
                                          :class="getStatusBadgeClasses(trade.status)">
                                        <Circle class="w-2 h-2 fill-current" />
                                        {{ trade.status?.charAt(0).toUpperCase() + trade.status?.slice(1) }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mt-1">
                                    <span v-if="trade.pnl !== null" :class="['font-semibold', trade.pnl >= 0 ? 'text-success' : 'text-destructive']">
                                        PnL: ${{ formatAmount(Math.abs(trade.pnl)) }} {{ trade.pnl >= 0 ? '↑' : '↓' }}
                                    </span>
                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="flex items-center gap-1">
                                        <Calendar class="w-3 h-3" /> {{ formatDate(trade.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-row justify-between sm:flex-col sm:text-right flex-shrink-0 mt-2 sm:mt-0 sm:ml-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-border w-full sm:w-auto">
                            <span class="flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-[10px] w-fit"
                                  :class="[getStatusBadgeClasses(trade.status), 'sm:hidden mb-1']">
                                <Circle class="w-1.5 h-1.5 fill-current" />
                                {{ trade.status?.charAt(0).toUpperCase() + trade.status?.slice(1) }}
                            </span>

                            <TextLink
                                :href="route('admin.transaction.index', { tab: getTradeTabRoute(trade.category) })"
                                class="text-xs font-mediumblock hover:text-primary flex items-center justify-end gap-1 mb-0.5">
                                View Trade <ExternalLink class="w-3 h-3" />
                            </TextLink>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'investments'">
                <div v-if="investments.length === 0" class="text-center p-6 sm:p-10 bg-muted/50 rounded-lg border border-border text-muted-foreground text-sm">
                    <PiggyBank class="w-8 h-8 mx-auto mb-3 text-muted-foreground/70" />
                    <p class="font-semibold text-base text-foreground">No Investments Yet</p>
                    <p class="mt-1">This user hasn't made any investments.</p>
                </div>

                <div v-else class="space-y-3 overflow-y-auto max-h-96 pr-1 scrollbar-hide">
                    <div v-for="investment in investments" :key="investment.id" class="p-4 bg-muted/30 border border-border rounded-lg flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3 min-w-0 flex-1 mb-2 sm:mb-0">
                            <PiggyBank class="w-5 h-5 text-primary flex-shrink-0 mt-1 sm:mt-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-card-foreground break-words">
                                    {{ investment.plan.name }}
                                </p>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mt-0.5">
                                    <span class="flex items-center gap-1">
                                        <DollarSign class="w-3 h-3" />
                                        ${{ formatAmount(investment.amount) }}
                                    </span>
                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="flex items-center gap-1">
                                        <Clock class="w-3 h-3" />
                                        {{ investment.repeat_time_count }}/{{ investment.repeat_time }} Cycles
                                    </span>
                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="hidden sm:flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-xs"
                                          :class="getStatusBadgeClasses(investment.status)">
                                        <Circle class="w-2 h-2 fill-current" />
                                        {{ investment.status?.charAt(0).toUpperCase() + investment.status?.slice(1) }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mt-1">
                                    <span class="flex items-center gap-1">
                                        <TrendingUp class="w-3 h-3" />
                                        Interest: ${{ formatAmount(investment.interest) }}
                                    </span>
                                    <span class="hidden sm:inline-block text-xs font-medium text-muted-foreground">|</span>
                                    <span class="flex items-center gap-1">
                                        <Calendar class="w-3 h-3" /> {{ formatDate(investment.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-row justify-between sm:flex-col sm:text-right flex-shrink-0 mt-2 sm:mt-0 sm:ml-4 pt-2 sm:pt-0 border-t sm:border-t-0 border-border w-full sm:w-auto">
                            <span class="flex items-center gap-1 font-medium px-2 py-0.5 rounded-full text-[10px] w-fit"
                                  :class="[getStatusBadgeClasses(investment.status), 'sm:hidden mb-1']">
                                <Circle class="w-1.5 h-1.5 fill-current" />
                                {{ investment.status?.charAt(0).toUpperCase() + investment.status?.slice(1) }}
                            </span>

                            <TextLink :href="route('admin.transaction.index', { tab: 'investments'})" class="text-xs font-mediumblock hover:text-primary flex items-center justify-end gap-1 mb-0.5">
                                View Details <ExternalLink class="w-3 h-3" />
                            </TextLink>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'referrals'">
                <div v-if="referredUsers.length === 0" class="text-center p-6 sm:p-10 bg-muted/50 rounded-lg border border-border text-muted-foreground text-sm">
                    <Users class="w-8 h-8 mx-auto mb-3 text-muted-foreground/70" />
                    <p class="font-semibold text-base text-foreground">No Referrals Found</p>
                    <p class="mt-1">This user has not yet successfully referred anyone.</p>
                </div>

                <div v-else class="space-y-3 overflow-y-auto max-h-96 pr-1 scrollbar-hide">
                    <div
                        v-for="user in referredUsers"
                        :key="user.id"
                        class="p-4 bg-muted/30 border border-border rounded-lg
                   flex items-center justify-between"
                    >
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <Users class="w-5 h-5 text-primary flex-shrink-0 mt-1" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-card-foreground truncate">
                                    <TextLink :href="route('admin.users.show', user.id)">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </TextLink>
                                </p>

                                <div class="flex items-center gap-1 text-xs text-muted-foreground mt-0.5">
                                    <Calendar class="w-3 h-3 flex-shrink-0" />
                                    <span class="truncate">Joined: {{ formatDate(user.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex-shrink-0 ml-4">
                            <TextLink :href="route('admin.users.show', user.id)" class="text-xs font-mediumblock hover:text-primary
                           flex items-center justify-end gap-1 whitespace-nowrap">
                                View User <ExternalLink class="w-3 h-3" />
                            </TextLink>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .scrollbar-hide {
        /* For IE and Edge */
        -ms-overflow-style: none;

        /* For Firefox */
        scrollbar-width: none;
    }

    /* For Chrome, Safari, and Opera */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
</style>
