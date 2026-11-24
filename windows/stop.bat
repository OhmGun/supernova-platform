@echo off
title Supernova - Stop
echo Menghentikan Supernova (database & web)...
taskkill /FI "WINDOWTITLE eq Supernova-MariaDB*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq Supernova-Web*" /T /F >nul 2>&1
echo.
echo Selesai. Supernova sudah dihentikan.
pause
