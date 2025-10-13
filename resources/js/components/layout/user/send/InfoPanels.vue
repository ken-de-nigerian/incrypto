<script setup lang="ts">
    import { HelpCircleIcon, AlertCircleIcon } from 'lucide-vue-next';
</script>

<template>
    <div class="hidden sm:block">
        <div class="bg-card border border-border rounded-2xl p-4 mb-4"> <h3 class="text-sm font-semibold text-card-foreground mb-4 flex items-center gap-2">
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
    </div>
</template>
