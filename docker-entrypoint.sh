#!/bin/bash

# Terminate on error
set -e

echo "========================================="
echo "  EthioServe - Starting System..."
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
    echo "  → Fresh database init..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

# Start MariaDB in background
mysqld_safe --user=mysql --skip-syslog --skip-networking=0 --max-allowed-packet=128M &

# Wait for it to be ready
echo "  → Waiting for database availability..."
RETRIES=60
until mysqladmin ping --silent || [ $RETRIES -eq 0 ]; do
    sleep 1
    let RETRIES-=1
    echo -n "."
done
echo ""

if [ $RETRIES -eq 0 ]; then
    echo "  ✗ CRITICAL ERROR: Database service timed out!"
    exit 1
fi

# 3. Create Schema and Users
echo "[3/4] Auto-Configuring Database..."

# Ensure DB and Users exist
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ethioserve;
CREATE USER IF NOT EXISTS 'ethioserve'@'localhost' IDENTIFIED BY 'ethioserve_pass_2024';
CREATE USER IF NOT EXISTS 'ethioserve'@'127.0.0.1' IDENTIFIED BY 'ethioserve_pass_2024';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'localhost';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'127.0.0.1';
SET GLOBAL max_allowed_packet=134217728;
FLUSH PRIVILEGES;
EOF

# CHECK IF DATA ALREADY EXISTS
# We check the 'hotels' table which is essential.
HOTELS_EXIST=$(mysql -u root -N -e "SELECT count(*) FROM information_schema.tables WHERE table_schema='ethioserve' AND table_name='hotels';")

if [ "$HOTELS_EXIST" -eq "0" ]; then
    echo "  → DATA MISSING! Importing from database.sql..."
    if [ -f "/var/www/html/database.sql" ]; then
        # Use a more aggressive import
        mysql -u root ethioserve < /var/www/html/database.sql
        echo "  ✓ Data imported successfully!"
    else
        echo "  ✗ ERROR: database.sql not found!"
    fi
else
    echo "  ✓ Data already present ($HOTELS_EXIST tables found)."
fi

# 4. Start Apache
echo "[4/4] Activating Web Server..."
echo "========================================="
echo "  🚀 ETHIOSERVE IS READY!"
echo "========================================="

exec apache2-foreground
