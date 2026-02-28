#!/bin/bash

# NOTE: Do NOT use 'set -e' here - it would kill the container if any
# sub-command fails (e.g. mysqladmin ping during startup loop)

echo "========================================="
echo "  EthioServe - Starting System..."
echo "========================================="

# 1. Debug: List Files (Helpful for Render Logs)
echo "[0/4] Checking file path structure..."
ls -F /var/www/html/

# 2. Setup Apache Port (Render injects PORT env var)
PORT=${PORT:-80}
echo "[1/4] Configuring Apache on port $PORT..."
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s|<VirtualHost \*:80>|<VirtualHost *:$PORT>|g" /etc/apache2/sites-available/000-default.conf

# 3. Setup MariaDB
echo "[2/4] Starting Database Engine..."
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql
chmod 777 /var/run/mysqld

# Prepare Database Variables
DB_NAME=${DB_NAME:-ethioserve}
DB_USER=${DB_USER:-ethioserve}
DB_PASS=${DB_PASS:-ethioserve_pass_2024}

# Database Initialization
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "  → Initializing MariaDB data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null
fi
chown -R mysql:mysql /var/lib/mysql

# Start MariaDB in background
echo "Starting MariaDB (Low Memory Mode)..."
/usr/bin/mysqld_safe --datadir='/var/lib/mysql' \
    --socket='/var/run/mysqld/mysqld.sock' \
    --innodb-buffer-pool-size=32M \
    --innodb-log-buffer-size=1M \
    --key-buffer-size=8M \
    --max-connections=20 \
    --skip-log-bin \
    --nowatch &

# Wait for MariaDB
echo "  → Waiting for database to start..."
MAX_WAIT=120
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
    echo "  ✗ CRITICAL: Database did not start within ${MAX_WAIT} seconds!"
    exit 1
fi

# 4. Setup Database Schema and Users
echo "[3/4] Configuring Database: $DB_NAME"
mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';"
mysql -u root -e "CREATE USER IF NOT EXISTS '$DB_USER'@'%' IDENTIFIED BY '$DB_PASS';"
mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost' WITH GRANT OPTION;"
mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'127.0.0.1' WITH GRANT OPTION;"
mysql -u root -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'%' WITH GRANT OPTION;"
mysql -u root -e "FLUSH PRIVILEGES;"
echo "  ✓ Database users configured."

# ---------------------------------------------------------------------------
# Import database.sql — check for 'orders' table (a good indicator of
# a complete import, since it's deeper in the alphabet than 'users')
# ---------------------------------------------------------------------------
ORDERS_EXISTS=$(mysql -u root -N -s -e \
    "SELECT COUNT(*) FROM information_schema.tables \
     WHERE table_schema='$DB_NAME' AND table_name='orders';" 2>/dev/null)
ORDERS_EXISTS=${ORDERS_EXISTS:-0}

if [ "$ORDERS_EXISTS" -le 0 ]; then
    echo "  ⚠ Database incomplete or empty. Performing full import..."

    if [ -f "/var/www/html/database.sql" ]; then

        # Drop & recreate for a guaranteed clean slate
        echo "  → Resetting database..."
        mysql -u root -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
        mysql -u root -e "CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        mysql -u root -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost' WITH GRANT OPTION;"
        mysql -u root -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'%' WITH GRANT OPTION;"
        mysql -u root -e "FLUSH PRIVILEGES;"

        # Build a wrapper SQL that disables FK checks around the import
        WRAPPER_SQL="/tmp/import_wrapper.sql"
        echo "SET FOREIGN_KEY_CHECKS=0;" > "$WRAPPER_SQL"
        echo "SET sql_mode='';" >> "$WRAPPER_SQL"
        cat /var/www/html/database.sql >> "$WRAPPER_SQL"
        echo "SET FOREIGN_KEY_CHECKS=1;" >> "$WRAPPER_SQL"

        echo "  → Importing database.sql with --force (errors are non-fatal)..."
        mysql -u root \
              --force \
              --default-character-set=utf8mb4 \
              "$DB_NAME" < "$WRAPPER_SQL" 2>&1
        IMPORT_RC=$?
        rm -f "$WRAPPER_SQL"

        # Count tables created
        TABLE_COUNT=$(mysql -u root -N -s -e \
            "SELECT COUNT(*) FROM information_schema.tables \
             WHERE table_schema='$DB_NAME';" 2>/dev/null)
        TABLE_COUNT=${TABLE_COUNT:-0}
        echo "  → Import done (exit=$IMPORT_RC). Tables created: $TABLE_COUNT"

        # Verify critical tables
        for TBL in users orders hotels bookings; do
            EXISTS=$(mysql -u root -N -s -e \
                "SELECT COUNT(*) FROM information_schema.tables \
                 WHERE table_schema='$DB_NAME' AND table_name='$TBL';" 2>/dev/null)
            EXISTS=${EXISTS:-0}
            if [ "$EXISTS" -gt 0 ]; then
                echo "  ✓ Table '$TBL' OK"
            else
                echo "  ✗ Table '$TBL' MISSING after import!"
            fi
        done

    else
        echo "  ✗ CRITICAL: /var/www/html/database.sql not found!"
    fi

else
    TABLE_COUNT=$(mysql -u root -N -s -e \
        "SELECT COUNT(*) FROM information_schema.tables \
         WHERE table_schema='$DB_NAME';" 2>/dev/null)
    echo "  ✓ Database already fully populated ($TABLE_COUNT tables found)."
fi

# 5. Final Start
echo "[4/4] Starting Apache Web Server..."
echo "========================================="
echo "  🚀 ETHIOSERVE IS ONLINE! Port: $PORT"
echo "========================================="

exec apache2-foreground
