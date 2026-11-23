<div>
    <div class="pagehead">
        <div><h1>{{ __('Produk & Stok') }}</h1><p>{{ __('Katalog jualan online. Berasingan daripada Menu Food Cost, tetapi boleh dipautkan.') }}</p></div>
        <a class="btn" href="{{ route('tempahan.products.create') }}" wire:navigate>+ {{ __('Produk baharu') }}</a>
    </div>
    <div class="card" style="margin-bottom:14px"><label>{{ __('Cari nama / SKU') }}</label><input wire:model.live.debounce.300ms="q"></div>

    @php $canSort = trim($q) === ''; @endphp
    <div class="sortbar">
        <p class="hint" style="margin:0">
            @if ($canSort)
                {{ __('Seret ⠿ untuk susun kategori dan produk. Susunan yang sama dipaparkan kepada pelanggan di landing page.') }}
            @else
                {{ __('Kosongkan carian untuk menyusun semula.') }}
            @endif
        </p>
        <div class="foldall">
            <button type="button" class="btn sec sm" x-on:click="$dispatch('pmw-fold-all', true)">{{ __('Buka semua') }}</button>
            <button type="button" class="btn sec sm" x-on:click="$dispatch('pmw-fold-all', false)">{{ __('Tutup semua') }}</button>
        </div>
    </div>

    @if ($groups->isEmpty())
        <div class="card"><div class="empty">{{ __('Tiada produk.') }}</div></div>
    @endif

    {{-- Kategori (boleh diseret); "Tanpa kategori" sentiasa di hujung --}}
    <div class="sortgroups" data-handle=".group-handle" @if ($canSort) x-data="sortable('sortCategories')" @endif>
        @foreach ($groups as $g)
            @php $cat = $g['category']; $key = 'cat-'.($cat?->id ?? 'none'); @endphp
            <section class="sortgroup" wire:key="{{ $key }}" @if ($cat) data-sort-id="{{ $cat->id }}" @endif
                     x-data="collapsible('{{ $key }}')" x-on:pmw-fold-all.window="open = $event.detail">
                <header class="sortgroup-head">
                    @if ($canSort && $cat)
                        <span class="drag-handle group-handle" title="{{ __('Seret untuk susun') }}" aria-label="{{ __('Seret :name', ['name' => $cat->name]) }}">⠿</span>
                    @else
                        <span class="drag-handle off" aria-hidden="true"></span>
                    @endif
                    <button type="button" class="fold" x-on:click="toggle()" x-bind:aria-expanded="open">
                        <span class="chev" x-bind:class="{ 'shut': !open }">▾</span>
                        <b>{{ $cat?->name ?? __('Tanpa kategori') }}</b>
                        <span class="hint" style="margin:0">{{ __(':count produk', ['count' => $g['products']->count()]) }}</span>
                    </button>
                </header>

                <div class="sortrows" x-show="open" x-collapse.duration.150ms
                     @if ($canSort) x-data="sortable('sortProducts', {{ $cat?->id ?? 0 }})" @endif>
                    @forelse ($g['products'] as $p)
                        <div class="sortrow" wire:key="p-{{ $p->id }}" data-sort-id="{{ $p->id }}">
                            @if ($canSort)
                                <span class="drag-handle" title="{{ __('Seret untuk susun') }}" aria-label="{{ __('Seret :name', ['name' => $p->name]) }}">⠿</span>
                            @else
                                <span class="drag-handle off" aria-hidden="true"></span>
                            @endif
                            <div class="sr-main">
                                <a href="{{ route('tempahan.products.edit', $p) }}" wire:navigate>{{ $p->name }}</a>
                                @if ($p->sku)<span class="hint" style="margin:0"> · {{ $p->sku }}</span>@endif
                                @if ($p->has_variants)
                                    <div class="hint" style="margin:2px 0 0">{{ $p->variants->where('is_active', true)->pluck('name')->join(', ') }}</div>
                                @endif
                            </div>
                            <div class="sr-price">@if ($p->has_variants) {{ __('Ikut variasi') }} @else @sen($p->effectivePrice()) @endif</div>
                            <div class="sr-stock" title="{{ __('Stok') }}">{{ $p->has_variants ? '–' : ($p->stock ?? '∞') }}</div>
                            <div class="sr-status">@if ($p->is_hidden)<span class="badge">{{ __('Tersembunyi') }}</span>@elseif ($p->isSoldOut())<span class="badge bad">{{ __('Habis stok') }}</span>@else<span class="badge ok">{{ __('Aktif') }}</span>@endif</div>
                        </div>
                    @empty
                        <div class="sortrow empty-row"><span class="hint" style="margin:0">{{ __('Tiada produk dalam kategori ini.') }}</span></div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
