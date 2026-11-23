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
                    <div style="flex:1;min-width:220px"
                         x-data="{
                            uploading: false, progress: 0, failed: false, lastMove: 0, timer: null,
                            start() { this.uploading = true; this.progress = 0; this.failed = false; this.lastMove = Date.now(); this.watch(); },
                            moved(p) { if (p !== this.progress) { this.progress = p; this.lastMove = Date.now(); } },
                            stop(ok) { this.uploading = false; this.failed = !ok; clearInterval(this.timer); },
                            {{-- Pelayan kadang-kadang menolak fail besar tanpa respons (cth. had 413); jangan biar bar tersangkut --}}
                            watch() {
                                clearInterval(this.timer);
                                this.timer = setInterval(() => {
                                    if (this.uploading && Date.now() - this.lastMove > 20000) { $wire.cancelUpload('photo'); this.stop(false); }
                                }, 2000);
                            },
                         }"
                         x-on:livewire-upload-start="start()"
                         x-on:livewire-upload-progress="moved($event.detail.progress)"
                         x-on:livewire-upload-finish="progress = 100; stop(true)"
                         x-on:livewire-upload-cancel="uploading = false; clearInterval(timer)"
                         x-on:livewire-upload-error="stop(false)">
                        <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" x-bind:disabled="uploading">
                        {{-- Bar kemajuan muat naik --}}
                        <div x-show="uploading" x-cloak style="margin-top:8px" role="progressbar" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="progress">
                            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
                                <span>{{ __('Memuat naik…') }}</span><b x-text="progress + '%'"></b>
                            </div>
                            <div style="height:8px;border-radius:99px;background:var(--line);overflow:hidden">
                                <div style="height:100%;background:var(--accent);border-radius:99px;transition:width .2s ease" x-bind:style="{ width: progress + '%' }"></div>
                            </div>
                        </div>
                        <div x-show="failed" x-cloak class="auth-error">{{ __('Muat naik gagal atau terhenti. Fail mungkin melebihi had saiz pelayan; cuba gambar yang lebih kecil.') }}</div>
                        <p class="hint" style="margin:4px 0 0">{{ __('JPG, PNG atau WEBP, maksimum 10MB. Gambar dipaparkan kepada pelanggan selepas anda simpan.') }}</p>
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

        @if (count($groupIds) === 0)
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
            <div class="hint" style="margin-top:0;margin-bottom:10px">{!! __('Tick variant group dari :link. Semua pilihan dalam group itu akan dipaparkan kepada pelanggan.', ['link' => '<a href="'.e(route('tempahan.variants')).'" wire:navigate>'.e(__('halaman Variant')).'</a>']) !!}</div>
            @error('groupIds.*')<div class="auth-error">{{ $message }}</div>@enderror
            @if ($library->isEmpty())
                <div class="empty">{{ __('Belum ada variant group.') }} <a href="{{ route('tempahan.variants') }}" wire:navigate>+ {{ __('Tambah group') }}</a></div>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:8px">
                    @foreach ($library as $g)
                        <label wire:key="lg-{{ $g->id }}" style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--panel2);margin:0;cursor:pointer">
                            <input type="checkbox" value="{{ $g->id }}" wire:model.live="groupIds" style="margin-top:3px;width:auto;flex:0 0 auto">
                            <span>
                                <b>{{ $g->name }}</b>
                                <span class="hint" style="margin:2px 0 0;display:block">
                                    @forelse ($g->activeVariants as $v){{ $v->name }} (@sen($v->promo_price_cents ?? $v->price_cents))@if (! $loop->last), @endif @empty{{ __('Tiada pilihan lagi') }}@endforelse
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @if (count($groupIds))
                    <p class="hint">{{ __('Produk ini dijual ikut pilihan dalam group yang ditick; harga produk diabaikan.') }}</p>
                @endif
            @endif
        </div>

        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Ketersediaan outlet') }}</h2>
            <div class="hint" style="margin-top:0;margin-bottom:8px">{{ __('Tick outlet yang menjual produk ini. Bila outlet baru ditick, produk ini terus ditick di landing page outlet tersebut (boleh di-untick di editor landing page, cth. bila habis stok).') }}</div>
            @php $allOutletIds = $outlets->pluck('id')->map(fn ($i) => (string) $i)->values(); @endphp
            <label class="chk" style="margin-bottom:8px;font-weight:700"
                   x-data="{ all: @js($allOutletIds) }">
                <input type="checkbox"
                       x-bind:checked="all.every(id => $wire.availableOutlets.includes(id))"                       x-on:change="$wire.availableOutlets = $event.target.checked ? [...all] : []">
                {{ __('Pilih semua outlet') }}
            </label>
            <div style="display:flex;gap:14px;flex-wrap:wrap;padding-left:4px">@foreach ($outlets as $b)<label class="chk"><input type="checkbox" value="{{ $b->id }}" wire:model="availableOutlets"> {{ $b->name }}</label>@endforeach</div>
            @if ($outlets->isNotEmpty())
                <p class="hint" x-show="$wire.availableOutlets.length === 0" x-cloak style="color:var(--red)">{{ __('Tiada outlet ditick: produk ini tidak dijual di mana-mana outlet.') }}</p>
            @endif
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <button class="btn" type="submit" wire:loading.attr="disabled">{{ __('Simpan produk') }}</button>
            @if ($product)
                <button class="btn danger" type="button"
                        x-on:click="confirmTwice(@js(__('Padam produk ":name"? Ia akan dibuang dari semua landing page. Rekod pesanan lama tidak terjejas.', ['name' => $product->name]))) && $wire.deleteProduct()">{{ __('Padam produk') }}</button>
            @endif
        </div>
    </form>
</div>
