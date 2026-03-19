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

# ---------------------------------------------------------------------------
# [SMS] Ensure School Management System tables exist
# This runs on EVERY startup to guarantee tables are present
# ---------------------------------------------------------------------------
echo "  → Creating/verifying SMS (School Management System) tables..."
mysql -u root "$DB_NAME" << 'ENDSQL'
CREATE TABLE IF NOT EXISTS `sms_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` varchar(50) NOT NULL,
  `section` varchar(20) DEFAULT NULL,
  `capacity` int(11) DEFAULT 40,
  `room_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_parents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `qualification` text DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_student_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `student_id_number` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(50) DEFAULT NULL,
  `previous_school` varchar(100) DEFAULT NULL,
  `health_conditions` text DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_points` int(11) DEFAULT 100,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_assignment_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` varchar(10) DEFAULT NULL,
  `teacher_feedback` text DEFAULT NULL,
  `submission_text` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_class_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_timetables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_fee_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) DEFAULT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `fee_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('Paid','Partial','Pending') DEFAULT 'Paid',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Also ensure rental_requests has all needed columns */
ALTER TABLE rental_requests
  ADD COLUMN IF NOT EXISTS duration_months INT DEFAULT 1,
  ADD COLUMN IF NOT EXISTS broker_id INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS referral_code_used VARCHAR(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS chat_initiated TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS customer_typing_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS owner_typing_at DATETIME NULL;

/* rental_chat_messages */
CREATE TABLE IF NOT EXISTS `rental_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `message_type` enum('text','image','payment_proof') DEFAULT 'text',
  `file_path` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ENDSQL
echo "  ✓ SMS tables verified/created."

# Seed default classes if none exist
CLASS_COUNT=$(mysql -u root -N -s -e "SELECT COUNT(*) FROM $DB_NAME.sms_classes;" 2>/dev/null)
CLASS_COUNT=${CLASS_COUNT:-0}
if [ "$CLASS_COUNT" -le 0 ]; then
  mysql -u root "$DB_NAME" -e "INSERT INTO sms_classes (class_name,section,capacity,room_number) VALUES ('Grade 10','A',40,'Room 101'),('Grade 11','B',35,'Room 205'),('Grade 12','A',30,'Room 301');"
  mysql -u root "$DB_NAME" -e "INSERT INTO sms_subjects (subject_name,subject_code) VALUES ('Mathematics','MATH'),('English','ENG'),('Biology','BIO'),('Physics','PHY'),('Chemistry','CHEM');"
  echo "  ✓ Seeded default SMS classes and subjects."
fi

# ---------------------------------------------------------------------------
# Seed demo School users if they don't exist
# ---------------------------------------------------------------------------
echo "  → Seeding demo school users..."
PHP_HASH=$(php -r "echo password_hash('password', PASSWORD_DEFAULT);")

TEACHER_EXISTS=$(mysql -u root -N -s -e "SELECT COUNT(*) FROM $DB_NAME.users WHERE username='teacher1';" 2>/dev/null)
TEACHER_EXISTS=${TEACHER_EXISTS:-0}
if [ "$TEACHER_EXISTS" -le 0 ]; then
  mysql -u root "$DB_NAME" -e "INSERT IGNORE INTO users (username,email,password,full_name,role,created_at) VALUES ('teacher1','teacher1@ethioserve.com','$PHP_HASH','Abebe Bikila','teacher',NOW());"
  TEACHER_ID=$(mysql -u root -N -s -e "SELECT id FROM $DB_NAME.users WHERE username='teacher1';" 2>/dev/null)
  mysql -u root "$DB_NAME" -e "INSERT IGNORE INTO sms_teachers (user_id,employee_id,specialization) VALUES ($TEACHER_ID,'TCH001','Mathematics');"
  echo "  ✓ teacher1 created"
fi

STUDENT_EXISTS=$(mysql -u root -N -s -e "SELECT COUNT(*) FROM $DB_NAME.users WHERE username='student1';" 2>/dev/null)
STUDENT_EXISTS=${STUDENT_EXISTS:-0}
if [ "$STUDENT_EXISTS" -le 0 ]; then
  mysql -u root "$DB_NAME" -e "INSERT IGNORE INTO users (username,email,password,full_name,role,created_at) VALUES ('student1','student1@ethioserve.com','$PHP_HASH','Dawit Kebede','student',NOW());"
  STUDENT_ID=$(mysql -u root -N -s -e "SELECT id FROM $DB_NAME.users WHERE username='student1';" 2>/dev/null)
  CLASS_ID=$(mysql -u root -N -s -e "SELECT id FROM $DB_NAME.sms_classes LIMIT 1;" 2>/dev/null)
  CLASS_ID=${CLASS_ID:-1}
  mysql -u root "$DB_NAME" -e "INSERT IGNORE INTO sms_student_profiles (user_id,class_id,student_id_number,gender) VALUES ($STUDENT_ID,$CLASS_ID,'STU-001','Male');"
  echo "  ✓ student1 created"
fi

PARENT_EXISTS=$(mysql -u root -N -s -e "SELECT COUNT(*) FROM $DB_NAME.users WHERE username='parent1';" 2>/dev/null)
PARENT_EXISTS=${PARENT_EXISTS:-0}
if [ "$PARENT_EXISTS" -le 0 ]; then
  mysql -u root "$DB_NAME" -e "INSERT IGNORE INTO users (username,email,password,full_name,role,created_at) VALUES ('parent1','parent1@ethioserve.com','$PHP_HASH','Kebede Michael','parent',NOW());"
  PARENT_ID=$(mysql -u root -N -s -e "SELECT id FROM $DB_NAME.users WHERE username='parent1';" 2>/dev/null)
  mysql -u root "$DB_NAME" -e "INSERT IGNORE INTO sms_parents (user_id,occupation) VALUES ($PARENT_ID,'Self-Employed');"
  echo "  ✓ parent1 created"
fi

SADMIN_EXISTS=$(mysql -u root -N -s -e "SELECT COUNT(*) FROM $DB_NAME.users WHERE username='school_admin1';" 2>/dev/null)
SADMIN_EXISTS=${SADMIN_EXISTS:-0}
if [ "$SADMIN_EXISTS" -le 0 ]; then
  mysql -u root "$DB_NAME" -e "INSERT IGNORE INTO users (username,email,password,full_name,role,created_at) VALUES ('school_admin1','school_admin1@ethioserve.com','$PHP_HASH','School Administrator','school_admin',NOW());"
  echo "  ✓ school_admin1 created"
fi

echo "  ✓ Demo school users ready. (password = 'password')"

# 5. Final Start
echo "[4/4] Starting Apache Web Server..."
echo "========================================="
echo "  🚀 ETHIOSERVE IS ONLINE! Port: $PORT"
echo "========================================="

exec apache2-foreground
