<?php

namespace App\Support;

use App\Models\Outlet;

/**
 * Serialisasi/deserialisasi data outlet ke bentuk JSON prototaip `pakmaon-outlet.html`
 * supaya fail export/import serasi dua hala dengan prototaip asal.
 */
class OutletJson
{
    /** @return array<string,mixed> */
    public static function export(Outlet $outlet): array
    {
        $outlet->load(['staff', 'equipment', 'capexItems', 'fixedCosts', 'receipts.items', 'sales', 'menus.ingredients', 'cashRecords', 'categories']);

        return [
            'categories' => $outlet->categories->map(fn ($c) => [
                'key' => $c->key, 'name' => $c->name, 'icon' => $c->icon, 'is_cogs' => (bool) $c->is_cogs,
            ])->all(),
            'settings' => [
                'outlet' => $outlet->name,
                'targetSales' => (float) $outlet->target_sales,
                'targetMargin' => (float) $outlet->target_margin,
                'targetRoiMonths' => (int) $outlet->target_roi_months,
                'epf' => (float) $outlet->epf_pct,
                'socso' => (float) $outlet->socso_pct,
            ],
            'staff' => $outlet->staff->map(fn ($s) => [
                'nm' => $s->name, 'pos' => $s->position, 'gaji' => (float) $s->salary,
                'jenis' => $s->type, 'kwsp' => (bool) $s->epf_socso,
            ])->all(),
            'equipment' => $outlet->equipment->map(fn ($e) => [
                'nm' => $e->name, 'qty' => (int) $e->qty, 'price' => (float) $e->unit_price,
                'status' => $e->status, 'grp' => $e->group,
            ])->all(),
            'capex' => $outlet->capexItems->map(fn ($c) => [
                'nm' => $c->name, 'amt' => (float) $c->amount,
            ])->all(),
            'fixed' => $outlet->fixedCosts->map(fn ($f) => [
                'nm' => $f->name, 'amt' => (float) $f->amount, 'grp' => $f->group,
            ])->all(),
            'receipts' => $outlet->receipts->map(fn ($r) => [
                'date' => $r->date->format('Y-m-d'), 'inv' => $r->invoice_no, 'cat' => $r->category, 'amt' => (float) $r->amount,
                'items' => $r->items->map(fn ($i) => ['nm' => $i->name, 'amt' => (float) $i->amount])->all(),
                'note' => $r->note,
            ])->all(),
            'sales' => $outlet->sales->map(fn ($s) => [
                'date' => $s->date->format('Y-m-d'), 'amt' => (float) $s->amount,
            ])->all(),
            'menu' => $outlet->menus->map(fn ($m) => [
                'nm' => $m->name, 'price' => (float) $m->price,
                'ing' => $m->ingredients->map(fn ($i) => ['nm' => $i->name, 'cost' => (float) $i->cost])->all(),
            ])->all(),
            'cash' => $outlet->cashRecords->map(fn ($r) => [
                'date' => $r->date->format('Y-m-d'), 'floatAwal' => (float) $r->float_start,
                'jualanTunai' => (float) $r->cash_sales, 'bayaranTunai' => (float) $r->cash_payments,
                'kiraSebenar' => (float) $r->actual_count,
            ])->all(),
        ];
    }

    /** Padam semua data anak outlet (kekalkan outlet & user). */
    public static function wipe(Outlet $outlet): void
    {
        $outlet->receipts()->delete(); // cascade ke receipt_items
        $outlet->menus()->delete();    // cascade ke menu_ingredients
        $outlet->staff()->delete();
        $outlet->equipment()->delete();
        $outlet->capexItems()->delete();
        $outlet->fixedCosts()->delete();
        $outlet->sales()->delete();
        $outlet->cashRecords()->delete();
        $outlet->categories()->delete();
    }

    /**
     * Ganti data outlet dengan kandungan array JSON (bentuk prototaip).
     *
     * @param  array<string,mixed>  $data
     */
    public static function import(Outlet $outlet, array $data): void
    {
        self::wipe($outlet);

        $s = $data['settings'] ?? [];
        $outlet->update([
            'name' => $s['outlet'] ?? $outlet->name,
            'target_sales' => $s['targetSales'] ?? $outlet->target_sales,
            'target_margin' => $s['targetMargin'] ?? $outlet->target_margin,
            'target_roi_months' => $s['targetRoiMonths'] ?? $outlet->target_roi_months,
            'epf_pct' => $s['epf'] ?? $outlet->epf_pct,
            'socso_pct' => $s['socso'] ?? $outlet->socso_pct,
        ]);

        foreach ($data['categories'] ?? [] as $x) {
            if (empty($x['key'])) {
                continue;
            }
            $outlet->categories()->create([
                'key' => $x['key'], 'name' => $x['name'] ?? $x['key'],
                'icon' => $x['icon'] ?? '📁', 'is_cogs' => (bool) ($x['is_cogs'] ?? false),
            ]);
        }
        foreach ($data['staff'] ?? [] as $x) {
            $outlet->staff()->create([
                'name' => $x['nm'] ?? '', 'position' => $x['pos'] ?? null,
                'salary' => $x['gaji'] ?? 0, 'type' => $x['jenis'] ?? 'Sepenuh Masa',
                'epf_socso' => (bool) ($x['kwsp'] ?? false),
            ]);
        }
        foreach ($data['equipment'] ?? [] as $x) {
            $outlet->equipment()->create([
                'name' => $x['nm'] ?? '', 'qty' => $x['qty'] ?? 1, 'unit_price' => $x['price'] ?? 0,
                'status' => in_array($x['status'] ?? 'perlu', ['perlu', 'beli'], true) ? $x['status'] : 'perlu',
                'group' => $x['grp'] ?? 'Lain-lain',
            ]);
        }
        foreach ($data['capex'] ?? [] as $x) {
            $outlet->capexItems()->create(['name' => $x['nm'] ?? '', 'amount' => $x['amt'] ?? 0]);
        }
        foreach ($data['fixed'] ?? [] as $x) {
            $outlet->fixedCosts()->create(['name' => $x['nm'] ?? '', 'amount' => $x['amt'] ?? 0, 'group' => $x['grp'] ?? 'Lain-lain']);
        }
        foreach ($data['receipts'] ?? [] as $x) {
            $receipt = $outlet->receipts()->create([
                'date' => $x['date'] ?? now()->format('Y-m-d'),
                'invoice_no' => $x['inv'] ?? null,
                'category' => $x['cat'] ?? 'lain', 'amount' => $x['amt'] ?? 0, 'note' => $x['note'] ?? null,
            ]);
            foreach ($x['items'] ?? [] as $it) {
                $receipt->items()->create(['name' => $it['nm'] ?? '', 'amount' => $it['amt'] ?? 0]);
            }
        }
        foreach ($data['sales'] ?? [] as $x) {
            $outlet->sales()->create(['date' => $x['date'] ?? now()->format('Y-m-d'), 'amount' => $x['amt'] ?? 0]);
        }
        foreach ($data['menu'] ?? [] as $x) {
            $menu = $outlet->menus()->create(['name' => $x['nm'] ?? '', 'price' => $x['price'] ?? 0]);
            foreach ($x['ing'] ?? [] as $ig) {
                $menu->ingredients()->create(['name' => $ig['nm'] ?? '', 'cost' => $ig['cost'] ?? 0]);
            }
        }
        foreach ($data['cash'] ?? [] as $x) {
            $outlet->cashRecords()->create([
                'date' => $x['date'] ?? now()->format('Y-m-d'),
                'float_start' => $x['floatAwal'] ?? 0, 'cash_sales' => $x['jualanTunai'] ?? 0,
                'cash_payments' => $x['bayaranTunai'] ?? 0, 'actual_count' => $x['kiraSebenar'] ?? 0,
            ]);
        }
    }
}
