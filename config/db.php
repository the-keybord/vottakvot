<?php
/**
 * TikTak - Database Connection & Initialization Helper
 */

require_once __DIR__ . '/config.php';

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = 'mysql';
    $host = DB_HOST;
    $port = DB_PORT;
    $dbname = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    $charset = DB_CHARSET;

    try {
        // First try connecting with database specified
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // If database doesn't exist yet, try connecting to host without dbname to create it
            if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                $dsnWithoutDb = "mysql:host={$host};port={$port};charset={$charset}";
                $rootPdo = new PDO($dsnWithoutDb, $user, $pass, $options);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo = new PDO($dsn, $user, $pass, $options);
            } else {
                throw $e;
            }
        }

        initialize_mysql_schema($pdo);
        return $pdo;

    } catch (PDOException $e) {
        // If MySQL fails and SQLite fallback is enabled, use local SQLite database
        if (DB_ALLOW_SQLITE_FALLBACK) {
            $sqliteDir = dirname(DB_SQLITE_PATH);
            if (!is_dir($sqliteDir)) {
                @mkdir($sqliteDir, 0777, true);
            }
            $dsn = "sqlite:" . DB_SQLITE_PATH;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            initialize_sqlite_schema($pdo);
            return $pdo;
        }

        http_response_code(500);
        die("<div style='font-family: system-ui, sans-serif; max-width: 600px; margin: 4rem auto; padding: 2rem; border-radius: 12px; background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; line-height: 1.6;'>
            <h2 style='margin-top: 0; color: #be123c;'>⚡ TikTak Database Connection Failed</h2>
            <p>Could not connect to the centralized database at <code>{$host}:{$port}</code> (DB: <code>{$dbname}</code>).</p>
            <p style='font-size: 0.9rem; background: #fff; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #ffe4e6; word-break: break-all;'>
                <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
            </p>
            <p style='font-size: 0.85rem; color: #4b5563;'>If you are deploying on a hosting provider, ensure that outbound TCP connections to port <code>{$port}</code> on <code>{$host}</code> are permitted in your hosting firewall.</p>
        </div>");
    }
}

function initialize_mysql_schema(PDO $pdo) {
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) NOT NULL DEFAULT 'Professor',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Resources table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `resources` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `code` VARCHAR(10) NOT NULL UNIQUE,
        `title` VARCHAR(255) NOT NULL DEFAULT '',
        `type` ENUM('redirect', 'text', 'files', 'hosting') NOT NULL DEFAULT 'redirect',
        `content_link` TEXT NULL,
        `content_text` LONGTEXT NULL,
        `visits_count` INT NOT NULL DEFAULT 0,
        `expires_at` DATETIME NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`code`),
        INDEX (`expires_at`),
        INDEX (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Auto-update enum if previously created without 'files' or 'hosting'
    try {
        $pdo->exec("ALTER TABLE `resources` MODIFY COLUMN `type` ENUM('redirect', 'text', 'files', 'hosting') NOT NULL DEFAULT 'redirect'");
    } catch (\Throwable $e) {
        // Ignore if already updated or permissions restricted
    }

    // Resource Files table (for MinIO file console)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `resource_files` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `resource_id` INT NOT NULL,
        `code` VARCHAR(10) NOT NULL,
        `original_filename` VARCHAR(255) NOT NULL,
        `stored_filename` VARCHAR(255) NOT NULL,
        `s3_key` VARCHAR(500) NOT NULL,
        `file_size` BIGINT NOT NULL,
        `mime_type` VARCHAR(150) NULL,
        `upload_date` DATE NOT NULL,
        `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `ip_hash` VARCHAR(64) NULL,
        `downloads_count` INT NOT NULL DEFAULT 0,
        `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
        INDEX (`resource_id`),
        INDEX (`code`),
        INDEX (`upload_date`),
        INDEX (`uploaded_at`),
        INDEX (`is_pinned`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Auto-add is_pinned column if table already exists
    try {
        $pdo->exec("ALTER TABLE `resource_files` ADD COLUMN `is_pinned` TINYINT(1) NOT NULL DEFAULT 0");
    } catch (\Throwable $e) {
        // Column already exists
    }

    // Access logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `access_logs` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `resource_id` INT NOT NULL,
        `accessed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `ip_hash` VARCHAR(64) NULL,
        `user_agent` VARCHAR(255) NULL,
        INDEX (`resource_id`),
        INDEX (`accessed_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    seed_default_admin($pdo);
}

function initialize_sqlite_schema(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        name TEXT NOT NULL DEFAULT 'Professor',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        code TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL DEFAULT '',
        type TEXT NOT NULL DEFAULT 'redirect',
        content_link TEXT NULL,
        content_text TEXT NULL,
        visits_count INTEGER NOT NULL DEFAULT 0,
        expires_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS resource_files (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        resource_id INTEGER NOT NULL,
        code TEXT NOT NULL,
        original_filename TEXT NOT NULL,
        stored_filename TEXT NOT NULL,
        s3_key TEXT NOT NULL,
        file_size INTEGER NOT NULL,
        mime_type TEXT NULL,
        upload_date TEXT NOT NULL,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_hash TEXT NULL,
        downloads_count INTEGER NOT NULL DEFAULT 0,
        is_pinned INTEGER NOT NULL DEFAULT 0
    );");

    try {
        $pdo->exec("ALTER TABLE resource_files ADD COLUMN is_pinned INTEGER NOT NULL DEFAULT 0");
    } catch (\Throwable $e) {
        // Ignore
    }


    $pdo->exec("CREATE TABLE IF NOT EXISTS access_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        resource_id INTEGER NOT NULL,
        accessed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_hash TEXT NULL,
        user_agent TEXT NULL
    );");

    seed_default_admin($pdo);
}

function seed_default_admin(PDO $pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $stmt->fetch();
    if ($row && $row['count'] == 0) {
        $username = DEFAULT_PROF_USERNAME;
        $passwordHash = password_hash(DEFAULT_PROF_PASSWORD, PASSWORD_BCRYPT);
        $name = DEFAULT_PROF_NAME;
        
        $insert = $pdo->prepare("INSERT INTO users (username, password_hash, name) VALUES (?, ?, ?)");
        $insert->execute([$username, $passwordHash, $name]);
    }
}

