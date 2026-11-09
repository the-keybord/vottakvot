<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheRoboRangersCodex Wiki</title>
    
    <!-- Tailwind CSS with Typography Plugin for Markdown formatting -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Marked.js for Markdown parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <!-- DOMPurify for HTML sanitization -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
    
    <!-- Highlight.js for code syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

    <!-- Mermaid for diagrams -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mermaid/9.4.3/mermaid.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'media',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            850: '#1f2937',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Scrollbar for a cleaner look */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        @media (prefers-color-scheme: dark) {
            ::-webkit-scrollbar-thumb {
                background: #475569;
            }
        }
        .file-item, .folder-item {
            cursor: pointer;
            user-select: none;
        }
        /* Loader CSS */
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Typography overrides for better wiki readability */
        .prose pre {
            background-color: #282c34 !important; /* atom-one-dark bg */
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 h-screen flex overflow-hidden font-sans">

    <!-- Mobile Sidebar Toggle Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="bg-gray-50 dark:bg-gray-800 w-72 flex-shrink-0 border-r border-gray-200 dark:border-gray-700 flex flex-col h-full absolute md:relative z-30 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
        
        <!-- Header / Branding -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white truncate">
                <i class="ph ph-book-open text-blue-500 text-2xl"></i>
                <span class="truncate">RoboRangers Codex</span>
            </div>
            <button class="md:hidden text-gray-500 hover:text-gray-800 dark:hover:text-white" onclick="toggleSidebar()">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>

        <!-- Search Box -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 relative">
            <i class="ph ph-magnifying-glass absolute left-7 top-7 text-gray-400"></i>
            <input type="text" id="search-input" placeholder="Search files..." class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow text-gray-900 dark:text-white">
        </div>

        <!-- File Tree Navigation -->
        <div class="flex-1 overflow-y-auto p-2" id="file-tree-container">
            <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400 flex-col gap-3">
                <div class="loader"></div>
                <span class="text-sm">Fetching repository...</span>
            </div>
        </div>
        
        <!-- Footer / Credits -->
        <div class="p-3 text-xs text-gray-500 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
            <i class="ph ph-github-logo text-lg"></i>
            <a href="https://github.com/the-keybord/TheRoboRangersCodex" target="_blank" class="hover:underline">View on GitHub</a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-full min-w-0 bg-white dark:bg-gray-900 relative">
        
        <!-- Top Navbar (Mobile visible mostly) -->
        <header class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 md:hidden bg-gray-50 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-800 dark:hover:text-white focus:outline-none">
                    <i class="ph ph-list text-2xl"></i>
                </button>
                <span class="font-semibold text-gray-800 dark:text-white truncate" id="mobile-header-title">Wiki</span>
            </div>
        </header>

        <!-- Breadcrumbs & Actions -->
        <div class="hidden md:flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" id="breadcrumb-container">
                <!-- Breadcrumbs injected via JS -->
            </div>
            <div>
                <a id="github-file-link" href="#" target="_blank" class="hidden text-sm flex items-center gap-1 text-gray-500 hover:text-gray-900 dark:hover:text-white bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-md transition-colors">
                    <i class="ph ph-pencil-simple"></i> Edit on GitHub
                </a>
            </div>
        </div>

        <!-- Markdown Render Container -->
        <div class="flex-1 overflow-y-auto w-full">
            <div class="max-w-4xl mx-auto p-6 md:p-10 pb-24">
                
                <!-- Loading State -->
                <div id="content-loader" class="hidden flex flex-col items-center justify-center mt-20 gap-4">
                    <div class="loader"></div>
                    <span class="text-gray-500">Loading document...</span>
                </div>

                <!-- Error State -->
                <div id="content-error" class="hidden bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-lg mt-10">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <i class="ph ph-warning-circle text-xl"></i> Error Loading Content
                    </div>
                    <p id="error-message" class="text-sm"></p>
                </div>

                <!-- Empty/Initial State -->
                <div id="content-empty" class="flex flex-col items-center justify-center mt-32 text-center">
                    <i class="ph ph-files text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">Welcome to the Codex</h2>
                    <p class="text-gray-500 dark:text-gray-400 max-w-md">Select a markdown file from the sidebar to start reading. Use the search bar to quickly find documents.</p>
                </div>

                <!-- Actual Markdown Output -->
                <article id="markdown-content" class="prose prose-slate prose-blue dark:prose-invert lg:prose-lg max-w-none hidden">
                    <!-- Content injected via JS -->
                </article>

            </div>
        </div>
    </main>

    <script>
        /**
         * Configuration & State
         */
        const REPO_OWNER = 'the-keybord';
        const REPO_NAME = 'TheRoboRangersCodex';
        let defaultBranch = 'main';
        let fileTreeData = [];
        let currentPath = '';

        // Configure Marked.js with syntax highlighting
        marked.setOptions({
            highlight: function(code, lang) {
                const language = hljs.getLanguage(lang) ? lang : 'plaintext';
                return hljs.highlight(code, { language }).value;
            },
            breaks: true,
            gfm: true
        });

        // DOM Elements
        const treeContainer = document.getElementById('file-tree-container');
        const searchInput = document.getElementById('search-input');
        const markdownContent = document.getElementById('markdown-content');
        const contentLoader = document.getElementById('content-loader');
        const contentError = document.getElementById('content-error');
        const errorMessage = document.getElementById('error-message');
        const contentEmpty = document.getElementById('content-empty');
        const breadcrumbContainer = document.getElementById('breadcrumb-container');
        const mobileHeaderTitle = document.getElementById('mobile-header-title');
        const githubFileLink = document.getElementById('github-file-link');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');

        /**
         * Initialize the Application
         */
        async function init() {
            try {
                // 1. Get Repo Details (to find default branch)
                const repoInfoRes = await fetch(`https://api.github.com/repos/${REPO_OWNER}/${REPO_NAME}`);
                if (!repoInfoRes.ok) throw new Error('Repository not found or API rate limit exceeded.');
                const repoInfo = await repoInfoRes.json();
                defaultBranch = repoInfo.default_branch || 'main';

                // 2. Fetch the full repository tree recursively
                const treeRes = await fetch(`https://api.github.com/repos/${REPO_OWNER}/${REPO_NAME}/git/trees/${defaultBranch}?recursive=1`);
                if (!treeRes.ok) throw new Error('Failed to fetch repository structure.');
                const treeData = await treeRes.json();

                // 3. Filter for markdown files and construct a flat array
                fileTreeData = treeData.tree.filter(item => 
                    item.type === 'blob' && item.path.toLowerCase().endsWith('.md')
                );

                // 4. Render the Sidebar Tree
                renderSidebar(fileTreeData);

                // 5. Optionally load a README if it exists at root, or first file
                const readme = fileTreeData.find(f => f.path.toLowerCase() === 'readme.md');
                if (readme) {
                    loadFile(readme.path);
                } else if (fileTreeData.length > 0) {
                    loadFile(fileTreeData[0].path);
                }

            } catch (error) {
                console.error(error);
                treeContainer.innerHTML = `
                    <div class="text-red-500 text-sm p-4 text-center">
                        <i class="ph ph-warning text-2xl mb-2 block"></i>
                        Failed to load repository. Ensure it is public and GitHub API limits aren't exceeded.
                    </div>`;
            }
        }

        /**
         * Build and render the sidebar navigation
         */
        function renderSidebar(files, filterText = '') {
            treeContainer.innerHTML = ''; // Clear loader
            
            if (files.length === 0) {
                treeContainer.innerHTML = `<div class="p-4 text-sm text-gray-500 text-center">No markdown files found.</div>`;
                return;
            }

            // Group files by directory
            const structuredTree = {};
            
            files.forEach(file => {
                if (filterText && !file.path.toLowerCase().includes(filterText.toLowerCase())) return;

                const parts = file.path.split('/');
                const fileName = parts.pop();
                
                let currentLevel = structuredTree;
                parts.forEach(part => {
                    if (!currentLevel[part]) {
                        currentLevel[part] = { _isFolder: true, _isOpen: filterText !== '', children: {} };
                    }
                    currentLevel = currentLevel[part].children;
                });
                
                currentLevel[fileName] = { _isFolder: false, path: file.path, name: fileName };
            });

            if (Object.keys(structuredTree).length === 0 && filterText) {
                treeContainer.innerHTML = `<div class="p-4 text-sm text-gray-500 text-center">No matches for "${filterText}"</div>`;
                return;
            }

            // Recursively build HTML
            const treeHtml = createTreeHtml(structuredTree, 0);
            treeContainer.innerHTML = `<ul class="space-y-1">${treeHtml}</ul>`;

            // Attach folder toggle events
            document.querySelectorAll('.folder-item').forEach(folder => {
                folder.addEventListener('click', (e) => {
                    e.stopPropagation(); // prevent bubbling if nested
                    const childrenUl = folder.nextElementSibling;
                    const icon = folder.querySelector('.folder-icon');
                    if (childrenUl && childrenUl.classList.contains('hidden')) {
                        childrenUl.classList.remove('hidden');
                        icon.classList.replace('ph-folder', 'ph-folder-open');
                    } else if (childrenUl) {
                        childrenUl.classList.add('hidden');
                        icon.classList.replace('ph-folder-open', 'ph-folder');
                    }
                });
            });

            // Highlight currently active file
            updateActiveSidebarItem();
        }

        function createTreeHtml(node, depth) {
            let html = '';
            
            // Sort: Folders first, then files alphabetically
            const keys = Object.keys(node).sort((a, b) => {
                if (a.startsWith('_')) return 1; // Ignore internal flags in sorting
                if (b.startsWith('_')) return -1;
                const isAFolder = node[a]._isFolder;
                const isBFolder = node[b]._isFolder;
                if (isAFolder && !isBFolder) return -1;
                if (!isAFolder && isBFolder) return 1;
                return a.localeCompare(b);
            });

            keys.forEach(key => {
                if (key.startsWith('_')) return; // Skip internal flags

                const item = node[key];
                const padding = `padding-left: ${depth * 16 + 12}px;`;

                if (item._isFolder) {
                    const iconClass = item._isOpen ? 'ph-folder-open' : 'ph-folder';
                    const hiddenClass = item._isOpen ? '' : 'hidden';
                    html += `
                        <li>
                            <div class="folder-item flex items-center gap-2 py-1.5 pr-2 rounded-md hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-sm font-medium text-gray-700 dark:text-gray-300" style="${padding}">
                                <i class="ph ${iconClass} folder-icon text-gray-400"></i>
                                <span class="truncate">${key}</span>
                            </div>
                            <ul class="${hiddenClass} space-y-1 mt-1">
                                ${createTreeHtml(item.children, depth + 1)}
                            </ul>
                        </li>
                    `;
                } else {
                    // Extract title without .md for cleaner display
                    const displayName = item.name.replace(/\.md$/i, '');
                    html += `
                        <li>
                            <div onclick="loadFile('${item.path}')" data-path="${item.path}" class="file-item flex items-center gap-2 py-1.5 pr-2 rounded-md hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors text-sm text-gray-600 dark:text-gray-400 group" style="${padding}">
                                <i class="ph ph-file-text text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                                <span class="truncate group-hover:text-blue-600 dark:group-hover:text-blue-400">${displayName}</span>
                            </div>
                        </li>
                    `;
                }
            });
            return html;
        }

        /**
         * Fetch and render a Markdown file
         */
        async function loadFile(filePath) {
            // Update State
            currentPath = filePath;
            updateActiveSidebarItem();
            updateBreadcrumbs();
            
            // Close mobile sidebar if open
            if (window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
            }

            // Update UI State
            contentEmpty.classList.add('hidden');
            contentError.classList.add('hidden');
            markdownContent.classList.add('hidden');
            contentLoader.classList.remove('hidden');

            try {
                // Fetch raw markdown content
                const rawUrl = `https://raw.githubusercontent.com/${REPO_OWNER}/${REPO_NAME}/${defaultBranch}/${filePath}`;
                const response = await fetch(rawUrl);
                
                if (!response.ok) throw new Error(`Could not fetch file: ${response.statusText}`);
                
                const markdownText = await response.text();
                
                // Parse and Sanitize
                const rawHtml = marked.parse(markdownText);
                const safeHtml = DOMPurify.sanitize(rawHtml);
                
                // Inject and Show
            markdownContent.innerHTML = safeHtml;
            
            // Fix relative image links if any exist in the markdown
            fixImageLinks(filePath);

            // Render Mermaid diagrams
            renderMermaidDiagrams();

            contentLoader.classList.add('hidden');
            markdownContent.classList.remove('hidden');

            } catch (error) {
                contentLoader.classList.add('hidden');
                errorMessage.textContent = error.message;
                contentError.classList.remove('hidden');
            }
        }

        /**
         * Resolves relative image paths in markdown to absolute raw github URLs
         */
        function fixImageLinks(currentFilePath) {
            const images = markdownContent.querySelectorAll('img');
            const basePath = currentFilePath.split('/').slice(0, -1).join('/'); // Directory of current file
            
            images.forEach(img => {
                let src = img.getAttribute('src');
                if (src && !src.startsWith('http') && !src.startsWith('data:')) {
                    // It's a relative path. Resolve it against the repo raw URL
                    // Remove leading './' if present
                    if(src.startsWith('./')) src = src.substring(2);
                    
                    const resolvedPath = basePath ? `${basePath}/${src}` : src;
                    img.src = `https://raw.githubusercontent.com/${REPO_OWNER}/${REPO_NAME}/${defaultBranch}/${resolvedPath}`;
                }
            });
        }

        /**
         * Renders Mermaid.js diagrams from markdown code blocks
         */
        function renderMermaidDiagrams() {
            const mermaidBlocks = markdownContent.querySelectorAll('code.language-mermaid');
            if (mermaidBlocks.length === 0) return;

            // Setup Mermaid theme based on system preference
            const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            mermaid.initialize({
                startOnLoad: false,
                theme: isDarkMode ? 'dark' : 'default',
                securityLevel: 'loose'
            });

            mermaidBlocks.forEach((block) => {
                const graphDefinition = block.textContent;
                const pre = block.parentElement;
                
                const container = document.createElement('div');
                container.className = 'mermaid flex justify-center my-6 overflow-x-auto w-full';
                container.textContent = graphDefinition;
                
                pre.replaceWith(container);
            });

            try {
                mermaid.init(undefined, document.querySelectorAll('.mermaid'));
            } catch (e) {
                console.error("Mermaid failed to render", e);
            }
        }

        /**
         * UI Helpers
         */
        function updateActiveSidebarItem() {
            document.querySelectorAll('.file-item').forEach(el => {
                if (el.dataset.path === currentPath) {
                    el.classList.add('bg-blue-50', 'dark:bg-gray-700', 'text-blue-700', 'dark:text-blue-300', 'font-semibold');
                    el.classList.remove('text-gray-600', 'dark:text-gray-400');
                    
                    // Auto-open parent folders
                    let parent = el.parentElement;
                    while (parent && parent.tagName === 'UL') {
                        parent.classList.remove('hidden');
                        const folderDiv = parent.previousElementSibling;
                        if (folderDiv && folderDiv.classList.contains('folder-item')) {
                            const icon = folderDiv.querySelector('.folder-icon');
                            if (icon) icon.classList.replace('ph-folder', 'ph-folder-open');
                        }
                        parent = parent.parentElement ? parent.parentElement.parentElement : null;
                    }

                } else {
                    el.classList.remove('bg-blue-50', 'dark:bg-gray-700', 'text-blue-700', 'dark:text-blue-300', 'font-semibold');
                    el.classList.add('text-gray-600', 'dark:text-gray-400');
                }
            });
        }

        function updateBreadcrumbs() {
            if (!currentPath) return;
            const parts = currentPath.split('/');
            
            // Update Mobile Header Title
            mobileHeaderTitle.textContent = parts[parts.length - 1].replace(/\.md$/i, '');

            // Update Desktop Breadcrumbs
            let html = `<i class="ph ph-house mb-0.5 text-gray-400"></i>`;
            parts.forEach((part, index) => {
                const isLast = index === parts.length - 1;
                const displayName = isLast ? part.replace(/\.md$/i, '') : part;
                
                html += `<i class="ph ph-caret-right text-gray-400 mx-1 text-xs"></i>`;
                if (isLast) {
                    html += `<span class="text-gray-900 dark:text-white font-medium truncate max-w-[200px]">${displayName}</span>`;
                } else {
                    html += `<span class="truncate max-w-[150px]">${displayName}</span>`;
                }
            });
            breadcrumbContainer.innerHTML = html;

            // Update Edit Link
            githubFileLink.href = `https://github.com/${REPO_OWNER}/${REPO_NAME}/edit/${defaultBranch}/${currentPath}`;
            githubFileLink.classList.remove('hidden');
        }

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            mobileOverlay.classList.toggle('hidden');
        }

        // Search Implementation
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value;
            renderSidebar(fileTreeData, term);
        });

        // Run Initialization
        window.addEventListener('DOMContentLoaded', init);

    </script>
</body>
</html>