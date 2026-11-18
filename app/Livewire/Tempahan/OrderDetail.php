<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use App\Services\SlotService;
use App\Services\WhatsAppService;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OrderDetail extends Component
{
    use InteractsWithShop;

    public Order $order;

    public string $newDate = '';

    public string $newTime = '';

    public int $newGuests = 1;

    public string $cancelNote = '';

    public function mount(Order $order): void
    {
        $this->authorizeOrder($order);
        $this->order = $order;
        if ($order->booking_at) {
            $this->newDate = $order->booking_at->toDateString();
            $this->newTime = $order->booking_at->format('H:i');
            $this->newGuests = (int) $order->guests;
        }
    }

    public function setStatus(string $field, string $value, OrderService $orders): void
    {
        $this->authorizeOrder($this->order);
        abort_unless(in_array($field, ['booking_status', 'fulfillment_status'], true), 422);
        $labels = $field === 'booking_status' ? Order::BOOKING_STATUSES : Order::FULFILLMENT_STATUSES;
        abort_unless(array_key_exists($value, $labels), 422);
        abort_if($field === 'booking_status' && ! $this->order->isBooking(), 422);

        $order = $this->order->fresh();
        $old = $order->{$field};
        if ($old === $value) {
            return;
        }
        if ($old === 'cancelled') {
            $this->dispatch('toast', message: 'Rekod yang dibatalkan tidak boleh dibuka semula.');

            return;
        }

        $order->update([$field => $value] + ($value === 'confirmed' ? ['confirmed_at' => now()] : []));
        $note = $value === 'cancelled' && trim($this->cancelNote) !== '' ? '. Nota: '.trim($this->cancelNote) : '';
        $order->log($field.'_changed', ($labels[$old] ?? $old).' → '.$labels[$value].$note, auth()->id());

        if ($value === 'cancelled') {
            $orders->releaseStock($order, auth()->id());
            if ($order->isPaid()) {
                $order->log('refund_needed', 'Dibatalkan selepas dibayar. Lakukan refund melalui portal CHIP.', auth()->id());
            }
        }

        $this->order = $order->fresh();
        $this->dispatch('toast', message: 'Status dikemas kini.');
    }

    public function reschedule(SlotService $slots): void
    {
        $this->authorizeOrder($this->order);
        abort_unless($this->order->isBooking() && ! $this->order->isCancelled(), 422);
        $this->validate([
            'newDate' => ['required', 'date_format:Y-m-d'],
            'newTime' => ['required', 'date_format:H:i'],
            'newGuests' => ['required', 'integer', 'min:1'],
        ]);

        $at = CarbonImmutable::parse($this->newDate.' '.$this->newTime, config('app.timezone'));
        if ($msg = $slots->validate($this->order->outlet, $at, $this->newGuests, $this->order->id)) {
            $this->addError('newTime', $msg);

            return;
        }

        $old = $this->order->booking_at->format('j/n/Y g:i A').", {$this->order->guests} tetamu";
        $this->order->update(['booking_at' => $at, 'guests' => $this->newGuests]);
        $this->order->log('rescheduled', "{$old} → {$at->format('j/n/Y g:i A')}, {$this->newGuests} tetamu", auth()->id());
        $this->order = $this->order->fresh();
        $this->dispatch('toast', message: 'Dijadual semula. Maklumkan pelanggan melalui WhatsApp.');
    }

    public function syncPayment(PaymentService $payments): void
    {
        $this->authorizeOrder($this->order);
        $payments->refresh($this->order);
        $this->order = $this->order->fresh();
        $this->dispatch('toast', message: 'Status bayaran disemak dengan gateway.');
    }

    public function render(WhatsAppService $wa)
    {
        $this->order->load('items', 'outlet', 'form', 'payments', 'activities.user');

        return view('livewire.tempahan.order-detail', ['message' => $wa->message($this->order)]);
    }
}
