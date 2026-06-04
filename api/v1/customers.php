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
            $row = fetchSingle('fscrm_customers', $id);
            requireExists($row, 'Customer');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['name', 'phone', 'address', 'area']);
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_customers WHERE 1=1 $searchClause");
        if ($searchParams) $stmt->bind_param(str_repeat('s', count($searchParams)), ...$searchParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT * FROM fscrm_customers WHERE 1=1 $searchClause ORDER BY name ASC LIMIT ? OFFSET ?");
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
        $row = insertAndFetch('fscrm_customers',
            ['name', 'address', 'area', 'phone', 'services_for', 'location_lat', 'location_lng'],
            'sssssdd',
            [$data['name'] ?? '', $data['address'] ?? '', $data['area'] ?? '', $data['phone'] ?? '', $data['services_for'] ?? '', $data['location_lat'] ?? null, $data['location_lng'] ?? null]
        );
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $input = getJsonInput();
        $data = toSnake($input);
        $fields = [];
        $types = '';
        $vals = [];
        foreach (['name', 'address', 'area', 'phone', 'services_for', 'location_lat', 'location_lng'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= is_float($data[$f]) ? 'd' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_customers', $fields, $types, $vals, $id);
        requireExists($row, 'Customer');
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        requireExists(fetchSingle('fscrm_customers', $id), 'Customer');
        deleteById('fscrm_customers', $id);
        jsonResponse(['message' => 'Customer deleted']);
        break;
}
