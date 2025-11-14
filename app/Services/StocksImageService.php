<?php

namespace App\Services;

class StocksImageService
{
    /**
     * Get image URL for a stock symbol
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
     * Get all stocks with their image URLs
     *
     * @return array Array of stocks with added 'baseFlagUrl' field
     */
    public static function getStocksWithImages(): array
    {
        $stocks = StocksDataService::getStocksData();

        foreach ($stocks as &$stock) {
            $stock['baseFlagUrl'] = self::getTradingViewImage($stock['symbol']);
        }

        return $stocks;
    }

    /**
     * Map stock symbols to TradingView slug format
     *
     * @return array
     */
    private static function getSymbolToSlugMapping(): array
    {
        return [
            // Major Stocks
            'NVDA' => 'nvidia',
            'AAPL' => 'apple',
            'MSFT' => 'microsoft',
            'GOOGL' => 'alphabet',
            'AMZN' => 'amazon',
            'AVGO' => 'broadcom',

            // Tech Stocks
            'META' => 'meta-platforms',
            'TSLA' => 'tesla',
            'BRK.B' => 'berkshire-hathaway',
            'LLY' => 'eli-lilly',
            'JPM' => 'jpmorgan-chase',
            'WMT' => 'walmart',
            'ORCL' => 'oracle',
            'V' => 'visa',
            'XOM' => 'exxon',
            'MA' => 'mastercard',
            'NFLX' => 'netflix',
            'JNJ' => 'johnson-and-johnson',
            'PLTR' => 'palantir',
            'ABBV' => 'abbvie',
            'COST' => 'costco-wholesale',
            'AMD' => 'advanced-micro-devices',
            'BAC' => 'bank-of-america',
            'HD' => 'home-depot',
            'PG' => 'procter-and-gamble',
            'GE' => 'general-electric',

            // Financial Stocks
            'CVX' => 'chevron',
            'CSCO' => 'cisco',
            'KO' => 'coca-cola',
            'UNH' => 'unitedhealth',
            'IBM' => 'international-bus-mach',
            'MU' => 'micron-technology',
            'WFC' => 'wells-fargo',
            'MS' => 'morgan-stanley',
            'CAT' => 'caterpillar',
            'GS' => 'goldman-sachs',
            'AXP' => 'american-express',

            // Health Care Stocks
            'TMUS' => 't-mobile',
            'PM' => 'philip-morris',
            'RTX' => 'raytheon',
            'MRK' => 'merck',
            'CRM' => 'salesforce',
            'ABT' => 'abbott',
            'TMO' => 'thermo-fisher-scientific',
            'MCD' => 'mcdonalds',
            'PEP' => 'pepsico',
            'LIN' => 'linde',
            'ISRG' => 'intuitive-surgical',

            // Consumer Stocks
            'UBER' => 'uber',
            'APP' => 'applovin',
            'DIS' => 'walt-disney',
            'LRCX' => 'lam-research',
            'QCOM' => 'qualcomm',
            'INTU' => 'intuit',
            'AMGN' => 'amgen',

            // Media Stocks
            'T' => 'at-and-t',
            'C' => 'citigroup',
            'AMAT' => 'applied-materials',

            // Energy Stocks
            'NOW' => 'servicenow',
            'NEE' => 'nextera-energy',

            // Utility Stocks
            'BX' => 'blackstone',
            'BLK' => 'blackrock',
            'VZ' => 'verizon',
            'INTC' => 'intel',
            'SCHW' => 'schwab',

            // Materials Stocks
            'ANET' => 'arista-networks',
            'APH' => 'amphenol',
            'BKNG' => 'booking',
            'TJX' => 'tjx-cos',
            'GEV' => 'ge-verona',
            'DHR' => 'danaher',
            'GILD' => 'gilead',
            'BSX' => 'boston-scientific',
            'ACN' => 'accenture',
            'SPGI' => 's-and-p-global',
            'KLAC' => 'kla-tencor',
            'BA' => 'boeing',
            'TXN' => 'texas-instruments',
            'PFE' => 'pfizer',
            'SYK' => 'stryker',
            'PANW' => 'palo-alto-networks',
            'ADBE' => 'adobe',
            'ETN' => 'eaton',
            'CRWD' => 'crowdstrike',
            'COF' => 'capital-one',

            // Consumer Staples Stocks
            'WELL' => 'welltower-indrn',
            'UNP' => 'union-pacific',
            'PGR' => 'progressive-ohio',
            'DE' => 'deere',
            'LOW' => 'lowe-s',
            'HON' => 'honeywell',
            'MDT' => 'medtronic',
            'PLD' => 'prologis',
            'CB' => 'chubb',
            'ADI' => 'analog-devices',
            'COP' => 'conocophillips',
            'HOOD' => 'robinhood',
            'VRTX' => 'vertex-pharmaceutical',
            'HCA' => 'hca-healthcare',
            'LMT' => 'lockheed-martin',
            'CEG' => 'constellation-energy',
            'KKR' => 'kkr',
            'PH' => 'parker-hannifin',
            'MCK' => 'mckesson',
            'CME' => 'cme',
            'ADP' => 'automatic-data-processing',

            // Real Estate Stocks
            'CMCSA' => 'comcast',
            'SO' => 'southern',
            'CVS' => 'cvs-health',
            'MO' => 'altria',
            'SBUX' => 'starbucks',
            'NEM' => 'newmont',
            'DUK' => 'duke-energy',
            'BMY' => 'bristol-myers-squibb',
            'NKE' => 'nike',
            'TT' => 'trane-technologies',
            'GD' => 'general-dynamics',
            'DELL' => 'dell',
            'DASH' => 'doordash',

            // Industrial Stocks
            'MMC' => 'marsh-and-mclennan',
            'MMM' => '3m',
            'ICE' => 'intercontinental-exchange',
            'CDNS' => 'cadence-design-systems',
            'MCO' => 'moodys',
            'AMT' => 'american-tower',
            'WM' => 'waste-management',
            'ORLY' => 'o-reilly-auto',
            'SHW' => 'sherwin-williams',
            'HWM' => 'howmet-aerospace',
            'UPS' => 'united-parcel',
            'NOC' => 'northrop-grumman',
            'JCI' => 'johnson-controls',
            'BK' => 'bank-of-new-york-mellon',
            'COIN' => 'coinbase',
            'MAR' => 'marriott',
            'EQIX' => 'equinix',
            'APO' => 'apollo',
            'TDG' => 'transdigm-group',
            'CTAS' => 'cintas',

            // Defensive Stocks
            'AON' => 'aon',
            'WMB' => 'williams',
            'ABNB' => 'airbnb',
            'MDLZ' => 'mondelez',
            'ECL' => 'ecolab',
            'USB' => 'us-bancorp',
            'ELV' => 'anthem',
            'SNPS' => 'synopsys',
            'CI' => 'cigna',
            'PNC' => 'pnc-financial',
            'EMR' => 'emerson',
            'REGN' => 'regeneron-pharmaceuticals',
            'GLW' => 'corning',
            'COR' => 'amerisourcebergen',
            'ITW' => 'illinois-tool-works',
            'TEL' => 'te-connectivity',

            // Communication Stocks
            'MNST' => 'monster-beverage',
            'RCL' => 'royal-caribbean-cruises',
            'SPG' => 'simon-property',
            'GM' => 'general-motors',
            'AJG' => 'gallagher',
        ];
    }
}
