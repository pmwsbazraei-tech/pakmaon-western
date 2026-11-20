@php use App\Models\Order; use App\Support\Phone; @endphp
<div>
    <div class="pagehead">
        <div><h1>Pesanan & Booking</h1><p>Cari dan tapis semua rekod dari landing page.</p></div>
        <a class="btn sec" href="{{ route('tempahan.orders.export', $this->filters()) }}">Eksport CSV (ikut penapis)</a>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div class="row">
            <div style="flex:2"><label>Cari</label><input wire:model.live.debounce.400ms="q" placeholder="Rujukan, nama, telefon"></div>
            <div><label>Jenis</label><select wire:model.live="type"><option value="">Semua</option><option value="booking">Booking</option><option value="purchase">Pembelian</option></select></div>
            <div><label>Outlet</label><select wire:model.live="outlet"><option value="">Semua</option>@foreach ($outlets as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div><label>Landing page</label><select wire:model.live="form"><option value="">Semua</option>@foreach ($forms as $f)<option value="{{ $f->id }}">{{ $f->title }}</option>@endforeach</select></div>
        </div>
        <div class="row" style="margin-top:10px">
            <div><label>Status booking</label><select wire:model.live="booking_status"><option value="">Semua</option>@foreach (Order::BOOKING_STATUSES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div><label>Status bayaran</label><select wire:model.live="payment_status"><option value="">Semua</option>@foreach (Order::PAYMENT_STATUSES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div><label>Dari</label><input type="date" wire:model.live="from"></div>
            <div><label>Hingga</label><input type="date" wire:model.live="to"></div>
            <div style="flex:0"><button class="btn sec" wire:click="resetFilters">Reset</button></div>
        </div>
    </div>

    <div class="card">
        @if ($canDelete && count($selected))
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap">
                <b>{{ count($selected) }} dipilih</b>
                <button class="btn danger sm" wire:click="deleteSelected"
                        wire:confirm="Padam {{ count($selected) }} pesanan ini secara kekal? Stok akan dipulangkan. Tindakan ini tak boleh dibatalkan.">Padam</button>
                <button class="btn sec sm" wire:click="$set('selected', [])">Batal pilih</button>
            </div>
        @endif
        <div class="table-wrap">
            <table class="tbl-sticky">
                <thead><tr>@if ($canDelete)<th style="width:36px"><input type="checkbox" aria-label="Pilih semua di halaman ini" x-on:change="$wire.set('selected', $event.target.checked ? @js($orders->pluck('id')->map(fn ($id) => (string) $id)->values()) : [])" @checked($orders->isNotEmpty() && count($selected) === $orders->count())></th>@endif<th>Rujukan</th><th>Pelanggan</th><th>Outlet</th><th>Jadual / cara</th><th class="num">Jumlah</th><th>Status</th><th>Dicipta</th></tr></thead>
                <tbody>
                    @forelse ($orders as $o)
                        <tr wire:key="o-{{ $o->id }}">
                            @if ($canDelete)<td><input type="checkbox" wire:model.live="selected" value="{{ $o->id }}" aria-label="Pilih {{ $o->reference }}"></td>@endif
                            <td><a href="{{ route('tempahan.orders.show', $o) }}" wire:navigate style="font-weight:700;text-decoration:underline">{{ $o->reference }}</a><div class="hint" style="margin:0">{{ $o->isBooking() ? 'Booking' : 'Pembelian' }}</div></td>
                            <td>{{ $o->customer_name }}<div class="hint" style="margin:0">{{ Phone::display($o->customer_phone) }}@if ($o->customer_order_no) · No. order {{ $o->customer_order_no }}@endif</div></td>
                            <td>{{ $o->outlet?->name }}</td>
                            <td style="white-space:nowrap">{{ $o->booking_at ? $o->booking_at->format('j/n/Y g:i A').' · '.$o->guests.' org' : Order::FULFILLMENTS[$o->fulfillment] }}</td>
                            <td class="num">@sen($o->total_cents)@if ($o->deposit_cents && ! $o->isPaid())<div class="hint" style="margin:0;white-space:nowrap">Deposit @sen($o->deposit_cents) · Baki @sen($o->balanceCents())</div>@endif</td>
                            <td>@include('livewire.tempahan._status', ['o' => $o])</td>
                            <td style="white-space:nowrap;color:var(--muted)">{{ $o->created_at->format('j/n g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canDelete ? 8 : 7 }}" class="empty">Tiada rekod.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
                <button class="btn sec sm" wire:click="previousPage" @disabled($orders->onFirstPage())>← Sebelum</button>
                <span class="hint" style="margin:0">Halaman {{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span>
                <button class="btn sec sm" wire:click="nextPage" @disabled(! $orders->hasMorePages())>Seterusnya →</button>
            </div>
        @endif
    </div>
</div>
