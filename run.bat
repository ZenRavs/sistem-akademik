@echo off
title Sistem AKADEMIK FIK Launcher
echo ===================================================
echo   Sistem AKADEMIK FIK - Built-in Server Launcher
echo ===================================================
echo.

if exist "D:\PROJECTS\library\php-8.5.10-nts-Win32-vs17-x64\php.exe" (
    set PHP_CMD="D:\PROJECTS\library\php-8.5.10-nts-Win32-vs17-x64\php.exe"
    set PATH=D:\PROJECTS\library\php-8.5.10-nts-Win32-vs17-x64;%PATH%
) else if exist "C:\xampp\php\php.exe" (
    set PHP_CMD="C:\xampp\php\php.exe"
    set PATH=C:\xampp\php;%PATH%
) else (
    where php >nul 2>nul
    if %errorlevel% equ 0 (
        set PHP_CMD=php
    ) else (
        echo [ERROR] PHP executable tidak ditemukan.
        echo Harap pastikan PHP terpasang.
        pause
        exit /b 1
    )
)

echo [INFO] Menggunakan PHP: %PHP_CMD%
echo [INFO] Menjalankan server lokal pada http://localhost:8000 ...
echo [INFO] Membuka browser otomatis...
echo [INFO] Tekan Ctrl+C untuk menghentikan server.
echo.

start http://localhost:8000
%PHP_CMD% -S localhost:8000

