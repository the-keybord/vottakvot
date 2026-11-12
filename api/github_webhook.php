<?php
/**
 * TikTak - GitHub Webhook Endpoint for Micro-hosting Auto-Sync
 * Configure in GitHub: Repo -> Settings -> Webhooks -> Add webhook
 *   Payload URL: https://tik.tak.md/api/github_webhook.php?code=XXXXXX
 *   Content type: application/json
 *   Secret: (copied from Site Manager -> GitHub Settings)
 *   Events: Just the push event
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/GithubSync.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$code = strtoupper(trim($_GET['code'] ?? ''));
if (empty($code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing resource code parameter']);
    exit;
}

$rawBody = file_get_contents('php://input');
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
$sigHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT * FROM resources WHERE code = ? LIMIT 1");
$stmt->execute([$code]);
$resource = $stmt->fetch();

if (!$resource || ($resource['type'] ?? '') !== 'hosting') {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Hosting resource not found']);
    exit;
}

$config = GithubSync::decodeConfig($resource['content_link'] ?? '');
if (!$config) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No GitHub repository is linked to this resource']);
    exit;
}

$secret = (string)($config['webhook_secret'] ?? '');
if ($secret === '') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Webhook secret is not configured']);
    exit;
}

// Verify GitHub HMAC-SHA256 signature
$expectedSig = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
if (!is_string($sigHeader) || !hash_equals($expectedSig, $sigHeader)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid webhook signature']);
    exit;
}

if ($event === 'ping') {
    echo json_encode([
        'success' => true,
        'message' => 'TikTak GitHub webhook verified for ' . $code,
        'repo' => $config['owner'] . '/' . $config['repo'] . '@' . ($config['branch'] ?? 'main'),
    ]);
    exit;
}

if ($event !== 'push') {
    echo json_encode([
        'success' => true,
        'skipped' => true,
        'reason' => "Ignored event '{$event}'",
    ]);
    exit;
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    // Support form-urlencoded fallback if user forgot application/json
    if (!empty($_POST['payload'])) {
        $payload = json_decode($_POST['payload'], true);
    }
}

$targetBranch = $config['branch'] ?? 'main';
$pushedRef = $payload['ref'] ?? '';
if ($pushedRef !== '' && $pushedRef !== 'refs/heads/' . $targetBranch) {
    echo json_encode([
        'success' => true,
        'skipped' => true,
        'reason' => "Push was to '{$pushedRef}', configured branch is '{$targetBranch}'",
    ]);
    exit;
}

$siteDir = ensure_site_dir($code);
$syncResult = GithubSync::syncToDirectory($config, $siteDir);

if (!$syncResult['success']) {
    http_response_code(500);
    echo json_encode($syncResult);
    exit;
}

$config['last_synced_at'] = $syncResult['synced_at'];
if (!empty($payload['after'])) {
    $config['last_commit'] = substr((string)$payload['after'], 0, 7);
}

$pdo->prepare("UPDATE resources SET content_link = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
    ->execute([json_encode($config, JSON_UNESCAPED_SLASHES), $resource['id']]);

echo json_encode([
    'success' => true,
    'code' => $code,
    'files_extracted' => $syncResult['files_extracted'],
    'synced_at' => $syncResult['synced_at'],
    'commit' => $config['last_commit'] ?? null,
]);

