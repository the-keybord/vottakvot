<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>3D School Course Viewer</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
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
              800: '#0c4a6e',
            },
            ghdark: {
              bg: '#0d1117',
              surface: '#161b22',
              border: '#30363d',
              text: '#c9d1d9',
              muted: '#8b949e',
              active: '#1f6feb26'
            }
          }
        }
      }
    };
  </script>

  <!-- Marked Markdown Parser -->
  <script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>
  <!-- DOMPurify for HTML sanitization -->
  <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.9/dist/purify.min.js"></script>
  <!-- Highlight.js for code syntax highlighting -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark-dimmed.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

  <!-- GitHub Markdown CSS Styles -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.5.1/github-markdown-dark.min.css" />

  <style>
    /* Custom scrollbar and markdown container tweaking */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.1);
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(140, 150, 170, 0.3);
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(140, 150, 170, 0.5);
    }
    .markdown-body {
      background-color: transparent !important;
      font-size: 15px;
      line-height: 1.65;
    }
    .markdown-body pre {
      background-color: #161b22 !important;
      border: 1px solid #30363d;
      border-radius: 8px;
    }
    .markdown-body img {
      max-width: 100%;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }
  </style>
</head>

<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col font-sans antialiased selection:bg-sky-500 selection:text-white">

  <header class="h-16 border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-30 px-4 sm:px-6 flex items-center justify-between">
    <div class="flex items-center space-x-3">
      <!-- Mobile Sidebar Toggle -->
      <button id="toggle-sidebar-btn" class="md:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none" aria-label="Toggle Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <div class="flex items-center space-x-2.5">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-600/20 text-sky-400 ring-1 ring-sky-500/40 font-bold text-sm">
          3D
        </span>
        <div>
          <h1 class="text-sm font-semibold text-white tracking-wide flex items-center gap-2">
            3D School Course
            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-sky-400 font-mono font-normal">main</span>
          </h1>
          <p class="text-xs text-slate-400 font-mono">the-keybord / courses / 3d-school-start</p>
        </div>
      </div>
    </div>

    <!-- Header Actions -->
    <div class="flex items-center space-x-2">
      <!-- Direct GitHub Link -->
      <a href="https://github.com/the-keybord/courses/tree/main/3d-school-start" target="_blank" rel="noopener noreferrer" 
         class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 transition shadow-sm">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
        </svg>
        GitHub Repo
      </a>

      <!-- Refresh Button -->
      <button id="refresh-btn" title="Refresh directory" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
        <svg id="refresh-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>

      <!-- Token / Settings Button -->
      <button id="settings-btn" title="API Token Settings" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </button>
    </div>
  </header>

  <div class="flex-1 flex overflow-hidden relative">

    <!-- Mobile Overlay Backdrop -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/60 z-20 md:hidden hidden transition-opacity"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-20 w-72 bg-slate-900 border-r border-slate-800 flex flex-col transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out">
      <!-- Search Bar -->
      <div class="p-3.5 border-b border-slate-800">
        <div class="relative">
          <input id="search-input" type="text" placeholder="Filter lessons (e.g. 01, intro)..."
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-lg pl-9 pr-3 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition" />
          <svg class="w-4 h-4 text-slate-500 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Lessons List -->
      <div class="flex-1 overflow-y-auto p-3 space-y-1" id="lessons-nav-list">
        <!-- Dynamic Lesson items will be injected here -->
        <div class="text-center py-8 text-xs text-slate-500 animate-pulse">
          Fetching lessons from GitHub...
        </div>
      </div>

      <!-- Sidebar Status Footer -->
      <div class="p-3 border-t border-slate-800/80 bg-slate-950/40 text-[11px] text-slate-400 flex items-center justify-between">
        <span id="lesson-count-badge">0 lessons</span>
        <span id="api-status-badge" class="flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Public API
        </span>
      </div>
    </aside>

    <!-- Content Area -->
    <main id="content-container" class="flex-1 flex flex-col overflow-y-auto bg-slate-950">
      
      <!-- Top banner for notifications / warnings (e.g. rate limit fallback active) -->
      <div id="notice-banner" class="hidden px-6 py-2.5 bg-amber-500/10 border-b border-amber-500/20 text-amber-300 text-xs flex items-center justify-between">
        <span id="notice-text">Rate limit detected. Using fallback lessons index.</span>
        <button onclick="document.getElementById('notice-banner').classList.add('hidden')" class="text-amber-400 hover:text-white font-bold ml-4">✕</button>
      </div>

      <!-- Main Reader Area -->
      <div class="flex-1 max-w-4xl w-full mx-auto p-6 md:p-10 flex flex-col justify-between">
        <div>
          <!-- Breadcrumb & Direct Folder Link -->
          <div class="flex flex-wrap items-center justify-between gap-2 pb-5 border-b border-slate-800 text-xs text-slate-400">
            <div class="flex items-center space-x-1.5">
              <span>Courses</span>
              <span>/</span>
              <span>3d-school-start</span>
              <span>/</span>
              <span id="current-lesson-breadcrumb" class="font-semibold text-sky-400">Loading...</span>
            </div>
            <a id="current-folder-github-link" href="#" target="_blank" rel="noopener noreferrer" 
               class="text-xs text-slate-400 hover:text-sky-400 inline-flex items-center gap-1">
              <span>View directory on GitHub</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
            </a>
          </div>

          <!-- Document Render Area -->
          <article id="doc-content" class="markdown-body mt-6 min-h-[400px]">
            <div class="flex flex-col items-center justify-center py-20 text-slate-500 space-y-3">
              <svg class="w-8 h-8 animate-spin text-sky-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <p class="text-sm">Fetching README.md content...</p>
            </div>
          </article>
        </div>

        <!-- Footer Nav Controls -->
        <nav class="mt-12 pt-6 border-t border-slate-800/80 flex items-center justify-between gap-4">
          <button id="prev-lesson-btn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-700 bg-slate-900 hover:bg-slate-800 text-slate-300 text-sm font-medium transition disabled:opacity-30 disabled:cursor-not-allowed">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="hidden sm:inline">Previous:</span>
            <span id="prev-lesson-title" class="font-normal text-xs text-slate-400 truncate max-w-[130px] sm:max-w-xs">None</span>
          </button>

          <button id="next-lesson-btn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-700 bg-slate-900 hover:bg-slate-800 text-slate-300 text-sm font-medium transition disabled:opacity-30 disabled:cursor-not-allowed">
            <span class="hidden sm:inline">Next:</span>
            <span id="next-lesson-title" class="font-normal text-xs text-slate-400 truncate max-w-[130px] sm:max-w-xs">None</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </nav>
      </div>
    </main>
  </div>

  <!-- Token / Fallback Modal -->
  <div id="settings-modal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800">
        <h3 class="font-semibold text-slate-100 flex items-center gap-2">
          <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
          </svg>
          GitHub Settings
        </h3>
        <button id="close-modal-btn" class="text-slate-400 hover:text-white text-lg leading-none">✕</button>
      </div>

      <div class="space-y-3 text-xs text-slate-300">
        <p>
          GitHub limits anonymous requests to <strong>60 per hour</strong>. If you hit this limit or want faster directory queries, you can supply an optional GitHub Personal Access Token (classic or fine-grained with <code>public_repo</code> read-only permission).
        </p>
        <div>
          <label class="block mb-1 text-slate-400 font-medium">Personal Access Token (optional)</label>
          <input id="gh-token-input" type="password" placeholder="ghp_xxxxxxxxxxxx" 
                 class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-100 focus:outline-none focus:border-sky-500" />
          <p class="text-[10px] text-slate-500 mt-1">Saved securely in your browser's localStorage only.</p>
        </div>

        <div class="pt-2 border-t border-slate-800">
          <p class="font-semibold text-slate-200 mb-1">Manual Lesson Count Fallback</p>
          <p class="text-slate-400 mb-2">If GitHub's API is blocked completely, populate lessons automatically up to:</p>
          <div class="flex items-center gap-2">
            <input id="fallback-count-input" type="number" min="5" max="50" value="20" class="w-24 bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-200" />
            <button id="apply-fallback-btn" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-200 transition">
              Generate Fallback List
            </button>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
        <button id="save-token-btn" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-semibold transition">
          Save Settings
        </button>
      </div>
    </div>
  </div>

  <script>
    (function () {
      // Configuration
      const REPO_OWNER = "the-keybord";
      const REPO_NAME = "courses";
      const REPO_BRANCH = "main";
      const REPO_PATH = "3d-school-start";

      // Application State
      let lessonsList = [];
      let currentFolder = "";
      let authToken = localStorage.getItem("gh_token") || "";

      // DOM Elements
      const sidebar = document.getElementById("sidebar");
      const sidebarBackdrop = document.getElementById("sidebar-backdrop");
      const toggleSidebarBtn = document.getElementById("toggle-sidebar-btn");
      const lessonsNavList = document.getElementById("lessons-nav-list");
      const searchInput = document.getElementById("search-input");
      const docContent = document.getElementById("doc-content");
      const currentLessonBreadcrumb = document.getElementById("current-lesson-breadcrumb");
      const currentFolderGithubLink = document.getElementById("current-folder-github-link");
      const lessonCountBadge = document.getElementById("lesson-count-badge");
      const refreshBtn = document.getElementById("refresh-btn");
      const refreshIcon = document.getElementById("refresh-icon");
      const settingsBtn = document.getElementById("settings-btn");
      const settingsModal = document.getElementById("settings-modal");
      const closeModalBtn = document.getElementById("close-modal-btn");
      const saveTokenBtn = document.getElementById("save-token-btn");
      const ghTokenInput = document.getElementById("gh-token-input");
      const noticeBanner = document.getElementById("notice-banner");
      const noticeText = document.getElementById("notice-text");
      const prevLessonBtn = document.getElementById("prev-lesson-btn");
      const nextLessonBtn = document.getElementById("next-lesson-btn");
      const prevLessonTitle = document.getElementById("prev-lesson-title");
      const nextLessonTitle = document.getElementById("next-lesson-title");
      const fallbackCountInput = document.getElementById("fallback-count-input");
      const applyFallbackBtn = document.getElementById("apply-fallback-btn");

      if (authToken) ghTokenInput.value = authToken;

      // Configure Marked with custom renderer to automatically rewrite relative images & links
      const renderer = new marked.Renderer();

      // Custom link handler: open external links in new tab and rewrite relative file links
      renderer.link = function (tokenOrHref, title, text) {
        let href = typeof tokenOrHref === 'object' ? tokenOrHref.href : tokenOrHref;
        let linkTitle = (typeof tokenOrHref === 'object' ? tokenOrHref.title : title) || '';
        let linkText = typeof tokenOrHref === 'object' ? tokenOrHref.text : text;

        if (!href) return linkText;

        // If it's a relative link, link it to the raw or github tree
        if (!href.startsWith('http://') && !href.startsWith('https://') && !href.startsWith('#') && !href.startsWith('mailto:')) {
          const cleanPath = href.replace(/^\.\//, '');
          href = `https://github.com/${REPO_OWNER}/${REPO_NAME}/blob/${REPO_BRANCH}/${REPO_PATH}/${currentFolder}/${cleanPath}`;
        }
        return `<a href="${href}" target="_blank" rel="noopener noreferrer" title="${linkTitle || ''}" class="text-sky-400 hover:underline inline-flex items-center gap-0.5">${linkText}</a>`;
      };

      // Custom image handler: redirect relative image paths to raw.githubusercontent.com
      renderer.image = function (tokenOrHref, title, alt) {
        let src = typeof tokenOrHref === 'object' ? tokenOrHref.href : tokenOrHref;
        let imgTitle = (typeof tokenOrHref === 'object' ? tokenOrHref.title : title) || '';
        let imgAlt = (typeof tokenOrHref === 'object' ? tokenOrHref.text : alt) || '';

        if (!src) return '';

        // If relative image, rewrite to raw github user content
        if (!src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('data:')) {
          const cleanSrc = src.replace(/^\.\//, '');
          src = `https://raw.githubusercontent.com/${REPO_OWNER}/${REPO_NAME}/${REPO_BRANCH}/${REPO_PATH}/${encodeURIComponent(currentFolder)}/${cleanSrc}`;
        }

        return `<figure class="my-4"><img src="${src}" alt="${imgAlt}" title="${imgTitle}" class="rounded-lg shadow border border-slate-800 max-w-full h-auto mx-auto" onerror="this.onerror=null;this.classList.add('opacity-40');this.alt='[Image failed to load: '+this.alt+']';" /><figcaption class="text-center text-xs text-slate-500 mt-2">${imgAlt || ''}</figcaption></figure>`;
      };

      marked.setOptions({
        renderer: renderer,
        highlight: function (code, lang) {
          const language = hljs.getLanguage(lang) ? lang : 'plaintext';
          return hljs.highlight(code, { language }).value;
        },
        gfm: true,
        breaks: true
      });

      async function fetchLessonsDirectory() {
        refreshIcon.classList.add('animate-spin');
        lessonsNavList.innerHTML = `<div class="text-center py-8 text-xs text-slate-500 animate-pulse">Scanning ${REPO_PATH}...</div>`;

        const apiUrl = `https://api.github.com/repos/${REPO_OWNER}/${REPO_NAME}/contents/${REPO_PATH}?ref=${REPO_BRANCH}`;
        const headers = { 'Accept': 'application/vnd.github.v3+json' };
        if (authToken) {
          headers['Authorization'] = `Bearer ${authToken}`;
        }

        try {
          const response = await fetch(apiUrl, { headers });

          if (response.status === 403 || response.status === 429) {
            showNotice("GitHub API Rate Limit reached (60/hr). Using smart fallback lessons list.");
            generateFallbackLessons(25);
            return;
          }

          if (!response.ok) {
            throw new Error(`GitHub API returned HTTP ${response.status}`);
          }

          const data = await response.json();

          if (!Array.isArray(data)) {
            throw new Error("Unexpected API response structure");
          }

          // Filter only directories
          const dirItems = data
            .filter(item => item.type === "dir")
            .map(item => item.name);

          // Natural numerical sort: "lesson 00", "lesson 01", ..., "lesson 10"
          dirItems.sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }));

          if (dirItems.length === 0) {
            showNotice("No subfolders detected via API. Generating fallback list.");
            generateFallbackLessons(20);
            return;
          }

          lessonsList = dirItems;
          finishDirectoryLoad();

        } catch (err) {
          console.error("Error fetching repository contents:", err);
          showNotice(`API Request Error: ${err.message}. Showing fallback list.`);
          generateFallbackLessons(25);
        } finally {
          refreshIcon.classList.remove('animate-spin');
        }
      }

      function generateFallbackLessons(count = 20) {
        lessonsList = [];
        for (let i = 0; i <= count; i++) {
          const pad = i < 10 ? `0${i}` : `${i}`;
          lessonsList.push(`lesson ${pad}`);
        }
        finishDirectoryLoad();
      }

      function finishDirectoryLoad() {
        lessonCountBadge.textContent = `${lessonsList.length} lessons`;
        renderLessonSidebar();

        // Check if there is a hash in URL (e.g. #lesson%2001 or #lesson-01)
        const hash = decodeURIComponent(window.location.hash.replace(/^#/, ''));
        const matchedFolder = lessonsList.find(f => f.toLowerCase() === hash.toLowerCase() || f.toLowerCase().replace(/\s+/g, '-') === hash.toLowerCase());

        if (matchedFolder) {
          selectLesson(matchedFolder);
        } else if (lessonsList.length > 0) {
          selectLesson(lessonsList[0]);
        }
      }

      function renderLessonSidebar() {
        const filter = searchInput.value.trim().toLowerCase();
        lessonsNavList.innerHTML = "";

        const filtered = lessonsList.filter(folder => folder.toLowerCase().includes(filter));

        if (filtered.length === 0) {
          lessonsNavList.innerHTML = `<div class="text-center py-6 text-xs text-slate-500">No lessons matching "${filter}"</div>`;
          return;
        }

        filtered.forEach(folder => {
          const isSelected = folder === currentFolder;
          const btn = document.createElement("button");
          btn.className = `w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center justify-between transition group ${
            isSelected
              ? "bg-sky-500/15 text-sky-400 border border-sky-500/30"
              : "text-slate-300 hover:bg-slate-800/80 hover:text-white"
          }`;

          // Format title cleanly
          const displayName = folder.replace(/^lesson\s*/i, 'Lesson ');

          btn.innerHTML = `
            <span class="flex items-center gap-2 truncate">
              <svg class="w-3.5 h-3.5 ${isSelected ? 'text-sky-400' : 'text-slate-500 group-hover:text-slate-300'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="truncate">${displayName}</span>
            </span>
            ${isSelected ? '<span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>' : ''}
          `;

          btn.onclick = () => {
            selectLesson(folder);
            closeSidebarMobile();
          };

          lessonsNavList.appendChild(btn);
        });
      }

      async function selectLesson(folderName) {
        currentFolder = folderName;
        window.location.hash = encodeURIComponent(folderName);

        // Update breadcrumb & GitHub link
        currentLessonBreadcrumb.textContent = folderName;
        currentFolderGithubLink.href = `https://github.com/${REPO_OWNER}/${REPO_NAME}/tree/${REPO_BRANCH}/${REPO_PATH}/${encodeURIComponent(folderName)}`;

        // Re-render sidebar to highlight active item
        renderLessonSidebar();
        updateBottomNavigation();

        // Show loading state
        docContent.innerHTML = `
          <div class="flex flex-col items-center justify-center py-20 text-slate-500 space-y-3">
            <svg class="w-8 h-8 animate-spin text-sky-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <p class="text-sm">Loading ${folderName}/README.md...</p>
          </div>
        `;

        // Scroll to top
        document.getElementById("content-container").scrollTo({ top: 0, behavior: 'smooth' });

        // Raw Markdown URL
        const rawUrl = `https://raw.githubusercontent.com/${REPO_OWNER}/${REPO_NAME}/${REPO_BRANCH}/${REPO_PATH}/${encodeURIComponent(folderName)}/README.md`;

        try {
          const res = await fetch(rawUrl);
          if (!res.ok) {
            if (res.status === 404) {
              docContent.innerHTML = `
                <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-8 text-center my-8">
                  <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                  </div>
                  <h3 class="text-lg font-semibold text-white mb-1">No README.md in "${folderName}"</h3>
                  <p class="text-xs text-slate-400 max-w-md mx-auto mb-4">This folder might still be under development or doesn't include a README file on branch "${REPO_BRANCH}".</p>
                  <a href="${currentFolderGithubLink.href}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-medium rounded-lg text-slate-200 transition">
                    Explore folder on GitHub ↗
                  </a>
                </div>
              `;
              return;
            }
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
          }

          const rawText = await res.text();
          // Parse Markdown and sanitize with DOMPurify
          const html = marked.parse(rawText);
          docContent.innerHTML = DOMPurify.sanitize(html, {
            ADD_ATTR: ['target', 'onerror']
          });

          // Apply Prism/highlightjs code blocks
          docContent.querySelectorAll('pre code').forEach((block) => {
            hljs.highlightElement(block);
          });

        } catch (err) {
          docContent.innerHTML = `
            <div class="rounded-xl border border-rose-900/50 bg-rose-950/20 p-6 text-rose-300 text-sm">
              <h3 class="font-semibold text-rose-200 mb-1">Failed to fetch document</h3>
              <p class="text-xs opacity-80">${err.message}</p>
              <button onclick="selectLesson('${folderName}')" class="mt-4 px-3 py-1.5 rounded-lg bg-rose-900/40 hover:bg-rose-900/70 border border-rose-700 text-xs transition">
                Retry loading
              </button>
            </div>
          `;
        }
      }

      function updateBottomNavigation() {
        const index = lessonsList.indexOf(currentFolder);

        // Previous
        if (index > 0) {
          prevLessonBtn.disabled = false;
          prevLessonTitle.textContent = lessonsList[index - 1];
          prevLessonBtn.onclick = () => selectLesson(lessonsList[index - 1]);
        } else {
          prevLessonBtn.disabled = true;
          prevLessonTitle.textContent = "None";
          prevLessonBtn.onclick = null;
        }

        // Next
        if (index >= 0 && index < lessonsList.length - 1) {
          nextLessonBtn.disabled = false;
          nextLessonTitle.textContent = lessonsList[index + 1];
          nextLessonBtn.onclick = () => selectLesson(lessonsList[index + 1]);
        } else {
          nextLessonBtn.disabled = true;
          nextLessonTitle.textContent = "None";
          nextLessonBtn.onclick = null;
        }
      }

      function showNotice(msg) {
        noticeText.textContent = msg;
        noticeBanner.classList.remove('hidden');
      }

      function toggleSidebarMobile() {
        const isOpen = !sidebar.classList.contains("-translate-x-full");
        if (isOpen) {
          closeSidebarMobile();
        } else {
          sidebar.classList.remove("-translate-x-full");
          sidebarBackdrop.classList.remove("hidden");
        }
      }

      function closeSidebarMobile() {
        sidebar.classList.add("-translate-x-full");
        sidebarBackdrop.classList.add("hidden");
      }

      // Search input handler
      searchInput.addEventListener("input", renderLessonSidebar);

      // Mobile toggles
      toggleSidebarBtn.addEventListener("click", toggleSidebarMobile);
      sidebarBackdrop.addEventListener("click", closeSidebarMobile);

      // Refresh button
      refreshBtn.addEventListener("click", () => {
        fetchLessonsDirectory();
      });

      // Settings Modal triggers
      settingsBtn.addEventListener("click", () => {
        settingsModal.classList.remove("hidden");
      });
      closeModalBtn.addEventListener("click", () => {
        settingsModal.classList.add("hidden");
      });
      saveTokenBtn.addEventListener("click", () => {
        authToken = ghTokenInput.value.trim();
        if (authToken) {
          localStorage.setItem("gh_token", authToken);
          showNotice("GitHub Token saved. Higher rate limits enabled.");
        } else {
          localStorage.removeItem("gh_token");
        }
        settingsModal.classList.add("hidden");
        fetchLessonsDirectory();
      });

      // Fallback manual trigger
      applyFallbackBtn.addEventListener("click", () => {
        const count = parseInt(fallbackCountInput.value, 10) || 20;
        generateFallbackLessons(count);
        settingsModal.classList.add("hidden");
        showNotice(`Generated ${count + 1} fallback lesson entries.`);
      });

      // Listen for window hash changes (e.g. user clicks browser back/forward buttons)
      window.addEventListener("hashchange", () => {
        const hash = decodeURIComponent(window.location.hash.replace(/^#/, ''));
        if (hash && hash !== currentFolder && lessonsList.includes(hash)) {
          selectLesson(hash);
        }
      });

      // Initialize
      fetchLessonsDirectory();
    })();
  </script>
</body>
</html>