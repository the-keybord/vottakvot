<?php
/**
 * TikTak - Resources API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? '';
$pdo = get_db_connection();

require_once __DIR__ . '/../includes/MinioClient.php';
require_once __DIR__ . '/../includes/GithubSync.php';

if ($action === 'create') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $code = strtoupper(trim($input['code'] ?? ''));
    $title = trim($input['title'] ?? '');
    $rawType = $input['type'] ?? 'redirect';
    $type = in_array($rawType, ['redirect', 'text', 'files', 'hosting', 'links']) ? $rawType : 'redirect';
    $contentLink = trim($input['content_link'] ?? '');
    $contentText = trim($input['content_text'] ?? '');
    $expiryPreset = $input['expiry_preset'] ?? 'never';
    $customExpiry = $input['custom_expiry'] ?? null;

    $userId = $_SESSION['tiktak_user_id'] ?? null;

    if (empty($code) || strlen($code) < 3 || strlen($code) > 10) {
        $code = generate_unique_code($pdo);
    } else {
        // If code already exists, silently generate a new unique one
        $stmt = $pdo->prepare("SELECT id FROM resources WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            $code = generate_unique_code($pdo);
        }
    }

    // Validate type content
    if ($type === 'redirect') {
        if (empty($contentLink)) {
            echo json_encode(['success' => false, 'error' => 'Please provide a valid destination URL.']);
            exit;
        }
        if (!preg_match('~^https?://~i', $contentLink)) {
            $contentLink = 'https://' . $contentLink;
        }
    } elseif ($type === 'text') {
        if (empty($contentText)) {
            echo json_encode(['success' => false, 'error' => 'Text note content cannot be empty.']);
            exit;
        }
    } elseif ($type === 'hosting') {
        $siteDir = ensure_site_dir($code);
        $ghEnabled = !empty($input['github_enabled']);
        $ghRepoInput = trim($input['github_repo'] ?? '');

        if ($ghEnabled && $ghRepoInput !== '') {
            $ghBranch = trim($input['github_branch'] ?? '');
            $ghSubdir = trim($input['github_subdir'] ?? '');
            $ghToken = trim($input['github_token'] ?? '');

            $ghConfig = GithubSync::parseRepoInput($ghRepoInput, $ghBranch, $ghSubdir);
            if (!$ghConfig) {
                echo json_encode(['success' => false, 'error' => 'Invalid GitHub repository URL or owner/repo format.']);
                exit;
            }
            $ghConfig['enabled'] = true;
            $ghConfig['code'] = $code;
            if ($ghToken !== '') {
                $ghConfig['token'] = $ghToken;
            }
            $ghConfig['webhook_secret'] = bin2hex(random_bytes(16));

            $syncRes = GithubSync::syncToDirectory($ghConfig, $siteDir);
            if (!$syncRes['success']) {
                echo json_encode(['success' => false, 'error' => $syncRes['error'] ?? 'Failed to sync from GitHub.']);
                exit;
            }
            $ghConfig['last_synced_at'] = $syncRes['synced_at'];
            $contentLink = json_encode($ghConfig, JSON_UNESCAPED_SLASHES);
            if (empty($title)) {
                $title = $ghConfig['owner'] . '/' . $ghConfig['repo'];
            }
        } else {
            $indexContent = !empty($contentText) ? $contentText : get_default_site_template($code, $title);
            $indexPath = safe_site_path($code, 'index.php');
            if ($indexPath) {
                file_put_contents($indexPath, $indexContent);
            }
        }
    } elseif ($type === 'links') {
        // Parse and clean link list
        $links = [];
        if (!empty($input['links']) && is_array($input['links'])) {
            $links = $input['links'];
        } elseif (!empty($contentText)) {
            $decoded = json_decode($contentText, true);
            if (is_array($decoded)) {
                $links = $decoded;
            }
        }
        $cleanLinks = [];
        foreach ($links as $lnk) {
            $lTitle = trim($lnk['title'] ?? '');
            $lUrl = trim($lnk['url'] ?? '');
            if ($lTitle === '' && $lUrl === '') continue;
            if ($lTitle === '') $lTitle = $lUrl;
            if (!empty($lUrl)) {
                if (preg_match('/^[A-Za-z0-9]{3,10}$/', $lUrl)) {
                    $lUrl = '/code=' . strtoupper($lUrl);
                } elseif (preg_match('/^code=([A-Za-z0-9]{3,10})$/i', $lUrl, $m)) {
                    $lUrl = '/code=' . strtoupper($m[1]);
                } elseif (!preg_match('~^(https?://|/|#)~i', $lUrl)) {
                    $lUrl = 'https://' . $lUrl;
                }
            }
            $cleanLinks[] = [
                'title' => $lTitle,
                'url' => $lUrl
            ];
        }
        $contentText = json_encode($cleanLinks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    if (empty($title)) {
        if ($type === 'redirect') {
            $title = parse_url($contentLink, PHP_URL_HOST) ?? 'Redirect Link';
        } elseif ($type === 'text') {
            $title = 'Shared Note ' . $code;
        } elseif ($type === 'hosting') {
            $title = 'Micro Site ' . $code;
        } elseif ($type === 'links') {
            $title = 'Link List ' . $code;
        } else {
            $title = 'File Console ' . $code;
        }
    }

    // Calculate Expiration
    $expiresAt = null;
    $now = time();
    switch ($expiryPreset) {
        case '1h':
            $expiresAt = date('Y-m-d H:i:s', $now + 3600);
            break;
        case '6h':
            $expiresAt = date('Y-m-d H:i:s', $now + 21600);
            break;
        case '24h':
            $expiresAt = date('Y-m-d H:i:s', $now + 86400);
            break;
        case '3d':
            $expiresAt = date('Y-m-d H:i:s', $now + (86400 * 3));
            break;
        case '7d':
            $expiresAt = date('Y-m-d H:i:s', $now + (86400 * 7));
            break;
        case '30d':
            $expiresAt = date('Y-m-d H:i:s', $now + (86400 * 30));
            break;
        case 'custom':
            if (!empty($customExpiry)) {
                $expiresAt = date('Y-m-d H:i:s', strtotime($customExpiry));
            }
            break;
        case 'never':
        default:
            $expiresAt = null;
            break;
    }

    $insert = $pdo->prepare("INSERT INTO resources (user_id, code, title, type, content_link, content_text, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->execute([$userId, $code, $title, $type, $contentLink, $contentText, $expiresAt]);

    echo json_encode([
        'success' => true,
        'code' => $code,
        'url' => get_base_url() . '/code=' . $code,
        'site_url' => get_base_url() . '/sites/' . $code . '/'
    ]);
    exit;
}

if ($action === 'update') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['tiktak_user_id'] ?? '';
    $id = trim((string)($input['id'] ?? ''));
    $title = trim($input['title'] ?? '');
    $contentLink = trim($input['content_link'] ?? '');
    $contentText = trim($input['content_text'] ?? '');
    $newCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($input['code'] ?? '')));

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }

    $existingStmt = $pdo->prepare("SELECT * FROM resources WHERE id = ? AND user_id = ? LIMIT 1");
    $existingStmt->execute([$id, $userId]);
    $existingRes = $existingStmt->fetch();
    if (!$existingRes) {
        echo json_encode(['success' => false, 'error' => 'Resource not found']);
        exit;
    }

    $oldCode = $existingRes['code'];
    $finalCode = $oldCode;

    // Handle code change if requested
    if ($newCode !== '' && $newCode !== $oldCode) {
        if (strlen($newCode) < 3 || strlen($newCode) > 10) {
            echo json_encode(['success' => false, 'error' => 'Access code must be between 3 and 10 alphanumeric characters.']);
            exit;
        }

        // Check if new code is already in use
        $check = $pdo->prepare("SELECT id FROM resources WHERE code = ? AND id != ? LIMIT 1");
        $check->execute([$newCode, $id]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => "Access code '{$newCode}' is already taken by another resource."]);
            exit;
        }

        $finalCode = $newCode;

        // If hosting, rename site directory on disk
        if (($existingRes['type'] ?? '') === 'hosting') {
            $renameOk = rename_site_directory($oldCode, $newCode);
            if (!$renameOk) {
                echo json_encode(['success' => false, 'error' => "Failed to rename site folder on disk from '{$oldCode}' to '{$newCode}'."]);
                exit;
            }
        }

        // If files, update code in resource_files table
        if (($existingRes['type'] ?? '') === 'files') {
            try {
                $pdo->prepare("UPDATE resource_files SET code = ? WHERE resource_id = ?")->execute([$newCode, $id]);
            } catch (\Throwable $e) {
                // Ignore if files update encounters issues
            }
        }
    }

    if (($existingRes['type'] ?? '') === 'hosting') {
        // Preserve GitHub link config JSON stored in content_link, updating code inside it if code changed
        $savedConfig = GithubSync::decodeConfig($existingRes['content_link'] ?? '');
        if ($savedConfig) {
            $savedConfig['code'] = $finalCode;
            $contentLink = json_encode($savedConfig, JSON_UNESCAPED_SLASHES);
        } else {
            $contentLink = $existingRes['content_link'] ?? '';
        }
    } elseif (!empty($contentLink) && !preg_match('~^https?://~i', $contentLink)) {
        $contentLink = 'https://' . $contentLink;
    }

    if (!empty($input['links']) && is_array($input['links'])) {
        $cleanLinks = [];
        foreach ($input['links'] as $lnk) {
            $lTitle = trim($lnk['title'] ?? '');
            $lUrl = trim($lnk['url'] ?? '');
            if ($lTitle === '' && $lUrl === '') continue;
            if ($lTitle === '') $lTitle = $lUrl;
            if (!empty($lUrl)) {
                if (preg_match('/^[A-Za-z0-9]{3,10}$/', $lUrl)) {
                    $lUrl = '/code=' . strtoupper($lUrl);
                } elseif (preg_match('/^code=([A-Za-z0-9]{3,10})$/i', $lUrl, $m)) {
                    $lUrl = '/code=' . strtoupper($m[1]);
                } elseif (!preg_match('~^(https?://|/|#)~i', $lUrl)) {
                    $lUrl = 'https://' . $lUrl;
                }
            }
            $cleanLinks[] = [
                'title' => $lTitle,
                'url' => $lUrl
            ];
        }
        $contentText = json_encode($cleanLinks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // Only allow updating own resources
    $update = $pdo->prepare("UPDATE resources SET code = ?, title = ?, content_link = ?, content_text = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    $update->execute([$finalCode, $title, $contentLink, $contentText, $id, $userId]);

    echo json_encode([
        'success' => true,
        'code' => $finalCode,
        'url' => get_base_url() . '/code=' . $finalCode,
        'site_url' => get_base_url() . '/sites/' . $finalCode . '/'
    ]);
    exit;
}

if ($action === 'delete') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['tiktak_user_id'] ?? '';
    $id = trim((string)($input['id'] ?? ''));
    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }

    // Check resource before deleting to clean MinIO files or site folder
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$id, $userId]);
    $resource = $stmt->fetch();
    if ($resource) {

        if ($resource['type'] === 'files') {
            // Delete files in MinIO
            $minio = MinioClient::getInstance();
            $minio->deleteFolder($resource['code']);

            // Delete database file records
            $delFiles = $pdo->prepare("DELETE FROM resource_files WHERE resource_id = ?");
            $delFiles->execute([$id]);
        } elseif ($resource['type'] === 'hosting') {
            // Note: Site folder on disk is preserved so user files are never auto-deleted.
        }

        // Delete resource
        $del = $pdo->prepare("DELETE FROM resources WHERE id = ? AND user_id = ?");
        $del->execute([$id, $userId]);
    }

    echo json_encode(['success' => true]);
    exit;
}


if ($action === 'extend') {

    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['tiktak_user_id'] ?? '';
    $id = trim((string)($input['id'] ?? ''));
    $hours = (int)($input['hours'] ?? 2);
    if ($hours <= 0) $hours = 2;

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$id, $userId]);
    $resource = $stmt->fetch();

    if (!$resource) {
        echo json_encode(['success' => false, 'error' => 'Resource not found']);
        exit;
    }

    // If expired, add hours from NOW(). If not expired yet, add hours from current expires_at
    $baseTime = time();
    if (!empty($resource['expires_at']) && strtotime($resource['expires_at']) > $baseTime) {
        $baseTime = strtotime($resource['expires_at']);
    }
    $newExpiresAt = date('Y-m-d H:i:s', $baseTime + ($hours * 3600));

    $update = $pdo->prepare("UPDATE resources SET expires_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    $update->execute([$newExpiresAt, $id, $userId]);

    echo json_encode([
        'success' => true,
        'new_expires_at' => $newExpiresAt,
        'label' => format_expiry_status($newExpiresAt)['label']
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);


