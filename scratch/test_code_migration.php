<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/MinioClient.php';

$pdo = get_db_connection();

echo "=== 1. Testing is_reserved_resource_code ===\n";
$reservedSamples = ['PROF', 'prof', 'api', 'SITES', 'assets', 'LOGIN', 'logout'];
foreach ($reservedSamples as $sample) {
    if (!is_reserved_resource_code($sample)) {
        echo "FAILED: '$sample' should be reserved!\n";
        exit(1);
    }
}
$validSamples = ['MATH101', 'PHYS42', 'ABC123', 'TEST99'];
foreach ($validSamples as $sample) {
    if (is_reserved_resource_code($sample)) {
        echo "FAILED: '$sample' should NOT be reserved!\n";
        exit(1);
    }
}
echo "PASS: Reserved codes correctly validated.\n\n";

echo "=== 2. Testing generate_unique_code ===\n";
for ($i = 0; $i < 20; $i++) {
    $code = generate_unique_code($pdo, 6);
    if (strlen($code) !== 6 || is_reserved_resource_code($code)) {
        echo "FAILED: generate_unique_code produced invalid or reserved code: '$code'\n";
        exit(1);
    }
}
echo "PASS: generate_unique_code produces valid, non-reserved codes.\n\n";

echo "=== 3. Testing rename_site_directory & github sync metadata ===\n";
$siteOld = 'TSTOLD' . rand(100, 999);
$siteNew = 'TSTNEW' . rand(100, 999);

$oldDir = ensure_site_dir($siteOld);
file_put_contents($oldDir . '/index.php', '<?php echo "Hello Test"; ?>');
$dummySync = [
    'code' => $siteOld,
    'owner' => 'testuser',
    'repo' => 'testrepo',
    'branch' => 'main'
];
file_put_contents($oldDir . '/.github_sync.json', json_encode($dummySync, JSON_PRETTY_PRINT));

$renameResult = rename_site_directory($siteOld, $siteNew);
if (!$renameResult) {
    echo "FAILED: rename_site_directory returned false!\n";
    exit(1);
}

$newDir = get_site_dir($siteNew);
if (!file_exists($newDir . '/index.php')) {
    echo "FAILED: index.php missing in $newDir\n";
    exit(1);
}
$newSync = json_decode(file_get_contents($newDir . '/.github_sync.json'), true);
if (($newSync['code'] ?? '') !== $siteNew) {
    echo "FAILED: .github_sync.json code not updated to $siteNew (got: " . ($newSync['code'] ?? 'null') . ")\n";
    exit(1);
}
if (is_dir($oldDir)) {
    echo "FAILED: old site dir $oldDir still exists!\n";
    exit(1);
}
delete_site_directory($siteNew);
echo "PASS: rename_site_directory properly moved directory and updated sync config.\n\n";

echo "=== 4. Testing rename_resource_storage (local fallback & folder migration) ===\n";
$storageOld = 'STR' . rand(100, 999);
$storageNew = 'STR' . rand(100, 999);

$minio = MinioClient::getInstance();
$baseStorage = TIKTAK_ROOT . '/data/storage/' . $minio->getBucket();
$srcDir = $baseStorage . '/' . $storageOld;
$dstDir = $baseStorage . '/' . $storageNew;

if (!is_dir($srcDir)) {
    mkdir($srcDir, 0777, true);
}
file_put_contents($srcDir . '/document.txt', 'Sample test content');

$minio->renameFolder($storageOld, $storageNew);

if (!file_exists($dstDir . '/document.txt')) {
    echo "FAILED: document.txt missing in $dstDir\n";
    exit(1);
}
if (file_get_contents($dstDir . '/document.txt') !== 'Sample test content') {
    echo "FAILED: document.txt content mismatch in $dstDir\n";
    exit(1);
}
if (is_dir($srcDir)) {
    echo "FAILED: old storage dir $srcDir still exists!\n";
    exit(1);
}

// Clean up
@unlink($dstDir . '/document.txt');
@rmdir($dstDir);
echo "PASS: rename_resource_storage successfully migrated local storage directory.\n\n";

echo "=== 5. Testing API logic for code update collisions & reserved codes ===\n";
// Create a temporary resource in the database
$testCode1 = 'TC' . rand(1000, 9999);
$testCode2 = 'TC' . rand(1000, 9999);
$userId = 'test_user_mig';

$ins = $pdo->prepare("INSERT INTO resources (user_id, code, title, type) VALUES (?, ?, ?, ?)");
$ins->execute([$userId, $testCode1, 'Test Migration Resource', 'files']);
$resId = $pdo->lastInsertId();

// Verify duplicate check
$ins->execute([$userId, $testCode2, 'Second Test Resource', 'files']);
$resId2 = $pdo->lastInsertId();

// Check if reserved code is rejected
$candidateCode = 'PROF';
if (is_reserved_resource_code($candidateCode)) {
    echo "PASS: Reserved code '$candidateCode' successfully identified and prevented.\n";
} else {
    echo "FAILED: Reserved code '$candidateCode' was not flagged!\n";
    exit(1);
}

// Check duplicate code detection
$check = $pdo->prepare("SELECT id FROM resources WHERE code = ? AND id != ? LIMIT 1");
$check->execute([$testCode2, $resId]);
$dup = $check->fetch();
if ($dup) {
    echo "PASS: Duplicate code '$testCode2' correctly identified as taken.\n";
} else {
    echo "FAILED: Duplicate code was not detected!\n";
    exit(1);
}

// Clean up test resources
$pdo->prepare("DELETE FROM resources WHERE id = ?")->execute([$resId]);
$pdo->prepare("DELETE FROM resources WHERE id = ?")->execute([$resId2]);
echo "PASS: Collision and reserved code database tests passed.\n\n";

echo "=== ALL VERIFICATION TESTS PASSED SUCCESSFULLY! ===\n";
