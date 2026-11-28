<div>
    <x-pagehead title="Dashboard" :sub="__('Tempahan online ikut cawangan — :month', ['month' => $monthLabel])" :withMonth="true" />

    {{-- Setiap kad/nombor membuka senarai pesanan bulan ini dengan status tersebut --}}
    <div class="grid g4">
        <a class="card kpi dash-link" href="{{ $link('new') }}" wire:navigate><div class="lbl">{{ __('Baharu') }}</div><div class="val">{{ $totals['new'] }}</div><div class="sub">{{ __('Belum bayar') }}</div></a>
        <a class="card kpi dash-link" href="{{ $link('deposit') }}" wire:navigate><div class="lbl">{{ __('Deposit') }}</div><div class="val">{{ $totals['deposit'] }}</div><div class="sub">{{ __('Deposit') }} @sen($totals['deposit_cents'])</div></a>
        <a class="card kpi dash-link" href="{{ $link('paid') }}" wire:navigate><div class="lbl">{{ __('Paid') }}</div><div class="val" style="color:var(--green)">{{ $totals['paid'] }}</div><div class="sub">{{ __('Jualan') }} @sen($totals['sales_cents'])</div></a>
        <a class="card kpi dash-link" href="{{ $link('cancelled') }}" wire:navigate><div class="lbl">{{ __('Dibatalkan') }}</div><div class="val" style="color:var(--red)">{{ $totals['cancelled'] }}</div><div class="sub">{{ __('Jumlah pesanan :count', ['count' => $totals['total']]) }}</div></a>
    </div>

    <h2 class="sec" style="margin-top:22px">{{ __('Ikut cawangan') }}</h2>
    @if ($branches->isEmpty())
        <div class="card"><div class="empty">{{ __('Tiada cawangan yang menerima tempahan online.') }}</div></div>
    @else
        <div class="grid g3">
            @foreach ($branches as $r)
                @php $s = $r['stats']; $oid = $r['outlet']->id; @endphp
                <div class="card" wire:key="b-{{ $oid }}">
                    <a class="dash-head" href="{{ $link(null, $oid) }}" wire:navigate>
                        <h3 style="margin:0;font-family:var(--font-head);text-transform:uppercase;font-size:18px">{{ $r['outlet']->name }}</h3>
                        <span class="hint" style="margin:0;white-space:nowrap">{{ __(':count pesanan', ['count' => $s['total']]) }} →</span>
                    </a>
                    <div style="margin-top:10px;font-variant-numeric:tabular-nums">
                        @foreach ([['new', 'warn', null], ['deposit', 'warn', 'deposit_cents'], ['paid', 'ok', 'sales_cents'], ['cancelled', 'bad', null]] as [$k, $tone, $amt])
                            <a class="dash-row" href="{{ $link($k, $oid) }}" wire:navigate>
                                <span class="badge {{ $tone }}">{{ __($buckets[$k]) }}</span>
                                <span style="text-align:right"><b style="font-size:18px">{{ $s[$k] }}</b> {{ __('order') }}
                                    @if ($amt && $s[$amt])<br><span class="hint" style="margin:0">@sen($s[$amt])</span>@endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <p class="hint">{{ __('Klik nombor untuk lihat senarai pesanan dengan status tersebut.') }}</p>
    @endif
</div>
