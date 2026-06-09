<?php
require_once __DIR__ . '/config.php';

requireBearerAuth();

$method = $_SERVER['REQUEST_METHOD'];
$auth = getAuthUser();
$userId = (int)($auth['userId'] ?? 0);
$db = getDB();

switch ($method) {
    case 'GET':
        $stmt = $db->prepare("SELECT * FROM fscrm_push_tokens WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        jsonResponse(toCamelArray($rows));
        break;

    case 'POST':
        $input = getJsonInput();
        $data = toSnake($input);
        $expoToken = $data['expo_token'] ?? '';
        $platform = $data['platform'] ?? 'android';
        $deviceName = $data['device_name'] ?? '';
        $appVersion = $data['app_version'] ?? '';

        if (!$expoToken) jsonError('expoToken is required', 400, 'VALIDATION_ERROR');

        $check = $db->prepare("SELECT id FROM fscrm_push_tokens WHERE user_id = ? AND expo_token = ?");
        $check->bind_param('is', $userId, $expoToken);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            $stmt = $db->prepare("UPDATE fscrm_push_tokens SET platform = ?, device_name = ?, app_version = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('sssi', $platform, $deviceName, $appVersion, $existing['id']);
            $stmt->execute();
            $row = fetchSingle('fscrm_push_tokens', $existing['id']);
        } else {
            $row = insertAndFetch('fscrm_push_tokens',
                ['user_id', 'platform', 'expo_token', 'device_name', 'app_version'],
                'issss',
                [$userId, $platform, $expoToken, $deviceName, $appVersion]
            );
        }
        jsonResponse(toCamel($row), $existing ? 200 : 201);
        break;

    case 'PUT':
        $input = getJsonInput();
        $data = toSnake($input);

        if (isset($data['all']) && $data['all'] === true) {
            $stmt = $db->prepare("UPDATE fscrm_push_tokens SET notifications_enabled = 0 WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            jsonResponse(['message' => 'All tokens disabled']);
        }

        $tokenId = $data['id'] ?? ($_GET['id'] ?? null);
        if (!$tokenId) jsonError('Token ID is required', 400, 'VALIDATION_ERROR');

        $fields = [];
        $types = '';
        $vals = [];
        foreach (['notifications_enabled', 'device_name', 'app_version', 'platform'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = $f;
                $types .= in_array($f, ['notifications_enabled']) ? 'i' : 's';
                $vals[] = $data[$f];
            }
        }
        if (empty($fields)) jsonError('No fields to update', 400, 'VALIDATION_ERROR');
        $row = updateAndFetch('fscrm_push_tokens', $fields, $types, $vals, $tokenId);
        jsonResponse(toCamel($row));
        break;

    case 'DELETE':
        $input = getJsonInput();
        $data = $input ?: [];

        if (!empty($data['all'])) {
            $stmt = $db->prepare("DELETE FROM fscrm_push_tokens WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            jsonResponse(['message' => 'All tokens deleted']);
        }

        $expoToken = $data['expo_token'] ?? '';
        if (!$expoToken) jsonError('expoToken is required', 400, 'VALIDATION_ERROR');

        $stmt = $db->prepare("DELETE FROM fscrm_push_tokens WHERE user_id = ? AND expo_token = ?");
        $stmt->bind_param('is', $userId, $expoToken);
        $stmt->execute();
        jsonResponse(['message' => 'Token unregistered']);
        break;
}
