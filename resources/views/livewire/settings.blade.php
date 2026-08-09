<div>
    <x-pagehead title="Tetapan" sub="Nama outlet & pengurusan data" />

    <div class="card" style="max-width:520px">
        <label>Nama Outlet</label>
        <input wire:model="name">
        @error('name') <div class="auth-error">{{ $message }}</div> @enderror

        <div class="row" style="margin-top:12px">
            <div>
                <label>Caruman EPF majikan (%)</label>
                <input wire:model="epf" type="number" step="0.01">
                @error('epf') <div class="auth-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Caruman SOCSO majikan (%)</label>
                <input wire:model="socso" type="number" step="0.01">
                @error('socso') <div class="auth-error">{{ $message }}</div> @enderror
            </div>
        </div>
        <button class="btn" wire:click="save" style="margin-top:12px">Simpan</button>
    </div>

    <div class="card" style="margin-top:14px;max-width:520px">
        <h2 class="sec" style="margin-top:0">Data</h2>
        <div class="row">
            <button class="btn sec sm" wire:click="export">⬇ Export data (JSON)</button>
            <label class="btn sec sm" style="cursor:pointer;margin:0;color:inherit;text-transform:none;letter-spacing:normal">
                ⬆ Import data
                <input type="file" wire:model="importFile" accept=".json" class="hidden">
            </label>
            <button class="btn sec sm" style="color:var(--red)" wire:click="resetData"
                    wire:confirm="Padam semua data dan mula semula?">↺ Reset semua</button>
        </div>

        @error('importFile') <div class="auth-error">{{ $message }}</div> @enderror

        <div wire:loading wire:target="importFile" class="hint">Memuat naik fail…</div>

        @if ($importFile)
            <div class="row" style="margin-top:10px">
                <button class="btn sm" wire:click="import">Sahkan Import</button>
                <button class="btn sec sm" wire:click="$set('importFile', null)">Batal</button>
            </div>
        @endif

        <div class="hint">Data disimpan dalam pangkalan data. Export untuk backup atau pindah peranti. Format serasi dengan prototaip asal.</div>
    </div>
</div>
