<?php
/**
 * EthioServe - Central Configuration File
 * ========================================
 * Supports deployment on:
 *   - Local XAMPP (default)
 *   - Render (Docker with environment variables)
 *   - InfinityFree (shared hosting)
 * 
 * On Render: Set environment variables in the Render dashboard.
 * On local XAMPP: Keep the defaults below.
 */

// ==================== ENVIRONMENT ====================
// Reads from ENV variable if set, otherwise defaults to 'local'
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'local');

// ==================== DATABASE ====================
if (ENVIRONMENT === 'production') {
    // -------- PRODUCTION DATABASE (Render / Hosting) --------
    // Reads from environment variables set in Render dashboard
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'ethioserve');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
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
    // When deployed to Render or hosting root
    define('BASE_URL', getenv('BASE_URL') !== false ? getenv('BASE_URL') : '');
} else {
    // Local development
    define('BASE_URL', '/ethioserve');
}

// ==================== SITE INFO ====================
define('SITE_NAME', getenv('SITE_NAME') ?: 'EthioServe');
define('SITE_DESCRIPTION', 'Food delivery, hotel booking, transport & broker services across Ethiopia.');
?>