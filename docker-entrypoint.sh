#!/bin/bash
set -e

echo "========================================="
echo "  EthioServe - Starting Services"
echo "========================================="

# ---- Configure Apache port for Render ----
PORT=${PORT:-80}
echo "[1/4] Configuring Apache to listen on port $PORT..."
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf

# ---- Start MariaDB ----
echo "[2/4] Starting MariaDB..."
mkdir -p /var/run/mysqld
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

# Initialize data directory if needed
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "  → Initializing MariaDB data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql 2>/dev/null
fi

# Start MariaDB in background
mysqld_safe --skip-syslog &

# Wait for MariaDB to be ready
echo "  → Waiting for MariaDB to start..."
RETRIES=30
until mysqladmin ping --silent 2>/dev/null; do
    RETRIES=$((RETRIES - 1))
    if [ $RETRIES -le 0 ]; then
        echo "  ✗ MariaDB failed to start!"
        exit 1
    fi
    sleep 1
done
echo "  ✓ MariaDB is running!"

# ---- Setup Database ----
echo "[3/4] Setting up database..."

# Create database and user
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ethioserve CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'localhost' IDENTIFIED BY 'ethioserve_pass_2024';
GRANT ALL PRIVILEGES ON ethioserve.* TO 'ethioserve'@'127.0.0.1' IDENTIFIED BY 'ethioserve_pass_2024';
FLUSH PRIVILEGES;
EOF

# Initialize via PHP for consistent connection/execution
echo "  → Running database initialization via PHP CLI..."
ENVIRONMENT=production DB_NAME=ethioserve DB_USER=ethioserve DB_PASS=ethioserve_pass_2024 php /var/www/html/force_db.php
echo "  ✓ Database initialization step completed."

# ---- Start Apache ----
echo "[4/4] Starting Apache on port $PORT..."
echo "========================================="
echo "  ✓ EthioServe is LIVE!"
echo "========================================="

# Start Apache in foreground (keeps container running)
exec apache2-foreground
