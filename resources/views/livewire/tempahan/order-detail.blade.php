@php use App\Models\Order; use App\Support\Phone; @endphp
<div>
    <div class="pagehead">
        <div>
            <a href="{{ route('tempahan.orders') }}" wire:navigate class="hint" style="text-decoration:underline">← {{ __('Semua pesanan') }}</a>
            <h1 style="margin-top:4px">{{ $order->reference }}</h1>
            <div style="margin-top:6px">@include('livewire.tempahan._status', ['o' => $order])</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn sec" href="https://wa.me/{{ $order->customer_phone }}" target="_blank" rel="noopener">{{ __('WhatsApp pelanggan') }}</a>
            <a class="btn sec" href="{{ $order->publicUrl() }}" target="_blank" rel="noopener">{{ __('Pautan invois pelanggan') }}</a>
        </div>
    </div>

    <div class="od-split">
        <div style="display:grid;gap:14px;align-content:start">
            <div class="card">
                <h2 class="sec" style="margin-top:0">{{ __('Butiran') }} <span class="hint" style="font-weight:400">· {{ $order->form->title }}</span></h2>
                <dl class="kv">
                    <dt>{{ __('Pelanggan') }}</dt><dd>{{ $order->customer_name }}</dd>
                    @if ($order->customer_order_no)<dt>{{ __('No. order') }}</dt><dd><b>{{ $order->customer_order_no }}</b></dd>@endif
                    <dt>{{ __('Telefon') }}</dt><dd>{{ Phone::display($order->customer_phone) }}</dd>
                    @if ($order->customer_email)<dt>{{ __('Emel') }}</dt><dd>{{ $order->customer_email }}</dd>@endif
                    <dt>{{ __('Outlet') }}</dt><dd>{{ $order->outlet?->name }}</dd>
                    @if ($order->isBooking())
                        <dt>{{ __('Waktu ketibaan') }}</dt><dd><b>{{ $order->booking_at->translatedFormat('l, j F Y') }}, {{ $order->booking_at->format('g:i A') }}</b></dd>
                        <dt>{{ __('Tetamu') }}</dt><dd>{{ __(':count orang', ['count' => $order->guests]) }}</dd>
                    @else
                        <dt>{{ __('Cara terima') }}</dt><dd>{{ __(Order::FULFILLMENTS[$order->fulfillment]) }}</dd>
                        @if ($order->delivery_address)<dt>{{ __('Alamat') }}</dt><dd>{{ $order->delivery_address }}</dd>@endif
                    @endif
                    <dt>{{ __('Nota') }}</dt><dd>{{ $order->notes ?: '-' }}</dd>
                    <dt>WhatsApp</dt><dd>{{ $order->whatsapp_opened_at ? __('Pelanggan membuka pautan pada :time (mesej belum tentu dihantar)', ['time' => $order->whatsapp_opened_at->format('j/n/Y g:i A')]) : __('Belum dibuka') }}</dd>
                </dl>
            </div>

            <div class="card">
                <h2 class="sec" style="margin-top:0">{{ $order->isBooking() ? __('Pre-order menu') : __('Item') }}</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>{{ __('Item') }}</th><th class="num">{{ __('Unit') }}</th><th class="num">{{ __('Qty') }}</th><th class="num">{{ __('Jumlah') }}</th></tr></thead>
                        <tbody>
                            @forelse ($order->items as $i)
                                <tr><td>{{ $i->product_name }}@if ($i->variant_name)<div class="hint" style="margin:0">{{ $i->variant_name }} @if ($i->sku)· {{ $i->sku }}@endif</div>@endif</td>
                                    <td class="num">@sen($i->unit_price_cents)</td><td class="num">{{ $i->quantity }}</td><td class="num">@sen($i->line_total_cents)</td></tr>
                            @empty
                                <tr><td colspan="4" class="empty">{{ __('Booking meja sahaja.') }}</td></tr>
                            @endforelse
                            <tr><td colspan="3">{{ __('Subtotal') }}</td><td class="num">@sen($order->subtotal_cents)</td></tr>
                            @if ($order->discount_cents)<tr><td colspan="3">{{ __('Diskaun') }}</td><td class="num">-@sen($order->discount_cents)</td></tr>@endif
                            @if ($order->delivery_fee_cents)<tr><td colspan="3">{{ __('Penghantaran') }}</td><td class="num">@sen($order->delivery_fee_cents)</td></tr>@endif
                            @if ($order->sst_added_cents)<tr><td colspan="3">{{ __('SST ditambah') }}</td><td class="num">@sen($order->sst_added_cents)</td></tr>@endif
                            @if ($order->rounding_cents)<tr><td colspan="3">{{ __('Rounding') }}</td><td class="num">{{ \App\Support\Money::format($order->rounding_cents) }}</td></tr>@endif
                            <tr><td colspan="3"><b>{{ __('Jumlah') }}</b></td><td class="num"><b>@sen($order->total_cents)</b></td></tr>
                        </tbody>
                    </table>
                </div>
                @if ($order->prices_include_sst)<div class="hint">{{ __('Harga termasuk SST :rate% (:amount). Tiada SST ditambah.', ['rate' => rtrim(rtrim($order->sst_rate, '0'), '.'), 'amount' => \App\Support\Money::format($order->sst_included_cents)]) }}</div>@endif
            </div>

            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                    <h2 class="sec" style="margin:0">{{ __('Bayaran') }}</h2>
                    @if ($order->payments->where('status', 'created')->count())
                        <button class="btn sec sm" wire:click="syncPayment">{{ __('Semak dengan gateway') }}</button>
                    @endif
                </div>
                <div class="table-wrap" style="margin-top:8px">
                    <table>
                        <thead><tr><th>{{ __('Gateway') }}</th><th>ID</th><th class="num">{{ __('Amaun') }}</th><th>{{ __('Status') }}</th><th>{{ __('Masa') }}</th></tr></thead>
                        <tbody>
                            @forelse ($order->payments as $p)
                                <tr><td>{{ strtoupper($p->provider) }}</td><td>{{ $p->provider_ref }}</td><td class="num">@sen($p->amount_cents)</td><td>{{ $p->status }}</td><td>{{ ($p->paid_at ?? $p->created_at)->format('j/n g:i A') }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="empty">{{ __('Tiada percubaan bayaran online.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="display:grid;gap:14px;align-content:start">
            @if ($order->isBooking())
                <div class="card">
                    <h2 class="sec" style="margin-top:0">{{ __('Status booking') }}</h2>
                    @if ($order->booking_status !== 'cancelled')
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            @if ($order->booking_status !== 'confirmed')<button class="btn" wire:click="setStatus('booking_status', 'confirmed')">{{ __('Sahkan') }}</button>@endif
                            @if ($order->booking_status !== 'completed')<button class="btn sec" wire:click="setStatus('booking_status', 'completed')">{{ __('Selesai') }}</button>@endif
                            @if ($order->booking_status !== 'no_show')<button class="btn sec" wire:click="setStatus('booking_status', 'no_show')">{{ __('Tidak hadir') }}</button>@endif
                        </div>
                        <h2 class="sec">{{ __('Jadual semula') }}</h2>
                        <div class="row">
                            <div><label>{{ __('Tarikh') }}</label><input type="date" wire:model="newDate"></div>
                            <div><label>{{ __('Masa') }}</label><input type="time" step="900" wire:model="newTime"></div>
                            <div><label>{{ __('Tetamu') }}</label><input type="number" min="1" wire:model="newGuests"></div>
                        </div>
                        @error('newTime')<div class="auth-error">{{ $message }}</div>@enderror
                        <button class="btn sec" style="margin-top:10px" wire:click="reschedule">{{ __('Simpan jadual baharu') }}</button>
                        <h2 class="sec" style="color:var(--red)">{{ __('Batalkan booking') }}</h2>
                        <input wire:model="cancelNote" placeholder="{{ __('Sebab pembatalan (pilihan)') }}">
                        <button class="btn danger" style="margin-top:10px" wire:click="setStatus('booking_status', 'cancelled')" wire:confirm="{{ __('Batalkan booking ini? Stok dan slot akan dilepaskan.') }}">{{ __('Batalkan') }}</button>
                        @if ($order->isPaid())<div class="hint">{{ __('Bayaran telah diterima. Refund perlu dibuat secara manual di portal CHIP.') }}</div>@endif
                    @else
                        <div class="empty">{{ __('Booking ini telah dibatalkan.') }}</div>
                    @endif
                </div>
            @else
                <div class="card">
                    <h2 class="sec" style="margin-top:0">{{ __('Status pesanan') }}</h2>
                    <div style="display:flex;gap:8px;flex-wrap:wrap" x-data="{ dep: @js($order->fulfillment_status === 'deposit') }">
                        @foreach (Order::FULFILLMENT_STATUSES as $k => $v)
                            @if ($k === 'deposit')
                                <button class="btn {{ $order->fulfillment_status === 'deposit' ? '' : 'sec' }} sm" x-on:click="dep = true; $nextTick(() => $refs.dep?.focus())">{{ __($v) }}</button>
                            @else
                                <button class="btn {{ $order->fulfillment_status === $k ? '' : ($k === 'cancelled' ? 'danger' : 'sec') }} sm" wire:click="setStatus('fulfillment_status', '{{ $k }}')"
                                    @if ($k === 'cancelled') wire:confirm="{{ __('Batalkan pesanan ini? Stok akan dilepaskan.') }}"
                                    @elseif ($k === 'paid') wire:confirm="{{ __('Tandakan pesanan ini sudah dibayar penuh (:amount)?', ['amount' => \App\Support\Money::format($order->total_cents)]) }}" @endif>{{ __($v) }}</button>
                            @endif
                        @endforeach

                        <form x-show="dep" x-cloak wire:submit="recordDeposit" style="flex-basis:100%;display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-top:6px">
                            <div style="flex:1;min-width:140px">
                                <label for="dep-amt">{{ __('Jumlah deposit (RM)') }}</label>
                                <input id="dep-amt" x-ref="dep" type="number" step="0.01" min="0.01" inputmode="decimal" wire:model="depositAmount" placeholder="{{ __('cth. 10.00') }}">
                                @error('depositAmount')<div class="hint" style="color:var(--red);margin:4px 0 0">{{ $message }}</div>@enderror
                            </div>
                            <button class="btn sm" type="submit">{{ __('Simpan deposit') }}</button>
                        </form>
                    </div>

                    <div class="sf-totals" style="margin-top:14px;border-top:1px solid var(--line);padding-top:10px">
                        <div style="display:flex;justify-content:space-between"><span>{{ __('Jumlah') }}</span><b>@sen($order->total_cents)</b></div>
                        @if ($order->deposit_cents && ! $order->isPaid())
                            <div style="display:flex;justify-content:space-between"><span>{{ __('Deposit diterima') }}</span><span>-@sen($order->deposit_cents)</span></div>
                        @endif
                        <div style="display:flex;justify-content:space-between;font-size:1.1em"><b>{{ __('Baki') }}</b><b style="color:{{ $order->balanceCents() ? 'var(--red)' : 'var(--green)' }}">@sen($order->balanceCents())</b></div>
                    </div>
                </div>
            @endif

            <div class="card">
                <h2 class="sec" style="margin-top:0">{{ __('Sejarah tindakan') }}</h2>
                <ul class="tl">
                    @foreach ($order->activities as $a)
                        <li><div>{{ $a->details ?: $a->action }}</div><div class="hint" style="margin:0">{{ $a->created_at->format('j/n/Y g:i A') }} · {{ $a->user?->name ?? __('Sistem/pelanggan') }}</div></li>
                    @endforeach
                </ul>
            </div>

            <div class="card">
                <h2 class="sec" style="margin-top:0">{{ __('Mesej WhatsApp') }}</h2>
                <pre style="white-space:pre-wrap;font-family:inherit;font-size:13px">{{ $message }}</pre>
            </div>

            @if (auth()->user()->canManageShop())
                <div class="card">
                    <h2 class="sec" style="margin-top:0">{{ __('Padam pesanan') }}</h2>
                    @if ($order->payments->contains('status', 'paid'))
                        <p class="hint" style="margin:0">{{ __('Pesanan ini sudah dibayar online, jadi tidak boleh dipadam. Batalkan dan buat refund jika perlu.') }}</p>
                    @else
                        <p class="hint" style="margin-top:0">{{ __('Buang rekod ini secara kekal (contoh: pesanan ujian atau spam). Stok akan dipulangkan.') }}</p>
                        <button class="btn danger sm" wire:click="deleteOrder"
                                wire:confirm="{{ __('Padam pesanan :ref secara kekal? Tindakan ini tak boleh dibatalkan.', ['ref' => $order->reference]) }}">{{ __('Padam pesanan') }}</button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
