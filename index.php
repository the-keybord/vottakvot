<?php
/**
 * TikTak - Public Gateway & Search Engine Homepage
 * tik.tak.md
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Detect code from query parameters or URI
$code = $_GET['code'] ?? '';

// Check if request uri is in format /code=XXXXXX or /XXXXXX
if (empty($code)) {
    $uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if (preg_match('~^code=([a-zA-Z0-9]{3,10})$~i', $uri, $matches)) {
        $code = $matches[1];
    } elseif (preg_match('~^([a-zA-Z0-9]{6})$~i', $uri, $matches) && strtolower($matches[1]) !== 'assets') {
        $code = $matches[1];
    }
}

$code = strtoupper(trim($code));

// If code is requested, handle lookup and routing
if (!empty($code)) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $resource = $stmt->fetch();

    if (!$resource) {
        $errorTitle = "Code Not Found";
        $errorMessage = "The code <strong class='view-code-tag'>$code</strong> does not exist or may have been deleted.";
        require __DIR__ . '/view.php';
        exit;
    }

    // Check expiration
    if (is_resource_expired($resource['expires_at'])) {
        $errorTitle = "Resource Expired";
        $errorMessage = "The material for code <strong class='view-code-tag'>$code</strong> has expired and is no longer accessible.";
        require __DIR__ . '/view.php';
        exit;
    }

    // Record visit
    record_resource_visit($pdo, $resource['id']);

    // Handle Type 1: Link Redirect
    if ($resource['type'] === 'redirect') {
        $destination = $resource['content_link'];
        if (!preg_match('~^https?://~i', $destination)) {
            $destination = 'https://' . $destination;
        }
        header("Location: " . $destination, true, 302);
        exit;
    }

    // Handle Type 4: Micro-hosting Web Directory
    if ($resource['type'] === 'hosting') {
        header("Location: /sites/" . $resource['code'] . "/", true, 302);
        exit;
    }

    // Handle Type 2, 3 & 5: Text / File Console / Link List Display
    require __DIR__ . '/view.php';
    exit;
}

// Render Main Search Engine Interface
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tik.tak.md</title>
    <meta name="description" content="Enter your 6-letter class code.">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="gateway-body">
    <div class="bg-ambient">
        <div class="ambient-orb orb-1"></div>
        <div class="ambient-orb orb-2"></div>
    </div>

    <header class="app-header gateway-header">
        <a href="/" class="gateway-brand">tik.tak.md</a>
        <div class="header-actions">
            <button id="themeToggle" class="btn btn-ghost btn-sm" aria-label="Toggle Theme" title="Toggle Theme"></button>
            <?php if (is_logged_in()): ?>
                <a href="/prof/dashboard.php" class="btn btn-ghost btn-sm" title="Dashboard">Dashboard</a>
            <?php else: ?>
                <a href="/prof/login.php" class="btn btn-ghost btn-sm gateway-teacher-link" title="Teacher Login">Teacher</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="gateway-main">
        <form id="searchForm" class="gateway-search-form" action="/" method="GET">
            <div class="gateway-input-box">
                <input 
                    type="text" 
                    id="codeInput"
                    name="code" 
                    class="gateway-code-input" 
                    placeholder="ENTER CODE" 
                    autocomplete="off" 
                    spellcheck="false" 
                    maxlength="6"
                    autofocus
                    required
                >
                <button type="submit" class="gateway-arrow-btn" id="btnSubmitSearch" aria-label="Access Code">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </form>
    </main>

    <script src="/assets/js/main.js"></script>
</body>
</html>
