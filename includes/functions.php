<?php
/**
 * TikTak - Helper Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Generate a random 6-letter English alphabetic code (uppercase)
 */
function generate_random_code(int $length = 6): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Exclude ambiguous 'I' and 'O' if preferred, or standard A-Z
    $code = '';
    $maxIndex = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $maxIndex)];
    }
    return $code;
}

/**
 * Generate a unique 6-letter code that does not yet exist in the database
 */
function generate_unique_code(PDO $pdo, int $length = 6): string {
    $attempts = 0;
    while ($attempts < 50) {
        $code = generate_random_code($length);
        $stmt = $pdo->prepare("SELECT id FROM resources WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
        $attempts++;
    }
    return strtoupper(substr(md5(uniqid('', true)), 0, $length));
}

/**
 * Check if a resource expiration date has passed
 */
function is_resource_expired(?string $expiresAt): bool {
    if (empty($expiresAt)) {
        return false;
    }
    $expiryTimestamp = strtotime($expiresAt);
    return ($expiryTimestamp !== false && $expiryTimestamp <= time());
}

/**
 * Format relative time (e.g. "in 3 hours", "expired 12m ago", "Never")
 */
function format_expiry_status(?string $expiresAt): array {
    if (empty($expiresAt)) {
        return [
            'is_expired' => false,
            'label' => 'Never expires',
            'badge_class' => 'badge-infinity',
            'remaining_seconds' => null,
        ];
    }
    
    $expiry = strtotime($expiresAt);
    $now = time();
    $diff = $expiry - $now;
    
    if ($diff <= 0) {
        $past = abs($diff);
        $timeStr = human_time_diff($past) . ' ago';
        return [
            'is_expired' => true,
            'label' => 'Expired ' . $timeStr,
            'badge_class' => 'badge-expired',
            'remaining_seconds' => 0,
        ];
    }

    return [
        'is_expired' => false,
        'label' => 'Expires in ' . human_time_diff($diff),
        'badge_class' => 'badge-active',
        'remaining_seconds' => $diff,
    ];
}

function human_time_diff(int $seconds): string {
    if ($seconds < 60) {
        return "{$seconds}s";
    }
    $minutes = round($seconds / 60);
    if ($minutes < 60) {
        return "{$minutes}m";
    }
    $hours = round($minutes / 60);
    if ($hours < 24) {
        return "{$hours}h";
    }
    $days = round($hours / 24);
    if ($days < 30) {
        return "{$days}d";
    }
    $months = round($days / 30);
    return "{$months}mo";
}

/**
 * Authentication Helpers
 */
function is_logged_in(): bool {
    return !empty($_SESSION['tiktak_user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['tiktak_user_id'],
        'username' => $_SESSION['tiktak_username'] ?? '',
        'name' => $_SESSION['tiktak_name'] ?? 'Professor',
    ];
}

function require_auth() {
    if (!is_logged_in()) {
        header("Location: /prof/login.php");
        exit;
    }
}

/**
 * Base URL detection
 */
function get_base_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? APP_DOMAIN;
    return $protocol . $host;
}

/**
 * Record a visit and increment count
 */
function record_resource_visit(PDO $pdo, int $resourceId) {
    try {
        $update = $pdo->prepare("UPDATE resources SET visits_count = visits_count + 1 WHERE id = ?");
        $update->execute([$resourceId]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ipHash = hash('sha256', $ip . 'tiktak_salt');
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

        $log = $pdo->prepare("INSERT INTO access_logs (resource_id, ip_hash, user_agent) VALUES (?, ?, ?)");
        $log->execute([$resourceId, $ipHash, $ua]);
    } catch (Exception $e) {
        // Non-blocking log failure
    }
}

/**
 * Format bytes to readable string (e.g. 15.4 MB)
 */
function format_file_size(int $bytes): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pow = floor(log($bytes, 1024));
    $pow = min($pow, count($units) - 1);
    $size = $bytes / pow(1024, $pow);
    return round($size, 1) . ' ' . $units[$pow];
}

/**
 * Check if a file size exceeds the heavy file warning threshold (100MB)
 */
function is_heavy_file(int $bytes): bool {
    return $bytes >= HEAVY_FILE_THRESHOLD_BYTES;
}

/**
 * Deduplicate filename by adding zero-padded suffixes (_01, _02, ...)
 */
function generate_suffixed_filename(PDO $pdo, int $resourceId, string $originalFilename): string {
    $cleanFilename = preg_replace('/[^\w\.\-\(\)\s]/u', '_', $originalFilename);
    $cleanFilename = trim($cleanFilename);
    if (empty($cleanFilename)) {
        $cleanFilename = 'unnamed_file';
    }

    $info = pathinfo($cleanFilename);
    $basename = $info['filename'] ?? 'file';
    $extension = !empty($info['extension']) ? '.' . strtolower($info['extension']) : '';

    // Check if original name is already unique
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resource_files WHERE resource_id = ? AND stored_filename = ?");
    $stmt->execute([$resourceId, $cleanFilename]);
    $row = $stmt->fetch();
    if ($row && (int)$row['count'] === 0) {
        return $cleanFilename;
    }

    // Otherwise find the next suffix: _01, _02, _03...
    $suffix = 1;
    while ($suffix < 1000) {
        $suffixStr = sprintf('_%02d', $suffix);
        $candidate = $basename . $suffixStr . $extension;

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resource_files WHERE resource_id = ? AND stored_filename = ?");
        $stmt->execute([$resourceId, $candidate]);
        $row = $stmt->fetch();
        if ($row && (int)$row['count'] === 0) {
            return $candidate;
        }
        $suffix++;
    }

    return $basename . '_' . time() . $extension;
}

/**
 * Get file type meta: icon, category name, color tag
 */
function get_file_type_meta(string $filename, ?string $mimeType = null): array {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $categories = [
        'pdf' => ['icon' => '📄', 'tag' => 'PDF', 'class' => 'file-tag-pdf'],
        'doc' => ['icon' => '📝', 'tag' => 'DOC', 'class' => 'file-tag-doc'],
        'docx' => ['icon' => '📝', 'tag' => 'DOC', 'class' => 'file-tag-doc'],
        'txt' => ['icon' => '📑', 'tag' => 'TXT', 'class' => 'file-tag-txt'],
        'md' => ['icon' => '📑', 'tag' => 'MD', 'class' => 'file-tag-txt'],
        'xls' => ['icon' => '📊', 'tag' => 'XLS', 'class' => 'file-tag-sheet'],
        'xlsx' => ['icon' => '📊', 'tag' => 'XLS', 'class' => 'file-tag-sheet'],
        'csv' => ['icon' => '📊', 'tag' => 'CSV', 'class' => 'file-tag-sheet'],
        'ppt' => ['icon' => '📽️', 'tag' => 'PPT', 'class' => 'file-tag-ppt'],
        'pptx' => ['icon' => '📽️', 'tag' => 'PPT', 'class' => 'file-tag-ppt'],
        'jpg' => ['icon' => '🖼️', 'tag' => 'IMG', 'class' => 'file-tag-img'],
        'jpeg' => ['icon' => '🖼️', 'tag' => 'IMG', 'class' => 'file-tag-img'],
        'png' => ['icon' => '🖼️', 'tag' => 'IMG', 'class' => 'file-tag-img'],
        'gif' => ['icon' => '🖼️', 'tag' => 'GIF', 'class' => 'file-tag-img'],
        'svg' => ['icon' => '🖼️', 'tag' => 'SVG', 'class' => 'file-tag-img'],
        'webp' => ['icon' => '🖼️', 'tag' => 'IMG', 'class' => 'file-tag-img'],
        'mp4' => ['icon' => '🎬', 'tag' => 'VIDEO', 'class' => 'file-tag-video'],
        'mov' => ['icon' => '🎬', 'tag' => 'VIDEO', 'class' => 'file-tag-video'],
        'mkv' => ['icon' => '🎬', 'tag' => 'VIDEO', 'class' => 'file-tag-video'],
        'mp3' => ['icon' => '🎵', 'tag' => 'AUDIO', 'class' => 'file-tag-audio'],
        'wav' => ['icon' => '🎵', 'tag' => 'AUDIO', 'class' => 'file-tag-audio'],
        'zip' => ['icon' => '📦', 'tag' => 'ZIP', 'class' => 'file-tag-zip'],
        'rar' => ['icon' => '📦', 'tag' => 'RAR', 'class' => 'file-tag-zip'],
        '7z' => ['icon' => '📦', 'tag' => '7Z', 'class' => 'file-tag-zip'],
        'tar' => ['icon' => '📦', 'tag' => 'TAR', 'class' => 'file-tag-zip'],
        'gz' => ['icon' => '📦', 'tag' => 'GZ', 'class' => 'file-tag-zip'],
        'py' => ['icon' => '🐍', 'tag' => 'CODE', 'class' => 'file-tag-code'],
        'js' => ['icon' => '📜', 'tag' => 'JS', 'class' => 'file-tag-code'],
        'ts' => ['icon' => '📜', 'tag' => 'TS', 'class' => 'file-tag-code'],
        'html' => ['icon' => '🌐', 'tag' => 'HTML', 'class' => 'file-tag-code'],
        'css' => ['icon' => '🎨', 'tag' => 'CSS', 'class' => 'file-tag-code'],
        'json' => ['icon' => '⚙️', 'tag' => 'JSON', 'class' => 'file-tag-code'],
        'sql' => ['icon' => '🗄️', 'tag' => 'SQL', 'class' => 'file-tag-code'],
        'c' => ['icon' => '💻', 'tag' => 'CODE', 'class' => 'file-tag-code'],
        'cpp' => ['icon' => '💻', 'tag' => 'CODE', 'class' => 'file-tag-code'],
        'java' => ['icon' => '☕', 'tag' => 'JAVA', 'class' => 'file-tag-code'],
    ];

    if (isset($categories[$ext])) {
        return $categories[$ext];
    }

    return ['icon' => '📁', 'tag' => strtoupper($ext ?: 'FILE'), 'class' => 'file-tag-generic'];
}

/**
 * Get file statistics for a resource
 */
function get_resource_files_summary(PDO $pdo, int $resourceId): array {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_files, COALESCE(SUM(file_size), 0) as total_size, SUM(CASE WHEN file_size >= ? THEN 1 ELSE 0 END) as heavy_count FROM resource_files WHERE resource_id = ?");
    $stmt->execute([HEAVY_FILE_THRESHOLD_BYTES, $resourceId]);
    $res = $stmt->fetch();
    return [
        'total_files' => (int)($res['total_files'] ?? 0),
        'total_size' => (int)($res['total_size'] ?? 0),
        'total_size_formatted' => format_file_size((int)($res['total_size'] ?? 0)),
        'heavy_count' => (int)($res['heavy_count'] ?? 0),
    ];
}

/**
 * ============================================================================
 * Micro-hosting Web Directory Helpers
 * ============================================================================
 */

function get_sites_base_dir(): string {
    $dir = TIKTAK_ROOT . DIRECTORY_SEPARATOR . 'sites';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function get_site_dir(string $code): string {
    $cleanCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code));
    return get_sites_base_dir() . DIRECTORY_SEPARATOR . $cleanCode;
}

function ensure_site_dir(string $code): string {
    $dir = get_site_dir($code);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

/**
 * Securely validate and resolve a path inside a site directory
 */
function safe_site_path(string $code, string $relativePath = ''): ?string {
    $baseDir = realpath(ensure_site_dir($code));
    if (!$baseDir) {
        return null;
    }

    $relativePath = str_replace(['\\', "\0"], ['/', ''], $relativePath);
    $relativePath = ltrim($relativePath, '/');

    if ($relativePath === '') {
        return $baseDir;
    }

    // Split and filter parts to avoid traversal
    $parts = explode('/', $relativePath);
    $safeParts = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '' || $part === '.') continue;
        if ($part === '..') return null; // Traversal attempt
        // Check for invalid control characters
        if (preg_match('/[\x00-\x1F\x7F:*?"<>|]/', $part)) {
            return null;
        }
        $safeParts[] = $part;
    }

    if (empty($safeParts)) {
        return $baseDir;
    }

    $target = $baseDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $safeParts);

    // If target exists, verify it starts with baseDir
    $realTarget = realpath($target);
    if ($realTarget !== false) {
        if (strpos($realTarget, $baseDir) !== 0) {
            return null;
        }
        return $realTarget;
    }

    // Target does not exist on disk yet: verify target string starts with baseDir prefix
    if (strpos($target, $baseDir . DIRECTORY_SEPARATOR) !== 0) {
        return null;
    }

    return $target;
}

/**
 * Recursively delete a site directory
 */
function delete_site_directory(string $code): bool {
    $dir = get_site_dir($code);
    if (!is_dir($dir)) {
        return true;
    }

    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) {
            @rmdir($file->getRealPath());
        } else {
            @unlink($file->getRealPath());
        }
    }
    return @rmdir($dir);
}

/**
 * Get summary and file listing of a micro-hosting site
 */
function get_site_summary(string $code): array {
    $dir = get_site_dir($code);
    if (!is_dir($dir)) {
        return [
            'total_files' => 0,
            'total_dirs' => 0,
            'total_size' => 0,
            'total_size_formatted' => '0 B',
            'has_index' => false,
            'main_file' => null,
            'files' => []
        ];
    }

    $totalFiles = 0;
    $totalDirs = 0;
    $totalSize = 0;
    $hasIndex = false;
    $mainFile = null;
    $fileList = [];

    $baseReal = realpath($dir);

    try {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $realPath = $item->getRealPath();
            $relPath = str_replace('\\', '/', substr($realPath, strlen($baseReal) + 1));
            $isDir = $item->isDir();
            $size = $isDir ? 0 : $item->getSize();
            $mtime = $item->getMTime();
            $filename = $item->getFilename();

            if ($isDir) {
                $totalDirs++;
            } else {
                $totalFiles++;
                $totalSize += $size;
                if (strtolower($relPath) === 'index.php') {
                    $hasIndex = true;
                    $mainFile = 'index.php';
                } elseif (strtolower($relPath) === 'index.html' && !$hasIndex) {
                    $hasIndex = true;
                    $mainFile = 'index.html';
                }
            }

            $meta = $isDir ? ['icon' => '📁', 'tag' => 'DIR', 'class' => 'file-tag-generic'] : get_file_type_meta($filename);

            $fileList[] = [
                'name' => $filename,
                'path' => $relPath,
                'is_dir' => $isDir,
                'size' => $size,
                'size_formatted' => $isDir ? '-' : format_file_size($size),
                'mtime' => $mtime,
                'mtime_formatted' => date('M j, Y H:i', $mtime),
                'icon' => $meta['icon'],
                'tag' => $meta['tag'],
                'is_editable' => !$isDir && is_text_editable_file($filename)
            ];
        }
    } catch (\Throwable $e) {
        // Fallback for empty/unreadable
    }

    // Sort: directories first, then alphabetically
    usort($fileList, function($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['path'], $b['path']);
    });

    return [
        'total_files' => $totalFiles,
        'total_dirs' => $totalDirs,
        'total_size' => $totalSize,
        'total_size_formatted' => format_file_size($totalSize),
        'has_index' => $hasIndex,
        'main_file' => $mainFile,
        'files' => $fileList
    ];
}

/**
 * Check if a file is safe and appropriate to view/edit in browser code editor
 */
function is_text_editable_file(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $editable = [
        'php', 'html', 'htm', 'css', 'js', 'json', 'txt', 'md', 'sql',
        'xml', 'svg', 'csv', 'yaml', 'yml', 'env', 'ini', 'htaccess',
        'py', 'c', 'cpp', 'h', 'java', 'ts', 'jsx', 'tsx', 'vue'
    ];
    return in_array($ext, $editable, true);
}

/**
 * Modern default starter template for index.php
 */
function get_default_site_template(string $code, string $title = ''): string {
    $displayTitle = !empty($title) ? htmlspecialchars($title) : 'Micro Site ' . $code;
    return <<<PHP
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$displayTitle}</title>
    <style>
        :root {
            --bg-grad-1: #0f172a;
            --bg-grad-2: #1e1b4b;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --accent-cyan: #38bdf8;
            --accent-indigo: #818cf8;
            --text-light: #f8fafc;
            --text-dim: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(circle at 50% 20%, var(--bg-grad-2), var(--bg-grad-1));
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .container {
            max-width: 680px;
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            text-align: center;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: var(--accent-cyan);
            padding: 0.35rem 0.9rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }
        h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, #ffffff, var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            color: var(--text-dim);
            line-height: 1.6;
            margin-bottom: 1.75rem;
            font-size: 1.05rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        .info-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            padding: 1rem;
            border-radius: 12px;
        }
        .info-label {
            font-size: 0.75rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        .info-val {
            font-family: monospace;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--accent-cyan);
        }
        .footer-note {
            font-size: 0.85rem;
            color: var(--text-dim);
            border-top: 1px solid var(--border-color);
            padding-top: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">🌐 TikTak Micro-Site Live</div>
        <h1><?= htmlspecialchars("{$displayTitle}") ?></h1>
        <p>This web page is hosted directly on the TikTak server. You can edit this file (<code>index.php</code>), add HTML/CSS/JavaScript, or upload assets from your teacher console.</p>

        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Access Code</div>
                <div class="info-val"><?= htmlspecialchars("{$code}") ?></div>
            </div>
            <div class="info-card">
                <div class="info-label">PHP Version</div>
                <div class="info-val"><?= phpversion() ?></div>
            </div>
            <div class="info-card">
                <div class="info-label">Server Time</div>
                <div class="info-val"><?= date('H:i:s') ?></div>
            </div>
        </div>

        <div class="footer-note">
            Powered by <strong>TikTak</strong> Universal Gateway &bull; Directory <code>sites/{$code}/</code>
        </div>
    </div>
</body>
</html>
PHP;
}


