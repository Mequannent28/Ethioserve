@echo off
echo ========================================
echo      EthioServe Database Import Tool
echo ========================================
echo.
echo Database Name: ethioserve
echo Import File:   database.sql
echo.

set mysql="c:\xampp\mysql\bin\mysql.exe"

if not exist %mysql% (
    echo ERROR: mysql.exe not found at c:\xampp\mysql\bin\mysql.exe
    echo Please check your XAMPP installation path.
    pause
    exit /b
)

echo 1. Creating database 'ethioserve' if it doesn't exist...
%mysql% -u root -e "CREATE DATABASE IF NOT EXISTS ethioserve;"
if errorlevel 1 (
    echo [ERROR] Failed to create database. Is MySQL running?
    echo Please start MySQL from XAMPP Control Panel first.
    pause
    exit /b
)

echo.
echo 2. Importing data from database.sql...
%mysql% -u root ethioserve < database.sql
if errorlevel 1 (
    echo [ERROR] Import failed.
    pause
    exit /b
)

echo.
echo [SUCCESS] Database 'ethioserve' has been successfully imported!
echo.
pause
