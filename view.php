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
        <?php elseif (($resource['type'] ?? '') === 'links'): ?>
            <!-- Link List Viewer -->
            <?php 
                $links = json_decode($resource['content_text'] ?? '[]', true) ?: [];
            ?>
            <div class="view-container linklist-container">
                <div class="view-card linklist-card">
                    <div class="view-card-header linklist-card-header">
                        <div class="view-header-meta">
                            <h1 class="view-title"><?= htmlspecialchars($resource['title'] ?: 'Resource Links') ?></h1>
                            <div class="view-meta-row">
                                <span>Code: <strong class="view-code-tag"><?= htmlspecialchars($resource['code']) ?></strong></span>
                                <span>&bull;</span>
                                <span class="badge <?= $expiryStatus['badge_class'] ?>"><?= htmlspecialchars($expiryStatus['label']) ?></span>
                                <span>&bull;</span>
                                <span><?= number_format($resource['visits_count']) ?> views</span>
                            </div>
                        </div>
                        <?php if (is_logged_in()): ?>
                            <div>
                                <button class="btn btn-secondary btn-sm" onclick="openLinkListEditModal()">
                                    ✏️ Edit Link List
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="view-card-body linklist-card-body">
                        <?php if (empty($links)): ?>
                            <div class="linklist-empty-state">
                                <div class="linklist-empty-icon">🔗</div>
                                <h3 style="margin-bottom: 0.5rem;">No Links Yet</h3>
                                <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto 1.5rem;">
                                    This link list does not contain any destination buttons yet.
                                </p>
                                <?php if (is_logged_in()): ?>
                                    <button class="btn btn-primary" onclick="openLinkListEditModal()">
                                        + Add Destination Buttons
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="linklist-grid">
                                <?php foreach ($links as $idx => $link): ?>
                                    <?php 
                                        $lTitle = trim($link['title'] ?? '');
                                        $lUrl = trim($link['url'] ?? '#');
                                        if ($lTitle === '') $lTitle = $lUrl;
                                        $isExternal = preg_match('~^https?://~i', $lUrl);
                                        $hostDisplay = $isExternal ? parse_url($lUrl, PHP_URL_HOST) : $lUrl;
                                    ?>
                                    <a href="<?= htmlspecialchars($lUrl) ?>" 
                                       class="linklist-btn-card" 
                                       <?= $isExternal ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                                        <div class="linklist-btn-left">
                                            <div class="linklist-btn-icon">
                                                <span><?= $idx + 1 ?></span>
                                            </div>
                                            <div class="linklist-btn-info">
                                                <div class="linklist-btn-title"><?= htmlspecialchars($lTitle) ?></div>
                                                <div class="linklist-btn-url"><?= htmlspecialchars($hostDisplay) ?></div>
                                            </div>
                                        </div>
                                        <div class="linklist-btn-arrow">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (is_logged_in()): ?>
                <!-- In-Page Link List Edit Modal -->
                <div id="linkListEditModal" class="modal-backdrop">
                    <div class="modal-box modal-box-wide">
                        <div class="modal-header">
                            <h2 class="modal-title">Edit Link List</h2>
                            <button class="modal-close" onclick="closeLinkListEditModal()">&times;</button>
                        </div>
                        <form onsubmit="handleInPageLinkListSubmit(event)">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label class="form-label" for="inPageTitle">List Title</label>
                                    <input type="text" id="inPageTitle" class="form-control" value="<?= htmlspecialchars($resource['title']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <div class="link-builder-header">
                                        <div>
                                            <label class="form-label" style="margin: 0;">Link Buttons</label>
                                            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">
                                                Add, edit, or remove destination buttons.
                                            </p>
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="addInPageLinkRow('', '')">
                                            + Add Button
                                        </button>
                                    </div>
                                    <div id="inPageLinksContainer" class="link-items-list" style="margin-top: 0.75rem;">
                                        <!-- Rows inserted via script -->
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="closeLinkListEditModal()">Cancel</button>
                                <button type="submit" id="btnSaveInPageLinks" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
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

        <?php if (($resource['type'] ?? '') === 'links' && is_logged_in()): ?>
        let initialLinks = <?= json_encode($links ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function openLinkListEditModal() {
            const m = document.getElementById('linkListEditModal');
            if (!m) return;
            const container = document.getElementById('inPageLinksContainer');
            container.innerHTML = '';
            if (initialLinks && initialLinks.length > 0) {
                initialLinks.forEach(link => addInPageLinkRow(link.title || '', link.url || ''));
            } else {
                addInPageLinkRow('', '');
                addInPageLinkRow('', '');
            }
            m.classList.add('active');
        }

        function closeLinkListEditModal() {
            const m = document.getElementById('linkListEditModal');
            if (m) m.classList.remove('active');
        }

        function addInPageLinkRow(title = '', url = '') {
            const container = document.getElementById('inPageLinksContainer');
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'link-item-row';
            row.innerHTML = `
                <span class="link-item-handle" title="Button link">🔗</span>
                <div class="link-item-fields">
                    <input type="text" class="form-control inpage-link-title" placeholder="Button Label (e.g. Lecture Slides, Quiz 1)" value="${escapeHtml(title)}">
                    <input type="text" class="form-control inpage-link-url" placeholder="Destination URL or 6-letter Code (e.g. https://... or ABCDEF)" value="${escapeHtml(url)}">
                </div>
                <button type="button" class="link-item-remove" title="Remove button" onclick="this.closest('.link-item-row').remove()">&times;</button>
            `;
            container.appendChild(row);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        async function handleInPageLinkListSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveInPageLinks');
            btn.disabled = true;
            btn.innerText = 'Saving...';

            const title = document.getElementById('inPageTitle').value.trim();
            const rows = document.querySelectorAll('#inPageLinksContainer .link-item-row');
            const links = [];
            rows.forEach(r => {
                const t = r.querySelector('.inpage-link-title')?.value.trim() || '';
                const u = r.querySelector('.inpage-link-url')?.value.trim() || '';
                if (t !== '' || u !== '') {
                    links.push({ title: t || u, url: u });
                }
            });

            try {
                const res = await fetch('/api/resources.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update',
                        id: <?= (int)($resource['id'] ?? 0) ?>,
                        title: title,
                        links: links,
                        content_text: JSON.stringify(links)
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Link List saved!', 'success');
                    closeLinkListEditModal();
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    showToast(data.error || 'Failed to save', 'error');
                    btn.disabled = false;
                    btn.innerText = 'Save Changes';
                }
            } catch (err) {
                showToast('Network error while saving', 'error');
                btn.disabled = false;
                btn.innerText = 'Save Changes';
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>

