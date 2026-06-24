<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, COALESCE(t.is_recurring, 0) AS is_recurring, COALESCE(sv.problem, t.problem) AS service_problem, rt.title AS recurrence_title FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id LEFT JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id WHERE t.id = ?");
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
                $where[] = 't.status = ?';
                $types .= 's';
                $vals[] = $_GET['status'];
            }
            if (!empty($_GET['customer_id'])) {
                $where[] = 't.customer_id = ?';
                $types .= 'i';
                $vals[] = intval($_GET['customer_id']);
            }
            if (!empty($_GET['assigned_to'])) {
                $where[] = 't.assigned_to = ?';
                $types .= 'i';
                $vals[] = intval($_GET['assigned_to']);
            }
            if (!empty($_GET['service_id'])) {
                $where[] = 't.service_id = ?';
                $types .= 'i';
                $vals[] = intval($_GET['service_id']);
            }
            if (!empty($_GET['scheduled_date'])) {
                $where[] = 't.scheduled_date = ?';
                $types .= 's';
                $vals[] = $_GET['scheduled_date'];
            }
            if (!empty($_GET['start_date'])) {
                $where[] = 't.scheduled_date >= ?';
                $types .= 's';
                $vals[] = $_GET['start_date'];
            }
            if (!empty($_GET['end_date'])) {
                $where[] = 't.scheduled_date <= ?';
                $types .= 's';
                $vals[] = $_GET['end_date'];
            }
            $sql = "SELECT t.*, c.name AS customer_name, COALESCE(t.is_recurring, 0) AS is_recurring, COALESCE(sv.problem, t.problem) AS service_problem, rt.title AS recurrence_title FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id LEFT JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY t.scheduled_date DESC";
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
        $stmt = $db->prepare("INSERT INTO fscrm_tasks (service_id, recurring_task_id, customer_id, title, problem, status, scheduled_date, completed_date, assigned_to, notes, category_id, completed_by, received_name, received_contact, signature, is_recurring, rec_value, rec_unit, repeat_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iiissssissssssiiss',
            $data['service_id'],
            $data['recurring_task_id'],
            $data['customer_id'],
            $data['title'],
            $data['problem'] ?? '',
            $data['status'],
            $data['scheduled_date'],
            $data['completed_date'],
            $data['assigned_to'],
            $data['notes'],
            $data['category_id'],
            $data['completed_by'],
            $data['received_name'],
            $data['received_contact'],
            $data['signature'],
            $data['is_recurring'] ?? 0,
            $data['rec_value'] ?? null,
            $data['rec_unit'] ?? null,
            $data['repeat_from'] ?? null
        );
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, rt.title AS recurrence_title FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id WHERE t.id = ?");
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
        $colMap = ['service_id', 'recurring_task_id', 'customer_id', 'title', 'problem', 'status', 'scheduled_date', 'completed_date', 'assigned_to', 'notes', 'category_id', 'completed_by', 'received_name', 'received_contact', 'signature', 'is_recurring', 'rec_value', 'rec_unit', 'repeat_from'];
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

        // Recurrence engine: if task completed and linked to a recurring template, generate next instance
        if (!empty($data['status']) && $data['status'] === 'completed') {
            $stmt2 = $db->prepare("SELECT t.id AS task_id, t.recurring_task_id, t.customer_id, t.title, t.problem, t.assigned_to, t.notes, t.scheduled_date, t.completed_date, rt.rec_value, rt.rec_unit, rt.repeat_from, rt.next_due_date FROM fscrm_tasks t JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id WHERE t.id = ?");
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $rtRow = $stmt2->get_result()->fetch_assoc();
            if ($rtRow) {
                $baseDate = $rtRow['repeat_from'] === 'last-done' ? ($rtRow['completed_date'] ?: $rtRow['scheduled_date']) : $rtRow['next_due_date'];
                if ($baseDate) {
                    $dt = new DateTime($baseDate);
                    $unitMap = ['days' => 'D', 'weeks' => 'W', 'months' => 'M', 'years' => 'Y'];
                    $unit = $unitMap[$rtRow['rec_unit']] ?? 'D';
                    $dt->add(new DateInterval('P' . $rtRow['rec_value'] . $unit));
                    $nextDue = $dt->format('Y-m-d');

                    $updateRt = $db->prepare("UPDATE fscrm_recurring_tasks SET last_completed_date = ?, next_due_date = ? WHERE id = ?");
                    $updateRt->bind_param('ssi', $rtRow['completed_date'], $nextDue, $rtRow['recurring_task_id']);
                    $updateRt->execute();

                    $insertTask = $db->prepare("INSERT INTO fscrm_tasks (recurring_task_id, customer_id, title, problem, status, scheduled_date, assigned_to, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
                    $insertTask->bind_param('iisssis', $rtRow['recurring_task_id'], $rtRow['customer_id'], $rtRow['title'], $rtRow['problem'], $nextDue, $rtRow['assigned_to'], $rtRow['notes']);
                    $insertTask->execute();
                }
            }
        }

        $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, COALESCE(t.is_recurring, 0) AS is_recurring, COALESCE(sv.problem, t.problem) AS service_problem, rt.title AS recurrence_title FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id LEFT JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id WHERE t.id = ?");
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
