<div>
    <x-pagehead title="Jualan (Sales)" sub="Masukkan jualan harian — auto jumlah bulanan" :withMonth="true" />

    <div class="grid g3">
        <div class="card kpi"><div class="lbl">Jualan Bulan Ini</div><div class="val">@rm($sales)</div></div>
        <div class="card kpi"><div class="lbl">Purata Harian</div><div class="val">@rm($avg)</div></div>
        <div class="card kpi"><div class="lbl">Hari Direkod</div><div class="val">{{ $days->count() }}</div></div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="row">
            <div><label>Tarikh</label><input type="date" wire:model="newDate"></div>
            <div><label>Jualan (RM)</label><input type="number" wire:model="newAmount" step="0.01" placeholder="0.00"></div>
            <div style="flex:0"><button class="btn" wire:click="add">+ Rekod Jualan</button></div>
        </div>
        @error('newDate') <div class="auth-error">{{ $message }}</div> @enderror
        @error('newAmount') <div class="auth-error">{{ $message }}</div> @enderror
        <div class="hint">Tip: masuk jualan setiap hari tutup kedai untuk PnL tepat.</div>
    </div>

    <div class="split" style="margin-top:14px">
        <div class="card">
            @if ($days->isEmpty())
                <div class="empty">Belum ada jualan direkod.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tarikh</th><th class="num">Jualan</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($days as $s)
                                <tr wire:key="sale-{{ $s->id }}">
                                    <td>{{ $s->date->format('Y-m-d') }}</td>
                                    <td class="num">@rm($s->amount)</td>
                                    <td><span class="del" wire:click="delete({{ $s->id }})">✕</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <div class="card">
            <h2 class="sec" style="margin-top:0">Trend Jualan Harian</h2>
            <x-chart :config="$chartConfig" />
        </div>
    </div>
</div>
