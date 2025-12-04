export interface ManualWallet {
    id: string;
    name: string;
    logo: string | null;
    description: string;
    type: string;
    is_popular?: boolean;
}

export const MANUAL_WALLETS: Record<string, ManualWallet> = {
    'pi-network': {
        id: 'pi-network',
        name: 'Pi Network',
        logo: 'https://s3-symbol-logo.tradingview.com/crypto/XTVCPI3--big.svg',
        description: 'Pi Network is a cryptocurrency project that allows users to mine Pi coins on their mobile phones without draining battery or consuming data.',
        type: 'Mobile Mining',
        is_popular: true,
    },
};

/**
 * Check if a wallet ID is a manually added wallet
 */
export const isManualWallet = (walletId: string): boolean => {
    return walletId in MANUAL_WALLETS;
};

/**
 * Get manual wallet data by ID
 */
export const getManualWallet = (walletId: string): ManualWallet | null => {
    return MANUAL_WALLETS[walletId] || null;
};

/**
 * Get all manual wallets as an array
 */
export const getAllManualWallets = (): ManualWallet[] => {
    return Object.values(MANUAL_WALLETS);
};

/**
 * Get the logo URL for any wallet (manual or API-based)
 */
export const getWalletLogoUrl = (walletId: string, apiLogoUrl?: string): string | null => {
    const manualWallet = getManualWallet(walletId);
    if (manualWallet) {
        return manualWallet.logo;
    }

    if (apiLogoUrl) {
        return `https://www.cryptocompare.com${apiLogoUrl}`;
    }

    return null;
};
