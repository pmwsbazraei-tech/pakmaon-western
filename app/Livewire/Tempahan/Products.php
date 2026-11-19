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

    /** Alih kategori ke atas (-1) atau ke bawah (+1). Susunan ini juga dipakai di landing page. */
    public function moveCategory(int $id, int $dir): void
    {
        $this->swap(ProductCategory::where('user_id', $this->shopOwner()->id), $id, $dir);
    }

    /** Alih produk ke atas (-1) atau ke bawah (+1) dalam kategori yang sama. */
    public function moveProduct(int $id, int $dir): void
    {
        $owner = $this->shopOwner();
        $product = Product::where('user_id', $owner->id)->findOrFail($id);
        $this->swap(Product::where('user_id', $owner->id)->where('product_category_id', $product->product_category_id), $id, $dir);
    }

    private function swap(Builder $siblings, int $id, int $dir): void
    {
        $ids = $siblings->orderBy('sort')->orderBy('id')->pluck('id')->values();
        $i = $ids->search($id);
        $j = $i === false ? false : $i + ($dir < 0 ? -1 : 1);
        if ($j === false || $j < 0 || $j >= $ids->count()) {
            return;
        }

        $order = $ids->all();
        [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
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

        $products = Product::where('user_id', $owner->id)->with('variants')
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
