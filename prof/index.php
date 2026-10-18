<?php
/**
 * TikTak - Professor Portal Router
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header("Location: /prof/dashboard.php");
} else {
    header("Location: /prof/login.php");
}
exit;
