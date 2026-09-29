<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { Shield } from 'lucide-vue-next';
    import MobileHeader from '@/components/layout/auth/wallet-phrase/MobileHeader.vue';
    import LiveSupport from '@/components/LiveSupport.vue';
</script>

<template>
    <Head title="Secure Wallet" />

    <MobileHeader />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-center p-4 pt-20">
        <div class="w-full max-w-md mx-auto p-4">
            <div class="flex justify-center items-center pt-2 mb-8">
                <Shield class="w-24 h-24 text-accent" />
            </div>

            <div class="flex justify-center items-center flex-col text-center">
                <h2 class="text-2xl font-semibold text-foreground">Secure Your Wallet</h2>
                <p class="text-sm text-muted-foreground pt-6">
                    Secure your wallet save your
                    <span class="text-accent font-bold">Seed Phrase</span> in a trusted location.
                    It's crucial for wallet recovery in case of app lockout or device
                    change, ensuring your funds remain accessible and protected.
                </p>

                <p class="text-sm text-muted-foreground pt-4">
                    It's the only way to recover your wallet if you get locked out of
                    the app or get a new device.
                </p>
            </div>

            <div class="w-full pt-10 flex flex-col items-center gap-4">
                <TextLink :href="route('secure.wallet.phrase.show')" class="bg-accent text-accent-foreground font-semibold text-center py-3 rounded-lg w-full hover:bg-accent/90">
                    Next
                </TextLink>

                <TextLink :href="route('secure.wallet.skip')" method="post" as="button" class="text-muted-foreground hover:text-foreground bg-transparent border-none cursor-pointer">
                    Remind Me Later
                </TextLink>
            </div>
        </div>
    </div>
    <LiveSupport />
</template>
