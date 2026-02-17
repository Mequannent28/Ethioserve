@echo off
echo Attempting Quick Fix for MySQL...
cd /d "c:\xampp1\mysql\data"

if exist ib_logfile0 (
    echo Renaming ib_logfile0...
    move ib_logfile0 ib_logfile0.bak
)

if exist ib_logfile1 (
    echo Renaming ib_logfile1...
    move ib_logfile1 ib_logfile1.bak
)

echo.
echo Log files renamed. 
echo Please try starting MySQL from the XAMPP Control Panel now.
echo.
pause
