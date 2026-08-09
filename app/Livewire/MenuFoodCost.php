<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Models\MenuIngredient;
use App\Services\OutletMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MenuFoodCost extends Component
{
    use InteractsWithOutlet;

    public string $newName = '';

    public string $newPrice = '';

    public ?int $openMenu = null;

    public array $editMenu = [];   // [menuId => [name, price]]

    public array $editIng = [];    // [ingredientId => [name, cost]]

    public array $ingName = [];    // [menuId => string]  bahan baharu

    public array $ingCost = [];    // [menuId => string]

    public function mount(): void
    {
        $this->syncEdit();
    }

    public function syncEdit(): void
    {
        $this->editMenu = [];
        $this->editIng = [];
        foreach ($this->outlet()->menus()->with('ingredients')->orderBy('id')->get() as $m) {
            $this->editMenu[$m->id] = ['name' => $m->name, 'price' => (string) $m->price];
            foreach ($m->ingredients as $ig) {
                $this->editIng[$ig->id] = ['name' => $ig->name, 'cost' => (string) $ig->cost];
            }
        }
    }

    public function addMenu(): void
    {
        $data = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newPrice' => ['required', 'numeric', 'min:0.01'],
        ], attributes: ['newName' => 'nama', 'newPrice' => 'harga']);

        $this->outlet()->menus()->create([
            'name' => $data['newName'],
            'price' => $data['newPrice'],
        ]);

        $this->reset('newName', 'newPrice');
        $this->syncEdit();
        $this->dispatch('toast', message: 'Menu ditambah');
    }

    public function toggleRecipe(int $id): void
    {
        $this->openMenu = $this->openMenu === $id ? null : $id;
    }

    public function deleteMenu(int $id): void
    {
        $this->outlet()->menus()->whereKey($id)->delete();
        if ($this->openMenu === $id) {
            $this->openMenu = null;
        }
        $this->syncEdit();
        $this->dispatch('toast', message: 'Menu dipadam');
    }

    public function addIngredient(int $menuId): void
    {
        $menu = $this->outlet()->menus()->find($menuId);
        if (! $menu) {
            return;
        }

        $name = trim((string) ($this->ingName[$menuId] ?? ''));
        if ($name === '') {
            $this->dispatch('toast', message: 'Isi nama bahan');

            return;
        }
        $cost = (float) ($this->ingCost[$menuId] ?? 0);

        $menu->ingredients()->create(['name' => $name, 'cost' => $cost]);
        $this->ingName[$menuId] = '';
        $this->ingCost[$menuId] = '';
        $this->syncEdit();
    }

    public function deleteIngredient(int $ingredientId): void
    {
        $this->ingredientQuery($ingredientId)?->delete();
        $this->syncEdit();
    }

    public function updated(string $name, $value): void
    {
        if (str_starts_with($name, 'editMenu.')) {
            [, $id, $field] = explode('.', $name);
            $menu = $this->outlet()->menus()->find($id);
            if (! $menu) {
                return;
            }
            if ($field === 'price') {
                $menu->price = is_numeric($value) ? (float) $value : 0;
            } else {
                $menu->name = $value;
            }
            $menu->save();
        } elseif (str_starts_with($name, 'editIng.')) {
            [, $id, $field] = explode('.', $name);
            $ing = $this->ingredientQuery((int) $id);
            if (! $ing) {
                return;
            }
            if ($field === 'cost') {
                $ing->cost = is_numeric($value) ? (float) $value : 0;
            } else {
                $ing->name = $value;
            }
            $ing->save();
        }
    }

    private function ingredientQuery(int $ingredientId): ?MenuIngredient
    {
        $outletId = $this->outlet()->id;

        return MenuIngredient::whereHas('menu', fn ($q) => $q->where('outlet_id', $outletId))
            ->find($ingredientId);
    }

    public function render()
    {
        $menus = $this->outlet()->menus()->with('ingredients')->orderBy('id')->get();

        return view('livewire.menu-food-cost', [
            'menus' => $menus,
            'avgGp' => OutletMetrics::averageGp($menus),
            'okThreshold' => config('outlet.food_cost.ok'),
            'warnThreshold' => config('outlet.food_cost.warn'),
        ]);
    }
}
