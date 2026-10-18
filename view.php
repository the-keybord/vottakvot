<?php
/**
 * TikTak - Resource Note Viewer & Error Notice Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$isError = !empty($errorTitle);
$pageTitle = $isError ? $errorTitle : htmlspecialchars($resource['title'] ?? 'Shared Material');
$expiryStatus = !$isError ? format_expiry_status($resource['expires_at'] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — <?= APP_NAME ?></title>
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
            <button id="themeToggle" class="btn btn-secondary btn-sm" aria-label="Toggle Theme" title="Toggle Theme"></button>
            <a href="/" class="btn btn-ghost btn-sm">Enter Code</a>
            
            <?php if (is_logged_in()): ?>
                <a href="/prof/dashboard.php" class="btn btn-secondary btn-sm" title="Open Professor Dashboard">
                    👑 Professor
                </a>
                <a href="/prof/logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-ghost btn-sm" style="color: var(--accent-rose);">
                    Sign Out
                </a>
            <?php else: ?>
                <button class="btn btn-primary btn-sm" onclick="openTeacherLoginModal()" title="Login as Teacher for special options">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Teacher Login
                </button>
            <?php endif; ?>
        </div>
    </header>

    <main class="main-wrapper">
        <?php if ($isError): ?>
            <!-- Error / Expired Notice Card -->
            <div class="notice-card">
                <div class="notice-icon icon-error">
                    <svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h2 class="view-title" style="margin-bottom: 0.75rem;"><?= htmlspecialchars($errorTitle) ?></h2>
                <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1.05rem;"><?= $errorMessage ?></p>
                <a href="/" class="btn btn-primary">Return to Search Gateway</a>
            </div>
        <?php elseif (($resource['type'] ?? '') === 'files'): ?>
            <!-- MinIO File Console Viewer -->
            <?php require __DIR__ . '/includes/files_view.php'; ?>
        <?php else: ?>
            <!-- Shared Note / Text Resource Viewer -->
            <div class="view-container">
                <div class="view-card">
                    <div class="view-card-header">
                        <div class="view-header-meta">
                            <h1 class="view-title"><?= htmlspecialchars($resource['title'] ?: 'Shared Note') ?></h1>
                            <div class="view-meta-row">
                                <span>Code: <strong class="view-code-tag"><?= htmlspecialchars($resource['code']) ?></strong></span>
                                <span>&bull;</span>
                                <span class="badge <?= $expiryStatus['badge_class'] ?>"><?= htmlspecialchars($expiryStatus['label']) ?></span>
                                <span>&bull;</span>
                                <span><?= number_format($resource['visits_count']) ?> views</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <button class="btn btn-secondary btn-sm" onclick="copyToClipboard(document.getElementById('textContent').innerText, 'Note copied to clipboard!')">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                Copy Text
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="downloadAsText()">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download
                            </button>
                        </div>
                    </div>
                    <div class="view-card-body">
                        <div id="textContent" class="text-viewer-content"><?= htmlspecialchars($resource['content_text'] ?? '') ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- In-Page Teacher Login Modal -->
    <div id="teacherLoginModal" class="modal-backdrop">
        <div class="modal-box" style="max-width: 380px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.3rem;">🔐</span>
                    <h2 class="modal-title">Teacher Login</h2>
                </div>
                <button class="modal-close" onclick="closeTeacherLoginModal()">&times;</button>
            </div>
            <form onsubmit="handleTeacherModalLogin(event)">
                <div class="modal-body" style="padding: 1.5rem;">
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                        Log in to unlock special teacher features: pin files, delete uploads, and manage materials.
                    </p>
                    <div class="form-group">
                        <label class="form-label" for="modalTeacherUsername">Username</label>
                        <input type="text" id="modalTeacherUsername" class="form-control" placeholder="e.g. prof" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modalTeacherPassword">Password</label>
                        <input type="password" id="modalTeacherPassword" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeTeacherLoginModal()">Cancel</button>
                    <button type="submit" id="btnTeacherModalSubmit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="app-footer">
        <div>&copy; <?= date('Y') ?> <?= APP_NAME ?> &bull; Universal Gateway &bull; <a href="/" class="footer-link">Home</a></div>
    </footer>

    <script src="/assets/js/main.js"></script>
    <script>
        function openTeacherLoginModal() {
            const m = document.getElementById('teacherLoginModal');
            if (m) {
                m.classList.add('active');
                setTimeout(() => document.getElementById('modalTeacherUsername')?.focus(), 100);
            }
        }

        function closeTeacherLoginModal() {
            const m = document.getElementById('teacherLoginModal');
            if (m) m.classList.remove('active');
        }

        async function handleTeacherModalLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('btnTeacherModalSubmit');
            btn.disabled = true;
            btn.innerText = 'Logging in...';

            const username = document.getElementById('modalTeacherUsername').value.trim();
            const password = document.getElementById('modalTeacherPassword').value;

            try {
                const res = await fetch('/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        redirect: window.location.pathname + window.location.search
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Logged in as Teacher!', 'success');
                    closeTeacherLoginModal();
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    showToast(data.error || 'Invalid credentials', 'error');
                    btn.disabled = false;
                    btn.innerText = 'Login';
                }
            } catch (err) {
                showToast('Login network error', 'error');
                btn.disabled = false;
                btn.innerText = 'Login';
            }
        }

        function downloadAsText() {
            const content = document.getElementById('textContent').innerText;
            const code = '<?= $resource['code'] ?? 'tiktak_note' ?>';
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tiktak_${code}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>

