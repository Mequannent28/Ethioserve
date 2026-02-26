<?php
/**
 * EthioServe - Central Configuration File
 * ========================================
 * Supports deployment on:
 *   - Local XAMPP (default)
 *   - Render (self-contained Docker with MariaDB)
 * 
 * On Render: Everything is automatic, no configuration needed!
 * On local XAMPP: Keep the defaults below.
 */

// ==================== ENVIRONMENT ====================
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'local');

// ==================== DATABASE ====================
if (ENVIRONMENT === 'production') {
    // -------- RENDER / PRODUCTION DATABASE --------
    // Self-contained MariaDB inside Docker (auto-configured)
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'ethioserve');
    define('DB_USER', getenv('DB_USER') ?: 'ethioserve');
    define('DB_PASS', getenv('DB_PASS') ?: 'ethioserve_pass_2024');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
} else {
    // -------- LOCAL DATABASE --------
    // -------- LOCAL DATABASE --------
    // Default to MySQL Configuration (XAMPP)
    define('DB_TYPE', 'mysql');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ethioserve');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
}

// Ensure DB_TYPE is defined for production too
if (!defined('DB_TYPE')) {
    define('DB_TYPE', 'mysql'); // Default to mysql for existing production setups unless overridden
}
define('DB_CHARSET', 'utf8mb4');

// ==================== BASE URL ====================
if (ENVIRONMENT === 'production') {
    define('BASE_URL', getenv('BASE_URL') !== false ? getenv('BASE_URL') : '');
} else {
    define('BASE_URL', '/Ethioserve-main');
}

// ==================== SITE INFO ====================
define('SITE_NAME', getenv('SITE_NAME') ?: 'EthioServe');
define('SITE_DESCRIPTION', 'Food delivery, hotel booking, transport & broker services across Ethiopia.');

// ==================== EMAIL CONFIGURATION ====================
// Uses environment variables on Render, or defaults for local testing
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: (getenv('MAIL_USERNAME') ?: 'mequannentgashaw12@gmail.com'));
define('SMTP_PASS', getenv('SMTP_PASS') ?: (getenv('MAIL_PASSWORD') ?: 'awrs rpzj qnia cucn'));
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@ethioserve.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'EthioServe');
?>