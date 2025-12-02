<script setup lang="ts">
    import { computed, ref, onMounted, onUnmounted } from 'vue';
    import {
        AlertCircleIcon,
        ChevronDownIcon,
        SearchIcon,
        SendIcon,
        ClipboardIcon,
        AlertTriangleIcon
    } from 'lucide-vue-next';

    const props = defineProps<{
        availableAssets: Array<any>;
        prices: Record<string, number>;
        networkFee: number;
        userBalances: Record<string, number>;
        chargeNetworkFee: boolean;
        networkFeeMap: Record<string, { symbol: string; name: string }>;
    }>();

    const emit = defineEmits(['review-transaction']);

    const selectedAssetToSend = ref<any | null>(null);
    const recipientAddress = ref('');
    const sendAmount = ref('');
    const showAssetDropdown = ref(false);
    const assetSearchQuery = ref('');
    const addressError = ref('');
    const amountError = ref('');
    const dropdownRef = ref<HTMLElement | null>(null);

    const filteredAssets = computed(() => {
        if (!assetSearchQuery.value) return props.availableAssets;
        const query = assetSearchQuery.value.toLowerCase();
        return props.availableAssets.filter(asset =>
            asset.symbol.toLowerCase().includes(query) ||
            asset.name.toLowerCase().includes(query)
        );
    });

    const selectedBalance = computed(() => selectedAssetToSend.value?.balance || 0);
    const selectedPrice = computed(() => selectedAssetToSend.value?.price || 0);
    const selectedValue = computed(() => selectedBalance.value * selectedPrice.value);
    const amountInUSD = computed(() => (parseFloat(sendAmount.value) || 0) * selectedPrice.value);

    const feeTokenInfo = computed(() => {
        if (!selectedAssetToSend.value) return { symbol: 'ETH', name: 'Ethereum' };

        const tokenSymbol = selectedAssetToSend.value.symbol.toUpperCase();
        return props.networkFeeMap[tokenSymbol] || { symbol: 'ETH', name: 'Ethereum' };
    });

    const feeTokenBalance = computed(() => {
        return props.userBalances[feeTokenInfo.value.symbol] || 0;
    });

    const networkFeeUSD = computed(() => props.networkFee * (props.prices[feeTokenInfo.value.symbol] || 0));

    const isSendingSameAsFeeCurrency = computed(() =>
        selectedAssetToSend.value?.symbol === feeTokenInfo.value.symbol
    );

    const totalCostInToken = computed(() => {
        if (isSendingSameAsFeeCurrency.value && !props.chargeNetworkFee) {
            return (parseFloat(sendAmount.value) || 0) + props.networkFee;
        }
        return parseFloat(sendAmount.value) || 0;
    });

    const totalCostUSD = computed(() => {
        if (!props.chargeNetworkFee) {
            return amountInUSD.value + networkFeeUSD.value;
        }
        return amountInUSD.value;
    });

    const validateAddress = () => {
        addressError.value = '';
        if (!recipientAddress.value) {
            addressError.value = 'Recipient address is required';
            return false;
        }
        if (recipientAddress.value.length < 10) {
            addressError.value = 'Invalid recipient address format';
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

        if (!props.chargeNetworkFee) {
            if (isSendingSameAsFeeCurrency.value) {
                if (totalCostInToken.value > selectedBalance.value) {
                    amountError.value = `Amount + network fee exceeds your ${formatSymbol(selectedAssetToSend.value?.symbol)} balance`;
                    return false;
                }
            } else {
                if (amount > selectedBalance.value) {
                    amountError.value = `Amount exceeds your ${formatSymbol(selectedAssetToSend.value?.symbol)} balance`;
                    return false;
                }

                if (props.networkFee > feeTokenBalance.value) {
                    amountError.value = `Insufficient ${feeTokenInfo.value.symbol} for network fee (Required: ${props.networkFee.toFixed(6)} ${feeTokenInfo.value.symbol})`;
                    return false;
                }
            }
        } else {
            if (amount > selectedBalance.value) {
                amountError.value = `Amount exceeds your ${formatSymbol(selectedAssetToSend.value?.symbol)} balance`;
                return false;
            }
        }

        return true;
    };

    const isFormValid = computed(() => {
        if (props.chargeNetworkFee) {
            return false;
        }

        return selectedAssetToSend.value &&
            recipientAddress.value &&
            sendAmount.value &&
            !addressError.value &&
            !amountError.value;
    });

    const selectAsset = (asset: any) => {
        selectedAssetToSend.value = asset;
        showAssetDropdown.value = false;
        assetSearchQuery.value = '';
        sendAmount.value = '';
        amountError.value = '';
    };

    const setMaxAmount = () => {
        if (!selectedAssetToSend.value) return;

        let maxAmount = 0;

        if (!props.chargeNetworkFee && isSendingSameAsFeeCurrency.value) {
            maxAmount = Math.max(0, selectedBalance.value - props.networkFee);
        } else {
            maxAmount = selectedBalance.value;
        }

        sendAmount.value = maxAmount > 0 ? maxAmount.toFixed(8) : '0';
        validateAmount();
    };

    const pasteFromClipboard = async () => {
        try {
            if (navigator.clipboard && navigator.clipboard.readText) {
                const text = await navigator.clipboard.readText();
                if (text) {
                    recipientAddress.value = text.trim();
                    validateAddress();
                }
            } else {
                alert('Clipboard access is not supported or permission was denied. Please paste manually.');
            }
        } catch (err) {
            console.error('Failed to read clipboard contents: ', err);
            addressError.value = 'Failed to read clipboard. Check browser permissions.';
        }
    };

    const reviewTransaction = () => {
        if (!validateAddress() || !validateAmount()) return;

        emit('review-transaction', {
            token: selectedAssetToSend.value,
            amount: sendAmount.value,
            amountInUSD: amountInUSD.value,
            recipient_address: recipientAddress.value,
            fee: props.networkFee,
            fee_token: feeTokenInfo.value.symbol,
            fee_token_name: feeTokenInfo.value.name,
            feeInUSD: networkFeeUSD.value,
            totalCost: !props.chargeNetworkFee && isSendingSameAsFeeCurrency.value
                ? totalCostInToken.value
                : parseFloat(sendAmount.value),
            totalCostInUSD: totalCostUSD.value,
            balanceAfter: !props.chargeNetworkFee && isSendingSameAsFeeCurrency.value
                ? selectedBalance.value - totalCostInToken.value
                : selectedBalance.value - parseFloat(sendAmount.value),
            feeTokenBalanceAfter: !props.chargeNetworkFee && isSendingSameAsFeeCurrency.value
                ? selectedBalance.value - totalCostInToken.value
                : feeTokenBalance.value - props.networkFee,
        });
    };

    defineExpose({
        resetForm: () => {
            selectedAssetToSend.value = null;
            recipientAddress.value = '';
            sendAmount.value = '';
            addressError.value = '';
            amountError.value = '';
        }
    });

    const closeDropdown = (event: MouseEvent) => {
        if (showAssetDropdown.value && dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
            showAssetDropdown.value = false;
            assetSearchQuery.value = '';
        }
    };

    onMounted(() => document.addEventListener('click', closeDropdown));
    onUnmounted(() => document.removeEventListener('click', closeDropdown));

    const formatSymbol = (symbol: string): string => {
        if (!symbol) return '';
        const formatted = symbol.replace(/USDT_(BEP20|ERC20|TRC20)/i, (match) => {
            return match.replace('_', ' ');
        });
        return formatted.toUpperCase();
    };
</script>

<template>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-card-foreground">Send Crypto</h1>
        <p class="text-sm text-muted-foreground mt-1">Transfer cryptocurrency to any wallet address</p>
    </div>

    <div class="bg-card border border-border rounded-2xl p-6 space-y-6">
        <div v-if="chargeNetworkFee && sendAmount && selectedAssetToSend" class="p-4 bg-destructive/10 border border-border border-destructive rounded-lg">
            <div class="flex items-start gap-3">
                <AlertTriangleIcon class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-destructive mb-1">Network Fee Payment Required</h4>
                    <p class="text-sm text-destructive/90">
                        Insufficient {{ feeTokenInfo.symbol }} balance to cover network fees ({{ networkFee.toFixed(6) }} {{ feeTokenInfo.symbol }} before you can send cryptocurrency. Please add more {{ feeTokenInfo.symbol }} to your wallet to proceed.
                    </p>
                </div>
            </div>
        </div>

        <div>
            <label class="text-sm font-semibold text-card-foreground mb-2 block">Select Asset</label>
            <div class="relative" ref="dropdownRef">
                <button @click="showAssetDropdown = !showAssetDropdown" class="w-full p-4 bg-muted border border-border rounded-lg flex items-center justify-between hover:bg-muted/80 cursor-pointer">
                    <div v-if="selectedAssetToSend" class="flex items-center gap-3">
                        <img :src="selectedAssetToSend.logo" :alt="selectedAssetToSend.symbol" loading="lazy" class="w-8 h-8 rounded-full" />
                        <div class="text-left">
                            <div class="font-semibold text-card-foreground">{{ formatSymbol(selectedAssetToSend.symbol) }}</div>
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
                            <input v-model="assetSearchQuery" type="text" placeholder="Search assets..." class="w-full pl-10 pr-4 py-2 bg-muted border border-border rounded-lg text-sm input-crypto" />
                        </div>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <div v-if="filteredAssets.length === 0" class="p-4 text-center text-sm text-muted-foreground">No assets with balance found</div>
                        <button v-for="asset in filteredAssets" :key="asset.symbol" @click="selectAsset(asset)" class="w-full p-3 hover:bg-muted/50 flex items-center justify-between cursor-pointer">
                            <div class="flex items-center gap-3">
                                <img :src="asset.logo" :alt="asset.symbol" loading="lazy" class="w-8 h-8 rounded-full" />
                                <div class="text-left">
                                    <div class="font-medium text-card-foreground">{{ formatSymbol(asset.symbol) }}</div>
                                    <div class="text-xs text-muted-foreground">{{ asset.name }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-card-foreground">{{ asset.balance.toFixed(4) }}</div>
                                <div class="text-xs text-muted-foreground">${{ asset.value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="selectedAssetToSend" class="mt-3 p-3 bg-muted/30 rounded-lg">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-muted-foreground">Available</div>
                        <div class="font-semibold text-card-foreground">{{ selectedBalance.toFixed(6) }} {{ formatSymbol(selectedAssetToSend.symbol) }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Value</div>
                        <div class="font-semibold text-card-foreground">${{ selectedValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</div>
                    </div>
                </div>
                <div v-if="!isSendingSameAsFeeCurrency" class="mt-3 pt-3 border-t border-border">
                    <div class="text-xs text-muted-foreground mb-1">Network Fee Balance</div>
                    <div class="text-sm font-semibold text-card-foreground">
                        {{ feeTokenBalance.toFixed(6) }} {{ feeTokenInfo.symbol }}
                        <span class="text-xs text-muted-foreground ml-1">({{ feeTokenInfo.name }})</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label class="text-sm font-semibold text-card-foreground mb-2 block">Amount</label>
            <div class="relative">
                <input v-model="sendAmount" @blur="validateAmount" @input="amountError = ''" type="text" step="any" placeholder="0.00" :disabled="!selectedAssetToSend" class="w-full p-4 pr-20 bg-muted border border-border rounded-lg text-lg font-semibold input-crypto disabled:opacity-50 disabled:cursor-not-allowed" />
                <button @click="setMaxAmount" :disabled="!selectedAssetToSend" class="absolute right-3 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-primary text-primary-foreground rounded-md text-sm font-semibold hover:bg-primary/90 transition-opacity disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">
                    MAX
                </button>
            </div>
            <div v-if="amountError" class="mt-2 text-sm text-destructive flex items-center gap-1">
                <AlertCircleIcon class="w-4 h-4" /> {{ amountError }}
            </div>
            <div v-else-if="sendAmount && selectedAssetToSend" class="mt-2 text-sm text-muted-foreground">
                ≈ ${{ amountInUSD.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} USD
            </div>
        </div>

        <div>
            <label class="text-sm font-semibold text-card-foreground mb-2 block">Recipient Address</label>
            <div class="relative">
                <input v-model="recipientAddress"
                       @blur="validateAddress"
                       @input="addressError = ''"
                       type="text"
                       placeholder="0x..."
                       class="w-full p-4 pr-12 bg-muted border border-border rounded-lg font-mono text-sm input-crypto" />
                <button @click="pasteFromClipboard"
                        title="Paste from clipboard"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-card-foreground p-1 transition-colors cursor-pointer">
                    <ClipboardIcon class="w-5 h-5" />
                </button>
            </div>
            <div v-if="addressError" class="mt-2 text-sm text-destructive flex items-center gap-1">
                <AlertCircleIcon class="w-4 h-4" /> {{ addressError }}
            </div>
        </div>

        <div v-if="!sendAmount && !selectedAssetToSend" class="p-4 bg-muted/30 rounded-lg">
            <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-muted-foreground font-semibold">Network Fee</span>
                <span class="font-semibold text-card-foreground">${{ networkFeeUSD.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-muted-foreground">Charged from balance</span>
                <span class="text-muted-foreground">{{ networkFee.toFixed(6) }} (varies by network)</span>
            </div>
        </div>

        <div v-if="sendAmount && selectedAssetToSend" class="p-4 bg-muted/50 rounded-lg space-y-2">
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">You're sending</span>
                <span class="font-semibold text-card-foreground">{{ sendAmount }} {{ formatSymbol(selectedAssetToSend.symbol) }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Network fee</span>
                <div class="text-right">
                    <div class="font-semibold text-card-foreground">{{ networkFee.toFixed(6) }} {{ feeTokenInfo.symbol }}</div>
                    <div class="text-xs text-muted-foreground">{{ feeTokenInfo.name }}</div>
                </div>
            </div>
            <div class="border-t border-border pt-2 flex items-center justify-between">
                <span class="text-sm font-semibold text-card-foreground">Total {{ chargeNetworkFee ? 'amount' : 'cost' }}</span>
                <div class="text-right">
                    <div class="font-bold text-card-foreground">${{ totalCostUSD.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</div>
                    <div v-if="!chargeNetworkFee && isSendingSameAsFeeCurrency" class="text-xs text-muted-foreground">
                        {{ totalCostInToken.toFixed(6) }} {{ formatSymbol(selectedAssetToSend.symbol) }}
                    </div>
                    <div v-else class="text-xs text-muted-foreground">
                        {{ sendAmount }} {{ formatSymbol(selectedAssetToSend.symbol) }}
                    </div>
                </div>
            </div>
        </div>

        <button @click="reviewTransaction" :disabled="!isFormValid" class="w-full py-4 bg-primary text-primary-foreground rounded-lg font-semibold text-lg flex items-center justify-center gap-2 hover:bg-primary/90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
            <SendIcon class="w-5 h-5" />
            {{ chargeNetworkFee && sendAmount && selectedAssetToSend ? `${feeTokenInfo.symbol} Balance Required` : 'Review Transaction' }}
        </button>
    </div>
</template>
