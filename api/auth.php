<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleCheck();
        break;
    default:
        jsonError('Unknown action', 404);
}

function ensureDemoUser() {
    $db = getDB();
    $result = $db->query("SELECT COUNT(*) as cnt FROM fscrm_users");
    $row = $result->fetch_assoc();
    if ((int)$row['cnt'] === 0) {
        $hash = password_hash('demo123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO fscrm_users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $name = 'Admin User';
        $email = 'admin@demo.com';
        $role = 'admin';
        $stmt->bind_param('ssss', $name, $email, $hash, $role);
        $stmt->execute();
    }
}

function handleLogin() {
    $db = getDB();
    ensureDemoUser();

    $input = getJsonInput();
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        jsonError('Email and password are required');
    }

    $stmt = $db->prepare("SELECT id, name, email, password, role FROM fscrm_users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonError('Invalid email or password', 401);
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    jsonResponse([
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);
}

function handleLogout() {
    $_SESSION = [];
    session_destroy();
    jsonResponse(['message' => 'Logged out successfully']);
}

function handleCheck() {
    if (!empty($_SESSION['user_id'])) {
        jsonResponse([
            'authed' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
            ]
        ]);
    } else {
        jsonResponse(['authed' => false, 'user' => null]);
    }
}
