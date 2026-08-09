<div>
    <x-pagehead title="ROI & Target" sub="Kira pulangan modal & tetapkan sasaran" :withMonth="true" />

    <div class="grid g4">
        <div class="card kpi"><div class="lbl">Modal Buka</div><div class="val">@rm($c['capex'])</div></div>
        <div class="card kpi">
            <div class="lbl">Untung Bersih (sebenar)</div>
            <div class="val {{ $c['net'] >= 0 ? 'pos' : 'neg' }}">@rm($c['net'])</div>
        </div>
        <div class="card kpi">
            <div class="lbl">ROI Sebenar</div>
            <div class="val">{{ is_infinite($c['roiMonths']) ? '—' : number_format($c['roiMonths'], 1) }}<span style="font-size:13px"> bulan</span></div>
            <div class="sub">masa balik modal</div>
        </div>
        <div class="card kpi">
            <div class="lbl">ROI Sasaran</div>
            <div class="val">{{ is_infinite($c['targetRoi']) ? '—' : number_format($c['targetRoi'], 1) }}<span style="font-size:13px"> bulan</span></div>
            <div class="sub">jika capai target</div>
        </div>
    </div>

    <div class="split" style="margin-top:16px">
        <div class="card">
            <h2 class="sec" style="margin-top:0">Tetapkan Sasaran</h2>
            <label>Target Jualan Bulanan (RM)</label>
            <input type="number" wire:model="targetSales">
            @error('targetSales') <div class="auth-error">{{ $message }}</div> @enderror

            <label style="margin-top:10px">Target Margin Untung (%)</label>
            <input type="number" wire:model="targetMargin">
            @error('targetMargin') <div class="auth-error">{{ $message }}</div> @enderror

            <label style="margin-top:10px">Target Balik Modal (bulan)</label>
            <input type="number" wire:model="targetRoiMonths">
            @error('targetRoiMonths') <div class="auth-error">{{ $message }}</div> @enderror

            <button class="btn" wire:click="saveTargets" style="margin-top:14px;width:100%">Simpan Sasaran</button>

            <div class="hint">
                Untuk balik modal dalam <b>{{ (int) $c['targetRoiMonths'] }} bulan</b>, perlu untung bersih
                <b>@rm($c['neededNet'])</b>/bulan (≈ jualan <b>@rm($c['neededSales'])</b>/bulan pada margin {{ rtrim(rtrim(number_format($c['targetMargin'], 2), '0'), '.') }}%).
            </div>
        </div>
        <div class="card">
            <h2 class="sec" style="margin-top:0">Unjuran Balik Modal (kumulatif)</h2>
            <x-chart :config="$chartConfig" />
        </div>
    </div>
</div>
