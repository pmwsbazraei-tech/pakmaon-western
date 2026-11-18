<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Products extends Component
{
    use InteractsWithShop;

    #[Url]
    public string $q = '';

    public function render()
    {
        $t = trim($this->q);

        return view('livewire.tempahan.products', [
            'products' => Product::where('user_id', $this->shopOwner()->id)->with('category', 'variants')
                ->when($t !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$t}%")->orWhere('sku', 'like', "%{$t}%")
                    ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$t}%")->orWhere('name', 'like', "%{$t}%"))))
                ->orderBy('product_category_id')->orderBy('sort')->get(),
        ]);
    }
}
