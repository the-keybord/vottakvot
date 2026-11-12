<?php
/**
 * TikTak - GitHub Repository Sync Helper for Micro-hosting
 * Downloads repository archives via GitHub Zipball API and safely extracts
 * either the full repository or a specific subfolder into sites/{code}/.
 */

class GithubSync {
    /**
     * Parse a GitHub URL or owner/repo shorthand into structured parts.
     * Supports:
     *   - owner/repo
     *   - https://github.com/owner/repo
     *   - https://github.com/owner/repo.git
     *   - https://github.com/owner/repo/tree/branch/sub/folder
     */
    public static function parseRepoInput(string $input, string $branch = '', string $subdir = ''): ?array {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        $owner = '';
        $repo = '';
        $parsedBranch = trim($branch);
        $parsedSubdir = trim($subdir, " \t\n\r\0\x0B/");

        // Full URL
        if (preg_match('~^https?://(?:www\.)?github\.com/([^/]+)/([^/?#]+)(?:/tree/([^/?#]+)(?:/(.*))?)?~i', $input, $m)) {
            $owner = $m[1];
            $repo = preg_replace('/\.git$/i', '', $m[2]);
            if ($parsedBranch === '' && !empty($m[3])) {
                $parsedBranch = $m[3];
            }
            if ($parsedSubdir === '' && !empty($m[4])) {
                $parsedSubdir = trim(urldecode($m[4]), '/');
            }
        } elseif (preg_match('~^([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?$~', $input, $m)) {
            $owner = $m[1];
            $repo = $m[2];
        } else {
            return null;
        }

        if ($owner === '' || $repo === '') {
            return null;
        }

        return [
            'owner' => $owner,
            'repo' => $repo,
            'branch' => $parsedBranch !== '' ? $parsedBranch : 'main',
            'subdir' => $parsedSubdir,
        ];
    }

    /**
     * Decode stored content_link JSON into a GitHub config array.
     */
    public static function decodeConfig(?string $contentLink): ?array {
        if (empty($contentLink)) {
            return null;
        }
        $data = json_decode($contentLink, true);
        if (!is_array($data) || empty($data['owner']) || empty($data['repo'])) {
            return null;
        }
        return $data;
    }

    /**
     * Mask sensitive fields (token) before returning config to the browser UI.
     */
    public static function maskConfigForUi(?array $config, string $code = '', string $baseUrl = ''): ?array {
        if (!$config) {
            return null;
        }
        return [
            'owner' => $config['owner'],
            'repo' => $config['repo'],
            'branch' => $config['branch'] ?? 'main',
            'subdir' => $config['subdir'] ?? '',
            'has_token' => !empty($config['token']),
            'last_synced_at' => $config['last_synced_at'] ?? null,
            'last_commit' => $config['last_commit'] ?? null,
            'webhook_url' => ($baseUrl !== '' && $code !== '')
                ? rtrim($baseUrl, '/') . '/api/github_webhook.php?code=' . urlencode($code)
                : '',
            'webhook_secret' => $config['webhook_secret'] ?? '',
        ];
    }

    /**
     * Download GitHub repo zipball and extract (full or subdir) into $targetDir.
     * Preserves extra local files not present in the archive.
     */
    public static function syncToDirectory(array $config, string $targetDir): array {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'ZipArchive PHP extension is not enabled on this server.'];
        }
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'cURL PHP extension is not enabled on this server.'];
        }

        $owner = $config['owner'] ?? '';
        $repo = $config['repo'] ?? '';
        $branch = $config['branch'] ?? 'main';
        $subdir = trim(str_replace('\\', '/', $config['subdir'] ?? ''), '/');
        $token = trim((string)($config['token'] ?? ''));

        if ($owner === '' || $repo === '') {
            return ['success' => false, 'error' => 'Invalid GitHub repository configuration.'];
        }

        if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true)) {
            return ['success' => false, 'error' => 'Failed to create target site directory.'];
        }
        $targetBase = realpath($targetDir);
        if (!$targetBase) {
            return ['success' => false, 'error' => 'Target site directory is not accessible.'];
        }

        $zipUrl = sprintf(
            'https://api.github.com/repos/%s/%s/zipball/%s',
            rawurlencode($owner),
            rawurlencode($repo),
            rawurlencode($branch)
        );

        $tmpZip = tempnam(sys_get_temp_dir(), 'tiktak_gh_');
        if ($tmpZip === false) {
            return ['success' => false, 'error' => 'Could not allocate temporary file for download.'];
        }

        $fp = fopen($tmpZip, 'wb');
        if (!$fp) {
            @unlink($tmpZip);
            return ['success' => false, 'error' => 'Could not open temporary file for writing.'];
        }

        $headers = [
            'User-Agent: TikTak-MicroHost/1.0',
            'Accept: application/vnd.github+json',
        ];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($zipUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $ok = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $httpCode >= 400) {
            $errMsg = "GitHub download failed (HTTP {$httpCode})";
            if ($curlErr !== '') {
                $errMsg .= ": {$curlErr}";
            } elseif ($httpCode === 404) {
                $errMsg = "Repository '{$owner}/{$repo}' or branch '{$branch}' not found (404). If private, provide a GitHub Token.";
            } elseif ($httpCode === 401 || $httpCode === 403) {
                $errMsg = "GitHub authorization/rate-limit error (HTTP {$httpCode}). Check your GitHub Token.";
            }
            @unlink($tmpZip);
            return ['success' => false, 'error' => $errMsg];
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            return ['success' => false, 'error' => 'Downloaded archive could not be opened as a valid ZIP.'];
        }

        $extractedCount = 0;
        $totalBytes = 0;
        $subdirPrefix = $subdir !== '' ? $subdir . '/' : '';
        $matchedSubdir = ($subdir === '');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                continue;
            }
            $entryName = str_replace(['\\', "\0"], ['/', ''], $entryName);

            // GitHub zipballs wrap everything in a single top-level directory: owner-repo-sha/...
            $slashPos = strpos($entryName, '/');
            if ($slashPos === false) {
                continue;
            }
            $relFromRoot = substr($entryName, $slashPos + 1);
            if ($relFromRoot === '' || $relFromRoot === false) {
                continue;
            }

            // Apply optional subfolder filter
            if ($subdirPrefix !== '') {
                if (strpos($relFromRoot, $subdirPrefix) !== 0) {
                    continue;
                }
                $matchedSubdir = true;
                $relPath = substr($relFromRoot, strlen($subdirPrefix));
                if ($relPath === '' || $relPath === false) {
                    continue;
                }
            } else {
                $relPath = $relFromRoot;
            }

            // Sanitize path segments to prevent Zip-Slip traversal
            $parts = explode('/', $relPath);
            $safeParts = [];
            $skipEntry = false;
            foreach ($parts as $p) {
                if ($p === '' || $p === '.') continue;
                if ($p === '..') {
                    $skipEntry = true;
                    break;
                }
                $safeParts[] = $p;
            }
            if ($skipEntry || empty($safeParts)) {
                continue;
            }

            // Skip .git directory if present
            if ($safeParts[0] === '.git') {
                continue;
            }

            $dest = $targetBase . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $safeParts);

            if (substr($entryName, -1) === '/') {
                if (!is_dir($dest)) {
                    @mkdir($dest, 0777, true);
                }
            } else {
                $parent = dirname($dest);
                if (!is_dir($parent)) {
                    @mkdir($parent, 0777, true);
                }
                $stream = $zip->getStream($entryName);
                if ($stream) {
                    $out = @fopen($dest, 'wb');
                    if ($out) {
                        $bytes = stream_copy_to_stream($stream, $out);
                        fclose($out);
                        $extractedCount++;
                        $totalBytes += (int)$bytes;
                    }
                    fclose($stream);
                }
            }
        }

        $zip->close();
        @unlink($tmpZip);

        if (!$matchedSubdir) {
            return [
                'success' => false,
                'error' => "Subfolder '{$subdir}' was not found in '{$owner}/{$repo}@{$branch}'.",
            ];
        }

        return [
            'success' => true,
            'files_extracted' => $extractedCount,
            'total_bytes' => $totalBytes,
            'synced_at' => gmdate('Y-m-d H:i:s') . ' UTC',
        ];
    }
}

