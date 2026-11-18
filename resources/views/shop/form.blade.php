@extends('layouts.shop')
@section('title', $form->title)
@section('description', $form->description ?: 'Tempah meja dan pre-order menu Pak Maon Western.')
@section('robots', $preview ? 'noindex' : 'index,follow')

@section('content')
<div x-data="pmwShop(@js([
    'slug' => $form->slug,
    'catalog' => $catalog,
    'outlets' => $outlets,
    'purposes' => array_values(array_filter([$form->purpose_booking ? 'booking' : null, $form->purpose_purchase ? 'purchase' : null])),
    'tableOnly' => $form->allowsTableOnly(),
    'allowPickup' => $form->allow_pickup,
    'allowDelivery' => $form->allow_delivery,
    'actionWhatsapp' => $form->action_whatsapp,
    'actionOnline' => $form->action_online,
    'welcome' => $form->welcome_popup,
    'urls' => [
        'quote' => route('form.quote', $form->slug),
        'slots' => route('form.slots', $form->slug),
        'store' => route('form.store', $form->slug).($preview ? '?preview=1' : ''),
    ],
]))" x-cloak class="sf-pad">

    <header class="sf-head">
        <div class="sf-wrap">
            @include('shop._brand', ['sub' => $form->title])
            <button class="sf-iconbtn" type="button" @click="share()" aria-label="Kongsi pautan">↗</button>
        </div>
    </header>

    @if ($preview)
        <div class="sf-wrap"><div class="sf-alert info">Mod preview pemilik. Status page: <b>{{ $form->status }}</b>.</div></div>
    @endif

    {{-- ================= KATALOG ================= --}}
    <main class="sf-wrap" x-show="step === 'menu'">
        <section class="sf-hero">
            @if ($form->banner_url)
                <div class="sf-banner"><img src="{{ $form->banner_url }}" alt=""></div>
            @endif
            <h1>{{ $form->title }}</h1>
            @if ($form->description)<p>{{ $form->description }}</p>@endif
        </section>

        @foreach ($form->notices ?? [] as $n)
            <div class="sf-notice">{{ $n }}</div>
        @endforeach

        @if (count($outlets) === 0)
            <div class="sf-alert warn">Tiada cawangan menerima tempahan buat masa ini. Sila cuba lagi kemudian.</div>
        @endif

        <template x-if="purposes.length > 1">
            <div class="sf-purposes" role="radiogroup" aria-label="Pilih tujuan">
                <button type="button" class="sf-purpose" :class="{ on: purpose === 'booking' }" @click="setPurpose('booking')" role="radio" :aria-checked="purpose === 'booking'">
                    <span class="ic" aria-hidden="true">🍽️</span>
                    <h3>Tempah Meja &amp; Pre-order</h3>
                    <span class="m">Pilih cawangan, tarikh dan masa. Makanan disediakan sebelum anda tiba.</span>
                </button>
                <button type="button" class="sf-purpose" :class="{ on: purpose === 'purchase' }" @click="setPurpose('purchase')" role="radio" :aria-checked="purpose === 'purchase'">
                    <span class="ic" aria-hidden="true">🛍️</span>
                    <h3>Beli untuk Pickup{{ $form->allow_delivery ? ' / Delivery' : '' }}</h3>
                    <span class="m">Pesan dan bayar online, ambil di cawangan{{ $form->allow_delivery ? ' atau hantar ke rumah' : '' }}.</span>
                </button>
            </div>
        </template>

        <div x-show="purpose">
            <nav class="sf-tabs" aria-label="Kategori menu">
                <template x-for="(items, cat) in catalog" :key="cat">
                    <a class="sf-tab" :href="'#cat-' + slugify(cat)" x-text="cat"></a>
                </template>
            </nav>

            <template x-for="(items, cat) in catalog" :key="cat">
                <section>
                    <h2 class="sf-cat" :id="'cat-' + slugify(cat)" x-text="cat"></h2>
                    <template x-for="p in items" :key="p.id">
                        <article class="sf-product">
                            <div class="sf-img">
                                <template x-if="p.image"><img :src="p.image" :alt="p.name" loading="lazy"></template>
                                <template x-if="!p.image"><span x-text="initials(p.name)" aria-hidden="true"></span></template>
                            </div>
                            <div>
                                <h3 x-text="p.name"></h3>
                                <p class="sf-desc" x-show="p.description" x-text="p.description"></p>

                                <template x-if="!p.has_variants">
                                    <div class="sf-between">
                                        <span class="sf-price"><span x-text="rm(p.price)"></span><s x-show="p.regular_price && p.regular_price > p.price" x-text="rm(p.regular_price)"></s></span>
                                        <span x-show="p.sold_out" class="sf-soldout">Habis</span>
                                        <div x-show="!p.sold_out">
                                            <button type="button" class="btn sm" x-show="qtyOf(p.id, null) === 0" @click="add(p, null)">Tambah</button>
                                            <div class="sf-qty" x-show="qtyOf(p.id, null) > 0">
                                                <button type="button" class="sf-qbtn" @click="dec(p.id, null)" :aria-label="'Kurang ' + p.name">−</button>
                                                <b x-text="qtyOf(p.id, null)"></b>
                                                <button type="button" class="sf-qbtn" @click="add(p, null)" :disabled="p.max && qtyOf(p.id, null) >= p.max" :aria-label="'Tambah ' + p.name">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="p.has_variants">
                                    <div class="sf-between">
                                        <span class="sf-price">dari <span x-text="rm(p.from_price)"></span></span>
                                        <button type="button" class="sf-toggle" @click="toggle(p.id)" :aria-expanded="isOpen(p.id)">
                                            <span x-text="isOpen(p.id) ? 'Tutup pilihan' : 'Pilih variasi'"></span>
                                            <span x-show="qtyOfProduct(p.id) > 0" class="chip" x-text="qtyOfProduct(p.id) + ' dalam cart'"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div class="sf-variants" x-show="p.has_variants && isOpen(p.id)" x-transition>
                                <template x-for="v in p.variants" :key="v.id">
                                    <div class="sf-variant">
                                        <div class="sf-grow"><span x-text="v.name"></span><br><span class="sf-price" x-text="rm(v.price)"></span></div>
                                        <span x-show="v.sold_out" class="sf-soldout">Habis</span>
                                        <div x-show="!v.sold_out">
                                            <button type="button" class="btn sm" x-show="qtyOf(p.id, v.id) === 0" @click="add(p, v)">Tambah</button>
                                            <div class="sf-qty" x-show="qtyOf(p.id, v.id) > 0">
                                                <button type="button" class="sf-qbtn" @click="dec(p.id, v.id)" :aria-label="'Kurang ' + v.name">−</button>
                                                <b x-text="qtyOf(p.id, v.id)"></b>
                                                <button type="button" class="sf-qbtn" @click="add(p, v)" :disabled="v.max && qtyOf(p.id, v.id) >= v.max" :aria-label="'Tambah ' + v.name">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </article>
                    </template>
                </section>
            </template>
        </div>
    </main>

    {{-- Cart melekat di bawah --}}
    <div class="sf-cartbar" x-show="step === 'menu' && purpose && (count > 0 || canProceedEmpty)">
        <div class="sf-wrap">
            <span class="sf-count" x-text="count"></span>
            <div class="sf-grow">
                <div style="font-size:13px;color:var(--muted)" x-text="count > 0 ? count + ' item' : 'Tiada menu dipilih'"></div>
                <div class="sf-price" x-text="count > 0 ? rm(estimate) : 'Booking meja sahaja'"></div>
            </div>
            <button type="button" class="btn" @click="goCheckout()">Proceed</button>
        </div>
    </div>

    {{-- ================= CHECKOUT ================= --}}
    <main class="sf-wrap" x-show="step === 'checkout'" style="padding-top:12px">
        <button type="button" class="btn sec sm" @click="step = 'menu'; window.scrollTo(0, 0)">← Kembali ke menu</button>

        <div class="sf-step"><span class="n">1</span><h2 x-text="purpose === 'booking' ? 'Pre-order anda' : 'Cart anda'"></h2></div>
        <div class="card">
            <template x-if="cart.length === 0">
                <p style="color:var(--muted)">Tiada menu dipilih. <span x-show="canProceedEmpty">Anda boleh teruskan dengan booking meja sahaja.</span></p>
            </template>
            <template x-for="l in cart" :key="l.key">
                <div class="sf-line">
                    <div><div class="nm" x-text="l.name"></div><div class="mt" x-show="l.variant" x-text="l.variant"></div></div>
                    <div class="sf-price" style="text-align:right" x-text="rm(l.price * l.qty)"></div>
                    <div class="act">
                        <div class="sf-qty">
                            <button type="button" class="sf-qbtn" @click="dec(l.product_id, l.variant_id)" aria-label="Kurang">−</button>
                            <b x-text="l.qty"></b>
                            <button type="button" class="sf-qbtn" @click="inc(l)" :disabled="l.max && l.qty >= l.max" aria-label="Tambah">+</button>
                        </div>
                        <button type="button" class="sf-linkdel" @click="remove(l.key)">Buang</button>
                    </div>
                </div>
            </template>
            <template x-if="quote && quote.unavailable.length">
                <div class="sf-alert warn">Tiada di cawangan ini: <b x-text="quote.unavailable.join(', ')"></b>. Sila buang atau pilih cawangan lain.</div>
            </template>
            <template x-for="e in (quote ? quote.errors : [])"><div class="sf-alert bad" x-text="e"></div></template>
        </div>

        <div class="sf-step"><span class="n">2</span><h2>Maklumat anda</h2></div>
        <div class="card sf-stack">
            <div class="sf-field"><label for="sf-name">Nama <span class="req">*</span></label>
                <input id="sf-name" x-model.trim="d.name" autocomplete="name" maxlength="100">
                <div class="sf-err" x-text="err.name"></div></div>
            <div class="sf-field"><label for="sf-phone">Nombor telefon <span class="req">*</span></label>
                <input id="sf-phone" x-model.trim="d.phone" @blur="formatPhone()" inputmode="tel" autocomplete="tel" placeholder="012-345 6789">
                <div class="sf-err" x-text="err.phone || err.phone_normalized"></div></div>
            <div class="sf-field"><label for="sf-email">Emel <span style="color:var(--muted);font-weight:400">(pilihan, untuk resit)</span></label>
                <input id="sf-email" type="email" x-model.trim="d.email" autocomplete="email"></div>
            <label class="sf-check"><input type="checkbox" x-model="d.remember"> <span>Ingat maklumat saya pada peranti ini</span></label>
        </div>

        <div class="sf-step"><span class="n">3</span><h2 x-text="purpose === 'booking' ? 'Cawangan, tarikh & masa' : 'Cawangan & cara terima'"></h2></div>
        <div class="card sf-stack">
            <div class="sf-field"><label for="sf-outlet">Cawangan <span class="req">*</span></label>
                <select id="sf-outlet" x-model.number="d.outlet_id" @change="onOutletChange()">
                    <option value="">Pilih cawangan</option>
                    <template x-for="b in availableOutlets" :key="b.id"><option :value="b.id" x-text="b.name" :selected="b.id === d.outlet_id"></option></template>
                </select>
                <div class="sf-err" x-text="err.outlet_id"></div></div>
            <div class="sf-alert info" x-show="outletNote" x-text="outletNote"></div>

            <template x-if="purpose === 'booking'">
                <div class="sf-stack">
                    <div class="sf-2">
                        <div class="sf-field"><label for="sf-date">Tarikh <span class="req">*</span></label>
                            <input id="sf-date" type="date" x-model="d.date" :min="today" :max="maxDate" @change="loadSlots()"></div>
                        <div class="sf-field"><label for="sf-guests">Bilangan tetamu <span class="req">*</span></label>
                            <input id="sf-guests" type="number" min="1" :max="outlet ? outlet.max_guests : 50" x-model.number="d.guests" inputmode="numeric"></div>
                    </div>
                    <div class="sf-err" x-text="err.booking_date || err.guests"></div>
                    <div class="sf-field">
                        <label>Masa <span class="req">*</span></label>
                        <p class="hint" x-show="!d.outlet_id || !d.date">Pilih cawangan dan tarikh untuk melihat masa yang tersedia.</p>
                        <p class="hint" x-show="slotsLoading">Memuatkan masa…</p>
                        <div class="sf-alert warn" x-show="slotsReason" x-text="slotsReason"></div>
                        <div class="sf-slots" x-show="slots.length">
                            <template x-for="s in slots" :key="s.time">
                                <button type="button" class="sf-slot" :class="{ on: d.time === s.time }" :disabled="!s.available || (d.guests && s.remaining < d.guests)" @click="d.time = s.time" x-text="s.time" :title="s.available ? s.remaining + ' tempat lagi' : 'Tidak tersedia'"></button>
                            </template>
                        </div>
                        <div class="sf-err" x-text="err.booking_time"></div>
                    </div>
                    @if ($form->booking_notice)
                        <div class="sf-notice" style="margin:0">{{ $form->booking_notice }}</div>
                    @endif
                </div>
            </template>

            <template x-if="purpose === 'purchase'">
                <div class="sf-stack">
                    <div class="sf-field"><label>Cara terima <span class="req">*</span></label>
                        <div class="sf-choices">
                            <button type="button" class="sf-choice" x-show="allowPickup" :class="{ on: d.fulfillment === 'pickup' }" @click="d.fulfillment = 'pickup'; requote()">Pickup di cawangan</button>
                            <button type="button" class="sf-choice" x-show="allowDelivery" :class="{ on: d.fulfillment === 'delivery' }" @click="d.fulfillment = 'delivery'; requote()">Delivery</button>
                        </div>
                        <div class="sf-err" x-text="err.fulfillment"></div></div>
                    <p class="hint" x-show="d.fulfillment === 'pickup' && outlet">Lokasi pickup: <b x-text="outlet ? outlet.name : ''"></b><span x-show="outlet && outlet.address" x-text="outlet ? ', ' + outlet.address : ''"></span></p>
                    <div class="sf-field" x-show="d.fulfillment === 'delivery'"><label for="sf-addr">Alamat penghantaran <span class="req">*</span></label>
                        <textarea id="sf-addr" rows="3" x-model.trim="d.address" autocomplete="street-address"></textarea>
                        <div class="sf-err" x-text="err.delivery_address"></div></div>
                </div>
            </template>

            <div class="sf-field"><label for="sf-notes">Nota <span style="color:var(--muted);font-weight:400">(pilihan)</span></label>
                <textarea id="sf-notes" rows="2" x-model.trim="d.notes" maxlength="500" placeholder="Contoh: kerusi bayi, tanpa sayur"></textarea></div>
        </div>

        <div class="sf-step"><span class="n">4</span><h2>Ringkasan</h2></div>
        <div class="card">
            <div class="sf-totals" x-show="quote">
                <div class="sf-between"><span>Subtotal</span><span x-text="quote ? quote.subtotal : ''"></span></div>
                <div class="sf-between" x-show="quote && quote.discount"><span>Diskaun</span><span x-text="quote ? '-' + quote.discount : ''"></span></div>
                <div class="sf-between" x-show="quote && quote.delivery"><span>Caj penghantaran</span><span x-text="quote ? quote.delivery : ''"></span></div>
                <div class="sf-between" x-show="quote && quote.sst_added"><span>SST</span><span x-text="quote ? quote.sst_added : ''"></span></div>
                <div class="sf-between grand"><span>Jumlah</span><span x-text="quote ? quote.total : ''"></span></div>
                <div class="hint" x-show="quote && quote.sst_note" x-text="quote ? quote.sst_note : ''"></div>
            </div>
            <div class="sf-alert bad" x-show="err._general" x-text="err._general"></div>
            <div class="sf-alert bad" x-show="err.items" x-text="err.items"></div>
            <button type="button" class="btn block" style="margin-top:16px" @click="submit()" :disabled="submitting">
                <span x-text="submitting ? 'Sedang diproses…' : 'Proceed'"></span>
            </button>
            <p class="hint" style="text-align:center" x-text="proceedHint"></p>
        </div>
    </main>

    <div class="sf-modal-back" x-show="showWelcome" x-transition.opacity @click.self="closeWelcome()">
        <div class="card sf-modal sf-stack" role="dialog" aria-modal="true">
            <h2 style="font-size:19px;font-weight:800">Selamat datang</h2>
            <p style="white-space:pre-line" x-text="welcome"></p>
            <button class="btn block" type="button" @click="closeWelcome()">Mula pilih menu</button>
        </div>
    </div>

    <div class="toast" :class="{ show: toast }" x-text="toast"></div>
</div>
@endsection

@push('scripts')
<script>
function pmwShop(cfg) {
    const store = {
        get(k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
        set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
        del(k) { try { localStorage.removeItem(k); } catch (e) {} },
    };
    const pad = n => String(n).padStart(2, '0');
    const ymd = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const nowKL = () => new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Kuala_Lumpur' }));
    const csrf = () => document.querySelector('meta[name=csrf-token]').content;

    return {
        ...cfg,
        step: 'menu', purpose: null, cart: [], open: {},
        d: { name: '', phone: '', email: '', remember: false, outlet_id: '', date: '', time: '', guests: 2, fulfillment: '', address: '', notes: '' },
        err: {}, quote: null, slots: [], slotsReason: '', slotsLoading: false,
        submitting: false, showWelcome: false, toast: '', outletNote: '', _qTimer: null,

        init() {
            this.cart = store.get('pmw_cart_' + this.slug) || [];
            const saved = store.get('pmw_details');
            if (saved) Object.assign(this.d, saved, { remember: true });
            this.purpose = this.purposes.length === 1 ? this.purposes[0] : store.get('pmw_purpose_' + this.slug);
            if (!this.allowPickup && this.allowDelivery) this.d.fulfillment = 'delivery';
            if (this.allowPickup && !this.allowDelivery) this.d.fulfillment = 'pickup';
            if (this.outlets.length === 1) this.d.outlet_id = this.outlets[0].id;
            this.$watch('cart', v => store.set('pmw_cart_' + this.slug, v));
            this.$watch('d.guests', () => { if (this.d.time && !this.slotFits(this.d.time)) this.d.time = ''; });
            if (this.welcome && !store.get('pmw_welcome_' + this.slug)) this.showWelcome = true;
        },

        get today() { return ymd(nowKL()); },
        get maxDate() { const t = nowKL(); t.setDate(t.getDate() + (this.outlet ? this.outlet.advance_days : 30)); return ymd(t); },
        get outlet() { return this.outlets.find(b => b.id === Number(this.d.outlet_id)) || null; },
        get availableOutlets() {
            return this.outlets.filter(b => this.purpose === 'booking' ? b.booking : (this.d.fulfillment === 'delivery' ? b.delivery : (b.pickup || b.delivery)));
        },
        get count() { return this.cart.reduce((s, l) => s + l.qty, 0); },
        get estimate() { return this.cart.reduce((s, l) => s + l.price * l.qty, 0); },
        get canProceedEmpty() { return this.purpose === 'booking' && this.tableOnly; },
        get proceedHint() {
            if (this.actionWhatsapp && this.actionOnline) return 'Seterusnya anda boleh hantar ke WhatsApp cawangan atau Bayar Online.';
            if (this.actionOnline) return 'Anda akan dibawa ke halaman bayaran yang selamat.';
            return 'Seterusnya anda akan hantar butiran ke WhatsApp cawangan.';
        },

        setPurpose(p) { this.purpose = p; store.set('pmw_purpose_' + this.slug, p); this.d.time = ''; },
        rm(c) { return window.RM ? window.RM((c || 0) / 100) : 'RM' + ((c || 0) / 100).toFixed(2); },
        initials(n) { return n.split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase(); },
        slugify(s) { return s.toLowerCase().replace(/[^a-z0-9]+/g, '-'); },
        toggle(id) { this.open[id] = !this.open[id]; },
        isOpen(id) { return !!this.open[id]; },
        key(pid, vid) { return pid + ':' + (vid || 0); },
        qtyOf(pid, vid) { const l = this.cart.find(l => l.key === this.key(pid, vid)); return l ? l.qty : 0; },
        qtyOfProduct(pid) { return this.cart.filter(l => l.product_id === pid).reduce((s, l) => s + l.qty, 0); },

        add(p, v) {
            const k = this.key(p.id, v ? v.id : null);
            const l = this.cart.find(l => l.key === k);
            const max = v ? (v.max || p.max) : p.max;
            if (l) { if (!max || l.qty < max) l.qty++; }
            else this.cart.push({ key: k, product_id: p.id, variant_id: v ? v.id : null, name: p.name, variant: v ? v.name : null, price: v ? v.price : p.price, qty: 1, max: max || null });
            this.flash('Ditambah ke cart');
            this.requote();
        },
        inc(l) { if (!l.max || l.qty < l.max) l.qty++; this.requote(); },
        dec(pid, vid) {
            const k = this.key(pid, vid);
            const l = this.cart.find(l => l.key === k);
            if (!l) return;
            l.qty--;
            if (l.qty <= 0) this.cart = this.cart.filter(x => x.key !== k);
            this.requote();
        },
        remove(k) { this.cart = this.cart.filter(x => x.key !== k); this.requote(); },

        goCheckout() {
            if (this.count === 0 && !this.canProceedEmpty) return;
            this.step = 'checkout';
            window.scrollTo(0, 0);
            this.requote(true);
            if (this.purpose === 'booking' && this.d.outlet_id && this.d.date) this.loadSlots();
        },
        requote(now) {
            if (this.step !== 'checkout') return;
            clearTimeout(this._qTimer);
            this._qTimer = setTimeout(() => this.fetchQuote(), now ? 0 : 250);
        },
        async fetchQuote() {
            const res = await this.post(this.urls.quote, {
                items: this.items(), outlet_id: this.d.outlet_id || null,
                fulfillment: this.purpose === 'booking' ? 'dine_in' : (this.d.fulfillment || 'pickup'),
            });
            if (res.ok) this.quote = await res.json();
        },
        async onOutletChange() {
            this.d.time = ''; this.slots = []; this.slotsReason = '';
            await this.fetchQuote();
            this.outletNote = this.quote && this.quote.unavailable.length
                ? 'Cawangan ditukar. Sesetengah item tiada di cawangan ini, sila semak cart.'
                : (this.d.outlet_id ? 'Cawangan ditukar. Harga dan masa telah disemak semula.' : '');
            if (this.purpose === 'booking' && this.d.date) this.loadSlots();
        },
        async loadSlots() {
            this.slots = []; this.slotsReason = '';
            if (!this.d.outlet_id || !this.d.date) return;
            this.slotsLoading = true;
            try {
                const u = new URL(this.urls.slots, location.href);
                u.searchParams.set('outlet_id', this.d.outlet_id); u.searchParams.set('date', this.d.date);
                const j = await (await fetch(u, { headers: { Accept: 'application/json' } })).json();
                this.slots = j.slots || [];
                this.slotsReason = j.open ? (this.slots.some(s => s.available) ? '' : 'Tiada slot kosong pada tarikh ini.') : j.reason;
                if (this.d.time && !this.slotFits(this.d.time)) this.d.time = '';
            } finally { this.slotsLoading = false; }
        },
        slotFits(t) { const s = this.slots.find(s => s.time === t); return s && s.available && (!this.d.guests || s.remaining >= this.d.guests); },

        formatPhone() {
            const digits = this.d.phone.replace(/\D/g, '');
            let local = digits.startsWith('60') ? digits.slice(2) : digits.startsWith('0') ? digits.slice(1) : digits;
            if (/^1\d{8,9}$/.test(local)) this.d.phone = ('0' + local).replace(/^(01\d)(\d{3,4})(\d{4})$/, '$1-$2 $3');
        },
        idemKey() {
            let k = sessionStorage.getItem('pmw_idem_' + this.slug);
            if (!k) { k = crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2); sessionStorage.setItem('pmw_idem_' + this.slug, k); }
            return k;
        },
        validateLocal() {
            const e = {};
            if (!this.d.name) e.name = 'Sila isi nama.';
            if (!this.d.phone) e.phone = 'Sila isi nombor telefon.';
            if (!this.d.outlet_id) e.outlet_id = 'Sila pilih cawangan.';
            if (this.purpose === 'booking') {
                if (!this.d.date) e.booking_date = 'Sila pilih tarikh.';
                if (!this.d.guests || this.d.guests < 1) e.guests = 'Sila isi bilangan tetamu.';
                if (!this.d.time) e.booking_time = 'Sila pilih masa.';
            } else {
                if (!this.d.fulfillment) e.fulfillment = 'Sila pilih pickup atau delivery.';
                if (this.d.fulfillment === 'delivery' && !this.d.address) e.delivery_address = 'Sila isi alamat penghantaran.';
            }
            if (this.count === 0 && !this.canProceedEmpty) e.items = 'Sila pilih sekurang-kurangnya satu item.';
            return e;
        },
        async submit() {
            if (this.submitting) return;
            this.err = this.validateLocal();
            if (Object.keys(this.err).length) { this.flash('Sila lengkapkan maklumat yang ditanda.'); return; }
            this.submitting = true;
            try {
                const res = await this.post(this.urls.store, {
                    idempotency_key: this.idemKey(), type: this.purpose,
                    name: this.d.name, phone: this.d.phone, email: this.d.email || null,
                    outlet_id: this.d.outlet_id, booking_date: this.d.date, booking_time: this.d.time, guests: this.d.guests,
                    fulfillment: this.purpose === 'booking' ? null : this.d.fulfillment, delivery_address: this.d.address,
                    notes: this.d.notes, items: this.items(),
                });
                const j = await res.json().catch(() => ({}));
                if (res.status === 422) {
                    this.err = Object.fromEntries(Object.entries(j.errors || {}).map(([k, v]) => [k, v[0]]));
                    if (this.err.booking_time) this.loadSlots();
                    this.fetchQuote();
                    return;
                }
                if (!res.ok) { this.err = { _general: res.status === 429 ? 'Terlalu banyak cubaan. Sila tunggu sebentar.' : 'Maaf, berlaku ralat. Sila cuba lagi.' }; return; }

                if (this.d.remember) store.set('pmw_details', { name: this.d.name, phone: this.d.phone, email: this.d.email });
                else store.del('pmw_details');
                this.cart = []; store.del('pmw_cart_' + this.slug);
                sessionStorage.removeItem('pmw_idem_' + this.slug);

                // Hanya Bayar Online diaktifkan: terus ke gateway
                if (this.actionOnline && !this.actionWhatsapp) {
                    const f = document.createElement('form');
                    f.method = 'POST'; f.action = j.redirect.replace('?', '/pay?');
                    f.innerHTML = '<input type="hidden" name="_token" value="' + csrf() + '">';
                    document.body.appendChild(f); f.submit(); return;
                }
                location.href = j.redirect;
            } catch (e) {
                this.err = { _general: 'Sambungan terputus. Sila cuba lagi; pesanan anda tidak akan berganda.' };
            } finally { this.submitting = false; }
        },
        items() { return this.cart.map(l => ({ product_id: l.product_id, variant_id: l.variant_id, quantity: l.qty })); },
        post(url, body) {
            return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify(body) });
        },
        async share() {
            const data = { title: document.title, url: location.href.split('?')[0] };
            try { if (navigator.share) { await navigator.share(data); return; } } catch (e) { return; }
            try { await navigator.clipboard.writeText(data.url); this.flash('Pautan disalin'); } catch (e) { prompt('Salin pautan ini:', data.url); }
        },
        closeWelcome() { this.showWelcome = false; store.set('pmw_welcome_' + this.slug, true); },
        flash(m) { this.toast = m; clearTimeout(this._t); this._t = setTimeout(() => this.toast = '', 1800); },
    };
}
</script>
@endpush
