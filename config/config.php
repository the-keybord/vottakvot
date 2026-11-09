<?php
/**
 * TikTak - Configuration
 * tik.tak.md
 */

// Prevent direct script access outside bootstrapping if needed
if (!defined('TIKTAK_ROOT')) {
    define('TIKTAK_ROOT', dirname(__DIR__));
}

// Database Driver: 'mysql' (default) or 'pocketbase'
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');

// PocketBase Backend Configuration
define('POCKETBASE_URL', getenv('POCKETBASE_URL') ?: 'https://db.zece.info');
define('POCKETBASE_TABLE_PREFIX', getenv('POCKETBASE_TABLE_PREFIX') ?: 'tiktak_');
define('POCKETBASE_ADMIN_EMAIL', getenv('POCKETBASE_ADMIN_EMAIL') ?: 'bocanceatv@gmail.com');
define('POCKETBASE_ADMIN_PASSWORD', getenv('POCKETBASE_ADMIN_PASSWORD') ?: '0909atat');

// Legacy Centralized MySQL Database Configuration (if DB_DRIVER=mysql)
define('DB_HOST', getenv('DB_HOST') ?: '85.120.144.58');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'newuser_tiktak');
define('DB_USER', getenv('DB_USER') ?: 'newuser');
define('DB_PASS', getenv('DB_PASS') ?: 'secure_password');
define('DB_CHARSET', 'utf8mb4');
define('DB_USE_SSL', true);

// Disable silent SQLite fallback to ensure all deployments use the same centralized database
define('DB_ALLOW_SQLITE_FALLBACK', false);
define('DB_SQLITE_PATH', TIKTAK_ROOT . '/data/tiktak.sqlite');

// MinIO / S3 Storage Configuration
define('MINIO_ENDPOINT', getenv('MINIO_ENDPOINT') ?: 'minio-f4qsjb22cxs0wdyypygkj480.85.120.144.58.sslip.io');
define('MINIO_PORT', getenv('MINIO_PORT') ?: 80);
define('MINIO_USE_SSL', getenv('MINIO_USE_SSL') ? filter_var(getenv('MINIO_USE_SSL'), FILTER_VALIDATE_BOOLEAN) : false);
define('MINIO_BUCKET', getenv('MINIO_BUCKET') ?: 'tiktak');
define('MINIO_ACCESS_KEY', getenv('MINIO_ACCESS_KEY') ?: 'admin');
define('MINIO_SECRET_KEY', getenv('MINIO_SECRET_KEY') ?: 'ChangeThisPassword123!');
define('MINIO_REGION', getenv('MINIO_REGION') ?: 'us-east-1');

// File Upload Constraints
define('MAX_UPLOAD_SIZE_BYTES', 500 * 1024 * 1024); // 500 MB max file size
define('HEAVY_FILE_THRESHOLD_BYTES', 100 * 1024 * 1024); // 100 MB heavy file warning threshold

// Runtime limits for handling large uploads and ZIP archives (>10MB up to 500MB)
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '600');
@ini_set('max_input_time', '600');

// App Settings
define('APP_NAME', 'TikTak');
define('APP_TAGLINE', 'Universal Student Gateway');
define('APP_DOMAIN', 'tik.tak.md');

// Default initial professor account if no users exist in database
// Configurable via environment variables: ADMIN_USERNAME, ADMIN_PASSWORD, ADMIN_NAME
define('DEFAULT_PROF_USERNAME', getenv('ADMIN_USERNAME') ?: 'prof');
define('DEFAULT_PROF_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'prof123');
define('DEFAULT_PROF_NAME', getenv('ADMIN_NAME') ?: 'Professor');

// Production Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

