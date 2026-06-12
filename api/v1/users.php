<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../helpers.php';

requireBearerAuth();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT id, name, email, plain_password, role, staff_id, is_active, created_at, created_by FROM fscrm_users WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            requireExists($row, 'User');
            $row['id'] = (int)$row['id'];
            $row['staff_id'] = $row['staff_id'] ? (int)$row['staff_id'] : null;
            $row['is_active'] = (int)$row['is_active'];
            jsonResponse(toCamel($row));
        }
        $result = $db->query("SELECT id, name, email, plain_password, role, staff_id, is_active, created_at, created_by FROM fscrm_users ORDER BY created_at DESC");
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as &$r) {
            $r['id'] = (int)$r['id'];
            $r['staff_id'] = $r['staff_id'] ? (int)$r['staff_id'] : null;
            $r['is_active'] = (int)$r['is_active'];
        }
        jsonResponse(toCamelArray($rows));
        break;

    case 'POST':
        $input = getJsonInput();
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'staff';
        $staffId = isset($input['staffId']) ? intval($input['staffId']) : null;

        if (!$name || !$email || !$password) {
            jsonError('Name, email, and password are required', 400, 'VALIDATION_ERROR');
        }

        $check = $db->prepare("SELECT id FROM fscrm_users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            jsonError('Email already exists', 409, 'DUPLICATE_EMAIL');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $auth = getAuthUser();
        $createdBy = $auth['userName'] ?? '';

        $columns = ['name', 'email', 'password', 'plain_password', 'role', 'is_active', 'created_by'];
        $types = 'sssssis';
        $values = [$name, $email, $hash, $password, $role, 1, $createdBy];

        if ($staffId) {
            $columns[] = 'staff_id';
            $types .= 'i';
            $values[] = $staffId;
        }

        $row = insertAndFetch('fscrm_users', $columns, $types, $values);
        $row['id'] = (int)$row['id'];
        $row['staff_id'] = $row['staff_id'] ? (int)$row['staff_id'] : null;
        $row['is_active'] = (int)$row['is_active'];
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $input = getJsonInput();
        $fields = [];
        $types = '';
        $vals = [];

        $fieldMap = ['name' => 's', 'email' => 's', 'role' => 's', 'staffId' => 'i', 'isActive' => 'i'];
        foreach ($fieldMap as $camel => $type) {
            if (array_key_exists($camel, $input)) {
                $snake = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $camel));
                $fields[] = $snake;
                $types .= $type;
                $vals[] = $type === 'i' ? (int)$input[$camel] : $input[$camel];
            }
        }

        if (array_key_exists('password', $input) && !empty(trim($input['password']))) {
            $plain = trim($input['password']);
            $fields[] = 'password';
            $types .= 's';
            $vals[] = password_hash($plain, PASSWORD_DEFAULT);
            $fields[] = 'plain_password';
            $types .= 's';
            $vals[] = $plain;
        }

        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_users', $fields, $types, $vals, $id);
        requireExists($row, 'User');
        $row['id'] = (int)$row['id'];
        $row['staff_id'] = $row['staff_id'] ? (int)$row['staff_id'] : null;
        $row['is_active'] = (int)$row['is_active'];
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $auth = getAuthUser();
        if ($id === ($auth['userId'] ?? null)) jsonError('Cannot delete yourself', 403, 'FORBIDDEN');
        requireExists(fetchSingle('fscrm_users', $id), 'User');
        deleteById('fscrm_users', $id);
        jsonResponse(['message' => 'User deleted']);
        break;
}
