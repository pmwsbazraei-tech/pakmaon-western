<div>
    <x-pagehead title="Kos Tetap Bulanan" sub="Sewa, WiFi, langganan & liabiliti tetap (gaji diurus di modul Staff)" />

    <div class="grid g3">
        <div class="card kpi">
            <div class="lbl">Kos Tetap Bukan Gaji</div>
            <div class="val">@rm($total)</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Kos Staff (auto)</div>
            <div class="val">@rm($staffCost)</div>
            <div class="sub">dari modul Staff</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Jumlah Kos Tetap / Bulan</div>
            <div class="val">@rm($fixedTotal)</div>
        </div>
    </div>

    <div style="height:14px"></div>

    <div class="card" style="margin-top:14px">
        <div class="row">
            <div style="flex:2"><label>Perkara</label><input wire:model="newName" placeholder="cth: Gaji Cook"></div>
            <div><label>Kumpulan</label>
                <select wire:model="newGroup">
                    @foreach ($addGroups as $g)<option>{{ $g }}</option>@endforeach
                </select>
            </div>
            <div><label>Amaun (RM)</label><input wire:model="newAmount" type="number" step="0.01" placeholder="0.00"></div>
            <div style="flex:0"><button class="btn" wire:click="add">+ Tambah</button></div>
        </div>
        @error('newName') <div class="auth-error">{{ $message }}</div> @enderror
        @error('newAmount') <div class="auth-error">{{ $message }}</div> @enderror
    </div>

    <div class="card" style="margin-top:14px">
        @if ($items->isEmpty())
            <div class="empty">Belum ada kos tetap.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Perkara</th><th>Kumpulan</th><th class="num">Amaun</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($items as $f)
                            <tr wire:key="fixed-{{ $f->id }}">
                                <td><input wire:model.blur="edit.{{ $f->id }}.name" style="min-width:150px"></td>
                                <td><select wire:model.live="edit.{{ $f->id }}.group" style="min-width:120px">
                                    @foreach ($editGroups as $g)<option>{{ $g }}</option>@endforeach
                                </select></td>
                                <td class="num"><input type="number" step="0.01" class="miniedit" wire:model.blur="edit.{{ $f->id }}.amount"></td>
                                <td><span class="del" wire:click="delete({{ $f->id }})">✕</span></td>
                            </tr>
                        @endforeach
                        <tr style="border-top:2px solid var(--line)">
                            <td colspan="2" style="font-weight:700">JUMLAH (BUKAN GAJI)</td>
                            <td class="num" style="font-weight:800">@rm($total)</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
