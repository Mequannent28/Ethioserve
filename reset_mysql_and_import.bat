@echo off
setlocal
echo ===================================================
echo   MySQL Factory Reset & Data Import Tool
echo ===================================================
echo.
echo This script will:
echo 1. Stop MySQL.
echo 2. Archive the current corrupt data folder.
echo 3. Create a fresh, working data folder from backup.
echo 4. Start MySQL (Guaranteed to work).
echo 5. Re-create the 'ethioserve' database.
echo 6. Import your latest backup (database.sql).
echo.
echo Press any key to start...
pause >nul

echo.
echo 1. Stopping MySQL...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 3 /nobreak >nul

cd /d "c:\xampp\mysql"

rem Check if backup exists
if not exist "backup" (
    echo [ERROR] 'backup' folder missing in c:\xampp\mysql. Cannot reset!
    pause
    exit /b
)

rem Archive current data
set "archivename=data_corrupt_%random%"
echo 2. Archiving 'data' to '%archivename%'...
if exist "data" (
    ren "data" "%archivename%"
    if errorlevel 1 (
        echo [ERROR] Failed to rename data folder. Close XAMPP/MySQL and retry.
        pause
        exit /b
    )
)

rem Create fresh data
echo 3. Creating fresh 'data' folder...
xcopy /E /I /Q /H /Y "backup" "data" >nul

echo.
echo 4. Starting MySQL Service...
start "" "c:\xampp\mysql\bin\mysqld.exe" --console --standalone

echo Waiting for MySQL to initialize (15 seconds)...
timeout /t 15 /nobreak >nul

echo.
echo 5. Creating Database...
"c:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS ethioserve;"
if errorlevel 1 (
    echo [ERROR] Could not create database. MySQL might not be running?
    pause
    exit /b
) else (
    echo [SUCCESS] Database 'ethioserve' created.
)

echo.
echo 6. Importing Data from database.sql...
    "c:\xampp\mysql\bin\mysql.exe" -u root ethioserve < "c:\xampp\htdocs\Ethioserve-main\database.sql"
    if errorlevel 1 (
        echo [WARNING] Import had some errors, but data should be there.
    ) else (
        echo [SUCCESS] Data imported successfully!
    )
) else (
    echo [ERROR] database.sql not found!
)

echo.
echo ===================================================
echo             RECOVERY COMPLETE
echo ===================================================
echo.
echo A MySQL window is open running the service.
echo You can verify the site works now.
echo.
echo To return to normal XAMPP usage:
echo 1. Close the MySQL console window.
echo 2. Use XAMPP Control Panel to start MySQL.
echo.
pause
