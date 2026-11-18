<div>
    <div class="pagehead">
        <div>
            <a href="{{ route('tempahan.forms') }}" wire:navigate class="hint" style="text-decoration:underline">← Semua landing page</a>
            <h1 style="margin-top:4px">{{ $form ? $form->title : 'Landing page baharu' }}</h1>
        </div>
        @if ($form)<a class="btn sec" href="{{ route('form.show', ['slug' => $form->slug, 'preview' => 1]) }}" target="_blank">Preview</a>@endif
    </div>

    <nav class="sec-tabs">
        @foreach (['overview' => 'Overview', 'options' => 'Options', 'products' => 'Products', 'purchasing' => 'Purchasing & SST', 'fulfillment' => 'Pickup/Delivery', 'slots' => 'Date & Time', 'details' => 'Customer Details', 'whatsapp' => 'WhatsApp Message', 'payment' => 'Payment Gateway', 'popup' => 'Welcome Popup', 'maintenance' => 'Maintenance'] as $id => $label)
            <a href="#{{ $id }}">{{ $label }}</a>
        @endforeach
    </nav>

    <form wire:submit="save" style="display:grid;gap:14px">
        <div class="card" id="overview">
            <h2 class="sec" style="margin-top:0">Overview</h2>
            <div class="row">
                <div style="flex:2"><label>Tajuk *</label><input wire:model="f.title">@error('f.title')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div><label>Slug URL</label><input wire:model="f.slug" placeholder="dijana dari tajuk">@error('f.slug')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div><label>Status</label><select wire:model="f.status"><option value="draft">Draf (tersembunyi)</option><option value="published">Published (awam)</option><option value="maintenance">Maintenance (tutup sementara)</option></select></div>
            </div>
            <div class="hint">URL: {{ url('/p') }}/<b>{{ $f['slug'] ?: 'slug' }}</b></div>
            <div style="margin-top:12px"><label>Penerangan</label><textarea wire:model="f.description" rows="2"></textarea></div>
            <div style="margin-top:12px"><label>URL gambar banner (pilihan)</label><input wire:model="f.banner_url" placeholder="https://">@error('f.banner_url')<div class="auth-error">{{ $message }}</div>@enderror</div>
            <div style="margin-top:12px"><label>Makluman penting (satu baris = satu makluman)</label><textarea wire:model="f.notices" rows="3"></textarea></div>
        </div>

        <div class="card" id="options">
            <h2 class="sec" style="margin-top:0">Options: tujuan page</h2>
            <label class="chk"><input type="checkbox" wire:model="f.purpose_booking"> <span><b>Booking meja & pre-order menu</b><br><span class="hint">Pelanggan pilih outlet, tarikh, masa dan bilangan tetamu.</span></span></label>
            <label class="chk" style="margin-top:8px"><input type="checkbox" wire:model="f.purpose_purchase"> <span><b>Pembelian produk biasa</b><br><span class="hint">Pickup atau delivery, bayar online.</span></span></label>
            @error('f.purpose_booking')<div class="auth-error">{{ $message }}</div>@enderror
            <div class="hint">Jika kedua-duanya aktif, pelanggan diberi pilihan. Jika satu sahaja, pelanggan terus ke aliran itu.</div>
            <label style="margin-top:14px">Outlet yang dibenarkan *</label>
            <div style="display:flex;gap:14px;flex-wrap:wrap">
                @foreach ($outlets as $b)
                    <label class="chk"><input type="checkbox" value="{{ $b->id }}" wire:model="outletIds"> {{ $b->name }} @unless ($b->accepts_orders)<span class="badge warn">belum terima tempahan</span>@endunless</label>
                @endforeach
            </div>
            @error('outletIds')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="card" id="products">
            <h2 class="sec" style="margin-top:0">Products</h2>
            <div class="hint" style="margin-top:0;margin-bottom:10px">Tanda produk yang dipaparkan. Urus harga, variasi dan stok di <a href="{{ route('tempahan.products') }}" wire:navigate style="text-decoration:underline">Produk & Stok</a>.</div>
            @forelse ($products as $p)
                <label class="chk" style="margin-bottom:6px"><input type="checkbox" value="{{ $p->id }}" wire:model="productIds"> {{ $p->name }} <span class="hint" style="margin:0">· {{ $p->category?->name ?? 'Tiada kategori' }}@if ($p->is_hidden) · tersembunyi @endif</span></label>
            @empty
                <div class="empty">Belum ada produk.</div>
            @endforelse
        </div>

        <div class="card" id="purchasing">
            <h2 class="sec" style="margin-top:0">Purchasing & SST</h2>
            <label class="chk"><input type="checkbox" wire:model="f.prices_include_sst"> <span><b>Harga paparan sudah termasuk SST</b><br><span class="hint">Checkout dan invois tidak menambah SST lagi. Contoh: RM5.30 kekal RM5.30.</span></span></label>
            <div class="row" style="margin-top:12px">
                <div><label>Kadar SST (%)</label><input type="number" step="0.01" wire:model="f.sst_rate"></div>
                <div><label>Belian minimum (RM)</label><input type="number" step="0.01" wire:model="f.min_order"></div>
                <div><label>Belian maksimum (RM)</label><input type="number" step="0.01" wire:model="f.max_order"></div>
            </div>
            <div class="hint">Kupon diskaun: Fasa 2.</div>
        </div>

        <div class="card" id="fulfillment">
            <h2 class="sec" style="margin-top:0">Pickup / Delivery (pembelian produk)</h2>
            <label class="chk"><input type="checkbox" wire:model="f.allow_pickup"> Benarkan pickup di outlet</label>
            <label class="chk" style="margin-top:8px"><input type="checkbox" wire:model="f.allow_delivery"> Benarkan delivery (alamat diminta)</label>
            @error('f.allow_pickup')<div class="auth-error">{{ $message }}</div>@enderror
            <div style="max-width:260px;margin-top:12px"><label>Caj penghantaran asas (RM)</label><input type="number" step="0.01" wire:model="f.delivery_fee"><div class="hint">Caj outlet (jika ditetapkan) mengatasi nilai ini.</div></div>
        </div>

        <div class="card" id="slots">
            <h2 class="sec" style="margin-top:0">Date & Time Slots (booking)</h2>
            <label>Pre-order menu</label>
            <select wire:model="f.booking_mode"><option value="preorder_required">Wajib pre-order sekurang-kurangnya satu menu</option><option value="table_only_allowed">Booking meja sahaja dibenarkan (cart kosong OK)</option></select>
            <div style="margin-top:12px"><label>Makluman booking</label><textarea wire:model="f.booking_notice" rows="3"></textarea></div>
            <div class="hint">Waktu operasi, cuti, tempoh slot dan kapasiti ditetapkan bagi setiap outlet di <a href="{{ route('tempahan.outlets') }}" wire:navigate style="text-decoration:underline">Tetapan Outlet</a>.</div>
        </div>

        <div class="card" id="details">
            <h2 class="sec" style="margin-top:0">Customer Details</h2>
            <div>Nama dan nombor telefon (format Malaysia) <b>wajib</b>. Emel pilihan. Pelanggan boleh pilih "Ingat maklumat saya" pada peranti sendiri.</div>
        </div>

        <div class="card" id="whatsapp">
            <h2 class="sec" style="margin-top:0">WhatsApp Message</h2>
            <label class="chk"><input type="checkbox" wire:model="f.action_whatsapp"> <span><b>Aktifkan "Hantar ke WhatsApp"</b><br><span class="hint">Dibuka ke nombor WhatsApp outlet yang dipilih pelanggan.</span></span></label>
            @error('f.action_whatsapp')<div class="auth-error">{{ $message }}</div>@enderror
            <div style="margin-top:12px"><label>Templat mesej</label><textarea wire:model="f.whatsapp_template" rows="10" style="font-family:ui-monospace,Consolas,monospace;font-size:12.5px"></textarea></div>
            <div class="hint">Pemboleh ubah: {reference} {name} {phone} {outlet} {date} {time} {guests} {items} {total} {notes} {fulfillment} {address} {payment_link}</div>
        </div>

        <div class="card" id="payment">
            <h2 class="sec" style="margin-top:0">Payment Gateway</h2>
            <label class="chk"><input type="checkbox" wire:model="f.action_online"> <span><b>Aktifkan "Bayar Online"</b> (CHIP Collect)<br><span class="hint">Gateway semasa: <b>{{ $gateway }}</b></span></span></label>
            <label class="chk" style="margin-top:8px"><input type="checkbox" wire:model="f.auto_confirm_on_payment"> <span><b>Sahkan booking automatik selepas bayaran disahkan gateway</b><br><span class="hint">Jika tidak ditanda, staf outlet sahkan secara manual. Paid dan Confirmed sentiasa direkod berasingan.</span></span></label>
        </div>

        <div class="card" id="popup">
            <h2 class="sec" style="margin-top:0">Welcome Popup</h2>
            <label>Mesej popup (kosong = tiada popup)</label><textarea wire:model="f.welcome_popup" rows="3"></textarea>
        </div>

        <div class="card" id="maintenance">
            <h2 class="sec" style="margin-top:0">Maintenance</h2>
            <label>Mesej semasa maintenance</label><textarea wire:model="f.maintenance_message" rows="2"></textarea>
            <div class="hint">Tukar Status di Overview kepada Maintenance untuk menutup page sementara.</div>
        </div>

        <div style="position:sticky;bottom:0;background:var(--bg);padding:12px 0;border-top:1px solid var(--line);display:flex;gap:12px;align-items:center">
            <button class="btn" type="submit" wire:loading.attr="disabled">Simpan</button>
            @if ($form)<span class="hint" style="margin:0">URL awam: {{ $form->publicUrl() }}</span>@endif
        </div>
    </form>
</div>
