<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use App\Services\SlotService;
use App\Services\WhatsAppService;
use App\Support\Money;
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

    public string $depositAmount = '';

    public function mount(Order $order): void
    {
        $this->authorizeOrder($order);
        $this->order = $order;
        $this->depositAmount = $order->deposit_cents ? number_format($order->deposit_cents / 100, 2, '.', '') : '';
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
            $this->dispatch('toast', message: __('Rekod yang dibatalkan tidak boleh dibuka semula.'));

            return;
        }

        $extra = $value === 'confirmed' ? ['confirmed_at' => now()] : [];
        if ($field === 'fulfillment_status') {
            if ($value === 'deposit') {
                $this->recordDeposit($orders);

                return;
            }
            $paidOnline = $order->payments()->where('status', 'paid')->exists();
            if ($value === 'paid') {
                $extra = ['payment_status' => 'paid', 'paid_at' => $order->paid_at ?? now()];
            } elseif ($value === 'new' && ! $paidOnline) {
                $extra = ['payment_status' => 'unpaid', 'paid_at' => null, 'deposit_cents' => 0];
            }
        }

        $order->update([$field => $value] + $extra);
        $note = $value === 'cancelled' && trim($this->cancelNote) !== '' ? '. Nota: '.trim($this->cancelNote) : '';
        $order->log($field.'_changed', ($labels[$old] ?? $old).' → '.$labels[$value].$note, auth()->id());

        if ($value === 'cancelled') {
            $orders->releaseStock($order, auth()->id());
            if ($order->isPaid()) {
                $order->log('refund_needed', 'Dibatalkan selepas dibayar. Lakukan refund melalui portal CHIP.', auth()->id());
            }
        }

        $this->order = $order->fresh();
        $this->dispatch('toast', message: __('Status dikemas kini.'));
    }

    /** Rekod deposit manual (RM). Baki = jumlah - deposit. */
    public function recordDeposit(OrderService $orders): void
    {
        $this->authorizeOrder($this->order);
        $order = $this->order->fresh();
        abort_if($order->isBooking(), 422);
        if ($order->isCancelled()) {
            $this->dispatch('toast', message: __('Rekod yang dibatalkan tidak boleh dikemas kini.'));

            return;
        }

        $cents = Money::toCents($this->depositAmount);
        if (! $cents || $cents <= 0) {
            $this->addError('depositAmount', __('Masukkan jumlah deposit.'));

            return;
        }
        if ($cents >= $order->total_cents) {
            $this->addError('depositAmount', __('Deposit mesti kurang daripada jumlah (:total). Guna "Fully Paid" jika dibayar penuh.', ['total' => Money::format($order->total_cents)]));

            return;
        }

        $order->update(['fulfillment_status' => 'deposit', 'payment_status' => 'partial', 'deposit_cents' => $cents, 'paid_at' => null]);
        $order->log('deposit_recorded', 'Deposit '.Money::format($cents).' diterima. Baki '.Money::format($order->total_cents - $cents).'.', auth()->id());

        $this->resetErrorBag('depositAmount');
        $this->order = $order->fresh();
        $this->dispatch('toast', message: __('Deposit direkodkan.'));
    }

    public function deleteOrder(OrderService $orders)
    {
        $this->authorizeOrder($this->order);
        abort_unless(auth()->user()->canManageShop(), 403);

        if (! $orders->delete($this->order->fresh(), auth()->id())) {
            $this->dispatch('toast', message: __('Pesanan yang sudah dibayar online tidak boleh dipadam. Batalkan dan buat refund.'));

            return null;
        }

        session()->flash('toast', __('Pesanan :ref dipadam.', ['ref' => $this->order->reference]));

        return $this->redirectRoute('tempahan.orders', navigate: true);
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
        $this->dispatch('toast', message: __('Dijadual semula. Maklumkan pelanggan melalui WhatsApp.'));
    }

    public function syncPayment(PaymentService $payments): void
    {
        $this->authorizeOrder($this->order);
        $payments->refresh($this->order);
        $this->order = $this->order->fresh();
        $this->dispatch('toast', message: __('Status bayaran disemak dengan gateway.'));
    }

    public function render(WhatsAppService $wa)
    {
        $this->order->load('items', 'outlet', 'form', 'payments', 'activities.user');

        return view('livewire.tempahan.order-detail', ['message' => $wa->message($this->order)]);
    }
}
