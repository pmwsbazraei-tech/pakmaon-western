<?php

namespace App\Support;

use App\Models\Outlet;
use App\Models\User;

/**
 * Data DEFAULT identik dengan objek `DEFAULT` prototaip `pakmaon-outlet.html`.
 * Digunakan oleh OutletSeeder dan semasa pendaftaran user baharu.
 */
class DefaultOutletData
{
    public const SETTINGS = [
        'name' => 'Pak Maon Western',
        'target_sales' => 30000,
        'target_margin' => 20,
        'target_roi_months' => 12,
        'epf_pct' => 13,
        'socso_pct' => 1.75,
    ];

    public const STAFF = [
        ['name' => 'Head Cook', 'position' => 'Head Cook', 'salary' => 3000, 'type' => 'Sepenuh Masa', 'epf_socso' => true],
        ['name' => 'Cook', 'position' => 'Cook', 'salary' => 2200, 'type' => 'Sepenuh Masa', 'epf_socso' => true],
        ['name' => 'Helper', 'position' => 'Helper', 'salary' => 1700, 'type' => 'Separuh Masa', 'epf_socso' => false],
        ['name' => 'Cashier', 'position' => 'Cashier', 'salary' => 1800, 'type' => 'Sepenuh Masa', 'epf_socso' => true],
    ];

    public const EQUIPMENT = [
        ['name' => 'Gas grill / hotplate', 'qty' => 1, 'unit_price' => 4500, 'status' => 'perlu', 'group' => 'Dapur'],
        ['name' => 'Deep fryer', 'qty' => 2, 'unit_price' => 1200, 'status' => 'perlu', 'group' => 'Dapur'],
        ['name' => 'Chiller / peti sejuk', 'qty' => 1, 'unit_price' => 3500, 'status' => 'perlu', 'group' => 'Dapur'],
        ['name' => 'Freezer', 'qty' => 1, 'unit_price' => 2000, 'status' => 'perlu', 'group' => 'Dapur'],
        ['name' => 'Meja penyediaan stainless steel', 'qty' => 2, 'unit_price' => 800, 'status' => 'perlu', 'group' => 'Dapur'],
        ['name' => 'Exhaust hood', 'qty' => 1, 'unit_price' => 2500, 'status' => 'perlu', 'group' => 'Dapur'],
        ['name' => 'Meja pelanggan', 'qty' => 8, 'unit_price' => 180, 'status' => 'perlu', 'group' => 'Ruang Makan'],
        ['name' => 'Kerusi pelanggan', 'qty' => 32, 'unit_price' => 45, 'status' => 'perlu', 'group' => 'Ruang Makan'],
        ['name' => 'Sistem POS + printer', 'qty' => 1, 'unit_price' => 2500, 'status' => 'perlu', 'group' => 'Operasi'],
        ['name' => 'Signboard', 'qty' => 1, 'unit_price' => 3500, 'status' => 'perlu', 'group' => 'Operasi'],
        ['name' => 'Peralatan masak (kuali, senduk, pisau)', 'qty' => 1, 'unit_price' => 1500, 'status' => 'perlu', 'group' => 'Dapur'],
        ['name' => 'Pinggan mangkuk & cutlery', 'qty' => 1, 'unit_price' => 1200, 'status' => 'perlu', 'group' => 'Ruang Makan'],
    ];

    public const CAPEX = [
        ['name' => 'Renovasi & kelengkapan kedai', 'amount' => 25000],
        ['name' => 'Peralatan dapur (grill, fryer, chiller)', 'amount' => 18000],
        ['name' => 'Meja, kerusi & perabot', 'amount' => 6000],
        ['name' => 'Signboard & branding', 'amount' => 3500],
        ['name' => 'Deposit sewa (2 bulan)', 'amount' => 7000],
        ['name' => 'Stok awal bahan mentah', 'amount' => 5000],
        ['name' => 'Lesen, permit & POS sistem', 'amount' => 2500],
    ];

    public const FIXED = [
        ['name' => 'Sewa kedai', 'amount' => 3500, 'group' => 'Sewa'],
        ['name' => 'WiFi / Internet', 'amount' => 150, 'group' => 'Utiliti'],
        ['name' => 'Langganan POS', 'amount' => 120, 'group' => 'Langganan'],
    ];

    public const MENU = [
        ['name' => 'Chicken Chop', 'price' => 15.90, 'ing' => [
            ['name' => 'Isi ayam', 'cost' => 3.50],
            ['name' => 'Kentang & sayur', 'cost' => 1.20],
            ['name' => 'Sos black pepper', 'cost' => 0.80],
            ['name' => 'Minyak & rempah', 'cost' => 0.50],
        ]],
        ['name' => 'Beef Burger Special', 'price' => 12.90, 'ing' => [
            ['name' => 'Patty daging', 'cost' => 2.80],
            ['name' => 'Roti bun', 'cost' => 0.70],
            ['name' => 'Keju & sayur', 'cost' => 1.10],
            ['name' => 'Sos', 'cost' => 0.40],
        ]],
        ['name' => 'Grilled Fish & Chips', 'price' => 16.90, 'ing' => [
            ['name' => 'Ikan dory', 'cost' => 3.20],
            ['name' => 'Kentang', 'cost' => 1.00],
            ['name' => 'Tartar sauce', 'cost' => 0.60],
        ]],
        ['name' => 'Spaghetti Bolognese', 'price' => 11.90, 'ing' => [
            ['name' => 'Pasta', 'cost' => 0.90],
            ['name' => 'Daging kisar', 'cost' => 2.20],
            ['name' => 'Sos tomato', 'cost' => 0.80],
        ]],
    ];

    /**
     * Cipta satu outlet lengkap dengan data DEFAULT untuk seorang user.
     */
    public static function provision(User $user): Outlet
    {
        $outlet = $user->outlets()->create(self::SETTINGS);

        $outlet->staff()->createMany(self::STAFF);
        $outlet->equipment()->createMany(self::EQUIPMENT);
        $outlet->capexItems()->createMany(self::CAPEX);
        $outlet->fixedCosts()->createMany(self::FIXED);

        foreach (self::MENU as $menu) {
            $created = $outlet->menus()->create([
                'name' => $menu['name'],
                'price' => $menu['price'],
            ]);
            $created->ingredients()->createMany($menu['ing']);
        }

        // receipts / sales / cash sengaja dibiarkan kosong (macam prototaip)

        return $outlet;
    }
}
