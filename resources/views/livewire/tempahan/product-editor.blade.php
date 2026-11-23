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
            <div style="margin-top:12px">
                <label class="chk"><input type="checkbox" wire:model="p.is_hidden"> {{ __('Sembunyikan produk') }}</label>
            </div>
        </div>

        @if (count($variantIds) === 0)
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
            <div class="hint" style="margin-top:0;margin-bottom:10px">{!! __('Tick variant dari :link. Harga variant ditetapkan di sana.', ['link' => '<a href="'.e(route('tempahan.variants')).'" wire:navigate>'.e(__('senarai Variant')).'</a>']) !!}</div>
            @error('variantIds.*')<div class="auth-error">{{ $message }}</div>@enderror
            @if ($library->isEmpty())
                <div class="empty">{{ __('Belum ada variant.') }} <a href="{{ route('tempahan.variants') }}" wire:navigate>+ {{ __('Tambah variant') }}</a></div>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px">
                    @foreach ($library as $v)
                        <label class="chk" wire:key="lv-{{ $v->id }}" style="justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--panel2);margin:0">
                            <span><input type="checkbox" value="{{ $v->id }}" wire:model.live="variantIds"> {{ $v->name }}</span>
                            <span class="hint" style="margin:0;white-space:nowrap">@sen($v->promo_price_cents ?? $v->price_cents)</span>
                        </label>
                    @endforeach
                </div>
                @if (count($variantIds))
                    <p class="hint">{{ __('Produk ini dijual ikut variant yang ditick; harga produk diabaikan.') }}</p>
                @endif
            @endif
        </div>

        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Ketersediaan outlet') }}</h2>
            <div class="hint" style="margin-top:0;margin-bottom:8px">{{ __('Tanda outlet yang tidak menjual produk ini.') }}</div>
            <div style="display:flex;gap:14px;flex-wrap:wrap">@foreach ($outlets as $b)<label class="chk"><input type="checkbox" value="{{ $b->id }}" wire:model="unavailable"> {{ $b->name }}</label>@endforeach</div>
        </div>

        <div><button class="btn" type="submit" wire:loading.attr="disabled">{{ __('Simpan produk') }}</button></div>
    </form>
</div>
