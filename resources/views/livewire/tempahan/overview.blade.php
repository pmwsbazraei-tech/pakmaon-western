<div>
    <x-pagehead title="Tempahan Online" sub="Booking meja, pre-order dan pesanan dari landing page. Jualan dikira dari bayaran yang disahkan gateway sahaja." />

    <div class="card" style="margin-bottom:14px">
        <div class="row">
            <div><label>Dari</label><input type="date" wire:model.live="from"></div>
            <div><label>Hingga</label><input type="date" wire:model.live="to"></div>
            <div><label>Outlet</label><select wire:model.live="outlet"><option value="">Semua</option>@foreach ($outlets as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div><label>Landing page</label><select wire:model.live="form"><option value="">Semua</option>@foreach ($forms as $f)<option value="{{ $f->id }}">{{ $f->title }}</option>@endforeach</select></div>
        </div>
    </div>

    <div class="grid g4">
        <div class="card kpi"><div class="lbl">Booking</div><div class="val">{{ $stats['bookings'] }}</div></div>
        <div class="card kpi"><div class="lbl">Pesanan produk</div><div class="val">{{ $stats['purchases'] }}</div></div>
        <div class="card kpi"><div class="lbl">Bayaran diterima</div><div class="val">{{ $stats['paid'] }}</div></div>
        <div class="card kpi"><div class="lbl">Jualan disahkan</div><div class="val">@sen($stats['sales'])</div></div>
    </div>

    <div class="split" style="margin-top:16px">
        <div class="card">
            <h2 class="sec" style="margin-top:0">Perlu tindakan <span class="badge warn">{{ $needsAction->count() }}</span></h2>
            @forelse ($needsAction as $o)
                <a href="{{ route('tempahan.orders.show', $o) }}" wire:navigate style="display:block;padding:11px 0;border-bottom:1px solid var(--line)">
                    <div style="display:flex;justify-content:space-between;gap:8px"><b>{{ $o->reference }}</b><span class="hint" style="margin:0">{{ $o->outlet?->name }}</span></div>
                    <div style="font-size:13px">{{ $o->customer_name }} · {{ $o->booking_at ? $o->booking_at->format('j M, g:i A').' · '.$o->guests.' tetamu' : 'Pesanan produk' }}</div>
                    <div style="margin-top:5px">@include('livewire.tempahan._status', ['o' => $o])</div>
                </a>
            @empty
                <div class="empty">Tiada rekod yang perlu tindakan.</div>
            @endforelse
        </div>
        <div class="card">
            <h2 class="sec" style="margin-top:0">Booking 7 hari akan datang</h2>
            @forelse ($upcoming as $o)
                <a href="{{ route('tempahan.orders.show', $o) }}" wire:navigate style="display:block;padding:11px 0;border-bottom:1px solid var(--line)">
                    <div style="display:flex;justify-content:space-between;gap:8px"><b>{{ $o->booking_at->translatedFormat('D j M, g:i A') }}</b><span class="hint" style="margin:0">{{ $o->guests }} tetamu</span></div>
                    <div style="font-size:13px">{{ $o->customer_name }} · {{ $o->outlet?->name }} · {{ $o->reference }}</div>
                </a>
            @empty
                <div class="empty">Tiada booking akan datang.</div>
            @endforelse
        </div>
    </div>
</div>
