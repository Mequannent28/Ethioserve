@echo off
echo Backing up database...
"c:\xampp1\mysql\bin\mysqldump.exe" -u root ethioserve > database_backup.sql

echo Checking git status...
if not exist .git (
    echo Initializing git...
    git init
    git branch -M main
    git remote add origin https://github.com/Mequannent28/Ethioserve.git
)

echo Adding files...
git add .

echo Committing...
set timestamp=%date% %time%
git commit -m "Auto update: %timestamp%"

echo Pushing to remote...
git push -u origin main

echo Done!
