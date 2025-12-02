<?php

namespace App\Services;

class CommoditiesImageService
{
    /**
     * Get image URL for a commodity symbol
     *
     */
    public static function getTradingViewImage(string $symbol): ?string
    {
        $mapping = self::getSymbolToSlugMapping();

        if (!isset($mapping[$symbol])) {
            return null;
        }

        $slug = $mapping[$symbol];
        return "https://s3-symbol-logo.tradingview.com/$slug--big.svg";
    }

    /**
     * Get all commodities with their image URLs
     *
     * @return array Array of commodities with added 'baseFlagUrl' field
     */
    public static function getCommoditiesWithImages(): array
    {
        $commodities = CommoditiesDataService::getCommoditiesData();

        foreach ($commodities as &$commodity) {
            $commodity['baseFlagUrl'] = self::getTradingViewImage($commodity['symbol']);
        }

        return $commodities;
    }

    /**
     * Map commodity symbols to TradingView slug format
     *
     * @return array
     */
    private static function getSymbolToSlugMapping(): array
    {
        return [
            // Precious Metals
            'XAUUSD' => 'metal/gold',      // Gold vs USD
            'XAGUSD' => 'metal/silver',      // Silver vs USD
            'XPTUSD' => 'metal/platinum',      // Platinum vs USD
            'XPDUSD' => 'metal/palladium',      // Palladium vs USD
            'XAU' => 'metal/gold',         // Gold
            'XAG' => 'metal/silver',         // Silver
            'XPT' => 'metal/platinum',         // Platinum
            'XPD' => 'metal/palladium',         // Palladium

            // Energy - Crude Oil
            'CL' => 'crude-oil',             // WTI Crude Oil
            'USOIL' => 'crude-oil',          // US Oil (WTI)
            'UKOIL' => 'crude-oil',        // UK Oil (Brent)
            'BZ' => 'crude-oil',          // Brent Crude Oil
            'NG' => 'crude-oil',             // Natural Gas
            'NATGAS' => 'crude-oil',         // Natural Gas
            'HO' => 'crude-oil',             // Heating Oil
            'RB' => 'crude-oil',             // RBOB Gasoline

            // Agriculture - Grains
            'ZC' => 'commodity/soybean',        // Corn
            'CORN' => 'commodity/corn',      // Corn
            'ZW' => 'commodity/wheat',        // Wheat
            'WHEAT' => 'commodity/wheat',     // Wheat
            'ZS' => 'commodity/soybean',        // Soybeans
            'ZM' => 'commodity/soybean',        // Soybeans
            'ZL' => 'commodity/soybean',        // Soybeans
            'SOYBEAN' => 'commodity/soybean',   // Soybeans
            'ZO' => 'oats',        // Oats
            'ZR' => 'commodity/rice',        // Rice

            // Agriculture - Soft Commodities
            'KC' => 'commodity/coffee',        // Coffee
            'COFFEE' => 'commodity/coffee',    // Coffee
            'CC' => 'cocoa',        // Cocoa
            'COCOA' => 'cocoa',     // Cocoa
            'SB' => 'commodity/sugar',        // Sugar
            'SUGAR' => 'commodity/sugar',     // Sugar
            'CT' => 'commodity/cotton',        // Cotton
            'COTTON' => 'commodity/cotton',    // Cotton
            'OJ' => 'orange-juice',        // Orange Juice

            // Industrial Metals
            'HG' => 'metal/copper',              // Copper
            'COPPER' => 'metal/copper',          // Copper
            'ALI' => 'metal/aluminum',            // Aluminum
            'ALUMINUM' => 'metal/aluminum',       // Aluminum
            'ZNC' => 'metal/zinc',            // Zinc
            'ZINC' => 'metal/zinc',           // Zinc
            'NI' => 'metal/nickel',              // Nickel
            'NICKEL' => 'metal/nickel',          // Nickel
            'PL' => 'metal/lead',              // Lead
            'LEAD' => 'metal/lead',            // Lead

            // Alternative Commodities
            'LBS' => 'commodity/softs',      // Lumber
            'LUMBER' => 'commodity/softs',   // Lumber

            // Commodity Indices (if needed)
            'DXY' => 'indices/u-s-dollar-index',
        ];
    }
}
