@php use App\Models\Order; use App\Support\Phone; @endphp
<div>
    <div class="pagehead">
        <div>
            <a href="{{ route('tempahan.orders') }}" wire:navigate class="hint" style="text-decoration:underline">← Semua pesanan</a>
            <h1 style="margin-top:4px">{{ $order->reference }}</h1>
            <div style="margin-top:6px">@include('livewire.tempahan._status', ['o' => $order])</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn sec" href="https://wa.me/{{ $order->customer_phone }}" target="_blank" rel="noopener">WhatsApp pelanggan</a>
            <a class="btn sec" href="{{ $order->publicUrl() }}" target="_blank" rel="noopener">Pautan invois pelanggan</a>
        </div>
    </div>

    <div class="od-split">
        <div style="display:grid;gap:14px;align-content:start">
            <div class="card">
                <h2 class="sec" style="margin-top:0">Butiran <span class="hint" style="font-weight:400">· {{ $order->form->title }}</span></h2>
                <dl class="kv">
                    <dt>Pelanggan</dt><dd>{{ $order->customer_name }}</dd>
                    @if ($order->customer_order_no)<dt>No. order</dt><dd><b>{{ $order->customer_order_no }}</b></dd>@endif
                    <dt>Telefon</dt><dd>{{ Phone::display($order->customer_phone) }}</dd>
                    @if ($order->customer_email)<dt>Emel</dt><dd>{{ $order->customer_email }}</dd>@endif
                    <dt>Outlet</dt><dd>{{ $order->outlet?->name }}</dd>
                    @if ($order->isBooking())
                        <dt>Waktu ketibaan</dt><dd><b>{{ $order->booking_at->translatedFormat('l, j F Y') }}, {{ $order->booking_at->format('g:i A') }}</b></dd>
                        <dt>Tetamu</dt><dd>{{ $order->guests }} orang</dd>
                    @else
                        <dt>Cara terima</dt><dd>{{ Order::FULFILLMENTS[$order->fulfillment] }}</dd>
                        @if ($order->delivery_address)<dt>Alamat</dt><dd>{{ $order->delivery_address }}</dd>@endif
                    @endif
                    <dt>Nota</dt><dd>{{ $order->notes ?: '-' }}</dd>
                    <dt>WhatsApp</dt><dd>{{ $order->whatsapp_opened_at ? 'Pelanggan membuka pautan pada '.$order->whatsapp_opened_at->format('j/n/Y g:i A').' (mesej belum tentu dihantar)' : 'Belum dibuka' }}</dd>
                </dl>
            </div>

            <div class="card">
                <h2 class="sec" style="margin-top:0">{{ $order->isBooking() ? 'Pre-order menu' : 'Item' }}</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Item</th><th class="num">Unit</th><th class="num">Qty</th><th class="num">Jumlah</th></tr></thead>
                        <tbody>
                            @forelse ($order->items as $i)
                                <tr><td>{{ $i->product_name }}@if ($i->variant_name)<div class="hint" style="margin:0">{{ $i->variant_name }} @if ($i->sku)· {{ $i->sku }}@endif</div>@endif</td>
                                    <td class="num">@sen($i->unit_price_cents)</td><td class="num">{{ $i->quantity }}</td><td class="num">@sen($i->line_total_cents)</td></tr>
                            @empty
                                <tr><td colspan="4" class="empty">Booking meja sahaja.</td></tr>
                            @endforelse
                            <tr><td colspan="3">Subtotal</td><td class="num">@sen($order->subtotal_cents)</td></tr>
                            @if ($order->discount_cents)<tr><td colspan="3">Diskaun</td><td class="num">-@sen($order->discount_cents)</td></tr>@endif
                            @if ($order->delivery_fee_cents)<tr><td colspan="3">Penghantaran</td><td class="num">@sen($order->delivery_fee_cents)</td></tr>@endif
                            @if ($order->sst_added_cents)<tr><td colspan="3">SST ditambah</td><td class="num">@sen($order->sst_added_cents)</td></tr>@endif
                            @if ($order->rounding_cents)<tr><td colspan="3">Rounding</td><td class="num">{{ \App\Support\Money::format($order->rounding_cents) }}</td></tr>@endif
                            <tr><td colspan="3"><b>Jumlah</b></td><td class="num"><b>@sen($order->total_cents)</b></td></tr>
                        </tbody>
                    </table>
                </div>
                @if ($order->prices_include_sst)<div class="hint">Harga termasuk SST {{ rtrim(rtrim($order->sst_rate, '0'), '.') }}% (@sen($order->sst_included_cents)). Tiada SST ditambah.</div>@endif
            </div>

            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                    <h2 class="sec" style="margin:0">Bayaran</h2>
                    @if ($order->payments->where('status', 'created')->count())
                        <button class="btn sec sm" wire:click="syncPayment">Semak dengan gateway</button>
                    @endif
                </div>
                <div class="table-wrap" style="margin-top:8px">
                    <table>
                        <thead><tr><th>Gateway</th><th>ID</th><th class="num">Amaun</th><th>Status</th><th>Masa</th></tr></thead>
                        <tbody>
                            @forelse ($order->payments as $p)
                                <tr><td>{{ strtoupper($p->provider) }}</td><td>{{ $p->provider_ref }}</td><td class="num">@sen($p->amount_cents)</td><td>{{ $p->status }}</td><td>{{ ($p->paid_at ?? $p->created_at)->format('j/n g:i A') }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="empty">Tiada percubaan bayaran online.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="display:grid;gap:14px;align-content:start">
            @if ($order->isBooking())
                <div class="card">
                    <h2 class="sec" style="margin-top:0">Status booking</h2>
                    @if ($order->booking_status !== 'cancelled')
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            @if ($order->booking_status !== 'confirmed')<button class="btn" wire:click="setStatus('booking_status', 'confirmed')">Sahkan</button>@endif
                            @if ($order->booking_status !== 'completed')<button class="btn sec" wire:click="setStatus('booking_status', 'completed')">Selesai</button>@endif
                            @if ($order->booking_status !== 'no_show')<button class="btn sec" wire:click="setStatus('booking_status', 'no_show')">Tidak hadir</button>@endif
                        </div>
                        <h2 class="sec">Jadual semula</h2>
                        <div class="row">
                            <div><label>Tarikh</label><input type="date" wire:model="newDate"></div>
                            <div><label>Masa</label><input type="time" step="900" wire:model="newTime"></div>
                            <div><label>Tetamu</label><input type="number" min="1" wire:model="newGuests"></div>
                        </div>
                        @error('newTime')<div class="auth-error">{{ $message }}</div>@enderror
                        <button class="btn sec" style="margin-top:10px" wire:click="reschedule">Simpan jadual baharu</button>
                        <h2 class="sec" style="color:var(--red)">Batalkan booking</h2>
                        <input wire:model="cancelNote" placeholder="Sebab pembatalan (pilihan)">
                        <button class="btn danger" style="margin-top:10px" wire:click="setStatus('booking_status', 'cancelled')" wire:confirm="Batalkan booking ini? Stok dan slot akan dilepaskan.">Batalkan</button>
                        @if ($order->isPaid())<div class="hint">Bayaran telah diterima. Refund perlu dibuat secara manual di portal CHIP.</div>@endif
                    @else
                        <div class="empty">Booking ini telah dibatalkan.</div>
                    @endif
                </div>
            @else
                <div class="card">
                    <h2 class="sec" style="margin-top:0">Status fulfillment</h2>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        @foreach (Order::FULFILLMENT_STATUSES as $k => $v)
                            <button class="btn {{ $order->fulfillment_status === $k ? '' : ($k === 'cancelled' ? 'danger' : 'sec') }} sm" wire:click="setStatus('fulfillment_status', '{{ $k }}')" @if ($k === 'cancelled') wire:confirm="Batalkan pesanan ini? Stok akan dilepaskan." @endif>{{ $v }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card">
                <h2 class="sec" style="margin-top:0">Sejarah tindakan</h2>
                <ul class="tl">
                    @foreach ($order->activities as $a)
                        <li><div>{{ $a->details ?: $a->action }}</div><div class="hint" style="margin:0">{{ $a->created_at->format('j/n/Y g:i A') }} · {{ $a->user?->name ?? 'Sistem/pelanggan' }}</div></li>
                    @endforeach
                </ul>
            </div>

            <div class="card">
                <h2 class="sec" style="margin-top:0">Mesej WhatsApp</h2>
                <pre style="white-space:pre-wrap;font-family:inherit;font-size:13px">{{ $message }}</pre>
            </div>

            @if (auth()->user()->canManageShop())
                <div class="card">
                    <h2 class="sec" style="margin-top:0">Padam pesanan</h2>
                    @if ($order->payments->contains('status', 'paid'))
                        <p class="hint" style="margin:0">Pesanan ini sudah dibayar online, jadi tidak boleh dipadam. Batalkan dan buat refund jika perlu.</p>
                    @else
                        <p class="hint" style="margin-top:0">Buang rekod ini secara kekal (contoh: pesanan ujian atau spam). Stok akan dipulangkan.</p>
                        <button class="btn danger sm" wire:click="deleteOrder"
                                wire:confirm="Padam pesanan {{ $order->reference }} secara kekal? Tindakan ini tak boleh dibatalkan.">Padam pesanan</button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
