<?php

namespace App\Services;

class CurrencyToCountryCodeMapService
{
    /**
     * Maps currency codes to 2-letter ISO country codes for flag CDNs.
     */
    public static function getCurrencyToCountryCodeMap(): array
    {
        return [
            // ========== MAJOR GLOBAL CURRENCIES ==========
            'EUR' => 'eu',   // European Union (Austria, Belgium, Cyprus, Estonia, Finland, France, Germany, Greece, Ireland, Italy, Latvia, Lithuania, Luxembourg, Malta, Netherlands, Portugal, Slovakia, Slovenia, Spain)
            'USD' => 'us',   // United States (also: Ecuador, El Salvador, Zimbabwe, British Virgin Islands, Turks & Caicos, etc.)
            'GBP' => 'gb',   // United Kingdom
            'JPY' => 'jp',   // Japan
            'CHF' => 'ch',   // Switzerland (also Liechtenstein)
            'AUD' => 'au',   // Australia (also: Kiribati, Nauru, Tuvalu)
            'CAD' => 'ca',   // Canada
            'NZD' => 'nz',   // New Zealand (also: Cook Islands, Niue, Pitcairn Islands, Tokelau)

            // ========== WESTERN EUROPE ==========
            'DKK' => 'dk',   // Denmark (also Faroe Islands, Greenland)
            'NOK' => 'no',   // Norway (also Svalbard and Jan Mayen, Bouvet Island)
            'SEK' => 'se',   // Sweden
            'ISK' => 'is',   // Iceland

            // ========== CENTRAL & EASTERN EUROPE ==========
            'PLN' => 'pl',   // Poland
            'HUF' => 'hu',   // Hungary
            'CZK' => 'cz',   // Czech Republic (Czechia)
            'RON' => 'ro',   // Romania
            'BGN' => 'bg',   // Bulgaria
            'HRK' => 'hr',   // Croatia
            'RSD' => 'rs',   // Serbia
            'BAM' => 'ba',   // Bosnia and Herzegovina
            'MKD' => 'mk',   // North Macedonia
            'ALL' => 'al',   // Albania
            'TRY' => 'tr',   // Turkey

            // ========== EASTERN EUROPE & CIS ==========
            'RUB' => 'ru',   // Russia
            'UAH' => 'ua',   // Ukraine
            'BYN' => 'by',   // Belarus
            'MDL' => 'md',   // Moldova
            'GEL' => 'ge',   // Georgia
            'AMD' => 'am',   // Armenia
            'AZN' => 'az',   // Azerbaijan
            'KZT' => 'kz',   // Kazakhstan
            'UZS' => 'uz',   // Uzbekistan
            'KGS' => 'kg',   // Kyrgyzstan
            'TJS' => 'tj',   // Tajikistan
            'TMT' => 'tm',   // Turkmenistan

            // ========== EAST ASIA ==========
            'CNY' => 'cn',   // China
            'CNH' => 'cn',   // Chinese Yuan (Offshore)
            'HKD' => 'hk',   // Hong Kong
            'MOP' => 'mo',   // Macau
            'TWD' => 'tw',   // Taiwan
            'KRW' => 'kr',   // South Korea
            'KPW' => 'kp',   // North Korea
            'MNT' => 'mn',   // Mongolia

            // ========== SOUTHEAST ASIA ==========
            'SGD' => 'sg',   // Singapore
            'THB' => 'th',   // Thailand
            'MYR' => 'my',   // Malaysia
            'IDR' => 'id',   // Indonesia
            'PHP' => 'ph',   // Philippines
            'VND' => 'vn',   // Vietnam
            'BND' => 'bn',   // Brunei
            'KHR' => 'kh',   // Cambodia
            'LAK' => 'la',   // Laos
            'MMK' => 'mm',   // Myanmar
            'BDT' => 'bd',   // Bangladesh

            // ========== SOUTH ASIA ==========
            'INR' => 'in',   // India (also Bhutan)
            'PKR' => 'pk',   // Pakistan
            'LKR' => 'lk',   // Sri Lanka
            'NPR' => 'np',   // Nepal
            'BTN' => 'bt',   // Bhutan (also uses INR)
            'MVR' => 'mv',   // Maldives
            'AFN' => 'af',   // Afghanistan

            // ========== MIDDLE EAST ==========
            'AED' => 'ae',   // United Arab Emirates
            'SAR' => 'sa',   // Saudi Arabia
            'QAR' => 'qa',   // Qatar
            'KWD' => 'kw',   // Kuwait
            'BHD' => 'bh',   // Bahrain
            'OMR' => 'om',   // Oman
            'ILS' => 'il',   // Israel
            'JOD' => 'jo',   // Jordan
            'LBP' => 'lb',   // Lebanon
            'SYP' => 'sy',   // Syria
            'IQD' => 'iq',   // Iraq
            'IRR' => 'ir',   // Iran
            'YER' => 'ye',   // Yemen

            // ========== NORTH AFRICA ==========
            'EGP' => 'eg',   // Egypt
            'MAD' => 'ma',   // Morocco
            'DZD' => 'dz',   // Algeria
            'TND' => 'tn',   // Tunisia
            'LYD' => 'ly',   // Libya
            'SDG' => 'sd',   // Sudan
            'SSP' => 'ss',   // South Sudan

            // ========== WEST AFRICA ==========
            'XOF' => 'sn',   // West African CFA Franc (Benin, Burkina Faso, Côte d'Ivoire, Guinea-Bissau, Mali, Niger, Senegal, Togo)
            'NGN' => 'ng',   // Nigeria
            'GHS' => 'gh',   // Ghana
            'GMD' => 'gm',   // Gambia
            'GNF' => 'gn',   // Guinea
            'LRD' => 'lr',   // Liberia
            'SLL' => 'sl',   // Sierra Leone
            'CVE' => 'cv',   // Cape Verde

            // ========== CENTRAL AFRICA ==========
            'XAF' => 'cm',   // Central African CFA Franc (Cameroon, CAR, Chad, Rep. of Congo, Equatorial Guinea, Gabon)
            'CDF' => 'cd',   // DR Congo
            'AOA' => 'ao',   // Angola
            'STN' => 'st',   // São Tomé and Príncipe

            // ========== EAST AFRICA ==========
            'KES' => 'ke',   // Kenya
            'TZS' => 'tz',   // Tanzania
            'UGX' => 'ug',   // Uganda
            'RWF' => 'rw',   // Rwanda
            'BIF' => 'bi',   // Burundi
            'ETB' => 'et',   // Ethiopia
            'SOS' => 'so',   // Somalia
            'DJF' => 'dj',   // Djibouti
            'ERN' => 'er',   // Eritrea

            // ========== SOUTHERN AFRICA ==========
            'ZAR' => 'za',   // South Africa (also: Eswatini, Lesotho, Namibia)
            'MZN' => 'mz',   // Mozambique
            'ZMW' => 'zm',   // Zambia
            'ZWL' => 'zw',   // Zimbabwe (also uses USD)
            'BWP' => 'bw',   // Botswana
            'NAD' => 'na',   // Namibia
            'SZL' => 'sz',   // Eswatini (Swaziland)
            'LSL' => 'ls',   // Lesotho
            'MWK' => 'mw',   // Malawi

            // ========== INDIAN OCEAN ==========
            'MUR' => 'mu',   // Mauritius
            'SCR' => 'sc',   // Seychelles
            'MGA' => 'mg',   // Madagascar
            'KMF' => 'km',   // Comoros
            'MRU' => 'mr',   // Mauritania

            // ========== NORTH AMERICA ==========
            'MXN' => 'mx',   // Mexico
            'BMD' => 'bm',   // Bermuda
            'KYD' => 'ky',   // Cayman Islands

            // ========== CENTRAL AMERICA & CARIBBEAN ==========
            'GTQ' => 'gt',   // Guatemala
            'BZD' => 'bz',   // Belize
            'HNL' => 'hn',   // Honduras
            'NIO' => 'ni',   // Nicaragua
            'CRC' => 'cr',   // Costa Rica
            'PAB' => 'pa',   // Panama (also uses USD)
            'CUP' => 'cu',   // Cuba
            'DOP' => 'do',   // Dominican Republic
            'HTG' => 'ht',   // Haiti
            'JMD' => 'jm',   // Jamaica
            'TTD' => 'tt',   // Trinidad and Tobago
            'BBD' => 'bb',   // Barbados
            'BSD' => 'bs',   // Bahamas
            'XCD' => 'ag',   // East Caribbean Dollar (Antigua & Barbuda, Dominica, Grenada, St. Kitts & Nevis, St. Lucia, St. Vincent & the Grenadines, Anguilla, Montserrat)
            'AWG' => 'aw',   // Aruba
            'ANG' => 'cw',   // Netherlands Antillean Guilder (Curaçao, Sint Maarten)

            // ========== SOUTH AMERICA ==========
            'BRL' => 'br',   // Brazil
            'ARS' => 'ar',   // Argentina
            'CLP' => 'cl',   // Chile
            'COP' => 'co',   // Colombia
            'PEN' => 'pe',   // Peru
            'UYU' => 'uy',   // Uruguay
            'PYG' => 'py',   // Paraguay
            'BOB' => 'bo',   // Bolivia
            'VES' => 've',   // Venezuela
            'SRD' => 'sr',   // Suriname
            'GYD' => 'gy',   // Guyana
            'FKP' => 'fk',   // Falkland Islands

            // ========== OCEANIA ==========
            'FJD' => 'fj',   // Fiji
            'PGK' => 'pg',   // Papua New Guinea
            'SBD' => 'sb',   // Solomon Islands
            'VUV' => 'vu',   // Vanuatu
            'TOP' => 'to',   // Tonga
            'WST' => 'ws',   // Samoa
            'XPF' => 'pf',   // CFP Franc (French Polynesia, New Caledonia, Wallis and Futuna)

            // ========== SMALL TERRITORIES ==========
            'GIP' => 'gi',   // Gibraltar
            'SHP' => 'sh',   // Saint Helena
        ];
    }
}
