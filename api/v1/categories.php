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
            $row = fetchSingle('fscrm_categories', $id);
            requireExists($row, 'Category');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['name']);
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_categories WHERE 1=1 $searchClause");
        if ($searchParams) $stmt->bind_param(str_repeat('s', count($searchParams)), ...$searchParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT * FROM fscrm_categories WHERE 1=1 $searchClause ORDER BY name ASC LIMIT ? OFFSET ?");
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
        $row = insertAndFetch('fscrm_categories',
            ['name'],
            's',
            [$data['name'] ?? '']
        );
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $input = getJsonInput();
        $data = toSnake($input);
        if (!array_key_exists('name', $data)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_categories', ['name'], 's', [$data['name']], $id);
        requireExists($row, 'Category');
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        requireExists(fetchSingle('fscrm_categories', $id), 'Category');
        deleteById('fscrm_categories', $id);
        jsonResponse(['message' => 'Category deleted']);
        break;
}
