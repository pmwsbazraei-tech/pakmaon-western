<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Products extends Component
{
    use InteractsWithShop;

    #[Url]
    public string $q = '';

    public function mount(): void
    {
        if ($msg = session('toast')) {
            $this->dispatch('toast', message: $msg);
        }
    }

    /** Susunan kategori selepas drag. Susunan ini juga dipakai di landing page. */
    public function sortCategories(array $ids): void
    {
        $this->applyOrder(ProductCategory::where('user_id', $this->shopOwner()->id), $ids);
    }

    /** Susunan produk dalam satu kategori selepas drag (0 = tanpa kategori). */
    public function sortProducts(int $categoryId, array $ids): void
    {
        $this->applyOrder(
            Product::where('user_id', $this->shopOwner()->id)->where('product_category_id', $categoryId ?: null),
            $ids,
        );
    }

    /** Simpan susunan baharu; id yang bukan milik kumpulan ini diabaikan, yang tertinggal diletak di hujung. */
    private function applyOrder(Builder $siblings, array $ids): void
    {
        $existing = $siblings->orderBy('sort')->orderBy('id')->pluck('id');
        $wanted = collect($ids)->map(fn ($i) => (int) $i)->filter(fn ($i) => $existing->contains($i))->unique();
        $order = $wanted->merge($existing->diff($wanted))->values();
        $model = $siblings->getModel();

        DB::transaction(function () use ($order, $model) {
            foreach ($order as $pos => $rowId) {
                $model->newQuery()->whereKey($rowId)->update(['sort' => $pos + 1]);
            }
        });
    }

    public function render()
    {
        $owner = $this->shopOwner();
        $t = trim($this->q);

        $products = Product::where('user_id', $owner->id)->with('variants', 'variantGroups')
            ->when($t !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$t}%")->orWhere('sku', 'like', "%{$t}%")
                ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$t}%")->orWhere('name', 'like', "%{$t}%"))))
            ->orderBy('sort')->orderBy('id')->get()
            ->groupBy(fn ($p) => $p->product_category_id ?? 0);

        $categories = ProductCategory::where('user_id', $owner->id)->orderBy('sort')->orderBy('id')->get();

        // Kumpulan ikut susunan kategori; "Tanpa kategori" di hujung (sama seperti landing page)
        $groups = $categories->map(fn ($c) => ['category' => $c, 'products' => $products->get($c->id, collect())])
            ->push(['category' => null, 'products' => $products->get(0, collect())])
            ->filter(fn ($g) => $g['products']->isNotEmpty() || ($g['category'] && $t === ''))
            ->values();

        return view('livewire.tempahan.products', [
            'groups' => $groups,
            'categoryIds' => $categories->pluck('id')->values(),
            'hasProducts' => $products->isNotEmpty(),
        ]);
    }
}
