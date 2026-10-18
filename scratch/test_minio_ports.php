<?php
$urls = [
    'http://minio-f4qsjb22cxs0wdyypygkj480.85.120.144.58.sslip.io:9000/minio/health/live',
    'http://minio-f4qsjb22cxs0wdyypygkj480.85.120.144.58.sslip.io/minio/health/live',
    'https://minio-f4qsjb22cxs0wdyypygkj480.85.120.144.58.sslip.io/minio/health/live',
    'http://85.120.144.58:9000/minio/health/live',
    'http://85.120.144.58:9000/tiktak'
];

foreach ($urls as $u) {
    $ch = curl_init($u);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    echo "URL: $u => HTTP $httpCode" . ($err ? " (Error: $err)" : "") . "\n";
}
