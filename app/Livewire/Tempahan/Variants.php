<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Variant;
use App\Models\VariantGroup;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Variant group dikongsi. Contoh: group "Chicken" dengan pilihan Chicken 1 / Chicken 2 / Chicken 3.
 * Produk menanda group; perubahan pada group/pilihan terus dipakai oleh semua produk berkenaan.
 */
#[Layout('layouts.app')]
class Variants extends Component
{
    use InteractsWithShop;

    /** Borang group: id null = group baharu. */
    public ?int $groupId = null;

    public string $groupName = '';

    /** Borang pilihan: dalam group mana, dan id pilihan (null = baharu). */
    public ?int $optGroupId = null;

    public ?int $optId = null;

    public array $o = ['name' => '', 'sku' => '', 'price' => '', 'promo_price' => ''];

    // ---- Group ----

    public function editGroup(int $id): void
    {
        $g = $this->findGroup($id);
        $this->groupId = $g->id;
        $this->groupName = $g->name;
        $this->resetValidation();
    }

    public function cancelGroup(): void
    {
        $this->reset('groupId', 'groupName');
        $this->resetValidation();
    }

    public function saveGroup(): void
    {
        $this->validate(['groupName' => ['required', 'string', 'max:120']], [], ['groupName' => __('nama group')]);
        $owner = $this->shopOwner();

        if ($this->groupId) {
            $this->findGroup($this->groupId)->update(['name' => trim($this->groupName)]);
            $this->dispatch('toast', message: __('Group dikemas kini.'));
        } else {
            $g = VariantGroup::create([
                'user_id' => $owner->id, 'name' => trim($this->groupName),
                'sort' => (VariantGroup::where('user_id', $owner->id)->max('sort') ?? -1) + 1,
            ]);
            $this->openOption($g->id); // terus tambah pilihan pertama
            $this->dispatch('toast', message: __('Group ditambah. Sekarang tambah pilihan.'));
        }
        $this->cancelGroup();
    }

    /** Required = pelanggan wajib pilih sekurang-kurangnya 1; Optional = boleh langkau. */
    public function setRequired(int $id, bool $required): void
    {
        $this->findGroup($id)->update(['is_required' => $required]);
        $this->dispatch('toast', message: __('Tetapan group disimpan.'));
    }

    /** Maksimum pilihan yang boleh dipilih pelanggan (1 hingga bilangan pilihan dalam group). */
    public function setMax(int $id, int $max): void
    {
        $g = $this->findGroup($id);
        $g->update(['max_select' => max(1, min($max, max(1, $g->activeVariants()->count())))]);
        $this->dispatch('toast', message: __('Tetapan group disimpan.'));
    }

    /** Padam group: dibuang dari semua produk. */
    public function deleteGroup(int $id): void
    {
        $g = $this->findGroup($id);
        DB::transaction(function () use ($g) {
            $g->update(['is_active' => false]);
            $products = $g->products()->get();
            $g->products()->detach();
            foreach ($products as $p) {
                $p->syncVariantLinks();
            }
        });
        if ($this->optGroupId === $id) {
            $this->cancelOption();
        }
        $this->dispatch('toast', message: __('Group dipadam.'));
    }

    /** Susunan group selepas drag. */
    public function sortGroups(array $ids): void
    {
        $ids = $this->ordered($this->groups()->pluck('id'), $ids);
        DB::transaction(function () use ($ids) {
            foreach ($ids as $i => $gid) {
                VariantGroup::whereKey($gid)->update(['sort' => $i]);
            }
            foreach (VariantGroup::whereIn('id', $ids)->get() as $g) {
                $g->syncProducts();
            }
        });
    }

    // ---- Pilihan dalam group ----

    public function openOption(int $groupId, ?int $optionId = null): void
    {
        $g = $this->findGroup($groupId);
        $this->optGroupId = $g->id;
        $this->optId = null;
        $this->o = ['name' => '', 'sku' => '', 'price' => '', 'promo_price' => ''];
        if ($optionId) {
            $v = $g->activeVariants()->whereKey($optionId)->firstOrFail();
            $this->optId = $v->id;
            $this->o = [
                'name' => $v->name, 'sku' => $v->sku ?? '',
                'price' => number_format($v->price_cents / 100, 2, '.', ''),
                'promo_price' => $v->promo_price_cents !== null ? number_format($v->promo_price_cents / 100, 2, '.', '') : '',
            ];
        }
        $this->resetValidation();
    }

    public function cancelOption(): void
    {
        $this->reset('optGroupId', 'optId', 'o');
        $this->resetValidation();
    }

    public function saveOption(): void
    {
        $g = $this->findGroup((int) $this->optGroupId);
        $this->validate([
            'o.name' => ['required', 'string', 'max:120'],
            'o.sku' => ['nullable', 'string', 'max:60'],
            'o.price' => ['required', 'numeric', 'min:0'],
            'o.promo_price' => ['nullable', 'numeric', 'min:0', 'lt:o.price'],
        ], [], ['o.name' => __('nama'), 'o.price' => __('harga'), 'o.promo_price' => __('harga promosi')]);

        $data = [
            'name' => trim($this->o['name']), 'sku' => trim($this->o['sku']) ?: null,
            'price_cents' => Money::toCents($this->o['price']),
            'promo_price_cents' => $this->o['promo_price'] === '' ? null : Money::toCents($this->o['promo_price']),
        ];

        DB::transaction(function () use ($g, $data) {
            if ($this->optId) {
                $g->activeVariants()->whereKey($this->optId)->firstOrFail()->update($data);
            } else {
                $g->variants()->create($data + ['sort' => ($g->variants()->max('sort') ?? -1) + 1]);
            }
            $g->syncProducts();
        });

        $this->dispatch('toast', message: $this->optId ? __('Pilihan dikemas kini.') : __('Pilihan ditambah.'));
        // Kekal dalam group yang sama untuk tambah pilihan seterusnya
        $this->openOption($g->id);
    }

    public function deleteOption(int $groupId, int $optionId): void
    {
        $g = $this->findGroup($groupId);
        DB::transaction(function () use ($g, $optionId) {
            $g->activeVariants()->whereKey($optionId)->firstOrFail()->update(['is_active' => false]);
            $g->syncProducts();
        });
        if ($this->optId === $optionId) {
            $this->openOption($g->id);
        }
        $this->dispatch('toast', message: __('Pilihan dipadam.'));
    }

    /** Buka/tutup pilihan. Berkuat kuasa di semua produk yang memakai group ini. */
    public function toggleAvailable(int $groupId, int $optionId): void
    {
        $v = $this->findGroup($groupId)->activeVariants()->whereKey($optionId)->firstOrFail();
        $v->update(['is_available' => ! $v->is_available]);
        $this->dispatch('toast', message: $v->is_available
            ? __(':name dibuka semula.', ['name' => $v->name])
            : __(':name ditutup — dipaparkan sebagai Tidak tersedia.', ['name' => $v->name]));
    }

    /** Susunan pilihan dalam satu group selepas drag. */
    public function sortOptions(int $groupId, array $ids): void
    {
        $g = $this->findGroup($groupId);
        $ids = $this->ordered($g->activeVariants()->pluck('id'), $ids);
        DB::transaction(function () use ($g, $ids) {
            foreach ($ids as $i => $vid) {
                Variant::whereKey($vid)->update(['sort' => $i]);
            }
            $g->syncProducts();
        });
    }

    // ---- Bantuan ----

    /** Susunan baharu: hanya id yang wujud dalam kumpulan; yang tertinggal diletak di hujung. */
    private function ordered($existing, array $ids)
    {
        $wanted = collect($ids)->map(fn ($i) => (int) $i)->filter(fn ($i) => $existing->contains($i))->unique();

        return $wanted->merge($existing->diff($wanted))->values();
    }

    private function groups()
    {
        return VariantGroup::where('user_id', $this->shopOwner()->id)->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    private function findGroup(int $id): VariantGroup
    {
        return $this->groups()->whereKey($id)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.tempahan.variants', [
            'groups' => $this->groups()->with(['activeVariants', 'products' => fn ($q) => $q->with('category')->orderBy('name')])->withCount('products')->get(),
        ]);
    }
}
