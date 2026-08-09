<div>
    <x-pagehead title="Dashboard" :sub="$outlet->name.' — ringkasan prestasi bulanan'" :withMonth="true" />

    @php
        $fc = $c['foodCostPct'];
        $ok = config('outlet.food_cost.ok');
        $warn = config('outlet.food_cost.warn');
    @endphp

    <div class="grid g4">
        <div class="card kpi">
            <div class="lbl">Jualan Bulan Ini</div>
            <div class="val">@rm($c['sales'])</div>
            <div class="sub">Target @rm($outlet->target_sales)</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Jumlah Kos</div>
            <div class="val">@rm($c['expenses'])</div>
            <div class="sub">Tetap @rm($c['fixedTotal']) + Berubah @rm($c['varTotal'])</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Untung Bersih</div>
            <div class="val {{ $c['net'] >= 0 ? 'pos' : 'neg' }}">@rm($c['net'])</div>
            <div class="sub">Margin {{ number_format($c['marginPct'], 1) }}%</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Food Cost %</div>
            <div class="val">
                {{ number_format($fc, 1) }}%
                @if ($fc > 0)
                    @if ($fc <= $ok)
                        <span class="badge ok">Sihat</span>
                    @elseif ($fc <= $warn)
                        <span class="badge warn">Tinggi</span>
                    @else
                        <span class="badge bad">Bahaya</span>
                    @endif
                @endif
            </div>
            <div class="sub">COGS @rm($c['cogs'])</div>
        </div>
    </div>

    <div class="grid g3" style="margin-top:14px">
        <div class="card kpi">
            <div class="lbl">Break-even (jualan)</div>
            <div class="val">@rm($c['beSales'])</div>
            <div class="sub">≈ @rm($c['beSales'] / 30)/hari perlu tak rugi</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Modal Buka (Capex)</div>
            <div class="val">@rm($c['capex'])</div>
            <div class="sub">Balik modal: {{ is_infinite($c['roiMonths']) ? '—' : number_format($c['roiMonths'], 1).' bulan' }}</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Pencapaian Target Sales</div>
            @php $pct = $outlet->target_sales > 0 ? $c['sales'] / $outlet->target_sales * 100 : 0; @endphp
            <div class="val">{{ number_format($pct, 0) }}%</div>
            <div class="bar"><span style="width:{{ min(100, $pct) }}%"></span></div>
        </div>
    </div>

    <div class="split" style="margin-top:16px">
        <div class="card">
            <h2 class="sec" style="margin-top:0">Pecahan Kos Mengikut Kategori</h2>
            <x-chart :config="$pieConfig" />
        </div>
        <div class="card">
            <h2 class="sec" style="margin-top:0">Jualan vs Kos vs Untung (6 bulan)</h2>
            <x-chart :config="$barConfig" />
        </div>
    </div>
</div>
