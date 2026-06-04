<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../helpers.php';

requireBearerAuth();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$customerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$isRecurring = isset($_GET['is_recurring']) ? $_GET['is_recurring'] : null;

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $row = fetchSingle('fscrm_services', $id);
            requireExists($row, 'Service');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['s.service_name', 's.description']);
        $filters = [];
        $filterParams = [];
        if ($customerId) { $filters[] = 's.customer_id = ?'; $filterParams[] = $customerId; }
        if ($categoryId) { $filters[] = 's.category_id = ?'; $filterParams[] = $categoryId; }
        if ($isRecurring !== null) { $filters[] = 's.is_recurring = ?'; $filterParams[] = $isRecurring; }
        $filterClause = $filters ? 'AND ' . implode(' AND ', $filters) : '';
        $allParams = array_merge($searchParams, $filterParams);
        $types = str_repeat('s', count($allParams));
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_services s WHERE 1=1 $searchClause $filterClause");
        if ($allParams) $stmt->bind_param($types, ...$allParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT s.* FROM fscrm_services s WHERE 1=1 $searchClause $filterClause ORDER BY s.service_name ASC LIMIT ? OFFSET ?");
        $allParams2 = array_merge($allParams, [$perPage, $offset]);
        $types2 = $types . 'ii';
        if ($allParams) $stmt->bind_param($types2, ...$allParams2); else $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        paginatedResponse(toCamelArray($rows), $total, $page, $perPage);
        break;

    case 'POST':
        $input = getJsonInput();
        $data = toSnake($input);
        $row = insertAndFetch('fscrm_services',
            ['customer_id', 'category_id', 'service_name', 'description', 'is_recurring', 'rec_value', 'rec_unit', 'repeat_from', 'status'],
            'iississs',
            [
                $data['customer_id'] ?? null,
                $data['category_id'] ?? null,
                $data['service_name'] ?? '',
                $data['description'] ?? '',
                $data['is_recurring'] ?? 0,
                $data['rec_value'] ?? null,
                $data['rec_unit'] ?? '',
                $data['repeat_from'] ?? '',
                $data['status'] ?? 'active'
            ]
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
        $colMap = ['customer_id', 'category_id', 'service_name', 'description', 'is_recurring', 'rec_value', 'rec_unit', 'repeat_from', 'status'];
        foreach ($colMap as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= in_array($f, ['customer_id', 'category_id', 'is_recurring', 'rec_value']) ? 'i' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_services', $fields, $types, $vals, $id);
        requireExists($row, 'Service');
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        requireExists(fetchSingle('fscrm_services', $id), 'Service');
        deleteById('fscrm_services', $id);
        jsonResponse(['message' => 'Service deleted']);
        break;
}
