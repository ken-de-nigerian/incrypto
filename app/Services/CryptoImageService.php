<?php

namespace App\Services;

class CryptoImageService
{
    /**
     * Get image URL for a crypto symbol
     *
     */
    public static function getTradingViewImage(string $symbol): ?string
    {
        $mapping = self::getSymbolToSlugMapping();

        if (!isset($mapping[$symbol])) {
            return null;
        }

        $slug = $mapping[$symbol];
        return "https://s3-symbol-logo.tradingview.com/crypto/$slug--big.svg";
    }

    /**
     * Get all crypto with their image URLs
     *
     * @return array Array of crypto with added 'baseFlagUrl' field
     */
    public static function getCryptosWithImages(): array
    {
        $cryptos = CryptoDataService::getCryptoData();

        foreach ($cryptos as &$crypto) {
            $crypto['baseFlagUrl'] = self::getTradingViewImage($crypto['symbol']);
        }

        return $cryptos;
    }

    /**
     * Map crypto symbols to TradingView slug format
     *
     * @return array
     */
    private static function getSymbolToSlugMapping(): array
    {
        return [

            // Major Cryptocurrencies
            'BTC' => 'XTVCBTC',
            'ETH' => 'XTVCETH',
            'USDT' => 'XTVCUSDT',
            'BNB' => 'XTVCBNB',
            'SOL' => 'XTVCSOL',
            'XRP' => 'XTVCXRP',

            // Large Cap Cryptos
            'USDC' => 'XTVCUSDC',
            'ADA' => 'XTVCADA',
            'DOGE' => 'XTVCDOGE',
            'TRX' => 'XTVCTRX',
            'AVAX' => 'XTVCAVAX',
            'SHIB' => 'XTVCSHIB',
            'TON' => 'XTVCTON',
            'DOT' => 'XTVCDOT',
            'LINK' => 'XTVCLINK',
            'MATIC' => 'XTVCMATIC',
            'DAI' => 'XTVCDAI',
            'LTC' => 'XTVCLTC',
            'BCH' => 'XTVCBCH',
            'UNI' => 'XTVCUNI',
            'NEAR' => 'XTVCNEAR',

            // Mid Cap Cryptocurrencies
            'ICP' => 'XTVCICP',
            'APT' => 'XTVCAPT',
            'FET' => 'XTVCFET',
            'STX' => 'XTVCSTX',
            'XLM' => 'XTVCXLM',
            'HBAR' => 'XTVCHBAR',
            'ARB' => 'XTVCARBI',
            'VET' => 'XTVCVET',
            'FIL' => 'XTVCFIL',
            'ATOM' => 'XTVCATOM',
            'INJ' => 'XTVCINJ',

            // DeFi Tokens
            'MKR' => 'XTVCMKR',
            'AAVE' => 'XTVCAAVE',
            'GRT' => 'XTVCGRAPH',
            'RUNE' => 'XTVCRUNE',
            'SNX' => 'XTVCSNX',
            'COMP' => 'XTVCCOMP',
            'CRV' => 'XTVCCRV',
            'SUSHI' => 'XTVCSUSHI',
            '1INCH' => 'XTVC1INCH',
            'BAL' => 'XTVCBAL',

            // Layer 1 & Layer 2
            'ETC' => 'XTVCETC',
            'OP' => 'XTVCOP',
            'ALGO' => 'XTVCALGO',
            'FTM' => 'XTVCFTM',
            'EGLD' => 'XTVCEGLD',
            'FLOW' => 'XTVCFLOW',
            'XTZ' => 'XTVCXTZ',
            'THETA' => 'XTVCTHETA',
            'EOS' => 'XTVCEOS',
            'MINA' => 'XTVCMINA',

            // Meme Coins
            'PEPE' => 'XTVCPEPE',
            'WIF' => 'XTVCWIF',
            'FLOKI' => 'XTVCFLOKI',
            'BONK' => 'XTVCBONK',

            // Exchange Tokens
            'OKB' => 'XTVCOKB',
            'CRO' => 'XTVCCRO',
            'LEO' => 'XTVCLEO',
            'HT' => 'XTVCHT',

            // Gaming & Metaverse
            'AXS' => 'XTVCAXS',
            'SAND' => 'XTVCSAND',
            'MANA' => 'XTVCMANA',
            'ENJ' => 'XTVCENJ',
            'GALA' => 'XTVCGALA',
            'IMX' => 'XTVCIMXIMMUTABLEX',

            // Infrastructure & Oracles
            'QNT' => 'XTVCQNT',
            'CHZ' => 'XTVCCHZ',
            'MPLX' => 'XTVCMPLX',
            'CAKE' => 'XTVCCAKE',

            // Privacy Coins
            'XMR' => 'XTVCXMR',
            'ZEC' => 'XTVCZEC',

            // Stablecoins
            'TUSD' => 'XTVCTUSD',
            'USDP' => 'XTVCUSDPPAXDOLLAR',
            'GUSD' => 'XTVCGUSD',
            'BUSD' => 'XTVCBUSD',

            // Other Notable Cryptos
            'KAS' => 'XTVCKAS',
            'AR' => 'XTVCAR',
            'NEO' => 'XTVCNEO',
            'KAVA' => 'XTVCKAVA',
            'ZIL' => 'XTVCZIL',
            'DASH' => 'XTVCDASH',
            'WAVES' => 'XTVCWAVES',
            'BSV' => 'XTVCBSV',
            'IOTA' => 'XTVCIOTA',
            'XEM' => 'XTVCXEM',
            'QTUM' => 'XTVCQTUM',
            'ZRX' => 'XTVCZRX',
            'BAT' => 'XTVCBAT',
            'KLAY' => 'XTVCKLAY',
            'CELO' => 'XTVCCELO',
            'ONE' => 'XTVCONE',
            'IOTX' => 'XTVCIOTX',
            'HNT' => 'XTVCHNT',
            'TFUEL' => 'XTVCTFUEL',
            'ROSE' => 'XTVCROSE',
            'LRC' => 'XTVCLRC',
            'ANKR' => 'XTVCANKR',
            'AUDIO' => 'XTVCAUDIO',
            'SKL' => 'XTVCSKL',
            'REN' => 'XTVCREN',
            'SC' => 'XTVCSC',
            'ICX' => 'XTVCICX',
            'ONT' => 'XTVCONT',
            'LSK' => 'XTVCLSK',
            'DGB' => 'XTVCDGB',
            'HIVE' => 'XTVCHIVE',
            'SYS' => 'XTVCSYS',
            'STEEM' => 'XTVCSTEEM',
            'STORJ' => 'XTVCSTORJ',
        ];
    }
}
