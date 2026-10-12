# Pak Maon Western — Sistem Pengurusan Outlet

Aplikasi web untuk merancang & mengurus outlet makanan Western (F&B) dari A–Z:
kos permulaan (Capex), peralatan, staff, kos operasi, jualan, tutup kira harian,
food cost setiap menu, dan penyata PnL/ROI — semua pengiraan automatik mengikut
bulan dipilih.

> Migrasi 1:1 daripada prototaip `pakmaon-outlet.html` (single-file HTML/JS +
> localStorage) ke aplikasi Laravel + Livewire + pangkalan data, mesra mobile penuh.

## Stack

| Lapisan | Teknologi |
|---|---|
| Framework | Laravel 12 · PHP 8.4 |
| UI dinamik | Livewire 3 + Alpine.js (dibundle) |
| Auth | Laravel Breeze (Livewire/Volt) |
| Pangkalan data | MySQL 8.4 (production) · SQLite (local dev) |
| Carta | Chart.js 4.4.1 |
| OCR resit | Tesseract.js 5 (jalan di pelayar) |
| Build asset | Vite |
| Deploy | GitHub → Laravel Forge |

## Modul (12)

Dashboard · Scan Resit (OCR) · Staff · Kos Tetap · Sales · Petty Cash ·
Menu Food Cost · Peralatan · Bajet Buka · ROI & Target · Laporan Penuh · Tetapan.

Semua data terikat pada **outlet** milik user (multi-tenant, dilindungi `OutletPolicy`).
Pengiraan kewangan berpusat dalam `App\Services\OutletMetrics`.

## Keperluan

- PHP **8.4**
- Composer 2
- Node.js 20+ & npm
- MySQL **8.4** (production) — SQLite memadai untuk local dev

## Setup Local (SQLite)

```bash
git clone <repo-url> pakmaon-western
cd pakmaon-western

composer install
cp .env.example .env
php artisan key:generate

# Guna SQLite untuk dev (DB_CONNECTION=sqlite dalam .env)
touch database/database.sqlite
php artisan migrate --seed          # cipta jadual + user demo & data contoh

npm install
npm run build                       # atau: npm run dev

php artisan storage:link            # supaya imej resit boleh dipapar
php artisan serve
```

Log masuk demo: **demo@pakmaon.test** / **password**
(User baharu yang mendaftar akan diberi data DEFAULT secara automatik.)

### Windows (automatik)

Pasang **Laravel Herd** (PHP 8.4 + Composer) dan **Node.js**, kemudian double-click
`setup-windows.bat` (kali pertama). Lain kali, double-click `mula-windows.bat`
untuk buka webapp di http://127.0.0.1:8000.

### Guna MySQL secara local

Set dalam `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pakmaon_western
DB_USERNAME=root
DB_PASSWORD=
```

kemudian `php artisan migrate --seed`.

## Ujian & Format

```bash
php artisan test        # unit OutletMetrics + smoke semua modul
vendor/bin/pint         # format kod (PSR-12)
```

## Zon waktu & bahasa

- `APP_TIMEZONE=Asia/Kuala_Lumpur`
- `APP_LOCALE=ms` — semua label UI dalam Bahasa Melayu
- Format wang `RM1,234.56` (`en-MY`) — helper `rm()` / directive `@rm`

## Deploy ke Laravel Forge

1. Cipta repo GitHub (private), `git push`.
2. Di Forge: sambung repo, pilih **PHP 8.4** & **MySQL 8.4**, tetapkan domain.
3. Isi `.env` production: `APP_ENV=production`, `APP_DEBUG=false`,
   `APP_TIMEZONE=Asia/Kuala_Lumpur`, kredensial `DB_*` MySQL.
4. `php artisan key:generate` (sekali sahaja) & pastikan `APP_KEY` ada.
5. Salin `deploy.sh` ke **Deploy Script** Forge (ringkasan):

   ```bash
   cd $FORGE_SITE_PATH
   git pull origin $FORGE_SITE_BRANCH
   composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
   php artisan migrate --force
   npm ci && npm run build
   php artisan storage:link
   php artisan optimize
   ```

6. Deploy. (Queue/scheduler tidak diperlukan buat masa ini.)

## Nota

- **Imej resit** disimpan ke `storage/app/public/receipts` (perlukan `storage:link`).
- **Export/Import JSON** dalam modul Tetapan serasi dua hala dengan prototaip asal.
- Data DEFAULT (staff, peralatan, capex, kos tetap, menu) diurus dalam
  `App\Support\DefaultOutletData` dan digunakan oleh seeder & pendaftaran user.
