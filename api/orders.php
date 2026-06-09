<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT o.*, s.name AS assigned_staff_name FROM fscrm_orders o LEFT JOIN fscrm_staff s ON o.assigned_to = s.id WHERE o.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('Order not found', 404);
            jsonResponse(toCamel($row));
        } else {
            $where = [];
            $types = '';
            $vals = [];
            if (!empty($_GET['status'])) {
                $where[] = 'o.status = ?';
                $types .= 's';
                $vals[] = $_GET['status'];
            }
            if (!empty($_GET['customer_id'])) {
                $where[] = 'o.customer_id = ?';
                $types .= 'i';
                $vals[] = intval($_GET['customer_id']);
            }
            if (!empty($_GET['priority'])) {
                $where[] = 'o.priority = ?';
                $types .= 's';
                $vals[] = $_GET['priority'];
            }
            if (!empty($_GET['assigned_to'])) {
                $where[] = 'o.assigned_to = ?';
                $types .= 'i';
                $vals[] = intval($_GET['assigned_to']);
            }
            $sql = "SELECT o.*, s.name AS assigned_staff_name FROM fscrm_orders o LEFT JOIN fscrm_staff s ON o.assigned_to = s.id";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY o.created_at DESC";
            if (!empty($where)) {
                $stmt = $db->prepare($sql);
                if (!empty($vals)) $stmt->bind_param($types, ...$vals);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                $result = $db->query($sql);
                $rows = $result->fetch_all(MYSQLI_ASSOC);
            }
            jsonResponse(toCamelArray($rows));
        }
        break;

    case 'POST':
        $input = getJsonInput();
        $data = toSnake($input);
        if (!empty($data['assigned_to']) && empty($data['assigned_staff_name'])) {
            $sStmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
            $sStmt->bind_param('i', $data['assigned_to']);
            $sStmt->execute();
            $sRow = $sStmt->get_result()->fetch_assoc();
            $data['assigned_staff_name'] = $sRow ? $sRow['name'] : '';
        }
        $dateFields = ['scheduled_date', 'completed_date', 'dispatch_date'];
        foreach ($dateFields as $f) {
            if (isset($data[$f]) && $data[$f] === '') $data[$f] = null;
        }
        $stmt = $db->prepare("INSERT INTO fscrm_orders (customer_id, customer_name, service_for, problem, status, priority, assigned_to, assigned_staff_name, scheduled_date, completed_date, notes, dispatch_date, dispatch_by, received_name, received_contact, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssisssssssss',
            $data['customer_id'],
            $data['customer_name'],
            $data['service_for'],
            $data['problem'],
            $data['status'],
            $data['priority'],
            $data['assigned_to'],
            $data['assigned_staff_name'],
            $data['scheduled_date'],
            $data['completed_date'],
            $data['notes'],
            $data['dispatch_date'],
            $data['dispatch_by'],
            $data['received_name'],
            $data['received_contact'],
            $data['signature']
        );
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt = $db->prepare("SELECT * FROM fscrm_orders WHERE id = ?");
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required');
        $input = getJsonInput();
        $data = toSnake($input);
        foreach (['scheduled_date', 'completed_date', 'dispatch_date'] as $f) {
            if (isset($data[$f]) && $data[$f] === '') $data[$f] = null;
        }
        if (!empty($data['assigned_to']) && empty($data['assigned_staff_name'])) {
            $sStmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
            $sStmt->bind_param('i', $data['assigned_to']);
            $sStmt->execute();
            $sRow = $sStmt->get_result()->fetch_assoc();
            $data['assigned_staff_name'] = $sRow ? $sRow['name'] : '';
        }
        $fields = [];
        $types = '';
        $vals = [];
        $colMap = ['customer_id', 'customer_name', 'service_for', 'problem', 'status', 'priority', 'assigned_to', 'assigned_staff_name', 'scheduled_date', 'completed_date', 'notes', 'dispatch_date', 'dispatch_by', 'received_name', 'received_contact', 'signature'];
        foreach ($colMap as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $v = $data[$f];
                if (is_int($v)) {
                    $types .= 'i';
                } elseif (is_float($v)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $vals[] = $v;
            }
        }
        if (empty($fields)) jsonError('No fields to update');
        $types .= 'i';
        $vals[] = $id;
        $stmt = $db->prepare("UPDATE fscrm_orders SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt = $db->prepare("SELECT * FROM fscrm_orders WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) jsonError('Order not found', 404);
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $stmt = $db->prepare("DELETE FROM fscrm_orders WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) jsonError('Order not found', 404);
        jsonResponse(['message' => 'Order deleted']);
        break;
}
