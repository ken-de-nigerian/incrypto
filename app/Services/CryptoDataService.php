<?php

namespace App\Services;

class CryptoDataService
{
    public static function getCryptoData(): array
    {
        return [
            // ==================== MAJOR CRYPTOCURRENCIES ====================
            ['symbol' => 'BTC', 'polygon' => 'X:BTCUSD', 'name' => 'Bitcoin'],
            ['symbol' => 'ETH', 'polygon' => 'X:ETHUSD', 'name' => 'Ethereum'],
            ['symbol' => 'USDT', 'polygon' => 'X:USDTUSD', 'name' => 'Tether'],
            ['symbol' => 'BNB', 'polygon' => 'X:BNBUSD', 'name' => 'BNB'],
            ['symbol' => 'SOL', 'polygon' => 'X:SOLUSD', 'name' => 'Solana'],
            ['symbol' => 'XRP', 'polygon' => 'X:XRPUSD', 'name' => 'XRP'],

            // ==================== LARGE CAP CRYPTOS ====================
            ['symbol' => 'USDC', 'polygon' => 'X:USDCUSD', 'name' => 'USD Coin'],
            ['symbol' => 'ADA', 'polygon' => 'X:ADAUSD', 'name' => 'Cardano'],
            ['symbol' => 'DOGE', 'polygon' => 'X:DOGEUSD', 'name' => 'Dogecoin'],
            ['symbol' => 'TRX', 'polygon' => 'X:TRXUSD', 'name' => 'TRON'],
            ['symbol' => 'AVAX', 'polygon' => 'X:AVAXUSD', 'name' => 'Avalanche'],
            ['symbol' => 'SHIB', 'polygon' => 'X:SHIBUSD', 'name' => 'Shiba Inu'],
            ['symbol' => 'TON', 'polygon' => 'X:TONUSD', 'name' => 'Toncoin'],
            ['symbol' => 'DOT', 'polygon' => 'X:DOTUSD', 'name' => 'Polkadot'],
            ['symbol' => 'LINK', 'polygon' => 'X:LINKUSD', 'name' => 'Chainlink'],
            ['symbol' => 'MATIC', 'polygon' => 'X:MATICUSD', 'name' => 'Polygon'],
            ['symbol' => 'DAI', 'polygon' => 'X:DAIUSD', 'name' => 'Dai'],
            ['symbol' => 'LTC', 'polygon' => 'X:LTCUSD', 'name' => 'Litecoin'],
            ['symbol' => 'BCH', 'polygon' => 'X:BCHUSD', 'name' => 'Bitcoin Cash'],
            ['symbol' => 'UNI', 'polygon' => 'X:UNIUSD', 'name' => 'Uniswap'],
            ['symbol' => 'NEAR', 'polygon' => 'X:NEARUSD', 'name' => 'NEAR Protocol'],

            // ==================== MID CAP CRYPTOCURRENCIES ====================
            ['symbol' => 'ICP', 'polygon' => 'X:ICPUSD', 'name' => 'Internet Computer'],
            ['symbol' => 'APT', 'polygon' => 'X:APTUSD', 'name' => 'Aptos'],
            ['symbol' => 'FET', 'polygon' => 'X:FETUSD', 'name' => 'Fetch.ai'],
            ['symbol' => 'STX', 'polygon' => 'X:STXUSD', 'name' => 'Stacks'],
            ['symbol' => 'XLM', 'polygon' => 'X:XLMUSD', 'name' => 'Stellar'],
            ['symbol' => 'HBAR', 'polygon' => 'X:HBARUSD', 'name' => 'Hedera'],
            ['symbol' => 'ARB', 'polygon' => 'X:ARBUSD', 'name' => 'Arbitrum'],
            ['symbol' => 'VET', 'polygon' => 'X:VETUSD', 'name' => 'VeChain'],
            ['symbol' => 'FIL', 'polygon' => 'X:FILUSD', 'name' => 'Filecoin'],
            ['symbol' => 'ATOM', 'polygon' => 'X:ATOMUSD', 'name' => 'Cosmos'],
            ['symbol' => 'INJ', 'polygon' => 'X:INJUSD', 'name' => 'Injective'],

            // ==================== DEFI TOKENS ====================
            ['symbol' => 'MKR', 'polygon' => 'X:MKRUSD', 'name' => 'Maker'],
            ['symbol' => 'AAVE', 'polygon' => 'X:AAVEUSD', 'name' => 'Aave'],
            ['symbol' => 'GRT', 'polygon' => 'X:GRTUSD', 'name' => 'The Graph'],
            ['symbol' => 'RUNE', 'polygon' => 'X:RUNEUSD', 'name' => 'THORChain'],
            ['symbol' => 'SNX', 'polygon' => 'X:SNXUSD', 'name' => 'Synthetix'],
            ['symbol' => 'COMP', 'polygon' => 'X:COMPUSD', 'name' => 'Compound'],
            ['symbol' => 'CRV', 'polygon' => 'X:CRVUSD', 'name' => 'Curve DAO Token'],
            ['symbol' => 'SUSHI', 'polygon' => 'X:SUSHIUSD', 'name' => 'SushiSwap'],
            ['symbol' => '1INCH', 'polygon' => 'X:1INCHUSD', 'name' => '1inch'],
            ['symbol' => 'BAL', 'polygon' => 'X:BALUSD', 'name' => 'Balancer'],

            // ==================== LAYER 1 & LAYER 2 ====================
            ['symbol' => 'ETC', 'polygon' => 'X:ETCUSD', 'name' => 'Ethereum Classic'],
            ['symbol' => 'OP', 'polygon' => 'X:OPUSD', 'name' => 'Optimism'],
            ['symbol' => 'ALGO', 'polygon' => 'X:ALGOUSD', 'name' => 'Algorand'],
            ['symbol' => 'FTM', 'polygon' => 'X:FTMUSD', 'name' => 'Fantom'],
            ['symbol' => 'EGLD', 'polygon' => 'X:EGLDUSD', 'name' => 'MultiversX'],
            ['symbol' => 'FLOW', 'polygon' => 'X:FLOWUSD', 'name' => 'Flow'],
            ['symbol' => 'XTZ', 'polygon' => 'X:XTZUSD', 'name' => 'Tezos'],
            ['symbol' => 'THETA', 'polygon' => 'X:THETAUSD', 'name' => 'Theta Network'],
            ['symbol' => 'EOS', 'polygon' => 'X:EOSUSD', 'name' => 'EOS'],
            ['symbol' => 'MINA', 'polygon' => 'X:MINAUSD', 'name' => 'Mina Protocol'],

            // ==================== MEME COINS ====================
            ['symbol' => 'PEPE', 'polygon' => 'X:PEPEUSD', 'name' => 'Pepe'],
            ['symbol' => 'WIF', 'polygon' => 'X:WIFUSD', 'name' => 'dogwifhat'],
            ['symbol' => 'FLOKI', 'polygon' => 'X:FLOKIUSD', 'name' => 'FLOKI'],
            ['symbol' => 'BONK', 'polygon' => 'X:BONKUSD', 'name' => 'Bonk'],

            // ==================== EXCHANGE TOKENS ====================
            ['symbol' => 'OKB', 'polygon' => 'X:OKBUSD', 'name' => 'OKB'],
            ['symbol' => 'CRO', 'polygon' => 'X:CROUSD', 'name' => 'Cronos'],
            ['symbol' => 'LEO', 'polygon' => 'X:LEOUSD', 'name' => 'UNUS SED LEO'],
            ['symbol' => 'HT', 'polygon' => 'X:HTUSD', 'name' => 'Huobi Token'],

            // ==================== GAMING & METAVERSE ====================
            ['symbol' => 'AXS', 'polygon' => 'X:AXSUSD', 'name' => 'Axie Infinity'],
            ['symbol' => 'SAND', 'polygon' => 'X:SANDUSD', 'name' => 'The Sandbox'],
            ['symbol' => 'MANA', 'polygon' => 'X:MANAUSD', 'name' => 'Decentraland'],
            ['symbol' => 'ENJ', 'polygon' => 'X:ENJUSD', 'name' => 'Enjin Coin'],
            ['symbol' => 'GALA', 'polygon' => 'X:GALAUSD', 'name' => 'Gala'],
            ['symbol' => 'IMX', 'polygon' => 'X:IMXUSD', 'name' => 'Immutable'],

            // ==================== INFRASTRUCTURE & ORACLES ====================
            ['symbol' => 'QNT', 'polygon' => 'X:QNTUSD', 'name' => 'Quant'],
            ['symbol' => 'CHZ', 'polygon' => 'X:CHZUSD', 'name' => 'Chiliz'],
            ['symbol' => 'MPLX', 'polygon' => 'X:MPLXUSD', 'name' => 'Metaplex'],
            ['symbol' => 'CAKE', 'polygon' => 'X:CAKEUSD', 'name' => 'PancakeSwap'],

            // ==================== PRIVACY COINS ====================
            ['symbol' => 'XMR', 'polygon' => 'X:XMRUSD', 'name' => 'Monero'],
            ['symbol' => 'ZEC', 'polygon' => 'X:ZECUSD', 'name' => 'Zcash'],

            // ==================== STABLECOINS ====================
            ['symbol' => 'TUSD', 'polygon' => 'X:TUSDUSD', 'name' => 'TrueUSD'],
            ['symbol' => 'USDP', 'polygon' => 'X:USDPUSD', 'name' => 'Pax Dollar'],
            ['symbol' => 'GUSD', 'polygon' => 'X:GUSDUSD', 'name' => 'Gemini Dollar'],
            ['symbol' => 'BUSD', 'polygon' => 'X:BUSDUSD', 'name' => 'Binance USD'],

            // ==================== OTHER NOTABLE CRYPTOS ====================
            ['symbol' => 'KAS', 'polygon' => 'X:KASUSD', 'name' => 'Kaspa'],
            ['symbol' => 'AR', 'polygon' => 'X:ARUSD', 'name' => 'Arweave'],
            ['symbol' => 'NEO', 'polygon' => 'X:NEOUSD', 'name' => 'NEO'],
            ['symbol' => 'KAVA', 'polygon' => 'X:KAVAUSD', 'name' => 'Kava'],
            ['symbol' => 'ZIL', 'polygon' => 'X:ZILUSD', 'name' => 'Zilliqa'],
            ['symbol' => 'DASH', 'polygon' => 'X:DASHUSD', 'name' => 'Dash'],
            ['symbol' => 'WAVES', 'polygon' => 'X:WAVESUSD', 'name' => 'Waves'],
            ['symbol' => 'BSV', 'polygon' => 'X:BSVUSD', 'name' => 'Bitcoin SV'],
            ['symbol' => 'IOTA', 'polygon' => 'X:IOTAUSD', 'name' => 'IOTA'],
            ['symbol' => 'XEM', 'polygon' => 'X:XEMUSD', 'name' => 'NEM'],
            ['symbol' => 'QTUM', 'polygon' => 'X:QTUMUSD', 'name' => 'Qtum'],
            ['symbol' => 'ZRX', 'polygon' => 'X:ZRXUSD', 'name' => '0x'],
            ['symbol' => 'BAT', 'polygon' => 'X:BATUSD', 'name' => 'Basic Attention Token'],
            ['symbol' => 'KLAY', 'polygon' => 'X:KLAYUSD', 'name' => 'Klaytn'],
            ['symbol' => 'CELO', 'polygon' => 'X:CELOUSD', 'name' => 'Celo'],
            ['symbol' => 'ONE', 'polygon' => 'X:ONEUSD', 'name' => 'Harmony'],
            ['symbol' => 'IOTX', 'polygon' => 'X:IOTXUSD', 'name' => 'IoTeX'],
            ['symbol' => 'HNT', 'polygon' => 'X:HNTUSD', 'name' => 'Helium'],
            ['symbol' => 'TFUEL', 'polygon' => 'X:TFUELUSD', 'name' => 'Theta Fuel'],
            ['symbol' => 'ROSE', 'polygon' => 'X:ROSEUSD', 'name' => 'Oasis Network'],
            ['symbol' => 'LRC', 'polygon' => 'X:LRCUSD', 'name' => 'Loopring'],
            ['symbol' => 'ANKR', 'polygon' => 'X:ANKRUSD', 'name' => 'Ankr'],
            ['symbol' => 'AUDIO', 'polygon' => 'X:AUDIOUSD', 'name' => 'Audius'],
            ['symbol' => 'SKL', 'polygon' => 'X:SKLUSD', 'name' => 'SKALE'],
            ['symbol' => 'REN', 'polygon' => 'X:RENUSD', 'name' => 'Ren'],
            ['symbol' => 'SC', 'polygon' => 'X:SCUSD', 'name' => 'Siacoin'],
            ['symbol' => 'ICX', 'polygon' => 'X:ICXUSD', 'name' => 'ICON'],
            ['symbol' => 'ONT', 'polygon' => 'X:ONTUSD', 'name' => 'Ontology'],
            ['symbol' => 'LSK', 'polygon' => 'X:LSKUSD', 'name' => 'Lisk'],
            ['symbol' => 'DGB', 'polygon' => 'X:DGBUSD', 'name' => 'DigiByte'],
            ['symbol' => 'HIVE', 'polygon' => 'X:HIVEUSD', 'name' => 'Hive'],
            ['symbol' => 'SYS', 'polygon' => 'X:SYSUSD', 'name' => 'Syscoin'],
            ['symbol' => 'STEEM', 'polygon' => 'X:STEEMUSD', 'name' => 'Steem'],
            ['symbol' => 'STORJ', 'polygon' => 'X:STORJUSD', 'name' => 'Storj'],
        ];
    }
}
