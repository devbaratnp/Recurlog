<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM fscrm_staff WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('Staff not found', 404);
            jsonResponse(toCamel($row));
        } else {
            $result = $db->query("SELECT * FROM fscrm_staff ORDER BY name ASC");
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            jsonResponse(toCamelArray($rows));
        }
        break;

    case 'POST':
        $input = getJsonInput();
        $data = toSnake($input);
        $stmt = $db->prepare("INSERT INTO fscrm_staff (name, phone, avatar, active_tasks) VALUES (?, ?, ?, ?)");
        $activeTasks = $data['active_tasks'] ?? 0;
        $stmt->bind_param('sssi', $data['name'], $data['phone'], $data['avatar'], $activeTasks);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt = $db->prepare("SELECT * FROM fscrm_staff WHERE id = ?");
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
        foreach (['name', 'phone', 'avatar', 'active_tasks'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $types .= is_int($data[$f]) ? 'i' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update');
        $types .= 'i';
        $vals[] = $id;
        $stmt = $db->prepare("UPDATE fscrm_staff SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt = $db->prepare("SELECT * FROM fscrm_staff WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) jsonError('Staff not found', 404);
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $stmt = $db->prepare("DELETE FROM fscrm_staff WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) jsonError('Staff not found', 404);
        jsonResponse(['message' => 'Staff deleted']);
        break;
}
