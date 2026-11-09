<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Databases Course Documentation</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            database: {
              50: '#f0fdf4',
              100: '#dcfce7',
              200: '#bbf7d0',
              500: '#22c55e',
              600: '#16a34a',
              700: '#15803d',
              800: '#166534',
            },
            accent: {
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

  <!-- Marked Parser -->
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

  <!-- Highlight.js (Light Theme: GitHub style) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

  <!-- GitHub Markdown CSS (Light Theme) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.5.0/github-markdown-light.min.css" />

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: #f8fafc;
      color: #0f172a;
    }

    code, pre {
      font-family: 'JetBrains Mono', monospace !important;
    }

    /* Light Markdown Custom Enhancements */
    .markdown-body {
      box-sizing: border-box;
      min-width: 200px;
      max-width: 900px;
      margin: 0 auto;
      background-color: #ffffff !important;
      color: #1e293b !important;
      font-size: 1rem;
      line-height: 1.75;
      padding: 2.5rem;
      border: 1px solid #e2e8f0;
      border-radius: 1rem;
      box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.05);
    }

    .markdown-body h1, .markdown-body h2, .markdown-body h3, 
    .markdown-body h4, .markdown-body h5, .markdown-body h6 {
      color: #0f172a !important;
      border-bottom-color: #e2e8f0 !important;
      font-weight: 700;
    }

    .markdown-body p, .markdown-body li {
      color: #334155 !important;
    }

    .markdown-body pre {
      background-color: #f8fafc !important;
      border: 1px solid #e2e8f0 !important;
      border-radius: 0.75rem !important;
    }

    .markdown-body code:not(pre code) {
      background-color: #f1f5f9 !important;
      color: #0284c7 !important;
      padding: 0.2em 0.4em !important;
      border-radius: 4px;
      font-size: 85%;
    }

    .markdown-body blockquote {
      border-left: 4px solid #0284c7 !important;
      color: #475569 !important;
      background: #f0f9ff !important;
      border-radius: 0 0.5rem 0.5rem 0;
      padding: 0.85rem 1.25rem;
    }

    .markdown-body img {
      border-radius: 0.75rem;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
      max-height: 540px;
      margin: 1.5rem auto;
      display: block;
    }

    /* Neutralized Inert Links */
    .inert-link {
      color: #0284c7;
      font-weight: 500;
      text-decoration: underline;
      cursor: default;
    }

    /* Custom subtle scrollbars */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #f1f5f9;
    }
    ::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
  </style>
</head>
<body class="h-full flex flex-col overflow-hidden bg-slate-50 text-slate-800 antialiased selection:bg-sky-100 selection:text-sky-900">

  <header class="h-16 border-b border-slate-200 bg-white shadow-xs flex items-center justify-between px-4 lg:px-6 z-30 shrink-0">
    <div class="flex items-center space-x-3">
      <button id="mobile-toggle" aria-label="Toggle Navigation" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>

      <div class="flex items-center space-x-3">
        <!-- Database Vector Icon -->
        <div class="w-9 h-9 rounded-lg bg-sky-600 flex items-center justify-center font-bold text-white shadow-xs">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
          </svg>
        </div>
        <div>
          <h1 class="text-sm font-bold tracking-tight text-slate-900">Databases Course</h1>
          <p class="text-xs text-slate-500 font-medium">Interactive Lessons & Documentation</p>
        </div>
      </div>
    </div>

    <div class="flex items-center space-x-3">
      <span id="lesson-counter-badge" class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
        Loading lessons...
      </span>
      <button id="refresh-btn" title="Refresh directory" class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition border border-transparent hover:border-slate-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
      </button>
    </div>
  </header>

  <div class="flex-1 flex overflow-hidden relative">

    <!-- Mobile Drawer Backdrop -->
    <div id="drawer-backdrop" class="fixed inset-0 bg-slate-900/30 backdrop-blur-xs z-30 hidden lg:hidden transition-opacity"></div>

    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-72 lg:w-80 bg-white border-r border-slate-200 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shrink-0 pt-16 lg:pt-0 shadow-sm lg:shadow-none">
      
      <!-- Lesson Search / Filter Input with Autofill Protection -->
      <div class="p-4 border-b border-slate-200">
        <label for="lesson-search" class="text-xs font-bold tracking-wider text-slate-500 uppercase block mb-1.5">
          Filter Lessons
        </label>
        <div class="relative">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <input 
            type="text" 
            id="lesson-search" 
            name="lesson_filter_db_query_field"
            placeholder="Search lessons..." 
            autocomplete="one-time-code" 
            autocorrect="off" 
            autocapitalize="off" 
            spellcheck="false" 
            readonly 
            onfocus="this.removeAttribute('readonly');"
            class="w-full bg-slate-50 border border-slate-300 text-sm text-slate-800 rounded-lg pl-9 pr-8 py-2 focus:outline-none focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-100 transition placeholder:text-slate-400 font-medium"
          />
          <button id="clear-search" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600" aria-label="Clear filter">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <nav id="lessons-nav" class="flex-1 overflow-y-auto p-3 space-y-1" aria-label="Course Lessons">
        <div class="space-y-2 p-1" id="loading-skeletons">
          <div class="h-10 bg-slate-100 rounded-lg animate-pulse"></div>
          <div class="h-10 bg-slate-100 rounded-lg animate-pulse"></div>
          <div class="h-10 bg-slate-100 rounded-lg animate-pulse"></div>
          <div class="h-10 bg-slate-100 rounded-lg animate-pulse"></div>
        </div>
        <ul id="lesson-items" class="space-y-1 hidden"></ul>
      </nav>

      <!-- Sidebar Status -->
      <div class="p-3 border-t border-slate-200 bg-slate-50 text-[11px] text-slate-500 flex items-center justify-between font-medium">
        <div class="flex items-center space-x-2 truncate">
          <span id="api-status-dot" class="inline-block w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
          <span id="api-status-text" class="truncate">Ready</span>
        </div>
        <span id="current-count-text" class="text-slate-400 font-mono text-[10px]">0 items</span>
      </div>
    </aside>

    <main class="flex-1 flex flex-col h-full overflow-y-auto bg-slate-50" id="main-scroll">
      
      <!-- Top Content Header -->
      <div class="border-b border-slate-200 bg-white/80 backdrop-blur-md px-6 py-3 flex items-center justify-between text-xs text-slate-500 sticky top-0 z-20">
        <div class="flex items-center space-x-2 truncate">
          <span>Curriculum</span>
          <span class="text-slate-300">/</span>
          <span class="text-slate-600 font-medium">Databases</span>
          <span class="text-slate-300">/</span>
          <span id="active-lesson-breadcrumb" class="text-sky-600 font-semibold truncate">Select a lesson</span>
        </div>
        <div>
          <button id="copy-markdown-btn" class="hidden items-center space-x-1.5 px-3 py-1 text-xs rounded-md bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 transition shadow-xs font-medium" title="Copy raw document text">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            <span id="copy-btn-text">Copy Raw</span>
          </button>
        </div>
      </div>

      <!-- Markdown Viewer Container -->
      <div class="flex-1 px-4 sm:px-8 lg:px-12 py-8 max-w-5xl w-full mx-auto">
        
        <!-- Loading State -->
        <div id="content-loading" class="hidden py-24 flex flex-col items-center justify-center space-y-4 text-center">
          <div class="w-10 h-10 border-4 border-slate-200 border-t-sky-600 rounded-full animate-spin"></div>
          <p class="text-sm text-slate-500 font-medium">Loading documentation...</p>
        </div>

        <!-- Error State -->
        <div id="content-error" class="hidden p-6 rounded-xl bg-red-50 border border-red-200 text-red-800">
          <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
              <h2 class="text-sm font-semibold text-red-900">Unable to load document</h2>
              <p id="error-message" class="text-xs text-red-700 mt-1"></p>
              <button id="retry-btn" class="mt-3 px-3 py-1.5 text-xs bg-white hover:bg-red-100 border border-red-300 rounded-lg text-red-800 font-medium transition shadow-xs">
                Retry loading
              </button>
            </div>
          </div>
        </div>

        <!-- Rendered Markdown Body -->
        <article id="markdown-container" class="markdown-body"></article>

        <!-- Navigation Footer -->
        <div id="nav-footer" class="mt-10 pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 hidden">
          <button id="prev-lesson-btn" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-lg bg-white hover:bg-slate-50 border border-slate-300 text-sm font-semibold text-slate-700 transition shadow-xs disabled:opacity-40 disabled:pointer-events-none">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            <span id="prev-btn-label">Previous Lesson</span>
          </button>

          <span id="pagination-indicator" class="text-xs font-medium text-slate-500 order-first sm:order-none"></span>

          <button id="next-lesson-btn" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold transition shadow-xs disabled:opacity-40 disabled:pointer-events-none">
            <span id="next-btn-label">Next Lesson</span>
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>
        </div>

      </div>
    </main>
  </div>

  <script>
    const CONFIG = {
      owner: "the-keybord",
      repo: "courses",
      branch: "main",
      baseDirectory: "databases",
      fallbackLessonCount: 20
    };

    let lessons = [];
    let currentLessonIndex = -1;
    let rawMarkdownContent = "";

    // DOM Elements
    const lessonItemsEl = document.getElementById("lesson-items");
    const loadingSkeletonsEl = document.getElementById("loading-skeletons");
    const markdownContainerEl = document.getElementById("markdown-container");
    const contentLoadingEl = document.getElementById("content-loading");
    const contentErrorEl = document.getElementById("content-error");
    const errorMessageEl = document.getElementById("error-message");
    const activeLessonBreadcrumb = document.getElementById("active-lesson-breadcrumb");
    const searchInput = document.getElementById("lesson-search");
    const clearSearchBtn = document.getElementById("clear-search");
    const prevBtn = document.getElementById("prev-lesson-btn");
    const nextBtn = document.getElementById("next-lesson-btn");
    const prevBtnLabel = document.getElementById("prev-btn-label");
    const nextBtnLabel = document.getElementById("next-btn-label");
    const paginationIndicator = document.getElementById("pagination-indicator");
    const navFooter = document.getElementById("nav-footer");
    const retryBtn = document.getElementById("retry-btn");
    const refreshBtn = document.getElementById("refresh-btn");
    const apiStatusDot = document.getElementById("api-status-dot");
    const apiStatusText = document.getElementById("api-status-text");
    const currentCountText = document.getElementById("current-count-text");
    const lessonCounterBadge = document.getElementById("lesson-counter-badge");
    const copyMarkdownBtn = document.getElementById("copy-markdown-btn");
    const copyBtnText = document.getElementById("copy-btn-text");
    const mobileToggle = document.getElementById("mobile-toggle");
    const sidebar = document.getElementById("sidebar");
    const drawerBackdrop = document.getElementById("drawer-backdrop");
    const mainScroll = document.getElementById("main-scroll");

    /**
     * Builds a Marked custom renderer that:
     * 1. Safely parses token objects { href, title, text } across both modern and legacy Marked
     * 2. Prefixes relative image URLs to raw CDN
     * 3. Intercepts and neutralizes any link pointing to GitHub repositories into inert span text
     */
    function createConfiguredRenderer(folderName) {
      const renderer = new marked.Renderer();
      const rawBase = `https://raw.githubusercontent.com/${CONFIG.owner}/${CONFIG.repo}/${CONFIG.branch}/${CONFIG.baseDirectory}/${encodeURIComponent(folderName)}`;

      function parseArgs(arg1, arg2, arg3) {
        if (arg1 && typeof arg1 === "object") {
          return {
            href: String(arg1.href || ""),
            title: String(arg1.title || ""),
            text: String(arg1.text || "")
          };
        }
        return {
          href: String(arg1 || ""),
          title: String(arg2 || ""),
          text: String(arg3 || "")
        };
      }

      renderer.image = function(arg1, arg2, arg3) {
        const { href, title, text } = parseArgs(arg1, arg2, arg3);
        let finalHref = href.trim();

        if (!/^https?:\/\//i.test(finalHref)) {
          finalHref = finalHref.replace(/^\.\//, "");
          finalHref = `${rawBase}/${finalHref}`;
        }

        const titleAttr = title ? ` title="${title}"` : "";
        return `<img src="${finalHref}" alt="${text || 'Illustration'}"${titleAttr} loading="lazy" />`;
      };

      renderer.link = function(arg1, arg2, arg3) {
        const { href, title, text } = parseArgs(arg1, arg2, arg3);

        // Strip/neutralize links pointing to GitHub or external repository hosting
        const isExternalRepo = /github\.com/i.test(href) || /github\.io/i.test(href) || /raw\.githubusercontent\.com/i.test(href);

        if (isExternalRepo) {
          return `<span class="inert-link" title="${title || ''}">${text}</span>`;
        }

        if (href.startsWith("#")) {
          return `<a href="${href}" class="text-sky-600 hover:text-sky-700 underline font-medium">${text}</a>`;
        }

        const titleAttr = title ? ` title="${title}"` : "";
        return `<a href="${href}" target="_blank" rel="noopener noreferrer"${titleAttr} class="text-sky-600 hover:text-sky-700 underline font-medium">${text}</a>`;
      };

      return renderer;
    }

    // Configure base Marked options with code syntax highlighting
    marked.setOptions({
      highlight: function(code, lang) {
        if (lang && hljs.getLanguage(lang)) {
          try {
            return hljs.highlight(code, { language: lang }).value;
          } catch (e) {}
        }
        return hljs.highlightAuto(code).value;
      },
      gfm: true,
      breaks: true
    });

    /**
     * Discovers subfolders in GitHub directory
     */
    async function loadDirectoryStructure() {
      setLoadingLessonsUI(true);
      apiStatusDot.className = "inline-block w-2 h-2 rounded-full bg-amber-500 animate-pulse";
      apiStatusText.textContent = "Loading catalog...";

      const apiUrl = `https://api.github.com/repos/${CONFIG.owner}/${CONFIG.repo}/contents/${CONFIG.baseDirectory}?ref=${CONFIG.branch}`;

      try {
        const response = await fetch(apiUrl);

        if (response.status === 403) {
          console.warn("API rate limit active. Using local fallback range.");
          useFallbackLessons("Rate limit active (Showing default catalog)");
          return;
        }

        if (!response.ok) {
          throw new Error(`Directory query returned HTTP ${response.status}`);
        }

        const data = await response.json();
        if (!Array.isArray(data)) {
          throw new Error("Invalid directory response structure");
        }

        const discovered = data
          .filter(item => item.type === "dir")
          .map(item => ({
            name: item.name,
            displayName: formatLessonTitle(item.name)
          }));

        if (discovered.length === 0) {
          useFallbackLessons("Default catalog generated");
          return;
        }

        // Natural numeric sort ('lesson 00', 'lesson 01', ... 'lesson 11')
        discovered.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' }));

        lessons = discovered;
        initAfterDiscovery("Connected");

      } catch (err) {
        console.error("Discovery error:", err);
        useFallbackLessons(`Fallback active: ${err.message}`);
      }
    }

    /**
     * Fallback generator for rate-limited instances
     */
    function useFallbackLessons(reason) {
      const generated = [];
      for (let i = 0; i <= CONFIG.fallbackLessonCount; i++) {
        const pad = String(i).padStart(2, "0");
        const folderName = `lesson ${pad}`;
        generated.push({
          name: folderName,
          displayName: formatLessonTitle(folderName)
        });
      }
      lessons = generated;
      initAfterDiscovery(reason, true);
    }

    function formatLessonTitle(rawName) {
      return rawName
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());
    }

    function initAfterDiscovery(statusMessage, isFallback = false) {
      setLoadingLessonsUI(false);
      apiStatusDot.className = isFallback 
        ? "inline-block w-2 h-2 rounded-full bg-amber-400" 
        : "inline-block w-2 h-2 rounded-full bg-emerald-500";
      apiStatusText.textContent = statusMessage;
      currentCountText.textContent = `${lessons.length} lessons`;
      lessonCounterBadge.textContent = `${lessons.length} Lessons Available`;

      renderLessonList(lessons);

      // Deep link support via hash
      const hash = window.location.hash.replace(/^#/, "");
      if (hash) {
        const foundIdx = lessons.findIndex(l => 
          slugify(l.name) === hash.toLowerCase() || 
          encodeURIComponent(l.name.toLowerCase()) === hash.toLowerCase() ||
          l.name.toLowerCase() === decodeURIComponent(hash).toLowerCase()
        );
        if (foundIdx !== -1) {
          selectLesson(foundIdx);
          return;
        }
      }

      if (lessons.length > 0) {
        selectLesson(0);
      }
    }

    function renderLessonList(list) {
      lessonItemsEl.innerHTML = "";

      if (list.length === 0) {
        lessonItemsEl.innerHTML = `<li class="p-4 text-center text-xs text-slate-400">No matching lessons</li>`;
        lessonItemsEl.classList.remove("hidden");
        return;
      }

      list.forEach((item) => {
        const originalIndex = lessons.findIndex(l => l.name === item.name);
        const isActive = originalIndex === currentLessonIndex;

        const li = document.createElement("li");
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = `w-full text-left px-3 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition flex items-center justify-between group ${
          isActive 
            ? "bg-sky-600 text-white shadow-xs" 
            : "text-slate-700 hover:bg-slate-100 hover:text-slate-900"
        }`;

        btn.innerHTML = `
          <div class="flex items-center space-x-2.5 truncate">
            <span class="w-2 h-2 rounded-full ${isActive ? 'bg-white' : 'bg-slate-300 group-hover:bg-sky-500'} transition-colors shrink-0"></span>
            <span class="truncate">${item.displayName}</span>
          </div>
          <span class="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded font-semibold ${isActive ? 'bg-sky-700 text-white' : 'bg-slate-100 text-slate-500'}">MD</span>
        `;

        btn.addEventListener("click", () => {
          selectLesson(originalIndex);
          closeMobileDrawer();
        });

        li.appendChild(btn);
        lessonItemsEl.appendChild(li);
      });

      lessonItemsEl.classList.remove("hidden");
    }

    async function selectLesson(index) {
      if (index < 0 || index >= lessons.length) return;

      currentLessonIndex = index;
      const lesson = lessons[index];

      // Update sidebar active highlights
      renderLessonList(getFilteredLessons());

      // Update header and URL hash
      activeLessonBreadcrumb.textContent = lesson.displayName;
      window.location.hash = slugify(lesson.name);

      // UI States
      contentLoadingEl.classList.remove("hidden");
      markdownContainerEl.classList.add("hidden");
      contentErrorEl.classList.add("hidden");
      navFooter.classList.add("hidden");
      copyMarkdownBtn.classList.add("hidden");

      const rawUrl = `https://raw.githubusercontent.com/${CONFIG.owner}/${CONFIG.repo}/${CONFIG.branch}/${CONFIG.baseDirectory}/${encodeURIComponent(lesson.name)}/README.md`;

      try {
        const response = await fetch(rawUrl);

        if (!response.ok) {
          throw new Error(`Could not find README.md in "${lesson.name}"`);
        }

        const markdownText = await response.text();
        rawMarkdownContent = markdownText;

        // Custom renderer configured for this folder
        const folderRenderer = createConfiguredRenderer(lesson.name);
        markdownContainerEl.innerHTML = marked.parse(markdownText, { renderer: folderRenderer });

        // Highlight code blocks
        markdownContainerEl.querySelectorAll('pre code').forEach((block) => {
          hljs.highlightElement(block);
        });

        contentLoadingEl.classList.add("hidden");
        markdownContainerEl.classList.remove("hidden");
        copyMarkdownBtn.classList.remove("hidden");
        copyMarkdownBtn.classList.add("inline-flex");

        updatePagination();
        navFooter.classList.remove("hidden");

        mainScroll.scrollTo({ top: 0, behavior: 'smooth' });

      } catch (err) {
        contentLoadingEl.classList.add("hidden");
        errorMessageEl.textContent = err.message || "Failed to fetch document.";
        contentErrorEl.classList.remove("hidden");
        navFooter.classList.remove("hidden");
        updatePagination();
      }
    }

    function updatePagination() {
      if (currentLessonIndex > 0) {
        prevBtn.disabled = false;
        prevBtnLabel.textContent = lessons[currentLessonIndex - 1].displayName;
      } else {
        prevBtn.disabled = true;
        prevBtnLabel.textContent = "Start";
      }

      if (currentLessonIndex < lessons.length - 1) {
        nextBtn.disabled = false;
        nextBtnLabel.textContent = lessons[currentLessonIndex + 1].displayName;
      } else {
        nextBtn.disabled = true;
        nextBtnLabel.textContent = "Finished";
      }

      paginationIndicator.textContent = `Lesson ${currentLessonIndex + 1} of ${lessons.length}`;
    }

    function getFilteredLessons() {
      const q = searchInput.value.trim().toLowerCase();
      if (!q) return lessons;
      return lessons.filter(l => 
        l.name.toLowerCase().includes(q) || 
        l.displayName.toLowerCase().includes(q)
      );
    }

    function slugify(text) {
      return text.toString().toLowerCase().trim().replace(/[\s\W-]+/g, '-');
    }

    function setLoadingLessonsUI(loading) {
      if (loading) {
        loadingSkeletonsEl.classList.remove("hidden");
        lessonItemsEl.classList.add("hidden");
      } else {
        loadingSkeletonsEl.classList.add("hidden");
      }
    }

    function closeMobileDrawer() {
      sidebar.classList.add("-translate-x-full");
      drawerBackdrop.classList.add("hidden");
    }

    function openMobileDrawer() {
      sidebar.classList.remove("-translate-x-full");
      drawerBackdrop.classList.remove("hidden");
    }

    // Input Search Filtering
    searchInput.addEventListener("input", () => {
      const filtered = getFilteredLessons();
      renderLessonList(filtered);
      clearSearchBtn.classList.toggle("hidden", searchInput.value.length === 0);
    });

    clearSearchBtn.addEventListener("click", () => {
      searchInput.value = "";
      clearSearchBtn.classList.add("hidden");
      renderLessonList(lessons);
      searchInput.focus();
    });

    // Pagination handlers
    prevBtn.addEventListener("click", () => {
      if (currentLessonIndex > 0) selectLesson(currentLessonIndex - 1);
    });

    nextBtn.addEventListener("click", () => {
      if (currentLessonIndex < lessons.length - 1) selectLesson(currentLessonIndex + 1);
    });

    retryBtn.addEventListener("click", () => {
      if (currentLessonIndex >= 0) selectLesson(currentLessonIndex);
      else loadDirectoryStructure();
    });

    refreshBtn.addEventListener("click", () => {
      loadDirectoryStructure();
    });

    // Copy Raw Markdown
    copyMarkdownBtn.addEventListener("click", () => {
      if (!rawMarkdownContent) return;
      const textarea = document.createElement("textarea");
      textarea.value = rawMarkdownContent;
      textarea.style.position = "fixed";
      textarea.style.opacity = "0";
      document.body.appendChild(textarea);
      textarea.select();
      
      try {
        document.execCommand("copy");
        copyBtnText.textContent = "Copied!";
        setTimeout(() => { copyBtnText.textContent = "Copy Raw"; }, 2000);
      } catch (err) {
        console.error("Copy failed", err);
      } finally {
        document.body.removeChild(textarea);
      }
    });

    // Mobile Drawer Handlers
    mobileToggle.addEventListener("click", openMobileDrawer);
    drawerBackdrop.addEventListener("click", closeMobileDrawer);

    // Deep Link Hash Change Listener
    window.addEventListener("hashchange", () => {
      const hash = window.location.hash.replace(/^#/, "");
      if (!hash) return;
      const foundIdx = lessons.findIndex(l => 
        slugify(l.name) === hash.toLowerCase() || 
        encodeURIComponent(l.name.toLowerCase()) === hash.toLowerCase() ||
        l.name.toLowerCase() === decodeURIComponent(hash).toLowerCase()
      );
      if (foundIdx !== -1 && foundIdx !== currentLessonIndex) {
        selectLesson(foundIdx);
      }
    });

    // Initial load on ready
    window.addEventListener("DOMContentLoaded", () => {
      loadDirectoryStructure();
    });
  </script>
</body>
</html>