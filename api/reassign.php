<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

requireAuth();
requireCsrfToken();

$db = getDB();
$input = getJsonInput();

$entityType = $input['entity_type'] ?? '';
$entityId = intval($input['entity_id'] ?? 0);
$newAssigneeId = $input['new_assignee_id'] !== null && $input['new_assignee_id'] !== '' ? intval($input['new_assignee_id']) : null;
$changedBy = intval($_SESSION['user_id'] ?? 0);

$allowedTypes = ['task', 'order', 'service'];
if (!in_array($entityType, $allowedTypes)) {
    jsonError('Invalid entity_type. Allowed: ' . implode(', ', $allowedTypes));
}
if (!$entityId) {
    jsonError('entity_id is required');
}
if (!$changedBy) {
    jsonError('Authentication required');
}

$db->begin_transaction();
try {
    $previousAssigneeId = null;
    $title = '';
    $customerName = '';

    if ($entityType === 'task') {
        $stmt = $db->prepare("SELECT t.assigned_to, t.title, c.name AS customer_name FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id WHERE t.id = ?");
        $stmt->bind_param('i', $entityId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) throw new Exception('Task not found');
        $previousAssigneeId = $row['assigned_to'];
        $title = $row['title'];
        $customerName = $row['customer_name'] ?? '';

        $stmt = $db->prepare("UPDATE fscrm_tasks SET assigned_to = ? WHERE id = ?");
        $stmt->bind_param('ii', $newAssigneeId, $entityId);
        $stmt->execute();

    } elseif ($entityType === 'order') {
        $stmt = $db->prepare("SELECT o.assigned_to, o.problem, o.customer_name FROM fscrm_orders o WHERE o.id = ?");
        $stmt->bind_param('i', $entityId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) throw new Exception('Order not found');
        $previousAssigneeId = $row['assigned_to'];
        $title = $row['problem'];
        $customerName = $row['customer_name'] ?? '';

        $staffName = null;
        if ($newAssigneeId) {
            $sStmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
            $sStmt->bind_param('i', $newAssigneeId);
            $sStmt->execute();
            $sRow = $sStmt->get_result()->fetch_assoc();
            $staffName = $sRow ? $sRow['name'] : null;
        }

        $stmt = $db->prepare("UPDATE fscrm_orders SET assigned_to = ?, assigned_staff_name = ? WHERE id = ?");
        $stmt->bind_param('isi', $newAssigneeId, $staffName, $entityId);
        $stmt->execute();

    } elseif ($entityType === 'service') {
        $stmt = $db->prepare("SELECT s.assigned_to, s.title, c.name AS customer_name FROM fscrm_services s LEFT JOIN fscrm_customers c ON s.customer_id = c.id WHERE s.id = ?");
        $stmt->bind_param('i', $entityId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) throw new Exception('Service not found');
        $previousAssigneeId = $row['assigned_to'];
        $title = $row['title'];
        $customerName = $row['customer_name'] ?? '';

        $stmt = $db->prepare("UPDATE fscrm_services SET assigned_to = ? WHERE id = ?");
        $stmt->bind_param('ii', $newAssigneeId, $entityId);
        $stmt->execute();

        $stmt = $db->prepare("UPDATE fscrm_tasks SET assigned_to = ? WHERE service_id = ? AND status = 'pending'");
        $stmt->bind_param('ii', $newAssigneeId, $entityId);
        $stmt->execute();
    }

    $stmt = $db->prepare("INSERT INTO fscrm_assignment_history (entity_type, entity_id, previous_assignee_id, new_assignee_id, changed_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('siiii', $entityType, $entityId, $previousAssigneeId, $newAssigneeId, $changedBy);
    $stmt->execute();
    $historyId = $db->insert_id;

    $typeLabel = $entityType === 'order' ? 'Order' : ($entityType === 'service' ? 'Service' : 'Task');
    $entityLabel = $typeLabel . ' "' . ($title ?: '#' . $entityId) . '"' . ($customerName ? ' for ' . $customerName : '');

    if ($newAssigneeId) {
        $newStaffName = '';
        $sStmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
        $sStmt->bind_param('i', $newAssigneeId);
        $sStmt->execute();
        $sRow = $sStmt->get_result()->fetch_assoc();
        $newStaffName = $sRow ? $sRow['name'] : '';

        $uStmt = $db->prepare("SELECT id FROM fscrm_users WHERE staff_id = ?");
        $uStmt->bind_param('i', $newAssigneeId);
        $uStmt->execute();
        $uRow = $uStmt->get_result()->fetch_assoc();
        if ($uRow) {
            createNotification($db, 'You have been assigned to ' . $entityLabel, 'task', $entityId, (int)$uRow['id']);
        }
    }

    if ($previousAssigneeId) {
        $uStmt = $db->prepare("SELECT id FROM fscrm_users WHERE staff_id = ?");
        $uStmt->bind_param('i', $previousAssigneeId);
        $uStmt->execute();
        $uRow = $uStmt->get_result()->fetch_assoc();
        if ($uRow) {
            createNotification($db, 'You have been unassigned from ' . $entityLabel, 'task', $entityId, (int)$uRow['id']);
        }
    }

    $db->commit();
    jsonResponse([
        'history_id' => $historyId,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'previous_assignee_id' => $previousAssigneeId,
        'new_assignee_id' => $newAssigneeId,
    ]);

} catch (Exception $e) {
    $db->rollback();
    jsonError($e->getMessage(), 400);
}
