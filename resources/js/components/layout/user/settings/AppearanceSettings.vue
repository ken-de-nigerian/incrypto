<script setup lang="ts">
    import { useAppearance } from '@/composables/useAppearance';
    import { Monitor, Moon, Sun } from 'lucide-vue-next';

    const { appearance, updateAppearance } = useAppearance();

    const tabs = [
        { value: 'light', Icon: Sun, label: 'Light' },
        { value: 'dark', Icon: Moon, label: 'Dark' },
        { value: 'system', Icon: Monitor, label: 'System' },
    ] as const;
</script>

<template>
    <div class="space-y-6 margin-bottom">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col space-y-1.5 p-6">
                <div class="text-2xl font-semibold leading-none tracking-tight flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-palette h-5 w-5" aria-hidden="true">
                        <path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"></path>
                        <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle>
                        <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle>
                        <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle>
                        <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle>
                    </svg>
                    <span>Appearance</span>
                </div>

                <div class="text-sm text-muted-foreground mt-2">Customize the look and feel of your dashboard.</div>
            </div>

            <div class="p-6 pt-0 space-y-4">
                <div class="space-y-2">
                    <label class="peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-sm font-medium" for="theme">Theme</label>
                    <p class="text-sm text-muted-foreground">Select your preferred interface theme.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <button
                            v-for="{ value, Icon, label } in tabs"
                            :key="value"
                            @click="updateAppearance(value as 'light' | 'dark' | 'system')"
                            :class="[
                                'flex flex-col items-center justify-center gap-2 rounded-lg border-2 p-6 cursor-pointer',
                                appearance === value
                                    ? 'bg-primary/10 border-primary text-primary'
                                    : 'bg-secondary/20 border-border text-muted-foreground hover:border-primary',
                            ]">
                            <component :is="Icon" class="h-8 w-8" />
                            <span class="text-sm font-semibold">{{ label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media (max-width: 640px) {
    .margin-bottom {
        margin-bottom: 50px;
    }
}
</style>
