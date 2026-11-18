# Tempahan Online: Fasa 1

Landing page jualan dan booking untuk Pak Maon Western, disambungkan kepada backend sistem outlet ini.
Rujukan fungsi: Orderla.my (berdasarkan spesifikasi bertulis pemilik; dashboard Orderla dan screenshot
pelanggan **belum disemak**). Padanan penuh dengan Orderla belum diisytiharkan.

## Cara ia disambungkan ke sistem sedia ada

| Konsep tempahan | Dalam projek ini |
|---|---|
| Cawangan | `Outlet` sedia ada + medan baharu (`slug`, `whatsapp`, `accepts_orders`, slot, pickup/delivery) |
| Waktu operasi / cuti | `outlet_hours`, `outlet_closures` |
| Pemilik perniagaan | `User` sedia ada (pemilik outlet). Katalog, landing page dan pesanan dimiliki user |
| Staf cawangan | `outlet_user` (role `manager`/`staff`). Tiada outlet sendiri, tiada akses modul kewangan |
| Katalog jualan | `products` + `product_variants` (berasingan daripada `menus` food cost, boleh dipautkan melalui `menu_id`) |
| Admin UI | Komponen Livewire `App\Livewire\Tempahan\*` dalam `layouts.app` dan CSS sedia ada |
| Halaman awam | `layouts.shop` + kelas `.sf-*` dalam `resources/css/app.css`, token tema yang sama |
| Format wang | Disimpan dalam **sen**; dipaparkan dengan `@sen()` → `rm()` sedia ada |

## Fail utama

| Fail | Fungsi |
|---|---|
| `app/Services/PricingService.php` | Harga, SST termasuk/tambah, stok, had belian: semua di server |
| `app/Services/SlotService.php` | Waktu operasi, cuti, kapasiti slot (Asia/Kuala_Lumpur) |
| `app/Services/OrderService.php` | Cipta pesanan idempotent, kunci stok, lepas stok bila batal |
| `app/Services/Payments/*` | CHIP Collect, gateway simulasi, pengesahan bayaran |
| `app/Services/WhatsAppService.php` | Mesej WhatsApp dari templat landing page |
| `app/Http/Controllers/Shop/*` | Landing page awam, status pesanan, callback gateway |
| `app/Livewire/Tempahan/*` | Backend: ringkasan, booking harian, pesanan, landing page, produk, outlet, staf |
| `tests/Feature/Tempahan/*` | 42 ujian aliran pelanggan, bayaran, akses |

## Pembayaran (CHIP Collect)

`PAYMENT_DRIVER=fake` = gateway simulasi (hanya `local`/`testing`, tiada wang sebenar). Untuk CHIP:

1. Daftar di https://portal.chip-in.asia, ambil **Brand ID** dan **Secret Key** (kunci ujian dahulu).
2. `.env`: `PAYMENT_DRIVER=chip`, `CHIP_BRAND_ID=…`, `CHIP_SECRET_KEY=…`
3. Daftar webhook ke `https://DOMAIN/payments/chip/webhook` (event `purchase.paid`, `purchase.payment_failure`)
   dan salin kunci awamnya ke `CHIP_WEBHOOK_PUBLIC_KEY` (baris baharu sebagai `\n`).
4. Callback/webhook mesti boleh dicapai dari internet. Di localhost, status disemak terus dengan CHIP
   apabila pelanggan kembali ke halaman pesanan.

Keselamatan: tandatangan RSA `X-Signature` disahkan pada badan mentah, kemudian status disemak semula
dengan `GET /purchases/{id}/` dan dipadankan dengan rujukan, amaun dan MYR. Redirect pelayar tidak
menandakan bayaran. Webhook berulang, klik dua kali dan retry tidak mencipta rekod berganda.

## Matriks padanan

Bukti: **S** = spesifikasi bertulis pemilik, **D** = dokumentasi CHIP.
Ujian: **A** = automatik (`php artisan test`), **B** = diuji dalam pelayar (telefon + desktop).

### Pelanggan

| Fungsi | Bukti | Status | Ujian |
|---|---|---|---|
| Buka tanpa login, logo/nama, makluman ikut page, kongsi pautan | S | Siap | A, B |
| Pilihan tujuan jika dua aktif; terus ke aliran jika satu | S | Siap | A, B |
| Kategori, gambar, harga, tambah; variasi buka/tutup | S | Siap | A, B |
| Harga, SKU, stok, had belian setiap variasi | S | Siap | A |
| Cart melekat di telefon, tambah/kurang/buang, cart kekal | S | Siap | B |
| Subtotal, diskaun, caj, jumlah dikira di server | S | Siap | A |
| Harga termasuk SST 6%: RM5.30 kekal RM5.30 | S | Siap | A, B |
| Nama + telefon wajib, telefon Malaysia diseragamkan, Ingat maklumat saya | S | Siap | A, B |
| Booking: menu → maklumat → outlet → tarikh/masa/tetamu → ringkasan → Proceed | S | Siap | A, B |
| 3 outlet cawangan dengan WhatsApp, waktu, hari tutup, booking sendiri | S | Siap (data demo = **contoh**) | A |
| Pre-order wajib vs booking meja sahaja (cart kosong) | S | Siap | A |
| Semak waktu, cuti, kapasiti slot, ketersediaan menu ikut outlet | S | Siap | A |
| Tukar outlet: semak semula cart dan slot, maklumkan item | S | Siap | A |
| Makluman 5–10 minit boleh diedit | S | Siap | B |
| Pembelian: pickup/delivery, alamat hanya untuk delivery | S | Siap | A |
| Belian minimum/maksimum, caj penghantaran ikut page/outlet | S | Siap | A |
| Kupon tetap/peratus | S | **Fasa 2** | - |

### Selepas Proceed

| Fungsi | Bukti | Status | Ujian |
|---|---|---|---|
| Dua pilihan jelas (WhatsApp / Bayar Online) | S | Siap | B |
| Satu nombor rujukan; WhatsApp ke outlet dipilih, mesej siap diisi | S | Siap | A |
| Templat mesej boleh diedit | S | Siap | A |
| Hanya rekod "pautan dibuka"; papar "Menunggu pengesahan cawangan" | S | Siap | A, B |
| WhatsApp kemudian Bayar Online: rujukan sama, satu pesanan | S | Siap | A |
| Bayar Online terus ke gateway jika WhatsApp dimatikan | S | Siap | - |
| Status apabila kembali; cuba semula selepas gagal/batal/tamat | S | Siap | A, B |
| Tandatangan + semakan server-to-server; padan rujukan/amaun/MYR | S, D | Siap | A |
| Idempotent: klik dua kali, retry, webhook berulang | S | Siap | A |
| CHIP sandbox sebenar | D | **Perlu kunci ujian CHIP** | Http::fake sahaja |

### Backend

| Fungsi | Bukti | Status | Ujian |
|---|---|---|---|
| Ringkasan: booking, pesanan, bayaran, jualan; penapis tarikh/outlet/page | S | Siap | A, B |
| Booking harian ikut outlet untuk dapur (jumlah menu) | S | Siap | A, B |
| Kalendar bulanan | S | **Fasa 2** | - |
| Notifikasi pesanan/bayaran baharu | S | **Fasa 2** | - |
| Landing page: cipta, edit, preview, publish, maintenance, salin URL | S | Siap | A, B |
| Editor: Overview, Options, Products, Purchasing, Pickup/Delivery, Customer Details, Date & Time, WhatsApp, Payment, Welcome Popup, Maintenance | S | Siap | A, B |
| Setup berpandu, Themes, Integrations, Coupons dalam editor | S | **Fasa 2** | - |
| Produk, kategori, variasi, promosi, SKU, tersembunyi, ketersediaan outlet, pautan menu food cost | S | Siap | A, B |
| Add-on produk | S | **Fasa 2** | - |
| Carian nama/SKU; log pelarasan stok dan pergerakan pesanan | S | Siap (log dalam DB, tiada skrin sejarah) | A |
| Had harian & maksud "LIMIT" Orderla | S | **Perlu sahkan dengan akaun Orderla** | - |
| Outlet: WhatsApp, lokasi, waktu, cuti, slot, kapasiti, pickup, delivery | S | Siap | A, B |
| Integrasi EasyParcel/Delyva/Google Maps/rider | S | **Fasa 3** | - |
| Carian rujukan/pelanggan, penapis tarikh/outlet/page/status | S | Siap | A, B |
| Status booking, bayaran, fulfillment berasingan; sahkan/jadual semula/batal | S | Siap | A, B |
| Sejarah tindakan | S | Siap | B |
| Edit item selepas bayar dengan rekod perbezaan | S | **Fasa 2** | - |
| Pautan invois pelanggan; eksport CSV ikut penapis | S | Siap (invois = halaman pesanan) | A |
| Invois PDF | S | **Fasa 2** | - |
| Pemilik vs staf cawangan (manager/staff) terhad ikut outlet, di server | S | Siap | A |
| Pemilik lain tidak nampak data | S | Siap | A |
| Salesperson, Rider, POS, agihan giliran | S | **Fasa 3, perlu sahkan skop Orderla** | - |
| Jualan online dimasukkan ke modul Sales / PnL | - | **Cadangan Fasa 2** | - |

## Konfigurasi live yang masih diperlukan

1. Nombor WhatsApp sebenar setiap outlet (demo guna `60100000001-3`).
2. Waktu operasi, hari tutup, kapasiti slot sebenar.
3. Logo dan gambar menu.
4. Kunci CHIP (ujian dahulu), Brand ID, webhook + kunci awam.
5. HTTPS dan domain (Forge) supaya callback CHIP sampai.
6. Akaun staf sebenar melalui **Tempahan > Akaun Staf**.
