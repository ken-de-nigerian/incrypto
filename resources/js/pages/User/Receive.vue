<script setup lang="ts">
    import { computed, ref, watch } from 'vue';
    import {
        ActivityIcon,
        AlertCircleIcon,
        BarChart3Icon,
        CheckIcon,
        ChevronDownIcon,
        ClockIcon,
        CopyIcon,
        ExternalLinkIcon,
        HelpCircleIcon,
        QrCodeIcon,
        SearchIcon,
        TrendingDownIcon,
        TrendingUpIcon,
        WalletIcon,
        XIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
    import QRCodeVue3 from 'qrcode-vue3';
    import axios from 'axios';
    import { router } from '@inertiajs/vue3';

    // Props
    const props = defineProps<{
        tokens: Array<{
            symbol: string;
            name: string;
            address: string;
            logo: string;
            decimals: number;
            price_change_24h: number;
        }>;
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        portfolioChange24h: number;
        popularTokens: string[];
        pendingTransactions: Array<{
            id: number;
            token_symbol: string;
            wallet_address: string;
            amount: string | null;
            status: 'pending' | 'completed' | 'failed';
            transaction_hash: string | null;
            created_at: string;
            updated_at: string;
        }>;
    }>();

    // State
    const selectedToken = ref<typeof props.tokens[0] | null>(null);
    const isWalletModalOpen = ref(false);
    const copiedAddress = ref(false);
    const searchQuery = ref('');
    const activeTab = ref<'popular' | 'all'>('popular');
    const showTransactionHistory = ref(true);
    const isCreatingTransaction = ref(false);

    const page = usePage();
    const user = computed(() => page.props.auth?.user);

    const initials = computed(() => {
        if (user.value) {
            const first = user.value.first_name?.charAt(0) || '';
            const last = user.value.last_name?.charAt(0) || '';
            return `${first}${last}`.toUpperCase();
        }
        return '';
    });

    const isNotificationsModalOpen = ref(false);
    const notificationCount = computed(() => page.props.auth?.notification_count || 0);

    const openNotificationsModal = () => {
        isNotificationsModalOpen.value = true;
    };

    const closeNotificationsModal = () => {
        isNotificationsModalOpen.value = false;
    };

    const breadcrumbItems = [
        { label: 'Dashboard', href: route('user.dashboard') },
        { label: 'Receive Crypto' }
    ];

    const walletAddress = computed(() => {
        return selectedToken.value?.address || '';
    });

    const filteredTokens = computed(() => {
        if (!props.tokens) return [];
        return props.tokens.filter(token => {
            return token.symbol.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                token.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        });
    });

    const popularTokens = computed(() => {
        if (!props.tokens || !props.popularTokens) return [];
        return props.tokens.filter(t => props.popularTokens.includes(t.symbol));
    });

    const displayedTokens = computed(() => {
        const tokens = activeTab.value === 'popular' ? popularTokens.value : filteredTokens.value;
        return tokens.map(token => ({
            ...token,
            balance: props.userBalances[token.symbol] || 0,
            value: (props.userBalances[token.symbol] || 0) * (props.prices[token.symbol] || 0),
            price: props.prices[token.symbol] || 0
        }));
    });

    const selectedBalance = computed(() => {
        if (!selectedToken.value || !props.userBalances) return 0;
        return props.userBalances[selectedToken.value.symbol] || 0;
    });

    const selectedPrice = computed(() => {
        if (!selectedToken.value || !props.prices) return 0;
        return props.prices[selectedToken.value.symbol] || 0;
    });

    const totalPortfolioValue = computed(() => {
        if (!props.userBalances || !props.prices) return 0;
        return Object.keys(props.userBalances).reduce((total, symbol) => {
            const balance = props.userBalances[symbol] || 0;
            const price = props.prices[symbol] || 0;
            return total + (balance * price);
        }, 0);
    });

    const topTokens = computed(() => {
        if (!props.tokens || !props.userBalances || !props.prices) return [];
        return props.tokens
            .map(token => ({
                ...token,
                balance: props.userBalances[token.symbol] || 0,
                value: (props.userBalances[token.symbol] || 0) * (props.prices[token.symbol] || 0)
            }))
            .filter(token => token.balance > 0)
            .sort((a, b) => b.value - a.value)
            .slice(0, 5);
    });

    const totalAssets = computed(() => {
        if (!props.userBalances) return 0;
        return Object.keys(props.userBalances).filter(symbol => props.userBalances[symbol] > 0).length;
    });

    const recentTransactions = computed(() => {
        if (!props.pendingTransactions) return [];
        return [...props.pendingTransactions]
            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
            .slice(0, 10);
    });

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed':
                return 'text-primary';
            case 'pending':
                return 'text-yellow-500';
            case 'failed':
                return 'text-destructive';
            default:
                return 'text-muted-foreground';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'completed':
                return CheckIcon;
            case 'pending':
                return ClockIcon;
            case 'failed':
                return XIcon;
            default:
                return ClockIcon;
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

    // Methods
    const copyAddress = async () => {
        if (!walletAddress.value || !selectedToken.value) return;

        try {
            await navigator.clipboard.writeText(walletAddress.value);
            copiedAddress.value = true;

            // Create pending transaction record
            await createPendingTransaction();

            setTimeout(() => copiedAddress.value = false, 2000);
        } catch (err) {
            console.error('Failed to copy address:', err);
        }
    };

    const createPendingTransaction = async () => {
        if (!selectedToken.value || isCreatingTransaction.value) return;

        isCreatingTransaction.value = true;

        try {
            await axios.post(route('user.receive.store'), {
                token_symbol: selectedToken.value.symbol,
                wallet_address: walletAddress.value,
            });

            router.reload({ only: ['pendingTransactions'] });
        } catch (error) {
            console.error('Failed to create pending transaction:', error);
        } finally {
            isCreatingTransaction.value = false;
        }
    };

    const openWalletModal = (token: typeof props.tokens[0]) => {
        selectedToken.value = token;
        isWalletModalOpen.value = true;
        copiedAddress.value = false;
    };

    const closeWalletModal = () => {
        isWalletModalOpen.value = false;
        copiedAddress.value = false;
    };

    watch(isWalletModalOpen, (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
</script>

<template>
    <Head title="Receive Crypto" />

    <AppLayout>
        <div class="lg:ml-64 pt-5 lg:pt-10 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
            <Breadcrumb
                :items="breadcrumbItems"
                :user="user"
                :notification-count="notificationCount"
                :initials="initials"
                @open-notifications="openNotificationsModal"
            />

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-6">
                <div class="lg:col-span-3 space-y-4">
                    <div class="bg-gradient-to-br from-primary/10 to-primary/5 border border-border rounded-2xl p-6">
                        <div class="flex items-center gap-2 mb-2">
                            <WalletIcon class="w-5 h-5 text-primary" />
                            <span class="text-sm font-medium text-muted-foreground">Portfolio Value</span>
                        </div>

                        <div class="text-2xl sm:text-3xl font-bold text-card-foreground mb-1">
                            ${{ totalPortfolioValue.toFixed(2) }}
                        </div>

                        <div class="flex items-center gap-1 text-sm" :class="props.portfolioChange24h >= 0 ? 'text-primary' : 'text-destructive'">
                            <TrendingUpIcon v-if="props.portfolioChange24h >= 0" class="w-4 h-4" />
                            <TrendingDownIcon v-else class="w-4 h-4" />
                            <span>{{ props.portfolioChange24h >= 0 ? '+' : '' }}{{ props.portfolioChange24h.toFixed(2) }}% (24h)</span>
                        </div>
                    </div>

                    <div class="bg-card border border-border rounded-2xl p-4">
                        <h3 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                            <BarChart3Icon class="w-4 h-4" />
                            Top Holdings
                        </h3>
                        <div v-if="topTokens.length > 0" class="space-y-3">
                            <div v-for="token in topTokens" :key="token.symbol" class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <img :src="token.logo" :alt="token.symbol" class="w-6 h-6 rounded-full" />
                                    <div>
                                        <div class="text-sm font-medium text-card-foreground">{{ token.symbol }}</div>
                                        <div class="text-xs text-muted-foreground">{{ token.balance.toFixed(4) }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-card-foreground">${{ token.value.toFixed(2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4">
                            <p class="text-sm text-muted-foreground">No holdings yet</p>
                        </div>
                    </div>
                    <div class="bg-card border border-border rounded-2xl p-4">
                        <h3 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                            <ActivityIcon class="w-4 h-4" />
                            Quick Stats
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Total Assets</span>
                                <span class="text-sm font-semibold text-card-foreground">{{ totalAssets }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Available Tokens</span>
                                <span class="text-sm font-semibold text-primary">{{ props.tokens?.length || 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Network Status</span>
                                <span class="text-sm font-semibold text-primary flex items-center gap-1">
                                    <div class="w-2 h-2 bg-primary rounded-full"></div>
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-card-foreground">Receive Crypto</h1>
                        <p class="text-sm text-muted-foreground mt-1">Select a token to view your receiving address</p>
                    </div>

                    <div class="bg-card border border-border rounded-2xl overflow-hidden mb-4">
                        <div class="p-4 border-b border-border">
                            <div class="relative">
                                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by name or symbol"
                                    class="w-full pl-10 pr-4 py-3 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                            </div>
                        </div>

                        <div class="px-4 pt-2 flex gap-2 border-b border-border">
                            <button
                                @click="activeTab = 'popular'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium relative',
                                    activeTab === 'popular' ? 'text-primary' : 'text-muted-foreground'
                                ]">
                                Popular
                                <div v-if="activeTab === 'popular'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                            </button>
                            <button
                                @click="activeTab = 'all'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium relative',
                                    activeTab === 'all' ? 'text-primary' : 'text-muted-foreground'
                                ]">
                                All Assets
                                <div v-if="activeTab === 'all'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></div>
                            </button>
                        </div>

                        <div class="max-h-[600px] overflow-y-auto">
                            <div v-if="displayedTokens.length === 0" class="p-8 text-center">
                                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                                    <SearchIcon class="w-8 h-8 text-muted-foreground" />
                                </div>
                                <p class="text-sm text-muted-foreground">No tokens found</p>
                            </div>
                            <div
                                v-for="token in displayedTokens"
                                :key="token.symbol"
                                @click="openWalletModal(token)"
                                class="p-4 border-b border-border last:border-b-0 hover:bg-muted/50 cursor-pointer transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <img :src="token.logo" :alt="token.symbol" class="w-10 h-10 rounded-full flex-shrink-0" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <div class="font-semibold text-card-foreground">{{ token.symbol }}</div>
                                            </div>
                                            <div class="text-xs text-muted-foreground truncate">{{ token.name }}</div>
                                            <div v-if="token.address" class="text-xs text-muted-foreground font-mono truncate mt-0.5">
                                                {{ token.address.slice(0, 10) }}...{{ token.address.slice(-8) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right ml-2 sm:ml-4">
                                        <div class="font-semibold text-card-foreground">
                                            {{ token.balance.toFixed(4) }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            ${{ token.value.toFixed(2) }}
                                        </div>
                                        <div :class="['text-xs flex items-center justify-end gap-1', token.price_change_24h >= 0 ? 'text-primary' : 'text-destructive']">
                                            <TrendingUpIcon v-if="token.price_change_24h >= 0" class="w-3 h-3" />
                                            <TrendingDownIcon v-else class="w-3 h-3" />
                                            {{ token.price_change_24h >= 0 ? '+' : '' }}{{ token.price_change_24h.toFixed(2) }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 space-y-4">
                    <div class="bg-card border border-border rounded-2xl p-4">
                        <h3 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                            <HelpCircleIcon class="w-4 h-4" />
                            How to Receive
                        </h3>
                        <div class="space-y-4">
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold">1</div>
                                <div>
                                    <div class="text-sm font-medium text-card-foreground">Select Token</div>
                                    <div class="text-xs text-muted-foreground mt-1">Click on any token from the list</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold">2</div>
                                <div>
                                    <div class="text-sm font-medium text-card-foreground">View Address</div>
                                    <div class="text-xs text-muted-foreground mt-1">Copy the address or scan the QR code</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold">3</div>
                                <div>
                                    <div class="text-sm font-medium text-card-foreground">Send Funds</div>
                                    <div class="text-xs text-muted-foreground mt-1">Use the address in your sending wallet</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold">4</div>
                                <div>
                                    <div class="text-sm font-medium text-card-foreground">Track Status</div>
                                    <div class="text-xs text-muted-foreground mt-1">Monitor your received cryptos in the history below</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-primary/10 to-primary/5 border border-primary/20 rounded-2xl p-4">
                        <h3 class="text-sm font-semibold text-card-foreground mb-3 flex items-center gap-2">
                            <AlertCircleIcon class="w-4 h-4 text-primary" />
                            Safety Tips
                        </h3>
                        <ul class="space-y-2 text-xs text-muted-foreground">
                            <li class="flex gap-2"><span class="text-primary">•</span><span>Always verify the network before sending</span></li>
                            <li class="flex gap-2"><span class="text-primary">•</span><span>Start with a small test transaction first</span></li>
                            <li class="flex gap-2"><span class="text-primary">•</span><span>Never share your private keys or seed phrase</span></li>
                            <li class="flex gap-2"><span class="text-primary">•</span><span>Double-check the wallet address before sending</span></li>
                        </ul>
                    </div>
                    <div class="bg-card border border-border rounded-2xl overflow-hidden">
                        <button @click="showTransactionHistory = !showTransactionHistory" class="w-full flex items-center justify-between p-4 hover:bg-muted transition-colors">
                            <span class="font-semibold text-card-foreground flex items-center gap-2"><ClockIcon class="w-4 h-4" />Received Cryptos</span>
                            <div class="flex items-center gap-2">
                                <ChevronDownIcon :class="['w-4 h-4 text-muted-foreground transition-transform', showTransactionHistory && 'rotate-180']" />
                            </div>
                        </button>

                        <div v-if="showTransactionHistory" class="border-t border-border">
                            <div v-if="recentTransactions.length === 0" class="p-8 text-center">
                                <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-3"><ClockIcon class="w-6 h-6 text-muted-foreground" /></div>
                                <p class="text-sm text-muted-foreground">No received cryptos</p>
                                <p class="text-xs text-muted-foreground mt-1">Your received cryptos history will appear here</p>
                            </div>
                            <div v-else>
                                <div v-for="tx in recentTransactions" :key="tx.id" class="p-4 border-b border-border last:border-b-0 hover:bg-muted/50 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0', tx.status === 'completed' ? 'bg-primary/10' : tx.status === 'pending' ? 'bg-yellow-500/10' : 'bg-destructive/10']">
                                            <component :is="getStatusIcon(tx.status)" :class="['w-4 h-4', getStatusColor(tx.status)]" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <div class="text-sm font-medium text-card-foreground">Receive {{ tx.token_symbol }}</div>
                                                <div class="text-xs" :class="getStatusColor(tx.status)">{{ tx.status.charAt(0).toUpperCase() + tx.status.slice(1) }}</div>
                                            </div>
                                            <div class="text-xs text-muted-foreground mb-1">{{ formatDate(tx.created_at) }}</div>
                                            <div v-if="tx.amount" class="text-xs font-semibold text-card-foreground mb-1">+{{ parseFloat(tx.amount).toFixed(6) }} {{ tx.token_symbol }}</div>
                                            <div class="text-xs text-muted-foreground font-mono truncate">{{ tx.wallet_address }}</div>
                                            <a v-if="tx.transaction_hash && tx.status === 'completed'" :href="`https://etherscan.io/tx/${tx.transaction_hash}`" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1 mt-2">View on Explorer<ExternalLinkIcon class="w-3 h-3" /></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Teleport to="body">
                <div v-if="isWalletModalOpen && selectedToken" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeWalletModal">
                    <div class="w-full max-w-lg bg-card border border-border rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="p-6 border-b border-border">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-card-foreground">Receive {{ selectedToken.symbol }}</h3>
                                <button @click="closeWalletModal" class="p-2 hover:bg-muted rounded-lg transition-colors"><XIcon class="w-5 h-5 text-muted-foreground" /></button>
                            </div>
                            <div class="flex items-center gap-3">
                                <img :src="selectedToken.logo" :alt="selectedToken.symbol" class="w-12 h-12 rounded-full" />
                                <div class="flex-1">
                                    <div class="font-semibold text-card-foreground">{{ selectedToken.name }}</div>
                                    <div class="text-sm text-muted-foreground flex items-center gap-2"><span>{{ selectedBalance.toFixed(6) }} {{ selectedToken.symbol }}</span></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-card-foreground">${{ (selectedBalance * selectedPrice).toFixed(2) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-6 overflow-y-auto">
                            <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl flex items-start gap-3">
                                <AlertCircleIcon class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                                <div class="text-sm">
                                    <p class="font-semibold text-yellow-700 mb-1">Important: Network Warning</p>
                                    <p class="text-muted-foreground">Only send <span class="font-semibold text-card-foreground">{{ selectedToken.symbol }}</span> to this address. Sending other assets or using the wrong network may result in permanent loss of funds.</p>
                                </div>
                            </div>

                            <div v-if="walletAddress">
                                <label class="text-sm font-semibold text-card-foreground mb-3 block flex items-center gap-2"><QrCodeIcon class="w-4 h-4" />Scan QR Code</label>
                                <div class="flex justify-center p-4 sm:p-6 bg-white rounded-xl border-2 border-border">
                                    <QRCodeVue3 :value="walletAddress" :size="240" :corners-square-options="{ type: 'square', color: '#000000' }" :corners-dot-options="{ type: 'square', color: '#000000' }" :dots-options="{ type: 'square', color: '#000000' }" />
                                </div>
                                <p class="text-xs text-muted-foreground text-center mt-3">Scan this QR code with your sending wallet</p>
                            </div>

                            <div v-if="walletAddress">
                                <label class="text-sm font-semibold text-card-foreground mb-3 block flex items-center gap-2"><WalletIcon class="w-4 h-4" />Your Wallet Address</label>
                                <div class="p-4 bg-muted/50 border border-border rounded-xl">
                                    <div class="text-sm font-mono text-card-foreground break-all mb-3 leading-relaxed">{{ walletAddress }}</div>
                                    <button @click="copyAddress" :class="['w-full py-3 px-4 rounded-lg font-semibold text-sm flex items-center justify-center gap-2 transition-all', copiedAddress ? 'bg-primary/20 text-primary border-2 border-primary' : 'bg-primary hover:opacity-90 text-primary-foreground']">
                                        <CheckIcon v-if="copiedAddress" class="w-5 h-5" />
                                        <CopyIcon v-else class="w-5 h-5" />
                                        {{ copiedAddress ? 'Address Copied to Clipboard!' : 'Copy Address' }}
                                    </button>
                                </div>
                            </div>

                            <div v-else class="p-8 text-center">
                                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4"><WalletIcon class="w-8 h-8 text-muted-foreground" /></div>
                                <h3 class="text-lg font-semibold text-card-foreground mb-2">No Wallet Address Available</h3>
                                <p class="text-sm text-muted-foreground">This token does not have a configured wallet address yet.</p>
                            </div>

                            <div v-if="walletAddress" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-4 bg-muted/30 rounded-xl">
                                    <div class="text-xs text-muted-foreground mb-1">Symbol</div>
                                    <div class="text-sm font-semibold text-card-foreground">{{ selectedToken.symbol }}</div>
                                </div>
                                <div class="p-4 bg-muted/30 rounded-xl">
                                    <div class="text-xs text-muted-foreground mb-1">Current Price</div>
                                    <div class="text-sm font-semibold text-card-foreground">${{ selectedPrice.toFixed(2) }}</div>
                                </div>
                                <div class="p-4 bg-muted/30 rounded-xl">
                                    <div class="text-xs text-muted-foreground mb-1">Balance</div>
                                    <div class="text-sm font-semibold text-card-foreground">{{ selectedBalance.toFixed(6) }}</div>
                                </div>
                                <div class="p-4 bg-muted/30 rounded-xl">
                                    <div class="text-xs text-muted-foreground mb-1">24h Change</div>
                                    <div :class="['text-sm font-semibold flex items-center gap-1', selectedToken.price_change_24h >= 0 ? 'text-primary' : 'text-destructive']">
                                        <TrendingUpIcon v-if="selectedToken.price_change_24h >= 0" class="w-3 h-3" />
                                        <TrendingDownIcon v-else class="w-3 h-3" />
                                        {{ selectedToken.price_change_24h >= 0 ? '+' : '' }}{{ selectedToken.price_change_24h.toFixed(2) }}%
                                    </div>
                                </div>
                            </div>

                            <div v-if="walletAddress" class="p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <AlertCircleIcon class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                                    <div class="text-xs text-muted-foreground">
                                        <p class="font-semibold text-blue-700 mb-1">Processing Time</p>
                                        <p>Deposits are usually credited within 10-30 minutes depending on network congestion. You can track your transaction status in the "Pending Deposits" section.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 border-t border-border bg-muted/30">
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <button @click="closeWalletModal" class="w-full py-3 px-4 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-lg font-semibold text-sm transition-colors">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>

    <NotificationsModal
        :is-open="isNotificationsModalOpen"
        @close="closeNotificationsModal"
    />
</template>

<style scoped>
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: hsl(var(--muted));
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: hsl(var(--muted-foreground) / 0.3);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--muted-foreground) / 0.5);
    }

    /* Focus styles */
    button:focus-visible,
    input:focus-visible,
    select:focus-visible {
        outline: 2px solid hsl(var(--primary));
        outline-offset: 2px;
    }

    /* Modal animations */
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .fixed > div {
        animation: modalFadeIn 0.2s ease-out;
    }
</style>
