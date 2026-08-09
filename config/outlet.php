<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kategori Kos Resit (8) — statik, bukan jadual
    |--------------------------------------------------------------------------
    | Padan tepat dengan array CATS dalam prototaip.
    | 'cogs' => true bermakna kos ini dikira sebagai COGS (bahan, packaging).
    */
    'categories' => [
        'bahan' => ['nm' => 'Bahan Mentah', 'ic' => '🥩', 'cogs' => true],
        'hardware' => ['nm' => 'Hardware', 'ic' => '🔧', 'cogs' => false],
        'utiliti' => ['nm' => 'Utiliti', 'ic' => '💡', 'cogs' => false],
        'packaging' => ['nm' => 'Packaging', 'ic' => '📦', 'cogs' => true],
        'marketing' => ['nm' => 'Marketing', 'ic' => '📣', 'cogs' => false],
        'maintenance' => ['nm' => 'Maintenance', 'ic' => '🛠️', 'cogs' => false],
        'gas' => ['nm' => 'Gas / Bahan Api', 'ic' => '🔥', 'cogs' => false],
        'lain' => ['nm' => 'Lain-lain', 'ic' => '📁', 'cogs' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enum kumpulan
    |--------------------------------------------------------------------------
    */
    'staff_types' => ['Sepenuh Masa', 'Separuh Masa'],

    'equipment_groups' => ['Dapur', 'Ruang Makan', 'Operasi', 'Lain-lain'],

    'fixed_cost_groups' => ['Gaji', 'Sewa', 'Utiliti', 'Langganan', 'Insurans', 'Ansuran', 'Lain-lain'],

    // Kumpulan yang boleh dipilih dalam modul Kos Tetap (tanpa "Gaji" — gaji diurus di modul Staff)
    'fixed_cost_groups_selectable' => ['Sewa', 'Utiliti', 'Langganan', 'Insurans', 'Ansuran', 'Lain-lain'],

    /*
    |--------------------------------------------------------------------------
    | Ambang badge food cost (%)
    |--------------------------------------------------------------------------
    | <= ok : Sihat/Baik (hijau) · <= warn : Tinggi (kuning) · > warn : Bahaya/Rugi (merah)
    */
    'food_cost' => [
        'ok' => 35,
        'warn' => 45,
    ],
];
