<?php

namespace App\Models;

use App\Support\OnlinePayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderForm extends Model
{
    protected $guarded = ['id'];

    public const DEFAULT_SIMPLE_WHATSAPP_TEMPLATE = "Salam Pak Maon Western ({outlet}),\n\nSaya ingin membuat pesanan.\nRujukan: {reference}\nNama: {name}\nTelefon: {phone}\nNombor order: {order_no}\nCawangan: {outlet}\nEmel: {email}\n\nMenu:\n{items}\n\n{bill}\n{payment_link}";

    public const DEFAULT_WHATSAPP_TEMPLATE = "Salam Pak Maon Western ({outlet}),\n\nSaya ingin membuat tempahan.\nRujukan: {reference}\nNama: {name}\nNombor order: {order_no}\nTelefon: {phone}\nTarikh: {date}\nMasa: {time}\nBilangan tetamu: {guests}\n\nMenu:\n{items}\n\n{bill}\nNota: {notes}\n{payment_link}";

    protected function casts(): array
    {
        return [
            'notices' => 'array',
            'purpose_booking' => 'boolean',
            'purpose_purchase' => 'boolean',
            'simple_form' => 'boolean',
            'prices_include_sst' => 'boolean',
            'sst_rate' => 'decimal:2',
            'allow_pickup' => 'boolean',
            'allow_delivery' => 'boolean',
            'action_whatsapp' => 'boolean',
            'action_online' => 'boolean',
            'auto_confirm_on_payment' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Outlet (cawangan) yang dibenarkan pada page ini. */
    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class)->orderBy('name');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort')->orderByPivot('sort');
    }

    /** Variant produk yang tidak ditick (tidak dipaparkan) pada landing page ini. */
    public function hiddenVariants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'order_form_hidden_variants');
    }

    /** @return list<int> */
    public function hiddenVariantIds(): array
    {
        return $this->hiddenVariants()->pluck('product_variants.id')->map(fn ($i) => (int) $i)->all();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function allowsTableOnly(): bool
    {
        return $this->booking_mode === 'table_only_allowed';
    }

    public function publicUrl(): string
    {
        return route('form.show', $this->slug);
    }

    /** Bayar Online diaktifkan pada page DAN gateway sedia digunakan. */
    public function onlinePaymentEnabled(): bool
    {
        return $this->action_online && OnlinePayment::enabled();
    }

    public function template(): string
    {
        return $this->whatsapp_template ?: ($this->simple_form ? self::DEFAULT_SIMPLE_WHATSAPP_TEMPLATE : self::DEFAULT_WHATSAPP_TEMPLATE);
    }
}
