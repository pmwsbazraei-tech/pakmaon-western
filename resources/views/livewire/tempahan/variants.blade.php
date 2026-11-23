<div>
    <div class="pagehead">
        <div><h1>{{ __('Variant') }}</h1><p>{{ __('Cipta variant group (cth. Chicken) dengan pilihan dan harga masing-masing. Di produk, tick group sahaja.') }}</p></div>
    </div>

    <form class="card" wire:submit="saveGroup" style="margin-bottom:14px">
        <h2 class="sec" style="margin-top:0">{{ $groupId ? __('Namakan semula group') : __('Group baharu') }}</h2>
        <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
            <div style="flex:1;min-width:220px">
                <input wire:model="groupName" placeholder="{{ __('Contoh: Chicken, Lamb, Air') }}" aria-label="{{ __('Nama group') }}">
                @error('groupName')<div class="auth-error">{{ $message }}</div>@enderror
            </div>
            <button class="btn" type="submit">{{ $groupId ? __('Simpan') : '+ '.__('Tambah group') }}</button>
            @if ($groupId)<button class="btn sec" type="button" wire:click="cancelGroup">{{ __('Batal') }}</button>@endif
        </div>
    </form>

    @forelse ($groups as $g)
        <div class="card" wire:key="g-{{ $g->id }}" style="margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="reorder">
                        <button type="button" class="btn sec sm" wire:click="moveGroup({{ $g->id }}, -1)" @disabled($loop->first) aria-label="{{ __('Alih :name ke atas', ['name' => $g->name]) }}">▲</button>
                        <button type="button" class="btn sec sm" wire:click="moveGroup({{ $g->id }}, 1)" @disabled($loop->last) aria-label="{{ __('Alih :name ke bawah', ['name' => $g->name]) }}">▼</button>
                    </div>
                    <div>
                        <h2 class="sec" style="margin:0;border:0;padding:0">{{ $g->name }}</h2>
                        <span class="hint" style="margin:0">{{ __(':options pilihan · digunakan oleh :count produk', ['options' => $g->activeVariants->count(), 'count' => $g->products_count]) }}</span>
                    </div>
                </div>
                <div style="display:flex;gap:6px">
                    <button type="button" class="btn sec sm" wire:click="editGroup({{ $g->id }})">{{ __('Namakan semula') }}</button>
                    <button type="button" class="btn danger sm" wire:click="deleteGroup({{ $g->id }})"
                            wire:confirm="{{ $g->products_count ? __('Padam group :name? Ia akan dibuang dari :count produk.', ['name' => $g->name, 'count' => $g->products_count]) : __('Padam group :name?', ['name' => $g->name]) }}">{{ __('Padam') }}</button>
                </div>
            </div>

            @if ($g->activeVariants->isNotEmpty())
                <div class="table-wrap" style="margin-top:12px">
                    <table class="tbl-sortable">
                        <thead><tr><th class="sort-col"><span class="sr-only">{{ __('Susun') }}</span></th><th>{{ __('Pilihan') }}</th><th>SKU</th><th class="num">{{ __('Harga') }}</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($g->activeVariants as $v)
                                <tr wire:key="o-{{ $v->id }}" @if ($optId === $v->id) style="background:var(--panel2)" @endif>
                                    <td class="sort-col">
                                        <div class="reorder">
                                            <button type="button" class="btn sec sm" wire:click="moveOption({{ $g->id }}, {{ $v->id }}, -1)" @disabled($loop->first) aria-label="{{ __('Alih :name ke atas', ['name' => $v->name]) }}">▲</button>
                                            <button type="button" class="btn sec sm" wire:click="moveOption({{ $g->id }}, {{ $v->id }}, 1)" @disabled($loop->last) aria-label="{{ __('Alih :name ke bawah', ['name' => $v->name]) }}">▼</button>
                                        </div>
                                    </td>
                                    <td><b>{{ $v->name }}</b></td>
                                    <td>{{ $v->sku ?? '—' }}</td>
                                    <td class="num">
                                        @if ($v->promo_price_cents !== null)
                                            @sen($v->promo_price_cents)<br><s class="hint" style="margin:0">@sen($v->price_cents)</s>
                                        @else
                                            @sen($v->price_cents)
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap;text-align:right">
                                        <button type="button" class="btn sec sm" wire:click="openOption({{ $g->id }}, {{ $v->id }})">{{ __('Sunting') }}</button>
                                        <button type="button" class="btn danger sm" wire:click="deleteOption({{ $g->id }}, {{ $v->id }})" wire:confirm="{{ __('Padam pilihan :name?', ['name' => $v->name]) }}">{{ __('Padam') }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="hint">{{ __('Belum ada pilihan dalam group ini.') }}</p>
            @endif

            @if ($optGroupId === $g->id)
                <form wire:submit="saveOption" style="background:var(--panel2);border-radius:var(--radius-sm);padding:12px;margin-top:12px">
                    <b>{{ $optId ? __('Sunting pilihan') : __('Tambah pilihan ke :name', ['name' => $g->name]) }}</b>
                    <div class="row" style="margin-top:8px">
                        <div style="flex:2"><label>{{ __('Nama') }} *</label><input wire:model="o.name" placeholder="{{ __('Contoh: :name 1', ['name' => $g->name]) }}">@error('o.name')<div class="auth-error">{{ $message }}</div>@enderror</div>
                        <div><label>SKU</label><input wire:model="o.sku">@error('o.sku')<div class="auth-error">{{ $message }}</div>@enderror</div>
                        <div><label>{{ __('Harga (RM)') }} *</label><input type="number" step="0.01" min="0" wire:model="o.price">@error('o.price')<div class="auth-error">{{ $message }}</div>@enderror</div>
                        <div><label>{{ __('Harga promosi (RM)') }}</label><input type="number" step="0.01" min="0" wire:model="o.promo_price">@error('o.promo_price')<div class="auth-error">{{ $message }}</div>@enderror</div>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                        <button class="btn sm" type="submit">{{ $optId ? __('Simpan perubahan') : '+ '.__('Tambah pilihan') }}</button>
                        <button class="btn sec sm" type="button" wire:click="cancelOption">{{ __('Tutup') }}</button>
                    </div>
                </form>
            @else
                <button type="button" class="btn sec sm" style="margin-top:12px" wire:click="openOption({{ $g->id }})">+ {{ __('Tambah pilihan') }}</button>
            @endif
        </div>
    @empty
        <div class="card"><div class="empty">{{ __('Belum ada variant group. Cipta group di atas, tambah pilihan, kemudian tick group pada produk di Produk & Stok.') }}</div></div>
    @endforelse
</div>
