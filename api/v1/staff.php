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
            $row = fetchSingle('fscrm_staff', $id);
            requireExists($row, 'Staff');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['name', 'phone']);
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_staff WHERE 1=1 $searchClause");
        if ($searchParams) $stmt->bind_param(str_repeat('s', count($searchParams)), ...$searchParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT * FROM fscrm_staff WHERE 1=1 $searchClause ORDER BY name ASC LIMIT ? OFFSET ?");
        $allParams = array_merge($searchParams, [$perPage, $offset]);
        $types = str_repeat('s', count($searchParams)) . 'ii';
        $stmt->bind_param($types, ...$allParams);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        paginatedResponse(toCamelArray($rows), $total, $page, $perPage);
        break;

    case 'POST':
        $input = getJsonInput();
        $data = toSnake($input);
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        $row = insertAndFetch('fscrm_staff',
            ['name', 'phone', 'avatar', 'active_tasks'],
            'sssi',
            [$data['name'] ?? '', $data['phone'] ?? '', $data['avatar'] ?? '', $data['active_tasks'] ?? 0]
        );

        if ($email && $password) {
            $db = getDB();
            $check = $db->prepare("SELECT id FROM fscrm_users WHERE email = ?");
            $check->bind_param('s', $email);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $auth = getAuthUser();
                $createdBy = $auth['userName'] ?? '';
                $staffId = (int)$row['id'];
                $stmt = $db->prepare("INSERT INTO fscrm_users (name, email, password, role, staff_id, is_active, created_by) VALUES (?, ?, ?, 'staff', ?, 1, ?)");
                $stmt->bind_param('sssis', $data['name'], $email, $hash, $staffId, $createdBy);
                $stmt->execute();
            }
        }

        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $input = getJsonInput();
        $data = toSnake($input);
        $fields = [];
        $types = '';
        $vals = [];
        foreach (['name', 'phone', 'avatar', 'active_tasks'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= is_int($data[$f]) ? 'i' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_staff', $fields, $types, $vals, $id);
        requireExists($row, 'Staff');
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        requireExists(fetchSingle('fscrm_staff', $id), 'Staff');
        deleteById('fscrm_staff', $id);
        jsonResponse(['message' => 'Staff deleted']);
        break;
}
