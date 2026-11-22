/**
 * TikTak - Main Frontend Scripts
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSearchEngine();
});

/**
 * Light Theme Enforcement Manager (Anti-Browser-Dark-Mode)
 */
function initTheme() {
    // Explicitly lock document element to light theme
    document.documentElement.setAttribute('data-theme', 'light');

    // Overwrite any stored dark theme preference in localStorage
    try {
        localStorage.setItem('tiktak_theme', 'light');
    } catch (e) {}

    // Hide any legacy toggle element if present
    const toggleBtn = document.getElementById('themeToggle');
    if (toggleBtn) {
        toggleBtn.style.display = 'none';
    }
}

/**
 * Search Engine Code Input & Auto-Submit
 */
function initSearchEngine() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('codeInput');

    if (!searchInput) return;

    // Focus immediately on load
    searchInput.focus();

    // Force uppercase and clean non-alphanumeric characters
    searchInput.addEventListener('input', (e) => {
        let val = e.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        if (val.length > 10) val = val.substring(0, 10);
        e.target.value = val;

        // Auto-submit when user reaches 6 letters for lightning-fast access
        if (val.length === 6 && e.inputType !== 'deleteContentBackward') {
            searchForm.submit();
        }
    });

    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            const code = searchInput.value.trim().toUpperCase();
            if (!code) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }
}

/**
 * Universal Toast Notification Helper
 */
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    const icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ');
    toast.innerHTML = `<span style="font-weight: bold; color: var(--accent-${type === 'error' ? 'rose' : (type === 'success' ? 'emerald' : 'primary')})">${icon}</span> <span>${message}</span>`;
    
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Copy Text to Clipboard Helper
 */
function copyToClipboard(text, message = 'Copied to clipboard!') {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => showToast(message, 'success'));
    } else {
        const temp = document.createElement('textarea');
        temp.value = text;
        temp.style.position = 'fixed';
        temp.style.left = '-9999px';
        document.body.appendChild(temp);
        temp.select();
        try {
            document.execCommand('copy');
            showToast(message, 'success');
        } catch (err) {
            showToast('Failed to copy', 'error');
        }
        document.body.removeChild(temp);
    }
}
