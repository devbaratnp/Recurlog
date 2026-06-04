<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM fscrm_categories WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('Category not found', 404);
            jsonResponse(toCamel($row));
        } else {
            $result = $db->query("SELECT * FROM fscrm_categories ORDER BY name ASC");
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            jsonResponse(toCamelArray($rows));
        }
        break;

    case 'POST':
        $input = getJsonInput();
        $data = toSnake($input);
        $stmt = $db->prepare("INSERT INTO fscrm_categories (name, color) VALUES (?, ?)");
        $stmt->bind_param('ss', $data['name'], $data['color']);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt = $db->prepare("SELECT * FROM fscrm_categories WHERE id = ?");
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
        foreach (['name', 'color'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $types .= 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update');
        $types .= 'i';
        $vals[] = $id;
        $stmt = $db->prepare("UPDATE fscrm_categories SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt = $db->prepare("SELECT * FROM fscrm_categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) jsonError('Category not found', 404);
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $stmt = $db->prepare("DELETE FROM fscrm_categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) jsonError('Category not found', 404);
        jsonResponse(['message' => 'Category deleted']);
        break;
}
