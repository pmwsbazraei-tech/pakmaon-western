<div>
    <x-pagehead title="Staff" sub="Urus pekerja & kos gaji — klik terus untuk edit nama, posisi & gaji" />

    <div class="grid g4">
        <div class="card kpi">
            <div class="lbl">Bilangan Staff</div>
            <div class="val">{{ $staff->count() }}</div>
            <div class="sub">{{ $nFT }} sepenuh masa · {{ $nPT }} separuh masa</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Jumlah Gaji Pokok</div>
            <div class="val">@rm($totalGaji)</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Jumlah Kos Majikan/Bulan</div>
            <div class="val">@rm($totalKos)</div>
            <div class="sub">+EPF {{ rtrim(rtrim(number_format($epf, 2), '0'), '.') }}% +SOCSO {{ rtrim(rtrim(number_format($socso, 2), '0'), '.') }}%</div>
        </div>
        <div class="card kpi">
            <div class="lbl">Purata Kos / Staff</div>
            <div class="val">@rm($avgKos)</div>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="row">
            <div style="flex:2"><label>Nama</label><input wire:model="newName" placeholder="cth: Ali"></div>
            <div><label>Posisi</label><input wire:model="newPosition" placeholder="cth: Cook"></div>
            <div style="flex:0"><label>Jenis</label>
                <select wire:model="newType"><option>Sepenuh Masa</option><option>Separuh Masa</option></select>
            </div>
            <div><label>Gaji Pokok (RM)</label><input wire:model="newSalary" type="number" step="0.01" placeholder="0.00"></div>
            <div style="flex:0"><label>EPF/SOCSO</label>
                <select wire:model="newEpfSocso"><option value="1">Ya</option><option value="0">Tidak</option></select>
            </div>
            <div style="flex:0"><button class="btn" wire:click="add">+ Tambah</button></div>
        </div>
        @error('newName') <div class="auth-error">{{ $message }}</div> @enderror
        @error('newSalary') <div class="auth-error">{{ $message }}</div> @enderror
        <div class="hint">Kos majikan = gaji + EPF {{ rtrim(rtrim(number_format($epf, 2), '0'), '.') }}% + SOCSO {{ rtrim(rtrim(number_format($socso, 2), '0'), '.') }}% (jika diaktifkan). Jumlah ini masuk automatik ke Kos Tetap & PnL. Untuk staff separuh masa, letak gaji bulanan anggaran & pilih EPF/SOCSO = Tidak jika tidak berkaitan.</div>
    </div>

    <div class="card" style="margin-top:14px">
        @if ($staff->isEmpty())
            <div class="empty">Belum ada staff.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>Nama</th><th>Posisi</th><th>Jenis</th><th class="num">Gaji Pokok</th>
                        <th>EPF/SOCSO</th><th class="num">Kos Majikan</th><th></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($staff as $s)
                            <tr wire:key="staff-{{ $s->id }}">
                                <td><input wire:model.blur="edit.{{ $s->id }}.name" style="min-width:110px"></td>
                                <td><input wire:model.blur="edit.{{ $s->id }}.position" style="min-width:100px"></td>
                                <td><select wire:model.live="edit.{{ $s->id }}.type" style="min-width:130px">
                                    <option>Sepenuh Masa</option><option>Separuh Masa</option>
                                </select></td>
                                <td class="num"><input type="number" step="0.01" class="miniedit" wire:model.blur="edit.{{ $s->id }}.salary"></td>
                                <td><select wire:model.live="edit.{{ $s->id }}.epf_socso">
                                    <option value="1">Ya</option><option value="0">Tidak</option>
                                </select></td>
                                <td class="num" style="font-weight:600">@rm(\App\Services\OutletMetrics::staffTotal($s, $epf, $socso))</td>
                                <td><span class="del" wire:click="delete({{ $s->id }})">✕</span></td>
                            </tr>
                        @endforeach
                        <tr style="border-top:2px solid var(--line)">
                            <td colspan="5" style="font-weight:700">JUMLAH KOS STAFF / BULAN</td>
                            <td class="num" style="font-weight:800">@rm($totalKos)</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
