<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM fscrm_tasks WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('Task not found', 404);
            jsonResponse(toCamel($row));
        } else {
            $where = [];
            $types = '';
            $vals = [];
            if (!empty($_GET['status'])) {
                $where[] = 'status = ?';
                $types .= 's';
                $vals[] = $_GET['status'];
            }
            if (!empty($_GET['customer_id'])) {
                $where[] = 'customer_id = ?';
                $types .= 'i';
                $vals[] = intval($_GET['customer_id']);
            }
            if (!empty($_GET['assigned_to'])) {
                $where[] = 'assigned_to = ?';
                $types .= 'i';
                $vals[] = intval($_GET['assigned_to']);
            }
            if (!empty($_GET['service_id'])) {
                $where[] = 'service_id = ?';
                $types .= 'i';
                $vals[] = intval($_GET['service_id']);
            }
            if (!empty($_GET['scheduled_date'])) {
                $where[] = 'scheduled_date = ?';
                $types .= 's';
                $vals[] = $_GET['scheduled_date'];
            }
            if (!empty($_GET['start_date'])) {
                $where[] = 'scheduled_date >= ?';
                $types .= 's';
                $vals[] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
                $where[] = 'scheduled_date <= ?';
                $types .= 's';
                $vals[] = $_GET['end_date'];
            }
            $sql = "SELECT * FROM fscrm_tasks";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY scheduled_date DESC";
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
        $stmt = $db->prepare("INSERT INTO fscrm_tasks (service_id, customer_id, title, status, scheduled_date, completed_date, assigned_to, notes, category_id, completed_by, received_name, received_contact, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iissssisssss',
            $data['service_id'],
            $data['customer_id'],
            $data['title'],
            $data['status'],
            $data['scheduled_date'],
            $data['completed_date'],
            $data['assigned_to'],
            $data['notes'],
            $data['category_id'],
            $data['completed_by'],
            $data['received_name'],
            $data['received_contact'],
            $data['signature']
        );
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt = $db->prepare("SELECT * FROM fscrm_tasks WHERE id = ?");
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required');
        $input = getJsonInput();
        $data = toSnake($input);
        $fields = [];
        $types = '';
        $vals = [];
        $colMap = ['service_id', 'customer_id', 'title', 'status', 'scheduled_date', 'completed_date', 'assigned_to', 'notes', 'category_id', 'completed_by', 'received_name', 'received_contact', 'signature'];
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
        $stmt = $db->prepare("UPDATE fscrm_tasks SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt = $db->prepare("SELECT * FROM fscrm_tasks WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) jsonError('Task not found', 404);
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $stmt = $db->prepare("DELETE FROM fscrm_tasks WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) jsonError('Task not found', 404);
        jsonResponse(['message' => 'Task deleted']);
        break;
}
