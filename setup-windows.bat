@echo off
REM ---------------------------------------------------------------------------
REM Setup automatik Pak Maon Western untuk Windows (test local sahaja).
REM Double-click fail ini. Perlu: Laravel Herd (PHP 8.4 + Composer) dan Node.js.
REM Selamat dijalankan semula - langkah yang dah siap akan dilangkau.
REM ---------------------------------------------------------------------------
setlocal
cd /d "%~dp0"
title Pak Maon Western - Setup

echo.
echo ==== [1/8] Semak alat yang diperlukan ====
where php >nul 2>nul || (echo [X] PHP tidak dijumpai. Pasang Laravel Herd dan pilih PHP 8.4. & goto gagal)
where composer >nul 2>nul || (echo [X] Composer tidak dijumpai. Pasang Laravel Herd. & goto gagal)
where npm >nul 2>nul || (echo [X] Node.js tidak dijumpai. Pasang dari https://nodejs.org & goto gagal)
echo OK

echo.
echo ==== [2/8] Pasang library PHP (composer install) ====
call composer install --no-interaction || goto gagal

echo.
echo ==== [3/8] Sediakan fail .env ====
if not exist .env copy .env.example .env >nul
findstr /b /c:"APP_KEY=base64" .env >nul || (call php artisan key:generate --force || goto gagal)
echo OK

echo.
echo ==== [4/8] Sediakan database ====
set SEED=
if not exist database\database.sqlite (
    type nul > database\database.sqlite
    set SEED=1
)
call php artisan migrate --force || goto gagal

echo.
echo ==== [5/8] Isi data demo (sekali sahaja) ====
if defined SEED (call php artisan db:seed --force || goto gagal) else (echo Dilangkau - data sudah ada)

echo.
echo ==== [6/8] Pasang library paparan (npm) ====
call npm ci --no-audit --no-fund || goto gagal

echo.
echo ==== [7/8] Build CSS/JS ====
call npm run build || goto gagal

echo.
echo ==== [8/8] Sambung folder gambar resit ====
if not exist public\storage (call php artisan storage:link || goto gagal) else (echo OK)

echo.
echo ===================================================
echo  SETUP SIAP! Webapp akan dibuka dalam browser.
echo  Log masuk: demo@pakmaon.test / password
echo  JANGAN tutup tetingkap ini semasa guna webapp.
echo ===================================================
start "" cmd /c "timeout /t 3 >nul & start http://127.0.0.1:8000"
call php artisan serve
goto :eof

:gagal
echo.
echo ===================================================
echo  SETUP GAGAL di langkah di atas.
echo  Ambil gambar skrin tetingkap ini dan hantar kepada Claude.
echo ===================================================
pause
exit /b 1
