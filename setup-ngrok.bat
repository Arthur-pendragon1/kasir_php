@echo off
REM Setup Script untuk Ngrok - Windows
REM Jalankan script ini sebagai Administrator

setlocal enabledelayedexpansion

echo.
echo ========================================
echo   NGROK SETUP SCRIPT
echo ========================================
echo.

REM Check if running as admin
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ ERROR: Script harus dijalankan sebagai Administrator!
    echo Klik kanan file ini dan pilih "Run as administrator"
    pause
    exit /b 1
)

REM Step 1: Check if ngrok exists
set NGROK_DIR=C:\ngrok
set NGROK_EXE=%NGROK_DIR%\ngrok.exe

if not exist %NGROK_EXE% (
    echo ❌ ERROR: ngrok.exe tidak ditemukan di %NGROK_DIR%
    echo Silakan extract ngrok.zip ke C:\ngrok terlebih dahulu
    pause
    exit /b 1
)

echo ✅ Ngrok ditemukan di: %NGROK_EXE%
echo.

REM Step 2: Ask for auth token
echo.
echo Silakan masukkan AUTH TOKEN Anda dari dashboard.ngrok.com
echo (Atau tekan Enter jika sudah tersimpan)
echo.

set /p AUTHTOKEN=Masukkan Auth Token: 

if not "!AUTHTOKEN!"=="" (
    echo.
    echo ⏳ Menyimpan Auth Token...
    %NGROK_EXE% config add-authtoken !AUTHTOKEN!
    echo ✅ Auth Token tersimpan
) else (
    echo ℹ️  Melewati setup token (gunakan token yang sudah ada)
)

echo.
echo ========================================
echo   SETUP SELESAI
echo ========================================
echo.
echo Untuk menjalankan tunnel, buka PowerShell dan jalankan:
echo   cd C:\ngrok
echo   .\ngrok.exe http 80
echo.
echo Link publik akan muncul di terminal
echo.
pause
