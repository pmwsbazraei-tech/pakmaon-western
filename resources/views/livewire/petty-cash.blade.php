<div>
    <x-pagehead title="Petty Cash / Tutup Harian" sub="Kira duit laci hujung hari — kesan lebih/kurang (kebocoran)" :withMonth="true" />

    <div class="grid g3">
        <div class="card kpi"><div class="lbl">Jualan Tunai Bulan Ini</div><div class="val">@rm($totIn)</div></div>
        <div class="card kpi"><div class="lbl">Hari Direkod</div><div class="val">{{ $rows->count() }}</div></div>
        <div class="card kpi">
            <div class="lbl">Jumlah Beza (Variance)</div>
            <div class="val {{ $totDiff < 0 ? 'neg' : 'pos' }}">@rm($totDiff)</div>
            <div class="sub">{{ $totDiff < 0 ? 'kurang — periksa kebocoran' : 'lebih/seimbang' }}</div>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="row">
            <div><label>Tarikh</label><input type="date" wire:model="newDate"></div>
            <div><label>Float Awal (RM)</label><input type="number" wire:model="newFloat" step="0.01" placeholder="0.00"></div>
            <div><label>Jualan Tunai (RM)</label><input type="number" wire:model="newCashSales" step="0.01" placeholder="0.00"></div>
            <div><label>Bayaran Tunai (RM)</label><input type="number" wire:model="newCashPayments" step="0.01" placeholder="0.00"></div>
            <div><label>Kiraan Sebenar Laci (RM)</label><input type="number" wire:model="newActual" step="0.01" placeholder="0.00"></div>
            <div style="flex:0"><button class="btn" wire:click="add">+ Rekod</button></div>
        </div>
        @error('newDate') <div class="auth-error">{{ $message }}</div> @enderror
        <div class="hint">Sepatutnya = Float + Jualan Tunai − Bayaran Tunai. Beza negatif bermakna duit laci kurang dari sepatutnya.</div>
    </div>

    <div class="card" style="margin-top:14px">
        @if ($rows->isEmpty())
            <div class="empty">Belum ada rekod tutup harian bulan ini.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>Tarikh</th><th class="num">Float</th><th class="num">Jualan Tunai</th>
                        <th class="num">Bayaran Tunai</th><th class="num">Sepatutnya</th>
                        <th class="num">Sebenar</th><th class="num">Beza</th><th></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            @php $d = \App\Services\OutletMetrics::cashDiff($r); @endphp
                            <tr wire:key="cash-{{ $r->id }}">
                                <td>{{ $r->date->format('Y-m-d') }}</td>
                                <td class="num">@rm($r->float_start)</td>
                                <td class="num">@rm($r->cash_sales)</td>
                                <td class="num">@rm($r->cash_payments)</td>
                                <td class="num">@rm(\App\Services\OutletMetrics::cashExpected($r))</td>
                                <td class="num">@rm($r->actual_count)</td>
                                <td class="num {{ $d < 0 ? 'neg' : ($d > 0 ? 'pos' : '') }}" style="font-weight:600">@rm($d)</td>
                                <td><span class="del" wire:click="delete({{ $r->id }})">✕</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
