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
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_NAME', getenv('DB_NAME') ?: 'ethioserve');
    define('DB_USER', getenv('DB_USER') ?: 'ethioserve');
    define('DB_PASS', getenv('DB_PASS') ?: 'ethioserve_pass_2024');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
} else {
    // -------- LOCAL XAMPP DATABASE --------
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ethioserve');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
}

define('DB_CHARSET', 'utf8mb4');

// ==================== BASE URL ====================
if (ENVIRONMENT === 'production') {
    define('BASE_URL', getenv('BASE_URL') !== false ? getenv('BASE_URL') : '');
} else {
<<<<<<< HEAD
    define('BASE_URL', '/Ethioserve-main');
=======
    define('BASE_URL', '/ethioserve');
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
}

// ==================== SITE INFO ====================
define('SITE_NAME', getenv('SITE_NAME') ?: 'EthioServe');
define('SITE_DESCRIPTION', 'Food delivery, hotel booking, transport & broker services across Ethiopia.');
?>