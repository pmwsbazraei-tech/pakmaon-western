<div>
    <div class="pagehead">
        <div><h1>{{ __('Produk & Stok') }}</h1><p>{{ __('Katalog jualan online. Berasingan daripada Menu Food Cost, tetapi boleh dipautkan.') }}</p></div>
        <a class="btn" href="{{ route('tempahan.products.create') }}" wire:navigate>+ {{ __('Produk baharu') }}</a>
    </div>
    <div class="card" style="margin-bottom:14px"><label>{{ __('Cari nama / SKU') }}</label><input wire:model.live.debounce.300ms="q"></div>
    <div class="card">
        <p class="hint" style="margin-top:0">{{ __('Guna ▲ ▼ untuk susun kategori dan produk. Susunan yang sama dipaparkan kepada pelanggan di landing page.') }}</p>
        <div class="table-wrap">
            <table class="tbl-sortable">
                <thead><tr><th class="sort-col"><span class="sr-only">{{ __('Susun') }}</span></th><th>{{ __('Produk') }}</th><th>SKU</th><th class="num">{{ __('Harga') }}</th><th class="num">{{ __('Stok') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($groups as $g)
                        @php $cat = $g['category']; $ci = $cat ? $categoryIds->search($cat->id) : null; @endphp
                        <tr class="cat-row" wire:key="c-{{ $cat?->id ?? 'none' }}">
                            <td class="sort-col">
                                @if ($cat)
                                    <div class="reorder">
                                        <button type="button" class="btn sec sm" wire:click="moveCategory({{ $cat->id }}, -1)" @disabled($ci === 0) aria-label="{{ __('Alih kategori :name ke atas', ['name' => $cat->name]) }}">▲</button>
                                        <button type="button" class="btn sec sm" wire:click="moveCategory({{ $cat->id }}, 1)" @disabled($ci === $categoryIds->count() - 1) aria-label="{{ __('Alih kategori :name ke bawah', ['name' => $cat->name]) }}">▼</button>
                                    </div>
                                @endif
                            </td>
                            <td colspan="5"><b>{{ $cat?->name ?? __('Tanpa kategori') }}</b> <span class="hint" style="margin:0">· {{ __(':count produk', ['count' => $g['products']->count()]) }}</span></td>
                        </tr>
                        @foreach ($g['products'] as $p)
                            <tr wire:key="p-{{ $p->id }}">
                                <td class="sort-col">
                                    <div class="reorder">
                                        <button type="button" class="btn sec sm" wire:click="moveProduct({{ $p->id }}, -1)" @disabled($loop->first) aria-label="{{ __('Alih :name ke atas', ['name' => $p->name]) }}">▲</button>
                                        <button type="button" class="btn sec sm" wire:click="moveProduct({{ $p->id }}, 1)" @disabled($loop->last) aria-label="{{ __('Alih :name ke bawah', ['name' => $p->name]) }}">▼</button>
                                    </div>
                                </td>
                                <td><a href="{{ route('tempahan.products.edit', $p) }}" wire:navigate style="font-weight:700;text-decoration:underline">{{ $p->name }}</a>
                                    @if ($p->has_variants)<div class="hint" style="margin:0">@foreach ($p->variants->where('is_active', true) as $v){{ $v->name }} · @sen($v->effectivePrice()) · {{ __('stok') }} {{ $v->stock ?? '∞' }}<br>@endforeach</div>@endif</td>
                                <td>{{ $p->sku }}</td>
                                <td class="num">@if ($p->has_variants) {{ __('Ikut variasi') }} @else @sen($p->effectivePrice()) @endif</td>
                                <td class="num">{{ $p->has_variants ? '-' : ($p->stock ?? '∞') }}</td>
                                <td>@if ($p->is_hidden)<span class="badge">{{ __('Tersembunyi') }}</span>@elseif ($p->isSoldOut())<span class="badge bad">{{ __('Habis stok') }}</span>@else<span class="badge ok">{{ __('Aktif') }}</span>@endif</td>
                            </tr>
                        @endforeach
                        @if ($g['products']->isEmpty())
                            <tr wire:key="c-empty-{{ $cat?->id }}"><td></td><td colspan="5" class="hint">{{ __('Tiada produk dalam kategori ini.') }}</td></tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="empty">{{ __('Tiada produk.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
