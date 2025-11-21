<script setup lang="ts">
    import { computed, ref } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    import {
        TrendingUp,
        TrendingDown,
        BarChart3,
        Activity,
        DollarSign as DollarSignIcon,
        CheckCircle as CheckCircleIcon,
        XCircle as XCircleIcon,
        Clock as ClockIcon,
        Eye as EyeIcon,
        Users as UsersIcon,
        Calendar
    } from 'lucide-vue-next';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import CopyTradeTransactionsModal from '@/components/CopyTradeTransactionsModal.vue';
    import PaginationControls from '@/components/PaginationControls.vue';
    import TextLink from '@/components/TextLink.vue';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import FundingModal from '@/components/FundingModal.vue';
    import WithdrawalModal from '@/components/WithdrawalModal.vue';
    import { useFlash } from '@/composables/useFlash';
    import WalletBalanceCard from '@/components/WalletBalanceCard.vue';

    interface Token {
        symbol: string;
        name: string;
        logo: string;
        decimals: number;
        price_change_24h: number;
    }

    interface UserProfile {
        live_trading_balance: number | string;
        demo_trading_balance: number | string;
        trading_status: 'live' | 'demo';
    }

    interface User {
        id: number;
        first_name: string;
        last_name: string;
        profile: UserProfile;
    }

    interface MasterTrader {
        id: number;
        user_id: number;
        expertise: 'Newcomer' | 'Growing talent' | 'High achiever' | 'Expert' | 'Legend';
        risk_score: number | string;
        gain_percentage: number | string;
        copiers_count: number | string;
        commission_rate: number | string | null;
        total_profit: number | string;
        total_loss: number | string;
        is_active: boolean;
        bio: string | null;
        total_trades: number | string;
        win_rate: number | string;
        user: User | null;
    }

    interface CopyTradeTransaction {
        id: number;
        copy_trade_id: number;
        type: 'up' | 'down';
        amount: number;
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

    interface CopyTrade {
        id: number;
        user_id: number;
        master_trader_id: number;
        current_profit: number | string;
        current_loss: number | string;
        total_commission_paid: number | string;
        status: 'active' | 'paused' | 'stopped';
        started_at: string;
        paused_at: string | null;
        stopped_at: string | null;
        master_trader: MasterTrader | null;
        transactions?: CopyTradeTransaction[];
    }

    interface PaginatedData<T> {
        current_page: number;
        data: T[];
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: { url: string | null; label: string; active: boolean; }[];
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    }

    const props = defineProps<{
        tokens: Array<Token>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        copyTrades: PaginatedData<CopyTrade>;
        stats: {
            total_active: number;
            total_profit: number;
            total_loss: number;
            total_commission: number;
            net_profit: number;
            active_traders: number;
        };
        auth: {
            user: User;
            notification_count: number;
        };
    }>();

    const isNotificationsModalOpen = ref(false);
    const isFundingModalOpen = ref(false);
    const isWithdrawalModalOpen = ref(false);
    const isTransactionsModalOpen = ref(false);
    const selectedCopyTrade = ref<CopyTrade | null>(null);
    const statusFilter = ref<string>('all');
    const searchQuery = ref('');
    const sortBy = ref<string>('newest');

    const page = usePage();
    const { notify } = useFlash();
    const user = computed(() => page.props.auth?.user as User);
    const userProfile = computed(() => user.value?.profile as UserProfile);
    const isLiveMode = ref(userProfile.value?.trading_status === 'live');

    const liveBalance = computed(() => {
        const bal = userProfile.value?.live_trading_balance || 0.00;
        return typeof bal === 'string' ? parseFloat(bal) : bal;
    });

    const demoBalance = computed(() => {
        const bal = userProfile.value?.demo_trading_balance || 0.00;
        return typeof bal === 'string' ? parseFloat(bal) : bal;
    });

    const currentBalance = computed(() => isLiveMode.value ? liveBalance.value : demoBalance.value);
    const notificationCount = computed(() => page.props.auth?.notification_count || 0);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const pricesMap = computed(() => props.prices);

    const holdings = computed(() => {
        return props.tokens.map(token => {
            const balance = props.userBalances[token.symbol] || 0;
            const price = pricesMap.value[token.symbol] || 1;
            const isFiat = token.symbol === 'USD' || token.name.includes('Tether');

            return {
                symbol: token.symbol,
                name: token.name,
                logo: token.logo,
                balance: balance,
                value: balance * price,
                assetType: isFiat ? 'fiat' : 'crypto'
            };
        });
    });

    const cryptoHoldings = computed(() => holdings.value.filter(h => h.assetType === 'crypto'));

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Trading', href: route('user.trade.index') },
        { label: 'Copy Trading', href: route('user.trade.network') },
        { label: 'History' }
    ];

    const filteredCopyTrades = computed(() => {
        let filtered = [...props.copyTrades.data];

        if (statusFilter.value !== 'all') {
            filtered = filtered.filter(t => t.status === statusFilter.value);
        }

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(t =>
                t.master_trader?.user?.first_name?.toLowerCase().includes(query) ||
                t.master_trader?.user?.last_name?.toLowerCase().includes(query) ||
                `${t.master_trader?.user?.first_name} ${t.master_trader?.user?.last_name}`?.toLowerCase().includes(query)
            );
        }

        if (sortBy.value === 'newest') {
            filtered.sort((a, b) => new Date(b.started_at).getTime() - new Date(a.started_at).getTime());
        } else if (sortBy.value === 'oldest') {
            filtered.sort((a, b) => new Date(a.started_at).getTime() - new Date(b.started_at).getTime());
        } else if (sortBy.value === 'profit') {
            filtered.sort((a, b) => getNetProfit(b) - getNetProfit(a));
        } else if (sortBy.value === 'loss') {
            filtered.sort((a, b) => getNetProfit(a) - getNetProfit(b));
        }

        return filtered;
    });

    const getTraderName = (trader: MasterTrader) => {
        if (!trader.user) return 'Unknown Trader';
        return `${trader.user.first_name} ${trader.user.last_name}`;
    };

    const getTraderInitials = (trader: MasterTrader) => {
        if (!trader.user) return '';
        const first = trader.user.first_name?.charAt(0) || '';
        const last = trader.user.last_name?.charAt(0) || '';
        return `${first}${last}`.toUpperCase();
    };

    const getExpertiseColor = (expertise: string) => {
        const colors = {
            'Newcomer': 'bg-gray-100 text-gray-800 border-gray-200',
            'Growing talent': 'bg-blue-100 text-blue-800 border-blue-200',
            'High achiever': 'bg-green-100 text-green-800 border-green-200',
            'Expert': 'bg-cyan-100 text-cyan-800 border-cyan-200',
            'Legend': 'bg-orange-100 text-orange-800 border-orange-200'
        };
        return colors[expertise as keyof typeof colors] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const getStatusBadgeClass = (status: string) => {
        const classes = {
            active: 'bg-green-100 text-green-800 border-green-200',
            paused: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            stopped: 'bg-red-100 text-red-800 border-red-200'
        };
        return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const getStatusIcon = (status: string) => {
        const icons = {
            active: CheckCircleIcon,
            paused: ClockIcon,
            stopped: XCircleIcon
        };
        return icons[status as keyof typeof icons] || ClockIcon;
    };

    const getNetProfit = (trade: CopyTrade) => {
        return parseFloat(trade.current_profit as string) - parseFloat(trade.current_loss as string);
    };

    const viewTransactions = (trade: CopyTrade) => {
        selectedCopyTrade.value = trade;
        isTransactionsModalOpen.value = true;
    };

    const handlePauseCopyTrade = (copyTrade: CopyTrade) => {
        notify('warning', 'Are you sure you want to pause this copy trade?', {
            title: 'Confirm Pause',
            duration: 0,
            dismissible: true,
            action: {
                label: 'Confirm',
                callback: () => {
                    router.patch(route('user.trade.copy.pause', copyTrade.id), {}, {
                        preserveScroll: true,
                        only: ['copyTrades', 'stats'],
                        onSuccess: () => {
                            isTransactionsModalOpen.value = false;
                        },
                        onError: (errors) => {
                            console.error('Failed to pause copy trade:', errors);
                        }
                    });
                }
            }
        });
    };

    const handleResumeCopyTrade = (copyTrade: CopyTrade) => {
        notify('success', 'Are you sure you want to resume this copy trade?', {
            title: 'Confirm Resume',
            duration: 0,
            dismissible: true,
            action: {
                label: 'Confirm',
                callback: () => {
                    router.patch(route('user.trade.copy.resume', copyTrade.id), {}, {
                        preserveScroll: true,
                        only: ['copyTrades', 'stats'],
                        onSuccess: () => {
                            isTransactionsModalOpen.value = false;
                        },
                        onError: (errors) => {
                            console.error('Failed to resume copy trade:', errors);
                        }
                    });
                }
            }
        });
    };

    const handleStopCopyTrade = (copyTrade: CopyTrade) => {
        notify('error', 'This action cannot be undone. Are you sure you want to stop this copy trade?', {
            title: 'Confirm Stop',
            duration: 0,
            dismissible: true,
            action: {
                label: 'Stop Trade',
                callback: () => {
                    router.delete(route('user.trade.copy.stop', copyTrade.id), {
                        preserveScroll: true,
                        only: ['copyTrades', 'stats'],
                        onSuccess: () => {
                            isTransactionsModalOpen.value = false;
                        },
                        onError: (errors) => {
                            console.error('Failed to stop copy trade:', errors);
                        }
                    });
                }
            }
        });
    };

    const handleFundingClick = () => {
        isFundingModalOpen.value = true;
    };

    const handleWithdrawalClick = () => {
        isWithdrawalModalOpen.value = true;
    };

    const goToCopyTradesPage = (url: string) => {
        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['copyTrades'],
        });
    };
</script>

<template>
    <AppLayout>
        <Head title="My Copy Trades" />

        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="isNotificationsModalOpen = true"
            />

            <WalletBalanceCard
                :current-balance="currentBalance"
                v-model:is-live-mode="isLiveMode"
                :live-balance="liveBalance"
                :demo-balance="demoBalance"
                warning-message="Switch to Live Mode to start copy trading with real funds."
                @deposit="handleFundingClick"
                @withdraw="handleWithdrawalClick"
            />

            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4 mt-6 mb-6">
                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Activity class="w-4 h-4 text-primary" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Active</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground">{{ props.stats.total_active }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <UsersIcon class="w-4 h-4 text-cyan-600" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Traders</p>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-card-foreground">{{ props.stats.active_traders }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <TrendingUp class="w-4 h-4 text-green-600" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Profit</p>
                    </div>
                    <p class="text-lg sm:text-2xl font-bold text-green-600 truncate">+${{ props.stats.total_profit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <TrendingDown class="w-4 h-4 text-red-600" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Loss</p>
                    </div>
                    <p class="text-lg sm:text-2xl font-bold text-red-600 truncate">-${{ props.stats.total_loss.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <BarChart3 class="w-4 h-4" :class="props.stats.net_profit >= 0 ? 'text-green-600' : 'text-red-600'" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Net P/L</p>
                    </div>
                    <p class="text-lg sm:text-2xl font-bold truncate" :class="props.stats.net_profit >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ props.stats.net_profit >= 0 ? '+' : '' }}${{ Math.abs(props.stats.net_profit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </p>
                </div>

                <div class="bg-card border border-border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <DollarSignIcon class="w-4 h-4 text-orange-600" />
                        <p class="text-xs text-muted-foreground font-bold uppercase">Fees Paid</p>
                    </div>
                    <p class="text-lg sm:text-2xl font-bold text-card-foreground truncate">${{ props.stats.total_commission.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                </div>
            </div>

            <div class="margin-bottom mt-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                    <h2 class="text-xl sm:text-2xl font-bold text-card-foreground">
                        My Copy Trades
                    </h2>

                    <TextLink :href="route('user.trade.network')" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-3 sm:py-2 bg-background border border-border text-card-foreground rounded-xl text-sm font-semibold hover:bg-muted transition-colors touch-manipulation">
                        <UsersIcon class="w-4 h-4" />
                        <span>Master Traders</span>
                    </TextLink>
                </div>

                <div class="bg-card border border-border rounded-xl overflow-hidden">
                    <div v-if="filteredCopyTrades.length > 0">
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50 border-b border-border">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Trader</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Profit/Loss</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Fee</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Started</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr v-for="trade in filteredCopyTrades" :key="trade.id" class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-secondary flex items-center justify-center text-sm font-bold text-primary">
                                                    {{ getTraderInitials(trade.master_trader!) }}
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-card-foreground">{{ getTraderName(trade.master_trader!) }}</p>
                                                    <span :class="['text-[10px] px-2 py-0.5 rounded-full border font-bold uppercase tracking-wide inline-block mt-1', getExpertiseColor(trade.master_trader?.expertise || 'Newcomer')]">
                                                        {{ trade.master_trader?.expertise || 'Unknown' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4">
                                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold uppercase tracking-wide rounded-full border', getStatusBadgeClass(trade.status)]">
                                                <component :is="getStatusIcon(trade.status)" class="w-3.5 h-3.5" />
                                                {{ trade.status }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs font-medium text-green-600">+${{ parseFloat(trade.current_profit as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                                <span class="text-xs font-medium text-red-600">-${{ parseFloat(trade.current_loss as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                                <span class="text-sm font-bold mt-1" :class="getNetProfit(trade) >= 0 ? 'text-green-600' : 'text-red-600'">
                                                    Net: {{ getNetProfit(trade) >= 0 ? '+' : '' }}${{ Math.abs(getNetProfit(trade)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-sm font-semibold text-card-foreground">${{ parseFloat(trade.total_commission_paid as string).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>

                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                <Calendar class="w-4 h-4" />
                                                <span>{{ new Date(trade.started_at).toLocaleDateString() }}</span>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4">
                                            <button
                                                @click="viewTransactions(trade)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-primary-foreground rounded-lg text-xs font-semibold hover:bg-primary/90 cursor-pointer transition-colors">
                                                <EyeIcon class="w-3.5 h-3.5" />
                                                View Trades
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="lg:hidden p-4 space-y-4">
                            <div v-for="trade in filteredCopyTrades"
                                 :key="trade.id"
                                 class="group bg-background border border-border rounded-xl p-4 hover:border-primary/40 transition-colors">

                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-secondary flex items-center justify-center text-sm font-bold text-primary">
                                            {{ getTraderInitials(trade.master_trader!) }}
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-card-foreground text-sm">
                                                {{ getTraderName(trade.master_trader!) }}
                                            </h3>
                                            <span :class="['text-[10px] px-2 py-0.5 rounded-full border font-bold uppercase tracking-wide inline-block mt-1', getExpertiseColor(trade.master_trader?.expertise || 'Newcomer')]">
                                                {{ trade.master_trader?.expertise || 'Unknown' }}
                                            </span>
                                        </div>
                                    </div>

                                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full border', getStatusBadgeClass(trade.status)]">
                                        <component :is="getStatusIcon(trade.status)" class="w-3 h-3" />
                                        {{ trade.status }}
                                    </span>
                                </div>

                                <div class="bg-muted/30 rounded-lg p-3 grid grid-cols-3 gap-2 mb-4 border border-border/50">
                                    <div class="flex flex-col items-center justify-center text-center border-r border-border/50">
                                        <span class="text-[10px] text-muted-foreground font-semibold mb-1">Net P/L</span>
                                        <span :class="['text-sm font-bold', getNetProfit(trade) >= 0 ? 'text-green-600' : 'text-red-600']">
                                            {{ getNetProfit(trade) >= 0 ? '+' : '' }}${{ Math.abs(getNetProfit(trade)).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
                                        </span>
                                    </div>

                                    <div class="flex flex-col items-center justify-center text-center border-r border-border/50">
                                        <span class="text-[10px] text-muted-foreground font-semibold mb-1">Fees</span>
                                        <span class="text-sm font-bold text-card-foreground">
                                            ${{ parseFloat(trade.total_commission_paid as string).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
                                        </span>
                                    </div>

                                    <div class="flex flex-col items-center justify-center text-center">
                                        <span class="text-[10px] text-muted-foreground font-semibold mb-1">Started</span>
                                        <span class="text-xs font-bold text-card-foreground">
                                            {{ new Date(trade.started_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-end justify-between text-xs mb-4 px-1">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-muted-foreground font-semibold">Profit</span>
                                        <span class="font-bold text-green-600">+${{ parseFloat(trade.current_profit as string).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}</span>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] text-muted-foreground font-semibold">Loss</span>
                                        <span class="font-bold text-red-600">-${{ parseFloat(trade.current_loss as string).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}</span>
                                    </div>
                                </div>

                                <button
                                    @click="viewTransactions(trade)"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-sm bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer active:scale-[0.98] transition-transform touch-manipulation">
                                    <EyeIcon class="w-4 h-4" />
                                    View Trades
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center text-center py-16 px-4">
                        <div class="w-16 h-16 rounded-full bg-muted/50 flex items-center justify-center mb-4">
                            <UsersIcon class="w-8 h-8 text-muted-foreground/50" />
                        </div>

                        <h3 class="text-lg font-bold text-card-foreground mb-2">No Active Trades</h3>

                        <p class="text-sm text-muted-foreground mb-6 max-w-xs mx-auto">
                            {{ statusFilter === 'all' && !searchQuery
                            ? "You haven't started copying any traders yet."
                            : 'No copy trades match your current filters.'
                            }}
                        </p>

                        <TextLink :href="route('user.trade.network')" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-primary-foreground rounded-xl font-semibold hover:bg-primary/90 transition-colors touch-manipulation">
                            <UsersIcon class="w-4 h-4" />
                            Find Traders
                        </TextLink>
                    </div>

                    <PaginationControls
                        v-if="props.copyTrades.last_page > 1"
                        :links="props.copyTrades.links"
                        :from="props.copyTrades.from"
                        :to="props.copyTrades.to"
                        :total="props.copyTrades.total"
                        @go-to-page="goToCopyTradesPage"
                        class="p-4 md:p-6 border-t border-border"
                    />
                </div>
            </div>
        </div>

        <FundingModal
            :is-open="isFundingModalOpen"
            :live-balance="liveBalance"
            :crypto-holdings="cryptoHoldings"
            :prices-map="pricesMap"
            @close="isFundingModalOpen = false"
        />

        <WithdrawalModal
            :is-open="isWithdrawalModalOpen"
            :live-balance="liveBalance"
            :crypto-holdings="cryptoHoldings"
            :prices-map="pricesMap"
            @close="isWithdrawalModalOpen = false"
        />

        <NotificationsModal
            :is-open="isNotificationsModalOpen"
            @close="isNotificationsModalOpen = false"
        />

        <CopyTradeTransactionsModal
            :is-open="isTransactionsModalOpen"
            :copy-trade="selectedCopyTrade"
            @close="isTransactionsModalOpen = false"
            @pause="handlePauseCopyTrade"
            @resume="handleResumeCopyTrade"
            @stop="handleStopCopyTrade"
        />
    </AppLayout>
</template>

<style scoped>
    button:focus-visible,
    input:focus-visible,
    select:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }
</style>
