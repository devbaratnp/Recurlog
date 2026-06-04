<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM fscrm_localities WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('Locality not found', 404);
            jsonResponse(toCamel($row));
        } else {
            $result = $db->query("SELECT * FROM fscrm_localities ORDER BY name ASC");
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            jsonResponse(toCamelArray($rows));
        }
        break;

    case 'POST':
        $input = getJsonInput();
        $stmt = $db->prepare("INSERT INTO fscrm_localities (name) VALUES (?)");
        $stmt->bind_param('s', $input['name']);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt = $db->prepare("SELECT * FROM fscrm_localities WHERE id = ?");
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required');
        $input = getJsonInput();
        $stmt = $db->prepare("UPDATE fscrm_localities SET name = ? WHERE id = ?");
        $stmt->bind_param('si', $input['name'], $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) jsonError('Locality not found', 404);
        $stmt = $db->prepare("SELECT * FROM fscrm_localities WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $stmt = $db->prepare("DELETE FROM fscrm_localities WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) jsonError('Locality not found', 404);
        jsonResponse(['message' => 'Locality deleted']);
        break;
}
