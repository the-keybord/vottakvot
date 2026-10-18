/**
 * TikTak - Professor Dashboard & Micro-hosting Management Script
 */

document.addEventListener('DOMContentLoaded', () => {
    initResourceForm();
    initFilterSearch();
    initSiteManagerGlobalEvents();
});

let currentResourceType = 'redirect';
let currentExpiryPreset = 'never';

// Site Manager State
let currentSiteCode = '';
let currentSiteTitle = '';
let currentSiteFile = 'index.php';
let currentSiteSummary = null;
let isSiteFileDirty = false;

/**
 * Initialize Resource Modal & Creation Logic
 */
function initResourceForm() {
    // Generate initial random code when opening create modal
    const generateBtn = document.getElementById('btnGenerateCode');
    if (generateBtn) {
        generateBtn.addEventListener('click', generateNewCode);
    }

    // Resource Type Switcher (Redirect vs Text vs Files vs Hosting)
    const typeTabs = document.querySelectorAll('.type-tab');
    typeTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            typeTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentResourceType = tab.getAttribute('data-type');
            
            const linkField = document.getElementById('fieldRedirectLink');
            const textField = document.getElementById('fieldTextContent');
            const filesField = document.getElementById('fieldFilesInfo');
            const hostingField = document.getElementById('fieldHostingContent');
            const inputLink = document.getElementById('inputLink');
            const inputText = document.getElementById('inputText');
            
            if (currentResourceType === 'redirect') {
                if (linkField) linkField.style.display = 'block';
                if (textField) textField.style.display = 'none';
                if (filesField) filesField.style.display = 'none';
                if (hostingField) hostingField.style.display = 'none';
                if (inputLink) inputLink.required = true;
                if (inputText) inputText.required = false;
            } else if (currentResourceType === 'text') {
                if (linkField) linkField.style.display = 'none';
                if (textField) textField.style.display = 'block';
                if (filesField) filesField.style.display = 'none';
                if (hostingField) hostingField.style.display = 'none';
                if (inputLink) inputLink.required = false;
                if (inputText) inputText.required = true;
            } else if (currentResourceType === 'files') {
                if (linkField) linkField.style.display = 'none';
                if (textField) textField.style.display = 'none';
                if (filesField) filesField.style.display = 'block';
                if (hostingField) hostingField.style.display = 'none';
                if (inputLink) inputLink.required = false;
                if (inputText) inputText.required = false;
                updateNoticeCodes();
            } else if (currentResourceType === 'hosting') {
                if (linkField) linkField.style.display = 'none';
                if (textField) textField.style.display = 'none';
                if (filesField) filesField.style.display = 'none';
                if (hostingField) hostingField.style.display = 'block';
                if (inputLink) inputLink.required = false;
                if (inputText) inputText.required = false;
                updateNoticeCodes();
                // Set default template if empty
                const hostingCodeInput = document.getElementById('inputHostingCode');
                if (hostingCodeInput && !hostingCodeInput.value.trim()) {
                    applySiteTemplate('starter');
                }
            }
        });
    });

    const codeInput = document.getElementById('inputCode');
    if (codeInput) {
        codeInput.addEventListener('input', updateNoticeCodes);
    }

    // Expiry Preset Chips
    const expiryChips = document.querySelectorAll('.expiry-chip');
    const customDateContainer = document.getElementById('customExpiryContainer');
    expiryChips.forEach(chip => {
        chip.addEventListener('click', () => {
            expiryChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentExpiryPreset = chip.getAttribute('data-preset');
            
            if (currentExpiryPreset === 'custom') {
                customDateContainer.style.display = 'block';
            } else {
                customDateContainer.style.display = 'none';
            }
        });
    });

    // Form Submission
    const resourceForm = document.getElementById('resourceForm');
    if (resourceForm) {
        resourceForm.addEventListener('submit', handleResourceSubmit);
    }
}

function updateNoticeCodes() {
    const code = document.getElementById('inputCode')?.value.trim().toUpperCase() || '...';
    const filesNoticeElem = document.getElementById('filesNoticeCode');
    if (filesNoticeElem) {
        filesNoticeElem.innerText = code;
    }
    const hostingNoticeElem = document.getElementById('hostingNoticeCode');
    if (hostingNoticeElem) {
        hostingNoticeElem.innerText = code;
    }
}

/**
 * Generate a 6-letter English code on the client
 */
function generateNewCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    let code = '';
    for (let i = 0; i < 6; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const codeInput = document.getElementById('inputCode');
    if (codeInput) {
        codeInput.value = code;
        updateNoticeCodes();
    }
}

/**
 * Quick Template Presets for Micro-hosting Create Form
 */
const SITE_TEMPLATES = {
    starter: `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Micro Site</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
        .card { max-width: 600px; width: 100%; background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.3); text-align: center; }
        h1 { color: #38bdf8; margin-bottom: 0.5rem; }
        p { color: #94a3b8; line-height: 1.6; }
        .badge { background: #0284c7; color: white; padding: 0.3rem 0.8rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-bottom: 1rem; }
        .info { background: #0f172a; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; font-family: monospace; color: #38bdf8; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">🌐 TikTak Micro-Site</span>
        <h1>Welcome to My Micro-site!</h1>
        <p>This page is hosted dynamically on the server. You can edit this file, add HTML, CSS, JavaScript, and execute PHP.</p>
        <div class="info">
            Server Time: <?= date('Y-m-d H:i:s') ?><br>
            PHP Version: <?= phpversion() ?>
        </div>
        <p style="font-size: 0.9rem;">Edit this file in your Professor Dashboard.</p>
    </div>
</body>
</html>`,

    quiz: `<?php
\$submitted = \$_SERVER['REQUEST_METHOD'] === 'POST';
\$score = 0;
\$feedback = '';

if (\$submitted) {
    \$q1 = \$_POST['q1'] ?? '';
    \$q2 = \$_POST['q2'] ?? '';
    
    if (strtolower(trim(\$q1)) === 'php') \$score += 50;
    if (\$q2 === 'hypertext') \$score += 50;
    
    \$feedback = "You scored {\$score}%!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Quick Quiz</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; padding: 2rem; max-width: 600px; margin: 0 auto; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .question { margin-bottom: 1.5rem; }
        label { display: block; margin: 0.4rem 0; cursor: pointer; }
        input[type="text"] { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        button { background: #4f46e5; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .score-box { background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; font-weight: 700; margin-bottom: 1.5rem; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h1>📝 Classroom Quiz</h1>
        
        <?php if (\$submitted): ?>
            <div class="score-box"><?= \$feedback ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="question">
                <p><strong>1. What server-side language powers this micro-site?</strong></p>
                <input type="text" name="q1" placeholder="Type language name..." required value="<?= htmlspecialchars(\$_POST['q1'] ?? '') ?>">
            </div>

            <div class="question">
                <p><strong>2. What does the "H" in HTML stand for?</strong></p>
                <label><input type="radio" name="q2" value="hypertext" required> Hypertext</label>
                <label><input type="radio" name="q2" value="hybrid"> Hybrid</label>
                <label><input type="radio" name="q2" value="hosting"> Hosting</label>
            </div>

            <button type="submit">Submit Answers</button>
        </form>
    </div>
</body>
</html>`,

    canvas: `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Canvas Graphics</title>
    <style>
        body { margin: 0; overflow: hidden; background: #050811; color: white; font-family: system-ui, sans-serif; }
        #canvas { display: block; width: 100vw; height: 100vh; }
        .hud { position: absolute; top: 1rem; left: 1rem; background: rgba(0,0,0,0.6); padding: 0.75rem 1.25rem; border-radius: 10px; pointer-events: none; }
    </style>
</head>
<body>
    <div class="hud">
        <h3 style="margin: 0 0 0.25rem 0;">✨ Interactive Particle Matrix</h3>
        <p style="margin: 0; font-size: 0.85rem; color: #94a3b8;">Move your mouse or touch screen</p>
    </div>
    <canvas id="canvas"></canvas>

    <script>
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });

        const particles = [];
        const count = 75;
        let mouse = { x: width / 2, y: height / 2 };

        window.addEventListener('mousemove', (e) => { mouse.x = e.clientX; mouse.y = e.clientY; });

        for (let i = 0; i < count; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 1.5,
                vy: (Math.random() - 0.5) * 1.5,
                radius: Math.random() * 3 + 1.5,
                color: \`hsl(\${Math.random() * 60 + 190}, 90%, 65%)\`
            });
        }

        function loop() {
            ctx.fillStyle = 'rgba(5, 8, 17, 0.2)';
            ctx.fillRect(0, 0, width, height);

            particles.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0 || p.x > width) p.vx *= -1;
                if (p.y < 0 || p.y > height) p.vy *= -1;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.fillStyle = p.color;
                ctx.fill();

                const dx = mouse.x - p.x;
                const dy = mouse.y - p.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 120) {
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.strokeStyle = \`rgba(56, 189, 248, \${1 - dist / 120})\`;
                    ctx.stroke();
                }
            });

            requestAnimationFrame(loop);
        }
        loop();
    </script>
</body>
</html>`,

    api: `<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

\$response = [
    'status' => 'success',
    'timestamp' => date('c'),
    'server_time' => time(),
    'client_ip' => \$_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => \$_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'message' => 'Hello from TikTak Microhosting JSON API!',
    'items' => [
        ['id' => 1, 'name' => 'Data Stream Alpha', 'active' => true],
        ['id' => 2, 'name' => 'Data Stream Beta', 'active' => true],
        ['id' => 3, 'name' => 'Data Stream Gamma', 'active' => false]
    ]
];

echo json_encode(\$response, JSON_PRETTY_PRINT);`,

    blank: `<?php
/**
 * TikTak Micro-Site
 */

echo "Hello from TikTak Micro-site!";
`
};

function applySiteTemplate(key) {
    const input = document.getElementById('inputHostingCode');
    if (input && SITE_TEMPLATES[key]) {
        input.value = SITE_TEMPLATES[key];
        showToast(`Template "${key}" applied!`, 'info');
    }
}

/**
 * Open Modal Helper
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        if (modalId === 'createModal') {
            generateNewCode();
        }
    }
}

/**
 * Close Modal Helper
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('active');
    }
});

/**
 * Handle Resource Creation via AJAX
 */
async function handleResourceSubmit(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('btnSubmitResource');
    submitBtn.disabled = true;
    submitBtn.innerText = 'Creating...';

    let contentText = '';
    if (currentResourceType === 'text') {
        contentText = document.getElementById('inputText')?.value || '';
    } else if (currentResourceType === 'hosting') {
        contentText = document.getElementById('inputHostingCode')?.value || '';
    }

    const formData = {
        action: 'create',
        code: document.getElementById('inputCode').value.trim().toUpperCase(),
        title: document.getElementById('inputTitle').value.trim(),
        type: currentResourceType,
        content_link: document.getElementById('inputLink') ? document.getElementById('inputLink').value.trim() : '',
        content_text: contentText,
        expiry_preset: currentExpiryPreset,
        custom_expiry: document.getElementById('inputCustomExpiry') ? document.getElementById('inputCustomExpiry').value : ''
    };

    try {
        const res = await fetch('/api/resources.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const data = await res.json();

        if (data.success) {
            showToast('Resource created successfully!', 'success');
            closeModal('createModal');
            setTimeout(() => window.location.reload(), 600);
        } else {
            showToast(data.error || 'Failed to create resource', 'error');
            submitBtn.disabled = false;
            submitBtn.innerText = 'Create Resource';
        }
    } catch (err) {
        showToast('Network error while saving resource', 'error');
        submitBtn.disabled = false;
        submitBtn.innerText = 'Create Resource';
    }
}

/**
 * Delete Resource
 */
async function deleteResource(id, code) {
    if (!confirm(`Are you sure you want to delete code "${code}"? All associated files, micro-sites, and materials will be deleted.`)) {
        return;
    }

    try {
        const res = await fetch('/api/resources.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });
        const data = await res.json();

        if (data.success) {
            showToast(`Code ${code} deleted`, 'success');
            const row = document.getElementById(`resource-row-${id}`);
            if (row) row.remove();
        } else {
            showToast(data.error || 'Failed to delete', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

/**
 * Open Edit Modal
 */
function openEditModal(resource) {
    document.getElementById('editId').value = resource.id;
    document.getElementById('editCode').value = resource.code;
    document.getElementById('editTitle').value = resource.title;
    document.getElementById('editType').value = resource.type;

    const linkContainer = document.getElementById('editFieldLink');
    const textContainer = document.getElementById('editFieldText');
    const filesContainer = document.getElementById('editFieldFiles');
    const hostingContainer = document.getElementById('editFieldHosting');
    const consoleLink = document.getElementById('editConsoleLink');
    const btnEditOpenManager = document.getElementById('btnEditOpenManager');

    if (resource.type === 'redirect') {
        if (linkContainer) linkContainer.style.display = 'block';
        if (textContainer) textContainer.style.display = 'none';
        if (filesContainer) filesContainer.style.display = 'none';
        if (hostingContainer) hostingContainer.style.display = 'none';
        document.getElementById('editInputLink').value = resource.content_link || '';
    } else if (resource.type === 'text') {
        if (linkContainer) linkContainer.style.display = 'none';
        if (textContainer) textContainer.style.display = 'block';
        if (filesContainer) filesContainer.style.display = 'none';
        if (hostingContainer) hostingContainer.style.display = 'none';
        document.getElementById('editInputText').value = resource.content_text || '';
    } else if (resource.type === 'hosting') {
        if (linkContainer) linkContainer.style.display = 'none';
        if (textContainer) textContainer.style.display = 'none';
        if (filesContainer) filesContainer.style.display = 'none';
        if (hostingContainer) hostingContainer.style.display = 'block';
        if (btnEditOpenManager) {
            btnEditOpenManager.onclick = () => {
                closeModal('editModal');
                openSiteManager(resource.code, resource.title);
            };
        }
    } else {
        // files
        if (linkContainer) linkContainer.style.display = 'none';
        if (textContainer) textContainer.style.display = 'none';
        if (filesContainer) filesContainer.style.display = 'block';
        if (hostingContainer) hostingContainer.style.display = 'none';
        if (consoleLink) consoleLink.href = `/code=${resource.code}`;
    }

    openModal('editModal');
}

/**
 * Save Edit
 */
async function handleEditSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('editId').value;
    const title = document.getElementById('editTitle').value;
    const type = document.getElementById('editType').value;
    const contentLink = document.getElementById('editInputLink').value;
    const contentText = document.getElementById('editInputText').value;

    try {
        const res = await fetch('/api/resources.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update',
                id: id,
                title: title,
                content_link: contentLink,
                content_text: contentText
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Resource updated!', 'success');
            closeModal('editModal');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.error || 'Failed to update', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

/**
 * QR Code Generator for Classroom Projectors
 */
function showQrCode(code, fullUrl) {
    document.getElementById('qrModalCode').innerText = code;
    document.getElementById('qrModalUrl').innerText = fullUrl;
    
    const qrImg = document.getElementById('qrImage');
    const encodedUrl = encodeURIComponent(fullUrl);
    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodedUrl}&margin=10`;
    
    openModal('qrModal');
}

/**
 * Filter and search through resources
 */
function initFilterSearch() {
    const filterInput = document.getElementById('filterInput');
    if (!filterInput) return;

    filterInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.resource-item-row');
        
        rows.forEach(row => {
            const code = row.getAttribute('data-code') || '';
            const title = row.getAttribute('data-title') || '';
            if (code.toLowerCase().includes(query) || title.toLowerCase().includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

/**
 * Extend expired resource validity by N hours (+2 hours)
 */
async function extendExpiry(id, hours = 2) {
    try {
        const res = await fetch('/api/resources.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'extend', id: id, hours: hours })
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Code validity extended by +${hours} hours!`, 'success');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.error || 'Failed to extend code', 'error');
        }
    } catch (err) {
        showToast('Network error while extending expiry', 'error');
    }
}

/**
 * Handle Add Teacher submission via AJAX
 */
async function handleAddTeacherSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btnAddTeacherSubmit');
    const username = document.getElementById('newTeacherUsername').value.trim();
    const name = document.getElementById('newTeacherName').value.trim();
    const password = document.getElementById('newTeacherPassword').value.trim();

    if (!username || !password) {
        showToast('Please provide both username and password', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerText = 'Creating...';

    try {
        const res = await fetch('/api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_teacher',
                username: username,
                name: name,
                password: password
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || 'Teacher account created!', 'success');
            document.getElementById('addTeacherForm').reset();
            closeModal('addTeacherModal');
        } else {
            showToast(data.error || 'Failed to create teacher account', 'error');
        }
    } catch (err) {
        showToast('Network error while creating teacher account', 'error');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Create Account';
    }
}


/**
 * ============================================================================
 * Micro-hosting Site Manager Logic
 * ============================================================================
 */

function initSiteManagerGlobalEvents() {
    // Keyboard shortcut: Ctrl+S to save in code editor
    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            const modal = document.getElementById('siteManagerModal');
            if (modal && modal.classList.contains('active')) {
                e.preventDefault();
                saveCurrentSiteFile();
            }
        }
    });

    // Track textarea dirty changes
    const textarea = document.getElementById('siteCodeTextarea');
    if (textarea) {
        textarea.addEventListener('input', () => {
            setFileDirty(true);
        });
    }

    // Dropzone drag & drop setup
    const dropzone = document.getElementById('siteDropzone');
    if (dropzone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
            });
        });
        dropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                handleSiteFilesUpload(files);
            }
        });
    }
}

/**
 * Open Site Manager Modal
 */
async function openSiteManager(code, title) {
    currentSiteCode = code;
    currentSiteTitle = title || `Micro Site ${code}`;
    currentSiteFile = 'index.php';
    isSiteFileDirty = false;

    document.getElementById('siteModalCode').innerText = code;
    document.getElementById('siteModalTitle').innerText = currentSiteTitle;
    
    const liveUrl = `/sites/${code}/`;
    const openLink = document.getElementById('siteModalOpenLink');
    if (openLink) openLink.href = liveUrl;

    const previewDirectLink = document.getElementById('sitePreviewDirectLink');
    if (previewDirectLink) previewDirectLink.href = liveUrl;

    openModal('siteManagerModal');
    switchSiteView('editor');

    await loadSiteData(code);
}

/**
 * Load Site Summary and Initial File Content
 */
async function loadSiteData(code) {
    try {
        const res = await fetch(`/api/site.php?action=list&code=${code}`);
        const data = await res.json();
        if (data.success) {
            currentSiteSummary = data.summary;
            renderSiteOverview(data.summary);
            
            // Choose file to open: index.php, index.html, or first file
            let fileToOpen = 'index.php';
            if (data.summary.has_index && data.summary.main_file) {
                fileToOpen = data.summary.main_file;
            } else if (data.summary.files.length > 0) {
                const firstEditable = data.summary.files.find(f => !f.is_dir && f.is_editable);
                if (firstEditable) fileToOpen = firstEditable.path;
            }

            await loadSiteFile(fileToOpen);
        } else {
            showToast(data.error || 'Failed to load site information', 'error');
        }
    } catch (err) {
        showToast('Network error while loading micro-site', 'error');
    }
}

/**
 * Render File Trees and Tables
 */
function renderSiteOverview(summary) {
    // Update Meta Counters
    const countBadge = document.getElementById('siteFilesCount');
    if (countBadge) countBadge.innerText = summary.total_files;

    const sizeBadge = document.getElementById('siteTotalSizeFormatted');
    if (sizeBadge) sizeBadge.innerText = `${summary.total_files} files • ${summary.total_size_formatted}`;

    // Render Left Sidebar Tree
    const sidebarTree = document.getElementById('siteFileListSidebar');
    if (sidebarTree) {
        if (!summary.files || summary.files.length === 0) {
            sidebarTree.innerHTML = `<div style="padding: 1rem; color: var(--text-dim); font-size: 0.82rem; text-align: center;">No files found. Click + File to create one.</div>`;
        } else {
            sidebarTree.innerHTML = summary.files.map(f => {
                const isActive = f.path === currentSiteFile;
                const activeClass = isActive ? 'active' : '';
                return `
                    <div class="site-tree-item ${activeClass}" onclick="${f.is_dir ? '' : `loadSiteFile('${f.path}')`}" title="${f.path}">
                        <span class="site-item-icon">${f.icon}</span>
                        <span class="site-item-name">${f.path}</span>
                        ${!f.is_dir ? `<span class="site-item-size">${f.size_formatted}</span>` : `<span class="site-item-size">DIR</span>`}
                    </div>
                `;
            }).join('');
        }
    }

    // Render Full Files Table
    const tableBody = document.getElementById('siteFilesTableBody');
    if (tableBody) {
        if (!summary.files || summary.files.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-dim); padding: 2rem;">No files in directory yet.</td></tr>`;
        } else {
            tableBody.innerHTML = summary.files.map(f => {
                return `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                                <span>${f.icon}</span>
                                <span>${f.path}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge ${f.is_dir ? 'badge-secondary' : 'badge-primary'}">${f.tag}</span>
                        </td>
                        <td style="font-family: var(--font-mono); font-size: 0.85rem;">
                            ${f.size_formatted}
                        </td>
                        <td style="font-size: 0.82rem; color: var(--text-muted);">
                            ${f.mtime_formatted}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.35rem;">
                                ${f.is_editable ? `
                                    <button class="btn btn-secondary btn-sm" onclick="switchSiteView('editor'); loadSiteFile('${f.path}');" title="Edit file">
                                        Edit
                                    </button>
                                ` : ''}
                                ${!f.is_dir ? `
                                    <a href="/sites/${currentSiteCode}/${f.path}" target="_blank" class="btn btn-ghost btn-sm" title="View raw file">
                                        View ↗
                                    </a>
                                ` : ''}
                                <button class="btn btn-danger btn-sm" onclick="deleteSiteFile('${f.path}')" title="Delete file">
                                    ✕
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    }
}

/**
 * Load Content of a Specific File into Editor
 */
async function loadSiteFile(filename) {
    if (isSiteFileDirty) {
        if (!confirm(`You have unsaved changes in "${currentSiteFile}". Discard changes and switch?`)) {
            return;
        }
    }

    currentSiteFile = filename;
    setFileDirty(false);

    document.getElementById('siteActiveFileName').innerText = filename;
    
    // Update active highlight in sidebar
    document.querySelectorAll('.site-tree-item').forEach(el => {
        el.classList.remove('active');
        if (el.getAttribute('title') === filename) {
            el.classList.add('active');
        }
    });

    const textarea = document.getElementById('siteCodeTextarea');
    textarea.value = 'Loading...';
    textarea.disabled = true;

    try {
        const res = await fetch(`/api/site.php?action=read&code=${currentSiteCode}&file=${encodeURIComponent(filename)}`);
        const data = await res.json();
        if (data.success) {
            textarea.value = data.content;
            textarea.disabled = false;
        } else {
            textarea.value = `// Error: ${data.error || 'Could not load file'}`;
            textarea.disabled = true;
            showToast(data.error || 'Failed to read file', 'error');
        }
    } catch (err) {
        textarea.value = '// Network error loading file';
        textarea.disabled = true;
        showToast('Network error reading file', 'error');
    }
}

/**
 * Save Current File Content
 */
async function saveCurrentSiteFile() {
    const textarea = document.getElementById('siteCodeTextarea');
    const saveBtn = document.getElementById('btnSaveSiteFile');
    const content = textarea.value;

    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';

    try {
        const res = await fetch('/api/site.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save',
                code: currentSiteCode,
                file: currentSiteFile,
                content: content
            })
        });
        const data = await res.json();

        if (data.success) {
            setFileDirty(false);
            showToast(`Saved "${currentSiteFile}"!`, 'success');
            if (data.summary) {
                currentSiteSummary = data.summary;
                renderSiteOverview(data.summary);
            }
            // Auto reload preview frame if active
            reloadSitePreview();
        } else {
            showToast(data.error || 'Failed to save file', 'error');
        }
    } catch (err) {
        showToast('Network error while saving file', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = `💾 Save File <span class="kbd-shortcut">Ctrl+S</span>`;
    }
}

function setFileDirty(dirty) {
    isSiteFileDirty = dirty;
    const dot = document.getElementById('siteUnsavedIndicator');
    if (dot) {
        dot.style.display = dirty ? 'inline-block' : 'none';
    }
}

/**
 * Switch View Tabs in Site Manager
 */
function switchSiteView(view) {
    const tabBtns = document.querySelectorAll('.site-tab-btn');
    tabBtns.forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-view') === view);
    });

    const panels = {
        editor: document.getElementById('siteViewEditor'),
        files: document.getElementById('siteViewFiles'),
        preview: document.getElementById('siteViewPreview')
    };

    Object.keys(panels).forEach(k => {
        if (panels[k]) {
            panels[k].style.display = (k === view) ? 'block' : 'none';
        }
    });

    if (view === 'preview') {
        reloadSitePreview();
    }
}

/**
 * Create New File Prompt
 */
async function promptNewFile() {
    const filename = prompt('Enter new filename (e.g. style.css, about.php, app.js):', 'style.css');
    if (!filename || !filename.trim()) return;

    try {
        const res = await fetch('/api/site.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_file',
                code: currentSiteCode,
                file: filename.trim(),
                is_dir: false,
                content: ''
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Created file "${filename}"!`, 'success');
            if (data.summary) {
                currentSiteSummary = data.summary;
                renderSiteOverview(data.summary);
            }
            switchSiteView('editor');
            await loadSiteFile(filename.trim());
        } else {
            showToast(data.error || 'Failed to create file', 'error');
        }
    } catch (err) {
        showToast('Network error while creating file', 'error');
    }
}

/**
 * Create New Folder Prompt
 */
async function promptNewFolder() {
    const folderName = prompt('Enter new folder name (e.g. css, js, assets):', 'assets');
    if (!folderName || !folderName.trim()) return;

    try {
        const res = await fetch('/api/site.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_file',
                code: currentSiteCode,
                file: folderName.trim(),
                is_dir: true
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Created folder "${folderName}"!`, 'success');
            if (data.summary) {
                currentSiteSummary = data.summary;
                renderSiteOverview(data.summary);
            }
        } else {
            showToast(data.error || 'Failed to create folder', 'error');
        }
    } catch (err) {
        showToast('Network error while creating folder', 'error');
    }
}

/**
 * Delete File
 */
async function deleteSiteFile(filename) {
    if (!confirm(`Are you sure you want to delete "${filename}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const res = await fetch('/api/site.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_file',
                code: currentSiteCode,
                file: filename
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Deleted "${filename}"`, 'success');
            if (data.summary) {
                currentSiteSummary = data.summary;
                renderSiteOverview(data.summary);
            }
            if (currentSiteFile === filename) {
                loadSiteData(currentSiteCode);
            }
        } else {
            showToast(data.error || 'Failed to delete file', 'error');
        }
    } catch (err) {
        showToast('Network error deleting file', 'error');
    }
}

/**
 * Handle File / ZIP Uploads
 */
async function handleSiteFilesUpload(fileList) {
    if (!fileList || fileList.length === 0) return;

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('code', currentSiteCode);
    formData.append('extract_zip', '1');

    for (let i = 0; i < fileList.length; i++) {
        formData.append('files[]', fileList[i]);
    }

    showToast(`Uploading ${fileList.length} file(s)...`, 'info');

    try {
        const res = await fetch('/api/site.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showToast(`Uploaded ${data.uploaded_count} item(s) successfully!`, 'success');
            if (data.summary) {
                currentSiteSummary = data.summary;
                renderSiteOverview(data.summary);
            }
            reloadSitePreview();
        } else {
            showToast(data.error || 'Upload failed', 'error');
        }
    } catch (err) {
        showToast('Network error during file upload', 'error');
    } finally {
        const fileInput = document.getElementById('siteFileInput');
        if (fileInput) fileInput.value = '';
    }
}

/**
 * Export Site as ZIP Archive
 */
function downloadSiteZip() {
    if (!currentSiteCode) return;
    window.location.href = `/api/site.php?action=download_zip&code=${currentSiteCode}`;
}

/**
 * Live Preview Controls
 */
function reloadSitePreview() {
    const iframe = document.getElementById('sitePreviewIframe');
    if (iframe && currentSiteCode) {
        iframe.src = `/sites/${currentSiteCode}/?_t=${Date.now()}`;
    }
}

function setPreviewDevice(device) {
    const btns = document.querySelectorAll('.device-btn');
    btns.forEach(b => b.classList.toggle('active', b.getAttribute('data-device') === device));

    const wrapper = document.getElementById('previewFrameWrapper');
    if (!wrapper) return;

    wrapper.classList.remove('device-full', 'device-tablet', 'device-mobile');
    if (device === 'tablet') {
        wrapper.classList.add('device-tablet');
    } else if (device === 'mobile') {
        wrapper.classList.add('device-mobile');
    } else {
        wrapper.classList.add('device-full');
    }
}
