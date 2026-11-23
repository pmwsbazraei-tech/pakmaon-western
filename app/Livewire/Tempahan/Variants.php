<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Product;
use App\Models\Variant;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Senarai Variant dikongsi: nama + harga ditetapkan di sini, kemudian ditick pada produk.
 * Perubahan harga terus dipakai oleh semua produk yang menggunakan variant itu.
 */
#[Layout('layouts.app')]
class Variants extends Component
{
    use InteractsWithShop;

    /** Borang tambah / sunting. editingId null = variant baharu. */
    public ?int $editingId = null;

    public array $f = ['name' => '', 'sku' => '', 'price' => '', 'promo_price' => ''];

    public function edit(int $id): void
    {
        $v = $this->find($id);
        $this->editingId = $v->id;
        $this->f = [
            'name' => $v->name, 'sku' => $v->sku ?? '',
            'price' => number_format($v->price_cents / 100, 2, '.', ''),
            'promo_price' => $v->promo_price_cents !== null ? number_format($v->promo_price_cents / 100, 2, '.', '') : '',
        ];
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'f');
        $this->resetValidation();
    }

    public function save(): void
    {
        $owner = $this->shopOwner();
        $this->validate([
            'f.name' => ['required', 'string', 'max:120'],
            'f.sku' => ['nullable', 'string', 'max:60'],
            'f.price' => ['required', 'numeric', 'min:0'],
            'f.promo_price' => ['nullable', 'numeric', 'min:0', 'lt:f.price'],
        ], [], ['f.name' => __('nama'), 'f.price' => __('harga'), 'f.promo_price' => __('harga promosi')]);

        $data = [
            'name' => trim($this->f['name']), 'sku' => trim($this->f['sku']) ?: null,
            'price_cents' => Money::toCents($this->f['price']),
            'promo_price_cents' => $this->f['promo_price'] === '' ? null : Money::toCents($this->f['promo_price']),
        ];

        DB::transaction(function () use ($owner, $data) {
            if ($this->editingId) {
                $v = $this->find($this->editingId);
                $v->update($data);
                $v->syncToProducts();
            } else {
                Variant::create($data + ['user_id' => $owner->id, 'sort' => (Variant::where('user_id', $owner->id)->max('sort') ?? -1) + 1]);
            }
        });

        $this->dispatch('toast', message: $this->editingId ? __('Variant dikemas kini.') : __('Variant ditambah.'));
        $this->cancelEdit();
    }

    /** Padam variant: dibuang dari semua produk. Produk yang tiada variant lagi disembunyikan. */
    public function delete(int $id): void
    {
        $v = $this->find($id);
        $hidden = DB::transaction(function () use ($v) {
            $productIds = $v->productVariants()->where('is_active', true)->pluck('product_id');
            $v->productVariants()->update(['is_active' => false]);
            $v->update(['is_active' => false]);

            $empty = Product::whereIn('id', $productIds)->where('has_variants', true)
                ->whereDoesntHave('variants', fn ($q) => $q->where('is_active', true))->get();
            foreach ($empty as $p) {
                $p->update(['is_hidden' => true]);
            }

            return $empty->pluck('name');
        });

        if ($this->editingId === $id) {
            $this->cancelEdit();
        }
        $this->dispatch('toast', message: $hidden->isEmpty()
            ? __('Variant dipadam.')
            : __('Variant dipadam. Produk tanpa variant disembunyikan: :names', ['names' => $hidden->join(', ')]));
    }

    /** Alih variant ke atas (-1) atau ke bawah (+1); susunan dipakai di halaman pelanggan. */
    public function move(int $id, int $dir): void
    {
        $ids = $this->query()->pluck('id')->values();
        $pos = $ids->search($id);
        $to = $pos === false ? false : $pos + $dir;
        if ($to === false || $to < 0 || $to >= $ids->count()) {
            return;
        }
        [$ids[$pos], $ids[$to]] = [$ids[$to], $ids[$pos]];
        DB::transaction(function () use ($ids) {
            foreach ($ids as $i => $vid) {
                $v = Variant::find($vid);
                $v->update(['sort' => $i]);
                $v->syncToProducts();
            }
        });
    }

    private function query()
    {
        return Variant::where('user_id', $this->shopOwner()->id)->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    private function find(int $id): Variant
    {
        return $this->query()->whereKey($id)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.tempahan.variants', [
            'variants' => $this->query()
                ->withCount(['productVariants as products_count' => fn ($q) => $q->where('is_active', true)])
                ->get(),
        ]);
    }
}
