<div>
    <div class="pagehead">
        <div><h1>Produk & Stok</h1><p>Katalog jualan online. Berasingan daripada Menu Food Cost, tetapi boleh dipautkan.</p></div>
        <a class="btn" href="{{ route('tempahan.products.create') }}" wire:navigate>+ Produk baharu</a>
    </div>
    <div class="card" style="margin-bottom:14px"><label>Cari nama / SKU</label><input wire:model.live.debounce.300ms="q"></div>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Produk</th><th>Kategori</th><th>SKU</th><th class="num">Harga</th><th class="num">Stok</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr wire:key="p-{{ $p->id }}">
                            <td><a href="{{ route('tempahan.products.edit', $p) }}" wire:navigate style="font-weight:700;text-decoration:underline">{{ $p->name }}</a>
                                @if ($p->has_variants)<div class="hint" style="margin:0">@foreach ($p->variants->where('is_active', true) as $v){{ $v->name }} · @sen($v->effectivePrice()) · stok {{ $v->stock ?? '∞' }}<br>@endforeach</div>@endif</td>
                            <td>{{ $p->category?->name }}</td>
                            <td>{{ $p->sku }}</td>
                            <td class="num">@if ($p->has_variants) Ikut variasi @else @sen($p->effectivePrice()) @endif</td>
                            <td class="num">{{ $p->has_variants ? '-' : ($p->stock ?? '∞') }}</td>
                            <td>@if ($p->is_hidden)<span class="badge">Tersembunyi</span>@elseif ($p->isSoldOut())<span class="badge bad">Habis stok</span>@else<span class="badge ok">Aktif</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Tiada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
