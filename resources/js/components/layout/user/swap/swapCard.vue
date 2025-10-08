<script setup lang="ts">
    import { ref, computed, watch, onMounted } from 'vue';
    import { router } from '@inertiajs/vue3';
    import axios from 'axios';
    import {
        ZapIcon,
        Settings2Icon,
        AlertCircleIcon,
        ArrowDownIcon,
        RefreshCwIcon,
    } from 'lucide-vue-next';
    import TokenInput from '@/components/layout/user/swap/TokenInput.vue';
    import SwapSettings from '@/components/layout/user/swap/SwapSettings.vue';
    import SwapDetails from '@/components/layout/user/swap/SwapDetails.vue';
    import TokenSelectionModal from '@/components/layout/user/swap/TokenSelectionModal.vue';

    // Define a reusable Token type for better type safety
    interface Token {
        symbol: string;
        name: string;
        address: string;
        logo: string;
        decimals: number;
        chain: string;
        price_change_24h: number;
    }

    const props = defineProps<{
        tokens: Token[];
        userBalances: Record<string, number>;
        prices: Record<string, number>;
        gasPrices: Record<string, { gwei: number; time: string; usd: number }>;
        popularTokens: string[];
        fromToken: Token | null;
        toToken: Token | null;
        fromAmount: string;
        toAmount: string;
        isWalletConnected: boolean;
        isSwapping: boolean;
        needsApproval: boolean;
        isApproving: boolean;
        errorMessage: string;
        slippage: number;
        deadline: number;
        gasPreset: 'low' | 'medium' | 'high';
        walletAddress: string;
        selectedChain: string;
    }>();

    const emit = defineEmits([
        'update:fromToken',
        'update:toToken',
        'update:fromAmount',
        'update:toAmount',
        'update:isSwapping',
        'update:needsApproval',
        'update:isApproving',
        'update:errorMessage',
        'update:slippage',
        'update:deadline',
        'update:gasPreset',
        'update:selectedChain',
    ]);

    // Local UI state
    const isSettingsOpen = ref(false);
    const isFromModalOpen = ref(false);
    const isToModalOpen = ref(false);

    // Computed properties derived from props
    const fromBalance = computed(() => {
        if (!props.fromToken || !props.userBalances) return 0;
        return props.userBalances[props.fromToken.symbol] || 0;
    });

    const toBalance = computed(() => {
        if (!props.toToken || !props.userBalances) return 0;
        return props.userBalances[props.toToken.symbol] || 0;
    });

    const exchangeRate = computed(() => {
        if (!props.fromAmount || isNaN(parseFloat(props.fromAmount))) return 0;
        if (!props.fromToken || !props.toToken || !props.prices) return 0;
        const fromPrice = props.prices[props.fromToken.symbol] || 0;
        const toPrice = props.prices[props.toToken.symbol] || 0;
        if (toPrice === 0) return 0;
        return fromPrice / toPrice;
    });

    const canSwap = computed(() => {
        if (!props.isWalletConnected) return false;
        if (!props.fromAmount || isNaN(parseFloat(props.fromAmount)) || parseFloat(props.fromAmount) <= 0) return false;
        if (parseFloat(props.fromAmount) > fromBalance.value) return false;
        return !(props.isSwapping || props.isApproving);
    });

    const swapButtonText = computed(() => {
        if (!props.isWalletConnected) return 'Connect Wallet';
        if (props.isApproving) return 'Approving...';
        if (props.needsApproval && props.fromAmount) return `Approve ${props.fromToken?.symbol || ''}`;
        if (props.isSwapping) return 'Swapping...';
        if (!props.fromAmount) return 'Enter Amount';
        if (parseFloat(props.fromAmount) > fromBalance.value) return 'Insufficient Balance';
        return 'Swap';
    });

    // Methods that emit events to the parent
    const calculateToAmount = () => {
        if (!props.fromAmount || isNaN(parseFloat(props.fromAmount)) || !exchangeRate.value) {
            emit('update:toAmount', '');
            return;
        }
        // Simulate a slight delay for quote fetching
        setTimeout(() => {
            const amount = parseFloat(props.fromAmount) * exchangeRate.value;
            emit('update:toAmount', amount.toFixed(6));
        }, 300);
    };

    const reverseTokens = () => {
        const tempFromToken = props.fromToken;
        emit('update:fromToken', props.toToken);
        emit('update:toToken', tempFromToken);

        const tempFromAmount = props.fromAmount;
        emit('update:fromAmount', props.toAmount);
        emit('update:toAmount', tempFromAmount);

        emit('update:needsApproval', true);
    };

    const setMaxAmount = () => {
        if (!props.fromToken) return;
        const isNativeEth = props.fromToken.symbol === 'ETH';
        const gasFee = props.gasPrices[props.gasPreset]?.usd || 0;
        const ethPrice = props.prices['ETH'] || 3000;
        // Reserve a small amount for gas if maxing out the native token
        const gasReserve = isNativeEth ? gasFee / ethPrice : 0;
        emit('update:fromAmount', Math.max(0, fromBalance.value - gasReserve).toString());
    };

    const handleSelectFromToken = (token: Token) => {
        if (token.symbol === props.toToken?.symbol) {
            // If selected token is the same as `toToken`, swap them
            emit('update:toToken', props.fromToken);
        }
        emit('update:fromToken', token);
        emit('update:needsApproval', true);
        isFromModalOpen.value = false;
    };

    const handleSelectToToken = (token: Token) => {
        if (token.symbol === props.fromToken?.symbol) {
            // If selected token is the same as `fromToken`, swap them
            emit('update:fromToken', props.toToken);
        }
        emit('update:toToken', token);
        isToModalOpen.value = false;
    };

    const approveToken = async () => {
        emit('update:isApproving', true);
        emit('update:errorMessage', '');
        try {
            await axios.post(route('user.swap.approve'), {
                token: props.fromToken?.symbol,
                amount: props.fromAmount,
                walletAddress: props.walletAddress,
                chain: props.selectedChain
            });
            emit('update:needsApproval', false);
        } catch (error: any) {
            emit('update:errorMessage', error.response?.data?.error || 'Approval failed. Please try again.');
            console.error('Approval failed:', error);
        } finally {
            emit('update:isApproving', false);
        }
    };

    const executeSwap = async () => {
        if (!canSwap.value) {
            return;
        }

        if (props.needsApproval) {
            await approveToken();
            return; // After approval, user must click swap again
        }

        emit('update:isSwapping', true);
        emit('update:errorMessage', '');
        try {
            await axios.post(route('user.swap.process'), {
                fromToken: props.fromToken?.symbol,
                toToken: props.toToken?.symbol,
                fromAmount: parseFloat(props.fromAmount),
                toAmount: parseFloat(props.toAmount),
                walletAddress: props.walletAddress,
                chain: props.selectedChain,
                slippage: props.slippage,
                deadline: props.deadline,
                gasPreset: props.gasPreset
            });

            router.reload({
                only: ['userBalances', 'transactionHistory'],
                onSuccess: () => {
                    emit('update:fromAmount', '');
                    emit('update:toAmount', '');
                    emit('update:needsApproval', true);
                }
            });
        } catch (error: any) {
            emit('update:errorMessage', error.response?.data?.error || 'Swap failed. Please try again.');
            console.error('Swap failed:', error);
        } finally {
            emit('update:isSwapping', false);
        }
    };

    // Watch for changes on the fromAmount prop to recalculate
    watch(() => props.fromAmount, calculateToAmount);

    // Set default tokens on mount if they aren't already set by the parent
    onMounted(() => {
        if (props.tokens && props.tokens.length > 0) {
            if (!props.fromToken) {
                emit('update:fromToken', props.tokens[0]);
            }
            if (!props.toToken) {
                emit('update:toToken', props.tokens[1] || props.tokens[0]);
            }
        }
    });
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="errorMessage"
            class="p-4 bg-destructive/10 border border-destructive/30 rounded-lg flex items-start gap-2">
            <AlertCircleIcon class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
            <div class="text-sm text-destructive">{{ errorMessage }}</div>
        </div>

        <div class="bg-card border border-border rounded-2xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <ZapIcon class="w-5 h-5 text-primary" />
                    <span class="text-sm font-semibold text-card-foreground">Quick Swap</span>
                </div>
                <button @click="isSettingsOpen = !isSettingsOpen" class="p-2 hover:bg-muted rounded-lg">
                    <Settings2Icon class="w-5 h-5 text-muted-foreground" />
                </button>
            </div>

            <SwapSettings
                v-if="isSettingsOpen"
                :slippage="props.slippage"
                :deadline="props.deadline"
                :gas-preset="props.gasPreset"
                :gas-prices="props.gasPrices"
                @update:slippage="emit('update:slippage', $event)"
                @update:deadline="emit('update:deadline', $event)"
                @update:gas-preset="emit('update:gasPreset', $event)"
            />

            <TokenInput
                v-if="props.fromToken"
                label="From"
                :token="props.fromToken"
                :balance="fromBalance"
                :amount="props.fromAmount"
                :price="props.prices[props.fromToken?.symbol] || 0"
                @update:amount="emit('update:fromAmount', $event)"
                @open-modal="isFromModalOpen = true"
                @set-max="setMaxAmount"
            />

            <div class="flex justify-center -my-3 relative z-10">
                <button
                    @click="reverseTokens"
                    class="p-2 bg-card border-2 border-border hover:border-primary rounded-full hover:rotate-180 duration-300">
                    <ArrowDownIcon class="w-5 h-5 text-muted-foreground" />
                </button>
            </div>

            <TokenInput
                v-if="props.toToken"
                label="To"
                :token="props.toToken"
                :balance="toBalance"
                :amount="props.toAmount"
                :price="props.prices[props.toToken?.symbol] || 0"
                :readonly="true"
                @open-modal="isToModalOpen = true"
            />

            <SwapDetails
                :from-token="props.fromToken"
                :to-token="props.toToken"
                :from-amount="props.fromAmount"
                :to-amount="props.toAmount"
                :prices="props.prices"
                :slippage="props.slippage"
                :gas-prices="props.gasPrices"
                :gas-preset="props.gasPreset"
            />

            <button
                @click="executeSwap"
                :disabled="!canSwap"
                :class="[
                    'w-full mt-6 py-4 rounded-xl font-bold text-lg',
                    canSwap
                        ? 'bg-primary hover:opacity-90 text-primary-foreground'
                        : 'bg-muted text-muted-foreground cursor-not-allowed',
                ]">
                <span v-if="isSwapping || isApproving" class="flex items-center justify-center gap-2">
                    <RefreshCwIcon class="w-5 h-5 animate-spin" />
                    {{ swapButtonText }}
                </span>
                <span v-else>{{ swapButtonText }}</span>
            </button>
        </div>

        <TokenSelectionModal
            v-model:is-open="isFromModalOpen"
            :tokens="props.tokens"
            :popular-tokens="props.popularTokens"
            :user-balances="props.userBalances"
            :prices="props.prices"
            @select="handleSelectFromToken"
        />
        <TokenSelectionModal
            v-model:is-open="isToModalOpen"
            :tokens="props.tokens"
            :popular-tokens="props.popularTokens"
            :user-balances="props.userBalances"
            :prices="props.prices"
            @select="handleSelectToToken"
        />
    </div>
</template>
