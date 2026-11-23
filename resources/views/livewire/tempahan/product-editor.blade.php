<div>
    <div class="pagehead">
        <div>
            <a href="{{ route('tempahan.products') }}" wire:navigate class="hint" style="text-decoration:underline">← {{ __('Semua produk') }}</a>
            <h1 style="margin-top:4px">{{ $product ? $product->name : __('Produk baharu') }}</h1>
        </div>
    </div>

    <form wire:submit="save" style="display:grid;gap:14px">
        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Maklumat') }}</h2>
            <div class="row">
                <div style="flex:2"><label>{{ __('Nama') }} *</label><input wire:model="p.name">@error('p.name')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div><label>SKU</label><input wire:model="p.sku">@error('p.sku')<div class="auth-error">{{ $message }}</div>@enderror</div>
            </div>
            <div class="row" style="margin-top:12px">
                <div><label>{{ __('Kategori') }}</label><select wire:model="p.product_category_id"><option value="">{{ __('Tiada') }}</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div><label>{{ __('Atau kategori baharu') }}</label><input wire:model="p.new_category"></div>
                <div><label>{{ __('Pautan Menu Food Cost (pilihan)') }}</label><select wire:model="p.menu_id"><option value="">{{ __('Tiada') }}</option>@foreach ($menus as $m)<option value="{{ $m->id }}">{{ $m->name }} ({{ $m->outlet->name }})</option>@endforeach</select></div>
            </div>
            <div style="margin-top:12px"><label>{{ __('Penerangan') }}</label><textarea wire:model="p.description" rows="2"></textarea></div>
            <div style="margin-top:12px">
                <label>{{ __('Gambar produk') }}</label>
                @php $preview = $photo && method_exists($photo, 'temporaryUrl') && ! $errors->has('photo') ? $photo->temporaryUrl() : ($p['image_url'] ?: null); @endphp
                <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
                    <div style="width:96px;height:96px;border-radius:var(--radius-sm);border:1px solid var(--line);background:var(--panel2);overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        @if ($preview)
                            <img src="{{ $preview }}" alt="" style="width:100%;height:100%;object-fit:cover">
                        @else
                            <span class="hint" style="margin:0">{{ __('Tiada gambar') }}</span>
                        @endif
                    </div>
                    <div style="flex:1;min-width:220px">
                        <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp">
                        <div wire:loading wire:target="photo" class="hint">{{ __('Memuat naik…') }}</div>
                        <p class="hint" style="margin:4px 0 0">{{ __('JPG, PNG atau WEBP, maksimum 4MB. Gambar dipaparkan kepada pelanggan selepas anda simpan.') }}</p>
                        @if ($preview)
                            <button type="button" class="btn danger sm" style="margin-top:6px" wire:click="removePhoto">{{ __('Buang gambar') }}</button>
                        @endif
                        @error('photo')<div class="auth-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <details style="margin-top:8px" @if ($p['image_url'] && ! str_starts_with($p['image_url'], \App\Models\Product::UPLOAD_PREFIX)) open @endif>
                    <summary class="hint" style="cursor:pointer">{{ __('Atau guna URL gambar') }}</summary>
                    <input wire:model.blur="p.image_url" placeholder="https://" style="margin-top:6px">
                </details>
                @error('p.image_url')<div class="auth-error">{{ $message }}</div>@enderror
            </div>
            <div class="row" style="margin-top:12px">
                <div><label>{{ __('Susunan') }}</label><input type="number" wire:model="p.sort"></div>
                <div><label>{{ __('Had belian / pesanan') }}</label><input type="number" min="1" wire:model="p.max_per_order" placeholder="{{ __('Tiada had') }}"></div>
                <div><label class="chk" style="margin-top:24px"><input type="checkbox" wire:model="p.is_hidden"> {{ __('Sembunyikan produk') }}</label></div>
            </div>
        </div>

        @if (count($variants) === 0)
            <div class="card">
                <h2 class="sec" style="margin-top:0">{{ __('Harga & stok (tanpa variasi)') }}</h2>
                <div class="row">
                    <div><label>{{ __('Harga (RM)') }} *</label><input type="number" step="0.01" min="0" wire:model="p.price">@error('p.price')<div class="auth-error">{{ $message }}</div>@enderror</div>
                    <div><label>{{ __('Harga promosi (RM)') }}</label><input type="number" step="0.01" min="0" wire:model="p.promo_price"></div>
                    <div><label>{{ __('Stok') }}</label><input type="number" min="0" wire:model="p.stock" placeholder="{{ __('Kosong = tiada had') }}"></div>
                </div>
            </div>
        @endif

        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Variasi') }}</h2>
            <div class="hint" style="margin-top:0;margin-bottom:10px">{{ __('Setiap variasi ada harga, SKU, stok dan had belian sendiri. Kosongkan stok untuk tiada had.') }}</div>
            @error('variants')<div class="auth-error">{{ $message }}</div>@enderror
            @foreach ($variants as $i => $v)
                <div wire:key="v-{{ $i }}" style="background:var(--panel2);border-radius:var(--radius-sm);padding:12px;margin-bottom:10px">
                    <div class="row">
                        <div style="flex:2"><label>{{ __('Nama') }}</label><input wire:model="variants.{{ $i }}.name"></div>
                        <div><label>SKU</label><input wire:model="variants.{{ $i }}.sku"></div>
                        <div><label>{{ __('Harga (RM)') }}</label><input type="number" step="0.01" min="0" wire:model="variants.{{ $i }}.price"></div>
                    </div>
                    <div class="row" style="margin-top:8px">
                        <div><label>{{ __('Promosi (RM)') }}</label><input type="number" step="0.01" min="0" wire:model="variants.{{ $i }}.promo_price"></div>
                        <div><label>{{ __('Stok') }}</label><input type="number" min="0" wire:model="variants.{{ $i }}.stock"></div>
                        <div><label>{{ __('Had / pesanan') }}</label><input type="number" min="1" wire:model="variants.{{ $i }}.max_per_order"></div>
                        <div style="flex:0"><span class="del" wire:click="removeVariant({{ $i }})" title="{{ __('Buang variasi') }}">✕</span></div>
                    </div>
                </div>
            @endforeach
            <button type="button" class="btn sec sm" wire:click="addVariant">+ {{ __('Tambah variasi') }}</button>
        </div>

        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Ketersediaan outlet') }}</h2>
            <div class="hint" style="margin-top:0;margin-bottom:8px">{{ __('Tanda outlet yang tidak menjual produk ini.') }}</div>
            <div style="display:flex;gap:14px;flex-wrap:wrap">@foreach ($outlets as $b)<label class="chk"><input type="checkbox" value="{{ $b->id }}" wire:model="unavailable"> {{ $b->name }}</label>@endforeach</div>
        </div>

        <div><button class="btn" type="submit" wire:loading.attr="disabled">{{ __('Simpan produk') }}</button></div>
    </form>
</div>
