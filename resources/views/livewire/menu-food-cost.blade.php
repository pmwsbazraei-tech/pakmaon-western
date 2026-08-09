<div>
    <x-pagehead title="Menu Food Cost" sub="Kos bahan setiap menu vs harga jual — tengok menu mana untung" />

    <div class="grid g3">
        <div class="card kpi"><div class="lbl">Bilangan Menu</div><div class="val">{{ $menus->count() }}</div></div>
        <div class="card kpi"><div class="lbl">Purata Margin Kasar (GP)</div><div class="val">{{ number_format($avgGp, 1) }}%</div></div>
        <div class="card kpi"><div class="lbl">Sasaran Food Cost</div><div class="val">≤ 35%</div><div class="sub">standard industri F&B</div></div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="row">
            <div style="flex:2"><label>Nama Menu</label><input wire:model="newName" placeholder="cth: Lamb Chop"></div>
            <div><label>Harga Jual (RM)</label><input wire:model="newPrice" type="number" step="0.01" placeholder="0.00"></div>
            <div style="flex:0"><button class="btn" wire:click="addMenu">+ Tambah Menu</button></div>
        </div>
        @error('newName') <div class="auth-error">{{ $message }}</div> @enderror
        @error('newPrice') <div class="auth-error">{{ $message }}</div> @enderror
        <div class="hint">Klik "Resepi" pada mana-mana menu untuk masuk kos bahan satu-satu.</div>
    </div>

    <div class="card" style="margin-top:14px">
        @if ($menus->isEmpty())
            <div class="empty">Belum ada menu.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>Menu</th><th class="num">Harga Jual</th><th class="num">Kos Bahan</th>
                        <th class="num">Food Cost %</th><th class="num">Untung/Unit</th><th class="num">GP %</th><th></th><th></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($menus as $m)
                            @php
                                $stats = \App\Services\OutletMetrics::menuStats($m);
                                $fc = $stats['foodCostPct'];
                            @endphp
                            <tr wire:key="menu-{{ $m->id }}">
                                <td><input wire:model.blur="editMenu.{{ $m->id }}.name" style="min-width:130px"></td>
                                <td class="num"><input type="number" step="0.01" class="miniedit" wire:model.blur="editMenu.{{ $m->id }}.price"></td>
                                <td class="num">@rm($stats['cost'])</td>
                                <td class="num">
                                    {{ number_format($fc, 1) }}%
                                    @if ($fc > 0)
                                        @if ($fc <= $okThreshold)<span class="badge ok">Baik</span>
                                        @elseif ($fc <= $warnThreshold)<span class="badge warn">Tinggi</span>
                                        @else<span class="badge bad">Rugi</span>@endif
                                    @endif
                                </td>
                                <td class="num {{ $stats['gp'] >= 0 ? 'pos' : 'neg' }}">@rm($stats['gp'])</td>
                                <td class="num" style="font-weight:600">{{ number_format($stats['gpPct'], 1) }}%</td>
                                <td><button class="btn sec sm" wire:click="toggleRecipe({{ $m->id }})">Resepi</button></td>
                                <td><span class="del" wire:click="deleteMenu({{ $m->id }})">✕</span></td>
                            </tr>

                            @if ($openMenu === $m->id)
                                <tr wire:key="recipe-{{ $m->id }}">
                                    <td colspan="8" style="background:var(--panel2);border-radius:0 0 10px 10px">
                                        <div style="padding:6px 4px">
                                            <b style="font-size:12px">Resepi {{ $m->name }} — kos bahan setiap hidangan</b>
                                            <table style="margin-top:6px">
                                                @foreach ($m->ingredients as $ig)
                                                    <tr wire:key="ing-{{ $ig->id }}">
                                                        <td><input wire:model.blur="editIng.{{ $ig->id }}.name" style="min-width:140px"></td>
                                                        <td class="num"><input type="number" step="0.01" class="miniedit" wire:model.blur="editIng.{{ $ig->id }}.cost"></td>
                                                        <td style="width:30px"><span class="del" wire:click="deleteIngredient({{ $ig->id }})">✕</span></td>
                                                    </tr>
                                                @endforeach
                                                <tr>
                                                    <td><input wire:model="ingName.{{ $m->id }}" placeholder="+ bahan baharu"></td>
                                                    <td class="num"><input type="number" step="0.01" class="miniedit" wire:model="ingCost.{{ $m->id }}" placeholder="0.00"></td>
                                                    <td><button class="btn sm" wire:click="addIngredient({{ $m->id }})">+</button></td>
                                                </tr>
                                                <tr style="border-top:1px solid var(--line)">
                                                    <td style="font-weight:700">Jumlah Kos Bahan</td>
                                                    <td class="num" style="font-weight:700">@rm($stats['cost'])</td>
                                                    <td></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
