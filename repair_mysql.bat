@echo off
echo Stopping any stuck MySQL processes...
taskkill /F /IM mysqld.exe 2>nul
echo.

cd /d "c:\xampp1\mysql\data"

if exist "aria_log.00000001" (
    echo Found aria_log.00000001, renaming to .bak
    ren "aria_log.00000001" "aria_log.00000001.bak"
    echo Done.
) else (
    echo aria_log.00000001 not found.
)

echo.
echo Attempting to start MySQL to verify...
start "" "c:\xampp1\mysql\bin\mysqld.exe" --console --standalone
echo.
echo MySQL has been started in a new window for testing. 
echo If it stays open without errors, you can close it and use XAMPP Control Panel.
