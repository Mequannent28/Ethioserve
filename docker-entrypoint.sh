#!/bin/bash

# NOTE: Do NOT use 'set -e' here - it would kill the container if any
# sub-command fails (e.g. mysqladmin ping during startup loop)

echo "========================================="
echo "  EthioServe - Starting System..."
echo "========================================="

# 1. Setup Apache Port (Render injects PORT env var)
PORT=${PORT:-80}
echo "[1/4] Configuring Apache on port $PORT..."
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf

# 2. Setup MariaDB
echo "[2/4] Starting Database Engine..."
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

# 1. Prepare Database Variables
DB_NAME=${DB_NAME:-ethioserve}
DB_USER=${DB_USER:-ethioserve}
DB_PASS=${DB_PASS:-ethioserve_pass_2024}

# 2. Database Initialization (One-time)
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "  → Initializing MariaDB data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null
fi

# Ensure permissions
chown -R mysql:mysql /var/lib/mysql

# 3. Start MariaDB in background with low memory settings
echo "Starting MariaDB (Low Memory Mode)..."
/usr/bin/mysqld_safe --datadir='/var/lib/mysql' \
    --innodb-buffer-pool-size=32M \
    --innodb-log-buffer-size=1M \
    --key-buffer-size=8M \
    --max-connections=20 \
    --skip-log-bin \
    --nowatch &

# Wait for MariaDB to be ready (up to 90 seconds)
echo "  → Waiting for database to start..."
MAX_WAIT=90
WAITED=0
while [ $WAITED -lt $MAX_WAIT ]; do
    if mysqladmin ping --silent 2>/dev/null; then
        echo ""
        echo "  ✓ Database is ready! (waited ${WAITED}s)"
        break
    fi
    sleep 1
    WAITED=$((WAITED + 1))
    printf "."
done

if [ $WAITED -ge $MAX_WAIT ]; then
    echo ""
    echo "  ✗ CRITICAL: Database did not start within ${MAX_WAIT} seconds! Aborting."
    exit 1
fi

# 4. Setup Database Schema and Users
echo "  → Configuring Database: $DB_NAME"
mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'%';"
mysql -u root -e "FLUSH PRIVILEGES;"

# 5. Import initial data if tables are missing
TABLE_COUNT=$(mysql -u root -N -s -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';")
if [ "$TABLE_COUNT" -le 0 ]; then
    if [ -f "/var/www/html/database.sql" ]; then
        echo "  → Importing initial database schema (UTF-8)..."
        mysql -u root --default-character-set=utf8mb4 $DB_NAME < /var/www/html/database.sql 2>&1 | tail -10
        echo "  ✓ Database import complete."
    else
        echo "  ⚠ WARNING: /var/www/html/database.sql not found! Skipping import."
    fi
else
    echo "  ✓ Database already populated ($TABLE_COUNT tables found). Skipping import."
fi

# 4. Start Apache in foreground
echo "[4/4] Starting Apache Web Server..."
echo "========================================="
echo "  🚀 ETHIOSERVE IS ONLINE! Port: $PORT"
echo "========================================="

exec apache2-foreground
