# PRD — Sistem Pengurusan Outlet "Pak Maon Western"

**Versi:** 1.1
**Tarikh:** 2026-08-09
**Sumber:** Migrasi 1:1 daripada prototaip `pakmaon-outlet.html` (single-file HTML/JS + localStorage) ke aplikasi web sebenar.
**Objektif:** Tiru **100% UI & fungsi** prototaip HTML, tukar kepada stack Laravel + Livewire + MySQL, jadikan **mobile-responsive penuh**, **dwibahasa (BM + EN)**, ada **2 roles (Admin & Student)**, sedia untuk **GitHub + Laravel Forge**.

> **Apa yang berubah dari v1.0 → v1.1 (adjust mana yang berkurang):**
> 1. **Roles Admin & Student** ditambah (§4.1, §12) — dahulu hanya auth generik.
> 2. **Dwibahasa BM + English** ditambah (§13) — dahulu BM sahaja.
> 3. Semua modul, formula & tema visual **kekal sama** seperti v1.0.

---

## 1. Ringkasan Produk

Aplikasi bantu pemilik outlet makanan Western (F&B) merancang dan mengurus outlet dari A–Z: kos permulaan (Capex), peralatan, staff, kos operasi, jualan, tutup kira harian, food cost setiap menu, dan penyata PnL/ROI. Semua pengiraan automatik mengikut bulan yang dipilih.

**Pengguna sasaran:** Pemilik / pengurus / pelajar (student) outlet F&B kecil-sederhana di Malaysia — termasuk konteks latihan/mentoring keusahawanan.
**Bahasa UI:** **Dwibahasa** — Bahasa Melayu (default) & English, boleh tukar per-pengguna.
**Mata wang:** Ringgit Malaysia (format `RM1,234.56`, `en-MY`, 2 titik perpuluhan).

---

## 2. Stack Teknologi (Wajib)

| Lapisan | Teknologi |
|---|---|
| Framework | Laravel (versi terkini / 12.x) |
| Bahasa | PHP 8.4 |
| Pangkalan data | MySQL 8.4 |
| UI dinamik | Livewire 3 |
| Templat | Blade |
| Interaktiviti klien | Alpine.js (dibundle dengan Livewire 3) |
| Carta | Chart.js 4.4.1 (CDN / npm) |
| OCR resit | Tesseract.js 5 (jalan di pelayar) |
| i18n | Laravel Localization (`lang/ms/`, `lang/en/`, helper `__()`) |
| Build asset | Vite |
| Deploy | GitHub → Laravel Forge |

**Prinsip UI:** Kekalkan CSS kustom sedia ada **100% sama** (jangan tulis semula dalam Tailwind). Salin blok `:root` + semua kelas ke `resources/css/app.css`. Ini jamin padanan visual tepat.

---

## 3. Tema Visual (kekalkan tepat)

Tema **abu-abu efek simen** + aksen **kuning lemon**. Pembolehubah CSS:

```css
:root{
  --bg:#c4c6c1; --panel:#e5e6e1; --panel2:#d4d6d0; --line:#b0b2ac;
  --txt:#20232a; --muted:#5b5f66; --accent:#e9d21e; --accent2:#c9b40f; --ink:#1b1d20;
  --green:#2f9e57; --red:#cf3f4a; --blue:#3f7fd6; --yellow:#b8901f;
}
```

- `body` guna tekstur konkrit (3 lapisan radial-gradient + noise SVG inline, `background-attachment:fixed`).
- Teks di atas aksen kuning = `--ink` (gelap).
- Komponen UI teras: `.sidebar`, `.brand`, `.nav`, `.card`, `.kpi`, `.grid` (`.g2/.g3/.g4`), `.split`, `.btn` (`.sec`, `.sm`), `.row`, `table` (`.num`, `.chip`, `.del`, `.miniedit`), `.catgrid`/`.catbtn`, `.drop`, `.bar`, `.prog`, `.toast`, `.badge` (`.ok/.warn/.bad`), `.empty`, `.hint`, `.hidden`, `.monthsel`, `@media print`.

---

## 4. Model Data (MySQL)

Prototaip simpan satu objek JSON dalam `localStorage`. Dalam versi baharu, pecahkan ke jadual relational. **Tambah lapisan multi-tenant**: `users` → `outlets` → semua data milik `outlet`.

### 4.1 `users` (auth standard Laravel + role)
| Lajur | Jenis | Default | Nota |
|---|---|---|---|
| id | bigint PK | | |
| name | string | | |
| email | string unique | | |
| password | string | | |
| **role** | enum('admin','student') | 'student' | **BARU** — kawalan akses (§12) |
| **locale** | string(5) | 'ms' | **BARU** — bahasa pilihan pengguna (§13) |
| email_verified_at, remember_token, timestamps | | | |

### 4.2 `outlets` (gantikan objek `settings`)
| Lajur | Jenis | Default | Nota |
|---|---|---|---|
| id | bigint PK | | |
| user_id | FK users | | pemilik (student) |
| name | string | 'Pak Maon Western' | `settings.outlet` |
| target_sales | decimal(12,2) | 30000 | `targetSales` |
| target_margin | decimal(5,2) | 20 | `targetMargin` (%) |
| target_roi_months | int | 12 | `targetRoiMonths` |
| epf_pct | decimal(5,2) | 13 | `epf` |
| socso_pct | decimal(5,2) | 1.75 | `socso` |
| timestamps | | | |

### 4.3 `staff`
| Lajur | Jenis | Nota |
|---|---|---|
| id, outlet_id FK | | |
| name | string | `nm` |
| position | string nullable | `pos` |
| salary | decimal(10,2) | `gaji` (gaji pokok) |
| type | enum('Sepenuh Masa','Separuh Masa') | `jenis` |
| epf_socso | boolean | `kwsp` |

### 4.4 `equipment`
| Lajur | Jenis | Nota |
|---|---|---|
| id, outlet_id FK | | |
| name | string | `nm` |
| group | enum('Dapur','Ruang Makan','Operasi','Lain-lain') | `grp` |
| qty | int default 1 | |
| unit_price | decimal(10,2) | `price` |
| status | enum('perlu','beli') default 'perlu' | |

### 4.5 `capex_items` (Bajet Buka)
`id, outlet_id, name, amount decimal(12,2)`

### 4.6 `fixed_costs` (Kos Tetap bukan gaji)
| Lajur | Jenis | Nota |
|---|---|---|
| id, outlet_id | | |
| name | string | `nm` |
| group | enum('Sewa','Utiliti','Langganan','Insurans','Ansuran','Lain-lain','Gaji') | `grp` |
| amount | decimal(10,2) | `amt` |

### 4.7 `receipts` (Scan Resit)
| Lajur | Jenis | Nota |
|---|---|---|
| id, outlet_id | | |
| date | date | |
| category | enum (8 kategori, lihat §5) | `cat` |
| amount | decimal(10,2) | `amt` |
| note | string nullable | |
| image_path | string nullable | **BARU** — simpan imej resit yang diupload |

### 4.8 `receipt_items` (item dalam resit)
`id, receipt_id FK cascade, name, amount decimal(10,2)`

### 4.9 `sales`
`id, outlet_id, date, amount decimal(12,2)`

### 4.10 `menus`
`id, outlet_id, name, price decimal(10,2)`

### 4.11 `menu_ingredients`
`id, menu_id FK cascade, name, cost decimal(10,2)`

### 4.12 `cash_records` (Petty Cash)
| Lajur | Jenis | Nota |
|---|---|---|
| id, outlet_id | | |
| date | date | |
| float_start | decimal(10,2) | `floatAwal` |
| cash_sales | decimal(10,2) | `jualanTunai` |
| cash_payments | decimal(10,2) | `bayaranTunai` |
| actual_count | decimal(10,2) | `kiraSebenar` |

### 4.13 Kategori kos (config statik, bukan jadual)
8 kategori tetap dalam `config/outlet.php` (label ada kunci terjemahan §13):

| id | Nama (BM) | Ikon | COGS? |
|---|---|---|---|
| bahan | Bahan Mentah | 🥩 | ✅ |
| hardware | Hardware | 🔧 | |
| utiliti | Utiliti | 💡 | |
| packaging | Packaging | 📦 | ✅ |
| marketing | Marketing | 📣 | |
| maintenance | Maintenance | 🛠️ | |
| gas | Gas / Bahan Api | 🔥 | |
| lain | Lain-lain | 📁 | |

---

## 5. Formula Pengiraan (WAJIB tiru tepat)

Semua dikira **untuk bulan dipilih** (`curMonth`, format `YYYY-MM`). Bina satu service `App\Services\OutletMetrics` atau kaedah `Outlet::metrics($month)`.

```
// Per staff
staffTotal(s)   = s.salary + (s.epf_socso ? s.salary*(epf/100) + s.salary*(socso/100) : 0)

// Agregat bulanan
varTotal        = Σ receipts.amount  (bulan itu)
cogs            = Σ receipts.amount  di mana kategori.cogs == true  (bahan, packaging)
otherFixed      = Σ fixed_costs.amount
staffCost       = Σ staffTotal(s)
fixedTotal      = otherFixed + staffCost
sales           = Σ sales.amount  (bulan itu)
expenses        = varTotal + fixedTotal
net             = sales − expenses
capex           = Σ capex_items.amount
foodCostPct     = sales>0 ? cogs/sales*100 : 0
marginPct       = sales>0 ? net/sales*100 : 0
varRatio        = sales>0 ? varTotal/sales : 0
beSales         = (1−varRatio)>0 ? fixedTotal/(1−varRatio) : 0     // break-even sebulan
roiMonths       = net>0 ? capex/net : ∞

// Menu food cost
menuCost(m)     = Σ ingredients.cost
gpPerUnit       = price − menuCost
foodCostPct(m)  = price>0 ? menuCost/price*100 : 0
gpPct(m)        = price>0 ? gpPerUnit/price*100 : 0
avgGP           = purata gpPct semua menu

// ROI & sasaran
targetNet       = targetSales * targetMargin/100
targetRoi       = targetNet>0 ? capex/targetNet : ∞
neededNet       = targetRoiMonths>0 ? capex/targetRoiMonths : 0
neededSales     = targetMargin>0 ? neededNet/(targetMargin/100) : 0

// Petty cash
cashExpected(r) = floatStart + cashSales − cashPayments
cashDiff(r)     = actualCount − cashExpected
```

**Ambang badge food cost:** ≤35% = "Sihat/Baik" (hijau) · ≤45% = "Tinggi" (kuning) · >45% = "Bahaya/Rugi" (merah).

---

## 6. Modul & Skrin (12) — spesifikasi lengkap

Setiap modul = satu Livewire component + Blade view. Navigasi sidebar kekal urutan & label sama (label melalui `__()` untuk dwibahasa).

### 6.1 Dashboard (`vDash`)
- 4 KPI: Jualan Bulan Ini (+ target), Jumlah Kos (tetap+berubah), Untung Bersih (+ margin%), Food Cost % (+ badge, + COGS).
- 3 KPI baris kedua: Break-even (jualan/bulan + /hari), Modal Buka (Capex + roiMonths), Pencapaian Target Sales (% + progress bar).
- Carta 1: **Doughnut** — pecahan kos ikut kategori + satu hirisan "Kos Tetap (gaji/sewa)".
- Carta 2: **Bar** — Jualan vs Kos vs Untung untuk 6 bulan terakhir.
- Penapis bulan (`type="month"`) di kepala halaman.

### 6.2 Scan Resit (`vScan`)
- Langkah 1: grid 8 `catbtn` kategori (pilih satu, kelas `.on`).
- Langkah 2: dropzone upload/kamera (`accept="image/*" capture="environment"`) → papar preview → jalankan **OCR Tesseract.js** (progress %).
  - OCR baca item baris `nama … 12.50` (skip total/cash/gst/round dll) + kesan "Total/Jumlah".
- Langkah 3: tarikh, jumlah (RM), senarai item boleh edit (auto-jumlah bila edit item), nota, butang **Rekod Kos**.
- **BARU:** simpan imej resit ke storage (`receipts.image_path`).
- Senarai resit bulan semasa (tarikh, kategori chip, ringkasan item, jumlah, padam) + penapis bulan.

### 6.3 Staff (`vStaff`)
- 4 KPI: Bilangan Staff (+ pecahan FT/PT), Jumlah Gaji Pokok, Jumlah Kos Majikan/Bulan (+EPF/SOCSO), Purata Kos/Staff.
- Borang tambah: Nama, Posisi, Jenis (Sepenuh/Separuh Masa), Gaji Pokok, EPF/SOCSO (Ya/Tidak).
- Jadual **boleh edit inline** (nama, posisi, jenis, gaji, EPF/SOCSO) + kolum Kos Majikan (auto) + padam + baris jumlah.
- Kos staff **auto masuk** ke Kos Tetap & PnL.

### 6.4 Kos Tetap (`vKos`)
- 3 KPI: Kos Tetap Bukan Gaji, Kos Staff (auto dari modul Staff), Jumlah Kos Tetap/Bulan.
- Borang tambah: Perkara, Kumpulan (Sewa/Utiliti/Langganan/Insurans/Ansuran/Lain-lain), Amaun.
- Jadual **boleh edit inline** (perkara, kumpulan, amaun) + padam + baris jumlah.

### 6.5 Sales (`vSales`)
- 3 KPI: Jualan Bulan Ini, Purata Harian, Hari Direkod.
- Borang tambah: Tarikh, Jualan (RM).
- Senarai jualan harian (padam) + Carta **line** trend harian. Penapis bulan.

### 6.6 Petty Cash / Tutup Harian (`vCash`)
- 3 KPI: Jualan Tunai Bulan Ini, Hari Direkod, Jumlah Beza/Variance (warna ikut +/−).
- Borang: Tarikh, Float Awal (default 100), Jualan Tunai, Bayaran Tunai (default 0), Kiraan Sebenar Laci.
- Jadual: tarikh, float, jualan tunai, bayaran tunai, **Sepatutnya**, **Sebenar**, **Beza** (warna) + padam. Penapis bulan.

### 6.7 Menu Food Cost (`vMenu`)
- 3 KPI: Bilangan Menu, Purata Margin Kasar (GP%), Sasaran Food Cost (≤35%).
- Borang tambah menu: Nama, Harga Jual.
- Jadual menu: nama (edit), harga (edit), kos bahan (auto), food cost % (+ badge), untung/unit, GP%, butang **Resepi**, padam.
- **Baris kembang (expand)** bila klik Resepi: senarai bahan (nama + kos, boleh edit), tambah bahan baharu, padam bahan, baris jumlah kos bahan.

### 6.8 Peralatan (`vPeralatan`)
- 3 KPI: Jumlah Kos Peralatan, Sudah Dibeli (hijau), Perlu Dibeli Lagi (+ progress bar % siap).
- Borang tambah: Peralatan, Kumpulan (Dapur/Ruang Makan/Operasi/Lain-lain), Kuantiti, Harga Unit.
- Jadual (dikumpul ikut kumpulan): checkbox status (perlu/beli — strikethrough bila beli), nama, kumpulan chip, qty, harga unit, jumlah, padam + baris jumlah keseluruhan.

### 6.9 Bajet Buka / Capex (`vBajet`)
- 1 KPI besar: Jumlah Modal Buka Diperlukan.
- Borang tambah: Perkara, Kos.
- Jadual: perkara, kos (edit inline), padam + baris JUMLAH MODAL.

### 6.10 ROI & Target (`vRoi`)
- 4 KPI: Modal Buka, Untung Bersih (sebenar), ROI Sebenar (bulan), ROI Sasaran (bulan).
- Borang Sasaran: Target Jualan Bulanan, Target Margin (%), Target Balik Modal (bulan) → simpan ke `outlets`.
- Nota dinamik: untung bersih & jualan diperlukan untuk capai sasaran ROI.
- Carta **line**: unjuran balik modal kumulatif 24 bulan (Sasaran vs Semasa vs garisan Modal).

### 6.11 Laporan Penuh (`vLap`)
- Butang **Cetak/Simpan PDF** (`window.print()`, guna `@media print`).
- Jadual **Penyata Untung Rugi (PnL)**: Jualan → COGS → Untung Kasar → Kos Berubah (pecahan ikut kategori) → Kos Tetap (Staff + Sewa & lain-lain) → Jumlah Perbelanjaan → **Untung Bersih** → Margin%.
- Kad Metrik Prestasi: Food Cost %, Break-even bulan, Break-even hari, Purata jualan harian.
- Kad Bajet & ROI: Modal Buka, ROI sebenar, target jualan/untung/ROI, pencapaian sales vs target.

### 6.12 Tetapan (`vSet`)
- Nama Outlet, Caruman EPF majikan (%), Caruman SOCSO majikan (%) → simpan.
- **Pilihan bahasa** (BM / English) → simpan ke `users.locale` (§13).
- Data: **Export JSON**, **Import JSON**, **Reset semua** (dengan confirm).

---

## 7. Keperluan Mobile Responsive (Wajib 100%)

Prototaip hanya kecilkan sidebar ke 64px pada ≤900px. Untuk mobile sebenar, tambah:

1. **Navigasi mobile:** sidebar jadi off-canvas (hamburger di top bar) ATAU **bottom tab bar** pada ≤640px. Sidebar penuh kekal untuk desktop.
2. **Jadual boleh skrol mendatar** (`overflow-x:auto`) dalam bekas — elak jadual pecah pada skrin kecil.
3. **Grid runtuh** ke 1 lajur pada ≤560px (`.g2/.g3/.g4` → 1fr).
4. **Borang `.row`** susun menegak pada mobile (sudah `flex-wrap`, pastikan `min-width` sesuai).
5. **Sasaran sentuh** ≥44px tinggi untuk butang & input.
6. **Carta** guna `responsive:true, maintainAspectRatio:false` dengan tinggi bekas tetap.
7. **Meta viewport** kekal; uji pada 360px, 390px, 768px, 1024px.
8. Input kamera resit guna `capture="environment"` (sudah ada) untuk snap terus di telefon.
9. **Toggle bahasa** mudah dicapai di top bar mobile & sidebar desktop.

---

## 8. Apa Yang Perlu Di-"Adjust" / Ditambah (gap prototaip → produk)

| # | Perkara | Sebab / Tindakan |
|---|---|---|
| 1 | localStorage → MySQL | Data kekal, boleh akses pelbagai peranti |
| 2 | Tiada auth → tambah **login/register** | Multi-pengguna; guard semua route |
| 3 | Satu set data → **multi-outlet** per user | Skala; pilih outlet aktif |
| 4 | `uid()` string → **PK auto-increment** | Integriti relational |
| 5 | Imej resit tak disimpan → **simpan ke storage** | Audit; `receipts.image_path` |
| 6 | Tiada validasi pelayan → **Form Request / rules Livewire** | Keselamatan data |
| 7 | Kira di klien → **kira di pelayan** (service) + hantar ke Chart.js | Konsisten, selamat |
| 8 | Mobile separa → **nav mobile penuh + jadual skrol** | Keperluan §7 |
| 9 | Reset/Import/Export JSON → laksana atas DB | Kekal ciri, tambah backup Excel (pilihan) |
| 10 | Zon waktu → set `Asia/Kuala_Lumpur` | Tarikh betul |
| 11 | Format RM → helper Blade `rm()` / directive `@rm` | Guna semula |
| 12 | Data contoh (objek DEFAULT) → **Seeder** | `OutletSeeder` isi staff/equipment/capex/fixed/menu contoh |
| 13 | OCR klien → kekal Tesseract.js (Alpine), ATAU pilihan pakej OCR pelayan | Nota risiko: OCR muat turun ~ beberapa MB kali pertama |
| 14 | Tiada CSRF/keselamatan → ikut default Laravel + policy per-outlet | Elak akses silang data |
| 15 | Cetak PDF pelayar → kekal `window.print()`; pilihan: jana PDF pelayar (dompdf) | Laporan boleh muat turun |
| 16 | **Tiada role → 2 role (Admin/Student)** | Kawalan akses & panel admin (§12) |
| 17 | **UI satu bahasa → dwibahasa BM+EN** | Semua label melalui `__()` + fail terjemahan (§13) |

---

## 9. Seni Bina Laravel (cadangan)

```
app/
 ├─ Models/            User (role,locale), Outlet, Staff, Equipment, CapexItem,
 │                     FixedCost, Receipt, ReceiptItem, Sale, Menu, MenuIngredient, CashRecord
 ├─ Services/          OutletMetrics.php   (semua formula §5)
 ├─ Http/Middleware/   EnsureOutlet.php, SetLocale.php (BARU), EnsureAdmin.php (BARU)
 ├─ Policies/          OutletPolicy.php
 ├─ Livewire/
 │   ├─ Dashboard.php        ScanReceipt.php     StaffManager.php
 │   ├─ FixedCosts.php       SalesManager.php    PettyCash.php
 │   ├─ MenuFoodCost.php     Equipment.php       CapexBudget.php
 │   ├─ RoiTarget.php        FullReport.php      Settings.php
 │   ├─ Admin/UserManager.php (BARU — panel admin)
 │   └─ Concerns/WithMonthFilter.php  (trait penapis bulan berkongsi)
config/outlet.php     (8 kategori + kumpulan enum + ambang badge)
lang/
 ├─ ms/…  ms.json      (Bahasa Melayu — default)
 └─ en/…  en.json      (English)
resources/
 ├─ css/app.css        (SALIN CSS prototaip 100% + tambahan mobile §7)
 ├─ js/app.js          (Chart.js init, Tesseract OCR, Alpine helpers)
 └─ views/
     ├─ layouts/app.blade.php   (sidebar + top bar mobile + toggle bahasa + @livewireStyles/Scripts)
     └─ livewire/*.blade.php     (satu per komponen)
database/
 ├─ migrations/       (13 jadual §4 + kolum role & locale pada users)
 └─ seeders/          DatabaseSeeder, OutletSeeder (data DEFAULT + 1 admin + 1 student)
routes/web.php        (auth + satu route per modul; kumpulan admin dilindungi EnsureAdmin)
```

**Interaksi state bulan:** guna Livewire property `$month` (default `now()->format('Y-m')`) + `wire:model.live`. Komponen kira metrik guna `OutletMetrics::for($outlet, $month)`.

**Carta:** hantar data dari Livewire ke Alpine (`@js(...)`) → init Chart.js dalam `x-init`. Guna Livewire event untuk refresh bila bulan/DB berubah.

---

## 10. Kriteria Terima (Acceptance)

1. Semua 12 modul wujud dengan label & susunan sama; visual sepadan prototaip.
2. Semua formula §5 menghasilkan nilai sama seperti prototaip (uji dengan data seeder).
3. CRUD berfungsi & kekal dalam MySQL; edit inline staff/kos/menu berfungsi.
4. OCR resit berfungsi di pelayar; imej resit tersimpan.
5. Penapis bulan menukar semua nilai & carta.
6. PnL, ROI, Break-even, Food Cost, Petty Cash variance tepat.
7. Responsive penuh pada 360–1024px; navigasi mobile berfungsi; jadual skrol.
8. Auth melindungi data; pengguna hanya nampak outlet sendiri.
9. Seeder mengisi data contoh identik dengan objek DEFAULT prototaip.
10. Boleh `git push` & deploy di Forge tanpa ralat (migrate + build).
11. **Role Admin & Student berfungsi:** Student hanya urus outlet sendiri; Admin boleh lihat/urus semua pengguna & outlet (§12).
12. **Dwibahasa berfungsi:** toggle BM↔EN menukar semua label UI; pilihan disimpan per-pengguna (§13).

---

## 11. Deployment (GitHub + Forge)

- Repo GitHub (private). `.env.example` disediakan; jangan commit `.env`.
- Forge: sambung repo, pilih PHP 8.4, MySQL 8.4, domain.
- **Deploy script:** `composer install --no-dev`, `php artisan migrate --force`, `npm ci && npm run build`, `php artisan optimize`, `php artisan storage:link`.
- Set `APP_TIMEZONE=Asia/Kuala_Lumpur`, `APP_LOCALE=ms`, `APP_ENV=production`, `APP_DEBUG=false`.
- Aktifkan queue/scheduler hanya jika perlu (tiada keperluan kritikal buat masa ini).

---

## 12. Roles & Kawalan Akses (BARU)

Sistem ada **2 role** disimpan dalam `users.role`:

### 12.1 Student (default)
- Peranan asas pengguna. Setiap student **memiliki outlet sendiri** (`outlets.user_id`).
- Boleh guna **semua 12 modul** tetapi hanya untuk **outlet miliknya sendiri** (dikuatkuasa `OutletPolicy` + scope `where('user_id', auth()->id())`).
- Tidak nampak data student lain. Tiada akses panel admin.
- Boleh tukar Tetapan outlet sendiri & bahasa sendiri.

### 12.2 Admin
- Kuasa penuh (superuser). Boleh **lihat & urus semua pengguna dan semua outlet**.
- Panel admin baharu: `App\Livewire\Admin\UserManager` — senarai pengguna, cipta/edit/padam, tetapkan role, reset kata laluan, "impersonate/lihat sebagai" outlet student (pilihan).
- Boleh masuk mana-mana outlet untuk semak/betulkan data (audit/mentor).
- Route admin dilindungi middleware `EnsureAdmin` (semak `auth()->user()->role === 'admin'`), atau Gate `admin`.

### 12.3 Penguatkuasaan
- **Middleware/Gate:** `EnsureAdmin` untuk kumpulan route `/admin/*`.
- **Policy:** `OutletPolicy@view/update/delete` — benarkan jika `user->role==='admin'` **atau** `outlet->user_id===user->id`.
- **Navigasi:** pautan "Pengurusan Pengguna" hanya papar untuk admin (`@can`/`@if(auth()->user()->isAdmin())`).
- **Seeder:** cipta 1 admin demo (`admin@pakmaon.test`) + 1 student demo (`student@pakmaon.test`) dengan outlet contoh.
- Helper model: `User::isAdmin(): bool`, `User::isStudent(): bool`.

### 12.4 Matriks keizinan (ringkas)

| Keupayaan | Student | Admin |
|---|---|---|
| Guna 12 modul untuk outlet sendiri | ✅ | ✅ |
| Lihat/urus outlet pengguna lain | ❌ | ✅ |
| Panel Pengurusan Pengguna (CRUD user, set role) | ❌ | ✅ |
| Tukar bahasa & tetapan sendiri | ✅ | ✅ |
| Export/Import/Reset data outlet sendiri | ✅ | ✅ |

---

## 13. Dwibahasa / Localization (BARU)

Sistem mesti **dwibahasa: Bahasa Melayu (default) & English**.

### 13.1 Pendekatan
- Guna **Laravel Localization**: semua teks UI melalui `__('key')` atau `@lang('key')` — **tiada teks hardcoded** dalam Blade/Livewire.
- Fail terjemahan: `lang/ms.json` & `lang/en.json` (atau berkumpulan `lang/ms/outlet.php`, `lang/en/outlet.php`). Kunci berdasarkan label prototaip (contoh: `"Jualan Bulan Ini"`, `"Untung Bersih"`, `"Food Cost %"`, dll.).
- Label kategori kos, kumpulan enum, badge, nama modul & semua KPI **mesti ada dalam kedua-dua fail**.

### 13.2 Simpan & tukar bahasa
- Kolum `users.locale` (`ms`/`en`) simpan pilihan pengguna.
- Middleware `SetLocale`: `App::setLocale(auth()->user()?->locale ?? session('locale', config('app.locale')))` pada setiap request.
- Toggle bahasa di top bar (mobile) & sidebar (desktop) → Livewire action / route `POST /locale` yang kemas kini `users.locale` + `session('locale')`, kemudian refresh.
- Tetamu (belum login) guna `session('locale')`; default `ms`.

### 13.3 Format bergantung bahasa
- Mata wang kekal `RM1,234.56` (`en-MY`) untuk kedua-dua bahasa (standard Malaysia).
- Tarikh boleh guna `Carbon::locale()` untuk paparan mesra (pilihan).
- Nombor guna pemisah ribuan konsisten.

### 13.4 Kriteria terima i18n
- Menukar toggle BM↔EN menukar **semua** label nav, KPI, borang, jadual, badge & mesej toast serta-merta.
- Tiada kunci terjemahan hilang (tiada teks mentah `key.name` terpapar).
- Pilihan bahasa kekal selepas log keluar/masuk semula (disimpan di `users.locale`).
