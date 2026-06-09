<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$allowedOrigin = (!empty($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], 'http://localhost') !== false)
    ? $_SERVER['HTTP_ORIGIN'] : '';
if ($allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: null');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'ektamultp_recurlog';
$DB_PASS = getenv('DB_PASS') ?: '2^0y+57lo;.qfD.B';
$DB_NAME = getenv('DB_NAME') ?: 'ektamultp_recurlog';
$DB_PORT = (int)(getenv('DB_PORT') ?: 3306);

function getDB() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT;
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
        if ($conn->connect_error) {
            error_log('DB connection failed: ' . $conn->connect_error);
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'A database error occurred.']));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function jsonError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function requireAuth() {
    if (empty($_SESSION['user_id'])) {
        jsonError('Unauthorized', 401);
    }
}

function requireRole($role) {
    requireAuth();
    if (($_SESSION['user_role'] ?? '') !== $role) {
        jsonError('Forbidden: insufficient permissions', 403);
    }
}

function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        jsonError('Invalid JSON input');
    }
    return $data ?: [];
}

function checkRateLimit($key, $maxAttempts = 5, $windowSec = 300) {
    $rateKey = 'rate_limit_' . $key;
    $attempts = $_SESSION[$rateKey] ?? ['count' => 0, 'reset' => time() + $windowSec];
    if (time() > $attempts['reset']) {
        $attempts = ['count' => 0, 'reset' => time() + $windowSec];
    }
    $attempts['count']++;
    $_SESSION[$rateKey] = $attempts;
    if ($attempts['count'] > $maxAttempts) {
        return false;
    }
    return true;
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrfToken() {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validateCsrfToken($token)) {
        error_log('CSRF validation failed');
        jsonError('Invalid or missing CSRF token', 403);
    }
}
