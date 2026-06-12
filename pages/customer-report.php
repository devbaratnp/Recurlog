<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();

$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$customerId) {
    header('Location: customers.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM fscrm_customers WHERE id = ?");
$stmt->bind_param('i', $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
if (!$customer) {
    header('Location: customers.php');
    exit;
}

// Completed tasks
$taskStmt = $db->prepare("SELECT * FROM fscrm_tasks WHERE customer_id = ? AND status = 'completed' ORDER BY completed_date ASC, scheduled_date ASC");
$taskStmt->bind_param('i', $customerId);
$taskStmt->execute();
$completedTasks = $taskStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Completed orders
$orderStmt = $db->prepare("SELECT * FROM fscrm_orders WHERE customer_id = ? AND status = 'completed' ORDER BY COALESCE(completed_date, scheduled_date) ASC");
$orderStmt->bind_param('i', $customerId);
$orderStmt->execute();
$completedOrders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build chronological entries
$entries = [];
foreach ($completedTasks as $t) {
    $date = $t['completed_date'] ?: $t['scheduled_date'];
    $desc = ($t['notes'] && trim($t['notes'])) ? $t['notes'] : $t['title'];
    $entries[] = ['date' => $date, 'desc' => $desc];
}
foreach ($completedOrders as $o) {
    $date = $o['completed_date'] ?: $o['scheduled_date'];
    if (!$date && !empty($o['created_at'])) $date = substr($o['created_at'], 0, 10);
    $desc = ($o['notes'] && trim($o['notes'])) ? $o['notes'] : ($o['problem'] ?: 'Order completed');
    $entries[] = ['date' => $date, 'desc' => $desc];
}
usort($entries, function($a, $b) {
    return strcmp($a['date'] ?: '', $b['date'] ?: '');
});

function fmtDate($d) {
    if (!$d) return '—';
    return date('M j, Y', strtotime($d));
}
?><?php $pageTitle = 'Customer Report'; require_once '../includes/header.php'; ?>
<style>
    @media print {
      .sidebar, .sidebar-backdrop, .page-header, .bottom-nav, .no-print { display: none !important; }
      .page-content { margin: 0 !important; }
      body { background: #fff !important; }
      .report-sheet { box-shadow: none !important; border: none !important; }
    }
  </style>
<div class="page-content" id="page-content">
    <header class="page-header no-print">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <a href="customer-detail.php?id=<?= $customerId ?>" class="p-2 -ml-1 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
          </a>
          <h1 class="page-title">Customer Report</h1>
        </div>
        <button onclick="window.print()" class="btn btn-sm btn-primary">
          <i data-lucide="printer" class="w-4 h-4"></i> Print
        </button>
      </div>
    </header>

    <div class="p-4 md:p-6 max-w-3xl mx-auto">
      <div class="card p-6 sm:p-8 report-sheet">
        <h2 class="text-xl font-bold text-navy mb-4">Customer Report</h2>

        <div class="mb-6">
          <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($customer['name']) ?></p>
          <p class="text-gray-600"><?= htmlspecialchars($customer['area'] ?: $customer['address'] ?: '') ?></p>
          <p class="text-gray-600"><?= htmlspecialchars($customer['phone'] ?: '') ?></p>
        </div>

<?php if (empty($entries)): ?>
        <div class="empty-state">
          <i data-lucide="file-text"></i>
          <p>No completed work recorded yet for this customer.</p>
        </div>
<?php else: ?>
        <table class="w-full text-sm">
          <tbody>
<?php foreach ($entries as $e): ?>
            <tr class="border-b border-gray-100 align-top">
              <td class="py-3 pr-4 whitespace-nowrap text-gray-500 font-medium" style="width:120px"><?= fmtDate($e['date']) ?></td>
              <td class="py-3 text-gray-800"><?= htmlspecialchars($e['desc']) ?></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
<?php endif; ?>
      </div>
    </div>
  </div>
  <script>
    (function() {
      var lsId = localStorage.getItem('fscrm_currentCustomerId');
      var urlId = new URLSearchParams(window.location.search).get('id');
      if (!urlId && lsId) {
        window.location.replace('customer-report.php?id=' + encodeURIComponent(lsId));
      }
    })();
  </script>
</main>
<?php require_once '../includes/footer.php'; ?>
