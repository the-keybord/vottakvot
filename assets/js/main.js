/**
 * TikTak - Main Frontend Scripts
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSearchEngine();
});

/**
 * Dark / Light Theme Manager
 */
function initTheme() {
    const savedTheme = localStorage.getItem('tiktak_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    const toggleBtn = document.getElementById('themeToggle');
    if (toggleBtn) {
        updateThemeToggleIcon(toggleBtn, savedTheme);
        toggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('tiktak_theme', newTheme);
            updateThemeToggleIcon(toggleBtn, newTheme);
        });
    }
}

function updateThemeToggleIcon(btn, theme) {
    btn.innerHTML = theme === 'light' 
        ? `<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>`
        : `<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>`;
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
