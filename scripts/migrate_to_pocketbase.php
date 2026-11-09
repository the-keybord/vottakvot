<?php
/**
 * TikTak - Complete Live MySQL to PocketBase Migration CLI Tool
 *
 * Usage:
 *   php scripts/migrate_to_pocketbase.php --email="admin@example.com" --password="your_pb_password"
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/PocketBase.php';

// Parse CLI options
$options = getopt('', ['email:', 'password:', 'url:']);
$email = $options['email'] ?? getenv('POCKETBASE_ADMIN_EMAIL') ?: (defined('POCKETBASE_ADMIN_EMAIL') ? POCKETBASE_ADMIN_EMAIL : '');
$password = $options['password'] ?? getenv('POCKETBASE_ADMIN_PASSWORD') ?: (defined('POCKETBASE_ADMIN_PASSWORD') ? POCKETBASE_ADMIN_PASSWORD : '');
$pbUrl = $options['url'] ?? getenv('POCKETBASE_URL') ?: (defined('POCKETBASE_URL') ? POCKETBASE_URL : 'https://db.zece.info');

echo "=========================================================\n";
echo "  TikTak: Live MySQL -> PocketBase Migration Tool\n";
echo "  Target: $pbUrl\n";
echo "=========================================================\n\n";

if (empty($email) || empty($password)) {
    echo "[!] Superuser email or password not provided.\n";
    echo "    Please run:\n";
    echo "    php scripts/migrate_to_pocketbase.php --email=\"admin@example.com\" --password=\"your_password\"\n\n";
    exit(1);
}

$pb = new PocketBase($pbUrl);

echo "[1/5] Authenticating with PocketBase as superuser ($email)...\n";
$authRes = $pb->authAsSuperuser($email, $password);

if (empty($authRes['token'])) {
    echo "[-] Authentication failed!\n";
    echo "    Response: " . json_encode($authRes, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

echo "[+] Authenticated successfully! Superuser token acquired.\n\n";

// [2/5] Fetch existing collections and ensure required collections exist
echo "[2/5] Ensuring schema collections exist on $pbUrl...\n";
$existingCollections = $pb->getCollections();
$existingNames = array_column($existingCollections, 'name');

$schemaFile = __DIR__ . '/../pocketbase_schema.json';
if (!file_exists($schemaFile)) {
    echo "[-] Schema file not found: $schemaFile\n";
    exit(1);
}
$collectionsToCreate = json_decode(file_get_contents($schemaFile), true);

foreach ($collectionsToCreate as $colDef) {
    $colName = $colDef['name'];
    if (in_array($colName, $existingNames)) {
        echo "    Collection '{$colName}' already exists. Updating API rules...\n";
        $pb->updateCollection($colName, [
            'listRule' => $colDef['listRule'] ?? '',
            'viewRule' => $colDef['viewRule'] ?? '',
            'createRule' => $colDef['createRule'] ?? '',
            'updateRule' => $colDef['updateRule'] ?? '',
            'deleteRule' => $colDef['deleteRule'] ?? '',
        ]);
    } else {
        echo "    Creating collection '{$colName}'...\n";
        $createRes = $pb->createCollection($colDef);
        if (!empty($createRes['id'])) {
            echo "    [+] Collection '{$colName}' created successfully!\n";
        } else {
            echo "    [-] Failed to create collection '{$colName}': " . json_encode($createRes) . "\n";
        }
    }
}
echo "\n";

// [3/5] Connect to Live MySQL
echo "[3/5] Connecting to live MySQL database...\n";
$mysqlPdo = null;
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $mysqlPdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    echo "    [+] Connected to MySQL on " . DB_HOST . ":" . DB_PORT . " (DB: " . DB_NAME . ")\n\n";
} catch (Exception $e) {
    echo "    [-] Could not connect to MySQL: " . $e->getMessage() . "\n";
    echo "    [*] Falling back to tiktak_database.sql file if needed.\n\n";
}

// [4/5] Migrate Users
echo "[4/5] Migrating Users...\n";
$userMap = []; // mysql_user_id => pb_user_id
$pfx = defined('POCKETBASE_TABLE_PREFIX') ? POCKETBASE_TABLE_PREFIX : 'tiktak_';

if ($mysqlPdo) {
    $mysqlUsers = $mysqlPdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mysqlUsers as $u) {
        $username = $u['username'];
        $email = $username . '@tiktak.md';
        $existing = $pb->getFirstListItem($pfx . 'users', 'email = "' . addslashes($email) . '"');
        if ($existing) {
            echo "    [=] User '{$username}' already exists in PocketBase (ID: {$existing['id']})\n";
            $userMap[$u['id']] = $existing['id'];
        } else {
            $defaultPass = 'prof1234';
            $created = $pb->create($pfx . 'users', [
                'username' => $username,
                'email' => $email,
                'password' => $defaultPass,
                'passwordConfirm' => $defaultPass,
                'name' => $u['name'] ?? $username,
            ]);
            if (!empty($created['id'])) {
                echo "    [+] Created user '{$username}' in PocketBase (ID: {$created['id']})\n";
                $userMap[$u['id']] = $created['id'];
            } else {
                echo "    [-] Error creating user '{$username}': " . json_encode($created) . "\n";
            }
        }
    }
} else {
    // Default prof user check
    $existing = $pb->getFirstListItem($pfx . 'users', 'email = "' . DEFAULT_PROF_USERNAME . '@tiktak.md"');
    if ($existing) {
        $userMap[1] = $existing['id'];
    } else {
        $created = $pb->create($pfx . 'users', [
            'username' => DEFAULT_PROF_USERNAME,
            'email' => DEFAULT_PROF_USERNAME . '@tiktak.md',
            'password' => 'prof1234',
            'passwordConfirm' => 'prof1234',
            'name' => DEFAULT_PROF_NAME,
        ]);
        if (!empty($created['id'])) {
            $userMap[1] = $created['id'];
        }
    }
}
echo "\n";

// [5/5] Migrate Resources, Files & Logs
echo "[5/5] Migrating Resources, Files, and Access Logs...\n";
$resMap = []; // mysql_resource_id => pb_resource_id

if ($mysqlPdo) {
    // 1. Resources
    $resources = $mysqlPdo->query("SELECT * FROM resources ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo "    Found " . count($resources) . " resources in MySQL.\n";
    $resCount = 0;

    foreach ($resources as $r) {
        $code = $r['code'];
        $pbUserId = $userMap[$r['user_id']] ?? '';

        $existing = $pb->getFirstListItem($pfx . 'resources', 'code = "' . addslashes($code) . '"');
        if ($existing) {
            $resMap[$r['id']] = $existing['id'];
        } else {
            $created = $pb->create($pfx . 'resources', [
                'code' => $code,
                'user_id' => $pbUserId,
                'title' => $r['title'] ?? '',
                'type' => $r['type'] ?? 'redirect',
                'content_link' => $r['content_link'] ?? '',
                'content_text' => $r['content_text'] ?? '',
                'visits_count' => (int)($r['visits_count'] ?? 0),
                'expires_at' => !empty($r['expires_at']) ? date('Y-m-d H:i:s.000\Z', strtotime($r['expires_at'])) : null,
            ]);
            if (!empty($created['id'])) {
                $resMap[$r['id']] = $created['id'];
                $resCount++;
            }
        }
    }
    echo "    [+] Migrated $resCount new resources (Total matched: " . count($resMap) . ")\n";

    // 2. Resource Files
    $files = $mysqlPdo->query("SELECT * FROM resource_files ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo "    Found " . count($files) . " resource files in MySQL.\n";
    $fileCount = 0;

    foreach ($files as $f) {
        $pbResId = $resMap[$f['resource_id']] ?? '';
        $storedFilename = $f['stored_filename'];

        $existing = $pb->getFirstListItem($pfx . 'resource_files', 'stored_filename = "' . addslashes($storedFilename) . '"');
        if (!$existing) {
            $created = $pb->create($pfx . 'resource_files', [
                'resource_id' => $pbResId,
                'code' => $f['code'],
                'original_filename' => $f['original_filename'],
                'stored_filename' => $storedFilename,
                's3_key' => $f['s3_key'],
                'file_size' => (int)$f['file_size'],
                'mime_type' => $f['mime_type'] ?? '',
                'upload_date' => $f['upload_date'] ?? date('Y-m-d'),
                'ip_hash' => $f['ip_hash'] ?? '',
                'downloads_count' => (int)($f['downloads_count'] ?? 0),
                'is_pinned' => (bool)$f['is_pinned'],
            ]);
            if (!empty($created['id'])) {
                $fileCount++;
            }
        }
    }
    echo "    [+] Migrated $fileCount resource files.\n";

    // 3. Access Logs
    $logs = $mysqlPdo->query("SELECT * FROM access_logs ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo "    Found " . count($logs) . " access logs in MySQL.\n";
    $logCount = 0;

    foreach ($logs as $l) {
        $pbResId = $resMap[$l['resource_id']] ?? '';
        if ($pbResId) {
            $created = $pb->create($pfx . 'access_logs', [
                'resource_id' => $pbResId,
                'accessed_at' => !empty($l['accessed_at']) ? date('Y-m-d H:i:s.000\Z', strtotime($l['accessed_at'])) : null,
                'ip_hash' => $l['ip_hash'] ?? '',
                'user_agent' => $l['user_agent'] ?? '',
            ]);
            if (!empty($created['id'])) {
                $logCount++;
            }
        }
    }
    echo "    [+] Migrated $logCount access logs.\n";
}

echo "\n=========================================================\n";
echo "  [SUCCESS] All Live Data Successfully Migrated to PocketBase!\n";
echo "=========================================================\n";
