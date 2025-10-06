<template>
    <div class="bg-zinc-900 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-zinc-800">
        <div class="flex items-center justify-between text-xs sm:text-sm text-zinc-400 mb-3 sm:mb-4 px-1">
            <button @click="sortBy('name')" class="flex items-center gap-1 hover:text-white transition-colors">
                <span>Name</span>
                <span v-if="sortKey === 'name'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </button>
            <button @click="sortBy('price')" class="flex items-center gap-1 hover:text-white transition-colors">
                <span>Price</span>
                <span v-if="sortKey === 'price'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </button>
            <button @click="sortBy('change')" class="flex items-center gap-1 hover:text-white transition-colors">
                <span class="hidden sm:inline">Change 24h</span>
                <span class="sm:hidden">Change</span>
                <span v-if="sortKey === 'change'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </button>
        </div>

        <div class="space-y-2 sm:space-y-3">
            <div
                v-for="(crypto, idx) in sortedCryptoList"
                :key="idx"
                class="flex items-center justify-between p-2 sm:p-3 bg-zinc-800/50 rounded-lg sm:rounded-xl border border-zinc-800 hover:bg-zinc-800/70 transition-colors"
            >
                <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                    <button
                        @click="toggleFavorite(crypto)"
                        :class="crypto.favorite ? 'text-yellow-400' : 'text-zinc-600'"
                        class="hover:text-yellow-300 transition-colors flex-shrink-0"
                    >
                        <svg v-if="crypto.favorite" class="w-3 h-3 sm:w-4 sm:h-4" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1l2.5 5 5.5.8-4 3.9.9 5.3-4.9-2.6-4.9 2.6.9-5.3-4-3.9 5.5-.8z" />
                        </svg>
                        <svg v-else class="w-3 h-3 sm:w-4 sm:h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M8 1l2.5 5 5.5.8-4 3.9.9 5.3-4.9-2.6-4.9 2.6.9-5.3-4-3.9 5.5-.8z" />
                        </svg>
                    </button>
                    <div :class="['w-6 h-6 sm:w-8 sm:h-8 bg-zinc-800 rounded-full flex items-center justify-center font-bold text-xs sm:text-sm flex-shrink-0', crypto.color]">
                        {{ crypto.icon }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white font-medium text-xs sm:text-sm truncate">{{ crypto.name }}</p>
                        <p class="text-zinc-400 text-xs">{{ crypto.symbol }}</p>
                    </div>
                </div>
                <div class="text-right flex-shrink-0 mx-2 sm:mx-4">
                    <p class="text-white font-medium text-xs sm:text-sm">{{ crypto.price }}</p>
                </div>
                <div :class="['px-1.5 sm:px-2 py-0.5 sm:py-1 rounded text-xs font-semibold flex-shrink-0', crypto.change >= 0 ? 'bg-green-500 text-black' : 'bg-red-500 text-white']">
                    {{ crypto.change >= 0 ? '+' : '' }}{{ crypto.change }}%
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
export default {
    name: 'CryptoListCard',
    data() {
        return {
            sortKey: 'name',
            sortOrder: 'asc',
            cryptoList: [
                { name: 'Bitcoin', symbol: 'BTC', price: '$19953.10', change: 1.76, favorite: true, icon: '₿', color: 'text-orange-500' },
                { name: 'Chainlink', symbol: 'LINK', price: '$7.95', change: -0.4, favorite: false, icon: '⬡', color: 'text-blue-500' },
                { name: 'Cardano', symbol: 'ADA', price: '$0.463800', change: 0.94, favorite: true, icon: '◆', color: 'text-blue-400' },
                { name: 'Polygon', symbol: 'MATIC', price: '$0.891', change: 0.5, favorite: false, icon: '◈', color: 'text-purple-500' },
                { name: 'Ethereum', symbol: 'ETH', price: '$1692.36', change: -1.39, favorite: false, icon: 'Ξ', color: 'text-gray-400' },
                { name: 'Solana', symbol: 'SOL', price: '$35.50', change: 0.94, favorite: false, icon: '◈', color: 'text-purple-400' },
            ],
        };
    },
    computed: {
        sortedCryptoList() {
            const list = [...this.cryptoList];
            return list.sort((a, b) => {
                let aVal, bVal;

                if (this.sortKey === 'name') {
                    aVal = a.name.toLowerCase();
                    bVal = b.name.toLowerCase();
                } else if (this.sortKey === 'price') {
                    aVal = parseFloat(a.price.replace(/[^0-9.]/g, ''));
                    bVal = parseFloat(b.price.replace(/[^0-9.]/g, ''));
                } else if (this.sortKey === 'change') {
                    aVal = a.change;
                    bVal = b.change;
                }

                if (this.sortOrder === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
        },
    },
    methods: {
        toggleFavorite(crypto) {
            crypto.favorite = !crypto.favorite;
        },
        sortBy(key) {
            if (this.sortKey === key) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortOrder = 'asc';
            }
        },
    },
};
</script>
