<?php

namespace App\Services;

class StocksDataService
{
    public static function getStocksData(): array
    {
        return [
            // ==================== MAJOR STOCKS ====================
            ['symbol' => 'NVDA', 'polygon' => 'NVDA', 'name' => 'NVIDIA Corporation'],
            ['symbol' => 'AAPL', 'polygon' => 'AAPL', 'name' => 'Apple Inc.'],
            ['symbol' => 'MSFT', 'polygon' => 'MSFT', 'name' => 'Microsoft Corporation'],
            ['symbol' => 'GOOGL', 'polygon' => 'GOOGL', 'name' => 'Alphabet Inc.'],
            ['symbol' => 'AMZN', 'polygon' => 'AMZN', 'name' => 'Amazon.com, Inc.'],
            ['symbol' => 'AVGO', 'polygon' => 'AVGO', 'name' => 'Broadcom Inc.'],

            // ==================== TECH STOCKS ====================
            ['symbol' => 'META', 'polygon' => 'META', 'name' => 'Meta Platforms, Inc.'],
            ['symbol' => 'TSLA', 'polygon' => 'TSLA', 'name' => 'Tesla, Inc.'],
            ['symbol' => 'BRK.B', 'polygon' => 'BRK.B', 'name' => 'Berkshire Hathaway Inc.'],
            ['symbol' => 'LLY', 'polygon' => 'LLY', 'name' => 'Eli Lilly and Company'],
            ['symbol' => 'JPM', 'polygon' => 'JPM', 'name' => 'JPMorgan Chase & Co.'],
            ['symbol' => 'WMT', 'polygon' => 'WMT', 'name' => 'Walmart Inc.'],
            ['symbol' => 'ORCL', 'polygon' => 'ORCL', 'name' => 'Oracle Corporation'],
            ['symbol' => 'V', 'polygon' => 'V', 'name' => 'Visa Inc.'],
            ['symbol' => 'XOM', 'polygon' => 'XOM', 'name' => 'Exxon Mobil Corporation'],
            ['symbol' => 'MA', 'polygon' => 'MA', 'name' => 'Mastercard Incorporated'],
            ['symbol' => 'NFLX', 'polygon' => 'NFLX', 'name' => 'Netflix, Inc.'],
            ['symbol' => 'JNJ', 'polygon' => 'JNJ', 'name' => 'Johnson & Johnson'],
            ['symbol' => 'PLTR', 'polygon' => 'PLTR', 'name' => 'Palantir Technologies Inc.'],
            ['symbol' => 'ABBV', 'polygon' => 'ABBV', 'name' => 'AbbVie Inc.'],
            ['symbol' => 'COST', 'polygon' => 'COST', 'name' => 'Costco Wholesale Corporation'],
            ['symbol' => 'AMD', 'polygon' => 'AMD', 'name' => 'Advanced Micro Devices, Inc.'],
            ['symbol' => 'BAC', 'polygon' => 'BAC', 'name' => 'Bank of America Corporation'],
            ['symbol' => 'HD', 'polygon' => 'HD', 'name' => 'The Home Depot, Inc.'],
            ['symbol' => 'PG', 'polygon' => 'PG', 'name' => 'The Procter & Gamble Company'],
            ['symbol' => 'GE', 'polygon' => 'GE', 'name' => 'General Electric Company'],

            // ==================== FINANCIAL STOCKS ====================
            ['symbol' => 'CVX', 'polygon' => 'CVX', 'name' => 'Chevron Corporation'],
            ['symbol' => 'CSCO', 'polygon' => 'CSCO', 'name' => 'Cisco Systems, Inc.'],
            ['symbol' => 'KO', 'polygon' => 'KO', 'name' => 'The Coca-Cola Company'],
            ['symbol' => 'UNH', 'polygon' => 'UNH', 'name' => 'UnitedHealth Group Incorporated'],
            ['symbol' => 'IBM', 'polygon' => 'IBM', 'name' => 'International Business Machines Corporation'],
            ['symbol' => 'MU', 'polygon' => 'MU', 'name' => 'Micron Technology, Inc.'],
            ['symbol' => 'WFC', 'polygon' => 'WFC', 'name' => 'Wells Fargo & Company'],
            ['symbol' => 'MS', 'polygon' => 'MS', 'name' => 'Morgan Stanley'],
            ['symbol' => 'CAT', 'polygon' => 'CAT', 'name' => 'Caterpillar Inc.'],
            ['symbol' => 'GS', 'polygon' => 'GS', 'name' => 'The Goldman Sachs Group, Inc.'],
            ['symbol' => 'AXP', 'polygon' => 'AXP', 'name' => 'American Express Company'],

            // ==================== HEALTH CARE STOCKS ====================
            ['symbol' => 'TMUS', 'polygon' => 'TMUS', 'name' => 'T-Mobile US, Inc.'],
            ['symbol' => 'PM', 'polygon' => 'PM', 'name' => 'Philip Morris International Inc.'],
            ['symbol' => 'RTX', 'polygon' => 'RTX', 'name' => 'RTX Corporation'],
            ['symbol' => 'MRK', 'polygon' => 'MRK', 'name' => 'Merck & Co., Inc.'],
            ['symbol' => 'CRM', 'polygon' => 'CRM', 'name' => 'Salesforce, Inc.'],
            ['symbol' => 'ABT', 'polygon' => 'ABT', 'name' => 'Abbott Laboratories'],
            ['symbol' => 'TMO', 'polygon' => 'TMO', 'name' => 'Thermo Fisher Scientific Inc.'],
            ['symbol' => 'MCD', 'polygon' => 'MCD', 'name' => 'McDonald\'s Corporation'],
            ['symbol' => 'PEP', 'polygon' => 'PEP', 'name' => 'PepsiCo, Inc.'],
            ['symbol' => 'LIN', 'polygon' => 'LIN', 'name' => 'Linde plc'],
            ['symbol' => 'ISRG', 'polygon' => 'ISRG', 'name' => 'Intuitive Surgical, Inc.'],

            // ==================== CONSUMER STOCKS ====================
            ['symbol' => 'UBER', 'polygon' => 'UBER', 'name' => 'Uber Technologies, Inc.'],
            ['symbol' => 'APP', 'polygon' => 'APP', 'name' => 'AppLovin Corporation'],
            ['symbol' => 'DIS', 'polygon' => 'DIS', 'name' => 'The Walt Disney Company'],
            ['symbol' => 'LRCX', 'polygon' => 'LRCX', 'name' => 'Lam Research Corporation'],
            ['symbol' => 'QCOM', 'polygon' => 'QCOM', 'name' => 'QUALCOMM Incorporated'],
            ['symbol' => 'INTU', 'polygon' => 'INTU', 'name' => 'Intuit Inc.'],
            ['symbol' => 'AMGN', 'polygon' => 'AMGN', 'name' => 'Amgen Inc.'],

            // ==================== MEDIA STOCKS ====================
            ['symbol' => 'T', 'polygon' => 'T', 'name' => 'AT&T Inc.'],
            ['symbol' => 'C', 'polygon' => 'C', 'name' => 'Citigroup Inc.'],
            ['symbol' => 'AMAT', 'polygon' => 'AMAT', 'name' => 'Applied Materials, Inc.'],

            // ==================== ENERGY STOCKS ====================
            ['symbol' => 'NOW', 'polygon' => 'NOW', 'name' => 'ServiceNow, Inc.'],
            ['symbol' => 'NEE', 'polygon' => 'NEE', 'name' => 'NextEra Energy, Inc.'],

            // ==================== UTILITY STOCKS ====================
            ['symbol' => 'BX', 'polygon' => 'BX', 'name' => 'Blackstone Inc.'],
            ['symbol' => 'BLK', 'polygon' => 'BLK', 'name' => 'BlackRock, Inc.'],
            ['symbol' => 'VZ', 'polygon' => 'VZ', 'name' => 'Verizon Communications Inc.'],
            ['symbol' => 'INTC', 'polygon' => 'INTC', 'name' => 'Intel Corporation'],
            ['symbol' => 'SCHW', 'polygon' => 'SCHW', 'name' => 'The Charles Schwab Corporation'],

            // ==================== MATERIALS STOCKS ====================
            ['symbol' => 'ANET', 'polygon' => 'ANET', 'name' => 'Arista Networks Inc'],
            ['symbol' => 'APH', 'polygon' => 'APH', 'name' => 'Amphenol Corporation'],
            ['symbol' => 'BKNG', 'polygon' => 'BKNG', 'name' => 'Booking Holdings Inc.'],
            ['symbol' => 'TJX', 'polygon' => 'TJX', 'name' => 'The TJX Companies, Inc.'],
            ['symbol' => 'GEV', 'polygon' => 'GEV', 'name' => 'GE Vernova Inc.'],
            ['symbol' => 'DHR', 'polygon' => 'DHR', 'name' => 'Danaher Corporation'],
            ['symbol' => 'GILD', 'polygon' => 'GILD', 'name' => 'Gilead Sciences, Inc.'],
            ['symbol' => 'BSX', 'polygon' => 'BSX', 'name' => 'Boston Scientific Corporation'],
            ['symbol' => 'ACN', 'polygon' => 'ACN', 'name' => 'Accenture plc'],
            ['symbol' => 'SPGI', 'polygon' => 'SPGI', 'name' => 'S&P Global Inc.'],
            ['symbol' => 'KLAC', 'polygon' => 'KLAC', 'name' => 'KLA Corporation'],
            ['symbol' => 'BA', 'polygon' => 'BA', 'name' => 'The Boeing Company'],
            ['symbol' => 'TXN', 'polygon' => 'TXN', 'name' => 'Texas Instruments Incorporated'],
            ['symbol' => 'PFE', 'polygon' => 'PFE', 'name' => 'Pfizer Inc.'],
            ['symbol' => 'SYK', 'polygon' => 'SYK', 'name' => 'Stryker Corporation'],
            ['symbol' => 'PANW', 'polygon' => 'PANW', 'name' => 'Palo Alto Networks, Inc.'],
            ['symbol' => 'ADBE', 'polygon' => 'ADBE', 'name' => 'Adobe Inc.'],
            ['symbol' => 'ETN', 'polygon' => 'ETN', 'name' => 'Eaton Corporation plc'],
            ['symbol' => 'CRWD', 'polygon' => 'CRWD', 'name' => 'CrowdStrike Holdings, Inc.'],
            ['symbol' => 'COF', 'polygon' => 'COF', 'name' => 'Capital One Financial Corporation'],

            // ==================== CONSUMER STAPLES STOCKS ====================
            ['symbol' => 'WELL', 'polygon' => 'WELL', 'name' => 'Welltower Inc.'],
            ['symbol' => 'UNP', 'polygon' => 'UNP', 'name' => 'Union Pacific Corporation'],
            ['symbol' => 'PGR', 'polygon' => 'PGR', 'name' => 'The Progressive Corporation'],
            ['symbol' => 'DE', 'polygon' => 'DE', 'name' => 'Deere & Company'],
            ['symbol' => 'LOW', 'polygon' => 'LOW', 'name' => 'Lowe\'s Companies, Inc.'],
            ['symbol' => 'HON', 'polygon' => 'HON', 'name' => 'Honeywell International Inc.'],
            ['symbol' => 'MDT', 'polygon' => 'MDT', 'name' => 'Medtronic plc'],
            ['symbol' => 'PLD', 'polygon' => 'PLD', 'name' => 'Prologis, Inc.'],
            ['symbol' => 'CB', 'polygon' => 'CB', 'name' => 'Chubb Limited'],
            ['symbol' => 'ADI', 'polygon' => 'ADI', 'name' => 'Analog Devices, Inc.'],
            ['symbol' => 'COP', 'polygon' => 'COP', 'name' => 'ConocoPhillips'],
            ['symbol' => 'HOOD', 'polygon' => 'HOOD', 'name' => 'Robinhood Markets, Inc.'],
            ['symbol' => 'VRTX', 'polygon' => 'VRTX', 'name' => 'Vertex Pharmaceuticals Incorporated'],
            ['symbol' => 'HCA', 'polygon' => 'HCA', 'name' => 'HCA Healthcare, Inc.'],
            ['symbol' => 'LMT', 'polygon' => 'LMT', 'name' => 'Lockheed Martin Corporation'],
            ['symbol' => 'CEG', 'polygon' => 'CEG', 'name' => 'Constellation Energy Corporation'],
            ['symbol' => 'KKR', 'polygon' => 'KKR', 'name' => 'KKR & Co. Inc.'],
            ['symbol' => 'PH', 'polygon' => 'PH', 'name' => 'Parker-Hannifin Corporation'],
            ['symbol' => 'MCK', 'polygon' => 'MCK', 'name' => 'McKesson Corporation'],
            ['symbol' => 'CME', 'polygon' => 'CME', 'name' => 'CME Group Inc.'],
            ['symbol' => 'ADP', 'polygon' => 'ADP', 'name' => 'Automatic Data Processing, Inc.'],

            // ==================== REAL ESTATE STOCKS ====================
            ['symbol' => 'CMCSA', 'polygon' => 'CMCSA', 'name' => 'Comcast Corporation'],
            ['symbol' => 'SO', 'polygon' => 'SO', 'name' => 'The Southern Company'],
            ['symbol' => 'CVS', 'polygon' => 'CVS', 'name' => 'CVS Health Corporation'],
            ['symbol' => 'MO', 'polygon' => 'MO', 'name' => 'Altria Group, Inc.'],
            ['symbol' => 'SBUX', 'polygon' => 'SBUX', 'name' => 'Starbucks Corporation'],
            ['symbol' => 'NEM', 'polygon' => 'NEM', 'name' => 'Newmont Corporation'],
            ['symbol' => 'DUK', 'polygon' => 'DUK', 'name' => 'Duke Energy Corporation'],
            ['symbol' => 'BMY', 'polygon' => 'BMY', 'name' => 'Bristol-Myers Squibb Company'],
            ['symbol' => 'NKE', 'polygon' => 'NKE', 'name' => 'NIKE, Inc.'],
            ['symbol' => 'TT', 'polygon' => 'TT', 'name' => 'Trane Technologies plc'],
            ['symbol' => 'GD', 'polygon' => 'GD', 'name' => 'General Dynamics Corporation'],
            ['symbol' => 'DELL', 'polygon' => 'DELL', 'name' => 'Dell Technologies Inc.'],
            ['symbol' => 'DASH', 'polygon' => 'DASH', 'name' => 'DoorDash, Inc.'],

            // ==================== INDUSTRIAL STOCKS ====================
            ['symbol' => 'MMC', 'polygon' => 'MMC', 'name' => 'Marsh & McLennan Companies, Inc.'],
            ['symbol' => 'MMM', 'polygon' => 'MMM', 'name' => '3M Company'],
            ['symbol' => 'ICE', 'polygon' => 'ICE', 'name' => 'Intercontinental Exchange, Inc.'],
            ['symbol' => 'CDNS', 'polygon' => 'CDNS', 'name' => 'Cadence Design Systems, Inc.'],
            ['symbol' => 'MCO', 'polygon' => 'MCO', 'name' => 'Moody\'s Corporation'],
            ['symbol' => 'AMT', 'polygon' => 'AMT', 'name' => 'American Tower Corporation'],
            ['symbol' => 'WM', 'polygon' => 'WM', 'name' => 'Waste Management, Inc.'],
            ['symbol' => 'ORLY', 'polygon' => 'ORLY', 'name' => 'O\'Reilly Automotive, Inc.'],
            ['symbol' => 'SHW', 'polygon' => 'SHW', 'name' => 'The Sherwin-Williams Company'],
            ['symbol' => 'HWM', 'polygon' => 'HWM', 'name' => 'Howmet Aerospace Inc.'],
            ['symbol' => 'UPS', 'polygon' => 'UPS', 'name' => 'United Parcel Service, Inc.'],
            ['symbol' => 'NOC', 'polygon' => 'NOC', 'name' => 'Northrop Grumman Corporation'],
            ['symbol' => 'JCI', 'polygon' => 'JCI', 'name' => 'Johnson Controls International plc'],
            ['symbol' => 'BK', 'polygon' => 'BK', 'name' => 'The Bank of New York Mellon Corporation'],
            ['symbol' => 'COIN', 'polygon' => 'COIN', 'name' => 'Coinbase Global, Inc.'],
            ['symbol' => 'MAR', 'polygon' => 'MAR', 'name' => 'Marriott International, Inc.'],
            ['symbol' => 'EQIX', 'polygon' => 'EQIX', 'name' => 'Equinix, Inc.'],
            ['symbol' => 'APO', 'polygon' => 'APO', 'name' => 'Apollo Global Management, Inc.'],
            ['symbol' => 'TDG', 'polygon' => 'TDG', 'name' => 'TransDigm Group Incorporated'],
            ['symbol' => 'CTAS', 'polygon' => 'CTAS', 'name' => 'Cintas Corporation'],

            // ==================== DEFENSIVE STOCKS ====================
            ['symbol' => 'AON', 'polygon' => 'AON', 'name' => 'Aon plc'],
            ['symbol' => 'WMB', 'polygon' => 'WMB', 'name' => 'The Williams Companies, Inc.'],
            ['symbol' => 'ABNB', 'polygon' => 'ABNB', 'name' => 'Airbnb, Inc.'],
            ['symbol' => 'MDLZ', 'polygon' => 'MDLZ', 'name' => 'Mondelez International, Inc.'],
            ['symbol' => 'ECL', 'polygon' => 'ECL', 'name' => 'Ecolab Inc.'],
            ['symbol' => 'USB', 'polygon' => 'USB', 'name' => 'U.S. Bancorp'],
            ['symbol' => 'ELV', 'polygon' => 'ELV', 'name' => 'Elevance Health, Inc.'],
            ['symbol' => 'SNPS', 'polygon' => 'SNPS', 'name' => 'Synopsys, Inc.'],
            ['symbol' => 'CI', 'polygon' => 'CI', 'name' => 'The Cigna Group'],
            ['symbol' => 'PNC', 'polygon' => 'PNC', 'name' => 'The PNC Financial Services Group, Inc.'],
            ['symbol' => 'EMR', 'polygon' => 'EMR', 'name' => 'Emerson Electric Co.'],
            ['symbol' => 'REGN', 'polygon' => 'REGN', 'name' => 'Regeneron Pharmaceuticals, Inc.'],
            ['symbol' => 'GLW', 'polygon' => 'GLW', 'name' => 'Corning Incorporated'],
            ['symbol' => 'COR', 'polygon' => 'COR', 'name' => 'Cencora, Inc.'],
            ['symbol' => 'ITW', 'polygon' => 'ITW', 'name' => 'Illinois Tool Works Inc.'],
            ['symbol' => 'TEL', 'polygon' => 'TEL', 'name' => 'TE Connectivity plc'],

            // ==================== COMMUNICATION STOCKS ====================
            ['symbol' => 'MNST', 'polygon' => 'MNST', 'name' => 'Monster Beverage Corporation'],
            ['symbol' => 'RCL', 'polygon' => 'RCL', 'name' => 'Royal Caribbean Cruises Ltd.'],
            ['symbol' => 'SPG', 'polygon' => 'SPG', 'name' => 'Simon Property Group, Inc.'],
            ['symbol' => 'GM', 'polygon' => 'GM', 'name' => 'General Motors Company'],
            ['symbol' => 'AJG', 'polygon' => 'AJG', 'name' => 'Arthur J. Gallagher & Co.'],
        ];
    }
}
