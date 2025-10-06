import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const ONBOARDING_KEY = 'has_completed_onboarding'

export function useOnboarding() {
    const hasCompletedOnboarding = (): boolean => {
        return localStorage.getItem(ONBOARDING_KEY) === 'true'
    }

    const markOnboardingComplete = (): void => {
        localStorage.setItem(ONBOARDING_KEY, 'true')
    }

    const checkAndRedirect = (): void => {
        if (hasCompletedOnboarding()) {
            router.visit(route('login'))
        }
    }

    const clearOnboarding = (): void => {
        localStorage.removeItem(ONBOARDING_KEY)
    }

    return {
        hasCompletedOnboarding,
        markOnboardingComplete,
        checkAndRedirect,
        clearOnboarding,
    }
}
