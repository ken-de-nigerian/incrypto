<?php

namespace App\Services;

class pairsDataService
{
    public static function getPairsData(): array
    {
        return [
            // ==================== MAJOR PAIRS ====================
            // USD as quote or base with major currencies
            ['symbol' => 'EUR/USD', 'polygon' => 'C:EURUSD', 'name' => 'Euro vs US Dollar'],
            ['symbol' => 'GBP/USD', 'polygon' => 'C:GBPUSD', 'name' => 'British Pound vs US Dollar'],
            ['symbol' => 'USD/JPY', 'polygon' => 'C:USDJPY', 'name' => 'US Dollar vs Japanese Yen'],
            ['symbol' => 'USD/CHF', 'polygon' => 'C:USDCHF', 'name' => 'US Dollar vs Swiss Franc'],
            ['symbol' => 'AUD/USD', 'polygon' => 'C:AUDUSD', 'name' => 'Australian Dollar vs US Dollar'],
            ['symbol' => 'USD/CAD', 'polygon' => 'C:USDCAD', 'name' => 'US Dollar vs Canadian Dollar'],
            ['symbol' => 'NZD/USD', 'polygon' => 'C:NZDUSD', 'name' => 'New Zealand Dollar vs US Dollar'],

            // ==================== MINOR/CROSS PAIRS ====================
            // EUR crosses
            ['symbol' => 'EUR/GBP', 'polygon' => 'C:EURGBP', 'name' => 'Euro vs British Pound'],
            ['symbol' => 'EUR/JPY', 'polygon' => 'C:EURJPY', 'name' => 'Euro vs Japanese Yen'],
            ['symbol' => 'EUR/CHF', 'polygon' => 'C:EURCHF', 'name' => 'Euro vs Swiss Franc'],
            ['symbol' => 'EUR/AUD', 'polygon' => 'C:EURAUD', 'name' => 'Euro vs Australian Dollar'],
            ['symbol' => 'EUR/CAD', 'polygon' => 'C:EURCAD', 'name' => 'Euro vs Canadian Dollar'],
            ['symbol' => 'EUR/NZD', 'polygon' => 'C:EURNZD', 'name' => 'Euro vs New Zealand Dollar'],
            ['symbol' => 'EUR/NOK', 'polygon' => 'C:EURNOK', 'name' => 'Euro vs Norwegian Krone'],
            ['symbol' => 'EUR/SEK', 'polygon' => 'C:EURSEK', 'name' => 'Euro vs Swedish Krona'],
            ['symbol' => 'EUR/DKK', 'polygon' => 'C:EURDKK', 'name' => 'Euro vs Danish Krone'],
            ['symbol' => 'EUR/PLN', 'polygon' => 'C:EURPLN', 'name' => 'Euro vs Polish Zloty'],
            ['symbol' => 'EUR/HUF', 'polygon' => 'C:EURHUF', 'name' => 'Euro vs Hungarian Forint'],
            ['symbol' => 'EUR/CZK', 'polygon' => 'C:EURCZK', 'name' => 'Euro vs Czech Koruna'],
            ['symbol' => 'EUR/RON', 'polygon' => 'C:EURRON', 'name' => 'Euro vs Romanian Leu'],
            ['symbol' => 'EUR/TRY', 'polygon' => 'C:EURTRY', 'name' => 'Euro vs Turkish Lira'],
            ['symbol' => 'EUR/RUB', 'polygon' => 'C:EURRUB', 'name' => 'Euro vs Russian Ruble'],
            ['symbol' => 'EUR/ZAR', 'polygon' => 'C:EURZAR', 'name' => 'Euro vs South African Rand'],
            ['symbol' => 'EUR/SGD', 'polygon' => 'C:EURSGD', 'name' => 'Euro vs Singapore Dollar'],
            ['symbol' => 'EUR/HKD', 'polygon' => 'C:EURHKD', 'name' => 'Euro vs Hong Kong Dollar'],
            ['symbol' => 'EUR/MXN', 'polygon' => 'C:EURMXN', 'name' => 'Euro vs Mexican Peso'],
            ['symbol' => 'EUR/BRL', 'polygon' => 'C:EURBRL', 'name' => 'Euro vs Brazilian Real'],
            ['symbol' => 'EUR/ISK', 'polygon' => 'C:EURISK', 'name' => 'Euro vs Icelandic Krona'],

            // GBP crosses
            ['symbol' => 'GBP/JPY', 'polygon' => 'C:GBPJPY', 'name' => 'British Pound vs Japanese Yen'],
            ['symbol' => 'GBP/CHF', 'polygon' => 'C:GBPCHF', 'name' => 'British Pound vs Swiss Franc'],
            ['symbol' => 'GBP/AUD', 'polygon' => 'C:GBPAUD', 'name' => 'British Pound vs Australian Dollar'],
            ['symbol' => 'GBP/CAD', 'polygon' => 'C:GBPCAD', 'name' => 'British Pound vs Canadian Dollar'],
            ['symbol' => 'GBP/NZD', 'polygon' => 'C:GBPNZD', 'name' => 'British Pound vs New Zealand Dollar'],
            ['symbol' => 'GBP/NOK', 'polygon' => 'C:GBPNOK', 'name' => 'British Pound vs Norwegian Krone'],
            ['symbol' => 'GBP/SEK', 'polygon' => 'C:GBPSEK', 'name' => 'British Pound vs Swedish Krona'],
            ['symbol' => 'GBP/PLN', 'polygon' => 'C:GBPPLN', 'name' => 'British Pound vs Polish Zloty'],
            ['symbol' => 'GBP/SGD', 'polygon' => 'C:GBPSGD', 'name' => 'British Pound vs Singapore Dollar'],
            ['symbol' => 'GBP/ZAR', 'polygon' => 'C:GBPZAR', 'name' => 'British Pound vs South African Rand'],
            ['symbol' => 'GBP/TRY', 'polygon' => 'C:GBPTRY', 'name' => 'British Pound vs Turkish Lira'],

            // JPY crosses
            ['symbol' => 'AUD/JPY', 'polygon' => 'C:AUDJPY', 'name' => 'Australian Dollar vs Japanese Yen'],
            ['symbol' => 'CAD/JPY', 'polygon' => 'C:CADJPY', 'name' => 'Canadian Dollar vs Japanese Yen'],
            ['symbol' => 'CHF/JPY', 'polygon' => 'C:CHFJPY', 'name' => 'Swiss Franc vs Japanese Yen'],
            ['symbol' => 'NZD/JPY', 'polygon' => 'C:NZDJPY', 'name' => 'New Zealand Dollar vs Japanese Yen'],
            ['symbol' => 'SGD/JPY', 'polygon' => 'C:SGDJPY', 'name' => 'Singapore Dollar vs Japanese Yen'],
            ['symbol' => 'HKD/JPY', 'polygon' => 'C:HKDJPY', 'name' => 'Hong Kong Dollar vs Japanese Yen'],
            ['symbol' => 'ZAR/JPY', 'polygon' => 'C:ZARJPY', 'name' => 'South African Rand vs Japanese Yen'],
            ['symbol' => 'MXN/JPY', 'polygon' => 'C:MXNJPY', 'name' => 'Mexican Peso vs Japanese Yen'],
            ['symbol' => 'TRY/JPY', 'polygon' => 'C:TRYJPY', 'name' => 'Turkish Lira vs Japanese Yen'],
            ['symbol' => 'NOK/JPY', 'polygon' => 'C:NOKJPY', 'name' => 'Norwegian Krone vs Japanese Yen'],
            ['symbol' => 'SEK/JPY', 'polygon' => 'C:SEKJPY', 'name' => 'Swedish Krona vs Japanese Yen'],

            // AUD crosses
            ['symbol' => 'AUD/CAD', 'polygon' => 'C:AUDCAD', 'name' => 'Australian Dollar vs Canadian Dollar'],
            ['symbol' => 'AUD/CHF', 'polygon' => 'C:AUDCHF', 'name' => 'Australian Dollar vs Swiss Franc'],
            ['symbol' => 'AUD/NZD', 'polygon' => 'C:AUDNZD', 'name' => 'Australian Dollar vs New Zealand Dollar'],
            ['symbol' => 'AUD/SGD', 'polygon' => 'C:AUDSGD', 'name' => 'Australian Dollar vs Singapore Dollar'],
            ['symbol' => 'AUD/HKD', 'polygon' => 'C:AUDHKD', 'name' => 'Australian Dollar vs Hong Kong Dollar'],
            ['symbol' => 'AUD/MXN', 'polygon' => 'C:AUDMXN', 'name' => 'Australian Dollar vs Mexican Peso'],
            ['symbol' => 'AUD/ZAR', 'polygon' => 'C:AUDZAR', 'name' => 'Australian Dollar vs South African Rand'],

            // CAD crosses
            ['symbol' => 'CAD/CHF', 'polygon' => 'C:CADCHF', 'name' => 'Canadian Dollar vs Swiss Franc'],
            ['symbol' => 'NZD/CAD', 'polygon' => 'C:NZDCAD', 'name' => 'New Zealand Dollar vs Canadian Dollar'],
            ['symbol' => 'SGD/CAD', 'polygon' => 'C:SGDCAD', 'name' => 'Singapore Dollar vs Canadian Dollar'],

            // NZD crosses
            ['symbol' => 'NZD/CHF', 'polygon' => 'C:NZDCHF', 'name' => 'New Zealand Dollar vs Swiss Franc'],
            ['symbol' => 'NZD/SGD', 'polygon' => 'C:NZDSGD', 'name' => 'New Zealand Dollar vs Singapore Dollar'],

            // CHF crosses
            ['symbol' => 'CHF/SGD', 'polygon' => 'C:CHFSGD', 'name' => 'Swiss Franc vs Singapore Dollar'],
            ['symbol' => 'CHF/ZAR', 'polygon' => 'C:CHFZAR', 'name' => 'Swiss Franc vs South African Rand'],
            ['symbol' => 'CHF/HKD', 'polygon' => 'C:CHFHKD', 'name' => 'Swiss Franc vs Hong Kong Dollar'],
            ['symbol' => 'CHF/NOK', 'polygon' => 'C:CHFNOK', 'name' => 'Swiss Franc vs Norwegian Krone'],
            ['symbol' => 'CHF/SEK', 'polygon' => 'C:CHFSEK', 'name' => 'Swiss Franc vs Swedish Krona'],

            // ==================== USD PAIRS - AMERICAS ====================
            ['symbol' => 'USD/MXN', 'polygon' => 'C:USDMXN', 'name' => 'US Dollar vs Mexican Peso'],
            ['symbol' => 'USD/BRL', 'polygon' => 'C:USDBRL', 'name' => 'US Dollar vs Brazilian Real'],
            ['symbol' => 'USD/ARS', 'polygon' => 'C:USDARS', 'name' => 'US Dollar vs Argentine Peso'],
            ['symbol' => 'USD/CLP', 'polygon' => 'C:USDCLP', 'name' => 'US Dollar vs Chilean Peso'],
            ['symbol' => 'USD/COP', 'polygon' => 'C:USDCOP', 'name' => 'US Dollar vs Colombian Peso'],
            ['symbol' => 'USD/PEN', 'polygon' => 'C:USDPEN', 'name' => 'US Dollar vs Peruvian Sol'],
            ['symbol' => 'USD/UYU', 'polygon' => 'C:USDUYU', 'name' => 'US Dollar vs Uruguayan Peso'],
            ['symbol' => 'USD/PYG', 'polygon' => 'C:USDPYG', 'name' => 'US Dollar vs Paraguayan Guarani'],
            ['symbol' => 'USD/BOB', 'polygon' => 'C:USDBOB', 'name' => 'US Dollar vs Bolivian Boliviano'],
            ['symbol' => 'USD/VES', 'polygon' => 'C:USDVES', 'name' => 'US Dollar vs Venezuelan Bolivar'],
            ['symbol' => 'USD/CRC', 'polygon' => 'C:USDCRC', 'name' => 'US Dollar vs Costa Rican Colón'],
            ['symbol' => 'USD/GTQ', 'polygon' => 'C:USDGTQ', 'name' => 'US Dollar vs Guatemalan Quetzal'],
            ['symbol' => 'USD/HNL', 'polygon' => 'C:USDHNL', 'name' => 'US Dollar vs Honduran Lempira'],
            ['symbol' => 'USD/NIO', 'polygon' => 'C:USDNIO', 'name' => 'US Dollar vs Nicaraguan Córdoba'],
            ['symbol' => 'USD/DOP', 'polygon' => 'C:USDDOP', 'name' => 'US Dollar vs Dominican Peso'],
            ['symbol' => 'USD/JMD', 'polygon' => 'C:USDJMD', 'name' => 'US Dollar vs Jamaican Dollar'],
            ['symbol' => 'USD/TTD', 'polygon' => 'C:USDTTD', 'name' => 'US Dollar vs Trinidad and Tobago Dollar'],
            ['symbol' => 'USD/BBD', 'polygon' => 'C:USDBBDUS', 'name' => 'US Dollar vs Barbadian Dollar'],
            ['symbol' => 'USD/CUP', 'polygon' => 'C:USDCUP', 'name' => 'US Dollar vs Cuban Peso'],
            ['symbol' => 'USD/HTG', 'polygon' => 'C:USDHTG', 'name' => 'US Dollar vs Haitian Gourde'],

            // ==================== USD PAIRS - ASIA ====================
            ['symbol' => 'USD/CNY', 'polygon' => 'C:USDCNY', 'name' => 'US Dollar vs Chinese Yuan'],
            ['symbol' => 'USD/CNH', 'polygon' => 'C:USDCNH', 'name' => 'US Dollar vs Chinese Yuan (Offshore)'],
            ['symbol' => 'USD/HKD', 'polygon' => 'C:USDHKD', 'name' => 'US Dollar vs Hong Kong Dollar'],
            ['symbol' => 'USD/SGD', 'polygon' => 'C:USDSGD', 'name' => 'US Dollar vs Singapore Dollar'],
            ['symbol' => 'USD/INR', 'polygon' => 'C:USDINR', 'name' => 'US Dollar vs Indian Rupee'],
            ['symbol' => 'USD/KRW', 'polygon' => 'C:USDKRW', 'name' => 'US Dollar vs South Korean Won'],
            ['symbol' => 'USD/TWD', 'polygon' => 'C:USDTWD', 'name' => 'US Dollar vs Taiwan Dollar'],
            ['symbol' => 'USD/THB', 'polygon' => 'C:USDTHB', 'name' => 'US Dollar vs Thai Baht'],
            ['symbol' => 'USD/MYR', 'polygon' => 'C:USDMYR', 'name' => 'US Dollar vs Malaysian Ringgit'],
            ['symbol' => 'USD/IDR', 'polygon' => 'C:USDIDR', 'name' => 'US Dollar vs Indonesian Rupiah'],
            ['symbol' => 'USD/PHP', 'polygon' => 'C:USDPHP', 'name' => 'US Dollar vs Philippine Peso'],
            ['symbol' => 'USD/VND', 'polygon' => 'C:USDVND', 'name' => 'US Dollar vs Vietnamese Dong'],
            ['symbol' => 'USD/PKR', 'polygon' => 'C:USDPKR', 'name' => 'US Dollar vs Pakistani Rupee'],
            ['symbol' => 'USD/BDT', 'polygon' => 'C:USDBDT', 'name' => 'US Dollar vs Bangladeshi Taka'],
            ['symbol' => 'USD/LKR', 'polygon' => 'C:USDLKR', 'name' => 'US Dollar vs Sri Lankan Rupee'],
            ['symbol' => 'USD/MMK', 'polygon' => 'C:USDMMK', 'name' => 'US Dollar vs Myanmar Kyat'],
            ['symbol' => 'USD/KHR', 'polygon' => 'C:USDKHR', 'name' => 'US Dollar vs Cambodian Riel'],
            ['symbol' => 'USD/LAK', 'polygon' => 'C:USDLAK', 'name' => 'US Dollar vs Lao Kip'],
            ['symbol' => 'USD/NPR', 'polygon' => 'C:USDNPR', 'name' => 'US Dollar vs Nepalese Rupee'],
            ['symbol' => 'USD/MVR', 'polygon' => 'C:USDMVR', 'name' => 'US Dollar vs Maldivian Rufiyaa'],
            ['symbol' => 'USD/MNT', 'polygon' => 'C:USDMNT', 'name' => 'US Dollar vs Mongolian Tögrög'],

            // ==================== USD PAIRS - MIDDLE EAST ====================
            ['symbol' => 'USD/AED', 'polygon' => 'C:USDAED', 'name' => 'US Dollar vs UAE Dirham'],
            ['symbol' => 'USD/SAR', 'polygon' => 'C:USDSAR', 'name' => 'US Dollar vs Saudi Riyal'],
            ['symbol' => 'USD/QAR', 'polygon' => 'C:USDQAR', 'name' => 'US Dollar vs Qatari Riyal'],
            ['symbol' => 'USD/KWD', 'polygon' => 'C:USDKWD', 'name' => 'US Dollar vs Kuwaiti Dinar'],
            ['symbol' => 'USD/BHD', 'polygon' => 'C:USDBHD', 'name' => 'US Dollar vs Bahraini Dinar'],
            ['symbol' => 'USD/OMR', 'polygon' => 'C:USDOMR', 'name' => 'US Dollar vs Omani Rial'],
            ['symbol' => 'USD/ILS', 'polygon' => 'C:USDILS', 'name' => 'US Dollar vs Israeli Shekel'],
            ['symbol' => 'USD/JOD', 'polygon' => 'C:USDJOD', 'name' => 'US Dollar vs Jordanian Dinar'],
            ['symbol' => 'USD/LBP', 'polygon' => 'C:USDLBP', 'name' => 'US Dollar vs Lebanese Pound'],
            ['symbol' => 'USD/SYP', 'polygon' => 'C:USDSYP', 'name' => 'US Dollar vs Syrian Pound'],
            ['symbol' => 'USD/IQD', 'polygon' => 'C:USDIQD', 'name' => 'US Dollar vs Iraqi Dinar'],
            ['symbol' => 'USD/IRR', 'polygon' => 'C:USDIRR', 'name' => 'US Dollar vs Iranian Rial'],
            ['symbol' => 'USD/YER', 'polygon' => 'C:USDYER', 'name' => 'US Dollar vs Yemeni Rial'],

            // ==================== USD PAIRS - AFRICA ====================
            ['symbol' => 'USD/ZAR', 'polygon' => 'C:USDZAR', 'name' => 'US Dollar vs South African Rand'],
            ['symbol' => 'USD/EGP', 'polygon' => 'C:USDEGP', 'name' => 'US Dollar vs Egyptian Pound'],
            ['symbol' => 'USD/NGN', 'polygon' => 'C:USDNGN', 'name' => 'US Dollar vs Nigerian Naira'],
            ['symbol' => 'USD/KES', 'polygon' => 'C:USDKES', 'name' => 'US Dollar vs Kenyan Shilling'],
            ['symbol' => 'USD/GHS', 'polygon' => 'C:USDGHS', 'name' => 'US Dollar vs Ghanaian Cedi'],
            ['symbol' => 'USD/TZS', 'polygon' => 'C:USDTZS', 'name' => 'US Dollar vs Tanzanian Shilling'],
            ['symbol' => 'USD/UGX', 'polygon' => 'C:USDUGX', 'name' => 'US Dollar vs Ugandan Shilling'],
            ['symbol' => 'USD/MAD', 'polygon' => 'C:USDMAD', 'name' => 'US Dollar vs Moroccan Dirham'],
            ['symbol' => 'USD/ETB', 'polygon' => 'C:USDETB', 'name' => 'US Dollar vs Ethiopian Birr'],
            ['symbol' => 'USD/DZD', 'polygon' => 'C:USDDZD', 'name' => 'US Dollar vs Algerian Dinar'],
            ['symbol' => 'USD/TND', 'polygon' => 'C:USDTND', 'name' => 'US Dollar vs Tunisian Dinar'],
            ['symbol' => 'USD/LYD', 'polygon' => 'C:USDLYD', 'name' => 'US Dollar vs Libyan Dinar'],
            ['symbol' => 'USD/AOA', 'polygon' => 'C:USDAOA', 'name' => 'US Dollar vs Angolan Kwanza'],
            ['symbol' => 'USD/MZN', 'polygon' => 'C:USDMZN', 'name' => 'US Dollar vs Mozambican Metical'],
            ['symbol' => 'USD/ZMW', 'polygon' => 'C:USDZMW', 'name' => 'US Dollar vs Zambian Kwacha'],
            ['symbol' => 'USD/BWP', 'polygon' => 'C:USDBWP', 'name' => 'US Dollar vs Botswana Pula'],
            ['symbol' => 'USD/MUR', 'polygon' => 'C:USDMUR', 'name' => 'US Dollar vs Mauritian Rupee'],
            ['symbol' => 'USD/RWF', 'polygon' => 'C:USDRWF', 'name' => 'US Dollar vs Rwandan Franc'],
            ['symbol' => 'USD/XOF', 'polygon' => 'C:USDXOF', 'name' => 'US Dollar vs West African CFA Franc'],
            ['symbol' => 'USD/XAF', 'polygon' => 'C:USDXAF', 'name' => 'US Dollar vs Central African CFA Franc'],

            // ==================== USD PAIRS - EUROPE ====================
            ['symbol' => 'USD/DKK', 'polygon' => 'C:USDDKK', 'name' => 'US Dollar vs Danish Krone'],
            ['symbol' => 'USD/NOK', 'polygon' => 'C:USDNOK', 'name' => 'US Dollar vs Norwegian Krone'],
            ['symbol' => 'USD/SEK', 'polygon' => 'C:USDSEK', 'name' => 'US Dollar vs Swedish Krona'],
            ['symbol' => 'USD/ISK', 'polygon' => 'C:USDISK', 'name' => 'US Dollar vs Icelandic Krona'],
            ['symbol' => 'USD/PLN', 'polygon' => 'C:USDPLN', 'name' => 'US Dollar vs Polish Zloty'],
            ['symbol' => 'USD/HUF', 'polygon' => 'C:USDHUF', 'name' => 'US Dollar vs Hungarian Forint'],
            ['symbol' => 'USD/CZK', 'polygon' => 'C:USDCZK', 'name' => 'US Dollar vs Czech Koruna'],
            ['symbol' => 'USD/RON', 'polygon' => 'C:USDRON', 'name' => 'US Dollar vs Romanian Leu'],
            ['symbol' => 'USD/BGN', 'polygon' => 'C:USDBGN', 'name' => 'US Dollar vs Bulgarian Lev'],
            ['symbol' => 'USD/HRK', 'polygon' => 'C:USDHRK', 'name' => 'US Dollar vs Croatian Kuna'],
            ['symbol' => 'USD/RSD', 'polygon' => 'C:USDRSD', 'name' => 'US Dollar vs Serbian Dinar'],
            ['symbol' => 'USD/TRY', 'polygon' => 'C:USDTRY', 'name' => 'US Dollar vs Turkish Lira'],
            ['symbol' => 'USD/RUB', 'polygon' => 'C:USDRUB', 'name' => 'US Dollar vs Russian Ruble'],
            ['symbol' => 'USD/UAH', 'polygon' => 'C:USDUAH', 'name' => 'US Dollar vs Ukrainian Hryvnia'],
            ['symbol' => 'USD/KZT', 'polygon' => 'C:USDKZT', 'name' => 'US Dollar vs Kazakhstani Tenge'],
            ['symbol' => 'USD/BYN', 'polygon' => 'C:USDBYN', 'name' => 'US Dollar vs Belarusian Ruble'],

            // ==================== USD PAIRS - OCEANIA ====================
            ['symbol' => 'USD/FJD', 'polygon' => 'C:USDFJD', 'name' => 'US Dollar vs Fijian Dollar'],
            ['symbol' => 'USD/PGK', 'polygon' => 'C:USDPGK', 'name' => 'US Dollar vs Papua New Guinean Kina'],
            ['symbol' => 'USD/WST', 'polygon' => 'C:USDWST', 'name' => 'US Dollar vs Samoan Tala'],
            ['symbol' => 'USD/TOP', 'polygon' => 'C:USDTOP', 'name' => 'US Dollar vs Tongan Paʻanga'],
            ['symbol' => 'USD/VUV', 'polygon' => 'C:USDVUV', 'name' => 'US Dollar vs Vanuatu Vatu'],
        ];
    }
}
