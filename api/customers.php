<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM fscrm_customers WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('Customer not found', 404);
            jsonResponse(toCamel($row));
        } else {
            $result = $db->query("SELECT * FROM fscrm_customers ORDER BY name ASC");
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            jsonResponse(toCamelArray($rows));
        }
        break;

    case 'POST':
        $input = getJsonInput();
        $data = toSnake($input);
        $stmt = $db->prepare("INSERT INTO fscrm_customers (name, address, area, phone, services_for, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssdd',
            $data['name'],
            $data['address'],
            $data['area'],
            $data['phone'],
            $data['services_for'],
            $data['location_lat'],
            $data['location_lng']
        );
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt = $db->prepare("SELECT * FROM fscrm_customers WHERE id = ?");
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
        foreach (['name', 'address', 'area', 'phone', 'services_for', 'location_lat', 'location_lng'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $types .= is_float($data[$f]) ? 'd' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update');
        $types .= 'i';
        $vals[] = $id;
        $stmt = $db->prepare("UPDATE fscrm_customers SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt = $db->prepare("SELECT * FROM fscrm_customers WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) jsonError('Customer not found', 404);
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("DELETE FROM fscrm_orders WHERE customer_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $taskStmt = $db->prepare("DELETE FROM fscrm_tasks WHERE customer_id = ?");
            $taskStmt->bind_param('i', $id);
            $taskStmt->execute();

            $svcStmt = $db->prepare("DELETE FROM fscrm_services WHERE customer_id = ?");
            $svcStmt->bind_param('i', $id);
            $svcStmt->execute();

            $custStmt = $db->prepare("DELETE FROM fscrm_customers WHERE id = ?");
            $custStmt->bind_param('i', $id);
            $custStmt->execute();
            if ($custStmt->affected_rows === 0) {
                $db->rollback();
                jsonError('Customer not found', 404);
            }
            $db->commit();
            jsonResponse(['message' => 'Customer deleted']);
        } catch (Exception $e) {
            $db->rollback();
            jsonError('Delete failed: ' . $e->getMessage(), 500);
        }
        break;
}
