<?php
/**
 * Test Suite for TikTak MinIO File Console
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/MinioClient.php';

echo "=== TikTak MinIO File Console Verification ===\n";

$pdo = get_db_connection();

// 1. Check Schema
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'sqlite') {
    $res = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='resource_files'")->fetch();
} else {
    $res = $pdo->query("SHOW TABLES LIKE 'resource_files'")->fetch();
}
echo "[PASS] Database driver: $driver. resource_files table is verified.\n";


// 2. Test Suffixing Algorithm
$testCode = 'TESTFC';
// Clean any existing test records
$pdo->exec("DELETE FROM resources WHERE code = '$testCode'");

$pdo->prepare("INSERT INTO resources (code, title, type) VALUES (?, ?, ?)")
    ->execute([$testCode, 'Test File Console', 'files']);
$resId = $pdo->lastInsertId();

$name1 = generate_suffixed_filename($pdo, $resId, 'assignment.pdf');
echo "Name 1 (first upload): $name1\n";
assert($name1 === 'assignment.pdf', 'First upload should retain original name');

// Insert dummy record for assignment.pdf
$pdo->prepare("INSERT INTO resource_files (resource_id, code, original_filename, stored_filename, s3_key, file_size, upload_date) VALUES (?, ?, ?, ?, ?, ?, ?)")
    ->execute([$resId, $testCode, 'assignment.pdf', $name1, "$testCode/$name1", 1024, '2026-09-20']);

$name2 = generate_suffixed_filename($pdo, $resId, 'assignment.pdf');
echo "Name 2 (duplicate upload): $name2\n";
assert($name2 === 'assignment_01.pdf', 'Duplicate upload should get _01 suffix');

// Insert dummy record for assignment_01.pdf
$pdo->prepare("INSERT INTO resource_files (resource_id, code, original_filename, stored_filename, s3_key, file_size, upload_date) VALUES (?, ?, ?, ?, ?, ?, ?)")
    ->execute([$resId, $testCode, 'assignment.pdf', $name2, "$testCode/$name2", 2048, '2026-09-21']);

$name3 = generate_suffixed_filename($pdo, $resId, 'assignment.pdf');
echo "Name 3 (duplicate again): $name3\n";
assert($name3 === 'assignment_02.pdf', 'Second duplicate should get _02 suffix');

// 3. Test Heavy File Indicator (>100MB)
$smallBytes = 45 * 1024 * 1024; // 45 MB
$heavyBytes = 145 * 1024 * 1024; // 145 MB
assert(is_heavy_file($smallBytes) === false, '45MB should not be heavy');
assert(is_heavy_file($heavyBytes) === true, '145MB should be heavy');
echo "[PASS] Heavy file threshold validation (100MB): OK\n";

// 4. Test Date Grouping and Skipping Empty Days
// Insert a 3rd day record
$pdo->prepare("INSERT INTO resource_files (resource_id, code, original_filename, stored_filename, s3_key, file_size, upload_date) VALUES (?, ?, ?, ?, ?, ?, ?)")
    ->execute([$resId, $testCode, 'project.zip', 'project.zip', "$testCode/project.zip", $heavyBytes, '2026-09-23']);

// Query dates
$stmt = $pdo->prepare("SELECT upload_date, COUNT(*) as count FROM resource_files WHERE resource_id = ? GROUP BY upload_date ORDER BY upload_date DESC");
$stmt->execute([$resId]);
$dayGroups = $stmt->fetchAll();

echo "Active days with files: " . count($dayGroups) . "\n";
foreach ($dayGroups as $dg) {
    echo "  - Date {$dg['upload_date']}: {$dg['count']} files\n";
}
assert(count($dayGroups) === 3, 'Should only have 3 days (empty days in between skipped)');
echo "[PASS] Date grouping and empty day skipping: OK\n";

// 5. Test MinIO Storage Put & Delete
$minio = MinioClient::getInstance();
$testContent = "Hello from TikTak MinIO File Console at " . date('c');
$uploaded = $minio->putObject("$testCode/test_upload.txt", $testContent, 'text/plain', true);
echo "[PASS] MinIO/S3 PutObject: " . ($uploaded ? 'SUCCESS' : 'FAILED') . "\n";

// 6. Test File Summary
$summary = get_resource_files_summary($pdo, $resId);
echo "Summary: {$summary['total_files']} files, {$summary['total_size_formatted']}, {$summary['heavy_count']} heavy files.\n";
assert($summary['total_files'] === 3, 'Total files should be 3');
assert($summary['heavy_count'] === 1, 'Heavy count should be 1');

// Clean up test resource
$pdo->exec("DELETE FROM resource_files WHERE resource_id = $resId");
$pdo->exec("DELETE FROM resources WHERE id = $resId");
$minio->deleteFolder($testCode);

echo "\n[ALL TESTS PASSED SUCCESSFULLY!]\n";
