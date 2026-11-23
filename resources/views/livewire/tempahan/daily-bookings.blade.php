<div>
    <x-pagehead :title="__('Booking Harian')" :sub="$day->translatedFormat('l, j F Y').' · '.__(':count booking · :guests tetamu', ['count' => $count, 'guests' => $guestTotal])" />

    <div class="card" style="margin-bottom:14px">
        <div class="row">
            <div><label>{{ __('Tarikh') }}</label><input type="date" wire:model.live="date"></div>
            <div><label>{{ __('Outlet') }}</label><select wire:model.live="outlet">@foreach ($outlets as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div style="flex:0 1 auto;white-space:nowrap">
                <div class="daynav" role="group" aria-label="{{ __('Navigasi tarikh') }}">
                    <button class="btn sec" wire:click="shiftDay(-1)">← {{ __('Semalam') }}</button>
                    <button @class(['btn sec', 'today' => $date === now()->toDateString()]) wire:click="$set('date', '{{ now()->toDateString() }}')">{{ __('Hari ini') }}</button>
                    <button class="btn sec" wire:click="shiftDay(1)">{{ __('Esok') }} →</button>
                </div>
            </div>
        </div>
        <label class="chk" style="margin-top:10px"><input type="checkbox" wire:model.live="showCancelled"> {{ __('Tunjuk yang dibatalkan') }}</label>
    </div>

    <div class="split">
        <div style="display:grid;gap:12px">
            @forelse ($slots as $time => $list)
                <div class="card kitchen-slot">
                    <div class="t">{{ \Carbon\Carbon::createFromFormat('H:i', $time)->format('g:i A') }}<div class="hint" style="font-weight:500">{{ __(':count tetamu', ['count' => $list->where('booking_status', '!=', 'cancelled')->sum('guests')]) }}</div></div>
                    <div style="display:grid;gap:10px">
                        @foreach ($list as $o)
                            <div class="bk-item {{ $o->booking_status === 'confirmed' ? 'ok' : ($o->booking_status === 'cancelled' ? 'bad' : 'warn') }}">
                                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px"><a href="{{ route('tempahan.orders.show', $o) }}" wire:navigate><b style="text-decoration:underline">{{ $o->customer_name }}</b></a><span class="hint" style="margin:0">{{ $o->displayReference() }} · {{ __(':count tetamu', ['count' => $o->guests]) }}</span></div>
                                <div style="margin:4px 0">@include('livewire.tempahan._status', ['o' => $o])</div>
                                @forelse ($o->items as $i)
                                    <div style="font-size:13px">{{ $i->quantity }} × {{ $i->product_name }}@if ($i->variant_name) ({{ $i->variant_name }})@endif</div>
                                @empty
                                    <div class="hint" style="margin:0">{{ __('Meja sahaja') }}</div>
                                @endforelse
                                @if ($o->notes)<div style="font-size:13px;margin-top:4px">📝 {{ $o->notes }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card empty">{{ __('Tiada booking pada tarikh ini.') }}</div>
            @endforelse
        </div>
        <div class="card kitchen-total" style="align-self:start">
            <h2 class="sec" style="margin-top:0">{{ __('Jumlah dapur') }}</h2>
            <table>
                <tbody>
                    @forelse ($menuTotals as $name => $qty)
                        <tr><td>{{ $name }}</td><td class="num"><b>{{ $qty }}</b></td></tr>
                    @empty
                        <tr><td class="empty">{{ __('Tiada pre-order.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
