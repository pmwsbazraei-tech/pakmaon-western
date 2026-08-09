<div>
    <x-pagehead title="Bajet Buka Outlet" sub="Modal permulaan (Capex) untuk buka outlet" />

    <div class="card kpi" style="max-width:340px">
        <div class="lbl">Jumlah Modal Buka Diperlukan</div>
        <div class="val">@rm($total)</div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="row">
            <div style="flex:2"><label>Perkara</label><input wire:model="newName" placeholder="cth: Peralatan dapur"></div>
            <div><label>Kos (RM)</label><input wire:model="newAmount" type="number" step="0.01" placeholder="0.00"></div>
            <div style="flex:0"><button class="btn" wire:click="add">+ Tambah</button></div>
        </div>
        @error('newName') <div class="auth-error">{{ $message }}</div> @enderror
        @error('newAmount') <div class="auth-error">{{ $message }}</div> @enderror
    </div>

    <div class="card" style="margin-top:14px">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Perkara</th><th class="num">Kos</th><th></th></tr></thead>
                <tbody>
                    @foreach ($items as $c)
                        <tr wire:key="capex-{{ $c->id }}">
                            <td>{{ $c->name }}</td>
                            <td class="num"><input type="number" step="0.01" class="miniedit" wire:model.blur="edit.{{ $c->id }}.amount"></td>
                            <td><span class="del" wire:click="delete({{ $c->id }})">✕</span></td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="font-weight:700">JUMLAH MODAL</td>
                        <td class="num" style="font-weight:800">@rm($total)</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
