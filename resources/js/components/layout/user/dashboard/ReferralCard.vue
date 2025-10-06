<template>
    <div class="card-crypto p-4 sm:p-6">
        <h3 class="text-card-foreground font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Referral Program</h3>
        <div class="text-center">
            <h4 class="text-card-foreground text-lg sm:text-xl mb-2">Get <span class="text-primary">100 USDT</span></h4>
            <p class="text-card-foreground font-semibold mb-2 text-sm sm:text-base">Cashback Voucher</p>
            <div class="bg-secondary rounded-lg sm:rounded-xl p-3 sm:p-4 mb-3 sm:mb-4 flex items-center gap-2 sm:gap-3">
                <div class="text-3xl sm:text-4xl flex-shrink-0">💰</div>
                <p class="text-muted-foreground text-xs sm:text-sm text-left">
                    When InTree accumulative deposits more than $50.
                </p>
            </div>
            <div class="bg-secondary rounded-lg p-2 sm:p-3 mb-2 sm:mb-3">
                <p class="text-muted-foreground text-xs mb-1">REFERRAL LINK</p>
                <p class="text-card-foreground text-xs sm:text-sm font-mono break-all">https://crypto-merkulove/design-0010</p>
            </div>
            <p class="text-muted-foreground text-xs mb-2 sm:mb-3">Justo donec enim diam vulputate ut pharetra</p>
            <button class="btn-crypto w-full py-2.5 sm:py-3 text-sm sm:text-base">
                Copy
            </button>
        </div>
    </div>
</template>

<script lang="ts">
    export default {
        name: 'ReferralCard',
    };
</script>
