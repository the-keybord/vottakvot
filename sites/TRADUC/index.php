<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>the-keybord/traduceri - GitHub Repository Explorer</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Marked for Markdown rendering -->
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <!-- Highlight.js for syntax highlighting -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              500: '#0284c7',
              600: '#0369a1',
              700: '#075985',
            }
          }
        }
      }
    }
  </script>
  <style>
    /* Custom scrollbar styling */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: transparent;
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(156, 163, 175, 0.4);
      border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(156, 163, 175, 0.7);
    }
    .markdown-body pre {
      background-color: #0f172a !important;
      border-radius: 0.5rem;
      padding: 1rem;
      overflow-x: auto;
    }
    .markdown-body code {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 0.875rem;
    }
    .markdown-body h1, .markdown-body h2, .markdown-body h3 {
      font-weight: 700;
      margin-top: 1.25rem;
      margin-bottom: 0.75rem;
    }
    .markdown-body h1 { font-size: 1.75rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2); padding-bottom: 0.3em; }
    .markdown-body h2 { font-size: 1.35rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2); padding-bottom: 0.3em; }
    .markdown-body p { margin-bottom: 0.85rem; line-height: 1.6; }
    .markdown-body ul, .markdown-body ol { margin-left: 1.5rem; margin-bottom: 0.85rem; list-style-type: disc; }
    .markdown-body a { color: #38bdf8; text-decoration: underline; }
  </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-full flex flex-col font-sans transition-colors duration-200">

  <header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20 shrink-0">
          <i data-lucide="languages" class="w-5 h-5"></i>
        </div>
        <div class="truncate">
          <div class="flex items-center gap-2">
            <a href="https://github.com/the-keybord" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-sky-500 transition-colors">the-keybord</a>
            <span class="text-xs text-slate-400">/</span>
            <span id="repoNameDisplay" class="font-bold text-slate-900 dark:text-white truncate">traduceri</span>
            <span class="text-[10px] font-semibold tracking-wide uppercase px-2 py-0.5 rounded-full bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800">Public</span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Romanian translations & localization repository</p>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <!-- Direct Repo Link -->
        <a href="https://github.com/the-keybord/traduceri" target="_blank" rel="noopener noreferrer" 
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300 transition-all shadow-sm">
          <i data-lucide="github" class="w-4 h-4"></i>
          <span class="hidden sm:inline">GitHub</span>
        </a>

        <!-- Personal Token Modal Trigger -->
        <button id="openTokenModalBtn" title="Set GitHub API Token (Bypasses Rate Limit)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300 transition-all shadow-sm">
          <i data-lucide="key" class="w-4 h-4"></i>
          <span class="hidden md:inline">API Token</span>
        </button>

        <!-- Dark Mode Toggle -->
        <button id="themeToggleBtn" aria-label="Toggle theme" 
                class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors">
          <i data-lucide="sun" id="sunIcon" class="w-4 h-4 hidden"></i>
          <i data-lucide="moon" id="moonIcon" class="w-4 h-4"></i>
        </button>
      </div>
    </div>
  </header>

  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col gap-6">
    <!-- Notice Banner for Rate Limiting / Demo mode -->
    <div id="noticeBanner" class="hidden p-4 rounded-xl border transition-all duration-300 text-sm flex items-start justify-between gap-3">
      <div class="flex items-start gap-3">
        <i id="noticeIcon" data-lucide="alert-triangle" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <div>
          <h4 id="noticeTitle" class="font-semibold">Notice</h4>
          <p id="noticeDesc" class="text-xs opacity-90 mt-0.5"></p>
        </div>
      </div>
      <button id="noticeCloseBtn" class="text-xs font-semibold underline shrink-0 hover:opacity-75">Dismiss</button>
    </div>

    <!-- Explorer Controls & Quick Info -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
      <!-- Search Input -->
      <div class="relative flex-1">
        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="text" id="searchInput" placeholder="Filter files in current view..." 
               class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 dark:text-white placeholder-slate-400 transition-all">
      </div>

      <div class="flex items-center gap-2">
        <!-- Branch selection indicator -->
        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300">
          <i data-lucide="git-branch" class="w-3.5 h-3.5 text-sky-500"></i>
          <span id="currentBranchDisplay">main</span>
        </div>

        <!-- Reload directory button -->
        <button id="refreshBtn" title="Refresh files" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-colors">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        </button>
      </div>
    </div>

    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 px-2 text-sm text-slate-600 dark:text-slate-400 flex-wrap overflow-x-auto py-1" id="breadcrumbsNav">
      <!-- Dynamic breadcrumbs inserted here -->
    </nav>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
      <div class="px-5 py-3.5 bg-slate-100/60 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
        <span class="flex items-center gap-2">
          <i data-lucide="folder-tree" class="w-4 h-4 text-sky-500"></i>
          Name
        </span>
        <div class="flex items-center gap-6">
          <span class="hidden sm:inline">Size</span>
          <span>Actions</span>
        </div>
      </div>

      <!-- File Table / List -->
      <div id="fileListContainer" class="divide-y divide-slate-100 dark:divide-slate-800 min-h-[220px] relative">
        <!-- Loader Skeleton / Items -->
      </div>
    </div>

    <div id="fileViewerContainer" class="hidden bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
      <div class="px-5 py-3.5 bg-slate-100/70 dark:bg-slate-800/60 border-b border-slate-200/80 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <i data-lucide="file-code" class="w-4 h-4 text-sky-500"></i>
          <span id="previewFileName" class="text-sm font-semibold text-slate-800 dark:text-slate-200"></span>
          <span id="previewFileSize" class="text-xs text-slate-400 ml-2"></span>
        </div>
        <div class="flex items-center gap-2">
          <a id="previewRawBtn" href="#" target="_blank" class="px-2.5 py-1 text-xs rounded-lg font-medium border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 inline-flex items-center gap-1.5 transition-colors">
            <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Raw
          </a>
          <button id="copyContentBtn" class="px-2.5 py-1 text-xs rounded-lg font-medium border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 inline-flex items-center gap-1.5 transition-colors">
            <i data-lucide="copy" class="w-3.5 h-3.5"></i> Copy
          </button>
          <button id="closePreviewBtn" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
      <!-- View Content Body -->
      <div id="previewContentBody" class="p-4 sm:p-6 overflow-x-auto text-sm leading-relaxed max-h-[700px] overflow-y-auto">
        <!-- Rendered Code, Markdown or Image -->
      </div>
    </div>

    <div id="readmeCard" class="hidden bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
      <div class="px-5 py-3.5 bg-slate-100/60 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
        <i data-lucide="book-open" class="w-4 h-4 text-sky-500"></i>
        <span>README.md</span>
      </div>
      <div id="readmeContent" class="markdown-body p-6 text-slate-800 dark:text-slate-200 text-sm">
        <!-- Rendered README Markdown -->
      </div>
    </div>
  </main>

  <div id="tokenModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 max-w-md w-full rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-2xl relative">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-lg bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400">
            <i data-lucide="key-round" class="w-5 h-5"></i>
          </div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">GitHub API Token</h3>
        </div>
        <button id="closeTokenModalBtn" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">
        GitHub limits unauthenticated API requests to 60/hour. Adding a Personal Access Token (classic or fine-grained with read-only permissions) increases this limit to 5,000/hour. Your token stays in your browser's local memory only.
      </p>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Personal Access Token</label>
          <input type="password" id="tokenInput" placeholder="ghp_xxxxxxxxxxxx" class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 text-slate-800 dark:text-white">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button id="clearTokenBtn" class="px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition-colors">Clear</button>
          <button id="saveTokenBtn" class="px-4 py-2 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-500 rounded-xl transition-colors shadow-sm">Save Token</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Notification -->
  <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 pointer-events-none transition-all duration-300 px-4 py-2.5 rounded-xl text-xs font-semibold shadow-lg border flex items-center gap-2"></div>

  <script>
    /* State Management */
    const STATE = {
      owner: 'the-keybord',
      repo: 'traduceri',
      currentBranch: 'main',
      currentPath: '',
      items: [],
      filteredItems: [],
      token: localStorage.getItem('gh_token') || '',
      theme: localStorage.getItem('theme') || 'dark',
      activeFile: null
    };

    /* Fallback mock data in case GitHub Rate limit is reached before token entry */
    const FALLBACK_DIRECTORY = {
      '': [
        { name: 'games', type: 'dir', path: 'games', size: 0, html_url: 'https://github.com/the-keybord/traduceri/tree/main/games' },
        { name: 'apps', type: 'dir', path: 'apps', size: 0, html_url: 'https://github.com/the-keybord/traduceri/tree/main/apps' },
        { name: 'docs', type: 'dir', path: 'docs', size: 0, html_url: 'https://github.com/the-keybord/traduceri/tree/main/docs' },
        { name: 'README.md', type: 'file', path: 'README.md', size: 1420, download_url: null, html_url: 'https://github.com/the-keybord/traduceri/blob/main/README.md' },
        { name: 'LICENSE', type: 'file', path: 'LICENSE', size: 1064, download_url: null, html_url: 'https://github.com/the-keybord/traduceri/blob/main/LICENSE' }
      ],
      'games': [
        { name: 'romanian_strings.json', type: 'file', path: 'games/romanian_strings.json', size: 2840, download_url: null, html_url: 'https://github.com/the-keybord/traduceri/blob/main/games/romanian_strings.json' },
        { name: 'dialogues.po', type: 'file', path: 'games/dialogues.po', size: 4590, download_url: null, html_url: 'https://github.com/the-keybord/traduceri/blob/main/games/dialogues.po' }
      ]
    };

    function initTheme() {
      if (STATE.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        document.getElementById('moonIcon').classList.add('hidden');
        document.getElementById('sunIcon').classList.remove('hidden');
      } else {
        document.documentElement.classList.remove('dark');
        document.getElementById('sunIcon').classList.add('hidden');
        document.getElementById('moonIcon').classList.remove('hidden');
      }
    }

    function toggleTheme() {
      const isDark = document.documentElement.classList.toggle('dark');
      STATE.theme = isDark ? 'dark' : 'light';
      localStorage.setItem('theme', STATE.theme);
      document.getElementById('sunIcon').classList.toggle('hidden', !isDark);
      document.getElementById('moonIcon').classList.toggle('hidden', isDark);
    }

    function showToast(message, type = 'info') {
      const toast = document.getElementById('toast');
      const colors = {
        info: 'bg-slate-900 text-white border-slate-700 dark:bg-white dark:text-slate-900',
        success: 'bg-emerald-600 text-white border-emerald-500',
        error: 'bg-rose-600 text-white border-rose-500'
      };
      toast.className = `fixed bottom-5 right-5 z-50 transition-all duration-300 px-4 py-2.5 rounded-xl text-xs font-semibold shadow-xl border flex items-center gap-2 ${colors[type] || colors.info}`;
      toast.innerHTML = `<span>${message}</span>`;
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
      toast.style.pointerEvents = 'auto';

      setTimeout(() => {
        toast.style.transform = 'translateY(20px)';
        toast.style.opacity = '0';
        toast.style.pointerEvents = 'none';
      }, 3000);
    }

    function showNotice(title, desc, isWarning = true) {
      const banner = document.getElementById('noticeBanner');
      const noticeTitle = document.getElementById('noticeTitle');
      const noticeDesc = document.getElementById('noticeDesc');
      const noticeIcon = document.getElementById('noticeIcon');

      banner.classList.remove('hidden');
      if (isWarning) {
        banner.className = 'p-4 rounded-xl border transition-all duration-300 text-sm flex items-start justify-between gap-3 bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-400';
      } else {
        banner.className = 'p-4 rounded-xl border transition-all duration-300 text-sm flex items-start justify-between gap-3 bg-sky-500/10 border-sky-500/30 text-sky-700 dark:text-sky-300';
      }
      noticeTitle.textContent = title;
      noticeDesc.textContent = desc;
      lucide.createIcons();
    }

    function formatFileSize(bytes) {
      if (bytes === 0 || !bytes) return '-';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function getFileIcon(item) {
      if (item.type === 'dir') return 'folder';
      const ext = item.name.split('.').pop().toLowerCase();
      if (['md', 'txt'].includes(ext)) return 'file-text';
      if (['json', 'po', 'pot', 'mo', 'xml', 'yaml', 'yml'].includes(ext)) return 'file-code';
      if (['png', 'jpg', 'jpeg', 'svg', 'gif', 'webp'].includes(ext)) return 'file-image';
      if (['zip', 'tar', 'gz', '7z'].includes(ext)) return 'file-archive';
      return 'file';
    }

    async function fetchRepoContents(path = '') {
      renderLoading();
      const apiUrl = `https://api.github.com/repos/${STATE.owner}/${STATE.repo}/contents/${path}`;
      const headers = { 'Accept': 'application/vnd.github.v3+json' };
      if (STATE.token) {
        headers['Authorization'] = `token ${STATE.token}`;
      }

      try {
        const response = await fetch(apiUrl, { headers });

        if (response.status === 403) {
          const rateLimitMsg = "GitHub API rate limit exceeded. Switched to offline demo view. Click 'API Token' to enter a GitHub Token and resume unlimited requests.";
          showNotice("Rate Limited", rateLimitMsg, true);
          useFallbackData(path);
          return;
        }

        if (!response.ok) {
          throw new Error(`Repository error: HTTP ${response.status}`);
        }

        const data = await response.json();
        
        // Hide notice if request succeeded
        document.getElementById('noticeBanner').classList.add('hidden');

        // Check if response is a single file rather than array
        if (!Array.isArray(data)) {
          loadFilePreview(data);
          return;
        }

        // Sort: Directories first, then alphabetical
        data.sort((a, b) => {
          if (a.type === b.type) return a.name.localeCompare(b.name);
          return a.type === 'dir' ? -1 : 1;
        });

        STATE.items = data;
        STATE.filteredItems = data;
        STATE.currentPath = path;
        renderBreadcrumbs();
        renderExplorerList();

        // Check for README.md in current directory
        const readmeFile = data.find(f => f.name.toLowerCase() === 'readme.md');
        if (readmeFile) {
          fetchAndDisplayReadme(readmeFile);
        } else {
          document.getElementById('readmeCard').classList.add('hidden');
        }

      } catch (err) {
        console.error('Fetch error:', err);
        showNotice("Fetch Error", `${err.message}. Showing cached repository structure.`, true);
        useFallbackData(path);
      }
    }

    function useFallbackData(path) {
      STATE.items = FALLBACK_DIRECTORY[path] || [
        { name: 'sample_translation_ro.po', type: 'file', path: 'sample_translation_ro.po', size: 3410 },
        { name: 'README.md', type: 'file', path: 'README.md', size: 1045 }
      ];
      STATE.filteredItems = STATE.items;
      STATE.currentPath = path;
      renderBreadcrumbs();
      renderExplorerList();

      // Show sample README
      renderSampleReadme();
    }

    function renderBreadcrumbs() {
      const nav = document.getElementById('breadcrumbsNav');
      nav.innerHTML = '';

      // Root button
      const rootBtn = document.createElement('button');
      rootBtn.className = 'font-bold text-slate-800 dark:text-slate-200 hover:text-sky-500 dark:hover:text-sky-400 flex items-center gap-1';
      rootBtn.innerHTML = `<i data-lucide="git-branch" class="w-3.5 h-3.5"></i> ${STATE.repo}`;
      rootBtn.onclick = () => navigateTo('');
      nav.appendChild(rootBtn);

      if (!STATE.currentPath) {
        lucide.createIcons();
        return;
      }

      const parts = STATE.currentPath.split('/');
      let accumulated = '';

      parts.forEach((part, index) => {
        accumulated += (index === 0 ? '' : '/') + part;
        const currentTarget = accumulated;

        const sep = document.createElement('span');
        sep.textContent = '/';
        sep.className = 'text-slate-400 select-none';
        nav.appendChild(sep);

        const partBtn = document.createElement('button');
        const isLast = index === parts.length - 1;
        partBtn.className = isLast 
          ? 'font-semibold text-sky-600 dark:text-sky-400' 
          : 'hover:text-sky-500 text-slate-600 dark:text-slate-400';
        partBtn.textContent = part;
        if (!isLast) {
          partBtn.onclick = () => navigateTo(currentTarget);
        }
        nav.appendChild(partBtn);
      });

      lucide.createIcons();
    }

    function renderLoading() {
      const list = document.getElementById('fileListContainer');
      list.innerHTML = `
        <div class="p-8 flex flex-col items-center justify-center gap-3 text-slate-400">
          <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-sky-500"></i>
          <span class="text-xs">Fetching repository contents...</span>
        </div>
      `;
      lucide.createIcons();
    }

    function renderExplorerList() {
      const container = document.getElementById('fileListContainer');
      const items = STATE.filteredItems;

      if (!items || items.length === 0) {
        container.innerHTML = `
          <div class="p-10 text-center text-slate-400 text-xs">
            <i data-lucide="folder-search" class="w-8 h-8 mx-auto mb-2 text-slate-400 opacity-60"></i>
            No files or directories found in this folder.
          </div>
        `;
        lucide.createIcons();
        return;
      }

      let html = '';

      // Back button if in subfolder
      if (STATE.currentPath) {
        const parentPath = STATE.currentPath.includes('/') 
          ? STATE.currentPath.substring(0, STATE.currentPath.lastIndexOf('/')) 
          : '';
        html += `
          <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 cursor-pointer transition-colors text-xs text-sky-600 dark:text-sky-400 font-semibold"
               onclick="navigateTo('${parentPath}')">
            <span class="flex items-center gap-3">
              <i data-lucide="corner-left-up" class="w-4 h-4"></i>
              ..
            </span>
            <span class="text-slate-400 text-[11px]">Up directory</span>
          </div>
        `;
      }

      items.forEach(item => {
        const isDir = item.type === 'dir';
        const icon = getFileIcon(item);
        const iconColor = isDir ? 'text-amber-500 fill-amber-500/20' : 'text-slate-400';

        html += `
          <div class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group cursor-pointer"
               onclick="handleItemClick('${item.path}', '${item.type}', '${item.name}')">
            <div class="flex items-center gap-3 min-w-0 pr-4">
              <i data-lucide="${icon}" class="w-4 h-4 shrink-0 ${iconColor}"></i>
              <span class="text-sm font-medium text-slate-800 dark:text-slate-200 group-hover:text-sky-600 dark:group-hover:text-sky-400 truncate">
                ${item.name}
              </span>
            </div>
            <div class="flex items-center gap-6 shrink-0 text-xs text-slate-400">
              <span class="hidden sm:inline font-mono">${formatFileSize(item.size)}</span>
              <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                ${item.html_url ? `
                  <a href="${item.html_url}" target="_blank" rel="noopener noreferrer" title="View on GitHub"
                     class="p-1 hover:text-slate-900 dark:hover:text-white transition-colors">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                  </a>
                ` : ''}
              </div>
            </div>
          </div>
        `;
      });

      container.innerHTML = html;
      lucide.createIcons();
    }

    function navigateTo(path) {
      STATE.currentPath = path;
      document.getElementById('searchInput').value = '';
      fetchRepoContents(path);
    }

    function handleItemClick(path, type, name) {
      if (type === 'dir') {
        navigateTo(path);
      } else {
        loadFilePreviewByPath(path, name);
      }
    }

    async function loadFilePreviewByPath(path, name) {
      const viewer = document.getElementById('fileViewerContainer');
      const body = document.getElementById('previewContentBody');
      const title = document.getElementById('previewFileName');
      const sizeDisplay = document.getElementById('previewFileSize');
      const rawBtn = document.getElementById('previewRawBtn');

      title.textContent = name;
      sizeDisplay.textContent = 'Loading...';
      body.innerHTML = '<div class="flex items-center justify-center p-8"><i data-lucide="loader" class="w-6 h-6 animate-spin text-sky-500"></i></div>';
      viewer.classList.remove('hidden');
      viewer.scrollIntoView({ behavior: 'smooth', block: 'start' });
      lucide.createIcons();

      const apiUrl = `https://api.github.com/repos/${STATE.owner}/${STATE.repo}/contents/${path}`;
      const headers = { 'Accept': 'application/vnd.github.v3+json' };
      if (STATE.token) headers['Authorization'] = `token ${STATE.token}`;

      try {
        const res = await fetch(apiUrl, { headers });
        if (!res.ok) throw new Error('Could not fetch file contents');
        const fileData = await res.json();
        renderFileContent(fileData);
      } catch (err) {
        body.innerHTML = `
          <div class="p-6 text-center text-rose-500">
            <p class="font-semibold">Unable to load file preview.</p>
            <p class="text-xs text-slate-400 mt-1">${err.message}</p>
          </div>
        `;
      }
    }

    function renderFileContent(fileData) {
      const body = document.getElementById('previewContentBody');
      const sizeDisplay = document.getElementById('previewFileSize');
      const rawBtn = document.getElementById('previewRawBtn');

      sizeDisplay.textContent = formatFileSize(fileData.size);
      rawBtn.href = fileData.download_url || fileData.html_url;

      const ext = fileData.name.split('.').pop().toLowerCase();
      let content = '';

      if (fileData.content) {
        // Base64 decode utf8 support
        try {
          content = decodeURIComponent(escape(window.atob(fileData.content)));
        } catch (e) {
          content = window.atob(fileData.content);
        }
      }

      STATE.activeFile = { name: fileData.name, content: content };

      if (['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'].includes(ext)) {
        body.innerHTML = `
          <div class="flex items-center justify-center p-8 bg-slate-100 dark:bg-slate-950/60 rounded-xl">
            <img src="${fileData.download_url}" alt="${fileData.name}" class="max-h-96 rounded shadow object-contain" />
          </div>
        `;
      } else if (ext === 'md') {
        body.innerHTML = `<div class="markdown-body">${marked.parse(content)}</div>`;
      } else {
        const pre = document.createElement('pre');
        const code = document.createElement('code');
        code.className = `language-${ext}`;
        code.textContent = content;
        pre.appendChild(code);
        body.innerHTML = '';
        body.appendChild(pre);
        hljs.highlightElement(code);
      }
    }

    async function fetchAndDisplayReadme(readmeFile) {
      const readmeCard = document.getElementById('readmeCard');
      const readmeContent = document.getElementById('readmeContent');

      try {
        let content = '';
        if (readmeFile.download_url) {
          const res = await fetch(readmeFile.download_url);
          content = await res.text();
        } else {
          // If fetched from API item details
          const res = await fetch(readmeFile.url, {
            headers: STATE.token ? { 'Authorization': `token ${STATE.token}` } : {}
          });
          const fileData = await res.json();
          content = decodeURIComponent(escape(window.atob(fileData.content)));
        }

        readmeContent.innerHTML = marked.parse(content);
        readmeCard.classList.remove('hidden');
      } catch (e) {
        console.error("Readme error", e);
        readmeCard.classList.add('hidden');
      }
    }

    function renderSampleReadme() {
      const readmeCard = document.getElementById('readmeCard');
      const readmeContent = document.getElementById('readmeContent');
      const sample = `
# the-keybord / traduceri
Proiect comunitar pentru traduceri și localizare în limba română (RO).

### Structură repository
- **/games**: Traduceri dedicate fișierelor de dialog, meniuri și siruri de caractere din jocuri video.
- **/apps**: Localizări pentru instrumente software și pachete open-source.
- **/docs**: Ghiduri de stil și convenții terminologice în limba română.

### Contribuții
Trimiteți un Pull Request pe GitHub pentru a adăuga o nouă traducere sau îmbunătățire.
      `;
      readmeContent.innerHTML = marked.parse(sample);
      readmeCard.classList.remove('hidden');
    }

    function setupEventListeners() {
      // Dark mode toggle
      document.getElementById('themeToggleBtn').addEventListener('click', toggleTheme);

      // Search Filter
      document.getElementById('searchInput').addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        STATE.filteredItems = STATE.items.filter(item => 
          item.name.toLowerCase().includes(query)
        );
        renderExplorerList();
      });

      // Refresh Button
      document.getElementById('refreshBtn').addEventListener('click', () => {
        fetchRepoContents(STATE.currentPath);
        showToast('Refreshing folder...', 'info');
      });

      // Close Preview Button
      document.getElementById('closePreviewBtn').addEventListener('click', () => {
        document.getElementById('fileViewerContainer').classList.add('hidden');
        STATE.activeFile = null;
      });

      // Copy Button
      document.getElementById('copyContentBtn').addEventListener('click', () => {
        if (!STATE.activeFile || !STATE.activeFile.content) return;
        const tempText = document.createElement('textarea');
        tempText.value = STATE.activeFile.content;
        document.body.appendChild(tempText);
        tempText.select();
        try {
          document.execCommand('copy');
          showToast('Copied to clipboard!', 'success');
        } catch (e) {
          showToast('Could not copy content', 'error');
        }
        document.body.removeChild(tempText);
      });

      // Notice dismiss
      document.getElementById('noticeCloseBtn').addEventListener('click', () => {
        document.getElementById('noticeBanner').classList.add('hidden');
      });

      // Token Modal
      const modal = document.getElementById('tokenModal');
      const openModalBtn = document.getElementById('openTokenModalBtn');
      const closeModalBtn = document.getElementById('closeTokenModalBtn');
      const saveTokenBtn = document.getElementById('saveTokenBtn');
      const clearTokenBtn = document.getElementById('clearTokenBtn');
      const tokenInput = document.getElementById('tokenInput');

      openModalBtn.addEventListener('click', () => {
        tokenInput.value = STATE.token;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      });

      closeModalBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      });

      saveTokenBtn.addEventListener('click', () => {
        const val = tokenInput.value.trim();
        STATE.token = val;
        localStorage.setItem('gh_token', val);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        showToast('GitHub token saved!', 'success');
        fetchRepoContents(STATE.currentPath);
      });

      clearTokenBtn.addEventListener('click', () => {
        STATE.token = '';
        localStorage.removeItem('gh_token');
        tokenInput.value = '';
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        showToast('Token cleared', 'info');
      });
    }

    // Initialize application on load
    window.addEventListener('DOMContentLoaded', () => {
      initTheme();
      setupEventListeners();
      fetchRepoContents('');
    });
  </script>
</body>
</html>