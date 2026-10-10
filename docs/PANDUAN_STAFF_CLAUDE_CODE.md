# Panduan Staff — Ubah Webapp Pak Maon Western guna Claude Code

Cara paling mudah: guna **Claude Code di web** (claude.ai/code). Tak perlu pasang
PHP, Composer, Node atau apa-apa di laptop — semuanya jalan di cloud.

## Sekali sahaja (setup oleh pemilik)

1. Jemput staff sebagai **collaborator** repo GitHub `pakmaon-western`
   (GitHub → Settings → Collaborators). Akses **Write** cukup.
2. Staff perlu akaun Claude (Pro/Max/Team) dan sambung GitHub di claude.ai/code.
3. (Disyorkan) Di GitHub → Settings → Branches, tambah **branch protection** pada
   `main`: wajib Pull Request sebelum merge. Ini pastikan hanya pemilik boleh
   merge = deploy ke production.

## Setiap kali nak buat perubahan (staff)

1. Buka **claude.ai/code**, pilih repo `pakmaon-western`, mulakan sesi baharu.
2. Projek akan disediakan secara automatik (pasang dependency, DB demo, build) —
   tunggu sebentar di awal sesi.
3. Tulis arahan dalam bahasa biasa, contoh:
   - "Tambah kolum Catatan pada modul Petty Cash."
   - "Betulkan jumlah di Laporan Penuh — tak tolak kos petty cash."
   - "Tukar warna butang Simpan jadi hijau."
4. Minta Claude semak: **"Jalankan test dan pint, pastikan semua lulus."**
5. Bila puas hati: **"Commit dan push ke branch baharu, kemudian buat Pull Request."**
6. Beritahu pemilik link Pull Request tersebut. **Jangan merge sendiri.**

## Pemilik

Semak Pull Request di GitHub → **Merge** → Forge akan auto-deploy ke production.

## Tips

- Satu sesi = satu tugasan. Tugasan baru → sesi baru.
- Terangkan dengan jelas: modul mana, apa yang patut berlaku, contoh nombor.
- Peraturan projek untuk Claude ada dalam `CLAUDE.md` di root repo — Claude baca
  fail ini secara automatik.
