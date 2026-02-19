#!/bin/bash

# Terminate on error
set -e

echo "========================================="
echo "  EthioServe - System Booting..."
echo "========================================="

# 1. Setup Apache Port
PORT=${PORT:-80}
echo "[1/4] Configuring port $PORT..."
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf

# 2. Setup MySQL/MariaDB
echo "[2/4] Starting Database Engine..."
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "  → Initializing raw data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

# Start MariaDB in background (allow startup logs to go to stderr for debugging)
mysqld_safe --user=mysql --skip-syslog --skip-networking=0 &

# Wait for it to be ready
echo "  → Waiting for database availability..."
RETRIES=45
while ! mysqladmin ping --silent && [ $RETRIES -gt 0 ]; do
    sleep 1
    let RETRIES-=1
    echo -n "."
done
echo ""

if [ $RETRIES -eq 0 ]; then
    echo "  ✗ CRITICAL ERROR: Database service timed out!"
    exit 1
fi

# 3. Create Database & Fix Schema
echo "[3/4] Verifying Data Integrity..."

# Ensure DB and Users exist
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ethioserve;
CREATE USER IF NOT EXISTS 'ethioserve'@'localhost' IDENTIFIED BY 'ethioserve_pass_2024';
CREATE USER IF NOT EXISTS 'ethioserve'@'127.0.0.1' IDENTIFIED BY 'ethioserve_pass_2024';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'localhost';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'127.0.0.1';
FLUSH PRIVILEGES;
EOF

# Check for 'hotels' table specifically as it's a core table
HOTELS_EXIST=$(mysql -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='ethioserve' AND table_name='hotels';")

if [ "$HOTELS_EXIST" -eq "0" ]; then
    echo "  → TABLES MISSING! Starting full import from database.sql..."
    if [ -f "/var/www/html/database.sql" ]; then
        # Wrap import in foreign key disable/enable
        (
            echo "SET FOREIGN_KEY_CHECKS=0;"
            grep -vE "CREATE DATABASE|USE " /var/www/html/database.sql
            echo "SET FOREIGN_KEY_CHECKS=1;"
        ) | mysql -u root ethioserve
        echo "  ✓ Import successful!"
    else
        echo "  ✗ FAILURE: database.sql not found at /var/www/html/database.sql"
    fi
else
    echo "  ✓ Core tables detected. Skipping full import."
fi

# 4. Start Apache
echo "[4/4] Activating Web Server..."
echo "========================================="
echo "  🚀 ETHIOSERVE IS READY!"
echo "========================================="

exec apache2-foreground
