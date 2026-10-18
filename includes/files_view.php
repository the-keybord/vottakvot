<?php
/**
 * TikTak - File Console View Component (MinIO Storage Backend)
 * Features: Full-width Day Card View with Arrow Navigation, Top Pinned Files Section,
 * Anonymous 500MB Uploads, >100MB Red Alerts, and Professor Deletion/Pin Controls.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$isProf = is_logged_in();
$expiryStatus = format_expiry_status($resource['expires_at'] ?? null);
$stats = get_resource_files_summary($pdo, (int)$resource['id']);
$baseUrl = get_base_url();
$accessUrl = $baseUrl . '/code=' . $resource['code'];
?>
<div class="file-console-container">
    <!-- Top Console Hero Header -->
    <div class="console-hero-card">
        <div class="console-hero-header">
            <div class="console-hero-left">
                <div class="console-badge-row">
                    <span class="badge badge-files">📁 File Console</span>
                    <span class="badge <?= $expiryStatus['badge_class'] ?>"><?= htmlspecialchars($expiryStatus['label']) ?></span>
                    <?php if ($isProf): ?>
                        <span class="badge badge-active" style="background: rgba(16, 185, 129, 0.15); color: var(--accent-emerald);">👑 Professor Mode (Pin &amp; Delete Access)</span>
                    <?php else: ?>
                        <span class="badge badge-neutral">👥 Anonymous Mode</span>
                    <?php endif; ?>
                </div>
                <h1 class="console-title"><?= htmlspecialchars($resource['title'] ?: 'File Console ' . $resource['code']) ?></h1>
                <div class="console-meta-bar">
                    <div class="meta-item">
                        <span class="meta-label">Code:</span>
                        <strong class="view-code-tag" style="cursor: pointer;" onclick="copyToClipboard('<?= $accessUrl ?>', 'Access link copied!')" title="Click to copy link">
                            <?= htmlspecialchars($resource['code']) ?>
                        </strong>
                    </div>
                    <span class="meta-divider">&bull;</span>
                    <div class="meta-item">
                        <span class="meta-label">Total Files:</span>
                        <strong id="headerTotalFiles"><?= number_format($stats['total_files']) ?></strong>
                    </div>
                    <span class="meta-divider">&bull;</span>
                    <div class="meta-item">
                        <span class="meta-label">Total Storage:</span>
                        <strong id="headerTotalSize"><?= $stats['total_size_formatted'] ?></strong>
                    </div>
                    <span class="meta-divider">&bull;</span>
                    <div class="meta-item">
                        <span class="meta-label">Gateway Views:</span>
                        <strong><?= number_format($resource['visits_count']) ?></strong>
                    </div>
                </div>
            </div>

            <div class="console-hero-actions">
                <button class="btn btn-secondary" onclick="copyToClipboard('<?= $accessUrl ?>', 'Access link copied!')">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    Share Link
                </button>
                <button class="btn btn-primary" onclick="triggerFileInput()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Upload File
                </button>
            </div>
        </div>

        <!-- Drag & Drop Upload Zone -->
        <div id="dropZone" class="upload-dropzone" onclick="triggerFileInput()">
            <input type="file" id="fileInputElem" style="display: none;" onchange="handleFileSelect(event)">
            
            <div class="dropzone-content">
                <div class="dropzone-icon">
                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <div class="dropzone-text">
                    <h3>Drag &amp; drop files here, or <span class="highlight-link">browse</span></h3>
                    <p class="dropzone-hints">
                        <span>Anonymous uploads allowed</span> &bull; 
                        <span>Max 500 MB per file</span> &bull; 
                        <span style="color: var(--accent-rose); font-weight: 600;">Files &gt; 100 MB flagged with red alert</span>
                    </p>
                </div>
            </div>

            <!-- Upload Progress Bar Overlay -->
            <div id="uploadProgressContainer" class="upload-progress-container" style="display: none;" onclick="event.stopPropagation()">
                <div class="progress-info-row">
                    <div id="uploadFileName" class="upload-filename">Uploading file...</div>
                    <div id="uploadProgressPct" class="upload-pct">0%</div>
                </div>
                <div class="progress-track">
                    <div id="uploadProgressBar" class="progress-fill" style="width: 0%;"></div>
                </div>
                <div id="uploadWarningNote" class="upload-warning-note" style="display: none;"></div>
            </div>
        </div>
    </div>

    <!-- Pinned Files Top Section (Always on Top) -->
    <div id="pinnedSection" class="pinned-section-card" style="display: none;">
        <div class="pinned-section-header">
            <div class="pinned-header-title">
                <span class="pinned-icon">📌</span>
                <h2>Pinned Materials</h2>
                <span id="pinnedCountBadge" class="pinned-count-pill">0 pinned</span>
            </div>
            <span class="pinned-subtitle">Important course materials pinned to top</span>
        </div>
        <div id="pinnedFilesList" class="pinned-files-vertical-list">
            <!-- Pinned files injected dynamically -->
        </div>
    </div>

    <!-- Single Day Full-Width Card Navigator -->
    <div id="dayViewerCard" class="day-viewer-card" style="display: none;">
        <!-- Day Navigation Header Bar -->
        <div class="day-nav-bar">
            <button id="btnPrevDay" class="btn-day-nav" onclick="changeDay(-1)" title="Previous active day (Arrow Left)" aria-label="Previous Day">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                <span class="nav-btn-text">Previous Day</span>
            </button>

            <div class="day-center-info">
                <div class="day-counter-label" id="dayCounterLabel">Day 1 of 1</div>
                <h2 class="day-main-title" id="dayMainTitle">Today, September 23, 2026</h2>
                <div class="day-summary-badges">
                    <span id="dayFilesBadge" class="day-metric-pill">0 files</span>
                    <span id="daySizeBadge" class="day-metric-pill">0 MB</span>
                </div>
            </div>

            <button id="btnNextDay" class="btn-day-nav" onclick="changeDay(1)" title="Next active day (Arrow Right)" aria-label="Next Day">
                <span class="nav-btn-text">Next Day</span>
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </div>

        <!-- Date Ribbon Pills (Quick Day Jumper) -->
        <div class="date-ribbon-bar">
            <span class="ribbon-label">Jump to Date:</span>
            <div id="dateRibbonPills" class="date-ribbon-pills">
                <!-- Date pills injected dynamically -->
            </div>
        </div>

        <!-- Day Search & Filter Bar -->
        <div class="day-toolbar">
            <div class="day-search-box">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="daySearchInput" class="form-control" placeholder="Search full file name in this day..." oninput="filterDayFiles(this.value)">
            </div>
            <div class="day-keyboard-hint">
                <span>Tip: Use <kbd>◀</kbd> and <kbd>▶</kbd> keyboard arrows to switch days</span>
            </div>
        </div>

        <!-- Full-Width Day Files Vertical List (Newest First) -->
        <div id="activeDayFilesList" class="day-files-vertical-list">
            <!-- Active day files injected dynamically -->
        </div>
    </div>


    <!-- Loading State Placeholder -->
    <div id="loadingDaysState" class="days-loading-placeholder">
        <div class="spinner"></div>
        <p>Loading files from MinIO storage...</p>
    </div>

    <!-- Empty State Card (0 Files Total) -->
    <div id="emptyFilesState" class="empty-files-card" style="display: none;">
        <div class="empty-icon">📂</div>
        <h2>No files uploaded yet</h2>
        <p>This code folder is empty and ready. Anyone with code <strong class="view-code-tag"><?= htmlspecialchars($resource['code']) ?></strong> can drop files here anonymously.</p>
        <button class="btn btn-primary" onclick="triggerFileInput()">Drop First File</button>
    </div>
</div>

<script>
const CONSOLE_CODE = '<?= $resource['code'] ?>';
const IS_PROF = <?= $isProf ? 'true' : 'false' ?>;

let activeDaysData = [];
let pinnedFilesData = [];
let currentDayIndex = 0;

document.addEventListener('DOMContentLoaded', () => {
    initFileConsole();
    initKeyboardNav();
    loadFilesList();
});

function initFileConsole() {
    const dropZone = document.getElementById('dropZone');
    if (!dropZone) return;

    ['dragenter', 'dragover'].forEach(name => {
        dropZone.addEventListener(name, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(name => {
        dropZone.addEventListener(name, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            uploadSingleFile(files[0]);
        }
    });
}

function initKeyboardNav() {
    document.addEventListener('keydown', (e) => {
        // Ignore if user is typing in an input
        if (['input', 'textarea'].includes(document.activeElement.tagName.toLowerCase())) {
            return;
        }
        if (e.key === 'ArrowLeft') {
            changeDay(-1);
        } else if (e.key === 'ArrowRight') {
            changeDay(1);
        }
    });
}

function triggerFileInput() {
    const input = document.getElementById('fileInputElem');
    if (input) input.click();
}

function handleFileSelect(e) {
    const files = e.target.files;
    if (files.length > 0) {
        uploadSingleFile(files[0]);
        e.target.value = ''; // Reset
    }
}

/**
 * Upload file with real-time progress and >100MB heavy alert
 */
function uploadSingleFile(file) {
    const maxLimit = 500 * 1024 * 1024; // 500 MB
    const heavyThreshold = 100 * 1024 * 1024; // 100 MB

    if (file.size > maxLimit) {
        showToast(`File "${file.name}" exceeds the 500 MB limit! (${(file.size / (1024 * 1024)).toFixed(1)} MB)`, 'error');
        return;
    }

    const progressContainer = document.getElementById('uploadProgressContainer');
    const fileNameElem = document.getElementById('uploadFileName');
    const pctElem = document.getElementById('uploadProgressPct');
    const barElem = document.getElementById('uploadProgressBar');
    const warningElem = document.getElementById('uploadWarningNote');

    progressContainer.style.display = 'block';
    fileNameElem.innerText = `Uploading: ${file.name} (${(file.size / (1024 * 1024)).toFixed(1)} MB)`;
    pctElem.innerText = '0%';
    barElem.style.width = '0%';

    if (file.size >= heavyThreshold) {
        warningElem.style.display = 'block';
        warningElem.innerHTML = `⚠️ <strong>Heavy File Alert:</strong> This file is over 100 MB (${(file.size / (1024 * 1024)).toFixed(1)} MB). It will be marked with a red warning badge for teacher visibility.`;
    } else {
        warningElem.style.display = 'none';
    }

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('code', CONSOLE_CODE);
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
                        loadFilesList();
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

/**
 * Load files grouped by date from API
 */
async function loadFilesList() {
    try {
        const res = await fetch(`/api/files.php?action=list&code=${CONSOLE_CODE}`);
        const data = await res.json();

        const loadingElem = document.getElementById('loadingDaysState');
        if (loadingElem) loadingElem.style.display = 'none';

        if (data.success) {
            activeDaysData = data.days || [];
            pinnedFilesData = data.pinned_files || [];

            updateStatsBar(data.stats);
            renderPinnedSection(pinnedFilesData);

            if (activeDaysData.length === 0) {
                document.getElementById('dayViewerCard').style.display = 'none';
                document.getElementById('emptyFilesState').style.display = 'block';
            } else {
                document.getElementById('emptyFilesState').style.display = 'none';
                document.getElementById('dayViewerCard').style.display = 'block';
                if (currentDayIndex >= activeDaysData.length) {
                    currentDayIndex = 0;
                }
                renderCurrentDayView();
            }
        } else {
            showToast(data.error || 'Failed to load files', 'error');
        }
    } catch (err) {
        showToast('Failed to connect to storage API', 'error');
    }
}

function updateStatsBar(stats) {
    if (!stats) return;
    const totalFilesElem = document.getElementById('headerTotalFiles');
    const totalSizeElem = document.getElementById('headerTotalSize');
    if (totalFilesElem) totalFilesElem.innerText = stats.total_files;
    if (totalSizeElem) totalSizeElem.innerText = stats.total_size_formatted;
}

/**
 * Render Pinned Files at the top
 */
function renderPinnedSection(pinnedFiles) {
    const section = document.getElementById('pinnedSection');
    const list = document.getElementById('pinnedFilesList');
    const countBadge = document.getElementById('pinnedCountBadge');

    if (!pinnedFiles || pinnedFiles.length === 0) {
        section.style.display = 'none';
        if (list) list.innerHTML = '';
        return;
    }

    // Sort newest first
    const sortedPinned = [...pinnedFiles].sort((a, b) => new Date(b.uploaded_at).getTime() - new Date(a.uploaded_at).getTime());

    section.style.display = 'block';
    countBadge.innerText = `${sortedPinned.length} pinned`;
    list.innerHTML = sortedPinned.map(file => renderFileCard(file, true)).join('');
}

/**
 * Render Full-Width Active Day View
 */
function renderCurrentDayView() {
    if (activeDaysData.length === 0) return;

    const currentDay = activeDaysData[currentDayIndex];
    const totalDays = activeDaysData.length;

    // Day Header Meta
    document.getElementById('dayCounterLabel').innerText = `Day ${currentDayIndex + 1} of ${totalDays}`;
    document.getElementById('dayMainTitle').innerText = `${currentDay.day_badge} — ${currentDay.title}`;
    document.getElementById('dayFilesBadge').innerText = `${currentDay.total_files} file${currentDay.total_files === 1 ? '' : 's'}`;
    document.getElementById('daySizeBadge').innerText = currentDay.total_size_formatted;

    // Navigation buttons state
    const prevBtn = document.getElementById('btnPrevDay');
    const nextBtn = document.getElementById('btnNextDay');

    prevBtn.disabled = (currentDayIndex === 0);
    nextBtn.disabled = (currentDayIndex === totalDays - 1);

    // Render Quick Jump Date Ribbon
    const ribbon = document.getElementById('dateRibbonPills');
    ribbon.innerHTML = '';
    activeDaysData.forEach((d, idx) => {
        const pill = document.createElement('button');
        pill.className = `date-ribbon-pill ${idx === currentDayIndex ? 'active' : ''}`;
        pill.innerHTML = `<span>${escapeHtml(d.day_badge)}</span> <small>(${d.total_files})</small>`;
        pill.onclick = () => {
            currentDayIndex = idx;
            renderCurrentDayView();
        };
        ribbon.appendChild(pill);
    });

    // Ensure day files are sorted newest first by uploaded_at
    const sortedDayFiles = [...currentDay.files].sort((a, b) => new Date(b.uploaded_at).getTime() - new Date(a.uploaded_at).getTime());

    // Render Full-Width Files Vertical List
    const list = document.getElementById('activeDayFilesList');
    list.innerHTML = sortedDayFiles.map(file => renderFileCard(file, false)).join('');

    // Clear search filter input on day change
    const searchInput = document.getElementById('daySearchInput');
    if (searchInput) searchInput.value = '';
}

/**
 * Switch Active Day (-1 for earlier, +1 for later)
 */
function changeDay(delta) {
    const newIdx = currentDayIndex + delta;
    if (newIdx >= 0 && newIdx < activeDaysData.length) {
        currentDayIndex = newIdx;
        renderCurrentDayView();
    }
}

/**
 * Render Individual File Item Row (Full name visible, spacious layout)
 */
function renderFileCard(file, isPinnedArea = false) {
    const heavyClass = file.is_heavy ? 'file-card-heavy' : '';
    const heavyBadge = file.is_heavy 
        ? `<span class="badge badge-heavy-red" title="Heavy File (>100MB)">⚠️ ${file.size_formatted} — Heavy File</span>` 
        : `<span class="file-size-tag">${file.size_formatted}</span>`;

    // Teacher Pin Button
    const pinBtn = file.can_pin 
        ? `<button class="btn-action-pin ${file.is_pinned ? 'is-pinned' : ''}" onclick="togglePinFile(${file.id})" title="${file.is_pinned ? 'Unpin file from top' : 'Pin file to top section'}">
            <span class="pin-icon">📌</span>
            <span class="pin-text">${file.is_pinned ? 'Pinned' : 'Pin'}</span>
           </button>` 
        : (file.is_pinned ? `<span class="pinned-indicator-pill" title="Pinned by teacher">📌 Pinned</span>` : '');

    // Teacher Delete Button
    const deleteBtn = file.can_delete 
        ? `<button class="btn-action-delete" onclick="deleteFile(${file.id}, '${escapeHtml(file.filename)}')" title="Delete file (Professor only)">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
           </button>` 
        : '';

    return `
        <div class="file-item-card ${heavyClass}" id="file-row-${file.id}" data-filename="${escapeHtml(file.filename.toLowerCase())}">
            <div class="file-card-left">
                <div class="file-icon-badge ${file.type_meta.class}">
                    ${file.type_meta.icon}
                </div>
                <div class="file-info-main">
                    <div class="file-name-row">
                        <span class="file-display-name" title="${escapeHtml(file.filename)}">${escapeHtml(file.filename)}</span>
                    </div>
                    <div class="file-meta-sub">
                        ${heavyBadge}
                        <span class="meta-dot">&bull;</span>
                        <span class="file-time">Uploaded ${escapeHtml(file.time_formatted)}</span>
                        <span class="meta-dot">&bull;</span>
                        <span class="file-downloads">${file.downloads_count} download${file.downloads_count === 1 ? '' : 's'}</span>
                    </div>
                </div>
            </div>
            <div class="file-card-actions">
                ${pinBtn}
                <a href="${file.download_url}" class="btn btn-secondary btn-sm btn-download-file" title="Download file" download>
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Download</span>
                </a>
                ${deleteBtn}
            </div>
        </div>
    `;
}

/**
 * Toggle Pin on a file (Teacher only)
 */
async function togglePinFile(fileId) {
    if (!IS_PROF) {
        showToast('Only professors can pin files.', 'error');
        return;
    }

    try {
        const res = await fetch('/api/files.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_pin', file_id: fileId })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || (data.is_pinned ? 'File pinned to top' : 'File unpinned'), 'success');
            loadFilesList();
        } else {
            showToast(data.error || 'Failed to toggle pin', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

/**
 * Filter files live in active day
 */
function filterDayFiles(query) {
    const q = query.toLowerCase().trim();
    const fileCards = document.querySelectorAll('#activeDayFilesList .file-item-card');

    fileCards.forEach(card => {
        const name = card.getAttribute('data-filename') || '';
        if (name.includes(q)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}


/**
 * Delete File (Restricted to Professors)
 */
async function deleteFile(fileId, filename) {
    if (!IS_PROF) {
        showToast('Only professors can delete files.', 'error');
        return;
    }

    if (!confirm(`Are you sure you want to permanently delete "${filename}" from MinIO storage?`)) {
        return;
    }

    try {
        const res = await fetch('/api/files.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', file_id: fileId })
        });
        const data = await res.json();

        if (data.success) {
            showToast(`Deleted ${filename}`, 'success');
            loadFilesList();
        } else {
            showToast(data.error || 'Failed to delete file', 'error');
        }
    } catch (err) {
        showToast('Network error while deleting file', 'error');
    }
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
