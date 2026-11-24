@echo off
rem Supernova Windows - README / Panduan
title Supernova Windows
echo ============================================================
echo   Supernova Platform Provisioning - Aplikasi Windows
echo   Tanpa XAMPP
echo ============================================================
echo.
echo  SYARAT:
echo   1. PHP 8 terinstall dan masuk PATH (cek: php -v)
echo      -> https://windows.php.net/download
echo   2. Internet (hanya saat setup, untuk menyalin MariaDB)
echo.
echo  CARA INSTALL (sekali saja):
echo   1. Klik dua kali  setup.bat
echo      -> akan menyalin MariaDB portable ke folder windows\mariadb
echo.
echo  CARA MENJALANKAN:
echo   1. Klik dua kali  start.bat
echo      -> otomatis: start database + web + buka browser
echo      -> akses di  http://localhost:8080
echo   2. Klik dua kali  stop.bat  untuk menghentikan
echo.
echo  STRUKTUR:
echo   windows\mariadb   -> MariaDB portable (dibuat setup.bat)
echo   windows\data      -> database Supernova (dibuat otomatis)
echo   windows\start.bat -> jalankan aplikasi
echo   windows\stop.bat  -> hentikan aplikasi
echo.
echo  CATATAN:
echo   - Database Supernova otomatis dibuat dan diisi tabel pertama kali
echo     aplikasi dibuka (sudah ada mekanisme auto-create di api.php).
echo   - Kredensial DB: root / (kosong), sesuai koneksi.php.
echo.
pause
