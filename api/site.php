<?php
/**
 * TikTak - Micro-hosting Site Manager API Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!is_logged_in()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user = current_user();
$userId = $user['id'];
$pdo = get_db_connection();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? ($_GET['action'] ?? '');
$code = strtoupper(trim($input['code'] ?? ($_GET['code'] ?? '')));

if (empty($code)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Missing site code']);
    exit;
}

// Verify that resource exists and belongs to current user
$stmt = $pdo->prepare("SELECT * FROM resources WHERE code = ? AND user_id = ? AND type = 'hosting' LIMIT 1");
$stmt->execute([$code, $userId]);
$resource = $stmt->fetch();

if (!$resource) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Micro-site not found or access denied']);
    exit;
}

// Ensure site directory exists
$siteDir = ensure_site_dir($code);

// ============================================================================
// Action: download_zip (Direct file download)
// ============================================================================
if ($action === 'download_zip') {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('ZipArchive extension is not enabled on this server.');
    }

    $zip = new ZipArchive();
    $tempZipFile = tempnam(sys_get_temp_dir(), 'tiktak_site_');
    if ($zip->open($tempZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        die('Could not create temporary zip archive.');
    }

    $baseReal = realpath($siteDir);
    $it = new RecursiveDirectoryIterator($siteDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $item) {
        $realPath = $item->getRealPath();
        $relPath = str_replace('\\', '/', substr($realPath, strlen($baseReal) + 1));
        if ($item->isDir()) {
            $zip->addEmptyDir($relPath);
        } else {
            $zip->addFile($realPath, $relPath);
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="tiktak_site_' . $code . '.zip"');
    header('Content-Length: ' . filesize($tempZipFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tempZipFile);
    @unlink($tempZipFile);
    exit;
}

// Standard JSON responses for other actions
header('Content-Type: application/json; charset=utf-8');

// ============================================================================
// Action: list
// ============================================================================
if ($action === 'list') {
    $summary = get_site_summary($code);
    echo json_encode([
        'success' => true,
        'code' => $code,
        'title' => $resource['title'],
        'summary' => $summary,
        'site_url' => get_base_url() . '/sites/' . $code . '/'
    ]);
    exit;
}

// ============================================================================
// Action: read (Read text file content)
// ============================================================================
if ($action === 'read') {
    $filename = trim($input['file'] ?? ($_GET['file'] ?? 'index.php'));
    if (empty($filename)) $filename = 'index.php';

    $filePath = safe_site_path($code, $filename);
    if (!$filePath || !is_file($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File not found: ' . htmlspecialchars($filename)]);
        exit;
    }

    if (!is_text_editable_file($filename)) {
        echo json_encode(['success' => false, 'error' => 'This file format cannot be viewed in the text editor.']);
        exit;
    }

    $content = file_get_contents($filePath);
    echo json_encode([
        'success' => true,
        'file' => $filename,
        'content' => $content,
        'size' => filesize($filePath),
        'mtime' => filemtime($filePath)
    ]);
    exit;
}

// ============================================================================
// Action: save (Save text file content)
// ============================================================================
if ($action === 'save') {
    $filename = trim($input['file'] ?? 'index.php');
    if (empty($filename)) $filename = 'index.php';

    $content = $input['content'] ?? '';

    $filePath = safe_site_path($code, $filename);
    if (!$filePath) {
        echo json_encode(['success' => false, 'error' => 'Invalid file path.']);
        exit;
    }

    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    if (file_put_contents($filePath, $content) === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to write file. Check server write permissions.']);
        exit;
    }

    // Update resource updated_at timestamp
    $pdo->prepare("UPDATE resources SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$resource['id']]);

    echo json_encode([
        'success' => true,
        'file' => $filename,
        'size' => filesize($filePath),
        'summary' => get_site_summary($code)
    ]);
    exit;
}

// ============================================================================
// Action: create_file (Create new file or directory)
// ============================================================================
if ($action === 'create_file') {
    $filename = trim($input['file'] ?? '');
    $isDir = !empty($input['is_dir']);
    $initialContent = $input['content'] ?? '';

    if (empty($filename)) {
        echo json_encode(['success' => false, 'error' => 'Please provide a file name.']);
        exit;
    }

    $targetPath = safe_site_path($code, $filename);
    if (!$targetPath) {
        echo json_encode(['success' => false, 'error' => 'Invalid file name or directory traversal detected.']);
        exit;
    }

    if (file_exists($targetPath)) {
        echo json_encode(['success' => false, 'error' => 'A file or folder with this name already exists.']);
        exit;
    }

    if ($isDir) {
        if (!@mkdir($targetPath, 0777, true)) {
            echo json_encode(['success' => false, 'error' => 'Failed to create directory.']);
            exit;
        }
    } else {
        $parentDir = dirname($targetPath);
        if (!is_dir($parentDir)) {
            @mkdir($parentDir, 0777, true);
        }
        if (file_put_contents($targetPath, $initialContent) === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to create file.']);
            exit;
        }
    }

    echo json_encode([
        'success' => true,
        'file' => $filename,
        'summary' => get_site_summary($code)
    ]);
    exit;
}

// ============================================================================
// Action: delete_file (Delete a file or directory)
// ============================================================================
if ($action === 'delete_file') {
    $filename = trim($input['file'] ?? '');
    if (empty($filename)) {
        echo json_encode(['success' => false, 'error' => 'Please specify a file to delete.']);
        exit;
    }

    $targetPath = safe_site_path($code, $filename);
    if (!$targetPath || !file_exists($targetPath)) {
        echo json_encode(['success' => false, 'error' => 'File not found.']);
        exit;
    }

    // Do not allow deleting the root directory itself
    if ($targetPath === realpath($siteDir)) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete root site folder. Delete the resource instead.']);
        exit;
    }

    if (is_dir($targetPath)) {
        $it = new RecursiveDirectoryIterator($targetPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($targetPath);
    } else {
        @unlink($targetPath);
    }

    echo json_encode([
        'success' => true,
        'deleted' => $filename,
        'summary' => get_site_summary($code)
    ]);
    exit;
}

// ============================================================================
// Action: upload (Upload files or ZIP archive)
// ============================================================================
if ($action === 'upload') {
    if (empty($_FILES['files'])) {
        echo json_encode(['success' => false, 'error' => 'No files uploaded.']);
        exit;
    }

    $uploadedFiles = $_FILES['files'];
    $extractZip = !empty($_POST['extract_zip']);
    $subfolder = trim($_POST['subfolder'] ?? '');

    $targetBaseDir = empty($subfolder) ? $siteDir : safe_site_path($code, $subfolder);
    if (!$targetBaseDir || !is_dir($targetBaseDir)) {
        $targetBaseDir = $siteDir;
    }

    $countSaved = 0;
    $errors = [];

    // Normalize multiple file array
    $fileArray = [];
    if (is_array($uploadedFiles['name'])) {
        $fileCount = count($uploadedFiles['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
                $fileArray[] = [
                    'name' => $uploadedFiles['name'][$i],
                    'tmp_name' => $uploadedFiles['tmp_name'][$i],
                    'size' => $uploadedFiles['size'][$i],
                ];
            }
        }
    } else {
        if ($uploadedFiles['error'] === UPLOAD_ERR_OK) {
            $fileArray[] = [
                'name' => $uploadedFiles['name'],
                'tmp_name' => $uploadedFiles['tmp_name'],
                'size' => $uploadedFiles['size'],
            ];
        }
    }

    foreach ($fileArray as $file) {
        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // If it's a zip archive and extraction requested (or only zip uploaded)
        if ($ext === 'zip' && ($extractZip || count($fileArray) === 1)) {
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entryName = $zip->getNameIndex($i);
                        // Prevent zip slip traversal
                        $entryName = str_replace(['\\', "\0"], ['/', ''], $entryName);
                        $parts = explode('/', $entryName);
                        $safeParts = [];
                        foreach ($parts as $p) {
                            if ($p === '' || $p === '.') continue;
                            if ($p === '..') continue 2; // skip dangerous entry
                            $safeParts[] = $p;
                        }
                        $cleanEntry = implode('/', $safeParts);
                        if (empty($cleanEntry)) continue;

                        $dest = $targetBaseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanEntry);

                        if (substr($entryName, -1) === '/') {
                            @mkdir($dest, 0777, true);
                        } else {
                            $parent = dirname($dest);
                            if (!is_dir($parent)) {
                                @mkdir($parent, 0777, true);
                            }
                            $stream = $zip->getStream($zip->getNameIndex($i));
                            if ($stream) {
                                file_put_contents($dest, stream_get_contents($stream));
                                fclose($stream);
                                $countSaved++;
                            }
                        }
                    }
                    $zip->close();
                    continue;
                }
            }
        }

        // Standard file save
        $destPath = safe_site_path($code, (empty($subfolder) ? '' : $subfolder . '/') . $origName);
        if ($destPath) {
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $countSaved++;
            } else {
                $errors[] = "Could not move {$origName}";
            }
        } else {
            $errors[] = "Invalid filename {$origName}";
        }
    }

    echo json_encode([
        'success' => true,
        'uploaded_count' => $countSaved,
        'errors' => $errors,
        'summary' => get_site_summary($code)
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
