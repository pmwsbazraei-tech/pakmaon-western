<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Outlet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Peraturan slot booking. Semua masa dalam Asia/Kuala_Lumpur.
 */
class SlotService
{
    /**
     * @return array{open: bool, reason: ?string, slots: array<int, array{time: string, remaining: int, available: bool}>}
     */
    public function slotsFor(Outlet $outlet, string $date, ?int $ignoreOrderId = null): array
    {
        $tz = config('app.timezone');
        $day = CarbonImmutable::parse($date, $tz)->startOfDay();
        $now = CarbonImmutable::now($tz);

        if (! $outlet->accepts_orders || ! $outlet->booking_enabled) {
            return ['open' => false, 'reason' => 'Cawangan ini tidak menerima booking buat masa ini.', 'slots' => []];
        }
        if ($day->lt($now->startOfDay())) {
            return ['open' => false, 'reason' => 'Tarikh sudah berlalu.', 'slots' => []];
        }
        if ($day->gt($now->startOfDay()->addDays($outlet->advance_days))) {
            return ['open' => false, 'reason' => "Booking hanya dibuka {$outlet->advance_days} hari ke hadapan.", 'slots' => []];
        }
        if ($outlet->closures()->whereDate('date', $day->toDateString())->exists()) {
            return ['open' => false, 'reason' => 'Cawangan tutup pada tarikh ini.', 'slots' => []];
        }

        $hour = $outlet->hours()->where('weekday', $day->dayOfWeek)->first();
        if (! $hour || $hour->is_closed || ! $hour->opens_at || ! $hour->closes_at) {
            return ['open' => false, 'reason' => 'Cawangan tutup pada hari ini.', 'slots' => []];
        }

        $start = $day->setTimeFromTimeString($hour->opens_at);
        $end = $day->setTimeFromTimeString($hour->closes_at);
        if ($end->lte($start)) {
            $end = $end->addDay(); // waktu operasi melepasi tengah malam
        }
        $lastSlot = $end->subMinutes($outlet->slot_minutes);
        $earliest = $now->addMinutes($outlet->lead_minutes);

        $booked = $this->bookedGuests($outlet, $start, $end, $ignoreOrderId);

        $slots = [];
        for ($t = $start; $t->lte($lastSlot); $t = $t->addMinutes($outlet->slot_minutes)) {
            $key = $t->format('Y-m-d H:i');
            $remaining = max(0, $outlet->slot_capacity - ($booked[$key] ?? 0));
            $slots[] = [
                'time' => $t->format('H:i'),
                'remaining' => $remaining,
                'available' => $t->gte($earliest) && $remaining > 0,
            ];
        }

        return ['open' => true, 'reason' => null, 'slots' => $slots];
    }

    /** Pulangkan mesej ralat, atau null jika slot sah dan muat untuk bilangan tetamu. */
    public function validate(Outlet $outlet, CarbonImmutable $at, int $guests, ?int $ignoreOrderId = null): ?string
    {
        if ($guests < 1) {
            return 'Sila nyatakan bilangan tetamu.';
        }
        if ($guests > $outlet->max_guests_per_booking) {
            return "Maksimum {$outlet->max_guests_per_booking} tetamu bagi satu booking. Hubungi cawangan untuk kumpulan besar.";
        }

        $result = $this->slotsFor($outlet, $at->toDateString(), $ignoreOrderId);
        if (! $result['open']) {
            return $result['reason'];
        }

        $slot = collect($result['slots'])->firstWhere('time', $at->format('H:i'));
        if (! $slot) {
            return 'Masa yang dipilih bukan slot yang sah untuk cawangan ini.';
        }
        if (! $slot['available']) {
            return $slot['remaining'] > 0 ? 'Slot ini sudah terlalu hampir. Sila pilih masa lain.' : 'Slot ini sudah penuh. Sila pilih masa lain.';
        }
        if ($guests > $slot['remaining']) {
            return "Slot ini hanya ada tempat untuk {$slot['remaining']} tetamu lagi.";
        }

        return null;
    }

    /** @return array<string, int> */
    private function bookedGuests(Outlet $outlet, CarbonImmutable $from, CarbonImmutable $to, ?int $ignoreOrderId): array
    {
        return Order::query()
            ->where('outlet_id', $outlet->id)
            ->where('type', 'booking')
            ->whereNotIn('booking_status', ['cancelled'])
            ->whereNotIn('payment_status', ['expired'])
            ->when($ignoreOrderId, fn ($q) => $q->where('id', '!=', $ignoreOrderId))
            ->whereBetween('booking_at', [$from, $to])
            ->get(['booking_at', 'guests'])
            ->groupBy(fn ($o) => Carbon::parse($o->booking_at)->format('Y-m-d H:i'))
            ->map(fn ($g) => (int) $g->sum('guests'))
            ->all();
    }
}
