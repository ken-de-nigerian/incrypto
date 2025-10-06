<template>
    <div class="min-h-screen bg-background">
        <Sidebar />

        <div class="lg:pl-64">
            <!-- Header -->
            <header class="flex items-center justify-between p-6 border-b border-border">
                <div>
                    <h1 class="text-2xl font-bold">Trade</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative hidden sm:block">
                        <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                        <input
                            placeholder="ETH/USDT"
                            class="input-crypto pl-10 pr-4 py-2 w-48"
                        />
                    </div>
                    <button class="relative">
                        <BellIcon class="h-5 w-5 text-muted-foreground" />
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-destructive rounded-full" />
                    </button>
                </div>
            </header>

            <!-- Main Content -->
            <main class="p-6 pb-24 lg:pb-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                    <!-- Left Column - Coin List -->
                    <div class="xl:col-span-4">
                        <div class="card-crypto">
                            <div class="flex flex-col space-y-1.5 p-0">
                                <div class="w-full bg-muted p-1 rounded-lg flex">
                                    <button
                                        v-for="tab in tabs"
                                        :key="tab.value"
                                        @click="selectedTab = tab.value"
                                        :class="`flex-1 py-2 px-3 text-sm font-medium rounded-md transition-all ${
                      selectedTab === tab.value
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                    }`"
                                    >
                                        {{ tab.label }}
                                    </button>
                                </div>
                            </div>
                            <div class="pt-4">
                                <div class="space-y-1">
                                    <div class="grid grid-cols-4 gap-4 text-xs text-muted-foreground p-2 border-b border-border">
                                        <span>Name</span>
                                        <span class="text-right">Price</span>
                                        <span class="text-right">Change 24h</span>
                                        <span></span>
                                    </div>
                                    <div
                                        v-for="(coin, index) in coinData"
                                        :key="index"
                                        class="grid grid-cols-4 gap-4 items-center p-2 hover:bg-muted/20 rounded-lg cursor-pointer"
                                    >
                                        <div class="flex items-center space-x-2">
                                            <button class="text-muted-foreground hover:text-accent">
                                                <StarIcon :class="`h-4 w-4 ${coin.favorited ? 'fill-current text-accent' : ''}`" />
                                            </button>
                                            <div>
                                                <div class="font-medium text-sm">{{ coin.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ coin.symbol }}</div>
                                            </div>
                                        </div>
                                        <div class="text-right text-sm font-medium">
                                            {{ coin.price }}
                                        </div>
                                        <div class="text-right">
                                            <div :class="coin.positive ? 'badge-positive' : 'badge-negative'">
                                                {{ coin.change }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <TrendingUpIcon v-if="coin.positive" class="h-4 w-4 text-success ml-auto" />
                                            <TrendingDownIcon v-else class="h-4 w-4 text-destructive ml-auto" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Center Column - Chart -->
                    <div class="xl:col-span-5 space-y-6">

                        <!-- Chart -->
                        <div class="card-crypto">
                            <div class="pt-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <div class="text-lg font-semibold">BTCUSDT</div>
                                        <div class="text-2xl font-bold">22,228.00</div>
                                        <div class="flex items-center space-x-2">
                                            <div class="badge-positive">1.76%</div>
                                            <span class="text-sm text-muted-foreground">24H: 21,649.40</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chart Placeholder -->
                                <div class="h-80 bg-muted/20 rounded-lg flex items-center justify-center mb-4">
                                    <div class="text-center text-muted-foreground">
                                        <div class="text-lg font-medium">Candlestick Chart</div>
                                        <div class="text-sm">BTCUSDT Trading View</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buy Crypto Panel -->
                        <div class="card-crypto">
                            <div class="flex flex-col space-y-1.5 p-0">
                                <h3 class="text-xl font-bold leading-none tracking-tight flex items-center">
                                    <span class="mr-2">💰</span>
                                    Buy Crypto
                                </h3>
                            </div>
                            <div class="pt-4 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm text-muted-foreground">FROM</label>
                                        <input placeholder="I give" class="input-crypto" />
                                    </div>
                                    <div>
                                        <label class="text-sm text-muted-foreground">TO</label>
                                        <input placeholder="I receive" class="input-crypto" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <select class="input-crypto">
                                        <option value="uah">UAH</option>
                                        <option value="usd">USD</option>
                                        <option value="eur">EUR</option>
                                    </select>

                                    <select class="input-crypto">
                                        <option value="ada">ADA</option>
                                        <option value="btc">BTC</option>
                                        <option value="eth">ETH</option>
                                    </select>
                                </div>

                                <div class="text-sm text-muted-foreground">
                                    <div class="flex justify-between">
                                        <span>Fee:</span>
                                        <span>0.1%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Amount Received:</span>
                                        <span>0 USD</span>
                                    </div>
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    Justo donec enim diam vulputate ut pharetra. Ut placerat orci nulla pellentesque dignissim enim sit.
                                </p>

                                <button class="btn-crypto w-full h-12 px-6 py-3">
                                    Exchange
                                </button>
                            </div>
                        </div>

                        <!-- Deposit Panel -->
                        <div class="card-crypto">
                            <div class="flex flex-col space-y-1.5 p-0">
                                <h3 class="text-xl font-bold leading-none tracking-tight flex items-center">
                                    <span class="mr-2">📥</span>
                                    Deposit
                                </h3>
                            </div>
                            <div class="pt-4 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm text-muted-foreground">AMOUNT</label>
                                        <input placeholder="50" class="input-crypto" />
                                    </div>
                                    <div>
                                        <label class="text-sm text-muted-foreground">MERCHANT</label>
                                        <select class="input-crypto">
                                            <option value="visa">USD Visa/Mastercard Checkout</option>
                                            <option value="paypal">PayPal</option>
                                            <option value="bank">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="text-sm text-muted-foreground">
                                    <div class="flex justify-between">
                                        <span>Fee:</span>
                                        <span>0.1%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Amount Received:</span>
                                        <span>0 USD</span>
                                    </div>
                                </div>

                                <button class="btn-crypto w-full h-12 px-6 py-3">
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="xl:col-span-3 space-y-6">

                        <!-- Total Assets -->
                        <div class="card-crypto">
                            <div class="flex flex-col space-y-1.5 p-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-bold leading-none tracking-tight">Total Assets</h3>
                                </div>
                                <div class="flex items-center space-x-4 mt-4">
                                    <div>
                                        <div class="text-2xl font-bold">2,460.89 <span class="text-sm text-muted-foreground">USD</span></div>
                                        <div class="text-sm text-muted-foreground">0.23415600 BTC</div>
                                    </div>
                                    <div class="w-12 h-12 bg-gradient-to-br from-accent/20 to-accent/40 rounded-lg flex items-center justify-center">
                                        💎
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Wallet Balances -->
                        <div class="card-crypto">
                            <div class="flex flex-col space-y-1.5 p-0">
                                <h3 class="text-xl font-bold leading-none tracking-tight">Wallet Balances</h3>
                            </div>
                            <div class="pt-4">
                                <div class="space-y-3">
                                    <div
                                        v-for="(coin, index) in coinData.slice(0, 6)"
                                        :key="index"
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <div class="flex items-center space-x-2">
                                            <div class="w-6 h-6 bg-muted rounded-full flex items-center justify-center text-xs">
                                                {{ coin.symbol[0] }}
                                            </div>
                                            <span>{{ coin.symbol }}</span>
                                        </div>
                                        <span class="text-muted-foreground">0.00000000</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- History -->
                        <div class="card-crypto">
                            <div class="flex flex-col space-y-1.5 p-0">
                                <h3 class="text-xl font-bold leading-none tracking-tight flex items-center">
                                    <span class="mr-2">📋</span>
                                    History
                                </h3>
                            </div>
                            <div class="pt-4">
                                <div class="space-y-3">
                                    <div v-for="(item, index) in historyData" :key="index" class="space-y-1">
                                        <div class="text-sm text-muted-foreground">{{ item.date }}</div>
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <div class="text-sm font-medium">{{ item.type }}</div>
                                                <div :class="`text-xs ${item.action === 'Deposit' ? 'text-success' : 'text-destructive'}`">
                                                    {{ item.action }}
                                                </div>
                                            </div>
                                            <div class="text-sm font-medium">{{ item.amount }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <MobileNav />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Sidebar from '../components/layout/user/navigation/SidebarNav.vue'
import MobileNav from '../components/layout/user/navigation/MobileNav.vue'
import { Search as SearchIcon, Bell as BellIcon, Star as StarIcon, TrendingUp as TrendingUpIcon, TrendingDown as TrendingDownIcon } from 'lucide-vue-next'

const selectedTab = ref("coins")

const tabs = [
    { value: "favorites", label: "Favorites" },
    { value: "coins", label: "Coins" },
    { value: "zones", label: "Zones" }
]

const coinData = [
    { name: "Bitcoin", symbol: "BTC", price: "$19953.10", change: "+1.76%", positive: true, favorited: true },
    { name: "Chainlink", symbol: "LINK", price: "$7.95", change: "-0.4%", positive: false, favorited: false },
    { name: "Cardano", symbol: "ADA", price: "$0.469800", change: "+0.94%", positive: true, favorited: true },
    { name: "Polygon", symbol: "MATIC", price: "$0.891", change: "+0.5%", positive: true, favorited: false },
    { name: "Ethereum", symbol: "ETH", price: "$1692.36", change: "-1.39%", positive: false, favorited: false },
    { name: "Solana", symbol: "SOL", price: "$35.50", change: "+0.94%", positive: true, favorited: false },
    { name: "XRP", symbol: "XRP", price: "$0.3546", change: "+0.09%", positive: true, favorited: true },
]

const historyData = [
    { date: "15.06.2022", type: "Ethereum (ETH)", action: "Deposit", amount: "0.1832" },
    { date: "12.06.2022", type: "Bitcoin", action: "Withdraw", amount: "0.0003" },
    { date: "12.06.2022", type: "Binance (BUSD)", action: "Deposit", amount: "0.9999" },
]
</script>
