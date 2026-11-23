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

    @if ($groups->isEmpty())
        <div class="card"><div class="empty">{{ __('Belum ada variant group. Cipta group di atas, tambah pilihan, kemudian tick group pada produk di Produk & Stok.') }}</div></div>
    @else
        <div class="sortbar">
            <p class="hint" style="margin:0">{{ __('Seret ⠿ untuk susun group dan pilihan.') }}</p>
            <div class="foldall">
                <button type="button" class="btn sec sm" x-on:click="$dispatch('pmw-fold-all', true)">{{ __('Buka semua') }}</button>
                <button type="button" class="btn sec sm" x-on:click="$dispatch('pmw-fold-all', false)">{{ __('Tutup semua') }}</button>
            </div>
        </div>
    @endif

    <div class="sortgroups" data-handle=".group-handle" x-data="sortable('sortGroups')">
        @foreach ($groups as $g)
            <section class="sortgroup" wire:key="g-{{ $g->id }}" data-sort-id="{{ $g->id }}"
                     x-data="collapsible('vg-{{ $g->id }}')" x-on:pmw-fold-all.window="open = $event.detail">
                <header class="sortgroup-head">
                    <span class="drag-handle group-handle" title="{{ __('Seret untuk susun') }}" aria-label="{{ __('Seret :name', ['name' => $g->name]) }}">⠿</span>
                    <button type="button" class="fold" x-on:click="toggle()" x-bind:aria-expanded="open">
                        <span class="chev" x-bind:class="{ 'shut': !open }">▾</span>
                        <b>{{ $g->name }}</b>
                        <span class="hint" style="margin:0">{{ __(':options pilihan · digunakan oleh :count produk', ['options' => $g->activeVariants->count(), 'count' => $g->products_count]) }}</span>
                    </button>
                    <div class="actions">
                        <button type="button" class="btn sec sm" wire:click="editGroup({{ $g->id }})">{{ __('Namakan semula') }}</button>
                        <button type="button" class="btn danger sm" wire:click="deleteGroup({{ $g->id }})"
                                wire:confirm="{{ $g->products_count ? __('Padam group :name? Ia akan dibuang dari :count produk.', ['name' => $g->name, 'count' => $g->products_count]) : __('Padam group :name?', ['name' => $g->name]) }}">{{ __('Padam') }}</button>
                    </div>
                </header>

                <div x-show="open" x-collapse.duration.150ms>
                    <div class="sortrows" x-data="sortable('sortOptions', {{ $g->id }})">
                        @forelse ($g->activeVariants as $v)
                            <div class="sortrow opt-row" wire:key="o-{{ $v->id }}" data-sort-id="{{ $v->id }}" @if ($optId === $v->id) style="background:var(--panel2)" @endif>
                                <span class="drag-handle" title="{{ __('Seret untuk susun') }}" aria-label="{{ __('Seret :name', ['name' => $v->name]) }}">⠿</span>
                                <div class="sr-main"><b>{{ $v->name }}</b>@if ($v->sku)<span class="hint" style="margin:0"> · {{ $v->sku }}</span>@endif</div>
                                <div class="sr-price">
                                    @if ($v->promo_price_cents !== null)
                                        @sen($v->promo_price_cents) <s class="hint" style="margin:0">@sen($v->price_cents)</s>
                                    @else
                                        @sen($v->price_cents)
                                    @endif
                                </div>
                                <div class="sr-actions">
                                    <button type="button" class="btn sec sm" wire:click="openOption({{ $g->id }}, {{ $v->id }})">{{ __('Sunting') }}</button>
                                    <button type="button" class="btn danger sm" wire:click="deleteOption({{ $g->id }}, {{ $v->id }})" wire:confirm="{{ __('Padam pilihan :name?', ['name' => $v->name]) }}">{{ __('Padam') }}</button>
                                </div>
                            </div>
                        @empty
                            <div class="sortrow empty-row"><span class="hint" style="margin:0">{{ __('Belum ada pilihan dalam group ini.') }}</span></div>
                        @endforelse
                    </div>

                    <div style="padding:10px 12px;border-top:1px solid var(--line)">
                        @if ($optGroupId === $g->id)
                            <form wire:submit="saveOption" style="background:var(--panel2);border-radius:var(--radius-sm);padding:12px">
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
                            <button type="button" class="btn sec sm" wire:click="openOption({{ $g->id }})">+ {{ __('Tambah pilihan') }}</button>
                        @endif
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</div>
