<?php
function createNotification($db, $text, $type, $relatedId = null, $userId = null) {
    if (!$userId) $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) return false;

    $stmt = $db->prepare("INSERT INTO fscrm_notifications (text, type, related_id) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $text, $type, $relatedId);
    $stmt->execute();
    return $db->insert_id;
}
