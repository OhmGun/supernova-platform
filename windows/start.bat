@echo off
title Supernova Platform Provisioning
echo ============================================================
echo  Supernova Platform Provisioning
echo  http://localhost:8080
echo  Tekan CTRL+C untuk menghentikan server
echo ============================================================
echo.

rem ---------- Lokasi aplikasi (folder project) ----------
set APP_DIR=%~dp0..
set WIN_DIR=%~dp0

rem ---------- Pastikan PHP ada ----------
php -v >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP tidak ditemukan di PATH.
    echo Set PATH ke folder php.exe lalu jalankan ulang.
    pause
    exit /b 1
)

rem ---------- Cek MariaDB portable ----------
if not exist "%WIN_DIR%mariadb\bin\mysqld.exe" (
    echo [ERROR] MariaDB portable tidak ditemukan.
    echo Jalankan setup.bat dulu untuk menyiapkan runtime.
    pause
    exit /b 1
)

rem ---------- Data dir MariaDB ----------
rem Data disalin dari XAMPP saat setup.bat, jadi windows\mariadb\data sudah berisi supernova_olt.
set DATA_DIR=%WIN_DIR%mariadb\data
set MYSQL_PORT=3306
set MYSQL_SOCKET=%WIN_DIR%mysql.sock

if not exist "%DATA_DIR%mysql" (
    echo [ERROR] Folder data MariaDB tidak ditemukan.
    echo Jalankan setup.bat dulu (akan menyalin MariaDB + data dari XAMPP).
    pause
    exit /b 1
)

echo [DB] Menjalankan MariaDB di port %MYSQL_PORT% (datadir: %DATA_DIR%)...
start "Supernova-MariaDB" /min "%WIN_DIR%mariadb\bin\mysqld.exe" --no-defaults --datadir="%DATA_DIR%" --port=%MYSQL_PORT% --socket="%MYSQL_SOCKET%" --skip-grant-tables

rem Tunggu DB siap (max 15 detik)
set /a tries=0
:waitdb
"%WIN_DIR%mariadb\bin\mysql.exe" -h 127.0.0.1 -P %MYSQL_PORT% -u root -e "SELECT 1" >nul 2>&1
if not errorlevel 1 goto dbok
set /a tries+=1
if %tries% GEQ 15 (
    echo [ERROR] MariaDB tidak bisa start. Cek data folder.
    pause
    exit /b 1
)
timeout /t 1 /nobreak >nul
goto waitdb
:dbok
echo [OK] MariaDB siap.

rem ---------- Pastikan database supernova_olt ada ----------
echo [DB] Memastikan database supernova_olt...
"%WIN_DIR%mariadb\bin\mysql.exe" -h 127.0.0.1 -P %MYSQL_PORT% -u root -e "CREATE DATABASE IF NOT EXISTS supernova_olt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
echo [WEB] Menjalankan PHP di http://localhost:8080 ...
cd /d "%APP_DIR%"
start "Supernova-Web" cmd /k "php -S localhost:8080 -t %APP_DIR%"

rem Buka browser
timeout /t 2 /nobreak >nul
start "" http://localhost:8080

echo.
echo Server berjalan. Tutup jendela ini atau tekan CTRL+C untuk stop web,
echo lalu jalankan stop.bat untuk menghentikan database.
echo.
pause
