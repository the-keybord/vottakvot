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
    <title><?= APP_NAME ?> — <?= APP_TAGLINE ?></title>
    <meta name="description" content="Universal student gateway for instant material and activity access. Enter your 6-letter class code.">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="bg-ambient">
        <div class="ambient-orb orb-1"></div>
        <div class="ambient-orb orb-2"></div>
    </div>

    <header class="app-header">
        <a href="/" class="brand-link">
            <div class="brand-icon">⚡</div>
            <span><?= APP_NAME ?></span>
            <span class="brand-badge">Gateway</span>
        </a>
        <div class="header-actions">
            <button id="themeToggle" class="btn btn-secondary btn-sm" aria-label="Toggle Theme" title="Toggle Theme">
                <!-- Theme SVG rendered by JS -->
            </button>
            <?php if (is_logged_in()): ?>
                <a href="/prof/dashboard.php" class="btn btn-primary btn-sm">Professor Dashboard</a>
            <?php else: ?>
                <a href="/prof/login.php" class="btn btn-ghost btn-sm">Professor Login</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="main-wrapper">
        <section class="search-hero">
            <div class="hero-brand">
                <h1 class="hero-logo"><?= APP_NAME ?></h1>
                <p class="hero-subtitle">Enter your 6-letter code to instantly access course materials, links, and study notes.</p>
            </div>

            <form id="searchForm" class="search-form" action="/" method="GET">
                <div class="search-box-container">
                    <div class="search-icon">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        id="codeInput"
                        name="code" 
                        class="search-input" 
                        placeholder="ENTER 6-LETTER CODE..." 
                        autocomplete="off" 
                        spellcheck="false" 
                        maxlength="6"
                        required
                    >
                    <button type="submit" class="search-btn" id="btnSubmitSearch">
                        <span>Access</span>
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <div class="search-hints">
                <span>Direct access available at</span>
                <span class="hint-pill">tik.tak.md/code=XXXXXX</span>
            </div>
        </section>
    </main>

    <footer class="app-footer">
        <div>&copy; <?= date('Y') ?> <?= APP_NAME ?> &bull; Universal Gateway for Students &bull; <a href="/prof/login.php" class="footer-link">Teacher Portal</a></div>
    </footer>

    <script src="/assets/js/main.js"></script>
</body>
</html>
