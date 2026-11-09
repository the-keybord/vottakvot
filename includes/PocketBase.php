<?php
/**
 * TikTak - PocketBase Client & API Wrapper
 * Handles communication with the PocketBase backend at https://db.zece.info
 */

require_once __DIR__ . '/../config/config.php';

class PocketBase {
    private string $baseUrl;
    private ?string $token = null;
    private static ?PocketBase $instance = null;
    private mixed $lastResponse = null;
    private int $lastHttpCode = 0;
    private string $lastEndpoint = '';

    public function __construct(?string $baseUrl = null, ?string $token = null) {
        $this->baseUrl = rtrim($baseUrl ?: (defined('POCKETBASE_URL') ? POCKETBASE_URL : 'https://db.zece.info'), '/');
        $this->token = $token;
    }

    /**
     * Singleton instance with auto-auth if admin credentials are provided
     */
    public static function getInstance(): PocketBase {
        if (self::$instance === null) {
            self::$instance = new self();

            // Load cached token if available
            $cachedToken = self::loadCachedToken();
            if ($cachedToken) {
                self::$instance->setToken($cachedToken);
            }

            // If no token but admin credentials configured, authenticate as superuser
            if (!self::$instance->getToken() && defined('POCKETBASE_ADMIN_EMAIL') && defined('POCKETBASE_ADMIN_PASSWORD')
                && !empty(POCKETBASE_ADMIN_EMAIL) && !empty(POCKETBASE_ADMIN_PASSWORD)) {
                self::$instance->authAsSuperuser(POCKETBASE_ADMIN_EMAIL, POCKETBASE_ADMIN_PASSWORD);
            }
        }
        return self::$instance;
    }

    public function setToken(?string $token): self {
        $this->token = $token;
        return $this;
    }

    public function getToken(): ?string {
        return $this->token;
    }

    public function getLastResponse(): mixed {
        return $this->lastResponse;
    }

    public function getLastHttpCode(): int {
        return $this->lastHttpCode;
    }

    public function getLastEndpoint(): string {
        return $this->lastEndpoint;
    }

    /**
     * Authenticate as Superuser / Admin
     */
    public function authAsSuperuser(string $email, string $password): array {
        // PocketBase v0.23+ superusers endpoint
        $res = $this->request('POST', '/api/collections/_superusers/auth-with-password', [
            'identity' => $email,
            'password' => $password,
        ]);

        // Fallback to older PocketBase (<0.23) /api/admins endpoint
        if (empty($res['token'])) {
            $res = $this->request('POST', '/api/admins/auth-with-password', [
                'identity' => $email,
                'password' => $password,
            ]);
        }

        if (!empty($res['token'])) {
            $this->token = $res['token'];
            self::saveCachedToken($res['token']);
        }

        return $res;
    }

    /**
     * Authenticate standard collection user (e.g. teachers/users)
     */
    public function authWithPassword(string $collection, string $identity, string $password): array {
        $res = $this->request('POST', "/api/collections/{$collection}/auth-with-password", [
            'identity' => $identity,
            'password' => $password,
        ]);

        if (!empty($res['token'])) {
            $this->token = $res['token'];
        }

        return $res;
    }

    /**
     * Get paginated records list
     */
    public function getList(string $collection, int $page = 1, int $perPage = 30, array $params = []): array {
        $params['page'] = $page;
        $params['perPage'] = $perPage;
        if (!empty($params['filter'])) {
            // PocketBase requires string literals to be enclosed in single quotes ('...').
            // Convert double-quoted values to single-quoted literals: e.g. user_id = "abc" -> user_id = 'abc'
            $params['filter'] = preg_replace('/"([^"]*)"/', "'$1'", $params['filter']);
        }
        $query = http_build_query($params);
        return $this->request('GET', "/api/collections/{$collection}/records?" . $query);
    }

    /**
     * Fetch all records matching filter
     */
    public function getFullList(string $collection, array $params = []): array {
        $page = 1;
        $perPage = 100;
        $all = [];
        do {
            $res = $this->getList($collection, $page, $perPage, $params);
            $items = $res['items'] ?? [];
            foreach ($items as $item) {
                $all[] = $item;
            }
            $totalPages = $res['totalPages'] ?? 1;
            $page++;
        } while ($page <= $totalPages && !empty($items));
        return $all;
    }

    /**
     * Get first record matching filter
     */
    public function getFirstListItem(string $collection, string $filter, array $params = []): ?array {
        $params['filter'] = preg_replace('/"([^"]*)"/', "'$1'", $filter);
        $params['page'] = 1;
        $params['perPage'] = 1;
        $res = $this->getList($collection, 1, 1, $params);
        $items = $res['items'] ?? [];
        return !empty($items) ? $items[0] : null;
    }

    /**
     * Get single record by ID
     */
    public function getRecord(string $collection, string $id, array $params = []): ?array {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        $res = $this->request('GET', "/api/collections/{$collection}/records/{$id}" . $query);
        return !empty($res['id']) ? $res : null;
    }

    /**
     * Create record
     */
    public function create(string $collection, array $data): array {
        return $this->request('POST', "/api/collections/{$collection}/records", $data);
    }

    /**
     * Update record
     */
    public function update(string $collection, string $id, array $data): array {
        return $this->request('PATCH', "/api/collections/{$collection}/records/{$id}", $data);
    }

    /**
     * Delete record
     */
    public function delete(string $collection, string $id): bool {
        $res = $this->request('DELETE', "/api/collections/{$collection}/records/{$id}");
        return ($res === true || (is_array($res) && empty($res['status'])) || (isset($res['status']) && $res['status'] < 400));
    }

    /**
     * List all collections (admin/superuser)
     */
    public function getCollections(): array {
        $res = $this->request('GET', '/api/collections?page=1&perPage=100');
        return $res['items'] ?? [];
    }

    /**
     * Get single collection by name or ID
     */
    public function getCollection(string $nameOrId): ?array {
        $res = $this->request('GET', "/api/collections/{$nameOrId}");
        return !empty($res['name']) ? $res : null;
    }

    /**
     * Create collection (admin/superuser)
     */
    public function createCollection(array $schema): array {
        return $this->request('POST', '/api/collections', $schema);
    }

    /**
     * Update collection (admin/superuser)
     */
    public function updateCollection(string $nameOrId, array $schema): array {
        return $this->request('PATCH', "/api/collections/{$nameOrId}", $schema);
    }

    /**
     * Send HTTP request to PocketBase REST API
     */
    public function request(string $method, string $path, ?array $data = null) {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
        ];

        if ($this->token) {
            $headers[] = 'Authorization: ' . $this->token;
        }

        if ($method === 'POST' || $method === 'PATCH' || $method === 'PUT') {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data ?? []));
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        $this->lastHttpCode = $httpCode;
        $this->lastEndpoint = $path;

        if ($body === false) {
            $this->lastResponse = ['code' => 500, 'message' => 'cURL Error: ' . $curlErr, 'status' => 500];
            return $this->lastResponse;
        }

        // If 401 Unauthorized or 403 Forbidden with a cached token, retry once with fresh auth
        if (($httpCode === 401 || $httpCode === 403) && !str_contains($path, 'auth-with-password')) {
            @unlink(self::getCachePath());
            $this->token = null;
            if (defined('POCKETBASE_ADMIN_EMAIL') && defined('POCKETBASE_ADMIN_PASSWORD') && !empty(POCKETBASE_ADMIN_EMAIL)) {
                $auth = $this->authAsSuperuser(POCKETBASE_ADMIN_EMAIL, POCKETBASE_ADMIN_PASSWORD);
                if (!empty($auth['token'])) {
                    return $this->request($method, $path, $data);
                }
            }
        }

        if ($httpCode === 204) {
            $this->lastResponse = true;
            return true;
        }

        $decoded = json_decode($body, true);
        $this->lastResponse = $decoded !== null ? $decoded : ['status' => $httpCode, 'raw' => $body];
        return $this->lastResponse;
    }

    /**
     * Token caching helpers
     */
    private static function getCachePath(): string {
        return sys_get_temp_dir() . '/tiktak_pb_token.json';
    }

    private static function saveCachedToken(string $token): void {
        $data = [
            'token' => $token,
            'time' => time(),
        ];
        @file_put_contents(self::getCachePath(), json_encode($data));
    }

    private static function loadCachedToken(): ?string {
        $file = self::getCachePath();
        if (!file_exists($file)) return null;
        $data = json_decode(@file_get_contents($file) ?: '', true);
        if (!$data || empty($data['token']) || (time() - ($data['time'] ?? 0)) > 86400) {
            @unlink($file);
            return null;
        }
        return $data['token'];
    }
}

