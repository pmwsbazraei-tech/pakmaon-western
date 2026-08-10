<?php

namespace App\Support;

use App\Models\Outlet;
use Illuminate\Support\Str;

/**
 * Penyelesai kategori kos: gabung 8 kategori built-in (config/outlet.php)
 * dengan kategori custom setiap outlet (jadual outlet_categories).
 *
 * Setiap kategori dipulangkan sebagai [key => ['nm'=>..., 'ic'=>..., 'cogs'=>bool]].
 */
class Categories
{
    /** @return array<string,array{nm:string,ic:string,cogs:bool}> */
    public static function builtins(): array
    {
        return config('outlet.categories');
    }

    /** Kategori custom outlet, dikunci dengan slug kekal. */
    public static function custom(Outlet $outlet): array
    {
        $out = [];
        foreach ($outlet->categories()->orderBy('id')->get() as $c) {
            $out[$c->key] = ['nm' => $c->name, 'ic' => $c->icon ?: '📁', 'cogs' => (bool) $c->is_cogs];
        }

        return $out;
    }

    /** Semua kategori (built-in + custom) — untuk pengiraan & paparan. */
    public static function all(Outlet $outlet): array
    {
        return self::builtins() + self::custom($outlet);
    }

    /** Kategori yang boleh dipilih sebagai butang (tanpa 'lain' — digantikan butang Tambah). */
    public static function selectable(Outlet $outlet): array
    {
        $builtins = self::builtins();
        unset($builtins['lain']);

        return $builtins + self::custom($outlet);
    }

    public static function keys(Outlet $outlet): array
    {
        return array_keys(self::all($outlet));
    }

    public static function find(Outlet $outlet, string $key): array
    {
        return self::all($outlet)[$key] ?? ['nm' => $key, 'ic' => '📁', 'cogs' => false];
    }

    /** Jana slug unik untuk kategori baharu (elak langgar built-in & custom sedia ada). */
    public static function uniqueKey(Outlet $outlet, string $name): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $reserved = array_merge(array_keys(self::builtins()), $outlet->categories()->pluck('key')->all());

        $key = $base;
        $i = 1;
        while (in_array($key, $reserved, true)) {
            $key = $base.'-'.(++$i);
        }

        return $key;
    }
}
