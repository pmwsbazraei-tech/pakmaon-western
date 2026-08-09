# Prompt untuk Claude Code — Bina "Pak Maon Western" (Laravel + Livewire)

> Salin keseluruhan fail ini sebagai prompt pertama dalam Claude Code, di dalam folder projek.
> Rujukan wajib dalam folder: `pakmaon-outlet.html` (prototaip UI/logik) dan `PRD.md` (spesifikasi penuh v1.1).

---

## PERANAN & MATLAMAT

Kamu ialah jurutera Laravel senior. Bina semula prototaip `pakmaon-outlet.html` menjadi aplikasi web sebenar yang **meniru 100% UI dan fungsi**, tetapi guna pangkalan data, mesra mobile penuh, **dwibahasa (BM + EN)**, dan ada **2 role (Admin & Student)**.

**Stack wajib:** Laravel terkini (12.x) · PHP 8.4 · MySQL 8.4 · Livewire 3 · Blade · Alpine.js · Vite · Chart.js 4.4.1 · Tesseract.js 5 · Laravel Localization.

**Peraturan mutlak:**
1. Baca `pakmaon-outlet.html` dan `PRD.md` sepenuhnya SEBELUM menulis kod. Jangan tinggalkan mana-mana modul/fungsi.
2. **Salin CSS prototaip 100%** ke `resources/css/app.css` (blok `:root` + semua kelas). JANGAN tulis semula guna Tailwind. Padanan visual mesti tepat.
3. Kekalkan semua label, ikon, susunan, dan formula PERSIS seperti prototaip — tetapi **semua teks UI mesti melalui `__()`** (tiada hardcode) supaya boleh tukar BM↔EN.
4. Semua nilai wang guna `decimal`, format paparan `RM1,234.56` (`en-MY`).
5. Zon waktu `Asia/Kuala_Lumpur`, `APP_LOCALE=ms`.
6. Selepas setiap fasa, jalankan `php artisan test` / `vendor/bin/pint` dan pastikan tiada ralat sebelum teruskan.

> **Nota:** Jika folder sudah ada projek separa siap (`pakmaon-western/`), semak dahulu apa yang wujud, kemudian **tambah yang berkurang** (terutamanya Fasa 1b role, Fasa 3b i18n, Fasa 4b panel admin) tanpa memecahkan modul sedia ada.

---

## FASA 0 — Setup Projek

```bash
laravel new pakmaon-western   # atau composer create-project
cd pakmaon-western
composer require livewire/livewire
php artisan livewire:install
```

- Konfigur `.env`: `DB_CONNECTION=mysql`, DB 8.4, `APP_TIMEZONE=Asia/Kuala_Lumpur`, `APP_LOCALE=ms`, `APP_FALLBACK_LOCALE=en`.
- Pasang auth ringkas (Laravel Breeze **Livewire stack**, atau Fortify) untuk login/register.
- Sediakan Vite: import `resources/css/app.css` & `resources/js/app.js` dalam layout.
- Tambah Chart.js & Tesseract.js (npm `chart.js`, `tesseract.js`, atau CDN dalam layout).
- Commit awal: `chore: scaffold laravel + livewire + auth`.

---

## FASA 1 — Pangkalan Data (13 jadual)

Cipta migration + model Eloquent (dengan `$fillable`, casts decimal, relationship) untuk:

`outlets, staff, equipment, capex_items, fixed_costs, receipts, receipt_items, sales, menus, menu_ingredients, cash_records` (+ `users` sedia ada).

Ikut skema tepat di **PRD §4**. Perhatian:
- `outlets` menampung `settings` (name, target_sales, target_margin, target_roi_months, epf_pct, socso_pct).
- `receipts.image_path` **nullable** (ciri BARU simpan imej resit).
- Enum ikut PRD (staff.type, equipment.group/status, fixed_costs.group, receipts.category).
- Relationship: `Outlet hasMany` semua; `Receipt hasMany ReceiptItem`; `Menu hasMany MenuIngredient`; cascade delete anak.
- Policy `OutletPolicy` supaya user hanya akses outlet sendiri (kecuali admin — lihat Fasa 1b).

Kategori kos (8) letak dalam `config/outlet.php` sebagai array `[id => [nama, ikon, cogs bool]]` — JANGAN jadikan jadual. Sertakan juga senarai enum kumpulan & ambang badge food cost (35/45).

Commit: `feat: database schema + models`.

### FASA 1b — Roles pada users (BARU)

- Tambah migration: kolum `role` enum('admin','student') default 'student', dan `locale` string(5) default 'ms' pada jadual `users`.
- Model `User`: masukkan `role`, `locale` dalam `$fillable`; tambah kaedah `isAdmin(): bool` & `isStudent(): bool`.
- Gate/Middleware `EnsureAdmin` (semak `role==='admin'`), daftar alias `admin` di `bootstrap/app.php`.
- Kemas kini `OutletPolicy`: benarkan jika `user->isAdmin()` **atau** `outlet->user_id === user->id`.

Commit: `feat: user roles (admin/student) + policy`.

---

## FASA 2 — Logik Pengiraan (Service)

Cipta `app/Services/OutletMetrics.php` dengan kaedah `for(Outlet $outlet, string $month): array` yang memulangkan **semua** nilai dalam PRD §5:

`varTotal, varByCat, cogs, otherFixed, staffCost, fixedTotal, sales, expenses, net, capex, foodCostPct, marginPct, beSales, roiMonths` + pembantu `staffTotal(Staff)`, `menuCost(Menu)`, `targetNet/targetRoi/neededNet/neededSales`, `cashExpected/cashDiff`.

- Guna `whereYear+whereMonth` atau `where('date','like',"$month%")` untuk tapis bulan.
- **Tulis unit test** yang bandingkan output dengan nilai dijangka (guna data seeder) — ini kunci "tiru 100%".

Contoh nilai rujukan untuk ujian (dari data contoh):
- Kos staff (gaji 8700, sebahagian EPF/SOCSO) ≈ ikut kadar 13% + 1.75%.
- Menu "Chicken Chop" (harga 15.90, bahan 6.00) → food cost 37.7%, GP 62.3%, untung/unit 9.90.
- Petty cash: float 100 + jualan 850 − bayaran 50 = 900; jika sebenar 895 → beza −5.

Commit: `feat: OutletMetrics service + tests`.

---

## FASA 3 — Layout & Navigasi (mobile-first)

`resources/views/layouts/app.blade.php`:
- Sidebar desktop (230px) — **salin struktur `.sidebar/.brand/.nav`** dari prototaip; 12 pautan ikut susunan: Dashboard, Scan Resit, Staff, Kos Tetap, Sales, Petty Cash, Menu Food Cost, Peralatan, Bajet Buka, ROI & Target, Laporan Penuh, Tetapan.
- Pautan "Pengurusan Pengguna" (admin sahaja) di bawah, guna `@if(auth()->user()->isAdmin())`.
- **Mobile (≤640px):** hamburger + off-canvas sidebar ATAU bottom tab bar (guna Alpine untuk toggle). Lihat PRD §7.
- Pautan aktif guna kelas `.active`.
- **Toggle bahasa (BM/EN)** di top bar mobile & sidebar desktop.
- Sertakan `@livewireStyles`, `@livewireScripts`, Chart.js, Tesseract.js.

Tambah CSS mobile (PRD §7) di hujung `app.css`: nav mobile, `.table-wrap{overflow-x:auto}`, grid runtuh ≤560px, sasaran sentuh ≥44px, carta `maintainAspectRatio:false`.

Routing: guna satu route per modul (contoh `/dashboard`, `/scan`, …) yang render Livewire full-page component. Lindungi semua dengan `auth` middleware; kumpulan `/admin/*` dengan `admin`.

Commit: `feat: layout + responsive navigation`.

### FASA 3b — Dwibahasa / i18n (BARU)

- Cipta fail terjemahan `lang/ms.json` & `lang/en.json` (atau `lang/{ms,en}/outlet.php`). Kunci = setiap label UI dari prototaip: nav (12 modul), tajuk & subtajuk halaman, semua label KPI, label borang, tajuk kolum jadual, teks badge (Sihat/Baik/Tinggi/Bahaya/Rugi), butang, mesej toast/validasi, label kategori (8) & kumpulan enum.
- Ganti **semua** teks Blade/Livewire kepada `__('...')`. Tiada teks mentah.
- Middleware `SetLocale`: `App::setLocale(auth()->user()?->locale ?? session('locale', config('app.locale')))`.
- Livewire action / route `POST /locale/{lang}` → kemas kini `users.locale` + `session('locale')`, kemudian redirect balik.
- Uji: toggle BM↔EN menukar semua label serta-merta; tiada kunci hilang.

Commit: `feat: bilingual (BM/EN) localization`.

---

## FASA 4 — Livewire Components (12 modul)

Bina satu per satu, uji setiap satu. Gunakan trait `WithMonthFilter` (property `$month`, `wire:model.live`) untuk modul yang ada penapis bulan (Dashboard, Scan, Sales, Petty Cash, ROI, Laporan).

Semua komponen mesti **scope pada outlet pengguna** (`$this->outlet` = outlet milik `auth()->id()`; admin boleh pilih outlet lain). Rujuk **PRD §6** untuk kandungan tepat setiap skrin. Ringkasan komponen:

1. **Dashboard** — 4+3 KPI, doughnut (pecahan kos + kos tetap), bar 6-bulan (jualan/kos/untung). Data carta → Alpine via `@js`.
2. **ScanReceipt** — pilih kategori (catgrid), upload imej (`wire:model` file), **OCR Tesseract.js jalan di Alpine** (bukan pelayan), papar item boleh edit, simpan resit + item + **imej ke storage**, senarai resit bulan.
3. **StaffManager** — KPI FT/PT, borang tambah, **jadual edit inline** (wire:model per baris atau simpan-atas-blur), kos majikan auto.
4. **FixedCosts** — 3 KPI (termasuk kos staff auto), borang, jadual edit inline.
5. **SalesManager** — 3 KPI, borang, senarai, carta line trend harian.
6. **PettyCash** — 3 KPI (variance), borang, jadual (sepatutnya/sebenar/beza berwarna).
7. **MenuFoodCost** — 3 KPI, borang menu, jadual + **baris kembang Resepi** (bahan CRUD, jumlah auto).
8. **Equipment** — 3 KPI + progress, borang, jadual dikumpul ikut kumpulan, checkbox status (strikethrough).
9. **CapexBudget** — 1 KPI, borang, jadual edit inline + jumlah.
10. **RoiTarget** — 4 KPI, borang sasaran (simpan ke outlet), nota dinamik, carta line unjuran 24 bulan.
11. **FullReport** — jadual PnL penuh, kad metrik, kad bajet/ROI, butang cetak (`window.print`).
12. **Settings** — nama outlet + EPF/SOCSO, **pilihan bahasa**, Export/Import JSON, Reset (dengan pengesahan).

**Validasi:** setiap borang guna rules Livewire (`required`, `numeric`, `min:0`, `date`, `in:...`).

Selepas setiap komponen: commit `feat: <modul> livewire component`.

### FASA 4b — Panel Admin (BARU)

- `App\Livewire\Admin\UserManager` (route `/admin/users`, middleware `admin`):
  - Senarai semua pengguna (nama, email, role, bilangan outlet).
  - Cipta / edit / padam pengguna; tetapkan role (admin/student); reset kata laluan.
  - Pilihan: "Lihat sebagai" / masuk outlet mana-mana student untuk audit/mentor.
- Papar pautan panel hanya untuk admin.
- Uji: student cuba akses `/admin/*` → 403; admin boleh urus semua.

Commit: `feat: admin user management panel`.

---

## FASA 5 — Data Contoh (Seeder)

`OutletSeeder` isi data **identik** dengan objek `DEFAULT` prototaip:
- 1 outlet contoh (Pak Maon Western, target 30000/20%/12 bulan, EPF 13, SOCSO 1.75).
- 4 staff, 12 equipment, 7 capex, 3 fixed cost, 4 menu (dengan bahan seperti prototaip).
- `receipts/sales/cash` kosong (macam prototaip).

`DatabaseSeeder` cipta:
- **1 admin demo** (`admin@pakmaon.test`, role admin).
- **1 student demo** (`student@pakmaon.test`, role student) + panggil `OutletSeeder` untuk outlet miliknya.

Commit: `feat: seeders (admin + student demo)`.

---

## FASA 6 — Responsif, i18n & QA

- Uji lebar 360/390/768/1024px: nav mobile, jadual skrol, grid runtuh, carta.
- Uji setiap formula vs prototaip (jalankan tests).
- Uji OCR di pelayar; pastikan imej resit tersimpan & dipapar.
- Uji edit inline, padam, penapis bulan, cetak PDF.
- **Uji role:** student tak nampak data orang lain & ditolak dari `/admin/*`; admin boleh urus semua.
- **Uji dwibahasa:** toggle BM↔EN tukar semua label; pilihan kekal selepas login semula; tiada kunci terjemahan hilang.
- `vendor/bin/pint` (format), betulkan amaran.

Commit: `test: responsive + i18n + role checks`.

---

## FASA 7 — GitHub & Forge

```bash
git init && git add -A && git commit -m "feat: initial release"
gh repo create pakmaon-western --private --source=. --push   # atau manual
```

Sediakan untuk **Laravel Forge**:
- `.env.example` lengkap (tanpa rahsia) — termasuk `APP_LOCALE=ms`, `APP_FALLBACK_LOCALE=en`, `APP_TIMEZONE=Asia/Kuala_Lumpur`.
- Deploy script Forge:
  ```
  cd $FORGE_SITE_PATH
  git pull origin $FORGE_SITE_BRANCH
  composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
  php artisan migrate --force
  npm ci && npm run build
  php artisan storage:link
  php artisan optimize
  ```
- Nota README: PHP 8.4, MySQL 8.4, langkah `.env`, `php artisan key:generate`, akaun demo (admin & student).

Commit akhir: `docs: readme + forge deploy`.

---

## SENARAI SEMAK "JANGAN TERCICIR"

- [ ] 12 modul + label & susunan sama
- [ ] CSS prototaip disalin 100% (tema simen + kuning lemon)
- [ ] 8 kategori kos (2 COGS: bahan, packaging)
- [ ] Formula: staffTotal, cogs, fixedTotal, net, capex, foodCostPct, marginPct, beSales, roiMonths, menuCost/gpPct, targetNet/targetRoi/neededNet/neededSales, cashExpected/cashDiff
- [ ] Ambang badge food cost 35/45
- [ ] Edit inline: Staff, Kos Tetap, Menu, Capex
- [ ] Resepi kembang (menu ingredients CRUD)
- [ ] Peralatan dikumpul + checkbox status + progress
- [ ] OCR Tesseract.js + simpan imej resit (BARU)
- [ ] Penapis bulan pada Dashboard/Scan/Sales/PettyCash/ROI/Laporan
- [ ] Carta: doughnut, bar 6-bulan, line jualan, line ROI 24-bulan
- [ ] PnL penuh + cetak PDF
- [ ] Export/Import JSON + Reset
- [ ] Auth + multi-outlet + policy
- [ ] **2 role (Admin/Student) + EnsureAdmin + panel Pengurusan Pengguna**
- [ ] **Dwibahasa BM+EN — semua label `__()`, toggle bahasa, simpan users.locale**
- [ ] Mobile penuh (nav, jadual skrol, grid runtuh)
- [ ] Seeder = data DEFAULT + akaun admin & student demo
- [ ] GitHub + Forge deploy script

Bina fasa demi fasa, commit kerap, dan sahkan setiap fasa sebelum teruskan.
