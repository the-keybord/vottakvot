<?php
/**
 * TikTak - Course Dashboard Aggregator View
 * Clean classroom design with:
 * 1. Single Top Navigation Bar with high-contrast projector access code banner.
 * 2. Floating Link Cards Carousel in the air (no arrows, colorful filled cards).
 * 3. Day-based File Console (split layout: drag/drop & pinned on left, day files navigator on right).
 * 4. In-place live Teacher Edit Mode (remove icons & pin controls visible only in admin view).
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

<div class="course-page-wrapper">
    <!-- 1. Single Clean Top Navigation Bar (Classroom Projector Ready) -->
    <header class="course-unified-header">
        <div class="course-header-brand">
            <a href="/" class="brand-link" title="TikTak Gateway">
                <div class="brand-icon">⚡</div>
                <span class="brand-name">tik.tak.md</span>
            </a>
            <span class="header-divider">/</span>
            <div class="course-header-meta">
                <span class="badge badge-course">🎓 Course</span>
                <span class="course-name-text"><?= $courseTitle ?></span>
            </div>
        </div>

        <!-- Prominent Projector Access Code Banner for Students -->
        <div class="course-projector-banner" onclick="copyToClipboard('<?= $accessUrl ?>', 'Student link copied!')" title="Click to copy student access link">
            <div class="projector-banner-label">tik.tak.md</div>
            <div class="projector-banner-slash">/</div>
            <div class="projector-banner-prefix">CODE:</div>
            <div class="projector-banner-code"><?= $courseCode ?></div>
            <button type="button" class="projector-copy-icon" aria-label="Copy Code">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </button>
        </div>

        <div class="course-header-actions">
            <?php if ($isProf): ?>
                <button type="button" id="btnCourseEditToggle" class="btn btn-secondary btn-sm edit-mode-pill" onclick="toggleCourseEditMode()">
                    <span id="editModeIcon">✏️</span>
                    <span id="editModeText">Edit Mode</span>
                </button>
                <a href="/prof/dashboard.php" class="btn btn-ghost btn-sm" title="Teacher Dashboard">👑 Dashboard</a>
                <a href="/prof/logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-ghost btn-sm" style="color: var(--accent-rose);">Sign Out</a>
            <?php else: ?>
                <a href="/" class="btn btn-ghost btn-sm">Enter Code</a>
                <button type="button" class="btn btn-primary btn-sm" onclick="openTeacherLoginModal()" title="Teacher Login">Teacher Login</button>
            <?php endif; ?>
        </div>
    </header>

    <!-- 2. Floating Link Cards "In The Air" Carousel (No arrows, colorful filling) -->
    <div class="course-air-carousel-container">
        <div class="course-air-carousel-track" id="courseAirTrack">
            <!-- Dynamic colorful cards injected via JavaScript -->
        </div>
    </div>

    <!-- 3. Screen-Fitted File Layout: Left Column (Dropzone & Pinned), Right Column (Day Files) -->
    <div class="course-files-wrapper">
        <div class="course-files-grid-layout">
            <!-- LEFT COLUMN: Drag & Drop Card & Pinned Materials -->
            <div class="course-files-sidebar" id="courseFilesSidebar">
                <!-- Upload Dropzone (Available to both students and teachers) -->
                <div id="courseDropZone" class="upload-dropzone course-upload-zone" onclick="triggerCourseFileInput()">
                    <input type="file" id="courseFileInputElem" style="display: none;" onchange="handleCourseFileSelect(event)">
                    <div class="dropzone-content">
                        <div class="dropzone-icon">
                            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <div class="dropzone-text">
                            <h3 style="font-size: 0.95rem; margin-bottom: 0.2rem;">Drag &amp; drop files, or <span class="highlight-link">browse</span></h3>
                            <p class="dropzone-hints" style="font-size: 0.76rem;">
                                <span>Max 500 MB</span> &bull; <span>Worksheets, homework, starter code</span>
                            </p>
                        </div>
                    </div>

                    <!-- Upload Progress Bar Overlay -->
                    <div id="courseUploadProgressContainer" class="upload-progress-container" style="display: none;" onclick="event.stopPropagation()">
                        <div class="progress-info-row">
                            <div id="courseUploadFileName" class="upload-filename">Uploading...</div>
                            <div id="courseUploadProgressPct" class="upload-pct">0%</div>
                        </div>
                        <div class="progress-track">
                            <div id="courseUploadProgressBar" class="progress-fill" style="width: 0%;"></div>
                        </div>
                        <div id="courseUploadWarningNote" class="upload-warning-note" style="display: none;"></div>
                    </div>
                </div>

                <!-- Pinned Materials Section -->
                <div id="coursePinnedSection" class="pinned-section-card course-pinned-section" style="display: none;">
                    <div class="pinned-section-header" style="margin-bottom: 0.65rem;">
                        <div class="pinned-header-title">
                            <span class="pinned-icon">📌</span>
                            <h2 style="font-size: 0.95rem;">Pinned Materials</h2>
                        </div>
                        <span id="coursePinnedCountBadge" class="pinned-count-pill">0 pinned</span>
                    </div>
                    <div id="coursePinnedFilesList" class="course-pinned-grid">
                        <!-- Pinned mini cards injected dynamically -->
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Single Day Full-Width Card Navigator (Day 1 of N) -->
            <div class="course-files-main">
                <div id="courseDayViewerCard" class="day-viewer-card" style="display: none;">
                    <!-- Day Navigation Header Bar -->
                    <div class="day-nav-bar">
                        <button id="btnCoursePrevDay" class="btn-day-nav" onclick="changeCourseDay(-1)" title="Previous active day (Arrow Left)" aria-label="Previous Day">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                            <span class="nav-btn-text">Previous Day</span>
                        </button>

                        <div class="day-center-info">
                            <div class="day-counter-label" id="courseDayCounterLabel">Day 1 of 1</div>
                            <h2 class="day-main-title" id="courseDayMainTitle">Today</h2>
                            <div class="day-summary-badges">
                                <span id="courseDayFilesBadge" class="day-metric-pill">0 files</span>
                                <span id="courseDaySizeBadge" class="day-metric-pill">0 MB</span>
                            </div>
                        </div>

                        <button id="btnCourseNextDay" class="btn-day-nav" onclick="changeCourseDay(1)" title="Next active day (Arrow Right)" aria-label="Next Day">
                            <span class="nav-btn-text">Next Day</span>
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>

                    <!-- Date Ribbon Pills (Quick Day Jumper) -->
                    <div class="date-ribbon-bar">
                        <span class="ribbon-label">Jump to Date:</span>
                        <div id="courseDateRibbonPills" class="date-ribbon-pills">
                            <!-- Date pills injected dynamically -->
                        </div>
                    </div>

                    <!-- Day Search & Filter Bar -->
                    <div class="day-toolbar">
                        <div class="day-search-box">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" id="courseDaySearchInput" class="form-control" placeholder="Search files in this day..." oninput="filterCourseDayFiles(this.value)">
                        </div>
                        <div class="day-keyboard-hint">
                            <span>Use <kbd>◀</kbd> and <kbd>▶</kbd> arrows to switch days</span>
                        </div>
                    </div>

                    <!-- Full-Width Active Day Files List (Screen-fitted with internal scroll) -->
                    <div id="courseActiveDayFilesList" class="day-files-vertical-list course-scrollable-files-list">
                        <!-- File items injected dynamically -->
                    </div>
                </div>

                <!-- Empty Files State -->
                <div id="courseEmptyFilesState" class="empty-files-placeholder" style="display: none;">
                    <div class="empty-files-icon">📁</div>
                    <h3>No course files uploaded yet</h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.5rem;">
                        Use the upload box on the left to share worksheets, completed homework, or starter files.
                    </p>
                </div>

                <!-- Loading State Indicator -->
                <div id="courseLoadingFilesState" class="empty-files-placeholder">
                    <div class="empty-files-icon">⏳</div>
                    <h3>Loading course files...</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- In-Page Add / Edit Link Card Modal (For Teachers) -->
<div id="courseCardModal" class="modal-backdrop">
    <div class="modal-box" style="max-width: 480px;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span id="courseCardModalIconHeader" style="font-size: 1.3rem;">🔗</span>
                <h2 class="modal-title" id="courseCardModalTitle">Add Link Card</h2>
            </div>
            <button type="button" class="modal-close" onclick="closeCourseCardModal()">&times;</button>
        </div>
        <form id="courseCardForm" onsubmit="handleCourseCardSubmit(event)">
            <input type="hidden" id="cardEditId" value="">
            <div class="modal-body" style="padding: 1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="cardTitleInput">Card Title *</label>
                    <input type="text" id="cardTitleInput" class="form-control" placeholder="e.g. Scratch Project, Google Slides, Quiz" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label" for="cardUrlInput">Destination URL or Code *</label>
                    <input type="text" id="cardUrlInput" class="form-control" placeholder="e.g. https://scratch.mit.edu or 6-letter code" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label">Emoji / Icon</label>
                    <div class="emoji-picker-row">
                        <input type="text" id="cardIconInput" class="form-control" style="width: 70px; text-align: center; font-size: 1.3rem;" value="🚀" maxlength="4">
                        <div class="emoji-presets">
                            <button type="button" class="btn-emoji-quick" onclick="setCardEmoji('🚀')">🚀</button>
                            <button type="button" class="btn-emoji-quick" onclick="setCardEmoji('🐱')">🐱</button>
                            <button type="button" class="btn-emoji-quick" onclick="setCardEmoji('📊')">📊</button>
                            <button type="button" class="btn-emoji-quick" onclick="setCardEmoji('💻')">💻</button>
                            <button type="button" class="btn-emoji-quick" onclick="setCardEmoji('🎨')">🎨</button>
                            <button type="button" class="btn-emoji-quick" onclick="setCardEmoji('🎮')">🎮</button>
                            <button type="button" class="btn-emoji-quick" onclick="setCardEmoji('📚')">📚</button>
                            <button type="button" class="btn-emoji-quick" onclick="setCardEmoji('🔗')">🔗</button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Card Accent Color</label>
                    <div class="color-picker-row">
                        <label class="color-radio"><input type="radio" name="cardColor" value="#3b82f6" checked><span class="color-dot" style="background: #3b82f6;"></span></label>
                        <label class="color-radio"><input type="radio" name="cardColor" value="#10b981"><span class="color-dot" style="background: #10b981;"></span></label>
                        <label class="color-radio"><input type="radio" name="cardColor" value="#f59e0b"><span class="color-dot" style="background: #f59e0b;"></span></label>
                        <label class="color-radio"><input type="radio" name="cardColor" value="#ef4444"><span class="color-dot" style="background: #ef4444;"></span></label>
                        <label class="color-radio"><input type="radio" name="cardColor" value="#8b5cf6"><span class="color-dot" style="background: #8b5cf6;"></span></label>
                        <label class="color-radio"><input type="radio" name="cardColor" value="#06b6d4"><span class="color-dot" style="background: #06b6d4;"></span></label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="cardDescInput">Subtitle / Note (Optional)</label>
                    <input type="text" id="cardDescInput" class="form-control" placeholder="e.g. Activity 1 • In pairs" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeCourseCardModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btnSaveCourseCard">Save Card</button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Course Hub Client Controller
 */
const COURSE_CODE = <?= json_encode($resource['code']) ?>;
const IS_TEACHER = <?= $isProf ? 'true' : 'false' ?>;

let courseCards = <?= json_encode($courseCards, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let isEditMode = false;

// Files data state matching standard files_view
let activeDaysData = [];
let pinnedFilesData = [];
let currentDayIndex = 0;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initial render of cards
    renderAirCards();

    // 2. Fetch files from files API
    loadCourseFiles();

    // 3. Setup keyboard navigation for days
    document.addEventListener('keydown', (e) => {
        if (['input', 'textarea'].includes(document.activeElement.tagName.toLowerCase())) return;
        if (e.key === 'ArrowLeft') changeCourseDay(-1);
        if (e.key === 'ArrowRight') changeCourseDay(1);
    });

    // 4. Setup drag & drop for upload
    setupCourseDropZone();
});

/**
 * Toggle Teacher Edit Mode
 */
function toggleCourseEditMode() {
    isEditMode = !isEditMode;
    const btn = document.getElementById('btnCourseEditToggle');
    const icon = document.getElementById('editModeIcon');
    const text = document.getElementById('editModeText');
    const dropZone = document.getElementById('courseDropZone');

    if (isEditMode) {
        if (btn) btn.classList.add('active');
        if (icon) icon.innerText = '👁️';
        if (text) text.innerText = 'Student View';
        showToast('Admin Edit Mode active: tools enabled', 'info');
    } else {
        if (btn) btn.classList.remove('active');
        if (icon) icon.innerText = '✏️';
        if (text) text.innerText = 'Edit Mode';
        showToast('Switched to Student View', 'info');
    }

    renderAirCards();
    if (activeDaysData.length > 0) {
        renderCurrentCourseDayView();
    }
    renderCoursePinnedSection(pinnedFilesData);
}

/**
 * Render Floating Air Cards Carousel with Rich Color Filling
 */
function renderAirCards() {
    const track = document.getElementById('courseAirTrack');
    if (!track) return;
    track.innerHTML = '';

    if (!Array.isArray(courseCards)) {
        courseCards = [];
    }

    // Render cards
    courseCards.forEach((c, idx) => {
        const domain = extractDomain(c.url);
        const cardElem = document.createElement('div');
        cardElem.className = 'air-card';
        const color = c.color || '#3b82f6';

        // Rich color filling & styling
        cardElem.style.setProperty('--card-accent', color);
        cardElem.style.setProperty('--card-accent-soft', color + '22');
        cardElem.style.setProperty('--card-accent-solid', color);
        cardElem.style.borderColor = color + '55';
        cardElem.style.background = `linear-gradient(135deg, ${color}14 0%, ${color}06 100%), #ffffff`;

        cardElem.innerHTML = `
            <div class="air-card-stripe" style="background: ${escapeHtml(color)}"></div>
            <div class="air-card-top">
                <div class="air-card-icon-wrap" style="background: ${color}20; border-color: ${color}55;">
                    <span class="air-card-icon">${escapeHtml(c.icon || '🔗')}</span>
                </div>
                <span class="air-card-domain" style="background: ${color}15; color: ${color}; border: 1px solid ${color}30;">
                    ${escapeHtml(domain)}
                </span>
            </div>
            <div class="air-card-body">
                <h3 class="air-card-title">${escapeHtml(c.title || 'Resource')}</h3>
                ${c.desc ? `<p class="air-card-desc">${escapeHtml(c.desc)}</p>` : ''}
            </div>
            
            ${(IS_TEACHER && isEditMode) ? `
                <div class="air-card-edit-actions" onclick="event.stopPropagation()">
                    <button type="button" class="btn-air-action" onclick="openEditCardModal('${escapeHtml(c.id || '')}', ${idx})" title="Edit card">✏️</button>
                    <button type="button" class="btn-air-action btn-air-delete" onclick="deleteCard(${idx})" title="Delete card">🗑️</button>
                </div>
            ` : ''}
        `;

        cardElem.onclick = (e) => {
            if (e.target.closest('.air-card-edit-actions')) return;
            const targetUrl = c.url || '#';
            window.open(targetUrl, '_blank', 'noopener,noreferrer');
        };

        track.appendChild(cardElem);
    });

    // In Edit Mode, append floating "+ Add Card" card
    if (IS_TEACHER && isEditMode) {
        const addCardElem = document.createElement('div');
        addCardElem.className = 'air-card air-card-add';
        addCardElem.onclick = () => openAddCardModal();
        addCardElem.innerHTML = `
            <div class="air-card-add-inner">
                <div class="air-card-add-icon">➕</div>
                <div class="air-card-add-label">Add Link Card</div>
            </div>
        `;
        track.appendChild(addCardElem);
    }

    // If completely empty and in student view
    if (courseCards.length === 0 && (!IS_TEACHER || !isEditMode)) {
        track.innerHTML = `
            <div class="air-cards-empty-notice">
                <span>🔗 No launchpad links added yet.</span>
            </div>
        `;
    }
}

function extractDomain(url) {
    if (!url) return 'Link';
    try {
        if (!url.startsWith('http')) return url;
        const parsed = new URL(url);
        return parsed.hostname.replace(/^www\./, '');
    } catch (e) {
        return 'Link';
    }
}

/**
 * Card Modal & Persistence
 */
function openAddCardModal() {
    document.getElementById('cardEditId').value = '';
    document.getElementById('cardTitleInput').value = '';
    document.getElementById('cardUrlInput').value = '';
    document.getElementById('cardIconInput').value = '🚀';
    document.getElementById('cardDescInput').value = '';
    document.getElementById('courseCardModalTitle').innerText = 'Add Link Card';
    document.getElementById('courseCardModalIconHeader').innerText = '➕';

    const colorRadios = document.querySelectorAll('input[name="cardColor"]');
    if (colorRadios.length > 0) colorRadios[0].checked = true;

    openCourseModal('courseCardModal');
}

function openEditCardModal(cardId, idx) {
    const card = courseCards[idx];
    if (!card) return;

    document.getElementById('cardEditId').value = card.id || `card_${idx}`;
    document.getElementById('cardTitleInput').value = card.title || '';
    document.getElementById('cardUrlInput').value = card.url || '';
    document.getElementById('cardIconInput').value = card.icon || '🚀';
    document.getElementById('cardDescInput').value = card.desc || '';
    document.getElementById('courseCardModalTitle').innerText = 'Edit Link Card';
    document.getElementById('courseCardModalIconHeader').innerText = '✏️';

    const colorRadios = document.querySelectorAll('input[name="cardColor"]');
    colorRadios.forEach(r => {
        if (r.value === card.color) r.checked = true;
    });

    openCourseModal('courseCardModal');
}

function openCourseModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('active');
}

function closeCourseCardModal() {
    const m = document.getElementById('courseCardModal');
    if (m) m.classList.remove('active');
}

function setCardEmoji(emoji) {
    const iconInput = document.getElementById('cardIconInput');
    if (iconInput) iconInput.value = emoji;
}

async function handleCourseCardSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveCourseCard');
    const editId = document.getElementById('cardEditId').value;
    const title = document.getElementById('cardTitleInput').value.trim();
    let url = document.getElementById('cardUrlInput').value.trim();
    const icon = document.getElementById('cardIconInput').value.trim() || '🔗';
    const desc = document.getElementById('cardDescInput').value.trim();
    const checkedColor = document.querySelector('input[name="cardColor"]:checked');
    const color = checkedColor ? checkedColor.value : '#3b82f6';

    if (!title || !url) {
        showToast('Title and URL are required', 'error');
        return;
    }

    if (url.match(/^[A-Za-z0-9]{3,10}$/)) {
        url = '/code=' + url.toUpperCase();
    } else if (!url.match(/^(https?:\/\/|\/|#)/i)) {
        url = 'https://' + url;
    }

    btn.disabled = true;
    btn.innerText = 'Saving...';

    const updatedList = [...courseCards];
    if (editId) {
        const foundIdx = updatedList.findIndex(c => (c.id === editId));
        if (foundIdx !== -1) {
            updatedList[foundIdx] = { ...updatedList[foundIdx], title, url, icon, color, desc };
        }
    } else {
        updatedList.push({
            id: 'card_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
            title,
            url,
            icon,
            color,
            desc
        });
    }

    await saveCardsToServer(updatedList);
    btn.disabled = false;
    btn.innerText = 'Save Card';
    closeCourseCardModal();
}

async function deleteCard(idx) {
    if (!confirm('Are you sure you want to delete this link card?')) return;
    const updatedList = courseCards.filter((_, i) => i !== idx);
    await saveCardsToServer(updatedList);
}

async function saveCardsToServer(newList) {
    try {
        const res = await fetch('/api/resources.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_course_cards',
                code: COURSE_CODE,
                cards: newList
            })
        });
        const data = await res.json();
        if (data.success) {
            courseCards = data.cards || newList;
            renderAirCards();
            showToast('Course cards saved to database!', 'success');
        } else {
            showToast(data.error || 'Failed to save cards', 'error');
        }
    } catch (err) {
        showToast('Network error while saving cards', 'error');
    }
}

/**
 * File Console Integration (Screen-fitted layout, only admin can delete/pin)
 */
async function loadCourseFiles() {
    try {
        const res = await fetch(`/api/files.php?action=list&code=${encodeURIComponent(COURSE_CODE)}`);
        const data = await res.json();

        const loadingElem = document.getElementById('courseLoadingFilesState');
        if (loadingElem) loadingElem.style.display = 'none';

        if (data.success) {
            activeDaysData = data.days || [];
            pinnedFilesData = data.pinned_files || [];

            renderCoursePinnedSection(pinnedFilesData);

            if (activeDaysData.length === 0) {
                document.getElementById('courseDayViewerCard').style.display = 'none';
                document.getElementById('courseEmptyFilesState').style.display = 'block';
            } else {
                document.getElementById('courseEmptyFilesState').style.display = 'none';
                document.getElementById('courseDayViewerCard').style.display = 'block';
                if (currentDayIndex >= activeDaysData.length) {
                    currentDayIndex = 0;
                }
                renderCurrentCourseDayView();
            }
        } else {
            showToast(data.error || 'Failed to load course files', 'error');
        }
    } catch (err) {
        const loadingElem = document.getElementById('courseLoadingFilesState');
        if (loadingElem) loadingElem.style.display = 'none';
        showToast('Failed to connect to storage API', 'error');
    }
}

function renderCoursePinnedSection(pinnedFiles) {
    const section = document.getElementById('coursePinnedSection');
    const list = document.getElementById('coursePinnedFilesList');
    const countBadge = document.getElementById('coursePinnedCountBadge');

    if (!pinnedFiles || pinnedFiles.length === 0) {
        section.style.display = 'none';
        if (list) list.innerHTML = '';
        return;
    }

    const canTeacherAct = (IS_TEACHER && isEditMode);
    const sortedPinned = [...pinnedFiles].sort((a, b) => new Date(b.uploaded_at).getTime() - new Date(a.uploaded_at).getTime());
    section.style.display = 'block';
    if (countBadge) countBadge.innerText = `${sortedPinned.length} pinned`;

    list.innerHTML = sortedPinned.map(f => {
        const safeId = escapeHtml(String(f.id));
        const safeName = escapeHtml(String(f.filename || f.original_filename || 'file'));
        const icon = f.type_meta ? f.type_meta.icon : '📄';
        return `
            <div class="course-pinned-mini-card" id="pinned-file-${safeId}">
                <div class="pinned-mini-top">
                    <span class="pinned-mini-icon">${icon}</span>
                    <div class="pinned-mini-info">
                        <span class="pinned-mini-name" title="${safeName}">${safeName}</span>
                        <span class="pinned-mini-size">${escapeHtml(f.size_formatted || '')}</span>
                    </div>
                </div>
                <div class="pinned-mini-actions">
                    <a href="${f.download_url}&code=${encodeURIComponent(COURSE_CODE)}" class="btn btn-primary btn-sm btn-pinned-dl" download>
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Download</span>
                    </a>
                    ${canTeacherAct ? `
                        <button type="button" class="btn-action-pin is-pinned btn-sm" onclick="togglePinCourseFile('${safeId}')" title="Unpin file" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                            <span class="pin-icon">📌</span>
                            <span class="pin-text">Unpin</span>
                        </button>
                        <button type="button" class="btn-action-delete btn-sm" onclick="deleteCourseFile('${safeId}', '${safeName}')" title="Delete file" style="padding: 0.25rem 0.5rem;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function renderCurrentCourseDayView() {
    if (activeDaysData.length === 0) return;

    const currentDay = activeDaysData[currentDayIndex];
    const totalDays = activeDaysData.length;

    // Header Meta
    document.getElementById('courseDayCounterLabel').innerText = `Day ${currentDayIndex + 1} of ${totalDays}`;
    document.getElementById('courseDayMainTitle').innerText = `${currentDay.day_badge} — ${currentDay.title}`;
    document.getElementById('courseDayFilesBadge').innerText = `${currentDay.total_files} file${currentDay.total_files === 1 ? '' : 's'}`;
    document.getElementById('courseDaySizeBadge').innerText = currentDay.total_size_formatted;

    // Buttons
    const prevBtn = document.getElementById('btnCoursePrevDay');
    const nextBtn = document.getElementById('btnCourseNextDay');
    if (prevBtn) prevBtn.disabled = (currentDayIndex === 0);
    if (nextBtn) nextBtn.disabled = (currentDayIndex === totalDays - 1);

    // Date Ribbon Pills
    const ribbon = document.getElementById('courseDateRibbonPills');
    if (ribbon) {
        ribbon.innerHTML = '';
        activeDaysData.forEach((d, idx) => {
            const pill = document.createElement('button');
            pill.type = 'button';
            pill.className = `date-ribbon-pill ${idx === currentDayIndex ? 'active' : ''}`;
            pill.innerHTML = `<span>${escapeHtml(d.day_badge)}</span> <small>(${d.total_files})</small>`;
            pill.onclick = () => {
                currentDayIndex = idx;
                renderCurrentCourseDayView();
            };
            ribbon.appendChild(pill);
        });
    }

    // Sort files newest first
    const sortedDayFiles = [...currentDay.files].sort((a, b) => new Date(b.uploaded_at).getTime() - new Date(a.uploaded_at).getTime());
    const list = document.getElementById('courseActiveDayFilesList');
    if (list) {
        list.innerHTML = sortedDayFiles.map(f => renderCourseFileCard(f, false)).join('');
    }

    const searchInput = document.getElementById('courseDaySearchInput');
    if (searchInput) searchInput.value = '';
}

function changeCourseDay(delta) {
    const newIdx = currentDayIndex + delta;
    if (newIdx >= 0 && newIdx < activeDaysData.length) {
        currentDayIndex = newIdx;
        renderCurrentCourseDayView();
    }
}

function renderCourseFileCard(file, isPinnedArea = false) {
    const heavyClass = file.is_heavy ? 'file-card-heavy' : '';
    const heavyBadge = file.is_heavy 
        ? `<span class="badge badge-heavy-red" title="Heavy File (>100MB)">⚠️ ${file.size_formatted}</span>` 
        : `<span class="file-size-tag">${file.size_formatted}</span>`;

    // Strictly visible in Admin Edit Mode only
    const canTeacherAct = (IS_TEACHER && isEditMode);
    const safeId = escapeHtml(String(file.id));
    const safeName = escapeHtml(String(file.filename || file.original_filename || 'file'));

    const pinBtn = canTeacherAct
        ? `<button type="button" class="btn-action-pin ${file.is_pinned ? 'is-pinned' : ''}" onclick="togglePinCourseFile('${safeId}')" title="${file.is_pinned ? 'Unpin file' : 'Pin to top section'}">
            <span class="pin-icon">📌</span>
            <span class="pin-text">${file.is_pinned ? 'Pinned' : 'Pin'}</span>
           </button>` 
        : (file.is_pinned ? `<span class="pinned-indicator-pill" title="Pinned by teacher">📌 Pinned</span>` : '');

    const deleteBtn = canTeacherAct
        ? `<button type="button" class="btn-action-delete" onclick="deleteCourseFile('${safeId}', '${safeName}')" title="Delete file">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
           </button>`
        : '';

    return `
        <div class="file-item-card ${heavyClass}" id="file-card-${safeId}">
            <div class="file-card-main">
                <div class="file-icon-box ${file.type_meta ? file.type_meta.class : ''}">
                    <span>${file.type_meta ? file.type_meta.icon : '📄'}</span>
                </div>
                <div class="file-meta-content">
                    <div class="file-filename-row">
                        <h4 class="file-display-name" title="${safeName}">${safeName}</h4>
                        ${heavyBadge}
                    </div>
                    <div class="file-submeta-row">
                        <span class="file-ext-tag">${escapeHtml(file.type_meta ? file.type_meta.tag : 'FILE')}</span>
                        <span>&bull;</span>
                        <span class="file-upload-time">${escapeHtml(file.upload_date)} at ${escapeHtml(file.time_formatted || '')}</span>
                        <span>&bull;</span>
                        <span class="file-dl-count">${file.downloads_count} download${file.downloads_count === 1 ? '' : 's'}</span>
                    </div>
                </div>
            </div>
            <div class="file-card-actions">
                ${pinBtn}
                ${deleteBtn}
                <a href="${file.download_url}&code=${encodeURIComponent(COURSE_CODE)}" class="btn btn-primary btn-sm btn-download-file" download>
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Download</span>
                </a>
            </div>
        </div>
    `;
}

function filterCourseDayFiles(query) {
    const q = query.trim().toLowerCase();
    const rows = document.querySelectorAll('#courseActiveDayFilesList .file-item-card');
    rows.forEach(row => {
        const titleElem = row.querySelector('.file-display-name');
        const text = titleElem ? titleElem.innerText.toLowerCase() : '';
        row.style.display = text.includes(q) ? 'flex' : 'none';
    });
}

async function togglePinCourseFile(fileId) {
    if (!IS_TEACHER) return;
    try {
        const res = await fetch('/api/files.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_pin', file_id: fileId })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || (data.is_pinned ? 'File pinned to top section!' : 'File unpinned.'), 'success');
            await loadCourseFiles();
        } else {
            showToast(data.error || 'Failed to update pin', 'error');
        }
    } catch (e) {
        showToast('Network error while toggling pin', 'error');
    }
}

async function deleteCourseFile(fileId, filename) {
    if (!IS_TEACHER) {
        showToast('Only teachers can delete files.', 'error');
        return;
    }
    if (!confirm(`Are you sure you want to delete "${filename}"?`)) return;
    try {
        const res = await fetch('/api/files.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', file_id: fileId })
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Deleted ${filename}`, 'success');
            await loadCourseFiles();
        } else {
            showToast(data.error || 'Delete failed', 'error');
        }
    } catch (e) {
        showToast('Network error while deleting file', 'error');
    }
}

/**
 * File Upload Handling
 */
function triggerCourseFileInput() {
    const input = document.getElementById('courseFileInputElem');
    if (input) input.click();
}

function handleCourseFileSelect(e) {
    const files = e.target.files;
    if (files.length > 0) {
        uploadCourseFile(files[0]);
        e.target.value = '';
    }
}

function setupCourseDropZone() {
    const dz = document.getElementById('courseDropZone');
    if (!dz) return;

    ['dragenter', 'dragover'].forEach(name => {
        dz.addEventListener(name, (e) => {
            e.preventDefault();
            dz.classList.add('drag-active');
        }, false);
    });

    ['dragleave', 'drop'].forEach(name => {
        dz.addEventListener(name, (e) => {
            e.preventDefault();
            dz.classList.remove('drag-active');
        }, false);
    });

    dz.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            uploadCourseFile(files[0]);
        }
    });
}

function uploadCourseFile(file) {
    const maxLimit = 500 * 1024 * 1024;
    const heavyThreshold = 100 * 1024 * 1024;

    if (file.size > maxLimit) {
        showToast(`File "${file.name}" exceeds 500 MB limit!`, 'error');
        return;
    }

    const progressContainer = document.getElementById('courseUploadProgressContainer');
    const fileNameElem = document.getElementById('courseUploadFileName');
    const pctElem = document.getElementById('courseUploadProgressPct');
    const barElem = document.getElementById('courseUploadProgressBar');
    const warningElem = document.getElementById('courseUploadWarningNote');

    progressContainer.style.display = 'block';
    fileNameElem.innerText = `Uploading: ${file.name} (${(file.size / (1024 * 1024)).toFixed(1)} MB)`;
    pctElem.innerText = '0%';
    barElem.style.width = '0%';

    if (file.size >= heavyThreshold) {
        warningElem.style.display = 'block';
        warningElem.innerHTML = `⚠️ <strong>Heavy File Alert:</strong> This file is over 100 MB.`;
    } else {
        warningElem.style.display = 'none';
    }

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('code', COURSE_CODE);
    formData.append('file', file);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/files.php', true);

    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            pctElem.innerText = `${pct}%`;
            barElem.style.width = `${pct}%`;
        }
    };

    xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.success) {
                    showToast(`Uploaded "${res.file.filename}" successfully!`, 'success');
                    setTimeout(() => {
                        progressContainer.style.display = 'none';
                        loadCourseFiles();
                    }, 500);
                } else {
                    showToast(res.error || 'Upload failed', 'error');
                    progressContainer.style.display = 'none';
                }
            } catch (err) {
                showToast('Server returned invalid response', 'error');
                progressContainer.style.display = 'none';
            }
        } else {
            showToast(`Upload failed (${xhr.status})`, 'error');
            progressContainer.style.display = 'none';
        }
    };

    xhr.onerror = () => {
        showToast('Network error during file upload', 'error');
        progressContainer.style.display = 'none';
    };

    xhr.send(formData);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
