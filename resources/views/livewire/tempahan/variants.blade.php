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
                <div x-data="{ showProducts: false }">
                <header class="sortgroup-head">
                    <span class="drag-handle group-handle" title="{{ __('Seret untuk susun') }}" aria-label="{{ __('Seret :name', ['name' => $g->name]) }}">⠿</span>
                    <button type="button" class="fold" x-on:click="toggle()" x-bind:aria-expanded="open">
                        <span class="chev" x-bind:class="{ 'shut': !open }">▾</span>
                        <b>{{ $g->name }}</b>
                        <span class="hint" style="margin:0;white-space:nowrap">{{ __(':count pilihan', ['count' => $g->activeVariants->count()]) }}</span>
                    </button>
                    <div class="actions">
                        <button type="button" class="btn sec sm" x-on:click="showProducts = ! showProducts" x-bind:aria-expanded="showProducts">
                            {{ __('Lihat produk (:count)', ['count' => $g->products_count]) }}
                        </button>
                        <button type="button" class="btn sec sm" wire:click="editGroup({{ $g->id }})">{{ __('Namakan semula') }}</button>
                        <button type="button" class="btn danger sm"
                                x-on:click="confirmTwice(@js($g->products_count ? __('Padam group :name? Ia akan dibuang dari :count produk.', ['name' => $g->name, 'count' => $g->products_count]) : __('Padam group :name?', ['name' => $g->name]))) && $wire.deleteGroup({{ $g->id }})">{{ __('Padam') }}</button>
                    </div>
                </header>

                {{-- Produk yang menanda group ini --}}
                <div class="linked-products" x-show="showProducts" x-collapse.duration.150ms x-cloak>
                    @if ($g->products->isEmpty())
                        <span class="hint" style="margin:0">{{ __('Belum ada produk menggunakan group ini. Tick group ini di editor produk.') }}</span>
                    @else
                        <b style="font-size:13px">{{ __('Produk yang menggunakan :name:', ['name' => $g->name]) }}</b>
                        <ul>
                            @foreach ($g->products as $p)
                                <li>
                                    <a href="{{ route('tempahan.products.edit', $p) }}" wire:navigate>{{ $p->name }}</a>
                                    <span class="hint" style="margin:0">· {{ $p->category?->name ?? __('Tanpa kategori') }}@if ($p->is_hidden) · {{ __('tersembunyi') }}@endif</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                </div>

                <div x-show="open" x-collapse.duration.150ms>
                    {{-- Peraturan pilihan untuk pelanggan --}}
                    @php $optCount = $g->activeVariants->count(); @endphp
                    <div class="group-rules">
                        <label>
                            <span>{{ __('Jenis') }}</span>
                            <select x-on:change="$wire.setRequired({{ $g->id }}, $event.target.value === '1')">
                                <option value="1" @selected($g->is_required)>{{ __('Required (wajib pilih)') }}</option>
                                <option value="0" @selected(! $g->is_required)>{{ __('Optional (boleh langkau)') }}</option>
                            </select>
                        </label>
                        {{-- Optional: pelanggan boleh pilih semua pilihan, jadi maksimum tidak perlu ditetapkan --}}
                        <label @if (! $g->is_required) style="display:none" @endif>
                            <span>{{ __('Maksimum pilihan') }}</span>
                            <select x-on:change="$wire.setMax({{ $g->id }}, +$event.target.value)" @disabled($optCount <= 1)>
                                @for ($n = 1; $n <= max(1, $optCount); $n++)
                                    <option value="{{ $n }}" @selected($g->effectiveMax($optCount) === $n)>{{ $n === 1 ? __('Pilih 1 sahaja') : __('Sehingga :n', ['n' => $n]) }}{{ $n === $optCount && $n > 1 ? ' ('.__('semua').')' : '' }}</option>
                                @endfor
                            </select>
                        </label>
                        <span class="badge {{ $g->is_required ? 'warn' : '' }}">{{ $g->ruleLabel($optCount) }}</span>
                    </div>
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
                                    <button type="button" class="btn danger sm" x-on:click="confirmTwice(@js(__('Padam pilihan :name?', ['name' => $v->name]))) && $wire.deleteOption({{ $g->id }}, {{ $v->id }})">{{ __('Padam') }}</button>
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
