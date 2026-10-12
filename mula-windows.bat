@echo off
REM Jalankan webapp Pak Maon Western (selepas setup-windows.bat pernah dijalankan).
cd /d "%~dp0"
title Pak Maon Western - Server
if not exist vendor\autoload.php (
    echo Projek belum disediakan. Double-click setup-windows.bat dahulu.
    pause
    exit /b 1
)
echo ===================================================
echo  Webapp: http://127.0.0.1:8000
echo  Log masuk: demo@pakmaon.test / password
echo  JANGAN tutup tetingkap ini. Tekan Ctrl+C untuk berhenti.
echo ===================================================
start "" cmd /c "timeout /t 3 >nul & start http://127.0.0.1:8000"
call php artisan serve
pause
