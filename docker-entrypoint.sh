#!/bin/bash
set -e

echo "========================================="
echo "  EthioServe - Production Entrypoint"
echo "========================================="

# ---- Config Apache Port ----
PORT=${PORT:-80}
echo "[1/4] Configuring Apache to listen on port $PORT..."
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf

# ---- Prepare MariaDB ----
echo "[2/4] Starting MariaDB..."
mkdir -p /var/run/mysqld
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "  → Initializing MariaDB data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql 2>/dev/null
fi

# Start MariaDB
mysqld_safe --skip-syslog &

# Wait for MariaDB
echo "  → Waiting for MariaDB to start..."
for i in {30..0}; do
    if mysqladmin ping --silent; then
        break
    fi
    echo "  . Waiting ($i)..."
    sleep 1
done

if ! mysqladmin ping --silent; then
    echo "  ✗ ERROR: MariaDB failed to start!"
    exit 1
fi
echo "  ✓ MariaDB is running!"

# ---- Database Setup ----
echo "[3/4] Initializing Database Schema..."

# Create DB and User (Standard MariaDB commands)
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ethioserve CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ethioserve'@'localhost' IDENTIFIED BY 'ethioserve_pass_2024';
CREATE USER IF NOT EXISTS 'ethioserve'@'127.0.0.1' IDENTIFIED BY 'ethioserve_pass_2024';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'localhost';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'127.0.0.1';
FLUSH PRIVILEGES;
EOF

# Check if we need to import data
TABLE_COUNT=$(mysql -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='ethioserve';" 2>/dev/null || echo "0")
echo "  → Current table count: $TABLE_COUNT"

if [ "$TABLE_COUNT" -le "5" ]; then
    echo "  → Importing schema from database.sql..."
    if [ -f "/var/www/html/database.sql" ]; then
        mysql -u root ethioserve < /var/www/html/database.sql
        echo "  ✓ Schema import successful!"
    else
        echo "  ✗ WARNING: database.sql not found!"
    fi
fi

# Final Table Count
FINAL_COUNT=$(mysql -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='ethioserve';" 2>/dev/null || echo "0")
echo "  ✓ Database ready with $FINAL_COUNT tables."

# ---- Start Apache ----
echo "[4/4] Starting Apache Web Server..."
echo "========================================="
echo "  ✓ EthioServe is ready at port $PORT"
echo "========================================="

exec apache2-foreground
