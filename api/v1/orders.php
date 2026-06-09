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
            $db = getDB();
            $stmt = $db->prepare("SELECT o.*, s.name AS assigned_staff_name FROM fscrm_orders o LEFT JOIN fscrm_staff s ON o.assigned_to = s.id WHERE o.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            requireExists($row, 'Order');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['o.problem', 'o.customer_name']);
        $filters = [];
        $filterParams = [];
        foreach (['status', 'customer_id', 'priority', 'scheduled_date'] as $f) {
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
        $stmt = $db->prepare("SELECT o.*, s.name AS assigned_staff_name FROM fscrm_orders o LEFT JOIN fscrm_staff s ON o.assigned_to = s.id WHERE 1=1 $searchClause $filterClause ORDER BY o.created_at DESC LIMIT ? OFFSET ?");
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
        foreach (['scheduled_date', 'completed_date', 'dispatch_date'] as $f) {
            if (isset($data[$f]) && $data[$f] === '') $data[$f] = null;
        }
        if (!empty($data['assigned_to']) && empty($data['assigned_staff_name'])) {
            $sStmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
            $sStmt->bind_param('i', $data['assigned_to']);
            $sStmt->execute();
            $sRow = $sStmt->get_result()->fetch_assoc();
            $data['assigned_staff_name'] = $sRow ? $sRow['name'] : '';
        }
        $row = insertAndFetch('fscrm_orders',
            ['customer_id', 'customer_name', 'service_for', 'problem', 'status', 'priority', 'assigned_to', 'assigned_staff_name', 'scheduled_date', 'notes', 'dispatch_date', 'dispatch_by', 'received_name', 'received_contact', 'signature'],
            'isssssiisssssss',
            [
                $data['customer_id'] ?? null,
                $data['customer_name'] ?? '',
                $data['service_for'] ?? '',
                $data['problem'] ?? '',
                $data['status'] ?? 'pending',
                $data['priority'] ?? 'normal',
                $data['assigned_to'] ?? null,
                $data['assigned_staff_name'] ?? '',
                $data['scheduled_date'] ?? null,
                $data['notes'] ?? '',
                $data['dispatch_date'] ?? null,
                $data['dispatch_by'] ?? '',
                $data['received_name'] ?? '',
                $data['received_contact'] ?? '',
                $data['signature'] ?? ''
            ]
        );
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $input = getJsonInput();
        $data = toSnake($input);
        foreach (['scheduled_date', 'completed_date', 'dispatch_date'] as $f) {
            if (isset($data[$f]) && $data[$f] === '') $data[$f] = null;
        }
        if (!empty($data['assigned_to']) && empty($data['assigned_staff_name'])) {
            $sStmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
            $sStmt->bind_param('i', $data['assigned_to']);
            $sStmt->execute();
            $sRow = $sStmt->get_result()->fetch_assoc();
            $data['assigned_staff_name'] = $sRow ? $sRow['name'] : '';
        }
        $fields = [];
        $types = '';
        $vals = [];
        $colMap = ['customer_id', 'customer_name', 'service_for', 'problem', 'status', 'priority', 'assigned_to', 'assigned_staff_name', 'scheduled_date', 'completed_date', 'notes', 'dispatch_date', 'dispatch_by', 'received_name', 'received_contact', 'signature'];
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
