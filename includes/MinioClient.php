<?php
/**
 * TikTak - MinIO / S3 Storage Client
 * Lightweight, zero-dependency AWS Signature Version 4 implementation for MinIO and S3
 */

require_once __DIR__ . '/../config/config.php';

class MinioClient {
    private string $endpoint;
    private int $port;
    private bool $useSsl;
    private string $bucket;
    private string $accessKey;
    private string $secretKey;
    private string $region;
    private string $localFallbackDir;
    private ?bool $isMinioReachable = null;

    public function __construct(
        ?string $endpoint = null,
        ?int $port = null,
        ?bool $useSsl = null,
        ?string $bucket = null,
        ?string $accessKey = null,
        ?string $secretKey = null,
        ?string $region = null
    ) {
        $this->endpoint = $endpoint ?? MINIO_ENDPOINT;
        $this->port = $port ?? (int)MINIO_PORT;
        $this->useSsl = $useSsl ?? MINIO_USE_SSL;
        $this->bucket = $bucket ?? MINIO_BUCKET;
        $this->accessKey = $accessKey ?? MINIO_ACCESS_KEY;
        $this->secretKey = $secretKey ?? MINIO_SECRET_KEY;
        $this->region = $region ?? MINIO_REGION;
        $this->localFallbackDir = TIKTAK_ROOT . '/data/storage/' . $this->bucket;
    }

    public static function getInstance(): self {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public function getBucket(): string {
        return $this->bucket;
    }

    /**
     * Build base URL for the MinIO host
     */
    private function getBaseUrl(): string {
        $protocol = $this->useSsl ? 'https' : 'http';
        $portSuffix = '';
        if (($this->useSsl && $this->port !== 443) || (!$this->useSsl && $this->port !== 80)) {
            $portSuffix = ':' . $this->port;
        }
        return "{$protocol}://{$this->endpoint}{$portSuffix}";
    }

    /**
     * AWS Signature V4 request header generator
     */
    private function getV4Headers(
        string $method,
        string $uriPath,
        array $queryParams = [],
        string $payloadHash = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', // empty sha256
        array $extraHeaders = []
    ): array {
        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);

        $hostHeader = $this->endpoint;
        if (($this->useSsl && $this->port !== 443) || (!$this->useSsl && $this->port !== 80)) {
            $hostHeader .= ':' . $this->port;
        }

        $headers = array_merge([
            'host' => $hostHeader,
            'x-amz-date' => $amzDate,
            'x-amz-content-sha256' => $payloadHash,
        ], $extraHeaders);

        // Sort query parameters
        ksort($queryParams);
        $canonicalQueryArr = [];
        foreach ($queryParams as $k => $v) {
            $canonicalQueryArr[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        $canonicalQueryStr = implode('&', $canonicalQueryArr);

        // Lowercase and sort headers
        $canonicalHeadersArr = [];
        $signedHeadersArr = [];
        $normalizedHeaders = [];
        foreach ($headers as $k => $v) {
            $normalizedHeaders[strtolower(trim($k))] = trim((string)$v);
        }
        ksort($normalizedHeaders);
        foreach ($normalizedHeaders as $k => $v) {
            $canonicalHeadersArr[] = "{$k}:{$v}";
            $signedHeadersArr[] = $k;
        }
        $canonicalHeadersStr = implode("\n", $canonicalHeadersArr) . "\n";
        $signedHeadersStr = implode(';', $signedHeadersArr);

        // Canonical Request
        $canonicalRequest = implode("\n", [
            strtoupper($method),
            $uriPath,
            $canonicalQueryStr,
            $canonicalHeadersStr,
            $signedHeadersStr,
            $payloadHash
        ]);

        // String to sign
        $credentialScope = "{$dateStamp}/{$this->region}/s3/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest)
        ]);

        // Calculate Signature
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeadersStr}, Signature={$signature}";

        $outHeaders = [];
        foreach ($headers as $k => $v) {
            $outHeaders[] = "{$k}: {$v}";
        }
        $outHeaders[] = "Authorization: {$authHeader}";

        return $outHeaders;
    }

    /**
     * Ensure the target bucket exists, create if not present
     */
    public function ensureBucket(): bool {
        try {
            $uriPath = '/' . rawurlencode($this->bucket);
            $url = $this->getBaseUrl() . $uriPath;

            // Head bucket
            $headers = $this->getV4Headers('HEAD', $uriPath);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $this->isMinioReachable = true;
                return true;
            }

            // Create bucket with PUT
            $headers = $this->getV4Headers('PUT', $uriPath);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 || $httpCode === 204 || $httpCode === 409) {
                $this->isMinioReachable = true;
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->isMinioReachable = false;
            return false;
        }
    }

    /**
     * Upload a file or string content to MinIO under objectKey
     */
    public function putObject(string $objectKey, string $sourceFilePathOrContent, string $mimeType = 'application/octet-stream', bool $isContent = false): bool {
        $objectKey = ltrim($objectKey, '/');

        // Verify if MinIO is reachable or test bucket
        if ($this->isMinioReachable === null) {
            $this->ensureBucket();
        }

        if ($this->isMinioReachable !== false) {
            try {
                // Encode key components
                $keyParts = explode('/', $objectKey);
                $encodedKeyParts = array_map('rawurlencode', $keyParts);
                $uriPath = '/' . rawurlencode($this->bucket) . '/' . implode('/', $encodedKeyParts);
                $url = $this->getBaseUrl() . $uriPath;

                if ($isContent) {
                    $payload = $sourceFilePathOrContent;
                    $payloadLength = strlen($payload);
                    $payloadHash = hash('sha256', $payload);
                } else {
                    if (!file_exists($sourceFilePathOrContent)) {
                        return false;
                    }
                    $payloadLength = filesize($sourceFilePathOrContent);
                    $payloadHash = hash_file('sha256', $sourceFilePathOrContent);
                }

                $extraHeaders = [
                    'content-type' => $mimeType,
                    'content-length' => (string)$payloadLength,
                ];

                $headers = $this->getV4Headers('PUT', $uriPath, [], $payloadHash, $extraHeaders);

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => 120,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ]);

                if ($isContent) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                } else {
                    $fp = fopen($sourceFilePathOrContent, 'rb');
                    curl_setopt($ch, CURLOPT_INFILE, $fp);
                    curl_setopt($ch, CURLOPT_INFILESIZE, $payloadLength);
                    curl_setopt($ch, CURLOPT_UPLOAD, true);
                }

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                if (isset($fp) && is_resource($fp)) {
                    fclose($fp);
                }
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Fall through to local fallback
            }
        }

        // Local storage fallback
        $localPath = $this->localFallbackDir . '/' . $objectKey;
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if ($isContent) {
            return file_put_contents($localPath, $sourceFilePathOrContent) !== false;
        } else {
            return copy($sourceFilePathOrContent, $localPath);
        }
    }

    /**
     * Download or stream object from MinIO/S3 directly to PHP output
     */
    public function streamObjectToBrowser(string $objectKey, string $filename, string $mimeType = 'application/octet-stream'): void {
        $objectKey = ltrim($objectKey, '/');

        // Check local storage fallback first if file exists locally
        $localPath = $this->localFallbackDir . '/' . $objectKey;
        if (file_exists($localPath)) {
            $fileSize = filesize($localPath);
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
            header('Content-Length: ' . $fileSize);
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Accept-Ranges: bytes');
            readfile($localPath);
            exit;
        }

        // Fetch from MinIO
        $keyParts = explode('/', $objectKey);
        $encodedKeyParts = array_map('rawurlencode', $keyParts);
        $uriPath = '/' . rawurlencode($this->bucket) . '/' . implode('/', $encodedKeyParts);
        $url = $this->getBaseUrl() . $uriPath;

        $headers = $this->getV4Headers('GET', $uriPath);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Accept-Ranges: bytes');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HEADER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                echo $data;
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                return strlen($data);
            }
        ]);

        curl_exec($ch);
        curl_close($ch);
        exit;
    }

    /**
     * Delete an object from MinIO / S3
     */
    public function deleteObject(string $objectKey): bool {
        $objectKey = ltrim($objectKey, '/');

        // Remove local fallback if exists
        $localPath = $this->localFallbackDir . '/' . $objectKey;
        if (file_exists($localPath)) {
            @unlink($localPath);
        }

        try {
            $keyParts = explode('/', $objectKey);
            $encodedKeyParts = array_map('rawurlencode', $keyParts);
            $uriPath = '/' . rawurlencode($this->bucket) . '/' . implode('/', $encodedKeyParts);
            $url = $this->getBaseUrl() . $uriPath;

            $headers = $this->getV4Headers('DELETE', $uriPath);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($httpCode === 200 || $httpCode === 204);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Delete all objects in a code folder prefix (e.g. "ABCDEF/")
     */
    public function deleteFolder(string $prefix): bool {
        $prefix = trim($prefix, '/') . '/';

        // Clean local fallback directory
        $localFolder = $this->localFallbackDir . '/' . rtrim($prefix, '/');
        if (is_dir($localFolder)) {
            $files = glob($localFolder . '/*');
            foreach ($files as $f) {
                if (is_file($f)) @unlink($f);
            }
            @rmdir($localFolder);
        }

        // MinIO list & delete
        try {
            $uriPath = '/' . rawurlencode($this->bucket);
            $queryParams = ['prefix' => $prefix];
            $url = $this->getBaseUrl() . $uriPath . '?prefix=' . rawurlencode($prefix);

            $headers = $this->getV4Headers('GET', $uriPath, $queryParams);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $xmlResponse = curl_exec($ch);
            curl_close($ch);

            if ($xmlResponse && preg_match_all('~<Key>(.*?)</Key>~i', $xmlResponse, $matches)) {
                foreach ($matches[1] as $key) {
                    $this->deleteObject(html_entity_decode($key));
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
