<?php
/**
 * Test Course Hub Cards Persistence & Files API Integration
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();

echo "=== 1. Creating Test Course Resource ===\n";
$code = 'CRS' . rand(1000, 9999);
$userId = 'test_teacher_1';

$stmt = $pdo->prepare("INSERT INTO resources (user_id, code, title, type, content_text) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$userId, $code, 'Interactive Coding Course', 'course', json_encode(['cards' => []])]);
$resId = $pdo->lastInsertId();
echo "Course resource created with ID: $resId, Code: $code\n";

echo "\n=== 2. Testing Card Saving (update_course_cards query & PocketBasePDO) ===\n";
$testCards = [
    [
        'id' => 'card_scratch',
        'title' => 'Scratch Lab',
        'url' => 'https://scratch.mit.edu',
        'icon' => '🐱',
        'color' => '#f59e0b',
        'desc' => 'Animation & Game project'
    ],
    [
        'id' => 'card_slides',
        'title' => 'Lesson 1 Slides',
        'url' => 'https://docs.google.com/presentation',
        'icon' => '📊',
        'color' => '#3b82f6',
        'desc' => 'Variables and loops'
    ]
];

$payload = json_encode(['cards' => $testCards, 'updated_at' => date('Y-m-d H:i:s')]);
$updateStmt = $pdo->prepare("UPDATE resources SET content_text = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
$updateStmt->execute([$payload, $resId]);
echo "Update executed successfully.\n";

echo "\n=== 3. Fetching and Asserting Saved Cards from Database ===\n";
$fetchStmt = $pdo->prepare("SELECT * FROM resources WHERE code = ? LIMIT 1");
$fetchStmt->execute([$code]);
$row = $fetchStmt->fetch();

if (!$row) {
    echo "FAILED: Course resource not found by code $code\n";
    exit(1);
}

$decoded = json_decode($row['content_text'] ?? '', true);
if (!is_array($decoded) || empty($decoded['cards'])) {
    echo "FAILED: Cards data not saved in content_text!\nGot: " . ($row['content_text'] ?? 'null') . "\n";
    exit(1);
}

$savedCards = $decoded['cards'];
if (count($savedCards) !== 2) {
    echo "FAILED: Expected 2 cards, got " . count($savedCards) . "\n";
    exit(1);
}

if ($savedCards[0]['title'] !== 'Scratch Lab' || $savedCards[1]['title'] !== 'Lesson 1 Slides') {
    echo "FAILED: Card content mismatch!\n";
    var_dump($savedCards);
    exit(1);
}
echo "PASS: Cards successfully persisted in database!\n";

echo "\n=== 4. Cleaning up Test Data ===\n";
$pdo->prepare("DELETE FROM resources WHERE id = ?")->execute([$resId]);
echo "Test resource cleaned up.\n";

echo "\n=== 5. Testing Files List API Integration for Course Resource ===\n";
$_GET['action'] = 'list';
$_GET['code'] = 'MATH101'; // or any code
ob_start();
require __DIR__ . '/../api/files.php';
$responseStr = ob_get_clean();
