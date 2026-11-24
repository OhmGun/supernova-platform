@echo off
title Supernova Platform Provisioning - Setup
echo ============================================================
echo  Supernova Platform Provisioning - Setup Windows
echo ============================================================
echo.
echo Folder ini akan menyiapkan runtime portable:
echo   - PHP (pakai PHP yang sudah terinstall di sistem)
echo   - MariaDB portable (disalin dari XAMPP atau unduh otomatis)
echo.
echo Pastikan PHP ada di PATH (cek: php -v).
php -v >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP tidak ditemukan di PATH.
    echo Silakan install PHP 8 dari https://windows.php.net/download
    echo atau set PATH ke folder php.exe.
    pause
    exit /b 1
)
echo [OK] PHP ditemukan.
echo.
echo Menyiapkan MariaDB portable...
if exist "%~dp0mariadb\bin\mysqld.exe" (
    echo [OK] MariaDB portable sudah ada.
) else (
    echo Menyalin MariaDB dari XAMPP (jika ada)...
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        mkdir "%~dp0mariadb" 2>nul
        xcopy /E /I /Y /Q "C:\xampp\mysql" "%~dp0mariadb" >nul
        echo [OK] MariaDB disalin dari XAMPP.
    ) else (
        echo [ERROR] Tidak menemukan MariaDB di C:\xampp\mysql
        echo Silakan unduh MariaDB portable dan letakkan di folder windows\mariadb
        pause
        exit /b 1
    )
)
echo.
echo Setup selesai. Jalankan start.bat untuk memulai.
pause
