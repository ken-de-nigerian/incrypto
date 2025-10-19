<script setup lang="ts">
    import { usePage } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';

    const copied = ref(false);
    let copyTimeout: number | null = null;

    const page = usePage();

    const referral_link = computed<string>(() => page.props.auth?.referral_link || '');
    const referral_bonus = computed<number>(() => page.props.auth?.referral_bonus || 0);

    const copyLink = () => {
        if (copyTimeout) {
            clearTimeout(copyTimeout);
        }

        navigator.clipboard.writeText(referral_link.value)
            .then(() => {
                copied.value = true;
                copyTimeout = window.setTimeout(() => {
                    copied.value = false;
                    copyTimeout = null;
                }, 3000);
            })
            .catch(err => {
                console.error('Failed to copy link: ', err);
            });
    };
</script>

<template>
    <div class="card-crypto p-4 sm:p-6">
        <h3 class="text-card-foreground font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Invite & Earn Crypto</h3>
        <div class="text-center">

            <div class="bg-secondary/20 rounded-lg sm:rounded-xl p-3 sm:p-4 mb-3 sm:mb-4 flex items-center gap-2 sm:gap-3">
                <div class="text-3xl sm:text-4xl flex-shrink-0">💸</div>
                <p class="text-muted-foreground text-xs sm:text-sm text-left">
                    Earn an instant {{ referral_bonus }}% crypto bonus every time your friend makes their first deposit.
                </p>
            </div>

            <div class="bg-secondary/30 rounded-lg p-2 sm:p-3 mb-2 sm:mb-3">
                <p class="text-muted-foreground text-xs mb-1">YOUR EXCLUSIVE LINK</p>
                <p class="text-card-foreground text-xs sm:text-sm font-mono break-all">{{ referral_link }}</p>
            </div>

            <p class="text-muted-foreground text-xs mb-2 sm:mb-3">Share this link with friends to build your passive income stream.</p>

            <button class="btn-crypto w-full py-2.5 sm:py-3 text-sm sm:text-base cursor-pointer"
                    :class="{
                        'bg-success hover:bg-success/90': copied,
                        'bg-accent hover:bg-accent/90': !copied
                    }"
                    @click="copyLink">
                {{ copied ? 'Link Copied!' : 'Copy Link' }}
            </button>
        </div>
    </div>
</template>
