<?php
// Force the browser/client to read this as plain text
header('Content-Type: text/plain; charset=utf-8');

$owner = 'the-keybord';
$repo = 'TheRoboRangersCodex';

// The GitHub API requires a User-Agent header for all requests
$options = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: PHP-GitHub-Fetcher',
            'Accept: application/vnd.github.v3+json'
        ]
    ]
];
$context = stream_context_create($options);

// 1. Fetch repository details to dynamically get the default branch (usually 'main' or 'master')
$repoUrl = "https://api.github.com/repos/$owner/$repo";
$repoDataJson = @file_get_contents($repoUrl, false, $context);

if (!$repoDataJson) {
    die("Error: Could not fetch repository details. The repository might be private, or you've hit the GitHub API unauthenticated rate limit (60 requests/hour).");
}

$repoData = json_decode($repoDataJson, true);
$branch = $repoData['default_branch'] ?? 'main';

// 2. Fetch the recursive tree of the repository
$treeUrl = "https://api.github.com/repos/$owner/$repo/git/trees/$branch?recursive=1";
$treeDataJson = @file_get_contents($treeUrl, false, $context);

if (!$treeDataJson) {
    die("Error: Could not fetch the repository tree.");
}

$treeData = json_decode($treeDataJson, true);

if (!isset($treeData['tree'])) {
    die("Error: Invalid tree data returned from GitHub.");
}

// Extensions to skip so we don't output binary gibberish into our text
$binaryExtensions = ['png', 'jpg', 'jpeg', 'gif', 'ico', 'pdf', 'zip', 'exe', 'bin', 'stl', 'step', 'mp4', 'woff'];

// 3. Iterate through the tree and fetch the content of each file
foreach ($treeData['tree'] as $item) {
    // We only want to fetch files ('blob'), not directories ('tree')
    if ($item['type'] === 'blob') {
        $filePath = $item['path'];
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        
        // Output a clean separator for each file
        echo str_repeat("=", 80) . "\n";
        echo "FILE: $filePath\n";
        echo str_repeat("=", 80) . "\n\n";

        if (in_array(strtolower($ext), $binaryExtensions)) {
            echo "[Skipped binary/media file]\n\n";
            continue;
        }

        // Construct the raw URL for the file
        // e.g., https://raw.githubusercontent.com/the-keybord/TheRoboRangersCodex/main/path/to/file.ext
        $rawUrl = "https://raw.githubusercontent.com/$owner/$repo/$branch/" . str_replace(' ', '%20', $filePath);
        
        $fileContent = @file_get_contents($rawUrl);

        if ($fileContent !== false) {
            echo $fileContent . "\n\n";
        } else {
            echo "[Error reading file content]\n\n";
        }
    }
}
?>