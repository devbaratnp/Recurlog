<?php
require_once '../includes/config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    requireCsrfToken();
    $db->query("UPDATE fscrm_notifications SET is_read = 1 WHERE is_read = 0");
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $token = $_GET['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        exit;
    }
    $stmt = $db->prepare("UPDATE fscrm_notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: notifications.php');
    exit;
}

$result = $db->query("SELECT * FROM fscrm_notifications ORDER BY created_at DESC");
$notifications = $result->fetch_all(MYSQLI_ASSOC);

$unreadCount = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unreadCount++;
}

function notifIcon($type) {
    $map = array(
        'task_completed' => array('check-circle', '#22C55E'),
        'task_missed' => array('alert-circle', '#EF4444'),
        'service_added' => array('plus-circle', '#0EA5E9'),
        'customer_added' => array('user-plus', '#22C55E'),
        'order_created' => array('clipboard-list', '#3B82F6'),
        'order_assigned' => array('user-check', '#8B5CF6'),
        'order_completed' => array('check-circle', '#22C55E'),
    );
    return isset($map[$type]) ? $map[$type] : array('info', '#6B7280');
}

function relativeTime($dateStr) {
    $ts = strtotime($dateStr);
    $diff = time() - $ts;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $ts);
}

$pageTitle = 'Notifications';
?>
<?php require_once '../includes/header.php'; ?>
  <div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Notifications</h1>
          <span id="notification-badge" class="badge badge-info ml-1"<?= $unreadCount === 0 ? ' style="display:none"' : '' ?>><?= $unreadCount ?></span>
        </div>
        <form method="POST" action="" style="display:inline">
          <?= csrfHiddenField() ?>
          <button type="submit" name="mark_all_read" class="btn btn-sm btn-ghost">
            <i data-lucide="check-check" class="w-4 h-4"></i> Mark All Read
          </button>
        </form>
      </div>
    </header>

    <div class="p-4 sm:p-6">
      <div id="notifications-list" class="space-y-2">
        <?php if (count($notifications) > 0): ?>
          <?php foreach ($notifications as $n):
            $ico = notifIcon($n['type']);
          ?>
          <?php if ($n['is_read']): ?>
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-start gap-3 hover:shadow-md transition-all">
          <?php else: ?>
          <a href="?mark_read=<?= (int)$n['id'] ?>&csrf_token=<?= urlencode(getCsrfToken()) ?>" class="block no-underline">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-start gap-3 hover:shadow-md transition-all border-l-4 border-l-brand">
          <?php endif; ?>
              <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background:<?= $ico[1] ?>15">
                <i data-lucide="<?= $ico[0] ?>" class="w-5 h-5" style="color:<?= $ico[1] ?>"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700 leading-relaxed"><?= htmlspecialchars($n['text']) ?></p>
                <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars(relativeTime($n['created_at'])) ?></p>
              </div>
              <?php if (!$n['is_read']): ?>
              <span class="w-2 h-2 rounded-full bg-brand flex-shrink-0 mt-1.5"></span>
              <?php endif; ?>
            </div>
          <?php if (!$n['is_read']): ?>
          </a>
          <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div id="notifications-empty" class="empty-state<?= count($notifications) > 0 ? ' hidden' : '' ?>">
        <i data-lucide="bell-off"></i>
        <p>No notifications yet</p>
      </div>
    </div>
  </div>
  <?php require_once '../includes/footer.php'; ?>
</body>
</html>
