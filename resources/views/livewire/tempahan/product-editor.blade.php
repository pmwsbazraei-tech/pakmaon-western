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
            <div style="margin-top:12px"><label>{{ __('URL gambar') }}</label><input wire:model="p.image_url" placeholder="https://">@error('p.image_url')<div class="auth-error">{{ $message }}</div>@enderror</div>
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
