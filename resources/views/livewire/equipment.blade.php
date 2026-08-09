<div>
    <x-pagehead title="Peralatan Buka Outlet" sub="Checklist procurement — apa dah beli, apa belum" />

    <div class="grid g3">
        <div class="card kpi"><div class="lbl">Jumlah Kos Peralatan</div><div class="val">@rm($tot)</div></div>
        <div class="card kpi"><div class="lbl">Sudah Dibeli</div><div class="val pos">@rm($beli)</div></div>
        <div class="card kpi">
            <div class="lbl">Perlu Dibeli Lagi</div>
            <div class="val neg">@rm($perlu)</div>
            <div class="bar"><span style="width:{{ $pct }}%;background:var(--green)"></span></div>
            <div class="prog">{{ number_format($pct, 0) }}% siap</div>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="row">
            <div style="flex:2"><label>Peralatan</label><input wire:model="newName" placeholder="cth: Blender"></div>
            <div style="flex:0"><label>Kumpulan</label>
                <select wire:model="newGroup">
                    <option>Dapur</option><option>Ruang Makan</option><option>Operasi</option><option>Lain-lain</option>
                </select>
            </div>
            <div style="flex:0"><label>Kuantiti</label><input wire:model="newQty" type="number" style="width:80px"></div>
            <div><label>Harga Unit (RM)</label><input wire:model="newPrice" type="number" step="0.01" placeholder="0.00"></div>
            <div style="flex:0"><button class="btn" wire:click="add">+ Tambah</button></div>
        </div>
        @error('newName') <div class="auth-error">{{ $message }}</div> @enderror
        @error('newPrice') <div class="auth-error">{{ $message }}</div> @enderror
    </div>

    <div class="card" style="margin-top:14px">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Status</th><th>Peralatan</th><th>Kumpulan</th>
                    <th class="num">Qty</th><th class="num">Harga Unit</th><th class="num">Jumlah</th><th></th>
                </tr></thead>
                <tbody>
                    @foreach ($grouped as $group => $items)
                        @foreach ($items as $e)
                            @php $on = $e->status === 'beli'; @endphp
                            <tr wire:key="eq-{{ $e->id }}">
                                <td><input type="checkbox" style="width:auto" @checked($on) wire:click="toggle({{ $e->id }})"></td>
                                <td style="{{ $on ? 'text-decoration:line-through;color:var(--muted)' : '' }}">{{ $e->name }}</td>
                                <td><span class="chip">{{ $e->group }}</span></td>
                                <td class="num">{{ $e->qty }}</td>
                                <td class="num">@rm($e->unit_price)</td>
                                <td class="num">@rm($e->subtotal())</td>
                                <td><span class="del" wire:click="delete({{ $e->id }})">✕</span></td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr style="border-top:2px solid var(--line)">
                        <td colspan="5" style="font-weight:700">JUMLAH SEMUA PERALATAN</td>
                        <td class="num" style="font-weight:800">@rm($tot)</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
