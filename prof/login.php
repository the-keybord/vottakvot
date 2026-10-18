<?php
/**
 * TikTak - Professor Login
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '/prof/dashboard.php');
if (!str_starts_with($redirect, '/')) {
    $redirect = '/prof/dashboard.php';
}

// If already logged in, redirect
if (is_logged_in()) {
    header("Location: " . $redirect);
    exit;
}

// Trigger DB init if needed so default admin user is seeded
$pdo = get_db_connection();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['tiktak_user_id'] = $user['id'];
            $_SESSION['tiktak_username'] = $user['username'];
            $_SESSION['tiktak_name'] = $user['name'];
            header("Location: " . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Login — <?= APP_NAME ?></title>
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
            <span class="brand-badge">Professor Portal</span>
        </a>
        <div class="header-actions">
            <button id="themeToggle" class="btn btn-secondary btn-sm" aria-label="Toggle Theme"></button>
            <a href="/" class="btn btn-ghost btn-sm">Student Gateway</a>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="prof-auth-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div class="brand-icon" style="width: 52px; height: 52px; margin: 0 auto 1rem; font-size: 1.6rem;">⚡</div>
                <h1 style="font-family: var(--font-heading); font-size: 1.75rem; font-weight: 800;">Teacher Access</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.25rem;">Sign in to manage student codes and materials</p>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(244, 63, 94, 0.3); color: var(--accent-rose); padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.5rem; text-align: center;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="/prof/login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Username / ID</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. prof" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
                        Sign In to Dashboard
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="app-footer">
        <div>&copy; <?= date('Y') ?> <?= APP_NAME ?> &bull; <a href="/" class="footer-link">Gateway Home</a></div>
    </footer>

    <script src="/assets/js/main.js"></script>
</body>
</html>
