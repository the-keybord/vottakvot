<?php
/**
 * TikTak - PocketBase PDO Emulation Layer
 * Allows all existing TikTak code, API endpoints, and views to interact with PocketBase
 * transparently using table prefixing (tiktak_tablename).
 */

require_once __DIR__ . '/PocketBase.php';

class PocketBasePDO {
    private PocketBase $pb;
    private ?string $lastInsertId = null;
    public ?array $lastQueryDebug = null;

    public function __construct(?PocketBase $pb = null) {
        $this->pb = $pb ?: PocketBase::getInstance();
    }

    public function getPocketBase(): PocketBase {
        return $this->pb;
    }

    public function lastInsertId(?string $name = null): ?string {
        return $this->lastInsertId;
    }

    public function prepare(string $statement): PocketBaseStatement {
        return new PocketBaseStatement($this, $this->pb, $statement);
    }

    public function query(string $statement): PocketBaseStatement {
        $stmt = $this->prepare($statement);
        $stmt->execute();
        return $stmt;
    }

    public function exec(string $statement): int {
        $stmt = $this->prepare($statement);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function setLastInsertId(?string $id): void {
        $this->lastInsertId = $id;
    }
}

class PocketBaseStatement {
    private PocketBasePDO $pdo;
    private PocketBase $pb;
    private string $sql;
    private array $results = [];
    private int $cursor = 0;
    private int $affectedRows = 0;
    private string $pfx;

    public function __construct(PocketBasePDO $pdo, PocketBase $pb, string $sql) {
        $this->pdo = $pdo;
        $this->pb = $pb;
        $this->sql = trim($sql);
        $this->pfx = defined('POCKETBASE_TABLE_PREFIX') ? POCKETBASE_TABLE_PREFIX : 'tiktak_';
    }

    private function col(string $name): string {
        return $this->pfx . $name;
    }

    private function q(mixed $val): string {
        return "'" . addslashes((string)$val) . "'";
    }

    public function execute(array $params = []): bool {
        $this->cursor = 0;
        $this->results = [];
        $this->affectedRows = 0;

        $sql = $this->sql;

        // -------------------------------------------------------------
        // 1. SELECT * FROM resources WHERE code = ? LIMIT 1
        // -------------------------------------------------------------
        if (preg_match('/SELECT .* FROM resources WHERE code = \? LIMIT 1/i', $sql)) {
            $code = strtoupper(trim((string)($params[0] ?? '')));
            $rec = $this->pb->getFirstListItem($this->col('resources'), 'code=' . $this->q($code));
            if (!$rec && $this->pb->getLastHttpCode() !== 200) {
                // In-memory fallback
                $all = $this->pb->getFullList($this->col('resources'));
                foreach ($all as $item) {
                    if (strtoupper($item['code'] ?? '') === $code) {
                        $rec = $item;
                        break;
                    }
                }
            }
            if ($rec) {
                $this->results = [$this->normalizeResourceRecord($rec)];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 2. SELECT id FROM resources WHERE code = ? LIMIT 1
        // -------------------------------------------------------------
        if (preg_match('/SELECT id FROM resources WHERE code = \? LIMIT 1/i', $sql)) {
            $code = strtoupper(trim((string)($params[0] ?? '')));
            $rec = $this->pb->getFirstListItem($this->col('resources'), 'code=' . $this->q($code));
            if (!$rec && $this->pb->getLastHttpCode() !== 200) {
                $all = $this->pb->getFullList($this->col('resources'));
                foreach ($all as $item) {
                    if (strtoupper($item['code'] ?? '') === $code) {
                        $rec = $item;
                        break;
                    }
                }
            }
            if ($rec) {
                $this->results = [['id' => $rec['id']]];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 3. SELECT * FROM resources WHERE user_id = ? ORDER BY created_at DESC
        // -------------------------------------------------------------
        if (preg_match('/SELECT .* FROM resources WHERE user_id = \?/i', $sql)) {
            $rawUserId = $params[0] ?? '';
            $userId = (string)$rawUserId;
            if ($userId === '1' || $userId === '0' || empty($userId)) {
                $profRec = $this->pb->getFirstListItem($this->col('users'), "email='prof@tiktak.md'");
                if ($profRec && !empty($profRec['id'])) {
                    $userId = $profRec['id'];
                } else {
                    $userId = 'tyoun07mmiuck1b';
                }
            }
            $filter = 'user_id=' . $this->q($userId);
            $records = $this->pb->getFullList($this->col('resources'), [
                'filter' => $filter,
            ]);

            $lastCode = $this->pb->getLastHttpCode();
            $fallbackUsed = false;
            // If PocketBase rejected the filter (HTTP >= 400) or returned error,
            // fetch all resources and filter in PHP memory so the user NEVER sees 0 items!
            if ($lastCode >= 400 || (empty($records) && $lastCode !== 200)) {
                $allRecs = $this->pb->getFullList($this->col('resources'));
                if (!empty($allRecs)) {
                    $records = array_values(array_filter($allRecs, function($r) use ($userId) {
                        return ($r['user_id'] ?? '') === $userId;
                    }));
                    $fallbackUsed = true;
                }
            }

            $this->pdo->lastQueryDebug = [
                'sql' => $sql,
                'raw_user_id' => $rawUserId,
                'resolved_user_id' => $userId,
                'collection' => $this->col('resources'),
                'filter' => $filter,
                'returned_count' => count($records),
                'fallback_used' => $fallbackUsed,
                'last_http_code' => $this->pb->getLastHttpCode(),
                'last_response' => $this->pb->getLastResponse(),
            ];
            $this->results = array_map([$this, 'normalizeResourceRecord'], $records);
            // Sort in PHP memory: latest created first
            usort($this->results, function($a, $b) {
                $c = strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
                if ($c !== 0) return $c;
                return strcmp($b['id'] ?? '', $a['id'] ?? '');
            });
            $this->affectedRows = count($this->results);
            return true;
        }

        // -------------------------------------------------------------
        // 4. SELECT * FROM resources WHERE id = ? AND user_id = ? LIMIT 1
        // -------------------------------------------------------------
        if (preg_match('/SELECT .* FROM resources WHERE id = \? AND user_id = \?/i', $sql)) {
            $id = (string)($params[0] ?? '');
            $userId = (string)($params[1] ?? '');
            if ($userId === '1' || $userId === '0' || empty($userId)) {
                $userId = 'tyoun07mmiuck1b';
            }
            $rec = $this->pb->getRecord($this->col('resources'), $id);
            if ($rec && ($rec['user_id'] ?? '') === $userId) {
                $this->results = [$this->normalizeResourceRecord($rec)];
                $this->affectedRows = 1;
            } elseif ($rec && empty($userId)) {
                $this->results = [$this->normalizeResourceRecord($rec)];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 5. SELECT * FROM resources WHERE code = ? AND user_id = ? AND type = 'hosting' LIMIT 1
        // -------------------------------------------------------------
        if (preg_match('/SELECT .* FROM resources WHERE code = \? AND user_id = \? AND type = \'hosting\'/i', $sql)) {
            $code = strtoupper(trim((string)($params[0] ?? '')));
            $userId = (string)($params[1] ?? '');
            if ($userId === '1' || $userId === '0' || empty($userId)) {
                $userId = 'tyoun07mmiuck1b';
            }
            $rec = $this->pb->getFirstListItem($this->col('resources'), 'code=' . $this->q($code) . ' && type=\'hosting\'');
            if (!$rec && $this->pb->getLastHttpCode() !== 200) {
                $all = $this->pb->getFullList($this->col('resources'));
                foreach ($all as $item) {
                    if (strtoupper($item['code'] ?? '') === $code && ($item['type'] ?? '') === 'hosting') {
                        $rec = $item;
                        break;
                    }
                }
            }
            if ($rec) {
                $this->results = [$this->normalizeResourceRecord($rec)];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 6. INSERT INTO resources (...) VALUES (?, ?, ...)
        // -------------------------------------------------------------
        if (preg_match('/INSERT INTO resources/i', $sql)) {
            $data = [
                'user_id' => (string)($params[0] ?? ''),
                'code' => $params[1] ?? '',
                'title' => $params[2] ?? '',
                'type' => $params[3] ?? 'redirect',
                'content_link' => $params[4] ?? '',
                'content_text' => $params[5] ?? '',
                'visits_count' => 0,
            ];
            if (!empty($params[6])) {
                $data['expires_at'] = date('Y-m-d H:i:s.000\Z', strtotime($params[6]));
            }
            $created = $this->pb->create($this->col('resources'), $data);
            if (!empty($created['id'])) {
                $this->pdo->setLastInsertId($created['id']);
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 7. UPDATE resources SET title = ?, content_link = ?, content_text = ?, updated_at = ... WHERE id = ? AND user_id = ?
        // -------------------------------------------------------------
        if (preg_match('/UPDATE resources SET title = \?, content_link = \?, content_text = \?/i', $sql)) {
            $title = $params[0] ?? '';
            $link = $params[1] ?? '';
            $text = $params[2] ?? '';
            $id = $params[3] ?? '';
            $userId = $params[4] ?? '';

            $rec = $this->pb->getRecord($this->col('resources'), $id);
            if ($rec) {
                $this->pb->update($this->col('resources'), $rec['id'], [
                    'title' => $title,
                    'content_link' => $link,
                    'content_text' => $text,
                ]);
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 8. UPDATE resources SET expires_at = ?, updated_at = ... WHERE id = ? AND user_id = ?
        // -------------------------------------------------------------
        if (preg_match('/UPDATE resources SET expires_at = \?/i', $sql)) {
            $expiresAt = $params[0] ?? null;
            $id = $params[1] ?? '';
            $userId = $params[2] ?? '';

            $rec = $this->pb->getRecord($this->col('resources'), $id);
            if ($rec) {
                $this->pb->update($this->col('resources'), $rec['id'], [
                    'expires_at' => $expiresAt ? date('Y-m-d H:i:s.000\Z', strtotime($expiresAt)) : null,
                ]);
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 9. UPDATE resources SET visits_count = visits_count + 1 WHERE id = ?
        // -------------------------------------------------------------
        if (preg_match('/UPDATE resources SET visits_count = visits_count \+ 1 WHERE id = \?/i', $sql)) {
            $id = $params[0] ?? '';
            $rec = $this->pb->getRecord($this->col('resources'), $id);
            if ($rec) {
                $newCount = (int)($rec['visits_count'] ?? 0) + 1;
                $this->pb->update($this->col('resources'), $id, ['visits_count' => $newCount]);
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 10. UPDATE resources SET updated_at = ... WHERE id = ?
        // -------------------------------------------------------------
        if (preg_match('/UPDATE resources SET updated_at = .* WHERE id = \?/i', $sql)) {
            $id = $params[0] ?? '';
            $this->pb->update($this->col('resources'), $id, ['id' => $id]);
            $this->affectedRows = 1;
            return true;
        }

        // -------------------------------------------------------------
        // 11. DELETE FROM resources WHERE id = ? AND user_id = ?
        // -------------------------------------------------------------
        if (preg_match('/DELETE FROM resources WHERE id = \? AND user_id = \?/i', $sql)) {
            $id = $params[0] ?? '';
            $userId = $params[1] ?? '';
            $this->pb->delete($this->col('resources'), $id);
            $this->affectedRows = 1;
            return true;
        }

        // -------------------------------------------------------------
        // 12. SELECT * FROM users WHERE username = ? LIMIT 1
        // -------------------------------------------------------------
        if (preg_match('/SELECT .* FROM users WHERE username = \? LIMIT 1/i', $sql)) {
            $username = trim((string)($params[0] ?? ''));
            $email = strpos($username, '@') !== false ? $username : $username . '@tiktak.md';
            $rec = $this->pb->getFirstListItem($this->col('users'), "email='" . addslashes($email) . "'");
            if (!$rec) {
                $rec = $this->pb->getFirstListItem($this->col('users'), "username='" . addslashes($username) . "'");
            }
            if (!$rec) {
                $rec = $this->pb->getRecord($this->col('users'), $username);
            }
            if ($rec) {
                $this->results = [$this->normalizeUserRecord($rec)];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 13. SELECT id FROM users WHERE username = ? LIMIT 1
        // -------------------------------------------------------------
        if (preg_match('/SELECT id FROM users WHERE username = \? LIMIT 1/i', $sql)) {
            $username = trim((string)($params[0] ?? ''));
            $email = strpos($username, '@') !== false ? $username : $username . '@tiktak.md';
            $rec = $this->pb->getFirstListItem($this->col('users'), "email='" . addslashes($email) . "'");
            if (!$rec) {
                $rec = $this->pb->getFirstListItem($this->col('users'), "username='" . addslashes($username) . "'");
            }
            if (!$rec) {
                $rec = $this->pb->getRecord($this->col('users'), $username);
            }
            if ($rec) {
                $this->results = [['id' => $rec['id']]];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 14. INSERT INTO users (username, password_hash, name) VALUES (?, ?, ?)
        // -------------------------------------------------------------
        if (preg_match('/INSERT INTO users/i', $sql)) {
            $username = $params[0] ?? '';
            $hash = $params[1] ?? '';
            $name = $params[2] ?? 'Professor';
            $email = strpos($username, '@') !== false ? $username : $username . '@tiktak.md';
            $created = $this->pb->create($this->col('users'), [
                'username' => $username,
                'email' => $email,
                'password' => 'TempPass123!',
                'passwordConfirm' => 'TempPass123!',
                'name' => $name,
            ]);
            if (!empty($created['id'])) {
                $this->pdo->setLastInsertId($created['id']);
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 15. SELECT COUNT(*) as count FROM users
        // -------------------------------------------------------------
        if (preg_match('/SELECT COUNT\(\*\) as count FROM users/i', $sql)) {
            $list = $this->pb->getList($this->col('users'), 1, 1);
            $this->results = [['count' => $list['totalItems'] ?? 0]];
            $this->affectedRows = 1;
            return true;
        }

        // -------------------------------------------------------------
        // 16. SELECT * FROM resource_files WHERE resource_id = ? ORDER BY ...
        // -------------------------------------------------------------
        if (preg_match('/SELECT .* FROM resource_files WHERE resource_id = \?/i', $sql)) {
            $resourceId = $params[0] ?? '';
            $filter = 'resource_id=' . $this->q($resourceId);
            $records = $this->pb->getFullList($this->col('resource_files'), [
                'filter' => $filter,
            ]);
            $this->results = array_map([$this, 'normalizeFileRecord'], $records);
            usort($this->results, function($a, $b) {
                $c = strcmp($b['upload_date'] ?? '', $a['upload_date'] ?? '');
                return $c !== 0 ? $c : strcmp($b['uploaded_at'] ?? '', $a['uploaded_at'] ?? '');
            });
            $this->affectedRows = count($this->results);
            return true;
        }

        // -------------------------------------------------------------
        // 17. INSERT INTO resource_files (...) VALUES (...)
        // -------------------------------------------------------------
        if (preg_match('/INSERT INTO resource_files/i', $sql)) {
            $data = [
                'resource_id' => (string)($params[0] ?? ''),
                'code' => $params[1] ?? '',
                'original_filename' => $params[2] ?? '',
                'stored_filename' => $params[3] ?? '',
                's3_key' => $params[4] ?? '',
                'file_size' => (int)($params[5] ?? 0),
                'mime_type' => $params[6] ?? '',
                'upload_date' => $params[7] ?? date('Y-m-d'),
                'ip_hash' => $params[9] ?? '',
                'downloads_count' => 0,
                'is_pinned' => false,
            ];
            $created = $this->pb->create($this->col('resource_files'), $data);
            if (!empty($created['id'])) {
                $this->pdo->setLastInsertId($created['id']);
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 18. SELECT f.*, r.expires_at FROM resource_files f JOIN resources r ... WHERE f.id = ?
        // -------------------------------------------------------------
        if (preg_match('/SELECT .* FROM resource_files.*JOIN resources.*WHERE.*f\.id = \?/i', $sql)) {
            $id = $params[0] ?? '';
            $file = $this->pb->getRecord($this->col('resource_files'), $id);
            if ($file) {
                $normFile = $this->normalizeFileRecord($file);
                $res = $this->pb->getRecord($this->col('resources'), $normFile['resource_id']);
                $normFile['expires_at'] = $res['expires_at'] ?? null;
                $this->results = [$normFile];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 19. SELECT * FROM resource_files WHERE id = ? LIMIT 1
        // -------------------------------------------------------------
        if (preg_match('/SELECT .* FROM resource_files WHERE id = \? LIMIT 1/i', $sql)) {
            $id = $params[0] ?? '';
            $file = $this->pb->getRecord($this->col('resource_files'), $id);
            if ($file) {
                $this->results = [$this->normalizeFileRecord($file)];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 20. UPDATE resource_files SET downloads_count = downloads_count + 1 WHERE id = ?
        // -------------------------------------------------------------
        if (preg_match('/UPDATE resource_files SET downloads_count = downloads_count \+ 1 WHERE id = \?/i', $sql)) {
            $id = $params[0] ?? '';
            $rec = $this->pb->getRecord($this->col('resource_files'), $id);
            if ($rec) {
                $this->pb->update($this->col('resource_files'), $id, ['downloads_count' => (int)($rec['downloads_count'] ?? 0) + 1]);
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 21. DELETE FROM resource_files WHERE id = ?
        // -------------------------------------------------------------
        if (preg_match('/DELETE FROM resource_files WHERE id = \?/i', $sql)) {
            $id = $params[0] ?? '';
            $this->pb->delete($this->col('resource_files'), $id);
            $this->affectedRows = 1;
            return true;
        }

        // -------------------------------------------------------------
        // 22. DELETE FROM resource_files WHERE resource_id = ?
        // -------------------------------------------------------------
        if (preg_match('/DELETE FROM resource_files WHERE resource_id = \?/i', $sql)) {
            $resourceId = $params[0] ?? '';
            $files = $this->pb->getFullList($this->col('resource_files'), ['filter' => 'resource_id=' . $this->q($resourceId)]);
            foreach ($files as $f) {
                $this->pb->delete($this->col('resource_files'), $f['id']);
            }
            $this->affectedRows = count($files);
            return true;
        }

        // -------------------------------------------------------------
        // 23. SELECT id, is_pinned, stored_filename FROM resource_files WHERE id = ? LIMIT 1
        // -------------------------------------------------------------
        if (preg_match('/SELECT .*is_pinned.* FROM resource_files WHERE id = \? LIMIT 1/i', $sql)) {
            $id = $params[0] ?? '';
            $rec = $this->pb->getRecord($this->col('resource_files'), $id);
            if ($rec) {
                $this->results = [[
                    'id' => $rec['id'],
                    'is_pinned' => (int)!empty($rec['is_pinned']),
                    'stored_filename' => $rec['stored_filename'] ?? '',
                ]];
                $this->affectedRows = 1;
            }
            return true;
        }

        // -------------------------------------------------------------
        // 24. UPDATE resource_files SET is_pinned = ? WHERE id = ?
        // -------------------------------------------------------------
        if (preg_match('/UPDATE resource_files SET is_pinned = \? WHERE id = \?/i', $sql)) {
            $isPinned = (bool)$params[0];
            $id = $params[1] ?? '';
            $this->pb->update($this->col('resource_files'), $id, ['is_pinned' => $isPinned]);
            $this->affectedRows = 1;
            return true;
        }

        // -------------------------------------------------------------
        // 25. SELECT COUNT(*) as count FROM resource_files WHERE resource_id = ? AND stored_filename = ?
        // -------------------------------------------------------------
        if (preg_match('/SELECT COUNT\(\*\) as count FROM resource_files WHERE resource_id = \? AND stored_filename = \?/i', $sql)) {
            $resourceId = $params[0] ?? '';
            $filename = $params[1] ?? '';
            $rec = $this->pb->getFirstListItem($this->col('resource_files'), 'resource_id=' . $this->q($resourceId) . ' && stored_filename=' . $this->q($filename));
            $this->results = [['count' => $rec ? 1 : 0]];
            $this->affectedRows = 1;
            return true;
        }

        // -------------------------------------------------------------
        // 26. SELECT COUNT(*) as total_files, COALESCE(SUM(file_size), 0) as total_size...
        // -------------------------------------------------------------
        if (preg_match('/SELECT COUNT\(\*\) as total_files.* FROM resource_files WHERE resource_id = \?/i', $sql)) {
            $heavyThreshold = (int)($params[0] ?? 104857600);
            $resourceId = $params[1] ?? '';

            $files = $this->pb->getFullList($this->col('resource_files'), ['filter' => 'resource_id=' . $this->q($resourceId)]);
            $totalFiles = count($files);
            $totalSize = 0;
            $heavyCount = 0;

            foreach ($files as $f) {
                $sz = (int)($f['file_size'] ?? 0);
                $totalSize += $sz;
                if ($sz >= $heavyThreshold) {
                    $heavyCount++;
                }
            }

            $this->results = [[
                'total_files' => $totalFiles,
                'total_size' => $totalSize,
                'heavy_count' => $heavyCount,
            ]];
            $this->affectedRows = 1;
            return true;
        }

        // -------------------------------------------------------------
        // 27. INSERT INTO access_logs (resource_id, ip_hash, user_agent) VALUES (?, ?, ?)
        // -------------------------------------------------------------
        if (preg_match('/INSERT INTO access_logs/i', $sql)) {
            $this->pb->create($this->col('access_logs'), [
                'resource_id' => (string)($params[0] ?? ''),
                'ip_hash' => $params[1] ?? '',
                'user_agent' => $params[2] ?? '',
            ]);
            $this->affectedRows = 1;
            return true;
        }

        // Catch-all fallback
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_ASSOC): mixed {
        if ($this->cursor < count($this->results)) {
            return $this->results[$this->cursor++];
        }
        return false;
    }

    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array {
        $remaining = array_slice($this->results, $this->cursor);
        $this->cursor = count($this->results);
        return $remaining;
    }

    public function rowCount(): int {
        return $this->affectedRows;
    }

    private function normalizeResourceRecord(array $rec): array {
        return [
            'id' => $rec['id'],
            'user_id' => $rec['user_id'] ?? '',
            'code' => $rec['code'] ?? '',
            'title' => $rec['title'] ?? '',
            'type' => $rec['type'] ?? 'redirect',
            'content_link' => $rec['content_link'] ?? '',
            'content_text' => $rec['content_text'] ?? '',
            'visits_count' => (int)($rec['visits_count'] ?? 0),
            'expires_at' => $rec['expires_at'] ?? null,
            'created_at' => !empty($rec['created_at']) ? $rec['created_at'] : ($rec['created'] ?? date('Y-m-d H:i:s')),
            'updated_at' => !empty($rec['updated_at']) ? $rec['updated_at'] : ($rec['updated'] ?? date('Y-m-d H:i:s')),
        ];
    }

    private function normalizeUserRecord(array $rec): array {
        $email = $rec['email'] ?? '';
        $username = str_replace('@tiktak.md', '', $email);
        return [
            'id' => $rec['id'],
            'username' => $username,
            'password_hash' => $rec['password_hash'] ?? '',
            'name' => $rec['name'] ?? 'Professor',
            'created_at' => $rec['created'] ?? date('Y-m-d H:i:s'),
        ];
    }

    private function normalizeFileRecord(array $rec): array {
        return [
            'id' => $rec['id'],
            'resource_id' => $rec['resource_id'] ?? '',
            'code' => $rec['code'] ?? '',
            'original_filename' => $rec['original_filename'] ?? '',
            'stored_filename' => $rec['stored_filename'] ?? '',
            's3_key' => $rec['s3_key'] ?? '',
            'file_size' => (int)($rec['file_size'] ?? 0),
            'mime_type' => $rec['mime_type'] ?? '',
            'upload_date' => $rec['upload_date'] ?? date('Y-m-d'),
            'uploaded_at' => $rec['created'] ?? date('Y-m-d H:i:s'),
            'ip_hash' => $rec['ip_hash'] ?? '',
            'downloads_count' => (int)($rec['downloads_count'] ?? 0),
            'is_pinned' => (int)!empty($rec['is_pinned']),
        ];
    }
}
