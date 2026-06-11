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
            $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS assigned_staff_name, COALESCE(t.is_recurring, 0) AS is_recurring, COALESCE(sv.problem, t.problem) AS service_problem, rt.title AS recurrence_title FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id LEFT JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id WHERE t.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            requireExists($row, 'Task');
            jsonResponse(toCamel($row));
        }
        [$page, $perPage, $offset] = getPageParams();
        $search = $_GET['search'] ?? '';
        [$searchClause, $searchParams] = buildSearchClause($search, ['t.title', 't.notes']);
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
        $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS assigned_staff_name, COALESCE(t.is_recurring, 0) AS is_recurring, COALESCE(sv.problem, t.problem) AS service_problem, rt.title AS recurrence_title FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id LEFT JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id WHERE 1=1 $searchClause $filterClause ORDER BY t.scheduled_date ASC, t.title ASC LIMIT ? OFFSET ?");
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
            ['service_id', 'recurring_task_id', 'customer_id', 'title', 'problem', 'status', 'scheduled_date', 'assigned_to', 'notes', 'category_id', 'is_recurring', 'rec_value', 'rec_unit', 'repeat_from'],
            'iiississiiiss',
            [
                $data['service_id'] ?? null,
                $data['recurring_task_id'] ?? null,
                $data['customer_id'] ?? null,
                $data['title'] ?? '',
                $data['problem'] ?? '',
                $data['status'] ?? 'pending',
                $data['scheduled_date'] ?? null,
                $data['assigned_to'] ?? null,
                $data['notes'] ?? '',
                $data['category_id'] ?? null,
                $data['is_recurring'] ?? 0,
                $data['rec_value'] ?? null,
                $data['rec_unit'] ?? null,
                $data['repeat_from'] ?? null
            ]
        );
        $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS assigned_staff_name, sv.is_recurring AS is_recurring, sv.problem AS service_problem FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id WHERE t.id = ?");
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
        $colMap = ['service_id', 'recurring_task_id', 'customer_id', 'title', 'problem', 'status', 'scheduled_date', 'completed_date', 'assigned_to', 'notes', 'category_id', 'completed_by', 'received_name', 'received_contact', 'signature', 'is_recurring', 'rec_value', 'rec_unit', 'repeat_from'];
        foreach ($colMap as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= in_array($f, ['service_id', 'recurring_task_id', 'customer_id', 'assigned_to', 'category_id', 'is_recurring', 'rec_value']) ? 'i' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $updateRow = updateAndFetch('fscrm_tasks', $fields, $types, $vals, $id);
        requireExists($updateRow, 'Task');

        // Recurrence engine: if completed and has recurrence config, generate next instance
        if (!empty($data['status']) && $data['status'] === 'completed') {
            $taskInfo = fetchSingle('fscrm_tasks', $id);
            if ($taskInfo && $taskInfo['recurring_task_id']) {
                // Template-linked recurring task
                $stmt2 = $db->prepare("SELECT t.id AS task_id, t.recurring_task_id, t.customer_id, t.title, t.problem, t.assigned_to, t.notes, t.scheduled_date, t.completed_date, rt.rec_value, rt.rec_unit, rt.repeat_from, rt.next_due_date FROM fscrm_tasks t JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id WHERE t.id = ?");
                $stmt2->bind_param('i', $id);
                $stmt2->execute();
                $rtRow = $stmt2->get_result()->fetch_assoc();
                if ($rtRow) {
                    $baseDate = $rtRow['repeat_from'] === 'last-done' ? ($rtRow['completed_date'] ?: $rtRow['scheduled_date']) : $rtRow['next_due_date'];
                    if ($baseDate) {
                        $dt = new DateTime($baseDate);
                        $unitMap = ['days' => 'D', 'weeks' => 'W', 'months' => 'M', 'years' => 'Y'];
                        $unit = $unitMap[$rtRow['rec_unit']] ?? 'D';
                        $dt->add(new DateInterval('P' . $rtRow['rec_value'] . $unit));
                        $nextDue = $dt->format('Y-m-d');

                        $uRt = $db->prepare("UPDATE fscrm_recurring_tasks SET last_completed_date = ?, next_due_date = ? WHERE id = ?");
                        $uRt->bind_param('ssi', $rtRow['completed_date'], $nextDue, $rtRow['recurring_task_id']);
                        $uRt->execute();
                        $iTask = $db->prepare("INSERT INTO fscrm_tasks (recurring_task_id, customer_id, title, problem, status, scheduled_date, assigned_to, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
                        $iTask->bind_param('iisssis', $rtRow['recurring_task_id'], $rtRow['customer_id'], $rtRow['title'], $rtRow['problem'], $nextDue, $rtRow['assigned_to'], $rtRow['notes']);
                        $iTask->execute();
                    }
                }
            } elseif ($taskInfo && !empty($taskInfo['is_recurring']) && !empty($taskInfo['rec_value'])) {
                // Standalone recurring task (no template)
                $baseDate = $taskInfo['repeat_from'] === 'last-done' ? ($taskInfo['completed_date'] ?: $taskInfo['scheduled_date']) : $taskInfo['scheduled_date'];
                if ($baseDate) {
                    $dt = new DateTime($baseDate);
                    $unitMap = ['days' => 'D', 'weeks' => 'W', 'months' => 'M', 'years' => 'Y'];
                    $unit = $unitMap[$taskInfo['rec_unit']] ?? 'D';
                    $dt->add(new DateInterval('P' . $taskInfo['rec_value'] . $unit));
                    $nextDue = $dt->format('Y-m-d');

                    $iTask = $db->prepare("INSERT INTO fscrm_tasks (customer_id, title, problem, status, scheduled_date, assigned_to, notes, is_recurring, rec_value, rec_unit, repeat_from) SELECT customer_id, title, problem, 'pending', ?, assigned_to, notes, is_recurring, rec_value, rec_unit, repeat_from FROM fscrm_tasks WHERE id = ?");
                    $iTask->bind_param('si', $nextDue, $id);
                    $iTask->execute();
                }
            }
        }

        $stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS assigned_staff_name, COALESCE(t.is_recurring, 0) AS is_recurring, COALESCE(sv.problem, t.problem) AS service_problem, rt.title AS recurrence_title FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id LEFT JOIN fscrm_recurring_tasks rt ON t.recurring_task_id = rt.id WHERE t.id = ?");
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
