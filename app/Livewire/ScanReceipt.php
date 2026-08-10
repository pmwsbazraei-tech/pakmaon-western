<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Livewire\Concerns\WithMonthFilter;
use App\Support\Categories;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ScanReceipt extends Component
{
    use InteractsWithOutlet;
    use WithFileUploads;
    use WithMonthFilter;

    public ?string $category = null;

    public $image;

    public string $date = '';

    public string $invoiceNo = '';

    public string $amount = '';

    public array $items = [];   // [ ['name'=>..., 'amount'=>...] ]

    public string $note = '';

    // Pengurusan kategori custom
    public bool $addingCategory = false;

    public string $newCategoryName = '';

    public string $newCategoryIcon = '📁';

    public bool $newCategoryCogs = false;

    public array $editCat = [];   // [id => name]  edit nama kategori custom

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->syncEditCat();
    }

    public function syncEditCat(): void
    {
        $this->editCat = [];
        foreach ($this->outlet()->categories()->orderBy('id')->get() as $c) {
            $this->editCat[$c->id] = $c->name;
        }
    }

    public function selectCategory(string $id): void
    {
        if (array_key_exists($id, Categories::all($this->outlet()))) {
            $this->category = $id;
        }
    }

    public function startAddCategory(): void
    {
        $this->addingCategory = true;
    }

    public function cancelAddCategory(): void
    {
        $this->reset('addingCategory', 'newCategoryName', 'newCategoryIcon', 'newCategoryCogs');
    }

    public function addCategory(): void
    {
        $data = $this->validate([
            'newCategoryName' => ['required', 'string', 'max:60'],
            'newCategoryIcon' => ['nullable', 'string', 'max:8'],
        ], attributes: ['newCategoryName' => 'nama kategori']);

        $outlet = $this->outlet();
        $category = $outlet->categories()->create([
            'key' => Categories::uniqueKey($outlet, $data['newCategoryName']),
            'name' => $data['newCategoryName'],
            'icon' => $data['newCategoryIcon'] ?: '📁',
            'is_cogs' => $this->newCategoryCogs,
        ]);

        $this->category = $category->key;   // terus pilih kategori baharu
        $this->reset('addingCategory', 'newCategoryName', 'newCategoryIcon', 'newCategoryCogs');
        $this->syncEditCat();
        $this->dispatch('toast', message: 'Kategori ditambah');
    }

    public function deleteCategory(int $id): void
    {
        $this->outlet()->categories()->whereKey($id)->delete();
        $this->syncEditCat();
        $this->dispatch('toast', message: 'Kategori dipadam');
    }

    public function addItem(): void
    {
        $this->items[] = ['name' => '', 'amount' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->sumItems();
    }

    /** Dipanggil dari JS (Alpine) selepas OCR selesai untuk isi item + jumlah. */
    public function sumItems(): void
    {
        if (count($this->items) === 0) {
            return;
        }
        $total = collect($this->items)->sum(fn ($i) => (float) ($i['amount'] ?? 0));
        $this->amount = number_format($total, 2, '.', '');
    }

    public function updated(string $name, $value): void
    {
        if (preg_match('/^items\.\d+\.amount$/', $name)) {
            $this->sumItems();
        } elseif (preg_match('/^editCat\.(\d+)$/', $name, $m)) {
            $category = $this->outlet()->categories()->find($m[1]);
            if ($category && trim((string) $value) !== '') {
                $category->update(['name' => trim($value)]);
            }
        }
    }

    public function save(): void
    {
        $categories = Categories::keys($this->outlet());
        $this->validate([
            'category' => ['required', 'in:'.implode(',', $categories)],
            'date' => ['required', 'date'],
            'invoiceNo' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:8192'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0'],
        ], attributes: ['category' => 'kategori', 'amount' => 'jumlah', 'invoiceNo' => 'no invois']);

        $path = $this->image ? $this->image->store('receipts', 'public') : null;

        $receipt = $this->outlet()->receipts()->create([
            'date' => $this->date,
            'invoice_no' => $this->invoiceNo ?: null,
            'category' => $this->category,
            'amount' => (float) $this->amount,
            'note' => $this->note ?: null,
            'image_path' => $path,
        ]);

        foreach ($this->items as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $receipt->items()->create(['name' => $name, 'amount' => (float) ($item['amount'] ?? 0)]);
        }

        $catName = Categories::find($this->outlet(), $this->category)['nm'];
        $this->dispatch('toast', message: "Kos {$catName} ".rm($this->amount).' direkod');

        $this->reset('category', 'image', 'invoiceNo', 'amount', 'items', 'note');
        $this->date = now()->format('Y-m-d');
    }

    public function deleteReceipt(int $id): void
    {
        $receipt = $this->outlet()->receipts()->find($id);
        if ($receipt) {
            if ($receipt->image_path) {
                Storage::disk('public')->delete($receipt->image_path);
            }
            $receipt->delete();
        }
        $this->dispatch('toast', message: 'Resit dipadam');
    }

    public function render()
    {
        [$year, $mon] = explode('-', $this->month);

        $receipts = $this->outlet()->receipts()
            ->with('items')
            ->whereYear('date', (int) $year)
            ->whereMonth('date', (int) $mon)
            ->orderByDesc('date')
            ->get();

        $outlet = $this->outlet();

        return view('livewire.scan-receipt', [
            'outlet' => $outlet,
            'categories' => Categories::selectable($outlet),
            'customCategories' => $outlet->categories()->orderBy('id')->get(),
            'receipts' => $receipts,
        ]);
    }
}
