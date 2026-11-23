<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Variant group (cth. "Chicken") dengan beberapa pilihan; ditick pada produk. */
class VariantGroup extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_required' => 'boolean', 'max_select' => 'integer'];
    }

    /**
     * Maksimum pilihan sebenar. Optional: boleh pilih semua pilihan yang ada.
     * Required: ikut tetapan, tidak melebihi bilangan pilihan aktif (sekurang-kurangnya 1).
     */
    public function effectiveMax(?int $optionCount = null): int
    {
        $count = max(1, $optionCount ?? $this->activeVariants()->count());
        if (! $this->is_required) {
            return $count;
        }

        return max(1, min((int) $this->max_select ?: 1, $count));
    }

    /** Penerangan peraturan untuk pelanggan, cth. "Wajib · pilih 1" / "Pilihan · sehingga 3". */
    public function ruleLabel(?int $optionCount = null): string
    {
        $max = $this->effectiveMax($optionCount);
        if ($this->is_required) {
            return $max === 1 ? __('Wajib · pilih 1') : __('Wajib · pilih 1 hingga :max', ['max' => $max]);
        }

        return $max === 1 ? __('Pilihan · maksimum 1') : __('Pilihan · sehingga :max', ['max' => $max]);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class)->orderBy('sort')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Kemas kini variant semua produk yang menanda group ini. */
    public function syncProducts(): void
    {
        foreach ($this->products()->get() as $product) {
            $product->syncVariantLinks();
        }
    }
}
