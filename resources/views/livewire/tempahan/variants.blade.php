<div>
    <div class="pagehead">
        <div><h1>{{ __('Variant') }}</h1><p>{{ __('Senarai variant dikongsi. Tetapkan nama dan harga di sini, kemudian tick variant pada setiap produk.') }}</p></div>
    </div>

    <form class="card" wire:submit="save" style="margin-bottom:14px">
        <h2 class="sec" style="margin-top:0">{{ $editingId ? __('Sunting variant') : __('Tambah variant') }}</h2>
        <div class="row">
            <div style="flex:2"><label>{{ __('Nama') }} *</label><input wire:model="f.name" placeholder="{{ __('Contoh: Double, Extra cheese') }}">@error('f.name')<div class="auth-error">{{ $message }}</div>@enderror</div>
            <div><label>SKU</label><input wire:model="f.sku">@error('f.sku')<div class="auth-error">{{ $message }}</div>@enderror</div>
            <div><label>{{ __('Harga (RM)') }} *</label><input type="number" step="0.01" min="0" wire:model="f.price">@error('f.price')<div class="auth-error">{{ $message }}</div>@enderror</div>
            <div><label>{{ __('Harga promosi (RM)') }}</label><input type="number" step="0.01" min="0" wire:model="f.promo_price">@error('f.promo_price')<div class="auth-error">{{ $message }}</div>@enderror</div>
        </div>
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <button class="btn" type="submit">{{ $editingId ? __('Simpan perubahan') : '+ '.__('Tambah variant') }}</button>
            @if ($editingId)
                <button class="btn sec" type="button" wire:click="cancelEdit">{{ __('Batal') }}</button>
            @endif
        </div>
        @if ($editingId)
            <p class="hint">{{ __('Harga baharu terus dipakai oleh semua produk yang menggunakan variant ini.') }}</p>
        @endif
    </form>

    <div class="card">
        @if ($variants->isEmpty())
            <div class="empty">{{ __('Belum ada variant. Tambah variant di atas, kemudian tick pada produk di Produk & Stok.') }}</div>
        @else
            <div class="table-wrap">
                <table class="tbl-sortable">
                    <thead><tr><th class="sort-col"><span class="sr-only">{{ __('Susun') }}</span></th><th>{{ __('Variant') }}</th><th>SKU</th><th class="num">{{ __('Harga') }}</th><th class="num">{{ __('Produk') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($variants as $v)
                            <tr wire:key="v-{{ $v->id }}" @if ($editingId === $v->id) style="background:var(--panel2)" @endif>
                                <td class="sort-col">
                                    <div class="reorder">
                                        <button type="button" class="btn sec sm" wire:click="move({{ $v->id }}, -1)" @disabled($loop->first) aria-label="{{ __('Alih :name ke atas', ['name' => $v->name]) }}">▲</button>
                                        <button type="button" class="btn sec sm" wire:click="move({{ $v->id }}, 1)" @disabled($loop->last) aria-label="{{ __('Alih :name ke bawah', ['name' => $v->name]) }}">▼</button>
                                    </div>
                                </td>
                                <td><b>{{ $v->name }}</b></td>
                                <td>{{ $v->sku ?? '—' }}</td>
                                <td class="num">
                                    @if ($v->promo_price_cents !== null)
                                        @sen($v->promo_price_cents) <br><s class="hint" style="margin:0">@sen($v->price_cents)</s>
                                    @else
                                        @sen($v->price_cents)
                                    @endif
                                </td>
                                <td class="num">{{ $v->products_count }}</td>
                                <td style="white-space:nowrap;text-align:right">
                                    <button type="button" class="btn sec sm" wire:click="edit({{ $v->id }})">{{ __('Sunting') }}</button>
                                    <button type="button" class="btn danger sm" wire:click="delete({{ $v->id }})"
                                            wire:confirm="{{ $v->products_count ? __('Padam variant :name? Ia akan dibuang dari :count produk.', ['name' => $v->name, 'count' => $v->products_count]) : __('Padam variant :name?', ['name' => $v->name]) }}">{{ __('Padam') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
