<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT rt.*, c.name AS customer_name, s.name AS staff_name FROM fscrm_recurring_tasks rt LEFT JOIN fscrm_customers c ON rt.customer_id = c.id LEFT JOIN fscrm_staff s ON rt.assigned_to = s.id WHERE rt.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('Recurring task not found', 404);
            jsonResponse(toCamel($row));
        } else {
            $where = [];
            $types = '';
            $vals = [];
            if (!empty($_GET['customer_id'])) {
                $where[] = 'rt.customer_id = ?';
                $types .= 'i';
                $vals[] = intval($_GET['customer_id']);
            }
            if (!empty($_GET['assigned_to'])) {
                $where[] = 'rt.assigned_to = ?';
                $types .= 'i';
                $vals[] = intval($_GET['assigned_to']);
            }
            if (isset($_GET['is_active'])) {
                $where[] = 'rt.is_active = ?';
                $types .= 'i';
                $vals[] = intval($_GET['is_active']);
            }
            $sql = "SELECT rt.*, c.name AS customer_name, s.name AS staff_name FROM fscrm_recurring_tasks rt LEFT JOIN fscrm_customers c ON rt.customer_id = c.id LEFT JOIN fscrm_staff s ON rt.assigned_to = s.id";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY rt.next_due_date ASC";
            if (!empty($vals)) {
                $stmt = $db->prepare($sql);
                $stmt->bind_param($types, ...$vals);
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
        $stmt = $db->prepare("INSERT INTO fscrm_recurring_tasks (customer_id, title, problem, assigned_to, notes, rec_value, rec_unit, repeat_from, next_due_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issiisissi',
            $data['customer_id'],
            $data['title'],
            $data['problem'] ?? '',
            $data['assigned_to'],
            $data['notes'] ?? '',
            $data['rec_value'] ?? 1,
            $data['rec_unit'] ?? 'days',
            $data['repeat_from'] ?? 'last-done',
            $data['next_due_date'],
            $data['is_active'] ?? 1
        );
        $stmt->execute();
        $newId = $db->insert_id;

        $fetch = $db->prepare("SELECT rt.*, c.name AS customer_name, s.name AS staff_name FROM fscrm_recurring_tasks rt LEFT JOIN fscrm_customers c ON rt.customer_id = c.id LEFT JOIN fscrm_staff s ON rt.assigned_to = s.id WHERE rt.id = ?");
        $fetch->bind_param('i', $newId);
        $fetch->execute();
        $row = $fetch->get_result()->fetch_assoc();
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required');
        $input = getJsonInput();
        $data = toSnake($input);
        $fields = [];
        $types = '';
        $vals = [];
        $colMap = ['customer_id', 'title', 'problem', 'assigned_to', 'notes', 'rec_value', 'rec_unit', 'repeat_from', 'next_due_date', 'is_active', 'last_completed_date'];
        foreach ($colMap as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $v = $data[$f];
                $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
                $vals[] = $v;
            }
        }
        if (empty($fields)) jsonError('No fields to update');
        $types .= 'i';
        $vals[] = $id;
        $stmt = $db->prepare("UPDATE fscrm_recurring_tasks SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();

        $fetch = $db->prepare("SELECT rt.*, c.name AS customer_name, s.name AS staff_name FROM fscrm_recurring_tasks rt LEFT JOIN fscrm_customers c ON rt.customer_id = c.id LEFT JOIN fscrm_staff s ON rt.assigned_to = s.id WHERE rt.id = ?");
        $fetch->bind_param('i', $id);
        $fetch->execute();
        $row = $fetch->get_result()->fetch_assoc();
        if (!$row) jsonError('Recurring task not found', 404);
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $db->begin_transaction();
        try {
            $unlink = $db->prepare("UPDATE fscrm_tasks SET recurring_task_id = NULL WHERE recurring_task_id = ?");
            $unlink->bind_param('i', $id);
            $unlink->execute();

            $stmt = $db->prepare("DELETE FROM fscrm_recurring_tasks WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                $db->rollback();
                jsonError('Recurring task not found', 404);
            }
            $db->commit();
            jsonResponse(['message' => 'Recurring task deleted']);
        } catch (Exception $e) {
            $db->rollback();
            jsonError('Delete failed: ' . $e->getMessage(), 500);
        }
        break;
}
