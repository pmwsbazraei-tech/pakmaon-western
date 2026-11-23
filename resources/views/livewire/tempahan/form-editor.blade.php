<div>
    <div class="pagehead">
        <div>
            <a href="{{ route('tempahan.forms') }}" wire:navigate class="hint" style="text-decoration:underline">← {{ __('Semua landing page') }}</a>
            <h1 style="margin-top:4px">{{ $form ? $form->title : __('Landing page baharu') }}</h1>
        </div>
        @if ($form)<a class="btn sec" href="{{ route('form.show', ['slug' => $form->slug, 'preview' => 1]) }}" target="_blank">{{ __('Preview') }}</a>@endif
    </div>

    <nav class="sec-tabs">
        @foreach (['overview' => 'Overview', 'options' => 'Options', 'products' => 'Products', 'purchasing' => 'Purchasing & SST', 'fulfillment' => 'Pickup/Delivery', 'slots' => 'Date & Time', 'details' => 'Customer Details', 'whatsapp' => 'WhatsApp Message', 'payment' => 'Payment Gateway', 'popup' => 'Welcome Popup', 'maintenance' => 'Maintenance'] as $id => $label)
            <a href="#{{ $id }}" @if ($id === 'fulfillment') x-show="$wire.f.purpose_purchase && ! $wire.f.simple_form" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <form wire:submit="save" style="display:grid;gap:14px">
        <div class="card" id="overview">
            <h2 class="sec" style="margin-top:0">Overview</h2>
            <div class="row">
                <div style="flex:2"><label>{{ __('Tajuk') }} *</label><input wire:model="f.title">@error('f.title')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div><label>{{ __('Slug URL') }}</label><input wire:model="f.slug" placeholder="{{ __('dijana dari tajuk') }}">@error('f.slug')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div><label>{{ __('Status') }}</label><select wire:model="f.status"><option value="draft">{{ __('Draf (tersembunyi)') }}</option><option value="published">{{ __('Published (awam)') }}</option><option value="maintenance">{{ __('Maintenance (tutup sementara)') }}</option></select></div>
            </div>
            <div class="hint">URL: {{ url('/p') }}/<b>{{ $f['slug'] ?: 'slug' }}</b></div>
            <div style="margin-top:12px"><label>{{ __('Penerangan') }}</label><textarea wire:model="f.description" rows="2"></textarea></div>
            <div style="margin-top:12px"><label>{{ __('URL gambar banner (pilihan)') }}</label><input wire:model="f.banner_url" placeholder="https://">@error('f.banner_url')<div class="auth-error">{{ $message }}</div>@enderror</div>
            <div style="margin-top:12px"><label>{{ __('Makluman penting (satu baris = satu makluman)') }}</label><textarea wire:model="f.notices" rows="3"></textarea></div>
        </div>

        <div class="card" id="options">
            <h2 class="sec" style="margin-top:0">{{ __('Options: tujuan page') }}</h2>
            <label class="chk"><input type="checkbox" wire:model="f.purpose_booking"> <span><b>{{ __('Booking meja & pre-order menu') }}</b><br><span class="hint">{{ __('Pelanggan pilih outlet, tarikh, masa dan bilangan tetamu.') }}</span></span></label>
            <label class="chk" style="margin-top:8px"><input type="checkbox" wire:model="f.purpose_purchase"> <span><b>{{ __('Pembelian produk biasa') }}</b><br><span class="hint">{{ __('Pickup atau delivery, bayar online.') }}</span></span></label>
            @error('f.purpose_booking')<div class="auth-error">{{ $message }}</div>@enderror
            <div class="hint">{{ __('Jika kedua-duanya aktif, pelanggan diberi pilihan. Jika satu sahaja, pelanggan terus ke aliran itu.') }}</div>
            <label style="margin-top:14px">{{ __('Outlet yang dibenarkan') }} *</label>
            <div style="display:flex;gap:14px;flex-wrap:wrap">
                @foreach ($outlets as $b)
                    <label class="chk"><input type="checkbox" value="{{ $b->id }}" wire:model="outletIds"> {{ $b->name }} @unless ($b->accepts_orders)<span class="badge warn">{{ __('belum terima tempahan') }}</span>@endunless</label>
                @endforeach
            </div>
            @error('outletIds')<div class="auth-error">{{ $message }}</div>@enderror
        </div>

        <div class="card" id="products">
            <h2 class="sec" style="margin-top:0">Products</h2>
            <div class="hint" style="margin-top:0;margin-bottom:10px">{!! __('Tanda produk yang dipaparkan. Urus harga, variasi dan stok di :link.', ['link' => '<a href="'.e(route('tempahan.products')).'" wire:navigate style="text-decoration:underline">'.e(__('Produk & Stok')).'</a>']) !!}</div>
            @forelse ($products as $p)
                <label class="chk" style="margin-bottom:6px"><input type="checkbox" value="{{ $p->id }}" wire:model="productIds"> {{ $p->name }} <span class="hint" style="margin:0">· {{ $p->category?->name ?? __('Tiada kategori') }}@if ($p->is_hidden) · {{ __('tersembunyi') }} @endif</span></label>
            @empty
                <div class="empty">{{ __('Belum ada produk.') }}</div>
            @endforelse
        </div>

        <div class="card" id="purchasing">
            <h2 class="sec" style="margin-top:0">Purchasing & SST</h2>
            <label class="chk"><input type="checkbox" wire:model="f.prices_include_sst"> <span><b>{{ __('Harga paparan sudah termasuk SST') }}</b><br><span class="hint">{{ __('Checkout dan invois tidak menambah SST lagi. Contoh: RM5.30 kekal RM5.30.') }}</span></span></label>
            <div class="row" style="margin-top:12px">
                <div><label>{{ __('Kadar SST (%)') }}</label><input type="number" step="0.01" wire:model="f.sst_rate"></div>
                <div><label>{{ __('Belian minimum (RM)') }}</label><input type="number" step="0.01" wire:model="f.min_order"></div>
                <div><label>{{ __('Belian maksimum (RM)') }}</label><input type="number" step="0.01" wire:model="f.max_order"></div>
            </div>
            <div class="hint">{{ __('Kupon diskaun: Fasa 2.') }}</div>
        </div>

        {{-- Hanya relevan untuk pembelian produk (borang ringkas sentiasa pickup) --}}
        <div class="card" id="fulfillment" x-show="$wire.f.purpose_purchase && ! $wire.f.simple_form" x-cloak>
            <h2 class="sec" style="margin-top:0">{{ __('Pickup / Delivery (pembelian produk)') }}</h2>
            <label class="chk"><input type="checkbox" wire:model="f.allow_pickup"> {{ __('Benarkan pickup di outlet') }}</label>
            <label class="chk" style="margin-top:8px"><input type="checkbox" wire:model="f.allow_delivery"> {{ __('Benarkan delivery (alamat diminta)') }}</label>
            @error('f.allow_pickup')<div class="auth-error">{{ $message }}</div>@enderror
            <div style="max-width:260px;margin-top:12px"><label>{{ __('Caj penghantaran asas (RM)') }}</label><input type="number" step="0.01" wire:model="f.delivery_fee"><div class="hint">{{ __('Caj outlet (jika ditetapkan) mengatasi nilai ini.') }}</div></div>
        </div>

        <div class="card" id="slots">
            <h2 class="sec" style="margin-top:0">{{ __('Date & Time Slots (booking)') }}</h2>
            <label>{{ __('Pre-order menu') }}</label>
            <select wire:model="f.booking_mode"><option value="preorder_required">{{ __('Wajib pre-order sekurang-kurangnya satu menu') }}</option><option value="table_only_allowed">{{ __('Booking meja sahaja dibenarkan (cart kosong OK)') }}</option></select>
            <div style="margin-top:12px"><label>{{ __('Makluman booking') }}</label><textarea wire:model="f.booking_notice" rows="3"></textarea></div>
            <div class="hint">{!! __('Waktu operasi, cuti, tempoh slot dan kapasiti ditetapkan bagi setiap outlet di :link.', ['link' => '<a href="'.e(route('tempahan.outlets')).'" wire:navigate style="text-decoration:underline">'.e(__('Tetapan Outlet')).'</a>']) !!}</div>
        </div>

        <div class="card" id="details">
            <h2 class="sec" style="margin-top:0">Customer Details</h2>
            <div>{{ __('Nama, nombor telefon (format Malaysia) dan nombor order wajib. Emel pilihan. Pelanggan boleh pilih "Ingat maklumat saya" pada peranti sendiri.') }}</div>
            <label class="chk" style="margin-top:12px"><input type="checkbox" wire:model="f.simple_form"> <span><b>{{ __('Borang ringkas') }}</b><br><span class="hint">{{ __('Pelanggan hanya isi Nama, Telefon, Nombor order, Cawangan dan Emel. Tiada tarikh, masa, tetamu, pickup/delivery atau nota. Pesanan direkod sebagai Pickup.') }}</span></span></label>
        </div>

        <div class="card" id="whatsapp">
            <h2 class="sec" style="margin-top:0">WhatsApp Message</h2>
            <label class="chk"><input type="checkbox" wire:model="f.action_whatsapp"> <span><b>{{ __('Aktifkan "Hantar ke WhatsApp"') }}</b><br><span class="hint">{{ __('Dibuka ke nombor WhatsApp outlet yang dipilih pelanggan.') }}</span></span></label>
            @error('f.action_whatsapp')<div class="auth-error">{{ $message }}</div>@enderror
            <div style="margin-top:12px"><label>{{ __('Templat mesej') }}</label><textarea wire:model="f.whatsapp_template" rows="10" style="font-family:ui-monospace,Consolas,monospace;font-size:12.5px"></textarea></div>
            <div class="hint">{{ __('Pemboleh ubah:') }} {reference} {name} {order_no} {phone} {email} {outlet} {date} {time} {guests} {items} {bill} {subtotal} {sst} {rounding} {total} {notes} {fulfillment} {address} {payment_link}</div>
            <div class="hint"><b>{bill}</b> = {{ __('Subtotal, SST, Rounding (jika ada) dan Jumlah akhir, setiap satu pada baris sendiri.') }}</div>
        </div>

        <div class="card" id="payment">
            <h2 class="sec" style="margin-top:0">Payment Gateway</h2>
            <label class="chk"><input type="checkbox" wire:model="f.action_online"> <span><b>{{ __('Aktifkan "Bayar Online"') }}</b> (CHIP Collect)<br><span class="hint">{{ __('Gateway semasa:') }} <b>{{ $gateway }}</b></span></span></label>
            <label class="chk" style="margin-top:8px"><input type="checkbox" wire:model="f.auto_confirm_on_payment"> <span><b>{{ __('Sahkan booking automatik selepas bayaran disahkan gateway') }}</b><br><span class="hint">{{ __('Jika tidak ditanda, staf outlet sahkan secara manual. Paid dan Confirmed sentiasa direkod berasingan.') }}</span></span></label>
        </div>

        <div class="card" id="popup">
            <h2 class="sec" style="margin-top:0">Welcome Popup</h2>
            <label>{{ __('Mesej popup (kosong = tiada popup)') }}</label><textarea wire:model="f.welcome_popup" rows="3"></textarea>
        </div>

        <div class="card" id="maintenance">
            <h2 class="sec" style="margin-top:0">Maintenance</h2>
            <label>{{ __('Mesej semasa maintenance') }}</label><textarea wire:model="f.maintenance_message" rows="2"></textarea>
            <div class="hint">{{ __('Tukar Status di Overview kepada Maintenance untuk menutup page sementara.') }}</div>
        </div>

        <div style="position:sticky;bottom:0;background:var(--bg);padding:12px 0;border-top:1px solid var(--line);display:flex;gap:12px;align-items:center">
            <button class="btn" type="submit" wire:loading.attr="disabled">{{ __('Simpan') }}</button>
            @if ($form)<span class="hint" style="margin:0">{{ __('URL awam:') }} {{ $form->publicUrl() }}</span>@endif
        </div>
    </form>
</div>
