<?php
/**
 * TikTak - File Console API (MinIO Storage Backend)
 * Handles anonymous uploads, downloads, date-grouped listings, and professor-only deletion
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/MinioClient.php';

$pdo = get_db_connection();

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
if (!is_array($jsonInput)) {
    $jsonInput = [];
}
$inputData = array_merge($_POST, $jsonInput);
$action = $_GET['action'] ?? ($inputData['action'] ?? '');

// Helper to send JSON responses
function send_json(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// 1. LIST FILES (Grouped by upload date, skipping empty days)
// -------------------------------------------------------------
if ($action === 'list') {
    $code = strtoupper(trim($_GET['code'] ?? ''));
    if (empty($code)) {
        send_json(['success' => false, 'error' => 'Code is required.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM resources WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $resource = $stmt->fetch();

    if (!$resource) {
        send_json(['success' => false, 'error' => 'Resource not found.'], 404);
    }

    if (is_resource_expired($resource['expires_at'])) {
        send_json(['success' => false, 'error' => 'Resource has expired.'], 410);
    }

    // Fetch files sorted chronologically (latest upload date first, latest within day first)
    $stmt = $pdo->prepare("SELECT * FROM resource_files WHERE resource_id = ? ORDER BY upload_date DESC, uploaded_at DESC");
    $stmt->execute([$resource['id']]);
    $files = $stmt->fetchAll();

    $isProf = is_logged_in();
    $groupedByDate = [];
    $pinnedFiles = [];
    $todayStr = date('Y-m-d');
    $yesterdayStr = date('Y-m-d', strtotime('-1 day'));

    foreach ($files as $f) {
        $dateKey = $f['upload_date'];
        if (!isset($groupedByDate[$dateKey])) {
            $dateTimestamp = strtotime($dateKey);
            $formattedTitle = date('F j, Y', $dateTimestamp);
            $dayLabel = date('l', $dateTimestamp);

            if ($dateKey === $todayStr) {
                $dayBadge = 'Today';
            } elseif ($dateKey === $yesterdayStr) {
                $dayBadge = 'Yesterday';
            } else {
                $dayBadge = date('M j', $dateTimestamp);
            }

            $groupedByDate[$dateKey] = [
                'date' => $dateKey,
                'title' => $formattedTitle,
                'day_label' => $dayLabel,
                'day_badge' => $dayBadge,
                'total_files' => 0,
                'total_bytes' => 0,
                'files' => [],
            ];
        }

        $typeMeta = get_file_type_meta($f['stored_filename'], $f['mime_type']);
        $isHeavy = is_heavy_file((int)$f['file_size']);
        $isPinned = !empty($f['is_pinned']);

        $fileItem = [
            'id' => (int)$f['id'],
            'filename' => $f['stored_filename'],
            'original_filename' => $f['original_filename'],
            'size' => (int)$f['file_size'],
            'size_formatted' => format_file_size((int)$f['file_size']),
            'is_heavy' => $isHeavy,
            'is_pinned' => $isPinned,
            'time_formatted' => date('H:i', strtotime($f['uploaded_at'])),
            'upload_date' => $f['upload_date'],
            'uploaded_at' => $f['uploaded_at'],
            'downloads_count' => (int)$f['downloads_count'],
            'type_meta' => $typeMeta,
            'download_url' => '/api/files.php?action=download&file_id=' . $f['id'],
            'can_delete' => $isProf,
            'can_pin' => $isProf,
        ];

        if ($isPinned) {
            $pinnedFiles[] = $fileItem;
        }

        $groupedByDate[$dateKey]['total_files']++;
        $groupedByDate[$dateKey]['total_bytes'] += (int)$f['file_size'];
        $groupedByDate[$dateKey]['files'][] = $fileItem;
    }

    // Format day total sizes
    foreach ($groupedByDate as &$dayGroup) {
        $dayGroup['total_size_formatted'] = format_file_size($dayGroup['total_bytes']);
    }
    unset($dayGroup);

    // Days without files are automatically skipped because $groupedByDate only contains dates with files
    $daysList = array_values($groupedByDate);
    $stats = get_resource_files_summary($pdo, $resource['id']);

    send_json([
        'success' => true,
        'code' => $code,
        'title' => $resource['title'],
        'stats' => $stats,
        'is_prof' => $isProf,
        'pinned_files' => $pinnedFiles,
        'days' => $daysList,
        'total_days_with_files' => count($daysList)
    ]);
}


// -------------------------------------------------------------
// 2. UPLOAD FILE (Anonymous allowed, <= 500MB, auto-suffixing)
// -------------------------------------------------------------
if ($action === 'upload') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json(['success' => false, 'error' => 'POST method required.'], 405);
    }

    $code = strtoupper(trim($_POST['code'] ?? ''));
    if (empty($code)) {
        send_json(['success' => false, 'error' => 'Missing access code.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM resources WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $resource = $stmt->fetch();

    if (!$resource) {
        send_json(['success' => false, 'error' => 'Invalid or non-existent code.'], 404);
    }

    if (is_resource_expired($resource['expires_at'])) {
        send_json(['success' => false, 'error' => 'This resource has expired and no longer accepts uploads.'], 410);
    }

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['file']['error'] ?? 'no_file';
        $msg = 'Upload failed. ';
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $msg .= 'File exceeds server size limit.';
        } elseif ($errCode === UPLOAD_ERR_NO_FILE) {
            $msg .= 'No file was selected.';
        } else {
            $msg .= "Error code ({$errCode}).";
        }
        send_json(['success' => false, 'error' => $msg], 400);
    }

    $uploadedFile = $_FILES['file'];
    $fileSize = (int)$uploadedFile['size'];

    // Enforce 500MB maximum file size limit
    if ($fileSize > MAX_UPLOAD_SIZE_BYTES) {
        send_json([
            'success' => false,
            'error' => 'File size exceeds maximum limit of 500 MB (Uploaded: ' . format_file_size($fileSize) . ').'
        ], 400);
    }

    if ($fileSize <= 0) {
        send_json(['success' => false, 'error' => 'Cannot upload empty files.'], 400);
    }

    $originalName = basename($uploadedFile['name']);
    
    // Auto-suffixing logic: adds _01, _02 etc if file with same name exists
    $storedName = generate_suffixed_filename($pdo, (int)$resource['id'], $originalName);

    // MinIO key: "{code}/{storedName}"
    $s3Key = "{$code}/{$storedName}";

    $mimeType = $uploadedFile['type'] ?? 'application/octet-stream';
    if (empty($mimeType) || $mimeType === 'application/octet-stream') {
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($uploadedFile['tmp_name']);
            if ($detected) $mimeType = $detected;
        }
    }

    // Upload to MinIO
    $minio = MinioClient::getInstance();
    $uploadedOk = $minio->putObject($s3Key, $uploadedFile['tmp_name'], $mimeType, false);

    if (!$uploadedOk) {
        send_json(['success' => false, 'error' => 'Failed to store file in MinIO storage.'], 500);
    }

    // Record in database
    $uploadDate = date('Y-m-d');
    $uploadedAt = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ipHash = hash('sha256', $ip . 'tiktak_salt');

    $insert = $pdo->prepare("INSERT INTO resource_files (resource_id, code, original_filename, stored_filename, s3_key, file_size, mime_type, upload_date, uploaded_at, ip_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->execute([
        $resource['id'],
        $code,
        $originalName,
        $storedName,
        $s3Key,
        $fileSize,
        $mimeType,
        $uploadDate,
        $uploadedAt,
        $ipHash
    ]);

    $fileId = $pdo->lastInsertId();
    $isHeavy = is_heavy_file($fileSize);

    send_json([
        'success' => true,
        'file' => [
            'id' => (int)$fileId,
            'filename' => $storedName,
            'original_filename' => $originalName,
            'size' => $fileSize,
            'size_formatted' => format_file_size($fileSize),
            'is_heavy' => $isHeavy,
            'upload_date' => $uploadDate,
            'time_formatted' => date('H:i'),
            'type_meta' => get_file_type_meta($storedName, $mimeType),
            'download_url' => '/api/files.php?action=download&file_id=' . $fileId
        ]
    ]);
}

// -------------------------------------------------------------
// 3. DOWNLOAD FILE (Streaming directly from MinIO/S3)
// -------------------------------------------------------------
if ($action === 'download') {
    $fileId = (int)($_GET['file_id'] ?? 0);
    if ($fileId <= 0) {
        die("Invalid file request.");
    }

    $stmt = $pdo->prepare("SELECT f.*, r.expires_at FROM resource_files f JOIN resources r ON f.resource_id = r.id WHERE f.id = ? LIMIT 1");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if (!$file) {
        http_response_code(404);
        die("File not found.");
    }

    if (is_resource_expired($file['expires_at'])) {
        http_response_code(410);
        die("The access period for this material has expired.");
    }

    // Increment downloads count
    try {
        $up = $pdo->prepare("UPDATE resource_files SET downloads_count = downloads_count + 1 WHERE id = ?");
        $up->execute([$fileId]);
    } catch (\Throwable $e) {
        // Non-blocking
    }

    $minio = MinioClient::getInstance();
    $minio->streamObjectToBrowser($file['s3_key'], $file['stored_filename'], $file['mime_type'] ?: 'application/octet-stream');
    exit;
}

// -------------------------------------------------------------
// 4. DELETE FILE (Strictly restricted to professors only)
// -------------------------------------------------------------
if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json(['success' => false, 'error' => 'POST method required.'], 405);
    }

    // Permission check: only authenticated professors can delete files
    if (!is_logged_in()) {
        send_json([
            'success' => false,
            'error' => 'Permission denied. Only professors are authorized to delete files from this console.'
        ], 403);
    }

    $fileId = (int)($inputData['file_id'] ?? ($_GET['file_id'] ?? 0));

    if ($fileId <= 0) {
        send_json(['success' => false, 'error' => 'Invalid file ID.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM resource_files WHERE id = ? LIMIT 1");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if (!$file) {
        send_json(['success' => false, 'error' => 'File not found.'], 404);
    }

    // Delete object from MinIO
    $minio = MinioClient::getInstance();
    $minio->deleteObject($file['s3_key']);

    // Delete record from database
    $del = $pdo->prepare("DELETE FROM resource_files WHERE id = ?");
    $del->execute([$fileId]);

    send_json([
        'success' => true,
        'message' => "File {$file['stored_filename']} deleted successfully."
    ]);
}

// -------------------------------------------------------------
// 5. TOGGLE PIN (Professor only)
// -------------------------------------------------------------
if ($action === 'toggle_pin') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json(['success' => false, 'error' => 'POST method required.'], 405);
    }

    if (!is_logged_in()) {
        send_json([
            'success' => false,
            'error' => 'Permission denied. Only professors can pin files.'
        ], 403);
    }

    $fileId = (int)($inputData['file_id'] ?? ($_GET['file_id'] ?? 0));

    if ($fileId <= 0) {
        send_json(['success' => false, 'error' => 'Invalid file ID.'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, is_pinned, stored_filename FROM resource_files WHERE id = ? LIMIT 1");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if (!$file) {
        send_json(['success' => false, 'error' => 'File not found.'], 404);
    }

    $newPinState = empty($file['is_pinned']) ? 1 : 0;
    $up = $pdo->prepare("UPDATE resource_files SET is_pinned = ? WHERE id = ?");
    $up->execute([$newPinState, $fileId]);

    send_json([
        'success' => true,
        'is_pinned' => (bool)$newPinState,
        'message' => $newPinState ? "Pinned {$file['stored_filename']} to top" : "Unpinned {$file['stored_filename']}"
    ]);
}

send_json(['success' => false, 'error' => 'Invalid action.'], 400);

