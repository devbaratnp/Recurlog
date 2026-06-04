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
            $row = fetchSingle('fscrm_orders', $id);
            requireExists($row, 'Order');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['o.title', 'o.description', 'o.customer_name', 'o.address', 'o.phone']);
        $filters = [];
        $filterParams = [];
        foreach (['status', 'customer_id', 'priority'] as $f) {
            $v = $_GET[$f] ?? null;
            if ($v !== null && $v !== '') {
                $filters[] = "o.$f = ?";
                $filterParams[] = $v;
            }
        }
        $filterClause = $filters ? 'AND ' . implode(' AND ', $filters) : '';
        $allParams = array_merge($searchParams, $filterParams);
        $types = str_repeat('s', count($allParams));
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_orders o WHERE 1=1 $searchClause $filterClause");
        if ($allParams) $stmt->bind_param($types, ...$allParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT o.* FROM fscrm_orders o WHERE 1=1 $searchClause $filterClause ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
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
        $row = insertAndFetch('fscrm_orders',
            ['customer_id', 'customer_name', 'address', 'phone', 'locality', 'title', 'description', 'status', 'priority', 'assigned_to', 'order_date', 'total_amount'],
            'issssssssssd',
            [
                $data['customer_id'] ?? null,
                $data['customer_name'] ?? '',
                $data['address'] ?? '',
                $data['phone'] ?? '',
                $data['locality'] ?? '',
                $data['title'] ?? '',
                $data['description'] ?? '',
                $data['status'] ?? 'pending',
                $data['priority'] ?? 'normal',
                $data['assigned_to'] ?? null,
                $data['order_date'] ?? null,
                $data['total_amount'] ?? 0
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
        $colMap = ['customer_id', 'customer_name', 'address', 'phone', 'locality', 'title', 'description', 'status', 'priority', 'assigned_to', 'order_date', 'total_amount'];
        foreach ($colMap as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= in_array($f, ['customer_id', 'total_amount']) ? (is_float($data[$f]) ? 'd' : 'i') : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_orders', $fields, $types, $vals, $id);
        requireExists($row, 'Order');
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        requireExists(fetchSingle('fscrm_orders', $id), 'Order');
        deleteById('fscrm_orders', $id);
        jsonResponse(['message' => 'Order deleted']);
        break;
}
