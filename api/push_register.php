<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'POST':
        requireAuth();
        $input = getJsonInput();
        $platform = $input['platform'] ?? '';
        $userId = (int)$_SESSION['user_id'];

        if (!in_array($platform, ['android', 'ios', 'web'])) {
            jsonError('Invalid platform. Must be android, ios, or web.', 400);
        }

        if ($platform === 'web') {
            $endpoint = $input['endpoint'] ?? '';
            $p256dh = $input['p256dh'] ?? '';
            $auth = $input['auth'] ?? '';
            $deviceName = $input['deviceName'] ?? 'Web Browser';
            $appVersion = $input['appVersion'] ?? '';

            if (!$endpoint || !$p256dh || !$auth) {
                jsonError('Missing web push subscription data', 400);
            }

            $stmt = $db->prepare("SELECT id FROM fscrm_push_tokens WHERE user_id = ? AND platform = 'web' AND endpoint = ?");
            $stmt->bind_param('is', $userId, $endpoint);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if ($existing) {
                $stmt = $db->prepare("UPDATE fscrm_push_tokens SET p256dh = ?, auth = ?, device_name = ?, app_version = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param('ssssi', $p256dh, $auth, $deviceName, $appVersion, $existing['id']);
                $stmt->execute();
            } else {
                $stmt = $db->prepare("INSERT INTO fscrm_push_tokens (user_id, platform, endpoint, p256dh, auth, device_name, app_version) VALUES (?, 'web', ?, ?, ?, ?, ?)");
                $stmt->bind_param('isssss', $userId, $endpoint, $p256dh, $auth, $deviceName, $appVersion);
                $stmt->execute();
            }
        } else {
            $expoToken = $input['expoToken'] ?? '';
            $deviceName = $input['deviceName'] ?? 'Mobile Device';
            $appVersion = $input['appVersion'] ?? '';

            if (!$expoToken) {
                jsonError('Missing Expo push token', 400);
            }

            $stmt = $db->prepare("SELECT id FROM fscrm_push_tokens WHERE user_id = ? AND platform = ? AND expo_token = ?");
            $stmt->bind_param('iss', $userId, $platform, $expoToken);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if ($existing) {
                $stmt = $db->prepare("UPDATE fscrm_push_tokens SET device_name = ?, app_version = ?, notifications_enabled = 1, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param('ssi', $deviceName, $appVersion, $existing['id']);
                $stmt->execute();
            } else {
                $stmt = $db->prepare("INSERT INTO fscrm_push_tokens (user_id, platform, expo_token, device_name, app_version) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('issss', $userId, $platform, $expoToken, $deviceName, $appVersion);
                $stmt->execute();
            }
        }

        jsonResponse(['message' => 'Push token registered']);
        break;

    case 'DELETE':
        requireAuth();
        $userId = (int)$_SESSION['user_id'];
        $input = getJsonInput();

        $expoToken = $input['expoToken'] ?? '';
        $endpoint = $input['endpoint'] ?? '';
        $allDevices = !empty($input['all']);

        if ($allDevices) {
            $stmt = $db->prepare("DELETE FROM fscrm_push_tokens WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
        } elseif ($expoToken) {
            $stmt = $db->prepare("DELETE FROM fscrm_push_tokens WHERE user_id = ? AND expo_token = ?");
            $stmt->bind_param('is', $userId, $expoToken);
            $stmt->execute();
        } elseif ($endpoint) {
            $stmt = $db->prepare("DELETE FROM fscrm_push_tokens WHERE user_id = ? AND endpoint = ?");
            $stmt->bind_param('is', $userId, $endpoint);
            $stmt->execute();
        } else {
            jsonError('Provide expoToken, endpoint, or all=true to unregister', 400);
        }

        jsonResponse(['message' => 'Push token(s) removed']);
        break;

    case 'PUT':
        requireAuth();
        $userId = (int)$_SESSION['user_id'];
        $input = getJsonInput();

        $enabled = isset($input['notificationsEnabled']) ? ($input['notificationsEnabled'] ? 1 : 0) : null;
        $tokenId = isset($input['tokenId']) ? (int)$input['tokenId'] : null;

        if ($enabled !== null && $tokenId) {
            $stmt = $db->prepare("UPDATE fscrm_push_tokens SET notifications_enabled = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param('iii', $enabled, $tokenId, $userId);
            $stmt->execute();
        } elseif ($enabled !== null) {
            $stmt = $db->prepare("UPDATE fscrm_push_tokens SET notifications_enabled = ? WHERE user_id = ?");
            $stmt->bind_param('ii', $enabled, $userId);
            $stmt->execute();
        } else {
            jsonError('Provide notificationsEnabled and optionally tokenId', 400);
        }

        jsonResponse(['message' => 'Preferences updated']);
        break;

    case 'GET':
        requireAuth();
        $userId = (int)$_SESSION['user_id'];
        $stmt = $db->prepare("SELECT id, platform, device_name, app_version, notifications_enabled, created_at, updated_at FROM fscrm_push_tokens WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        jsonResponse($rows);
        break;
}
