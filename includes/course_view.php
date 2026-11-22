<?php
/**
 * TikTak - Course Dashboard Aggregator View
 * Combines classroom link launchpad cards (top third) with an integrated course file manager (lower two thirds).
 * Features high-contrast classroom projector banner, kid-friendly large targets, and live teacher in-place edit mode.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$isProf = is_logged_in();
$courseCode = htmlspecialchars($resource['code']);
$courseTitle = htmlspecialchars($resource['title'] ?: 'Course Hub ' . $resource['code']);
$baseUrl = get_base_url();
$accessUrl = $baseUrl . '/code=' . $resource['code'];

// Decode cards JSON from content_text
$courseData = json_decode($resource['content_text'] ?? '', true);
if (!is_array($courseData)) {
    $courseData = [];
}
$courseCards = $courseData['cards'] ?? [];
if (!is_array($courseCards)) {
    $courseCards = [];
}
?>

<div class="course-viewport-container" id="courseViewport">
    <!-- Top Classroom Projector Navigation Bar -->
    <header class="course-header">
        <div class="course-header-left">
            <a href="/" class="brand-link" title="TikTak Gateway">
                <div class="brand-icon">⚡</div>
                <span class="brand-name">tik.tak.md</span>
            </a>
            <div class="course-title-wrap">
                <span class="badge badge-course">🎓 Course</span>
                <h1 class="course-title-text" id="courseTitleDisplay"><?= $courseTitle ?></h1>
            </div>
        </div>

        <!-- Prominent Classroom Projector Access Code Banner -->
        <div class="course-code-banner" onclick="copyToClipboard('<?= $accessUrl ?>', 'Student access link copied!')" title="Click to copy link for students">
            <div class="banner-inner">
                <span class="banner-site">tik.tak.md</span>
                <span class="banner-slash">/</span>
                <span class="banner-label">CODE:</span>
                <strong class="banner-code"><?= $courseCode ?></strong>
            </div>
            <button class="banner-copy-btn" aria-label="Copy Link" title="Copy Student Link">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </button>
        </div>

        <div class="course-header-right">
            <?php if ($isProf): ?>
                <button type="button" id="btnCourseEditToggle" class="btn btn-secondary btn-sm edit-mode-pill" onclick="toggleCourseEditMode()">
                    <span id="editModeIcon">✏️</span>
                    <span id="editModeText">Edit Mode</span>
                </button>
                <a href="/prof/dashboard.php" class="btn btn-ghost btn-sm" title="Open Teacher Dashboard">👑 Dashboard</a>
                <a href="/prof/logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-ghost btn-sm" style="color: var(--accent-rose);">Sign Out</a>
            <?php else: ?>
                <a href="/" class="btn btn-ghost btn-sm">Enter Code</a>
                <button class="btn btn-primary btn-sm" onclick="openTeacherLoginModal()" title="Teacher Login">Teacher</button>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Viewport (Split: Top ~35% Cards, Lower ~65% Files) -->
    <main class="course-main-split">
        <!-- TOP THIRD: Interactive Link Cards Deck -->
        <section class="course-cards-section">
            <div class="course-section-header">
                <div class="course-section-title-wrap">
                    <span class="section-icon">🔗</span>
                    <h2 class="course-section-title">Class Launchpad &amp; Tools</h2>
                    <span id="courseCardsCountBadge" class="course-count-pill"><?= count($courseCards) ?> <?= count($courseCards) === 1 ? 'link' : 'links' ?></span>
                </div>
                <div class="course-section-actions">
                    <button type="button" id="btnAddCardTop" class="btn btn-primary btn-sm teacher-only-ctrl" onclick="openCourseCardModal()" style="display: none;">
                        + Add Link Card
                    </button>
                </div>
            </div>

            <!-- Cards Scrollable Deck -->
            <div id="courseCardsDeck" class="course-cards-deck">
                <!-- Cards rendered dynamically by JS -->
            </div>
        </section>

        <!-- LOWER TWO THIRDS: Integrated Course File Manager -->
        <section class="course-files-section">
            <div class="course-section-header">
                <div class="course-section-title-wrap">
                    <span class="section-icon">📁</span>
                    <h2 class="course-section-title">Course Materials &amp; Downloads</h2>
                    <span id="courseFilesCountBadge" class="course-count-pill">0 files</span>
                    <span id="courseFilesTotalSize" class="course-size-tag">0 MB</span>
                </div>
                <div class="course-section-actions">
                    <div class="course-search-box">
                        <input type="text" id="courseFileSearchInput" placeholder="Filter materials..." oninput="filterCourseFiles()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="triggerCourseFileInput()" title="Upload file">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Upload Material
                    </button>
                    <input type="file" id="courseFileInput" style="display: none;" onchange="handleCourseFileSelect(event)">
                </div>
            </div>

            <!-- Drag & Drop Upload Zone (Visible in Edit Mode or on Drag) -->
            <div id="courseDropZone" class="course-dropzone" onclick="triggerCourseFileInput()" style="display: none;">
                <div class="course-dropzone-inner">
                    <div class="dropzone-icon">☁️</div>
                    <div class="dropzone-text">
                        <strong>Drag &amp; drop course worksheets, PDFs, or code archives here</strong>
                        <span>or click to browse files (max 500 MB)</span>
                    </div>
                </div>
                <div id="courseUploadProgress" class="course-upload-progress-bar" style="display: none;">
                    <div id="courseUploadFill" class="course-upload-fill" style="width: 0%;"></div>
                </div>
            </div>

            <!-- Scrollable File Explorer Container -->
            <div class="course-files-scroll-area">
                <!-- Pinned Materials Section -->
                <div id="coursePinnedSection" class="course-pinned-box" style="display: none;">
                    <div class="pinned-box-header">
                        <span class="pinned-star">📌</span>
                        <strong>Important Course Material (Pinned to Top)</strong>
                    </div>
                    <div id="coursePinnedFilesList" class="course-pinned-files-grid">
                        <!-- Pinned files injected dynamically -->
                    </div>
                </div>

                <!-- Regular Files List -->
                <div class="course-files-table-wrap">
                    <table class="course-files-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th style="text-align: right; width: 1%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="courseFilesTableBody">
                            <!-- Files injected dynamically -->
                        </tbody>
                    </table>

                    <div id="courseFilesEmptyState" class="course-empty-state" style="display: none;">
                        <div class="empty-icon">📂</div>
                        <h3>No Course Materials Yet</h3>
                        <p>Worksheets, slides, code templates, and PDFs uploaded to this course code will appear here for students.</p>
                        <button class="btn btn-secondary btn-sm" onclick="triggerCourseFileInput()">+ Upload First File</button>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- IN-PAGE CARD MODAL (Add / Edit Link Card) -->
<div id="courseCardModal" class="modal-backdrop">
    <div class="modal-box" style="max-width: 520px;">
        <div class="modal-header">
            <h2 id="courseCardModalTitle" class="modal-title">Add Link Card</h2>
            <button class="modal-close" onclick="closeCourseCardModal()">&times;</button>
        </div>
        <form onsubmit="handleCourseCardSubmit(event)" autocomplete="off">
            <input type="hidden" id="cardEditIndex" value="-1">
            <div class="modal-body" style="display: grid; gap: 1rem;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="cardTitleInput">Card Title</label>
                    <input type="text" id="cardTitleInput" class="form-control" placeholder="e.g. Scratch Studio, Course Slides, Quiz" required>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="cardUrlInput">Destination URL</label>
                    <input type="text" id="cardUrlInput" class="form-control" placeholder="https://scratch.mit.edu/... or /code=XXXXXX" required>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Emoji / Icon</label>
                    <div class="emoji-presets-row">
                        <?php foreach (['🚀', '🐱', '📊', '💻', '🎮', '📖', '🎨', '📝', '🎯', '⚡', '🤖', '🌐'] as $em): ?>
                            <button type="button" class="emoji-preset-btn" onclick="selectCardEmoji('<?= $em ?>')"><?= $em ?></button>
                        <?php endforeach; ?>
                        <input type="text" id="cardIconInput" class="form-control emoji-custom-input" value="🚀" maxlength="4" style="width: 50px; text-align: center; font-size: 1.2rem; padding: 0.3rem;">
                    </div>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Accent Color</label>
                    <div class="color-presets-row">
                        <label class="color-pill-label">
                            <input type="radio" name="cardColor" value="#6366f1" checked>
                            <span class="color-pill-dot" style="background: #6366f1;"></span> Indigo
                        </label>
                        <label class="color-pill-label">
                            <input type="radio" name="cardColor" value="#0284c7">
                            <span class="color-pill-dot" style="background: #0284c7;"></span> Sky
                        </label>
                        <label class="color-pill-label">
                            <input type="radio" name="cardColor" value="#10b981">
                            <span class="color-pill-dot" style="background: #10b981;"></span> Emerald
                        </label>
                        <label class="color-pill-label">
                            <input type="radio" name="cardColor" value="#d97706">
                            <span class="color-pill-dot" style="background: #d97706;"></span> Amber
                        </label>
                        <label class="color-pill-label">
                            <input type="radio" name="cardColor" value="#e11d48">
                            <span class="color-pill-dot" style="background: #e11d48;"></span> Rose
                        </label>
                        <label class="color-pill-label">
                            <input type="radio" name="cardColor" value="#8b5cf6">
                            <span class="color-pill-dot" style="background: #8b5cf6;"></span> Purple
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="cardDescInput">Short Note / Subtitle (Optional)</label>
                    <input type="text" id="cardDescInput" class="form-control" placeholder="e.g. Today's Main Project, Read Chapters 1-3">
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeCourseCardModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Card</button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Course Dashboard Client Controller
 */
const COURSE_CODE = <?= json_encode($resource['code']) ?>;
const IS_TEACHER = <?= json_encode($isProf) ?>;
let courseCards = <?= json_encode($courseCards) ?>;
let courseFiles = [];
let isEditMode = false;

document.addEventListener('DOMContentLoaded', () => {
    renderCourseCards();
    loadCourseFiles();
    setupDropZone();
});

/**
 * Toggle Teacher In-Place Edit Mode
 */
function toggleCourseEditMode() {
    if (!IS_TEACHER) return;
    isEditMode = !isEditMode;

    const btn = document.getElementById('btnCourseEditToggle');
    const icon = document.getElementById('editModeIcon');
    const txt = document.getElementById('editModeText');
    const addCardBtn = document.getElementById('btnAddCardTop');
    const dropZone = document.getElementById('courseDropZone');
    const viewport = document.getElementById('courseViewport');

    if (isEditMode) {
        btn.classList.add('active-edit-mode');
        icon.textContent = '👁️';
        txt.textContent = 'Student View';
        if (addCardBtn) addCardBtn.style.display = 'inline-flex';
        if (dropZone) dropZone.style.display = 'block';
        if (viewport) viewport.classList.add('in-edit-mode');
    } else {
        btn.classList.remove('active-edit-mode');
        icon.textContent = '✏️';
        txt.textContent = 'Edit Mode';
        if (addCardBtn) addCardBtn.style.display = 'none';
        if (dropZone) dropZone.style.display = 'none';
        if (viewport) viewport.classList.remove('in-edit-mode');
    }

    renderCourseCards();
    renderCourseFiles();
}

/**
 * Render Link Cards Deck
 */
function renderCourseCards() {
    const deck = document.getElementById('courseCardsDeck');
    const countBadge = document.getElementById('courseCardsCountBadge');
    if (!deck) return;

    if (countBadge) {
        countBadge.textContent = `${courseCards.length} ${courseCards.length === 1 ? 'link' : 'links'}`;
    }

    deck.innerHTML = '';

    if (courseCards.length === 0 && !isEditMode) {
        deck.innerHTML = `
            <div class="course-deck-empty">
                <span>🚀</span>
                <p>No link cards configured for this class launchpad yet.</p>
                ${IS_TEACHER ? '<button class="btn btn-secondary btn-sm" onclick="toggleCourseEditMode()">Switch to Edit Mode to Add Cards</button>' : ''}
            </div>
        `;
        return;
    }

    courseCards.forEach((card, idx) => {
        const cardElem = document.createElement('div');
        cardElem.className = 'course-card';
        cardElem.style.setProperty('--card-accent', card.color || '#6366f1');

        let host = '';
        try {
            if (card.url.startsWith('http')) {
                host = new URL(card.url).hostname.replace(/^www\./, '');
            } else {
                host = card.url;
            }
        } catch (e) {
            host = card.url;
        }

        cardElem.innerHTML = `
            <a href="${escapeHtml(card.url)}" target="_blank" rel="noopener noreferrer" class="course-card-link">
                <div class="course-card-header">
                    <span class="course-card-icon">${escapeHtml(card.icon || '🔗')}</span>
                    <span class="course-card-host">${escapeHtml(host)}</span>
                </div>
                <div class="course-card-body">
                    <h3 class="course-card-title">${escapeHtml(card.title || 'Untitled Link')}</h3>
                    ${card.desc ? `<p class="course-card-desc">${escapeHtml(card.desc)}</p>` : ''}
                </div>
                <div class="course-card-arrow">↗</div>
            </a>
            ${(IS_TEACHER && isEditMode) ? `
                <div class="course-card-teacher-tools">
                    <button type="button" class="card-tool-btn" onclick="editCourseCard(event, ${idx})" title="Edit Card">✏️</button>
                    <button type="button" class="card-tool-btn card-tool-del" onclick="deleteCourseCard(event, ${idx})" title="Delete Card">🗑️</button>
                </div>
            ` : ''}
        `;

        deck.appendChild(cardElem);
    });

    // Add Card Card Button in Edit Mode
    if (IS_TEACHER && isEditMode) {
        const addCardBox = document.createElement('div');
        addCardBox.className = 'course-card course-card-add';
        addCardBox.onclick = () => openCourseCardModal(-1);
        addCardBox.innerHTML = `
            <div class="add-card-content">
                <span class="add-card-plus">+</span>
                <strong>Add Link Card</strong>
                <span>Scratch, Slides, Quiz, etc.</span>
            </div>
        `;
        deck.appendChild(addCardBox);
    }
}

/**
 * Open Modal to Add or Edit Link Card
 */
function openCourseCardModal(idx = -1) {
    const modal = document.getElementById('courseCardModal');
    const modalTitle = document.getElementById('courseCardModalTitle');
    const idxInput = document.getElementById('cardEditIndex');
    const titleInput = document.getElementById('cardTitleInput');
    const urlInput = document.getElementById('cardUrlInput');
    const iconInput = document.getElementById('cardIconInput');
    const descInput = document.getElementById('cardDescInput');

    idxInput.value = idx;

    if (idx >= 0 && courseCards[idx]) {
        const c = courseCards[idx];
        modalTitle.textContent = 'Edit Link Card';
        titleInput.value = c.title || '';
        urlInput.value = c.url || '';
        iconInput.value = c.icon || '🚀';
        descInput.value = c.desc || '';
        const colorRadio = document.querySelector(`input[name="cardColor"][value="${c.color || '#6366f1'}"]`);
        if (colorRadio) colorRadio.checked = true;
    } else {
        modalTitle.textContent = 'Add Link Card';
        titleInput.value = '';
        urlInput.value = '';
        iconInput.value = '🚀';
        descInput.value = '';
        const defaultRadio = document.querySelector(`input[name="cardColor"][value="#6366f1"]`);
        if (defaultRadio) defaultRadio.checked = true;
    }

    modal.classList.add('active');
    titleInput.focus();
}

function closeCourseCardModal() {
    const modal = document.getElementById('courseCardModal');
    if (modal) modal.classList.remove('active');
}

function selectCardEmoji(em) {
    const iconInput = document.getElementById('cardIconInput');
    if (iconInput) iconInput.value = em;
}

function editCourseCard(e, idx) {
    e.preventDefault();
    e.stopPropagation();
    openCourseCardModal(idx);
}

async function deleteCourseCard(e, idx) {
    e.preventDefault();
    e.stopPropagation();
    if (!confirm(`Delete card "${courseCards[idx]?.title || 'Link'}"?`)) return;

    courseCards.splice(idx, 1);
    await saveCourseCardsToServer();
    renderCourseCards();
}

/**
 * Handle Add/Edit Card Submission
 */
async function handleCourseCardSubmit(e) {
    e.preventDefault();
    const idx = parseInt(document.getElementById('cardEditIndex').value, 10);
    const title = document.getElementById('cardTitleInput').value.trim();
    let url = document.getElementById('cardUrlInput').value.trim();
    const icon = document.getElementById('cardIconInput').value.trim() || '🔗';
    const desc = document.getElementById('cardDescInput').value.trim();
    const colorRadio = document.querySelector('input[name="cardColor"]:checked');
    const color = colorRadio ? colorRadio.value : '#6366f1';

    if (!url) return;
    if (!url.startsWith('http://') && !url.startsWith('https://') && !url.startsWith('/')) {
        url = 'https://' + url;
    }

    const cardData = {
        id: idx >= 0 && courseCards[idx]?.id ? courseCards[idx].id : `c_${Date.now()}`,
        title,
        url,
        icon,
        color,
        desc
    };

    if (idx >= 0) {
        courseCards[idx] = cardData;
    } else {
        courseCards.push(cardData);
    }

    closeCourseCardModal();
    renderCourseCards();
    await saveCourseCardsToServer();
}

/**
 * Persist Cards Array to Server via API
 */
async function saveCourseCardsToServer() {
    try {
        const res = await fetch('/api/resources.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_course_cards',
                code: COURSE_CODE,
                cards: courseCards
            })
        });
        const data = await res.json();
        if (!data.success) {
            alert('Failed to save cards: ' + (data.error || 'Unknown error'));
        }
    } catch (err) {
        console.error(err);
        alert('Network error while saving cards.');
    }
}

/**
 * Load Files from API
 */
async function loadCourseFiles() {
    try {
        const res = await fetch(`/api/files.php?action=list&code=${encodeURIComponent(COURSE_CODE)}`);
        const data = await res.json();
        if (data.success) {
            courseFiles = data.files || [];
            renderCourseFiles(data.pinned_files || []);
            updateFilesSummary(data.stats || {});
        }
    } catch (err) {
        console.error('Failed to load course files', err);
    }
}

function updateFilesSummary(stats) {
    const countBadge = document.getElementById('courseFilesCountBadge');
    const sizeTag = document.getElementById('courseFilesTotalSize');
    if (countBadge) {
        const count = stats.total_files || courseFiles.length || 0;
        countBadge.textContent = `${count} ${count === 1 ? 'file' : 'files'}`;
    }
    if (sizeTag) {
        sizeTag.textContent = stats.total_size_formatted || '0 MB';
    }
}

/**
 * Render Files List & Pinned Section
 */
function renderCourseFiles(pinned = null) {
    const tableBody = document.getElementById('courseFilesTableBody');
    const emptyState = document.getElementById('courseFilesEmptyState');
    const pinnedSection = document.getElementById('coursePinnedSection');
    const pinnedGrid = document.getElementById('coursePinnedFilesList');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    // Separate pinned files
    const pinnedList = pinned || courseFiles.filter(f => f.is_pinned == 1);
    const regularFiles = courseFiles;

    // Render Pinned Grid
    if (pinnedGrid && pinnedSection) {
        if (pinnedList.length > 0) {
            pinnedSection.style.display = 'block';
            pinnedGrid.innerHTML = pinnedList.map(f => `
                <div class="course-pinned-card">
                    <div class="pinned-card-left">
                        <span class="file-icon-badge">${getFileIconEmoji(f.original_filename)}</span>
                        <div class="pinned-card-info">
                            <strong class="pinned-filename" title="${escapeHtml(f.original_filename)}">${escapeHtml(f.original_filename)}</strong>
                            <span class="pinned-filesize">${escapeHtml(f.file_size_formatted || '')}</span>
                        </div>
                    </div>
                    <div class="pinned-card-actions">
                        <a href="/api/files.php?action=download&code=${encodeURIComponent(COURSE_CODE)}&file_id=${f.id}" class="btn btn-primary btn-sm" download>
                            ⬇️ Download
                        </a>
                        ${(IS_TEACHER && isEditMode) ? `
                            <button type="button" class="btn btn-ghost btn-sm" onclick="togglePinFile(${f.id})" title="Unpin file">📌</button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteCourseFile(${f.id})" title="Delete file">🗑️</button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        } else {
            pinnedSection.style.display = 'none';
        }
    }

    if (regularFiles.length === 0) {
        if (emptyState) emptyState.style.display = 'block';
        return;
    } else {
        if (emptyState) emptyState.style.display = 'none';
    }

    regularFiles.forEach(f => {
        const tr = document.createElement('tr');
        tr.className = 'course-file-row';
        tr.setAttribute('data-name', (f.original_filename || '').toLowerCase());

        tr.innerHTML = `
            <td class="course-file-name-cell">
                <span class="file-emoji">${getFileIconEmoji(f.original_filename)}</span>
                <span class="file-title" title="${escapeHtml(f.original_filename)}">${escapeHtml(f.original_filename)}</span>
                ${f.is_pinned == 1 ? '<span class="pinned-tag">📌 Pinned</span>' : ''}
            </td>
            <td class="course-file-size-cell">${escapeHtml(f.file_size_formatted || '')}</td>
            <td class="course-file-date-cell">${escapeHtml(f.upload_date || '')}</td>
            <td style="text-align: right; white-space: nowrap;">
                <div class="course-file-actions">
                    <a href="/api/files.php?action=download&code=${encodeURIComponent(COURSE_CODE)}&file_id=${f.id}" class="btn btn-secondary btn-sm file-download-btn" download>
                        ⬇️ Download
                    </a>
                    ${(IS_TEACHER && isEditMode) ? `
                        <button type="button" class="btn btn-ghost btn-sm" onclick="togglePinFile(${f.id})" title="${f.is_pinned == 1 ? 'Unpin' : 'Pin to Top'}">
                            ${f.is_pinned == 1 ? '📌 Unpin' : '📌 Pin'}
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteCourseFile(${f.id})" title="Delete file">
                            🗑️
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        tableBody.appendChild(tr);
    });
}

function filterCourseFiles() {
    const q = document.getElementById('courseFileSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.course-file-row');
    rows.forEach(r => {
        const name = r.getAttribute('data-name') || '';
        r.style.display = name.includes(q) ? '' : 'none';
    });
}

/**
 * File Actions: Pin & Delete
 */
async function togglePinFile(fileId) {
    try {
        const res = await fetch('/api/files.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'pin',
                code: COURSE_CODE,
                file_id: fileId
            })
        });
        const data = await res.json();
        if (data.success) {
            await loadCourseFiles();
        } else {
            alert('Failed to update pin: ' + (data.error || ''));
        }
    } catch (e) {
        console.error(e);
    }
}

async function deleteCourseFile(fileId) {
    if (!confirm('Are you sure you want to delete this course file?')) return;
    try {
        const res = await fetch('/api/files.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete',
                code: COURSE_CODE,
                file_id: fileId
            })
        });
        const data = await res.json();
        if (data.success) {
            await loadCourseFiles();
        } else {
            alert('Failed to delete file: ' + (data.error || ''));
        }
    } catch (e) {
        console.error(e);
    }
}

/**
 * Uploads handling
 */
function triggerCourseFileInput() {
    const input = document.getElementById('courseFileInput');
    if (input) input.click();
}

function handleCourseFileSelect(e) {
    const file = e.target.files[0];
    if (file) uploadCourseFile(file);
}

function setupDropZone() {
    const dz = document.getElementById('courseDropZone');
    if (!dz) return;

    ['dragenter', 'dragover'].forEach(name => {
        dz.addEventListener(name, (e) => {
            e.preventDefault();
            dz.classList.add('drag-active');
        });
    });

    ['dragleave', 'drop'].forEach(name => {
        dz.addEventListener(name, (e) => {
            e.preventDefault();
            dz.classList.remove('drag-active');
        });
    });

    dz.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files && files[0]) {
            uploadCourseFile(files[0]);
        }
    });
}

async function uploadCourseFile(file) {
    const progress = document.getElementById('courseUploadProgress');
    const fill = document.getElementById('courseUploadFill');

    if (progress && fill) {
        progress.style.display = 'block';
        fill.style.width = '25%';
    }

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('code', COURSE_CODE);
    formData.append('file', file);

    try {
        const res = await fetch('/api/files.php', {
            method: 'POST',
            body: formData
        });
        if (fill) fill.style.width = '100%';
        const data = await res.json();
        if (data.success) {
            setTimeout(() => {
                if (progress) progress.style.display = 'none';
                if (fill) fill.style.width = '0%';
            }, 500);
            await loadCourseFiles();
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
            if (progress) progress.style.display = 'none';
        }
    } catch (err) {
        console.error(err);
        alert('Network error during upload.');
        if (progress) progress.style.display = 'none';
    }
}

function getFileIconEmoji(filename) {
    if (!filename) return '📄';
    const ext = filename.split('.').pop().toLowerCase();
    switch (ext) {
        case 'pdf': return '📕';
        case 'zip': case 'rar': case '7z': case 'tar': case 'gz': return '📦';
        case 'png': case 'jpg': case 'jpeg': case 'gif': case 'webp': case 'svg': return '🖼️';
        case 'mp3': case 'wav': case 'ogg': return '🎵';
        case 'mp4': case 'mov': case 'webm': return '🎬';
        case 'py': case 'js': case 'ts': case 'html': case 'css': case 'json': case 'sb3': return '💻';
        case 'doc': case 'docx': case 'txt': case 'md': return '📝';
        case 'xls': case 'xlsx': case 'csv': return '📊';
        case 'ppt': case 'pptx': return '📽️';
        default: return '📄';
    }
}

function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
</script>
