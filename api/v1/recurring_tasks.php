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
            $stmt = $db->prepare("SELECT rt.*, c.name AS customer_name, s.name AS staff_name FROM fscrm_recurring_tasks rt LEFT JOIN fscrm_customers c ON rt.customer_id = c.id LEFT JOIN fscrm_staff s ON rt.assigned_to = s.id WHERE rt.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            requireExists($row, 'Recurring task');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['rt.title', 'rt.problem', 'rt.notes']);
        $filters = [];
        $filterParams = [];
        foreach (['customer_id', 'assigned_to'] as $f) {
            $v = $_GET[$f] ?? null;
            if ($v !== null && $v !== '') {
                $filters[] = "rt.$f = ?";
                $filterParams[] = $v;
            }
        }
        if (isset($_GET['is_active'])) {
            $filters[] = 'rt.is_active = ?';
            $filterParams[] = $_GET['is_active'];
        }
        $filterClause = $filters ? 'AND ' . implode(' AND ', $filters) : '';
        $allParams = array_merge($searchParams, $filterParams);
        $types = str_repeat('s', count($allParams));
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_recurring_tasks rt WHERE 1=1 $searchClause $filterClause");
        if ($allParams) $stmt->bind_param($types, ...$allParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT rt.*, c.name AS customer_name, s.name AS staff_name FROM fscrm_recurring_tasks rt LEFT JOIN fscrm_customers c ON rt.customer_id = c.id LEFT JOIN fscrm_staff s ON rt.assigned_to = s.id WHERE 1=1 $searchClause $filterClause ORDER BY rt.next_due_date ASC LIMIT ? OFFSET ?");
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
        $row = insertAndFetch('fscrm_recurring_tasks',
            ['customer_id', 'title', 'problem', 'assigned_to', 'notes', 'rec_value', 'rec_unit', 'repeat_from', 'next_due_date', 'is_active'],
            'issiisissi',
            [
                $data['customer_id'] ?? null,
                $data['title'] ?? '',
                $data['problem'] ?? '',
                $data['assigned_to'] ?? null,
                $data['notes'] ?? '',
                $data['rec_value'] ?? 1,
                $data['rec_unit'] ?? 'days',
                $data['repeat_from'] ?? 'last-done',
                $data['next_due_date'] ?? null,
                $data['is_active'] ?? 1
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
        $colMap = ['customer_id', 'title', 'problem', 'assigned_to', 'notes', 'rec_value', 'rec_unit', 'repeat_from', 'next_due_date', 'is_active', 'last_completed_date'];
        foreach ($colMap as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= in_array($f, ['customer_id', 'assigned_to', 'rec_value', 'is_active']) ? 'i' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_recurring_tasks', $fields, $types, $vals, $id);
        requireExists($row, 'Recurring task');
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("UPDATE fscrm_tasks SET recurring_task_id = NULL WHERE recurring_task_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            deleteById('fscrm_recurring_tasks', $id);
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            jsonError('Delete failed', 500, 'DB_ERROR');
        }
        jsonResponse(['message' => 'Recurring task deleted']);
        break;
}
