<div class="outlet-switcher">
    <label style="padding:0 4px">Outlet aktif</label>
    <select wire:model.live="activeId" style="margin-bottom:8px">
        @foreach ($outlets as $o)
            <option value="{{ $o->id }}">{{ $o->name }}</option>
        @endforeach
    </select>
    <button class="btn sec sm" wire:click="createOutlet" style="width:100%"
            wire:confirm="Cipta outlet baharu (kosong)? Anda akan isi data sendiri.">
        + Outlet Baru
    </button>
</div>
