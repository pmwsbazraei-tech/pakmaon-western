<?php

namespace App\Livewire\Concerns;

use App\Models\Order;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Akses tempahan online. Pemilik nampak semua outletnya; staf cawangan hanya
 * outlet yang diberikan. Semua pertanyaan pesanan melalui scope ini (di server).
 */
trait InteractsWithShop
{
    protected function shopOwner(): User
    {
        return auth()->user()->shopOwner() ?? abort(403);
    }

    /** @return Collection<int, Outlet> */
    protected function visibleOutlets(): Collection
    {
        return Outlet::whereIn('id', auth()->user()->bookingOutletIds())->orderBy('name')->get();
    }

    protected function visibleOrders(): Builder
    {
        return Order::query()->visibleTo(auth()->user());
    }

    protected function authorizeOrder(Order $order): void
    {
        abort_unless(auth()->user()->canAccessOutlet($order->outlet_id), 403, __('Anda tiada akses ke cawangan ini.'));
    }
}
