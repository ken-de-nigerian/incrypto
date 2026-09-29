/**
 * Resolve a wallet's stored coin image to a URL.
 * New wallets store full CoinMarketCap logo URLs; wallets created before the switch
 * from CoinGecko store a CoinGecko CDN path until `php artisan wallet:refresh-images` runs.
 */
export const coinImageUrl = (image?: string | null): string => {
    if (!image) return '/assets/images/crypto.png';
    if (/^https?:\/\//.test(image)) return image;
    return `https://coin-images.coingecko.com${image}.png`;
};
