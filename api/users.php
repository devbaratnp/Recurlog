<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

requireAuth();

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT id, name, email, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) jsonError('User not found', 404);
            $row['id'] = (int)$row['id'];
            $row['staff_id'] = $row['staff_id'] ? (int)$row['staff_id'] : null;
            $row['is_active'] = (int)$row['is_active'];
            jsonResponse(toCamel($row));
        } else {
            $result = $db->query("SELECT id, name, email, role, staff_id, is_active, created_at, created_by FROM fscrm_users ORDER BY created_at DESC");
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) {
                $r['id'] = (int)$r['id'];
                $r['staff_id'] = $r['staff_id'] ? (int)$r['staff_id'] : null;
                $r['is_active'] = (int)$r['is_active'];
            }
            jsonResponse(toCamelArray($rows));
        }
        break;

    case 'POST':
        $input = getJsonInput();
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'staff';
        $staffId = isset($input['staffId']) ? intval($input['staffId']) : null;

        if (!$name || !$email || !$password) {
            jsonError('Name, email, and password are required');
        }

        $check = $db->prepare("SELECT id FROM fscrm_users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            jsonError('Email already exists');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $createdBy = $_SESSION['user_name'] ?? '';

        if ($staffId) {
            $stmt = $db->prepare("INSERT INTO fscrm_users (name, email, password, role, staff_id, is_active, created_by) VALUES (?, ?, ?, ?, ?, 1, ?)");
            $stmt->bind_param('ssssis', $name, $email, $hash, $role, $staffId, $createdBy);
        } else {
            $stmt = $db->prepare("INSERT INTO fscrm_users (name, email, password, role, is_active, created_by) VALUES (?, ?, ?, ?, 1, ?)");
            $stmt->bind_param('sssss', $name, $email, $hash, $role, $createdBy);
        }
        $stmt->execute();
        $newId = $db->insert_id;

        $stmt = $db->prepare("SELECT id, name, email, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE id = ?");
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $row['id'] = (int)$row['id'];
        $row['staff_id'] = $row['staff_id'] ? (int)$row['staff_id'] : null;
        $row['is_active'] = (int)$row['is_active'];
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required');
        $input = getJsonInput();
        $fields = [];
        $types = '';
        $vals = [];

        foreach (['name', 'email', 'role', 'staff_id', 'is_active'] as $f) {
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $f))));
            if (array_key_exists($camelKey, $input)) {
                $fields[] = "$f = ?";
                $v = $input[$camelKey];
                if ($f === 'is_active' || $f === 'staff_id') {
                    $types .= 'i';
                    $vals[] = (int)$v;
                } else {
                    $types .= 's';
                    $vals[] = $v;
                }
            }
        }

        if (empty($fields)) jsonError('No fields to update');
        $types .= 'i';
        $vals[] = $id;
        $stmt = $db->prepare("UPDATE fscrm_users SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();

        $stmt = $db->prepare("SELECT id, name, email, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) jsonError('User not found', 404);
        $row['id'] = (int)$row['id'];
        $row['staff_id'] = $row['staff_id'] ? (int)$row['staff_id'] : null;
        $row['is_active'] = (int)$row['is_active'];
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required');
        if ($id === $_SESSION['user_id']) jsonError('Cannot delete yourself');
        $stmt = $db->prepare("DELETE FROM fscrm_users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) jsonError('User not found', 404);
        jsonResponse(['message' => 'User deleted']);
        break;
}
