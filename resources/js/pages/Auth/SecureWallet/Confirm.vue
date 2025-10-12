<script setup lang="ts">
    import { Head, useForm } from '@inertiajs/vue3';
    import { route } from 'ziggy-js';
    import { CheckCheck, RefreshCw, XCircle, Lock } from 'lucide-vue-next';
    import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

    const props = defineProps<{
        phrase: string[];
    }>();

    const shuffleArray = (array: string[]): string[] => {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    };

    const wordPool = ref(shuffleArray(props.phrase));
    const selectedPhrase = ref<string[]>([]);
    const isModalOpen = ref(false);

    const form = useForm({
        confirmed_phrase: [] as string[],
    });
    const validationError = ref<string | null>(null);

    const isPhraseComplete = computed(() => selectedPhrase.value.length === props.phrase.length);

    const isCorrect = computed(() => {
        if (!isPhraseComplete.value) return false;
        return selectedPhrase.value.join(' ') === props.phrase.join(' ');
    });

    const isIncorrect = computed(() => {
        if (selectedPhrase.value.length === 0) return false;

        for (let i = 0; i < selectedPhrase.value.length; i++) {
            if (selectedPhrase.value[i] !== props.phrase[i]) {
                return true;
            }
        }
        return false;
    });

    // Function to disable/enable scrolling on the body
    const toggleBodyScroll = (shouldDisable: boolean) => {
        document.body.style.overflow = shouldDisable ? 'hidden' : '';
    };

    // Watcher to automatically trigger the modal when the phrase is correct
    watch(isCorrect, (newVal) => {
        if (newVal) {
            isModalOpen.value = true;
        }
    }, { immediate: true });

    // Watcher to manage body scroll based on modal state
    watch(isModalOpen, (newVal) => {
        toggleBodyScroll(newVal);
    });

    onMounted(() => {
        toggleBodyScroll(isModalOpen.value);
    });

    onUnmounted(() => {
        toggleBodyScroll(false);
    });

    const selectWord = (word: string, indexInPool: number) => {
        if (isPhraseComplete.value) return;

        // Allow selection of any word from the pool.
        selectedPhrase.value.push(word);
        wordPool.value.splice(indexInPool, 1);

        // Check for immediate correctness after selection
        if (selectedPhrase.value.length <= props.phrase.length && selectedPhrase.value[selectedPhrase.value.length - 1] !== props.phrase[selectedPhrase.value.length - 1]) {
            // Mark error only if the last selected word is incorrect
            validationError.value = "Incorrect word selected. Review your phrase order.";
        } else {
            validationError.value = null;
        }

        // If phrase is complete and incorrect, set a persistent error
        if (isPhraseComplete.value && !isCorrect.value) {
            validationError.value = "Phrase completed, but the order is incorrect. Please reset.";
        }
    };

    const deselectLastWord = () => {
        if (selectedPhrase.value.length > 0) {
            const lastWord = selectedPhrase.value.pop() as string;
            // Re-shuffle the wordPool to put the word back in a random location
            const tempPool = [...wordPool.value, lastWord];
            wordPool.value = shuffleArray(tempPool);

            // Clear validation error on deselect
            validationError.value = null;
        }
    };

    // Resetting the confirmation also closes the mandatory modal
    const resetConfirmation = () => {
        wordPool.value = shuffleArray(props.phrase);
        selectedPhrase.value = [];
        validationError.value = null;
        isModalOpen.value = false;
    };

    const submitConfirmation = () => {
        if (!isCorrect.value) {
            isModalOpen.value = false;
            validationError.value = "Validation failed. Please try again.";
            return;
        }

        form.confirmed_phrase = selectedPhrase.value;

        form.post(route('secure.wallet.phrase.confirm.update'), {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
            },
            onError: (errors) => {
                isModalOpen.value = false;
                validationError.value = errors.confirmed_phrase as string || "An unexpected server error occurred.";
            },
        });
    };
</script>

<template>
    <Head title="Confirm Seed Phrase" />

    <div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 flex flex-col items-center justify-start p-6">
        <div class="w-full max-w-md mx-auto pt-10 pb-10">

            <header class="text-center mb-8">
                <CheckCheck class="w-12 h-12 text-accent mx-auto mb-4" />
                <h2 class="text-2xl font-bold text-foreground">Confirm Seed Phrase</h2>
                <p class="text-sm text-muted-foreground mt-2">
                    Tap the words in the correct order to prove you have backed up your phrase.
                </p>
            </header>

            <div v-if="validationError"
                 class="p-3 mb-4 text-sm bg-destructive/10 border border-destructive/50 rounded-lg text-destructive transition-all duration-300"
                 role="alert">
                <div class="flex items-center gap-2 font-medium">
                    <XCircle class="w-4 h-4" />
                    {{ validationError }}
                </div>
            </div>

            <div class="p-4 bg-card border border-border rounded-xl shadow-card mb-6">
                <p class="text-sm font-semibold text-muted-foreground mb-3">Your Recovery Phrase ({{ selectedPhrase.length }}/12)</p>
                <div
                    class="min-h-[100px] grid grid-cols-4 sm:grid-cols-4 gap-2 p-2 border-2 rounded-lg transition-colors"
                    :class="{
                        // MODIFIED: Used destructive/success colors instead of hardcoded red/green
                        'border-destructive bg-destructive/10': isIncorrect,
                        'border-success bg-success/10': isCorrect,
                        'border-dashed border-border': !isIncorrect && !isCorrect
                    }">

                    <button
                        v-for="(word, index) in selectedPhrase"
                        :key="`selected-${index}`"
                        @click="deselectLastWord"
                        type="button"
                        class="flex items-center justify-center space-x-1 bg-accent/10 text-accent font-semibold text-xs py-2 px-1 rounded-md transition-colors hover:bg-accent/20 cursor-pointer relative group"
                        :disabled="form.processing">
                        <span class="text-xs text-muted-foreground font-medium mr-1">{{ index + 1 }}.</span>
                        <span class="truncate">{{ word }}</span>
                        <span class="absolute right-0 top-0 h-full w-4 flex items-center justify-end pr-1 text-destructive opacity-0 group-hover:opacity-100 transition-opacity text-lg">&times;</span>
                    </button>

                    <div v-for="i in (12 - selectedPhrase.length)" :key="`placeholder-${i}`" class="flex items-center justify-center text-xs text-muted-foreground/50 border border-dashed border-border/50 rounded-md py-2">
                        {{ selectedPhrase.length + i }}.
                    </div>
                </div>

                <button
                    @click="deselectLastWord"
                    :disabled="selectedPhrase.length === 0 || form.processing"
                    type="button"
                    class="mt-3 text-xs text-muted-foreground hover:text-foreground transition-colors disabled:opacity-50">
                    &larr; Backspace (Remove Last Word)
                </button>
            </div>


            <div
                v-if="!isPhraseComplete"
                class="p-4 bg-card border border-border rounded-xl shadow-card">
                <p class="text-sm font-semibold text-muted-foreground mb-3">Word Pool</p>
                <div class="grid grid-cols-4 sm:grid-cols-4 gap-2">
                    <button
                        v-for="(word, index) in wordPool"
                        :key="`pool-${word}`"
                        @click="selectWord(word, index)"
                        type="button"
                        class="bg-secondary text-secondary-foreground font-medium text-sm py-2 px-1 rounded-md transition-colors hover:bg-secondary/80 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                        :disabled="form.processing">
                        {{ word }}
                    </button>
                </div>
            </div>

            <div class="w-full pt-6 flex flex-col gap-3">
                <button
                    @click="resetConfirmation"
                    :disabled="form.processing"
                    type="button"
                    class="text-sm text-muted-foreground hover:text-accent flex items-center justify-center gap-2 transition-colors cursor-pointer">
                    <RefreshCw class="w-4 h-4" /> Reset Selection
                </button>
            </div>
        </div>
    </div>

    <Transition name="fade">
        <div v-if="isModalOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center transition-opacity duration-300 ease-in-out">

            <div
                class="w-full mx-auto shadow-2xl overflow-hidden animate-slide-up sm:w-auto sm:max-w-md sm:rounded-xl sm:animate-fade-in"
                :class="{
                    // Ensure the modal remains visually distinct (optional but good for mobile)
                    'absolute bottom-0 rounded-t-3xl': isModalOpen,
                    'sm:relative': isModalOpen
                }">

                <div class="bg-card w-full p-6">

                    <div class="flex justify-center items-center pt-2 pb-6 sm:hidden">
                        <div class="w-16 h-1 rounded-full bg-border"></div>
                    </div>

                    <h1 class="text-xl font-bold py-2 text-center text-foreground">
                        Please, Read This Carefully
                    </h1>

                    <div class="flex justify-start items-start gap-3 bg-secondary/50 rounded-xl p-4 mt-4">
                        <div class="bg-secondary flex justify-center items-center p-2 rounded-md text-accent">
                            <Lock class="w-5 h-5" />
                        </div>

                        <p class="text-sm text-muted-foreground pt-1">
                            The information below is **critical** to guarantee your account security and recovery.
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 text-sm text-muted-foreground py-8">
                        <p>
                            1. **Save your seed phrase carefully.** Avoid saving them on online storage, mobile phones, or any digital register.
                        </p>

                        <p>
                            2. **Write the 12 words down and keep them safe.** You will only need them to restore your account. **If you lose them, you can't access your account anymore.**
                        </p>

                        <p>
                            3. Your Seed is only for your personal use. We don't save it in our databases. **Don't share this sequence of words with anyone else!**
                        </p>
                    </div>

                    <div class="w-full pb-2 flex justify-center items-center gap-3">
                        <button
                            @click="submitConfirmation"
                            :disabled="form.processing"
                            class="block bg-accent text-accent-foreground font-semibold text-center py-3 rounded-lg w-full hover:bg-accent/90 transition-colors cursor-pointer disabled:opacity-50">
                            <span v-if="form.processing">Submitting...</span>
                            <span v-else>I Agree</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

/* Mobile slide-up keyframes */
@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}
/* Desktop fade-in keyframes */
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

/* The modal content container animation logic */
.animate-slide-up {
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
/* Apply fade-in for desktop view only */
@media (min-width: 640px) {
    .animate-slide-up {
        /* Override the slide up for desktop */
        animation: fadeIn 0.3s ease-out;
        transform: none !important;
    }
}
</style>
