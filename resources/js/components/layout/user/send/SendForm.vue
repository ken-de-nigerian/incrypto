<script setup lang="ts">
    import { computed, ref, onMounted, onUnmounted } from 'vue';
    import { AlertCircleIcon, ChevronDownIcon, SearchIcon, SendIcon, ZapIcon, ClipboardIcon } from 'lucide-vue-next';

    const props = defineProps<{
        availableAssets: Array<any>;
        prices: Record<string, number>;
    }>();

    const emit = defineEmits(['review-transaction']);

    // Form State
    const selectedAssetToSend = ref<any | null>(null);
    const recipientAddress = ref('');
    const sendAmount = ref('');
    const showAssetDropdown = ref(false);
    const assetSearchQuery = ref('');
    const selectedSpeed = ref<'slow' | 'average' | 'fast'>('average');
    const addressError = ref('');
    const amountError = ref('');
    const dropdownRef = ref<HTMLElement | null>(null);

    // Filtered assets for dropdown search
    const filteredAssets = computed(() => {
        if (!assetSearchQuery.value) return props.availableAssets;
        const query = assetSearchQuery.value.toLowerCase();
        return props.availableAssets.filter(asset =>
            asset.symbol.toLowerCase().includes(query) ||
            asset.name.toLowerCase().includes(query)
        );
    });

    // Computed properties for calculations
    const selectedBalance = computed(() => selectedAssetToSend.value?.balance || 0);
    const selectedPrice = computed(() => selectedAssetToSend.value?.price || 0);
    const selectedValue = computed(() => selectedBalance.value * selectedPrice.value);
    const amountInUSD = computed(() => (parseFloat(sendAmount.value) || 0) * selectedPrice.value);

    const networkFees = computed(() => {
        const baseFee = 0.0009; // Base fee in ETH, simplified
        return {
            slow: { fee: baseFee * 0.8, time: '5-10 min' },
            average: { fee: baseFee, time: '2-5 min' },
            fast: { fee: baseFee * 1.5, time: '< 2 min' }
        };
    });

    const selectedFee = computed(() => networkFees.value[selectedSpeed.value].fee);
    const selectedFeeUSD = computed(() => selectedFee.value * (props.prices['ETH'] || 3000)); // Default ETH price for safety
    const totalCost = computed(() => (parseFloat(sendAmount.value) || 0) + selectedFee.value);
    const totalCostUSD = computed(() => amountInUSD.value + selectedFeeUSD.value);

    // Validation
    const validateAddress = () => {
        addressError.value = '';
        if (!recipientAddress.value) {
            addressError.value = 'Recipient address is required';
            return false;
        }
        // Basic length check for an address placeholder
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
        if (totalCost.value > selectedBalance.value) {
            amountError.value = 'Amount + fee exceeds your available balance';
            return false;
        }
        return true;
    };

    const isFormValid = computed(() => {
        return selectedAssetToSend.value && recipientAddress.value && sendAmount.value && !addressError.value && !amountError.value;
    });

    // Methods
    const selectAsset = (asset: any) => {
        selectedAssetToSend.value = asset;
        showAssetDropdown.value = false;
        assetSearchQuery.value = '';
        sendAmount.value = '';
        amountError.value = '';
    };

    const setMaxAmount = () => {
        if (!selectedAssetToSend.value) return;
        const maxAmount = Math.max(0, selectedBalance.value - selectedFee.value);
        sendAmount.value = maxAmount > 0 ? maxAmount.toFixed(8) : '0';
        validateAmount();
    };

    // NEW METHOD: Handle pasting from clipboard
    const pasteFromClipboard = async () => {
        try {
            // Check for Clipboard API support and read text
            if (navigator.clipboard && navigator.clipboard.readText) {
                const text = await navigator.clipboard.readText();
                if (text) {
                    recipientAddress.value = text.trim();
                    validateAddress(); // Re-validate after pasting
                }
            } else {
                // Fallback for non-secure contexts or older browsers
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
            fee: selectedFee.value,
            feeInUSD: selectedFeeUSD.value,
            speed: selectedSpeed.value,
            speedTime: networkFees.value[selectedSpeed.value].time,
            totalCost: totalCost.value,
            totalCostInUSD: totalCostUSD.value,
            balanceAfter: selectedBalance.value - totalCost.value,
        });
    };

    // Manual click-outside handler
    const closeDropdown = (event: MouseEvent) => {
        if (showAssetDropdown.value && dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
            showAssetDropdown.value = false;
            assetSearchQuery.value = '';
        }
    };

    onMounted(() => document.addEventListener('click', closeDropdown));
    onUnmounted(() => document.removeEventListener('click', closeDropdown));

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
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-card-foreground">Send Crypto</h1>
        <p class="text-sm text-muted-foreground mt-1">Transfer cryptocurrency to any wallet address</p>
    </div>

    <div class="bg-card border border-border rounded-2xl p-6 space-y-6">
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
                        <div class="font-semibold text-card-foreground">{{ selectedBalance.toFixed(6) }} {{ formatSymbol(selectedAssetToSend.symbol) }}</div>
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
                <input v-model="sendAmount" @blur="validateAmount" @input="amountError = ''" type="text" step="any" placeholder="0.00" :disabled="!selectedAssetToSend" class="w-full p-4 pr-20 bg-muted border border-border rounded-lg text-lg font-semibold input-crypto disabled:opacity-50 disabled:cursor-not-allowed" />
                <button @click="setMaxAmount" :disabled="!selectedAssetToSend" class="absolute right-3 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-primary text-primary-foreground rounded-md text-sm font-semibold hover:bg-primary/90 transition-opacity disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">
                    MAX
                </button>
            </div>
            <div v-if="amountError" class="mt-2 text-sm text-destructive flex items-center gap-1">
                <AlertCircleIcon class="w-4 h-4" /> {{ amountError }}
            </div>
            <div v-else-if="sendAmount && selectedAssetToSend" class="mt-2 text-sm text-muted-foreground">
                ≈ ${{ amountInUSD.toFixed(2) }} USD
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

        <div>
            <label class="text-sm font-semibold text-card-foreground mb-3 block flex items-center gap-2">
                <ZapIcon class="w-4 h-4" /> Network Speed & Fee
            </label>
            <div class="grid grid-cols-3 gap-3">
                <button v-for="speed in (['slow', 'average', 'fast'] as const)" :key="speed" @click="selectedSpeed = speed" :class="['p-4 border-2 rounded-lg transition-all cursor-pointer', selectedSpeed === speed ? 'border-primary bg-primary/10' : 'border-border hover:border-primary/50']">
                    <div class="text-sm font-semibold text-card-foreground capitalize mb-1">{{ speed }}</div>
                    <div class="text-xs text-muted-foreground mb-2">{{ networkFees[speed].time }}</div>
                    <div class="text-sm font-bold text-primary">${{ (networkFees[speed].fee * (prices['ETH'] || 3000)).toFixed(2) }}</div>
                </button>
            </div>
        </div>

        <div v-if="sendAmount && selectedAssetToSend" class="p-4 bg-muted/50 rounded-lg space-y-2">
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">You're sending</span>
                <span class="font-semibold text-card-foreground">{{ sendAmount }} {{ formatSymbol(selectedAssetToSend.symbol) }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Network fee</span>
                <span class="font-semibold text-card-foreground">${{ selectedFeeUSD.toFixed(2) }}</span>
            </div>
            <div class="border-t border-border pt-2 flex items-center justify-between">
                <span class="text-sm font-semibold text-card-foreground">Total cost</span>
                <div class="text-right">
                    <div class="font-bold text-card-foreground">${{ totalCostUSD.toFixed(2) }}</div>
                    <div class="text-xs text-muted-foreground">{{ totalCost.toFixed(6) }} {{ formatSymbol(selectedAssetToSend.symbol) }}</div>
                </div>
            </div>
        </div>

        <button @click="reviewTransaction" :disabled="!isFormValid" class="w-full py-4 bg-primary text-primary-foreground rounded-lg font-semibold text-lg flex items-center justify-center gap-2 hover:bg-primary/90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
            <SendIcon class="w-5 h-5" />
            Review Transaction
        </button>
    </div>
</template>
