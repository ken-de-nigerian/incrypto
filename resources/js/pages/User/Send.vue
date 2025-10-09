<script setup lang="ts">
    import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
    import {
        ActivityIcon,
        AlertCircleIcon,
        ArrowRightIcon,
        BarChart3Icon,
        CheckIcon,
        ChevronDownIcon,
        ClockIcon,
        ExternalLinkIcon,
        HelpCircleIcon,
        SearchIcon,
        SendIcon,
        TrendingDownIcon,
        TrendingUpIcon,
        WalletIcon,
        XIcon,
        ZapIcon
    } from 'lucide-vue-next';
    import Breadcrumb from '@/components/Breadcrumb.vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/components/layout/user/dashboard/AppLayout.vue';
    import NotificationsModal from '@/components/utilities/NotificationsModal.vue';
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
        sentTransactions: Array<{
            id: number;
            token_symbol: string;
            recipient_address: string;
            amount: string;
            status: 'pending' | 'completed' | 'failed';
            transaction_hash: string | null;
            fee: string | null;
            created_at: string;
            updated_at: string;
        }>;
    }>();

    // State
    const selectedAssetToSend = ref<typeof props.tokens[0] | null>(null);
    const recipientAddress = ref('');
    const sendAmount = ref('');
    const isConfirmModalOpen = ref(false);
    const isSending = ref(false);
    const showAssetDropdown = ref(false);
    const assetSearchQuery = ref('');
    const selectedSpeed = ref<'slow' | 'average' | 'fast'>('average');
    const showTransactionHistory = ref(true);
    const message = ref<{ type: 'error'; text: string; } | null>(null);

    // Form validation states
    const addressError = ref('');
    const amountError = ref('');

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
        { label: 'Send Crypto' }
    ];

    // Available assets (tokens with balance > 0)
    const availableAssets = computed(() => {
        if (!props.tokens || !props.userBalances) return [];
        return props.tokens
            .filter(token => (props.userBalances[token.symbol] || 0) > 0)
            .map(token => ({
                ...token,
                balance: props.userBalances[token.symbol] || 0,
                value: (props.userBalances[token.symbol] || 0) * (props.prices[token.symbol] || 0),
                price: props.prices[token.symbol] || 0
            }))
            .sort((a, b) => b.value - a.value);
    });

    // Filtered assets for dropdown search
    const filteredAssets = computed(() => {
        if (!assetSearchQuery.value) return availableAssets.value;
        return availableAssets.value.filter(asset =>
            asset.symbol.toLowerCase().includes(assetSearchQuery.value.toLowerCase()) ||
            asset.name.toLowerCase().includes(assetSearchQuery.value.toLowerCase())
        );
    });

    // Selected asset details
    const selectedBalance = computed(() => {
        if (!selectedAssetToSend.value) return 0;
        return selectedAssetToSend.value.balance || 0;
    });

    const selectedPrice = computed(() => {
        if (!selectedAssetToSend.value) return 0;
        return selectedAssetToSend.value.price || 0;
    });

    const selectedValue = computed(() => {
        return selectedBalance.value * selectedPrice.value;
    });

    // Amount in USD
    const amountInUSD = computed(() => {
        const amount = parseFloat(sendAmount.value) || 0;
        return amount * selectedPrice.value;
    });

    // Network fees based on speed
    const networkFees = computed(() => {
        const baseFee = 0.0005; // Base fee in ETH equivalent
        return {
            slow: { fee: baseFee * 0.8, time: '5-10 min' },
            average: { fee: baseFee, time: '2-5 min' },
            fast: { fee: baseFee * 1.5, time: '< 2 min' }
        };
    });

    const selectedFee = computed(() => {
        return networkFees.value[selectedSpeed.value].fee;
    });

    const selectedFeeTime = computed(() => {
        return networkFees.value[selectedSpeed.value].time;
    });

    const selectedFeeUSD = computed(() => {
        const ethPrice = props.prices['ETH'] || 0;
        return selectedFee.value * ethPrice;
    });

    const totalCost = computed(() => {
        const amount = parseFloat(sendAmount.value) || 0;
        return amount + selectedFee.value;
    });

    const totalCostUSD = computed(() => {
        return amountInUSD.value + selectedFeeUSD.value;
    });

    // Portfolio stats
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
        if (!props.sentTransactions) return [];
        return [...props.sentTransactions]
            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
            .slice(0, 10);
    });

    // Form validation
    const validateAddress = () => {
        addressError.value = '';
        if (!recipientAddress.value) {
            addressError.value = 'Recipient address is required';
            return false;
        }
        return true;
    };

    const validateAmount = () => {
        amountError.value = '';
        const amount = parseFloat(sendAmount.value);

        if (!sendAmount.value) {
            amountError.value = 'Amount is required';
            return false;
        }
        if (isNaN(amount) || amount <= 0) {
            amountError.value = 'Amount must be greater than 0';
            return false;
        }
        if (amount > selectedBalance.value) {
            amountError.value = 'Insufficient balance';
            return false;
        }
        if (totalCost.value > selectedBalance.value) {
            amountError.value = 'Amount + fee exceeds balance';
            return false;
        }
        return true;
    };

    const isFormValid = computed(() => {
        return selectedAssetToSend.value &&
            recipientAddress.value &&
            sendAmount.value &&
            !addressError.value &&
            !amountError.value;
    });

    // Methods
    const selectAsset = (asset: typeof availableAssets.value[0]) => {
        selectedAssetToSend.value = asset;
        showAssetDropdown.value = false;
        assetSearchQuery.value = '';
        sendAmount.value = '';
        amountError.value = '';
    };

    const setMaxAmount = () => {
        if (!selectedAssetToSend.value) return;
        const maxAmount = Math.max(0, selectedBalance.value - selectedFee.value);
        sendAmount.value = maxAmount.toFixed(6);
        validateAmount();
    };

    const openConfirmModal = () => {
        if (!validateAddress() || !validateAmount()) return;
        isConfirmModalOpen.value = true;
    };

    const closeConfirmModal = () => {
        isConfirmModalOpen.value = false;
        message.value = null;
        recipientAddress.value = '';
        sendAmount.value = '';
        selectedAssetToSend.value = null;
        addressError.value = '';
        amountError.value = '';
    };

    const handleSendCrypto = async () => {
        if (!selectedAssetToSend.value || isSending.value) return;

        isSending.value = true;
        message.value = null;

        try {
            await axios.post(route('user.send.store'), {
                token_symbol: selectedAssetToSend.value.symbol,
                recipient_address: recipientAddress.value,
                amount: sendAmount.value,
                fee: selectedFee.value,
                speed: selectedSpeed.value
            });

            router.reload({ only: ['userBalances', 'sentTransactions'] });
            closeConfirmModal();
        } catch (error) {
            message.value = {
                type: 'error',
                text: error
            };
            isSending.value = false;
        }
    };

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

    // Manual click-outside handler
    const dropdownRef = ref<HTMLElement | null>(null);

    const closeDropdown = (event: MouseEvent) => {
        if (showAssetDropdown.value && dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
            showAssetDropdown.value = false;
            assetSearchQuery.value = '';
        }
    };

    onMounted(() => {
        document.addEventListener('click', closeDropdown);
    });

    onUnmounted(() => {
        document.removeEventListener('click', closeDropdown);
    });

    // Watch for modal state
    watch([isConfirmModalOpen], ([confirm]) => {
        if (confirm) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    });
</script>

<template>
    <Head title="Send Crypto" />

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
                                <span class="text-sm text-muted-foreground">Available to Send</span>
                                <span class="text-sm font-semibold text-primary">{{ availableAssets.length }}</span>
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
                        <h1 class="text-2xl font-bold text-card-foreground">Send Crypto</h1>
                        <p class="text-sm text-muted-foreground mt-1">Transfer cryptocurrency to any wallet address</p>
                    </div>

                    <div class="bg-card border border-border rounded-2xl p-6 space-y-6">
                        <div>
                            <label class="text-sm font-semibold text-card-foreground mb-2 block">Select Asset</label>
                            <div class="relative" ref="dropdownRef">
                                <button
                                    @click="showAssetDropdown = !showAssetDropdown"
                                    class="w-full p-4 bg-muted border border-border rounded-lg flex items-center justify-between hover:bg-muted/80 transition-colors cursor-pointer">
                                    <div v-if="selectedAssetToSend" class="flex items-center gap-3">
                                        <img :src="selectedAssetToSend.logo" :alt="selectedAssetToSend.symbol" class="w-8 h-8 rounded-full" />
                                        <div class="text-left">
                                            <div class="font-semibold text-card-foreground">{{ selectedAssetToSend.symbol }}</div>
                                            <div class="text-xs text-muted-foreground">Balance: {{ selectedBalance.toFixed(6) }}</div>
                                        </div>
                                    </div>
                                    <span v-else class="text-muted-foreground">Select an asset</span>
                                    <ChevronDownIcon :class="['w-5 h-5 text-muted-foreground transition-transform', showAssetDropdown && 'rotate-180']" />
                                </button>

                                <div v-if="showAssetDropdown" class="absolute top-full left-0 right-0 mt-2 bg-card border border-border rounded-lg shadow-xl z-50 max-h-80 overflow-hidden">
                                    <div class="p-3 border-b border-border">
                                        <div class="relative">
                                            <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                            <input
                                                v-model="assetSearchQuery"
                                                type="text"
                                                placeholder="Search assets..."
                                                class="w-full pl-10 pr-4 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                            />
                                        </div>
                                    </div>
                                    <div class="max-h-64 overflow-y-auto">
                                        <div v-if="filteredAssets.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                                            No assets with balance found
                                        </div>
                                        <button
                                            v-for="asset in filteredAssets"
                                            :key="asset.symbol"
                                            @click="selectAsset(asset)"
                                            class="w-full p-3 hover:bg-muted/50 transition-colors flex items-center justify-between cursor-pointer">
                                            <div class="flex items-center gap-3">
                                                <img :src="asset.logo" :alt="asset.symbol" class="w-8 h-8 rounded-full" />
                                                <div class="text-left">
                                                    <div class="font-medium text-card-foreground">{{ asset.symbol }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ asset.name }}</div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-semibold text-card-foreground">{{ asset.balance.toFixed(4) }}</div>
                                                <div class="text-xs text-muted-foreground">${{ asset.value.toFixed(2) }}</div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedAssetToSend" class="mt-3 p-3 bg-muted/30 rounded-lg">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <div class="text-muted-foreground">Available</div>
                                        <div class="font-semibold text-card-foreground">{{ selectedBalance.toFixed(6) }} {{ selectedAssetToSend.symbol }}</div>
                                    </div>
                                    <div>
                                        <div class="text-muted-foreground">Value</div>
                                        <div class="font-semibold text-card-foreground">${{ selectedValue.toFixed(2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-card-foreground mb-2 block">Amount</label>
                            <div class="relative">
                                <input
                                    v-model="sendAmount"
                                    @blur="validateAmount"
                                    @input="amountError = ''"
                                    type="number"
                                    step="0.000001"
                                    placeholder="0.00"
                                    :disabled="!selectedAssetToSend"
                                    class="w-full p-4 pr-20 bg-muted border border-border rounded-lg text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                />
                                <button
                                    @click="setMaxAmount"
                                    :disabled="!selectedAssetToSend"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-primary text-primary-foreground rounded-md text-sm font-semibold hover:opacity-90 transition-opacity disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">
                                    MAX
                                </button>
                            </div>
                            <div v-if="amountError" class="mt-2 text-sm text-destructive flex items-center gap-1">
                                <AlertCircleIcon class="w-4 h-4" />
                                {{ amountError }}
                            </div>
                            <div v-else-if="sendAmount && selectedAssetToSend" class="mt-2 text-sm text-muted-foreground">
                                ≈ ${{ amountInUSD.toFixed(2) }} USD
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-card-foreground mb-2 block">Recipient Address</label>
                            <input
                                v-model="recipientAddress"
                                @blur="validateAddress"
                                @input="addressError = ''"
                                type="text"
                                placeholder="0x..."
                                class="w-full p-4 bg-muted border border-border rounded-lg font-mono text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <div v-if="addressError" class="mt-2 text-sm text-destructive flex items-center gap-1">
                                <AlertCircleIcon class="w-4 h-4" />
                                {{ addressError }}
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-card-foreground mb-3 block flex items-center gap-2">
                                <ZapIcon class="w-4 h-4" />
                                Network Speed & Fee
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    v-for="speed in ['slow', 'average', 'fast']"
                                    :key="speed"
                                    @click="selectedSpeed = speed as typeof selectedSpeed"
                                    :class="[
                                        'p-4 border-2 rounded-lg transition-all cursor-pointer',
                                        selectedSpeed === speed
                                            ? 'border-primary bg-primary/10'
                                            : 'border-border hover:border-primary/50'
                                    ]">
                                    <div class="text-sm font-semibold text-card-foreground capitalize mb-1">{{ speed }}</div>
                                    <div class="text-xs text-muted-foreground mb-2">{{ networkFees[speed].time }}</div>
                                    <div class="text-sm font-bold text-primary">${{ (networkFees[speed].fee * (props.prices['ETH'] || 0)).toFixed(2) }}</div>
                                </button>
                            </div>
                        </div>

                        <div v-if="sendAmount && selectedAssetToSend" class="p-4 bg-muted/50 rounded-lg space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">You're sending</span>
                                <span class="font-semibold text-card-foreground">{{ sendAmount }} {{ selectedAssetToSend.symbol }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Network fee</span>
                                <span class="font-semibold text-card-foreground">${{ selectedFeeUSD.toFixed(2) }}</span>
                            </div>
                            <div class="border-t border-border pt-2 flex items-center justify-between">
                                <span class="text-sm font-semibold text-card-foreground">Total cost</span>
                                <div class="text-right">
                                    <div class="font-bold text-card-foreground">${{ totalCostUSD.toFixed(2) }}</div>
                                    <div class="text-xs text-muted-foreground">{{ totalCost.toFixed(6) }} {{ selectedAssetToSend.symbol }}</div>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="openConfirmModal"
                            :disabled="!isFormValid"
                            class="w-full py-4 bg-primary text-primary-foreground rounded-lg font-semibold text-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <SendIcon class="w-5 h-5" />
                            Review Transaction
                        </button>
                    </div>
                </div>

                <div class="lg:col-span-3 space-y-4">
                    <div class="bg-card border border-border rounded-2xl p-4">
                        <h3 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
                            <HelpCircleIcon class="w-4 h-4" />
                            How to Send
                        </h3>
                        <div class="space-y-4">
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold">1</div>
                                <div>
                                    <div class="text-sm font-medium text-card-foreground">Select Asset</div>
                                    <div class="text-xs text-muted-foreground mt-1">Choose a crypto from your holdings</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold">2</div>
                                <div>
                                    <div class="text-sm font-medium text-card-foreground">Enter Details</div>
                                    <div class="text-xs text-muted-foreground mt-1">Add amount and recipient address</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold">3</div>
                                <div>
                                    <div class="text-sm font-medium text-card-foreground">Review & Confirm</div>
                                    <div class="text-xs text-muted-foreground mt-1">Double-check all transaction details</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold">4</div>
                                <div>
                                    <div class="text-sm font-medium text-card-foreground">Send Crypto</div>
                                    <div class="text-xs text-muted-foreground mt-1">Transaction will be processed</div>
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
                            <li class="flex gap-2"><span class="text-primary">•</span><span>Always verify the recipient address carefully</span></li>
                            <li class="flex gap-2"><span class="text-primary">•</span><span>Start with a small test transaction first</span></li>
                            <li class="flex gap-2"><span class="text-primary">•</span><span>Ensure you're sending to the correct network</span></li>
                            <li class="flex gap-2"><span class="text-primary">•</span><span>Transactions cannot be reversed once sent</span></li>
                        </ul>
                    </div>

                    <div class="bg-card border border-border rounded-2xl overflow-hidden">
                        <button
                            @click="showTransactionHistory = !showTransactionHistory"
                            class="w-full flex items-center justify-between p-4 hover:bg-muted transition-colors">
                            <span class="font-semibold text-card-foreground flex items-center gap-2">
                                <ClockIcon class="w-4 h-4" />
                                Sent Cryptos
                            </span>
                            <ChevronDownIcon
                                :class="['w-4 h-4 text-muted-foreground transition-transform', showTransactionHistory && 'rotate-180']"
                            />
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
                                <div
                                    v-for="tx in recentTransactions"
                                    :key="tx.id"
                                    class="p-4 border-b border-border last:border-b-0 hover:bg-muted/50 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div
                                            :class="[
                                                'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
                                                tx.status === 'completed' ? 'bg-primary/10' :
                                                tx.status === 'pending' ? 'bg-yellow-500/10' :
                                                'bg-destructive/10'
                                            ]">
                                            <component
                                                :is="getStatusIcon(tx.status)"
                                                :class="['w-4 h-4', getStatusColor(tx.status)]"
                                            />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <div class="text-sm font-medium text-card-foreground">
                                                    Send {{ tx.token_symbol }}
                                                </div>
                                                <div
                                                    class="text-xs"
                                                    :class="getStatusColor(tx.status)">
                                                    {{ tx.status.charAt(0).toUpperCase() + tx.status.slice(1) }}
                                                </div>
                                            </div>
                                            <div class="text-xs text-muted-foreground mb-1">
                                                {{ formatDate(tx.created_at) }}
                                            </div>
                                            <div class="text-xs font-semibold text-destructive mb-1">
                                                -{{ parseFloat(tx.amount).toFixed(6) }} {{ tx.token_symbol }}
                                            </div>
                                            <div class="text-xs text-muted-foreground mb-1">
                                                To: {{ tx.recipient_address.slice(0, 10) }}...{{ tx.recipient_address.slice(-8) }}
                                            </div>
                                            <div v-if="tx.fee" class="text-xs text-muted-foreground">
                                                Fee: ${{ parseFloat(tx.fee).toFixed(4) }}
                                            </div>
                                            <a
                                                v-if="tx.transaction_hash && tx.status === 'completed'"
                                                :href="`https://etherscan.io/tx/${tx.transaction_hash}`"
                                                target="_blank"
                                                class="text-xs text-primary hover:underline flex items-center gap-1 mt-2">
                                                View on Explorer
                                                <ExternalLinkIcon class="w-3 h-3" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Teleport to="body">
                <div
                    v-if="isConfirmModalOpen && selectedAssetToSend"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="closeConfirmModal">
                    <div class="w-full max-w-lg bg-card border border-border rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="p-6 border-b border-border">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xl font-bold text-card-foreground">{{ message ? 'Transaction Failed' : 'Confirm Transaction' }}</h3>
                                <button
                                    @click="closeConfirmModal"
                                    class="p-2 hover:bg-muted rounded-lg transition-colors cursor-pointer">
                                    <XIcon class="w-5 h-5 text-muted-foreground" />
                                </button>
                            </div>
                            <p v-if="!message" class="text-sm text-muted-foreground">
                                Please review the transaction details carefully before confirming
                            </p>
                        </div>

                        <div class="p-6 space-y-6 overflow-y-auto">
                            <div v-if="message" class="p-4 rounded-xl flex items-start gap-3 bg-destructive/10 border border-destructive/20">
                                <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5 text-destructive" />
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-destructive">{{ message.text }}</p>
                                </div>
                            </div>

                            <div v-else>
                                <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl flex items-start gap-3">
                                    <AlertCircleIcon class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                                    <div class="text-sm">
                                        <p class="font-semibold text-yellow-700 mb-1">Transaction Cannot Be Reversed</p>
                                        <p class="text-muted-foreground">
                                            Once confirmed, this transaction cannot be cancelled or reversed.
                                            Please verify all details are correct.
                                        </p>
                                    </div>
                                </div>

                                <div class="text-center py-4">
                                    <div class="text-sm text-muted-foreground mb-2">You're sending</div>
                                    <div class="flex items-center justify-center gap-3 mb-2">
                                        <img
                                            :src="selectedAssetToSend.logo"
                                            :alt="selectedAssetToSend.symbol"
                                            class="w-12 h-12 rounded-full"
                                        />
                                        <div class="text-3xl font-bold text-card-foreground">
                                            {{ sendAmount }}
                                        </div>
                                        <div class="text-2xl font-semibold text-muted-foreground">
                                            {{ selectedAssetToSend.symbol }}
                                        </div>
                                    </div>
                                    <div class="text-lg text-muted-foreground">
                                        ≈ ${{ amountInUSD.toFixed(2) }} USD
                                    </div>
                                </div>

                                <div class="flex justify-center">
                                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                        <ArrowRightIcon class="w-5 h-5 text-primary" />
                                    </div>
                                </div>

                                <div class="p-4 bg-muted/50 border border-border rounded-xl">
                                    <div class="text-xs text-muted-foreground mb-2 font-semibold uppercase">
                                        Recipient Address
                                    </div>
                                    <div class="text-sm font-mono text-card-foreground break-all leading-relaxed">
                                        {{ recipientAddress }}
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                                        <span class="text-sm text-muted-foreground">Speed</span>
                                        <span class="text-sm font-semibold text-card-foreground capitalize">
                                            {{ selectedSpeed }} ({{ selectedFeeTime }})
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                                        <span class="text-sm text-muted-foreground">Network Fee</span>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-card-foreground">
                                                ${{ selectedFeeUSD.toFixed(2) }}
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ selectedFee.toFixed(6) }}{{ selectedAssetToSend.symbol }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between p-4 bg-primary/10 border border-primary/20 rounded-lg">
                                        <span class="text-sm font-semibold text-card-foreground">Total Cost</span>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-card-foreground">
                                                ${{ totalCostUSD.toFixed(2) }}
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ totalCost.toFixed(6) }} {{ selectedAssetToSend.symbol }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-muted-foreground">Balance After Transaction</span>
                                        <span class="text-sm font-semibold text-card-foreground">
                                            {{ (selectedBalance - totalCost).toFixed(6) }} {{ selectedAssetToSend.symbol }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="!message" class="p-6 border-t border-border bg-muted/30">
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    @click="closeConfirmModal"
                                    :disabled="isSending"
                                    class="py-3 px-4 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-lg font-semibold transition-colors disabled:opacity-50 cursor-pointer">
                                    Cancel
                                </button>
                                <button
                                    @click="handleSendCrypto"
                                    :disabled="isSending"
                                    class="py-3 px-4 bg-primary hover:opacity-90 text-primary-foreground rounded-lg font-semibold flex items-center justify-center gap-2 transition-opacity disabled:opacity-50">
                                    <SendIcon v-if="!isSending" class="w-5 h-5" />
                                    <div v-else class="w-5 h-5 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
                                    {{ isSending ? 'Sending...' : 'Confirm & Send' }}
                                </button>
                            </div>
                        </div>
                        <div v-else class="p-6 border-t border-border bg-muted/30">
                            <button
                                @click="closeConfirmModal"
                                class="w-full py-3 px-4 bg-muted hover:bg-muted/80 border border-border text-card-foreground rounded-lg font-semibold transition-colors cursor-pointer">
                                Close
                            </button>
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

    /* Spinner animation */
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    /* Remove number input spinners */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>
