<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithOutlet;
use App\Livewire\Concerns\WithMonthFilter;
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

    public string $amount = '';

    public array $items = [];   // [ ['name'=>..., 'amount'=>...] ]

    public string $note = '';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function selectCategory(string $id): void
    {
        if (array_key_exists($id, config('outlet.categories'))) {
            $this->category = $id;
        }
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

    public function updated(string $name): void
    {
        if (preg_match('/^items\.\d+\.amount$/', $name)) {
            $this->sumItems();
        }
    }

    public function save(): void
    {
        $categories = array_keys(config('outlet.categories'));
        $this->validate([
            'category' => ['required', 'in:'.implode(',', $categories)],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:8192'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0'],
        ], attributes: ['category' => 'kategori', 'amount' => 'jumlah']);

        $path = $this->image ? $this->image->store('receipts', 'public') : null;

        $receipt = $this->outlet()->receipts()->create([
            'date' => $this->date,
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

        $catName = config("outlet.categories.{$this->category}.nm");
        $this->dispatch('toast', message: "Kos {$catName} ".rm($this->amount).' direkod');

        $this->reset('category', 'image', 'amount', 'items', 'note');
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

        return view('livewire.scan-receipt', [
            'categories' => config('outlet.categories'),
            'receipts' => $receipts,
        ]);
    }
}
