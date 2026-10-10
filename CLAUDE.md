# CLAUDE.md — Pak Maon Western

Panduan untuk Claude Code bila bekerja dalam repo ini. Baca `README.md` untuk gambaran penuh.

## Stack

Laravel 12 · Livewire 3 + Alpine · Breeze (Volt) · Vite · Chart.js · Tesseract.js.
DB: SQLite (dev) / MySQL 8.4 (production di Laravel Forge). Production guna PHP 8.4.

## Arahan biasa

```bash
php artisan test          # wajib lulus sebelum commit
vendor/bin/pint           # format kod (PSR-12) — jalan sebelum commit
npm run build             # build asset selepas ubah CSS/JS
php artisan migrate       # selepas tambah migration
```

Log masuk demo: `demo@pakmaon.test` / `password`.

## Struktur penting

- `app/Livewire/*` — satu komponen setiap modul (Dashboard, ScanReceipt, StaffManager, ...);
  view di `resources/views/livewire/*.blade.php`. Route di `routes/web.php`.
- `app/Services/OutletMetrics.php` — SEMUA pengiraan kewangan. Jangan kira di view/komponen.
- `app/Policies/OutletPolicy.php` — data terikat pada outlet milik user. Setiap query
  mesti di-scope ke outlet semasa; jangan bocor data antara user/outlet.
- `app/Support/DefaultOutletData.php` — data default (seeder & pendaftaran user baharu).
- `docs/PRD.md` — spesifikasi; `docs/pakmaon-outlet.html` — prototaip asal.

## Peraturan

- Label UI dalam **Bahasa Melayu**. Wang guna `decimal` & papar dengan `rm()` / `@rm` (`RM1,234.56`).
- Zon waktu `Asia/Kuala_Lumpur`.
- Ubahan kecil & fokus; tambah/kemas kini ujian dalam `tests/Feature` bila ubah logik.
- Jangan sunting migration lama yang sudah di-deploy — cipta migration baharu.
- Jangan commit `.env`, `database/database.sqlite`, `vendor/`, `node_modules/`, `public/build/`.

## Git

- **Jangan push ke `main`** dan jangan merge. Kerja dalam branch sendiri, push branch itu,
  dan biar pemilik repo semak & merge (merge ke `main` = deploy ke production melalui Forge).
- Mesej commit ringkas gaya `feat: ...`, `fix: ...`, `style: ...`.
