<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$date = trim($_POST['date'] ?? '');
$note = trim($_POST['note'] ?? '');

if (!$id || !$date) {
    http_response_code(400);
    echo 'Missing required fields';
    exit;
}

$stmt = $db->prepare("UPDATE fscrm_tasks SET status = 'completed', completed_date = ? WHERE id = ?");
$stmt->bind_param('si', $date, $id);
$stmt->execute();

echo 'ok';
