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

    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long.']);
        exit;
    }

    if (empty($name)) {
        $name = 'Professor ' . ucfirst($username);
    }

    if (defined('DB_DRIVER') && DB_DRIVER === 'pocketbase') {
        require_once __DIR__ . '/../includes/PocketBase.php';
        $pb = PocketBase::getInstance();
        $pfx = defined('POCKETBASE_TABLE_PREFIX') ? POCKETBASE_TABLE_PREFIX : 'tiktak_';
        $email = strpos($username, '@') !== false ? $username : $username . '@tiktak.md';

        $existing = $pb->getFirstListItem($pfx . 'users', 'email = "' . addslashes($email) . '"');
        if ($existing) {
            echo json_encode(['success' => false, 'error' => "The username '{$username}' is already taken."]);
            exit;
        }

        $created = $pb->create($pfx . 'users', [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'passwordConfirm' => $password,
            'name' => $name,
        ]);

        if (!empty($created['id'])) {
            echo json_encode([
                'success' => true,
                'message' => "Teacher account '{$username}' created successfully!"
            ]);
            exit;
        } else {
            $err = $created['message'] ?? 'Failed to create user in PocketBase';
            echo json_encode(['success' => false, 'error' => $err]);
            exit;
        }
    }

    // Legacy MySQL fallback
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

$authenticated = false;
$userSession = [];

if (defined('DB_DRIVER') && DB_DRIVER === 'pocketbase') {
    require_once __DIR__ . '/../includes/PocketBase.php';
    $pb = PocketBase::getInstance();
    $pfx = defined('POCKETBASE_TABLE_PREFIX') ? POCKETBASE_TABLE_PREFIX : 'tiktak_';

    // 1. Try auth as tiktak_users with input as identity
    $authRes = $pb->authWithPassword($pfx . 'users', $username, $password);

    // 2. If no '@' in username, also try username@tiktak.md
    if (empty($authRes['token']) && strpos($username, '@') === false) {
        $authRes = $pb->authWithPassword($pfx . 'users', $username . '@tiktak.md', $password);
    }

    // 3. Fallback: Check if superuser credentials were used
    if (empty($authRes['token'])) {
        $superAuth = $pb->authAsSuperuser($username, $password);
        if (!empty($superAuth['token'])) {
            $profRec = $pb->getFirstListItem($pfx . 'users', 'email = "prof@tiktak.md"');
            $userSession = [
                'id' => $profRec['id'] ?? 'tyoun07mmiuck1b',
                'username' => 'prof',
                'name' => 'Professor',
            ];
            $authenticated = true;
        }
    } else {
        $rec = $authRes['record'] ?? [];
        $email = $rec['email'] ?? '';
        $uname = !empty($rec['username']) ? $rec['username'] : str_replace('@tiktak.md', '', $email);
        $userSession = [
            'id' => $rec['id'],
            'username' => $uname,
            'name' => $rec['name'] ?? 'Professor',
        ];
        $authenticated = true;
    }
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $userSession = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name']
        ];
        $authenticated = true;
    }
}

if ($authenticated && !empty($userSession)) {
    $_SESSION['tiktak_user_id'] = $userSession['id'];
    $_SESSION['tiktak_username'] = $userSession['username'];
    $_SESSION['tiktak_name'] = $userSession['name'];

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $userSession['id'],
            'username' => $userSession['username'],
            'name' => $userSession['name']
        ],
        'redirect' => $redirect
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid email/username or password']);
