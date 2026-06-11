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
            $row = fetchSingle('fscrm_order_fors', $id);
            requireExists($row, 'Order for');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['name']);
        $allParams = $searchParams;
        $types = str_repeat('s', count($allParams));
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_order_fors WHERE 1=1 $searchClause");
        if ($allParams) $stmt->bind_param($types, ...$allParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT * FROM fscrm_order_fors WHERE 1=1 $searchClause ORDER BY name ASC LIMIT ? OFFSET ?");
        $allParams2 = array_merge($allParams, [$perPage, $offset]);
        $types2 = $types . 'ii';
        if ($allParams) $stmt->bind_param($types2, ...$allParams2); else $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        paginatedResponse(toCamelArray($rows), $total, $page, $perPage);
        break;

    case 'POST':
        $input = getJsonInput();
        if (empty($input['name'])) jsonError('Name is required', 400, 'VALIDATION_ERROR');
        $row = insertAndFetch('fscrm_order_fors', ['name'], 's', [$input['name']]);
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $input = getJsonInput();
        if (empty($input['name'])) jsonError('Name is required', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_order_fors', ['name'], 's', [$input['name']], $id);
        requireExists($row, 'Order for');
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        requireExists(fetchSingle('fscrm_order_fors', $id), 'Order for');
        deleteById('fscrm_order_fors', $id);
        jsonResponse(['message' => 'Order for deleted']);
        break;
}
