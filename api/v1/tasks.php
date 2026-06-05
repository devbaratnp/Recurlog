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
            $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS assigned_staff_name FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id WHERE t.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            requireExists($row, 'Task');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['t.title', 't.description', 't.notes']);
        $filters = [];
        $filterParams = [];
        foreach (['status', 'customer_id', 'assigned_to', 'service_id'] as $f) {
            $v = $_GET[$f] ?? null;
            if ($v !== null && $v !== '') {
                $filters[] = "t.$f = ?";
                $filterParams[] = $v;
            }
        }
        if (!empty($_GET['scheduled_date'])) {
            $filters[] = 't.scheduled_date = ?';
            $filterParams[] = $_GET['scheduled_date'];
        }
        if (!empty($_GET['start_date'])) {
            $filters[] = 't.scheduled_date >= ?';
            $filterParams[] = $_GET['start_date'];
        }
        if (!empty($_GET['end_date'])) {
            $filters[] = 't.scheduled_date <= ?';
            $filterParams[] = $_GET['end_date'];
        }
        $filterClause = $filters ? 'AND ' . implode(' AND ', $filters) : '';
        $allParams = array_merge($searchParams, $filterParams);
        $types = str_repeat('s', count($allParams));
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_tasks t WHERE 1=1 $searchClause $filterClause");
        if ($allParams) $stmt->bind_param($types, ...$allParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS assigned_staff_name FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id WHERE 1=1 $searchClause $filterClause ORDER BY t.scheduled_date ASC, t.title ASC LIMIT ? OFFSET ?");
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
        $insertRow = insertAndFetch('fscrm_tasks',
            ['customer_id', 'service_id', 'title', 'description', 'status', 'scheduled_date', 'assigned_to', 'priority', 'notes', 'is_recurring', 'rec_value', 'rec_unit', 'repeat_from'],
            'iissssisissis',
            [
                $data['customer_id'] ?? null,
                $data['service_id'] ?? null,
                $data['title'] ?? '',
                $data['description'] ?? '',
                $data['status'] ?? 'pending',
                $data['scheduled_date'] ?? null,
                $data['assigned_to'] ?? null,
                $data['priority'] ?? 'normal',
                $data['notes'] ?? '',
                $data['is_recurring'] ?? 0,
                $data['rec_value'] ?? null,
                $data['rec_unit'] ?? '',
                $data['repeat_from'] ?? ''
            ]
        );
        $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS assigned_staff_name FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id WHERE t.id = ?");
        $stmt->bind_param('i', $insertRow['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $input = getJsonInput();
        $data = toSnake($input);
        $fields = [];
        $types = '';
        $vals = [];
        $colMap = ['customer_id', 'service_id', 'title', 'description', 'status', 'scheduled_date', 'assigned_to', 'priority', 'notes', 'is_recurring', 'rec_value', 'rec_unit', 'repeat_from'];
        foreach ($colMap as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= in_array($f, ['customer_id', 'service_id', 'is_recurring', 'rec_value']) ? 'i' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $updateRow = updateAndFetch('fscrm_tasks', $fields, $types, $vals, $id);
        requireExists($updateRow, 'Task');
        $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS assigned_staff_name FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id WHERE t.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        requireExists(fetchSingle('fscrm_tasks', $id), 'Task');
        deleteById('fscrm_tasks', $id);
        jsonResponse(['message' => 'Task deleted']);
        break;
}
