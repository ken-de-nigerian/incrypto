<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { Shield } from 'lucide-vue-next';
</script>

<template>
    <Head title="Secure Wallet" />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md mx-auto">
            <div class="flex justify-center items-center pt-2 mb-8">
                <Shield class="w-32 h-32 text-accent" />
            </div>

            <div class="flex justify-center items-center flex-col text-center">
                <h2 class="text-2xl font-semibold text-foreground">Secure Your Wallet</h2>
                <p class="text-sm text-muted-foreground pt-6">
                    Secure your wallet save your
                    <span class="text-accent font-semibold">Seed Phrase</span> in a trusted location.
                    It's crucial for wallet recovery in case of app lockout or device
                    change, ensuring your funds remain accessible and protected.
                </p>

                <p class="text-sm text-muted-foreground pt-4">
                    It's the only way to recover your wallet if you get locked out of
                    the app or get a new device.
                </p>
            </div>

            <div class="w-full pt-20 flex justify-between items-center gap-4">
                <TextLink :href="route('secure.wallet.skip')" method="post" as="button" class="border text-accent border-accent font-semibold text-center py-3 rounded-lg w-full text-nowrap hover:bg-accent/10 transition-colors cursor-pointer">
                    Remind Me Later
                </TextLink>

                <TextLink :href="route('secure.wallet.phrase.show')" class="bg-accent text-accent-foreground font-semibold text-center py-3 rounded-lg w-full hover:bg-accent/90 transition-colors">
                    Next
                </TextLink>
            </div>
        </div>
    </div>
</template>
