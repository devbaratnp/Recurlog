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
            $row = fetchSingle('fscrm_notifications', $id);
            requireExists($row, 'Notification');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['title', 'message']);
        $filters = [];
        $filterParams = [];
        $v = $_GET['is_read'] ?? null;
        if ($v !== null && $v !== '') {
            $filters[] = 'is_read = ?';
            $filterParams[] = $v;
        }
        $filterClause = $filters ? 'AND ' . implode(' AND ', $filters) : '';
        $allParams = array_merge($searchParams, $filterParams);
        $types = str_repeat('s', count($allParams));
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM fscrm_notifications WHERE 1=1 $searchClause $filterClause");
        if ($allParams) $stmt->bind_param($types, ...$allParams);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt = $db->prepare("SELECT * FROM fscrm_notifications WHERE 1=1 $searchClause $filterClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
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
        $row = insertAndFetch('fscrm_notifications',
            ['text', 'type', 'related_id', 'is_read'],
            'ssii',
            [
                $data['text'] ?? '',
                $data['type'] ?? 'info',
                $data['related_id'] ?? null,
                $data['is_read'] ?? 0
            ]
        );
        jsonResponse(toCamel($row), 201);
        break;

    case 'PUT':
        if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
            $stmt = $db->prepare("UPDATE fscrm_notifications SET is_read = 1 WHERE is_read = 0");
            $stmt->execute();
            jsonResponse(['message' => 'All notifications marked as read']);
        }
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        $input = getJsonInput();
        $data = toSnake($input);
        $fields = [];
        $types = '';
        $vals = [];
        foreach (['text', 'type', 'related_id', 'is_read'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= in_array($f, ['related_id', 'is_read']) ? 'i' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_notifications', $fields, $types, $vals, $id);
        requireExists($row, 'Notification');
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        if (!$id) jsonError('ID is required', 400, 'VALIDATION_ERROR');
        requireExists(fetchSingle('fscrm_notifications', $id), 'Notification');
        deleteById('fscrm_notifications', $id);
        jsonResponse(['message' => 'Notification deleted']);
        break;
}
