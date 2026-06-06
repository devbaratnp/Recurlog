<?php
/**
 * Notification helper — createNotification() + sendPushNotification()
 *
 * Usage:
 *   require_once __DIR__ . '/includes/notification_helper.php';
 *   createNotification($db, $text, $type, $relatedId, $userId);
 *
 * Pass $userId explicitly, or it defaults to $_SESSION['user_id'].
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;

function createNotification($db, $text, $type, $relatedId = null, $userId = null) {
    if (!$userId) $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) return false;

    $stmt = $db->prepare("INSERT INTO fscrm_notifications (text, type, related_id) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $text, $type, $relatedId);
    $stmt->execute();
    $notifId = $db->insert_id;

    sendPushNotification($db, $userId, $text, $type, $relatedId);

    return $notifId;
}

function sendPushNotification($db, $userId, $text, $type, $relatedId = null) {
    $titleMap = [
        'task' => 'Task Update',
        'service' => 'Service Update',
        'task_completed' => 'Task Completed',
        'task_missed' => 'Task Missed',
        'customer_added' => 'New Customer',
        'service_added' => 'New Service',
        'order_created' => 'New Order',
        'order_assigned' => 'Order Assigned',
        'order_completed' => 'Order Completed',
    ];
    $title = $titleMap[$type] ?? 'Recurlog';

    $stmt = $db->prepare("SELECT id, platform, expo_token, endpoint, p256dh, auth FROM fscrm_push_tokens WHERE user_id = ? AND notifications_enabled = 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $tokens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($tokens)) return;

    $expoTokens = [];
    $webSubscriptions = [];

    foreach ($tokens as $t) {
        if (in_array($t['platform'], ['android', 'ios']) && $t['expo_token']) {
            $expoTokens[] = $t['expo_token'];
        } elseif ($t['platform'] === 'web' && $t['endpoint'] && $t['p256dh'] && $t['auth']) {
            $webSubscriptions[] = [
                'endpoint' => $t['endpoint'],
                'auth' => $t['auth'],
                'p256dh' => $t['p256dh'],
            ];
        }
    }

    // Send to Expo (mobile)
    if (!empty($expoTokens)) {
        $payload = [
            'to' => count($expoTokens) === 1 ? $expoTokens[0] : $expoTokens,
            'title' => $title,
            'body' => $text,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'recurlog-default',
            'data' => [
                'type' => $type,
                'relatedId' => $relatedId,
            ]
        ];

        $ch = curl_init('https://exp.host/--/api/v2/push/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if ($result && isset($result['data'])) {
                $dataItems = isset($result['data']['status']) ? [$result['data']] : $result['data'];
                foreach ($dataItems as $item) {
                    if (($item['status'] ?? '') === 'error') {
                        $err = $item['details']['error'] ?? '';
                        if ($err === 'DeviceNotRegistered' || $err === 'InvalidCredentials') {
                            $msg = $item['details']['message'] ?? '';
                            preg_match('/ExponentPushToken\[([^\]]+)\]/', $msg, $m);
                            $failedToken = $m[1] ?? '';
                            if ($failedToken) {
                                $cleanStmt = $db->prepare("DELETE FROM fscrm_push_tokens WHERE expo_token = ?");
                                $cleanStmt->bind_param('s', $failedToken);
                                $cleanStmt->execute();
                            }
                        }
                    }
                }
            }
        }
    }

    // Send Web Push via minishlink/web-push library
    if (!empty($webSubscriptions)) {
        $publicKey = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';
        $privateKey = defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : '';
        $subject = defined('VAPID_SUBJECT') ? VAPID_SUBJECT : 'mailto:admin@recurlog.com';

        if ($publicKey && $privateKey) {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $subject,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ]);

            $notifUrl = determineNotificationUrl($type, $relatedId);
            $pushPayload = json_encode([
                'title' => $title,
                'body' => $text,
                'icon' => '/assets/icons/icon-192.png',
                'badge' => '/assets/icons/icon-96.png',
                'data' => [
                    'type' => $type,
                    'relatedId' => $relatedId,
                    'url' => $notifUrl,
                ]
            ]);

            foreach ($webSubscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'publicKey' => $sub['p256dh'],
                        'authToken' => $sub['auth'],
                    ]),
                    $pushPayload
                );
            }

            foreach ($webPush->flush() as $report) {
                if (!$report->isSuccess()) {
                    $endpoint = $report->getEndpoint();
                    if ($report->isSubscriptionExpired()) {
                        $cleanStmt = $db->prepare("DELETE FROM fscrm_push_tokens WHERE endpoint = ?");
                        $cleanStmt->bind_param('s', $endpoint);
                        $cleanStmt->execute();
                    }
                }
            }
        }
    }
}

function determineNotificationUrl($type, $relatedId) {
    switch ($type) {
        case 'task':
        case 'task_completed':
        case 'task_missed':
            return $relatedId ? "/pages/tasks.php?task=$relatedId" : '/pages/tasks.php';
        case 'service':
        case 'service_added':
            return $relatedId ? "/pages/customer-detail.php?id=$relatedId" : '/pages/customers.php';
        case 'customer_added':
            return $relatedId ? "/pages/customer-detail.php?id=$relatedId" : '/pages/customers.php';
        case 'order_created':
        case 'order_assigned':
        case 'order_completed':
            return $relatedId ? "/pages/orders.php?order=$relatedId" : '/pages/orders.php';
        default:
            return '/pages/dashboard.php';
    }
}

function generateVapidKeys() {
    return VAPID::createVapidKeys();
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}
