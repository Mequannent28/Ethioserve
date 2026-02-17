@echo off
setlocal
echo ==========================================
echo      MySQL Database Repair Tool
echo ==========================================
echo.
echo 1. Stopping any running MySQL processes...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

cd /d "c:\xampp1\mysql"

if not exist "backup" (
    echo ERROR: 'backup' folder not found in c:\xampp1\mysql
    echo Cannot proceed safely.
    pause
    exit /b
)

if exist "data_old" (
    echo Removing existing 'data_old' folder...
    rmdir /s /q "data_old"
)

echo.
echo 2. Backing up current 'data' folder to 'data_old'...
ren "data" "data_old"
if errorlevel 1 (
    echo Failed to rename data folder. Is MySQL still running?
    echo Please try running this script as Administrator.
    pause
    exit /b
)

echo.
echo 3. Creating fresh 'data' folder from 'backup'...
xcopy /E /I /Q /H /Y "backup" "data" >nul

echo.
echo 4. Restoring 'ethioserve' database...
if exist "data_old\ethioserve" (
    xcopy /E /I /Q /H /Y "data_old\ethioserve" "data\ethioserve" >nul
    echo    - Restored ethioserve folder.
) else (
    echo    - WARNING: ethioserve folder not found in old data!
)

echo.
echo 5. Restoring 'ibdata1' (Main Database File)...
copy /Y "data_old\ibdata1" "data\ibdata1" >nul
echo    - Restored ibdata1.

echo.
echo 6. Attempting to start MySQL...
start "" "c:\xampp1\mysql\bin\mysqld.exe" --console --standalone

echo.
echo ==========================================
echo               REPAIR COMPLETE
echo ==========================================
echo.
echo A new window has opened with MySQL running.
echo If you see 'ready for connections', it implies success!
echo you can close the new window and use XAMPP Control Panel normally.
echo.
pause
