<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = ['id'];

    public const BOOKING_STATUSES = [
        'pending' => 'Menunggu Pengesahan',
        'confirmed' => 'Disahkan',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
        'no_show' => 'Tidak Hadir',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Belum Bayar',
        'pending' => 'Menunggu Bayaran',
        'paid' => 'Dibayar',
        'partial' => 'Deposit',
        'failed' => 'Gagal',
        'expired' => 'Tamat Tempoh',
        'refunded' => 'Dipulangkan',
    ];

    /** Butang status pesanan di backend. */
    public const FULFILLMENT_STATUSES = [
        'new' => 'Baharu',
        'deposit' => 'Deposit',
        'paid' => 'Fully Paid',
        'cancelled' => 'Dibatalkan',
    ];

    /** Label untuk paparan, termasuk status lama yang mungkin masih ada dalam rekod. */
    public const FULFILLMENT_LABELS = self::FULFILLMENT_STATUSES + [
        'preparing' => 'Disediakan',
        'ready' => 'Sedia',
        'completed' => 'Selesai',
    ];

    public const FULFILLMENTS = ['dine_in' => 'Makan di kedai', 'pickup' => 'Pickup', 'delivery' => 'Delivery'];

    protected function casts(): array
    {
        return [
            'booking_at' => 'datetime',
            'whatsapp_opened_at' => 'datetime',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'prices_include_sst' => 'boolean',
            'stock_released' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(OrderForm::class, 'order_form_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class)->latest('id');
    }

    public function isBooking(): bool
    {
        return $this->type === 'booking';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->booking_status === 'cancelled' || $this->fulfillment_status === 'cancelled';
    }

    public function publicUrl(): string
    {
        return route('order.show', ['reference' => $this->reference, 't' => $this->public_token]);
    }

    /** Baki yang perlu dibayar (0 jika sudah dibayar penuh). */
    public function balanceCents(): int
    {
        return $this->isPaid() ? 0 : max(0, $this->total_cents - (int) $this->deposit_cents);
    }

    public function log(string $action, ?string $details = null, ?int $userId = null): void
    {
        $this->activities()->create(['action' => $action, 'details' => $details, 'user_id' => $userId]);
    }

    /** Hadkan rekod kepada outlet yang boleh diakses pengguna. Dikuatkuasakan di server. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        return $q->whereIn('outlet_id', $user->bookingOutletIds());
    }

    /** Tapis ikut penapis admin: outlet, form, jenis, status, tarikh, carian. */
    public function scopeFiltered(Builder $q, array $f): Builder
    {
        $tz = config('app.timezone');

        return $q
            ->when($f['outlet'] ?? null, fn ($q, $v) => $q->where('outlet_id', (int) $v))
            ->when($f['form'] ?? null, fn ($q, $v) => $q->where('order_form_id', (int) $v))
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($f['booking_status'] ?? null, fn ($q, $v) => $q->where('booking_status', $v))
            ->when($f['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', CarbonImmutable::parse($v, $tz)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', CarbonImmutable::parse($v, $tz)->endOfDay()))
            ->when(trim((string) ($f['q'] ?? '')) !== '', function ($q) use ($f) {
                $term = trim($f['q']);
                $digits = preg_replace('/\D/', '', $term);
                $q->where(function ($w) use ($term, $digits) {
                    $w->where('reference', 'like', "%{$term}%")->orWhere('customer_name', 'like', "%{$term}%");
                    if (strlen($digits) >= 4) {
                        $w->orWhere('customer_phone', 'like', '%'.ltrim($digits, '0').'%');
                    }
                });
            });
    }
}
