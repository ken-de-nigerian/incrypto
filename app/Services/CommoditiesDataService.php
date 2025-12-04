<?php

namespace App\Services;

class CommoditiesDataService
{
    public static function getCommoditiesData(): array
    {
        return [
            // ==================== PRECIOUS METALS ====================
            ['symbol' => 'XAUUSD', 'polygon' => 'C:XAUUSD', 'name' => 'Gold'],
            ['symbol' => 'XAGUSD', 'polygon' => 'C:XAGUSD', 'name' => 'Silver'],
            ['symbol' => 'XPTUSD', 'polygon' => 'C:XPTUSD', 'name' => 'Platinum'],
            ['symbol' => 'XPDUSD', 'polygon' => 'C:XPDUSD', 'name' => 'Palladium'],

            // ==================== INDUSTRIAL METALS ====================
            ['symbol' => 'HG', 'polygon' => 'C:HG', 'name' => 'Copper'],
            ['symbol' => 'COPPER', 'polygon' => 'C:HG', 'name' => 'Copper'],
            ['symbol' => 'ALI', 'polygon' => 'C:ALI', 'name' => 'Aluminum'],
            ['symbol' => 'ALUMINUM', 'polygon' => 'C:ALI', 'name' => 'Aluminum'],
            ['symbol' => 'ZNC', 'polygon' => 'C:ZNC', 'name' => 'Zinc'],
            ['symbol' => 'ZINC', 'polygon' => 'C:ZNC', 'name' => 'Zinc'],
            ['symbol' => 'NI', 'polygon' => 'C:NI', 'name' => 'Nickel'],
            ['symbol' => 'NICKEL', 'polygon' => 'C:NI', 'name' => 'Nickel'],
            ['symbol' => 'PL', 'polygon' => 'C:PL', 'name' => 'Lead'],
            ['symbol' => 'LEAD', 'polygon' => 'C:PL', 'name' => 'Lead'],

            // ==================== ENERGY - CRUDE OIL ====================
            ['symbol' => 'CL', 'polygon' => 'C:CL', 'name' => 'WTI Crude Oil'],
            ['symbol' => 'USOIL', 'polygon' => 'C:CL', 'name' => 'US Oil (WTI)'],
            ['symbol' => 'BZ', 'polygon' => 'C:BZ', 'name' => 'Brent Crude Oil'],
            ['symbol' => 'UKOIL', 'polygon' => 'C:BZ', 'name' => 'UK Oil (Brent)'],

            // ==================== ENERGY - NATURAL GAS & REFINED PRODUCTS ====================
            ['symbol' => 'NG', 'polygon' => 'C:NG', 'name' => 'Natural Gas'],
            ['symbol' => 'NATGAS', 'polygon' => 'C:NG', 'name' => 'Natural Gas'],
            ['symbol' => 'HO', 'polygon' => 'C:HO', 'name' => 'Heating Oil'],
            ['symbol' => 'RB', 'polygon' => 'C:RB', 'name' => 'RBOB Gasoline'],

            // ==================== COMMODITY INDICES ====================
            ['symbol' => 'DXY', 'polygon' => 'I:DXY', 'name' => 'US Dollar Index'],

            // ==================== AGRICULTURE - GRAINS ====================
            ['symbol' => 'ZC', 'polygon' => 'C:ZC', 'name' => 'Corn'],
            ['symbol' => 'CORN', 'polygon' => 'C:ZC', 'name' => 'Corn'],
            ['symbol' => 'ZW', 'polygon' => 'C:ZW', 'name' => 'Wheat'],
            ['symbol' => 'WHEAT', 'polygon' => 'C:ZW', 'name' => 'Wheat'],
            ['symbol' => 'ZS', 'polygon' => 'C:ZS', 'name' => 'Soybeans'],
            ['symbol' => 'SOYBEAN', 'polygon' => 'C:ZS', 'name' => 'Soybeans'],
            ['symbol' => 'ZO', 'polygon' => 'C:ZO', 'name' => 'Oats'],
            ['symbol' => 'ZR', 'polygon' => 'C:ZR', 'name' => 'Rough Rice'],
            ['symbol' => 'ZM', 'polygon' => 'C:ZM', 'name' => 'Soybean Meal'],
            ['symbol' => 'ZL', 'polygon' => 'C:ZL', 'name' => 'Soybean Oil'],

            // ==================== AGRICULTURE - SOFT COMMODITIES ====================
            ['symbol' => 'KC', 'polygon' => 'C:KC', 'name' => 'Coffee'],
            ['symbol' => 'COFFEE', 'polygon' => 'C:KC', 'name' => 'Coffee'],
            ['symbol' => 'CC', 'polygon' => 'C:CC', 'name' => 'Cocoa'],
            ['symbol' => 'COCOA', 'polygon' => 'C:CC', 'name' => 'Cocoa'],
            ['symbol' => 'SB', 'polygon' => 'C:SB', 'name' => 'Sugar #11'],
            ['symbol' => 'SUGAR', 'polygon' => 'C:SB', 'name' => 'Sugar'],
            ['symbol' => 'CT', 'polygon' => 'C:CT', 'name' => 'Cotton'],
            ['symbol' => 'COTTON', 'polygon' => 'C:CT', 'name' => 'Cotton'],
            ['symbol' => 'OJ', 'polygon' => 'C:OJ', 'name' => 'Orange Juice'],

            // ==================== ALTERNATIVE COMMODITIES ====================
            ['symbol' => 'LBS', 'polygon' => 'C:LBS', 'name' => 'Lumber'],
            ['symbol' => 'LUMBER', 'polygon' => 'C:LBS', 'name' => 'Lumber'],
        ];
    }
}
