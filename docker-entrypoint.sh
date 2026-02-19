#!/bin/bash

echo "========================================="
echo "  EthioServe - Starting System..."
echo "========================================="

# 1. Setup Apache Port
PORT=${PORT:-80}
echo "[1/4] Setting Apache port to $PORT..."
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf

# 2. Setup MySQL/MariaDB
echo "[2/4] Initializing Database Engine..."
mkdir -p /var/run/mysqld
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "  → Fresh database init..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

# Start MariaDB in background
echo "  → Starting MariaDB Background Process..."
mysqld_safe --user=mysql --skip-syslog --skip-networking=0 &

# Wait for it to be ready
RETRIES=30
until mysqladmin ping --silent || [ $RETRIES -eq 0 ]; do
    echo "  . Waiting for database ($RETRIES)..."
    sleep 1
    let RETRIES-=1
done

if [ $RETRIES -eq 0 ]; then
    echo "  ✗ ERROR: Database failed to start!"
    exit 1
fi

# 3. Create Schema and Users
echo "[3/4] Auto-Configuring Database..."
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ethioserve;
CREATE USER IF NOT EXISTS 'ethioserve'@'localhost' IDENTIFIED BY 'ethioserve_pass_2024';
CREATE USER IF NOT EXISTS 'ethioserve'@'127.0.0.1' IDENTIFIED BY 'ethioserve_pass_2024';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'localhost';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'127.0.0.1';
FLUSH PRIVILEGES;
EOF

# Check table count
TABLE_COUNT=$(mysql -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='ethioserve';")
echo "  → Database currently has $TABLE_COUNT tables."

if [ "$TABLE_COUNT" -le "5" ]; then
    echo "  → Importing default data (database.sql)..."
    if [ -f "/var/www/html/database.sql" ]; then
        # Remove any database-switching lines from the file during import
        grep -v "CREATE DATABASE" /var/www/html/database.sql | grep -v "USE " | mysql -u root ethioserve
        echo "  ✓ Import finished!"
    else
        echo "  ✗ WARNING: database.sql MISSION! Application might fail."
    fi
fi

# 4. Final Handover to Apache
echo "[4/4] Handing over to Apache Server..."
echo "========================================="
echo "  🚀 ETHIOSERVE IS LIVE!"
echo "========================================="

exec apache2-foreground
