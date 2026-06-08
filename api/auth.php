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
    case 'me':
        handleMe();
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

    $stmt = $db->prepare("SELECT id, name, email, password, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE email = ?");
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
    $_SESSION['user_staff_id'] = $user['staff_id'];

    jsonResponse([
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'staffId' => $user['staff_id'] ? (int)$user['staff_id'] : null,
        'isActive' => (int)$user['is_active'],
        'createdAt' => $user['created_at']
    ]);
}

function handleLogout() {
    $_SESSION = [];
    session_destroy();
    jsonResponse(['message' => 'Logged out successfully']);
}

function handleCheck() {
    if (!empty($_SESSION['user_id'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user) {
            jsonResponse([
                'authed' => true,
                'user' => [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'staffId' => $user['staff_id'] ? (int)$user['staff_id'] : null,
                    'isActive' => (int)$user['is_active'],
                    'createdAt' => $user['created_at'],
                    'createdBy' => $user['created_by']
                ]
            ]);
        } else {
            jsonResponse(['authed' => false, 'user' => null]);
        }
    } else {
        jsonResponse(['authed' => false, 'user' => null]);
    }
}

function handleMe() {
    requireAuth();
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) jsonError('User not found', 404);
    jsonResponse([
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'staffId' => $user['staff_id'] ? (int)$user['staff_id'] : null,
        'isActive' => (int)$user['is_active'],
        'createdAt' => $user['created_at'],
        'createdBy' => $user['created_by']
    ]);
}
