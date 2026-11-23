@php use App\Models\Order; use App\Support\Phone; @endphp
<div>
    <div class="pagehead">
        <div><h1>{{ __('Pesanan & Booking') }}</h1><p>{{ __('Cari dan tapis semua rekod dari landing page.') }}</p></div>
        <a class="btn sec" href="{{ route('tempahan.orders.export', $this->filters()) }}">{{ __('Eksport CSV (ikut penapis)') }}</a>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div class="row">
            <div style="flex:2"><label>{{ __('Cari') }}</label><input wire:model.live.debounce.400ms="q" placeholder="{{ __('No. order, rujukan, nama, telefon') }}"></div>
            <div><label>{{ __('Jenis') }}</label><select wire:model.live="type"><option value="">{{ __('Semua') }}</option><option value="booking">{{ __('Booking') }}</option><option value="purchase">{{ __('Pembelian') }}</option></select></div>
            <div><label>{{ __('Outlet') }}</label><select wire:model.live="outlet"><option value="">{{ __('Semua') }}</option>@foreach ($outlets as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div><label>{{ __('Landing page') }}</label><select wire:model.live="form"><option value="">{{ __('Semua') }}</option>@foreach ($forms as $f)<option value="{{ $f->id }}">{{ $f->title }}</option>@endforeach</select></div>
        </div>
        <div class="row" style="margin-top:10px">
            <div><label>{{ __('Status booking') }}</label><select wire:model.live="booking_status"><option value="">{{ __('Semua') }}</option>@foreach (Order::BOOKING_STATUSES as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach</select></div>
            <div><label>{{ __('Status bayaran') }}</label><select wire:model.live="payment_status"><option value="">{{ __('Semua') }}</option>@foreach (Order::PAYMENT_STATUSES as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach</select></div>
            <div><label>{{ __('Dari') }}</label><input type="date" wire:model.live="from"></div>
            <div><label>{{ __('Hingga') }}</label><input type="date" wire:model.live="to"></div>
            <div style="flex:0"><button class="btn sec" wire:click="resetFilters">{{ __('Reset') }}</button></div>
        </div>
    </div>

    <div class="card">
        @if ($canDelete && count($selected))
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap">
                <b>{{ __(':count dipilih', ['count' => count($selected)]) }}</b>
                <button class="btn danger sm" wire:click="deleteSelected"
                        wire:confirm="{{ __('Padam :count pesanan ini secara kekal? Stok akan dipulangkan. Tindakan ini tak boleh dibatalkan.', ['count' => count($selected)]) }}">{{ __('Padam') }}</button>
                <button class="btn sec sm" wire:click="$set('selected', [])">{{ __('Batal pilih') }}</button>
            </div>
        @endif
        <div class="table-wrap">
            <table class="tbl-sticky">
                <thead><tr>@if ($canDelete)<th style="width:36px"><input type="checkbox" aria-label="{{ __('Pilih semua di halaman ini') }}" x-on:change="$wire.set('selected', $event.target.checked ? @js($orders->pluck('id')->map(fn ($id) => (string) $id)->values()) : [])" @checked($orders->isNotEmpty() && count($selected) === $orders->count())></th>@endif<th>{{ __('Rujukan') }}</th><th>{{ __('Pelanggan') }}</th><th>{{ __('Outlet') }}</th><th class="num">{{ __('Jumlah') }}</th><th>{{ __('Status') }}</th><th>{{ __('Dicipta') }}</th></tr></thead>
                <tbody>
                    @forelse ($orders as $o)
                        <tr wire:key="o-{{ $o->id }}">
                            @if ($canDelete)<td><input type="checkbox" wire:model.live="selected" value="{{ $o->id }}" aria-label="{{ __('Pilih :ref', ['ref' => $o->displayReference()]) }}"></td>@endif
                            <td><a href="{{ route('tempahan.orders.show', $o) }}" wire:navigate style="font-weight:700;text-decoration:underline">{{ $o->displayReference() }}</a><div class="hint" style="margin:0">{{ $o->isBooking() ? __('Booking') : __('Pembelian') }}@if ($o->booking_at) · {{ $o->booking_at->format('j/n g:i A') }} · {{ __(':count org', ['count' => $o->guests]) }}@endif @if ($o->customer_order_no) · {{ $o->reference }}@endif</div></td>
                            <td>{{ $o->customer_name }}<div class="hint" style="margin:0">{{ Phone::display($o->customer_phone) }}</div></td>
                            <td>{{ $o->outlet?->name }}</td>
                            <td class="num">@sen($o->total_cents)@if ($o->deposit_cents && ! $o->isPaid())<div class="hint" style="margin:0;white-space:nowrap">{{ __('Deposit') }} @sen($o->deposit_cents) · {{ __('Baki') }} @sen($o->balanceCents())</div>@endif</td>
                            <td>@include('livewire.tempahan._status', ['o' => $o])</td>
                            <td style="white-space:nowrap;color:var(--muted)">{{ $o->created_at->format('j/n g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canDelete ? 7 : 6 }}" class="empty">{{ __('Tiada rekod.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
                <button class="btn sec sm" wire:click="previousPage" @disabled($orders->onFirstPage())>← {{ __('Sebelum') }}</button>
                <span class="hint" style="margin:0">{{ __('Halaman :page / :last', ['page' => $orders->currentPage(), 'last' => $orders->lastPage()]) }}</span>
                <button class="btn sec sm" wire:click="nextPage" @disabled(! $orders->hasMorePages())>{{ __('Seterusnya') }} →</button>
            </div>
        @endif
    </div>
</div>
