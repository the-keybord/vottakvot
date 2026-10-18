<?php
/**
 * TikTak - Authentication API / Controller
 * Supports Teacher Login and Teacher Creation
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? 'login';
$pdo = get_db_connection();

// -------------------------------------------------------------
// 1. CREATE TEACHER (Strictly requires active professor session)
// -------------------------------------------------------------
if ($action === 'create_teacher') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Only logged-in teachers can create new teacher accounts.']);
        exit;
    }

    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    $name = trim($input['name'] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Please provide both username and password.']);
        exit;
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        echo json_encode(['success' => false, 'error' => 'Username must be between 3 and 50 characters.']);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
        echo json_encode(['success' => false, 'error' => 'Username can only contain letters, numbers, dots, and hyphens.']);
        exit;
    }

    if (strlen($password) < 4) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 4 characters long.']);
        exit;
    }

    if (empty($name)) {
        $name = 'Professor ' . ucfirst($username);
    }

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => "The username '{$username}' is already taken."]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $insert = $pdo->prepare("INSERT INTO users (username, password_hash, name) VALUES (?, ?, ?)");
    $insert->execute([$username, $passwordHash, $name]);

    echo json_encode([
        'success' => true,
        'message' => "Teacher account '{$username}' created successfully!"
    ]);
    exit;
}

// -------------------------------------------------------------
// 2. TEACHER LOGIN
// -------------------------------------------------------------
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');
$redirect = trim($input['redirect'] ?? '/prof/dashboard.php');
if (!str_starts_with($redirect, '/')) {
    $redirect = '/prof/dashboard.php';
}

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Please provide both username and password']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['tiktak_user_id'] = $user['id'];
    $_SESSION['tiktak_username'] = $user['username'];
    $_SESSION['tiktak_name'] = $user['name'];

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name']
        ],
        'redirect' => $redirect
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
