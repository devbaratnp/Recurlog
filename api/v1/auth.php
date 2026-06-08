<?php
require_once __DIR__ . '/config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'refresh':
        handleRefresh();
        break;
    case 'me':
        handleMe();
        break;
    default:
        handleLogin();
        break;
}

function handleLogin() {
    global $JWT_SECRET;

    $input = getJsonInput();
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        jsonError('Email and password are required', 400, 'VALIDATION_ERROR');
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, password, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonError('Invalid email or password', 401, 'UNAUTHORIZED');
    }

    $now = time();
    $token = generateJWT([
        'userId' => (int)$user['id'],
        'userRole' => $user['role'],
        'iat' => $now,
        'exp' => $now + 604800
    ], $JWT_SECRET);

    $refreshToken = generateJWT([
        'userId' => (int)$user['id'],
        'type' => 'refresh',
        'iat' => $now,
        'exp' => $now + 2592000
    ], $JWT_SECRET);

    $staffId = $user['staff_id'] ? (int)$user['staff_id'] : null;

    jsonResponse([
        'token' => $token,
        'refreshToken' => $refreshToken,
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'staffId' => $staffId,
            'isActive' => (int)$user['is_active'],
            'createdAt' => $user['created_at'],
            'createdBy' => $user['created_by']
        ]
    ]);
}

function handleRefresh() {
    global $JWT_SECRET;

    $input = getJsonInput();
    $refreshToken = $input['refreshToken'] ?? '';

    if (!$refreshToken) {
        jsonError('Refresh token is required', 400, 'VALIDATION_ERROR');
    }

    $payload = validateJWT($refreshToken, $JWT_SECRET);
    if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
        jsonError('Invalid or expired refresh token', 401, 'TOKEN_EXPIRED');
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE id = ?");
    $stmt->bind_param('i', $payload['userId']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        jsonError('User not found', 401, 'UNAUTHORIZED');
    }

    $now = time();
    $token = generateJWT([
        'userId' => (int)$user['id'],
        'userRole' => $user['role'],
        'iat' => $now,
        'exp' => $now + 604800
    ], $JWT_SECRET);

    $newRefresh = generateJWT([
        'userId' => (int)$user['id'],
        'type' => 'refresh',
        'iat' => $now,
        'exp' => $now + 2592000
    ], $JWT_SECRET);

    $staffId = $user['staff_id'] ? (int)$user['staff_id'] : null;

    jsonResponse([
        'token' => $token,
        'refreshToken' => $newRefresh,
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'staffId' => $staffId,
            'isActive' => (int)$user['is_active'],
            'createdAt' => $user['created_at'],
            'createdBy' => $user['created_by']
        ]
    ]);
}

function handleMe() {
    $auth = requireBearerAuth();

    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE id = ?");
    $stmt->bind_param('i', $auth['userId']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        jsonError('User not found', 404, 'NOT_FOUND');
    }

    $staffId = $user['staff_id'] ? (int)$user['staff_id'] : null;

    jsonResponse([
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'staffId' => $staffId,
        'isActive' => (int)$user['is_active'],
        'createdAt' => $user['created_at'],
        'createdBy' => $user['created_by']
    ]);
}
