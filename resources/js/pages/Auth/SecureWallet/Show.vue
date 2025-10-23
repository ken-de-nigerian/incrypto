<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import TextLink from '@/components/TextLink.vue';
    import { ClipboardCopy, Check, View } from 'lucide-vue-next';
    import { ref } from 'vue';
    import MobileHeader from '@/components/layout/auth/wallet-phrase/MobileHeader.vue';

    const props = defineProps<{
        phrase: string[];
    }>();

    const isCopied = ref(false);
    const isPhraseVisible = ref(false);

    const copyPhrase = async () => {
        if (!isPhraseVisible.value) {
            return;
        }

        try {
            const phraseString = props.phrase.join(' ');
            await navigator.clipboard.writeText(phraseString);

            isCopied.value = true;
            setTimeout(() => {
                isCopied.value = false;
            }, 3000);
        } catch (err) {
            console.error('Failed to copy phrase: ', err);
        }
    };
    const toggleVisibility = () => {
        isPhraseVisible.value = !isPhraseVisible.value;
    };
</script>

<template>
    <Head title="Write Down Seed Phrase" />

    <MobileHeader />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-center p-4 pt-20">
        <div class="w-full max-w-md mx-auto p-4">
            <header class="text-center mb-8">
                <h2 class="text-2xl font-bold text-foreground">Backup Your Phrase</h2>
                <p class="text-sm text-muted-foreground mt-2">
                    Write down these 12 words in order and keep them in a secure, offline location.
                </p>
            </header>

            <div class="p-4 bg-card border border-border rounded-xl shadow-card relative">

                <div v-if="!isPhraseVisible"
                     class="absolute inset-0 bg-card/95 backdrop-blur-sm flex flex-col justify-center items-center rounded-xl z-10 p-4 transition-opacity duration-300">
                    <h3 class="font-semibold text-foreground text-center">Tap show to open</h3>
                    <p class="text-muted-foreground text-sm text-center pt-3">
                        Make sure nobody is watching your screen
                    </p>
                    <p class="text-muted-foreground text-center text-xs pt-3">
                        Please copy your phrase, we will ask you to confirm this on the next screen.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3 text-sm font-mono text-foreground p-3">
                    <div
                        v-for="(word, index) in phrase"
                        :key="index"
                        class="flex items-center space-x-2 bg-secondary text-secondary-foreground p-3 rounded-sm">
                        <span class="text-xs text-muted-foreground font-semibold w-4 text-left">{{ index + 1 }}.</span>
                        <span>{{ word }}</span>
                    </div>
                </div>
            </div>

            <div class="w-full pt-10 flex gap-4">
                <button
                    v-if="!isPhraseVisible"
                    @click="toggleVisibility"
                    class="flex-1 border text-muted-foreground border-border font-semibold text-center py-3 rounded-lg w-full text-nowrap hover:bg-secondary/50 cursor-pointer flex items-center justify-center gap-2"
                    aria-label="View seed phrase">
                    <View class="w-5 h-5" /> Show
                </button>

                <button
                    v-else
                    @click="copyPhrase"
                    :disabled="!isPhraseVisible"
                    class="flex-1 border text-accent border-accent font-semibold text-center py-3 rounded-lg w-full text-nowrap hover:bg-accent/10 cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    aria-label="Copy seed phrase to clipboard">
                    <span v-if="isCopied" class="flex items-center gap-2">
                        <Check class="w-5 h-5" /> Copied
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <ClipboardCopy class="w-5 h-5" /> Copy
                    </span>
                </button>


                <TextLink :href="route('secure.wallet.phrase.confirm')" class="flex-1 bg-accent text-accent-foreground font-semibold text-center py-3 rounded-lg w-full hover:bg-accent/90 flex items-center justify-center">
                    Continue
                </TextLink>
            </div>
        </div>
    </div>
</template>
