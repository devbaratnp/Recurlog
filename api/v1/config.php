<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && strpos($origin, 'http://localhost') !== false) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$JWT_SECRET = getenv('JWT_SECRET') ?: 'recurlog-jwt-secret-change-in-production';

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
            die(json_encode(['success' => false, 'error' => 'A database error occurred.', 'code' => 'DB_ERROR']));
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

function paginatedResponse($rows, $total, $page, $perPage) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $rows,
        'pagination' => [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => max(1, (int)ceil($total / $perPage))
        ]
    ]);
    exit;
}

function jsonError($msg, $code = 400, $errorCode = 'BAD_REQUEST') {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg, 'code' => $errorCode]);
    exit;
}

function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        jsonError('Invalid JSON input', 400, 'INVALID_INPUT');
    }
    return $data ?: [];
}

function base64urlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64urlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateJWT($payload, $secret) {
    $header = base64urlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payloadEncoded = base64urlEncode(json_encode($payload));
    $signature = base64urlEncode(hash_hmac('sha256', "$header.$payloadEncoded", $secret, true));
    return "$header.$payloadEncoded.$signature";
}

function validateJWT($token, $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $signature] = $parts;
    $expectedSig = base64urlEncode(hash_hmac('sha256', "$header.$payload", $secret, true));
    if (!hash_equals($expectedSig, $signature)) return null;
    $data = json_decode(base64urlDecode($payload), true);
    if (!$data || !isset($data['exp']) || $data['exp'] < time()) return null;
    return $data;
}

function getAuthToken() {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function requireBearerAuth() {
    global $JWT_SECRET;
    $token = getAuthToken();
    if (!$token) {
        jsonError('Authorization header required', 401, 'UNAUTHORIZED');
    }
    $payload = validateJWT($token, $JWT_SECRET);
    if (!$payload) {
        jsonError('Invalid or expired token', 401, 'TOKEN_EXPIRED');
    }
    $_REQUEST['_auth'] = $payload;
    return $payload;
}

function getAuthUser() {
    return $_REQUEST['_auth'] ?? null;
}

function requireRole($role) {
    $user = getAuthUser();
    if (!$user) jsonError('Unauthorized', 401, 'UNAUTHORIZED');
    if (($user['userRole'] ?? '') !== $role) {
        jsonError('Forbidden: insufficient permissions', 403, 'FORBIDDEN');
    }
}

function getPageParams() {
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = min(200, max(1, intval($_GET['per_page'] ?? 50)));
    $offset = ($page - 1) * $perPage;
    return [$page, $perPage, $offset];
}

function buildSearchClause($term, $columns) {
    if (!$term || trim($term) === '') return ['', []];
    $clauses = [];
    $params = [];
    foreach ($columns as $col) {
        $clauses[] = "$col LIKE ?";
        $params[] = '%' . $term . '%';
    }
    return ['AND (' . implode(' OR ', $clauses) . ')', $params];
}

function fetchSingle($table, $id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function requireExists($row, $label) {
    if (!$row) jsonError("$label not found", 404, 'NOT_FOUND');
    return $row;
}

function insertAndFetch($table, $columns, $types, $values) {
    $db = getDB();
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $colNames = implode(', ', $columns);
    $stmt = $db->prepare("INSERT INTO $table ($colNames) VALUES ($placeholders)");
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        jsonError('Database error: ' . $stmt->error, 500, 'DB_ERROR');
    }
    $newId = $db->insert_id;
    return fetchSingle($table, $newId);
}

function updateAndFetch($table, $fields, $types, $values, $id) {
    $db = getDB();
    $sets = implode(', ', array_map(fn($f) => "$f = ?", $fields));
    $types .= 'i';
    $vals = array_merge($values, [$id]);
    $stmt = $db->prepare("UPDATE $table SET $sets WHERE id = ?");
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    return fetchSingle($table, $id);
}

function deleteById($table, $id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) jsonError('Resource not found', 404, 'NOT_FOUND');
}

function checkRateLimitIp($maxAttempts = 10, $windowSec = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = sys_get_temp_dir() . '/rcl_rate_' . md5($ip);
    $data = @file_get_contents($file);
    $records = $data ? json_decode($data, true) : [];
    $now = time();
    $records = array_filter($records, fn($t) => $t > $now - $windowSec);
    if (count($records) >= $maxAttempts) {
        jsonError('Too many requests. Try again later.', 429, 'RATE_LIMITED');
    }
    $records[] = $now;
    file_put_contents($file, json_encode($records));
}
