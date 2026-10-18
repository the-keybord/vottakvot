<?php
/**
 * TikTak - Local Dev Router for PHP Built-in Server
 * Usage: php -S 127.0.0.1:8000 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// Serve static assets or existing PHP files directly
if ($uri !== '/' && file_exists($file)) {
    if (is_file($file)) {
        return false;
    }
    if (is_dir($file)) {
        // Enforce trailing slash on directories for proper relative URL resolution
        if (substr($uri, -1) !== '/') {
            $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
            header("Location: " . $uri . '/' . $queryString, true, 302);
            exit;
        }
        if (file_exists($file . '/index.php')) {
            require $file . '/index.php';
            exit;
        }
        if (file_exists($file . '/index.html')) {
            require $file . '/index.html';
            exit;
        }
    }
}

// Route /prof or /prof/
if ($uri === '/prof' || $uri === '/prof/') {
    require __DIR__ . '/prof/index.php';
    exit;
}

// Route /code=XXXXXX
if (preg_match('~^/code=([a-zA-Z0-9]{3,10})/?$~i', $uri, $matches)) {
    $_GET['code'] = $matches[1];
    require __DIR__ . '/index.php';
    exit;
}

// Route 6-letter direct code /ABCDEF
if (preg_match('~^/([a-zA-Z0-9]{6})/?$~i', $uri, $matches) && !in_array(strtolower($matches[1]), ['assets', 'config', 'scratch', 'vendor'])) {
    $_GET['code'] = $matches[1];
    require __DIR__ . '/index.php';
    exit;
}

// Fallback to index.php
require __DIR__ . '/index.php';
