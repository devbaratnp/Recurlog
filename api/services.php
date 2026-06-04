<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM fscrm_services WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('Service not found', 404);
            jsonResponse(toCamel($row));
        } else {
            $where = [];
            $types = '';
            $vals = [];
            if (!empty($_GET['customer_id'])) {
                $where[] = 'customer_id = ?';
                $types .= 'i';
                $vals[] = intval($_GET['customer_id']);
            }
            if (!empty($_GET['category_id'])) {
                $where[] = 'category_id = ?';
                $types .= 'i';
                $vals[] = intval($_GET['category_id']);
            }
            if (isset($_GET['is_recurring'])) {
                $where[] = 'is_recurring = ?';
                $types .= 'i';
                $vals[] = intval($_GET['is_recurring']);
            }
            $sql = "SELECT * FROM fscrm_services";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY id ASC";
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
        $stmt = $db->prepare("INSERT INTO fscrm_services (customer_id, category_id, service_for, title, problem, is_recurring, first_scheduled_date, assigned_to, notes, rec_value, rec_unit, repeat_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iisssiisssss',
            $data['customer_id'],
            $data['category_id'],
            $data['service_for'],
            $data['title'],
            $data['problem'],
            $data['is_recurring'],
            $data['first_scheduled_date'],
            $data['assigned_to'],
            $data['notes'],
            $data['rec_value'],
            $data['rec_unit'],
            $data['repeat_from']
        );
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt = $db->prepare("SELECT * FROM fscrm_services WHERE id = ?");
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
        $colMap = ['customer_id', 'category_id', 'service_for', 'title', 'problem', 'is_recurring', 'first_scheduled_date', 'assigned_to', 'notes', 'rec_value', 'rec_unit', 'repeat_from'];
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
        $stmt = $db->prepare("UPDATE fscrm_services SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt = $db->prepare("SELECT * FROM fscrm_services WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) jsonError('Service not found', 404);
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $stmt = $db->prepare("DELETE FROM fscrm_services WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) jsonError('Service not found', 404);
        jsonResponse(['message' => 'Service deleted']);
        break;
}
