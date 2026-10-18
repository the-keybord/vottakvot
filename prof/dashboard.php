<?php
/**
 * TikTak - Professor Dashboard
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$user = current_user();
$pdo = get_db_connection();

// Fetch metrics & resources for this logged-in professor only
$stmt = $pdo->prepare("SELECT * FROM resources WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$resources = $stmt->fetchAll();

$totalActive = 0;
$totalExpired = 0;
$totalVisits = 0;

foreach ($resources as $res) {
    $totalVisits += (int)$res['visits_count'];
    if (is_resource_expired($res['expires_at'])) {
        $totalExpired++;
    } else {
        $totalActive++;
    }
}

$baseUrl = get_base_url();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="bg-ambient">
        <div class="ambient-orb orb-1"></div>
        <div class="ambient-orb orb-2"></div>
    </div>

    <!-- Header -->
    <header class="app-header">
        <a href="/" class="brand-link">
            <div class="brand-icon">⚡</div>
            <span><?= APP_NAME ?></span>
            <span class="brand-badge">Professor</span>
        </a>
        <div class="header-actions">
            <button id="themeToggle" class="btn btn-secondary btn-sm" aria-label="Toggle Theme"></button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="openModal('addTeacherModal')" style="font-size: 0.78rem; opacity: 0.75; font-weight: 500;" title="Create another teacher account">+ Teacher</button>
            <a href="/" target="_blank" class="btn btn-ghost btn-sm">Public Gateway ↗</a>
            <a href="/prof/logout.php" class="btn btn-danger btn-sm">Sign Out</a>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="prof-container">
            <!-- Metrics Bar -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-info">
                        <h3>Active Materials</h3>
                        <div class="metric-value"><?= number_format($totalActive) ?></div>
                    </div>
                    <div class="metric-icon" style="color: var(--accent-emerald);">●</div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h3>Total Student Clicks</h3>
                        <div class="metric-value"><?= number_format($totalVisits) ?></div>
                    </div>
                    <div class="metric-icon" style="color: var(--accent-primary);">⚡</div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h3>Expired Codes</h3>
                        <div class="metric-value"><?= number_format($totalExpired) ?></div>
                    </div>
                    <div class="metric-icon" style="color: var(--accent-rose);">⏱</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Your Codes &amp; Materials</h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Manage links, student notes, and live codes</p>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div style="position: relative;">
                        <input type="text" id="filterInput" class="form-control" placeholder="Search code or title..." style="padding-left: 2.2rem; min-width: 240px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--text-dim);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('createModal')">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        Create New Resource
                    </button>
                </div>
            </div>

            <!-- Resources Table -->
            <div class="resources-table-container">
                <?php if (empty($resources)): ?>
                    <div style="padding: 4rem 2rem; text-align: center; color: var(--text-muted);">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                        <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--text-main);">No resources created yet</h3>
                        <p style="margin-bottom: 1.5rem;">Click below to generate your first 6-letter student access code.</p>
                        <button class="btn btn-primary" onclick="openModal('createModal')">Generate First Code</button>
                    </div>
                <?php else: ?>
                    <table class="resources-table">
                        <thead>
                            <tr>
                                <th>Code &amp; Link</th>
                                <th>Type</th>
                                <th>Title / Preview</th>
                                <th>Status / Expiry</th>
                                <th>Visits</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources as $r): ?>
                                <?php 
                                    $expInfo = format_expiry_status($r['expires_at']);
                                    $fullUrl = $baseUrl . '/code=' . $r['code'];
                                    if ($r['type'] === 'redirect') {
                                        $preview = $r['content_link'];
                                    } elseif ($r['type'] === 'text') {
                                        $preview = mb_strimwidth($r['content_text'] ?? '', 0, 60, '...');
                                    } elseif ($r['type'] === 'hosting') {
                                        $sStats = get_site_summary($r['code']);
                                        $preview = "Web Directory: sites/{$r['code']}/ • {$sStats['total_files']} files ({$sStats['total_size_formatted']})";
                                    } elseif ($r['type'] === 'links') {
                                        $linksData = json_decode($r['content_text'] ?? '[]', true) ?: [];
                                        $lCount = count($linksData);
                                        $preview = "Link List: {$lCount} " . ($lCount === 1 ? 'button' : 'buttons');
                                        if ($lCount > 0 && !empty($linksData[0]['title'])) {
                                            $preview .= " • " . mb_strimwidth($linksData[0]['title'], 0, 35, '...');
                                        }
                                    } else {
                                        $fStats = get_resource_files_summary($pdo, (int)$r['id']);
                                        $preview = "MinIO folder: {$r['code']}/ • {$fStats['total_files']} files ({$fStats['total_size_formatted']})";
                                    }
                                ?>
                                <tr id="resource-row-<?= $r['id'] ?>" class="resource-item-row" data-code="<?= htmlspecialchars($r['code']) ?>" data-title="<?= htmlspecialchars($r['title']) ?>">
                                    <td>
                                        <div class="resource-code-cell">
                                            <span><?= htmlspecialchars($r['code']) ?></span>
                                            <button class="copy-btn-mini" onclick="copyToClipboard('<?= $fullUrl ?>', 'Access link copied!')" title="Copy full URL">
                                                Copy
                                            </button>
                                        </div>
                                        <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 0.2rem;">
                                            <a href="/code=<?= $r['code'] ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                                /code=<?= $r['code'] ?> ↗
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($r['type'] === 'redirect'): ?>
                                            <span class="badge badge-redirect">🔗 Link</span>
                                        <?php elseif ($r['type'] === 'text'): ?>
                                            <span class="badge badge-text">📝 Note</span>
                                        <?php elseif ($r['type'] === 'hosting'): ?>
                                            <span class="badge badge-hosting">🌐 Micro-host</span>
                                        <?php elseif ($r['type'] === 'links'): ?>
                                            <span class="badge badge-links">📑 Link List</span>
                                        <?php else: ?>
                                            <span class="badge badge-files">📁 File Console</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-main); margin-bottom: 0.2rem;">
                                            <?= htmlspecialchars($r['title'] ?: 'Untitled') ?>
                                        </div>
                                        <div style="font-size: 0.82rem; color: var(--text-muted); max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($preview) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.35rem; align-items: flex-start;">
                                            <span class="badge <?= $expInfo['badge_class'] ?>" id="expiry-badge-<?= $r['id'] ?>">
                                                <?= htmlspecialchars($expInfo['label']) ?>
                                            </span>
                                            <?php if ($expInfo['is_expired']): ?>
                                                <button class="btn-extend-expiry" onclick="extendExpiry(<?= $r['id'] ?>, 2)" title="Extend code validity by +2 hours">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    <span>+2 Hours</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-family: var(--font-mono); font-weight: 600;"><?= number_format($r['visits_count']) ?></span>
                                    </td>

                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 0.4rem;">
                                            <?php if ($r['type'] === 'hosting'): ?>
                                                <button class="btn btn-primary btn-sm" onclick="openSiteManager('<?= $r['code'] ?>', '<?= htmlspecialchars(addslashes($r['title'])) ?>')" title="Manage Hosted Web Directory & Files">
                                                    ⚙️ Manage Site
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-secondary btn-sm" onclick="showQrCode('<?= $r['code'] ?>', '<?= $fullUrl ?>')" title="Show QR Code for Classroom">
                                                QR
                                            </button>
                                            <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit Material">
                                                Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteResource(<?= $r['id'] ?>, '<?= $r['code'] ?>')" title="Delete Code">
                                                ✕
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Create Resource Modal -->
    <div id="createModal" class="modal-backdrop">
        <div class="modal-box modal-box-wide">
            <div class="modal-header">
                <h2 class="modal-title">Create New Resource</h2>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form id="resourceForm">
                <div class="modal-body">
                    <!-- Type Selection Tabs (5 Options) -->
                    <label class="form-label">Resource Type</label>
                    <div class="type-selector-tabs">
                        <div class="type-tab active" data-type="redirect">
                            <span>🔗</span>
                            <span>Redirect Link</span>
                        </div>
                        <div class="type-tab" data-type="text">
                            <span>📝</span>
                            <span>Shared Note</span>
                        </div>
                        <div class="type-tab" data-type="links">
                            <span>📑</span>
                            <span>Link List</span>
                        </div>
                        <div class="type-tab" data-type="files">
                            <span>📁</span>
                            <span>File Console</span>
                        </div>
                        <div class="type-tab" data-type="hosting">
                            <span>🌐</span>
                            <span>Micro-hosting</span>
                        </div>
                    </div>

                    <!-- 6-Letter Code Generator -->
                    <div class="form-group">
                        <label class="form-label" for="inputCode">6-Letter Access Code</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input 
                                type="text" 
                                id="inputCode" 
                                name="code" 
                                class="form-control" 
                                style="font-family: var(--font-mono); font-size: 1.15rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase;"
                                maxlength="6" 
                                required
                            >
                            <button type="button" id="btnGenerateCode" class="btn btn-secondary" title="Randomize 6-letter code">
                                🎲 Random
                            </button>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-dim);">Students will access via <code style="color: var(--accent-cyan);">tik.tak.md/code=XXXXXX</code></span>
                    </div>

                    <!-- Title -->
                    <div class="form-group">
                        <label class="form-label" for="inputTitle">Title / Reference Label</label>
                        <input type="text" id="inputTitle" name="title" class="form-control" placeholder="e.g. Week 4 Lecture Slides, Web Quiz, or Submissions">
                    </div>

                    <!-- Redirect URL Field -->
                    <div id="fieldRedirectLink" class="form-group">
                        <label class="form-label" for="inputLink">Destination Link (URL)</label>
                        <input type="url" id="inputLink" name="link" class="form-control" placeholder="https://drive.google.com/... or https://classroom.google.com/..." required>
                    </div>

                    <!-- Shared Text Field -->
                    <div id="fieldTextContent" class="form-group" style="display: none;">
                        <label class="form-label" for="inputText">Shared Text Content (Dontpad Style)</label>
                        <textarea id="inputText" name="text" class="form-control" placeholder="Type or paste student instructions, code snippets, notes..."></textarea>
                    </div>

                    <!-- File Console Info Notice -->
                    <div id="fieldFilesInfo" class="form-group" style="display: none;">
                        <div class="files-setup-notice">
                            <div class="notice-badge">⚡ MinIO Bucket Integration</div>
                            <p>Creates an isolated folder in MinIO for code <strong id="filesNoticeCode" class="view-code-tag">...</strong>. Anyone with this code can upload (anonymously) &amp; download files up to 500 MB. Files are organized by date, and only you (the professor) can delete files.</p>
                        </div>
                    </div>

                    <!-- Micro-hosting Web Directory Field -->
                    <div id="fieldHostingContent" class="form-group" style="display: none;">
                        <div class="hosting-create-box">
                            <div class="hosting-guide-header">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div style="font-weight: 700; color: var(--accent-cyan); font-size: 0.95rem;">🌐 Web Directory &amp; Micro-site Hosting</div>
                                    <span class="badge badge-hosting" style="font-size: 0.75rem;">Server Root: sites/<span id="hostingNoticeCode">...</span>/</span>
                                </div>
                                <p style="font-size: 0.84rem; color: var(--text-muted); margin-top: 0.4rem; line-height: 1.5;">
                                    Creates an isolated web directory. You can paste your <code>index.php</code> code below, pick a template, or upload multiple files and .ZIP archives after creation.
                                </p>
                            </div>

                            <div class="template-presets-bar">
                                <span style="font-size: 0.76rem; font-weight: 600; color: var(--text-dim); text-transform: uppercase;">Templates:</span>
                                <button type="button" class="preset-pill" onclick="applySiteTemplate('starter')">⚡ Default Starter</button>
                                <button type="button" class="preset-pill" onclick="applySiteTemplate('quiz')">📝 Interactive Form</button>
                                <button type="button" class="preset-pill" onclick="applySiteTemplate('canvas')">🎨 Canvas Graphics</button>
                                <button type="button" class="preset-pill" onclick="applySiteTemplate('api')">⚙️ JSON API</button>
                                <button type="button" class="preset-pill" onclick="applySiteTemplate('blank')">📄 Blank PHP</button>
                            </div>

                            <div style="margin-top: 0.75rem;">
                                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;" for="inputHostingCode">
                                    <span>index.php Content</span>
                                    <span style="font-size: 0.75rem; color: var(--text-dim); font-weight: normal;">PHP &amp; HTML supported</span>
                                </label>
                                <textarea 
                                    id="inputHostingCode" 
                                    name="hosting_code" 
                                    class="form-control code-editor-textarea" 
                                    rows="9" 
                                    spellcheck="false" 
                                    placeholder="Type or paste your index.php code here..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Link List Builder Field -->
                    <div id="fieldLinksContent" class="form-group" style="display: none;">
                        <div class="link-builder-wrapper">
                            <div class="link-builder-header">
                                <div>
                                    <div style="font-weight: 700; color: var(--accent-cyan); font-size: 0.95rem;">📑 Link List Buttons</div>
                                    <p style="font-size: 0.84rem; color: var(--text-muted); margin-top: 0.2rem;">
                                        Add interactive buttons that redirect students to other links or TikTak 6-letter codes.
                                    </p>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" id="btnCreateAddLink">
                                    + Add Button
                                </button>
                            </div>
                            <div id="createLinksContainer" class="link-items-list">
                                <!-- Dynamic link items -->
                            </div>
                        </div>
                    </div>

                    <!-- Expiration Delay Presets -->
                    <div class="form-group">
                        <label class="form-label">Expiration Delay</label>
                        <div class="chips-grid">
                            <div class="chip active expiry-chip" data-preset="never">Never</div>
                            <div class="chip expiry-chip" data-preset="1h">1 Hour</div>
                            <div class="chip expiry-chip" data-preset="6h">6 Hours</div>
                            <div class="chip expiry-chip" data-preset="24h">24 Hours</div>
                            <div class="chip expiry-chip" data-preset="3d">3 Days</div>
                            <div class="chip expiry-chip" data-preset="7d">7 Days</div>
                            <div class="chip expiry-chip" data-preset="30d">30 Days</div>
                            <div class="chip expiry-chip" data-preset="custom">Custom Date</div>
                        </div>
                    </div>

                    <div id="customExpiryContainer" class="form-group" style="display: none;">
                        <label class="form-label" for="inputCustomExpiry">Select Custom Expiry Date &amp; Time</label>
                        <input type="datetime-local" id="inputCustomExpiry" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" id="btnSubmitResource" class="btn btn-primary">Create Resource</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Resource Modal -->
    <div id="editModal" class="modal-backdrop">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title">Edit Resource</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form onsubmit="handleEditSubmit(event)">
                <input type="hidden" id="editId">
                <input type="hidden" id="editType">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Access Code</label>
                        <input type="text" id="editCode" class="form-control" disabled style="opacity: 0.7;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editTitle">Title</label>
                        <input type="text" id="editTitle" class="form-control" required>
                    </div>
                    <div id="editFieldLink" class="form-group">
                        <label class="form-label" for="editInputLink">Destination URL</label>
                        <input type="url" id="editInputLink" class="form-control">
                    </div>
                    <div id="editFieldText" class="form-group">
                        <label class="form-label" for="editInputText">Text Note Content</label>
                        <textarea id="editInputText" class="form-control"></textarea>
                    </div>
                    <div id="editFieldFiles" class="form-group" style="display: none;">
                        <p style="font-size: 0.88rem; color: var(--text-muted); background: var(--bg-glass); padding: 0.75rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            📁 <strong>MinIO File Console:</strong> To upload, download, or delete files, visit the <a id="editConsoleLink" href="#" target="_blank" style="color: var(--accent-cyan); font-weight: 600;">File Console Interface ↗</a>.
                        </p>
                    </div>
                    <div id="editFieldHosting" class="form-group" style="display: none;">
                        <div style="font-size: 0.88rem; color: var(--text-muted); background: var(--bg-glass); padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                            <div>
                                🌐 <strong>Micro-hosting Directory:</strong> Edit code, upload files, or view preview in the Site Manager.
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="btnEditOpenManager">
                                Open Manager ⚙️
                            </button>
                        </div>
                    </div>
                    <div id="editFieldLinks" class="form-group" style="display: none;">
                        <div class="link-builder-wrapper">
                            <div class="link-builder-header">
                                <div>
                                    <div style="font-weight: 700; color: var(--accent-cyan); font-size: 0.95rem;">📑 Edit Link Buttons</div>
                                    <p style="font-size: 0.84rem; color: var(--text-muted); margin-top: 0.2rem;">
                                        Add, edit, or remove destination buttons for this link list.
                                    </p>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" id="btnEditAddLink">
                                    + Add Button
                                </button>
                            </div>
                            <div id="editLinksContainer" class="link-items-list">
                                <!-- Dynamic link items -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Micro-hosting Site Manager Modal -->
    <div id="siteManagerModal" class="modal-backdrop modal-backdrop-full">
        <div class="modal-box modal-box-large">
            <div class="modal-header site-modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <span class="badge badge-hosting" style="font-size: 0.85rem;">🌐 Micro-host</span>
                    <h2 class="modal-title" id="siteModalTitle" style="font-size: 1.25rem;">Site Manager</h2>
                    <code id="siteModalCode" class="view-code-tag">...</code>
                </div>
                <div class="site-header-actions">
                    <a id="siteModalOpenLink" href="#" target="_blank" class="btn btn-secondary btn-sm" title="Open live site in new tab">
                        <span>Open Live Site ↗</span>
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="downloadSiteZip()" title="Download entire site as ZIP archive">
                        📦 Export ZIP
                    </button>
                    <button class="modal-close" onclick="closeModal('siteManagerModal')">&times;</button>
                </div>
            </div>

            <!-- Site Toolbar & Navigation Tabs -->
            <div class="site-toolbar">
                <div class="site-nav-tabs">
                    <button type="button" class="site-tab-btn active" data-view="editor" onclick="switchSiteView('editor')">
                        📝 Code Editor
                    </button>
                    <button type="button" class="site-tab-btn" data-view="files" onclick="switchSiteView('files')">
                        📂 Files &amp; Upload (<span id="siteFilesCount">0</span>)
                    </button>
                    <button type="button" class="site-tab-btn" data-view="preview" onclick="switchSiteView('preview')">
                        👁️ Live Preview
                    </button>
                </div>

                <div class="site-toolbar-meta" id="siteToolbarMeta">
                    <span id="siteTotalSizeFormatted" style="font-size: 0.82rem; color: var(--text-muted);">0 B</span>
                </div>
            </div>

            <div class="modal-body site-modal-body">
                <!-- View 1: Editor & File Tree -->
                <div id="siteViewEditor" class="site-view-panel active">
                    <div class="site-editor-layout">
                        <!-- Files Quick Sidebar -->
                        <div class="site-sidebar">
                            <div class="site-sidebar-header">
                                <span>Site Files</span>
                                <div style="display: flex; gap: 0.3rem;">
                                    <button type="button" class="btn-mini" onclick="promptNewFile()" title="Create New File">+ File</button>
                                    <button type="button" class="btn-mini" onclick="promptNewFolder()" title="Create New Folder">+ Dir</button>
                                </div>
                            </div>
                            <div id="siteFileListSidebar" class="site-file-tree">
                                <!-- Populated dynamically via JS -->
                            </div>
                        </div>

                        <!-- Main Code Editor Area -->
                        <div class="site-editor-main">
                            <div class="site-editor-tabs-bar">
                                <div class="site-active-tab" id="siteActiveFileLabel">
                                    <span id="siteActiveFileIcon">📄</span>
                                    <span id="siteActiveFileName">index.php</span>
                                    <span id="siteUnsavedIndicator" class="unsaved-dot" style="display: none;" title="Unsaved changes">●</span>
                                </div>
                                <div class="site-editor-actions">
                                    <button type="button" id="btnSaveSiteFile" class="btn btn-primary btn-sm" onclick="saveCurrentSiteFile()">
                                        💾 Save File <span class="kbd-shortcut">Ctrl+S</span>
                                    </button>
                                </div>
                            </div>

                            <div class="site-editor-container">
                                <textarea id="siteCodeTextarea" class="code-editor-textarea site-editor-core" spellcheck="false" placeholder="Loading file content..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View 2: File Manager & Uploader -->
                <div id="siteViewFiles" class="site-view-panel" style="display: none;">
                    <div class="site-files-management-layout">
                        <!-- Upload Dropzone -->
                        <div class="site-upload-zone" id="siteDropzone">
                            <div class="upload-zone-content">
                                <div style="font-size: 2.2rem; margin-bottom: 0.35rem;">📤</div>
                                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-main); margin-bottom: 0.2rem;">
                                    Drag &amp; Drop Files or .ZIP Archive
                                </div>
                                <p style="font-size: 0.83rem; color: var(--text-muted); margin-bottom: 1rem;">
                                    Upload PHP, HTML, CSS, JS, images, audio, or a full website as a .ZIP (auto-extracted).
                                </p>
                                <label class="btn btn-primary btn-sm" style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem;">
                                    <span>Select Files to Upload</span>
                                    <input type="file" id="siteFileInput" multiple style="display: none;" onchange="handleSiteFilesUpload(this.files)">
                                </label>
                            </div>
                        </div>

                        <!-- Full Table of Files -->
                        <div class="site-files-table-container">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <h3 style="font-size: 0.95rem; color: var(--text-main); font-weight: 600;">All Directory Files</h3>
                                <div style="display: flex; gap: 0.4rem;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="promptNewFile()">+ New File</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="promptNewFolder()">+ New Folder</button>
                                </div>
                            </div>
                            <table class="resources-table site-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Modified</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="siteFilesTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- View 3: Live Interactive Preview -->
                <div id="siteViewPreview" class="site-view-panel" style="display: none;">
                    <div class="site-preview-container">
                        <div class="preview-controls-bar">
                            <div class="preview-device-switch">
                                <button type="button" class="device-btn active" data-device="full" onclick="setPreviewDevice('full')">🖥️ Desktop</button>
                                <button type="button" class="device-btn" data-device="tablet" onclick="setPreviewDevice('tablet')">📱 Tablet</button>
                                <button type="button" class="device-btn" data-device="mobile" onclick="setPreviewDevice('mobile')">📱 Mobile</button>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="reloadSitePreview()" title="Reload Frame">
                                    🔄 Refresh
                                </button>
                                <a id="sitePreviewDirectLink" href="#" target="_blank" class="btn btn-secondary btn-sm">
                                    Full Screen ↗
                                </a>
                            </div>
                        </div>
                        <div class="preview-frame-wrapper" id="previewFrameWrapper">
                            <iframe id="sitePreviewIframe" src="about:blank" class="site-preview-frame"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Add Teacher Modal (Discreet) -->
    <div id="addTeacherModal" class="modal-backdrop">
        <div class="modal-box" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title" style="font-size: 1.15rem;">Add Teacher Account</h2>
                <button class="modal-close" onclick="closeModal('addTeacherModal')">&times;</button>
            </div>
            <form id="addTeacherForm" onsubmit="handleAddTeacherSubmit(event)">
                <div class="modal-body">
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                        Create an additional professor account. They will be able to log in, manage their own codes, and pin/delete materials.
                    </p>
                    <div class="form-group">
                        <label class="form-label" for="newTeacherUsername">Username</label>
                        <input type="text" id="newTeacherUsername" name="username" class="form-control" placeholder="e.g. prof_smith" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newTeacherName">Display Name (Optional)</label>
                        <input type="text" id="newTeacherName" name="name" class="form-control" placeholder="e.g. Prof. Smith">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="newTeacherPassword">Password</label>
                        <input type="password" id="newTeacherPassword" name="password" class="form-control" placeholder="Minimum 4 characters" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addTeacherModal')">Cancel</button>
                    <button type="submit" id="btnAddTeacherSubmit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- QR Code Modal (Projector View) -->
    <div id="qrModal" class="modal-backdrop">
        <div class="modal-box" style="max-width: 420px; text-align: center;">
            <div class="modal-header">
                <h2 class="modal-title">Classroom Access</h2>
                <button class="modal-close" onclick="closeModal('qrModal')">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem 1.5rem;">
                <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 0.5rem;">Class Access Code</div>
                <div id="qrModalCode" style="font-family: var(--font-mono); font-size: 2.8rem; font-weight: 800; letter-spacing: 0.25em; color: var(--accent-cyan); margin-bottom: 1.25rem;"></div>
                
                <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius-md); display: inline-block; box-shadow: var(--shadow-md);">
                    <img id="qrImage" src="" alt="QR Code" style="width: 220px; height: 220px; display: block;">
                </div>

                <div id="qrModalUrl" style="font-size: 0.85rem; color: var(--text-muted); margin-top: 1.25rem; word-break: break-all;"></div>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <button class="btn btn-secondary" onclick="copyToClipboard(document.getElementById('qrModalUrl').innerText, 'URL copied!')">Copy Link</button>
                <button class="btn btn-primary" onclick="closeModal('qrModal')">Done</button>
            </div>
        </div>
    </div>

    <footer class="app-footer">
        <div>&copy; <?= date('Y') ?> <?= APP_NAME ?> &bull; Logged in as <strong><?= htmlspecialchars($user['name']) ?></strong></div>
    </footer>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/prof.js"></script>
</body>
</html>
