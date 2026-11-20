<div>
    <x-pagehead title="Dashboard" sub="Tempahan online ikut cawangan — {{ $monthLabel }}" :withMonth="true" />

    <div class="grid g4">
        <div class="card kpi"><div class="lbl">Baharu</div><div class="val">{{ $totals['new'] }}</div><div class="sub">Belum bayar</div></div>
        <div class="card kpi"><div class="lbl">Deposit</div><div class="val">{{ $totals['deposit'] }}</div><div class="sub">Deposit @sen($totals['deposit_cents'])</div></div>
        <div class="card kpi"><div class="lbl">Paid</div><div class="val" style="color:var(--green)">{{ $totals['paid'] }}</div><div class="sub">Jualan @sen($totals['sales_cents'])</div></div>
        <div class="card kpi"><div class="lbl">Dibatalkan</div><div class="val" style="color:var(--red)">{{ $totals['cancelled'] }}</div><div class="sub">Jumlah pesanan {{ $totals['total'] }}</div></div>
    </div>

    <h2 class="sec" style="margin-top:22px">Ikut cawangan</h2>
    @if ($branches->isEmpty())
        <div class="card"><div class="empty">Tiada cawangan yang menerima tempahan online.</div></div>
    @else
        <div class="grid g3">
            @foreach ($branches as $r)
                @php $s = $r['stats']; @endphp
                <a class="card" href="{{ route('tempahan.orders', ['outlet' => $r['outlet']->id]) }}" wire:navigate wire:key="b-{{ $r['outlet']->id }}" style="display:block">
                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px">
                        <h3 style="margin:0;font-family:var(--font-head);text-transform:uppercase;font-size:18px">{{ $r['outlet']->name }}</h3>
                        <span class="hint" style="margin:0;white-space:nowrap">{{ $s['total'] }} pesanan</span>
                    </div>
                    <div style="margin-top:10px;font-variant-numeric:tabular-nums">
                        @foreach ([['new', 'warn', null], ['deposit', 'warn', 'deposit_cents'], ['paid', 'ok', 'sales_cents'], ['cancelled', 'bad', null]] as [$k, $tone, $amt])
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:7px 0;border-top:1px solid var(--line)">
                                <span class="badge {{ $tone }}">{{ $buckets[$k] }}</span>
                                <span style="text-align:right"><b style="font-size:18px">{{ $s[$k] }}</b> order
                                    @if ($amt && $s[$amt])<br><span class="hint" style="margin:0">@sen($s[$amt])</span>@endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </a>
            @endforeach
        </div>
        <p class="hint">Klik kad cawangan untuk lihat senarai pesanannya.</p>
    @endif
</div>
