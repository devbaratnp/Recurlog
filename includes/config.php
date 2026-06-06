<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Load DB config from environment or fallback
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'ektamultp_recurlog';
$DB_PASS = getenv('DB_PASS') ?: '2^0y+57lo;.qfD.B';
$DB_NAME = getenv('DB_NAME') ?: 'ektamultp_recurlog';
$DB_PORT = (int)(getenv('DB_PORT') ?: 3306);

function getDB() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT;
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
        if ($conn->connect_error) {
            error_log('DB connection failed: ' . $conn->connect_error);
            die('A database error occurred. Please try again later.');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// CSRF token management
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfHiddenField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken()) . '">';
}

function requireCsrfToken() {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validateCsrfToken($token)) {
        error_log('CSRF validation failed');
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
            exit;
        }
        die('Invalid request. Please go back and try again.');
    }
}

// Rate limiting
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

function isAuthed() {
    return !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isAuthed()) {
        header('Location: pages/login.php');
        exit;
    }
}

function requireRole($role) {
    requireAuth();
    if (($_SESSION['user_role'] ?? '') !== $role) {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

function authUser() {
    if (!isAuthed()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

// VAPID keys for Web Push
// Generate via: php -r 'require "includes/notification_helper.php"; print_r(generateVapidKeys());'
define('VAPID_PUBLIC_KEY', getenv('VAPID_PUBLIC_KEY') ?: 'BFrJeUqRee1Bdn_-6DTsnhRxEXzfzsNRSUso09DXwiwWtgDqZoGeCf2Sy1dgHdNOTyUKJKdCvQuEtAk7dFhwtBY');
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: 'huYGZ2S20ApmT7mcfsA9PBKneop8t42qaaD7Id2h93M');
define('VAPID_SUBJECT', getenv('VAPID_SUBJECT') ?: 'mailto:admin@recurlog.com');

// Flash messages
function setFlash($message, $type = 'success', $title = '') {
    $_SESSION['_flash'] = [
        'type' => $type,
        'title' => $title ?: ucfirst($type),
        'text' => $message
    ];
}

function getFlash() {
    $flash = $_SESSION['_flash'] ?? null;
    if ($flash) {
        unset($_SESSION['_flash']);
    }
    return $flash;
}

function cacheBust() {
    $cssFile = __DIR__ . '/../assets/css/custom.css';
    $mtime = file_exists($cssFile) ? filemtime($cssFile) : time();
    return dechex($mtime);
}

function appBaseUrl() {
    static $base = null;
    if ($base === null) {
        $appRoot = realpath(__DIR__ . '/..');
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
        $base = str_replace([$docRoot, '\\'], ['', '/'], $appRoot);
    }
    return $base;
}
