<?php
/**
 * TikTak - Professor Logout
 */

require_once __DIR__ . '/../config/config.php';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

$redirect = $_GET['redirect'] ?? '/prof/login.php';
// Prevent open redirects
if (!str_starts_with($redirect, '/')) {
    $redirect = '/prof/login.php';
}

header("Location: " . $redirect);
exit;

