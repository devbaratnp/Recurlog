<?php require_once '../includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Orders - Recurlog</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/custom.css?v=<?= cacheBust() ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: '#1DB954', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' },
          fontFamily: { sans: ['Poppins', 'sans-serif'] }
        }
      }
    }
  </script>
  <style>
    .signature-pad {
      width: 100%;
      height: 160px;
      border: 1px dashed #cbd5e1;
      border-radius: 10px;
      background: #fff;
      touch-action: none;
      cursor: crosshair;
      display: block;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
<?php $pageTitle = 'Orders'; require_once '../includes/header.php'; ?>
<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// ========== POST HANDLERS ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];

  if ($action === 'add_locality') {
    header('Content-Type: application/json');
    requireCsrfToken();
    $name = trim($_POST['name'] ?? '');
    if ($name) {
      $stmt = $db->prepare("INSERT IGNORE INTO fscrm_localities (name) VALUES (?)");
      $stmt->bind_param('s', $name);
      $stmt->execute();
      echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
      echo json_encode(['success' => false]);
    }
    exit;
  }

  $redirect = true;

  if ($action === 'create_order') {
    requireCsrfToken();
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $inlineName = trim($_POST['inline_cust_name'] ?? '');
    $customerName = '';

    $db->begin_transaction();
    try {
      if ($inlineName) {
        $customerName = $inlineName;
        $phone = trim($_POST['inline_cust_phone'] ?? '');
        $address = trim($_POST['inline_cust_address'] ?? '');
        $area = trim($_POST['inline_cust_area'] ?? '');
        if (!$area) {
          $area = trim($_POST['inline_new_locality'] ?? '');
        }
        $service = trim($_POST['inline_cust_service'] ?? '');
        $svc = $service ? '["' . $db->real_escape_string($service) . '"]' : '[]';

        if (!$inlineName) {
          throw new Exception('Customer name is required');
        }

        $stmt = $db->prepare("INSERT INTO fscrm_customers (name, phone, address, area, services_for) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $inlineName, $phone, $address, $area, $svc);
        $stmt->execute();
        $customerId = $db->insert_id;
      } elseif ($customerId) {
        $stmt = $db->prepare("SELECT name FROM fscrm_customers WHERE id = ?");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $customerName = $row ? $row['name'] : '';
      }

      $problem = trim($_POST['problem'] ?? '');
      $date = trim($_POST['scheduled_date'] ?? '');
      $staffId = (int)($_POST['assigned_to'] ?? 0);
      $staffName = '';

      if ($staffId) {
        $stmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        $staffRow = $stmt->get_result()->fetch_assoc();
        $staffName = $staffRow ? $staffRow['name'] : '';
      }

      $status = $staffId ? 'assigned' : 'pending';

      $stmt = $db->prepare("INSERT INTO fscrm_orders (customer_id, customer_name, problem, status, priority, assigned_to, assigned_staff_name, scheduled_date) VALUES (?, ?, ?, ?, 'normal', ?, ?, ?)");
      $stmt->bind_param('isssiss', $customerId, $customerName, $problem, $status, $staffId ?: null, $staffName ?: null, $date ?: null);
      $stmt->execute();
      $db->commit();
      setFlash('Order #' . $db->insert_id . ' created successfully');
    } catch (Exception $e) {
      $db->rollback();
      error_log('Order create failed: ' . $e->getMessage());
    }
  }

  elseif ($action === 'assign_order') {
    requireCsrfToken();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $date = trim($_POST['scheduled_date'] ?? '');

    if ($orderId && $staffId) {
      $stmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
      $stmt->bind_param('i', $staffId);
      $stmt->execute();
      $staffRow = $stmt->get_result()->fetch_assoc();
      $staffName = $staffRow ? $staffRow['name'] : '';

      $stmt = $db->prepare("UPDATE fscrm_orders SET status = 'assigned', assigned_to = ?, assigned_staff_name = ?, scheduled_date = ? WHERE id = ?");
      $stmt->bind_param('issi', $staffId, $staffName, $date ?: null, $orderId);
      $stmt->execute();
      setFlash('Order #' . $orderId . ' assigned to ' . $staffName);
    }
  }

  elseif ($action === 'complete_order') {
    requireCsrfToken();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $dispatchDate = trim($_POST['dispatch_date'] ?? '');
    $dispatchBy = trim($_POST['dispatch_by'] ?? '');
    $receivedName = trim($_POST['received_name'] ?? '');
    $receivedContact = trim($_POST['received_contact'] ?? '');
    $signature = trim($_POST['signature_data'] ?? '');
    $createTask = isset($_POST['create_task']);

    if ($orderId) {
      $db->begin_transaction();
      try {
        $stmt = $db->prepare("UPDATE fscrm_orders SET status = 'completed', completed_date = CURDATE(), notes = ?, dispatch_date = ?, dispatch_by = ?, received_name = ?, received_contact = ?, signature = ? WHERE id = ?");
        $stmt->bind_param('ssssssi', $notes, $dispatchDate ?: null, $dispatchBy ?: null, $receivedName ?: null, $receivedContact ?: null, $signature ?: null, $orderId);
        $stmt->execute();

        if ($createTask) {
          $stmt = $db->prepare("SELECT customer_id, customer_name, problem, assigned_to, assigned_staff_name, scheduled_date FROM fscrm_orders WHERE id = ?");
          $stmt->bind_param('i', $orderId);
          $stmt->execute();
          $orderRow = $stmt->get_result()->fetch_assoc();
          if ($orderRow) {
            $title = 'Order completed: ' . ($orderRow['customer_name'] ?: 'Customer #' . $orderRow['customer_id']);
            $stmt = $db->prepare("INSERT INTO fscrm_tasks (customer_id, title, status, scheduled_date, completed_date, assigned_to, notes, received_name, received_contact, signature) VALUES (?, ?, 'completed', ?, CURDATE(), ?, ?, ?, ?, ?)");
            $stmt->bind_param('isssisss', $orderRow['customer_id'], $title, $orderRow['scheduled_date'] ?: date('Y-m-d'), $orderRow['assigned_to'], $notes, $receivedName, $receivedContact, $signature);
            $stmt->execute();
          }
        }
        $db->commit();
        setFlash('Order #' . $orderId . ' completed successfully');
      } catch (Exception $e) {
        $db->rollback();
        error_log('Order complete failed: ' . $e->getMessage());
      }
    }
  }

  elseif ($action === 'cancel_order') {
    requireCsrfToken();
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId) {
      $stmt = $db->prepare("UPDATE fscrm_orders SET status = 'cancelled' WHERE id = ?");
      $stmt->bind_param('i', $orderId);
      $stmt->execute();
      setFlash('Order #' . $orderId . ' cancelled');
    }
  }

  if ($redirect) {
    header('Location: orders.php');
    exit;
  }
}

// ========== QUERIES ==========
$totalRecords = (int)$db->query("SELECT COUNT(*) as cnt FROM fscrm_orders")->fetch_assoc()['cnt'];
$totalPages = max(1, (int)ceil($totalRecords / $perPage));

$orders = $db->query("SELECT * FROM fscrm_orders ORDER BY created_at DESC LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
$staff_list = $db->query("SELECT * FROM fscrm_staff ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$customers = $db->query("SELECT * FROM fscrm_customers ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$service_types = $db->query("SELECT * FROM fscrm_service_types ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$localities = $db->query("SELECT id, name FROM fscrm_localities ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$orders_js = [];
foreach ($orders as $o) {
  $orders_js[] = [
    'id' => (int)$o['id'],
    'customerId' => (int)$o['customer_id'],
    'customerName' => $o['customer_name'],
    'serviceFor' => $o['service_for'],
    'problem' => $o['problem'],
    'status' => $o['status'],
    'priority' => $o['priority'],
    'assignedTo' => $o['assigned_to'] ? (int)$o['assigned_to'] : null,
    'assignedStaffName' => $o['assigned_staff_name'],
    'scheduledDate' => $o['scheduled_date'],
    'completedDate' => $o['completed_date'],
    'notes' => $o['notes'],
    'dispatchDate' => $o['dispatch_date'],
    'dispatchBy' => $o['dispatch_by'],
    'receivedName' => $o['received_name'],
    'receivedContact' => $o['received_contact'],
    'signature' => $o['signature'],
    'createdAt' => $o['created_at']
  ];
}

// ========== HELPERS ==========
function orderStatusBadge($status) {
  $map = [
    'pending' => '<span class="badge badge-order-pending"><i data-lucide="clock" class="w-3 h-3"></i> Pending</span>',
    'assigned' => '<span class="badge badge-order-assigned"><i data-lucide="user-check" class="w-3 h-3"></i> Assigned</span>',
    'completed' => '<span class="badge badge-order-completed"><i data-lucide="check-circle" class="w-3 h-3"></i> Completed</span>',
    'cancelled' => '<span class="badge badge-order-cancelled"><i data-lucide="x-circle" class="w-3 h-3"></i> Cancelled</span>'
  ];
  return $map[$status] ?? $map['pending'];
}

function priorityBadge($priority) {
  if ($priority === 'urgent') {
    return '<span class="badge badge-priority-urgent"><i data-lucide="alert-triangle" class="w-3 h-3"></i> Urgent</span>';
  }
  return '<span class="badge badge-priority-normal">Normal</span>';
}

function fmtDate($date) {
  if (!$date) return '';
  $ts = strtotime($date);
  return date('M j, Y', $ts);
}

function fmtRelative($date) {
  if (!$date) return '-';
  $now = new DateTime();
  $now->setTime(0, 0, 0);
  $d = new DateTime($date);
  $d->setTime(0, 0, 0);
  $diff = (int)$now->diff($d)->format('%r%a');
  if ($diff === 0) return 'Today';
  if ($diff === -1) return 'Yesterday';
  if ($diff === 1) return 'Tomorrow';
  if ($diff > 1 && $diff <= 7) return 'In ' . $diff . ' days';
  if ($diff < 0 && $diff >= -7) return abs($diff) . ' days ago';
  return fmtDate($date);
}

if (!function_exists('mb_strlen')) { function mb_strlen($s) { return strlen($s); } }
if (!function_exists('mb_substr')) { function mb_substr($s, $start, $len) { return substr($s, $start, $len); } }

function truncate($str, $len = 60) {
  if (mb_strlen($str) <= $len) return htmlspecialchars($str);
  return htmlspecialchars(mb_substr($str, 0, $len)) . '...';
}
?>
<div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Orders</h1>
        </div>
        <button onclick="openNewOrderModal()" class="btn btn-sm btn-primary">
          <i data-lucide="plus" class="w-4 h-4"></i> New Order
        </button>
      </div>
    </header>

    <div class="p-4 md:p-6 lg:p-8 max-w-5xl mx-auto">

      <!-- Filter Tabs -->
      <div class="flex flex-wrap gap-2 mb-4" id="order-tabs">
        <button class="order-tab-btn px-4 py-1.5 text-sm font-medium rounded-full transition-colors bg-brand text-white" data-tab="all">All</button>
        <button class="order-tab-btn px-4 py-1.5 text-sm font-medium rounded-full transition-colors text-gray-600 hover:bg-gray-100" data-tab="pending">Pending</button>
        <button class="order-tab-btn px-4 py-1.5 text-sm font-medium rounded-full transition-colors text-gray-600 hover:bg-gray-100" data-tab="assigned">Assigned</button>
        <button class="order-tab-btn px-4 py-1.5 text-sm font-medium rounded-full transition-colors text-gray-600 hover:bg-gray-100" data-tab="completed">Completed</button>
        <button class="order-tab-btn px-4 py-1.5 text-sm font-medium rounded-full transition-colors text-gray-600 hover:bg-gray-100" data-tab="cancelled">Cancelled</button>
      </div>

      <!-- Search -->
      <div class="relative mb-4">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
        <input type="text" id="order-search" placeholder="Search by customer name or problem..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand text-sm bg-white">
      </div>

      <!-- Orders Container -->
      <div id="orders-container" class="space-y-3">
        <?php if (empty($orders)): ?>
        <div class="empty-state">
          <i data-lucide="clipboard-list"></i>
          <p>No orders found</p>
          <p class="empty-sub">Create a new order to get started.</p>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $o):
          $problemShort = truncate($o['problem'] ?? '', 60);
          $today = date('Y-m-d');
        ?>
        <div class="order-card bg-white rounded-xl border border-gray-200 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow" data-order-id="<?= $o['id'] ?>" data-status="<?= htmlspecialchars($o['status']) ?>" data-search-text="<?= htmlspecialchars(($o['customer_name'] ?? '') . ' ' . ($o['problem'] ?? '')) ?>">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0 cursor-pointer" onclick="showOrderDetail(<?= $o['id'] ?>)">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($o['customer_name'] ?? '') ?></h3>
                <?= priorityBadge($o['priority'] ?? 'normal') ?>
              </div>
              <p class="text-sm text-gray-500 mt-1"><?= $problemShort ?></p>
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-gray-400">
                <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> <?= $o['scheduled_date'] ? fmtDate($o['scheduled_date']) : 'Not scheduled' ?></span>
                <span class="flex items-center gap-1"><i data-lucide="user" class="w-3 h-3"></i> <?= htmlspecialchars($o['assigned_staff_name'] ?? 'Unassigned') ?></span>
                <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?= fmtRelative($o['created_at']) ?></span>
              </div>
              <?php if ($o['notes']): ?>
              <p class="text-xs text-gray-400 mt-1 italic"><?= htmlspecialchars($o['notes']) ?></p>
              <?php endif; ?>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
              <?= orderStatusBadge($o['status']) ?>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-2 flex-wrap">
            <?php if ($o['status'] === 'pending'): ?>
            <button class="assign-btn px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Assign</button>
            <button class="cancel-btn px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel</button>
            <?php elseif ($o['status'] === 'assigned'): ?>
            <button class="complete-order-btn px-3 py-1.5 bg-brand text-white text-xs font-semibold rounded-lg hover:bg-brand/90 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Complete</button>
            <button class="cancel-btn px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel</button>
            <?php endif; ?>
            <button class="reassign-order-btn px-3 py-1.5 bg-purple-50 text-purple-600 text-xs font-semibold rounded-lg hover:bg-purple-100 transition-colors flex items-center gap-1.5" data-order-id="<?= $o['id'] ?>" data-current-staff="<?= $o['assigned_to'] ?? '' ?>"><i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Reassign</button>
            <button class="delete-order-btn px-3 py-1.5 bg-red-50 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1.5" data-order-id="<?= $o['id'] ?>" data-order-customer="<?= htmlspecialchars($o['customer_name'] ?? 'Unknown') ?>" data-order-problem="<?= htmlspecialchars($o['problem'] ?? '') ?>" data-order-status="<?= $o['status'] ?>"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete</button>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <div class="empty-state-hidden" style="display:none">
          <i data-lucide="clipboard-list"></i>
          <p id="empty-state-text">No orders found</p>
          <p class="empty-sub" id="empty-state-sub">Create a new order to get started.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- New Order Modal -->
  <div id="new-order-modal" class="modal-overlay" style="display:none">
    <form method="POST" id="new-order-form">
    <input type="hidden" name="action" value="create_order"><?= csrfHiddenField() ?>
    <div class="modal-content">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-bold text-gray-900">New Order</h3>
        <button type="button" onclick="closeModal('new-order-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="space-y-4">
        <!-- Customer Selection -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Customer <span class="text-danger">*</span></label>
          <div class="flex gap-2 items-start">
            <select id="order-customer" name="customer_id" class="form-select flex-1">
              <option value="">Select customer</option>
              <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" id="btn-inline-customer" class="px-3 py-2.5 border border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-500 hover:text-brand hover:border-brand transition-all whitespace-nowrap min-h-[44px] flex items-center gap-1.5">
              <i data-lucide="user-plus" class="w-4 h-4"></i> Add New
            </button>
          </div>
        </div>

        <!-- Inline Customer Creation Form (hidden by default) -->
        <div id="inline-customer-section" class="inline-customer-form hidden">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">New Customer Details</h4>
            <button type="button" id="btn-cancel-inline-customer" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Cancel</button>
          </div>
          <div class="form-row">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Name <span class="text-danger">*</span></label>
               <input type="text" id="inline-cust-name" name="inline_cust_name" class="form-input" placeholder="Customer name" maxlength="100">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Phone</label>
               <input type="text" id="inline-cust-phone" name="inline_cust_phone" class="form-input" value="+977-" placeholder="+977-98XXXXXXXX" maxlength="20">
            </div>
          </div>
          <div class="form-row" style="margin-top:12px">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Address</label>
               <input type="text" id="inline-cust-address" name="inline_cust_address" class="form-input" placeholder="e.g. Adarsh Nagar, Birgunj" maxlength="1000">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Area / Locality</label>
              <div class="location-picker">
                <select id="inline-cust-area" name="inline_cust_area" class="form-select location-select">
                  <option value="">Select locality</option>
                  <?php foreach ($localities as $l): ?>
                  <option value="<?= htmlspecialchars($l['name']) ?>"><?= htmlspecialchars($l['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" id="btn-inline-add-locality" class="location-custom-btn">
                  <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Custom
                </button>
              </div>
              <div id="inline-custom-locality-row" class="mt-2 hidden">
                <div class="flex gap-2">
                   <input type="text" id="inline-new-locality-input" name="inline_new_locality" class="form-input flex-1" placeholder="Type locality name" maxlength="100">
                  <button type="button" id="inline-save-locality-btn" class="px-4 py-2 bg-brand text-white text-sm font-semibold rounded-lg">Save</button>
                </div>
                <p id="inline-locality-error" class="text-xs text-danger mt-1 hidden"></p>
              </div>
            </div>
          </div>
          <div style="margin-top:12px">
            <label class="block text-xs font-medium text-gray-500 mb-1">Service For</label>
            <select id="inline-cust-service" name="inline_cust_service" class="form-select">
              <option value="">Select service type</option>
              <?php foreach ($service_types as $st): ?>
              <option value="<?= htmlspecialchars($st['name']) ?>"><?= htmlspecialchars($st['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label for="order-problem" class="block text-sm font-semibold text-gray-700 mb-1.5">Order Description <span class="text-danger">*</span></label>
           <textarea id="order-problem" name="problem" rows="4" class="form-textarea" placeholder="Describe the order / work to be done..." maxlength="1000"></textarea>
        </div>
        <div>
          <label for="order-staff" class="block text-sm font-semibold text-gray-700 mb-1.5">Order Assign To</label>
          <select id="order-staff" name="assigned_to" class="form-select">
            <option value="">Select Staff</option>
            <?php foreach ($staff_list as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="order-date" class="block text-sm font-semibold text-gray-700 mb-1.5">Schedule Task</label>
          <input type="date" id="order-date" name="scheduled_date" class="form-input max-w-xs">
        </div>
        <div class="flex gap-3 pt-2">
          <button type="button" onclick="closeModal('new-order-modal')" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
          <button type="submit" class="flex-1 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand/90 transition-colors brand-glow">Save Order</button>
        </div>
      </div>
    </div>
    </form>
  </div>

  <!-- Assign Staff Modal -->
  <div id="assign-modal" class="modal-overlay" style="display:none">
    <form method="POST" id="assign-form">
    <input type="hidden" name="action" value="assign_order"><?= csrfHiddenField() ?>
    <div class="modal-content">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-bold text-gray-900">Assign Staff</h3>
        <button type="button" onclick="closeModal('assign-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="space-y-4">
        <input type="hidden" id="assign-order-id" name="order_id" value="">
        <div>
          <label for="assign-staff" class="block text-sm font-semibold text-gray-700 mb-1.5">Staff Member <span class="text-danger">*</span></label>
          <select id="assign-staff" name="staff_id" class="form-select">
            <?php foreach ($staff_list as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="assign-date" class="block text-sm font-semibold text-gray-700 mb-1.5">Scheduled Date <span class="text-danger">*</span></label>
          <input type="date" id="assign-date" name="scheduled_date" class="form-input max-w-xs">
        </div>
        <div class="flex gap-3 pt-2">
          <button type="button" onclick="closeModal('assign-modal')" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
          <button type="submit" class="flex-1 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition-colors">Assign</button>
        </div>
      </div>
    </div>
    </form>
  </div>

  <!-- Complete Order Modal -->
  <div id="complete-order-modal" class="modal-overlay" style="display:none">
    <form method="POST" id="complete-order-form">
    <input type="hidden" name="action" value="complete_order"><?= csrfHiddenField() ?>
    <div class="modal-content">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-bold text-gray-900">Complete Order</h3>
        <button type="button" onclick="closeModal('complete-order-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="space-y-4">
        <input type="hidden" id="complete-order-id" name="order_id" value="">
        <input type="hidden" id="signature-data" name="signature_data" value="">

        <p class="text-sm font-bold text-navy">Order Report</p>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="dispatch-date" class="block text-xs font-medium text-gray-500 mb-1">Dispatch Date</label>
            <input type="date" id="dispatch-date" name="dispatch_date" class="form-input">
          </div>
          <div>
            <label for="dispatch-by" class="block text-xs font-medium text-gray-500 mb-1">Dispatch By</label>
             <input type="text" id="dispatch-by" name="dispatch_by" class="form-input" placeholder="Staff / person name" maxlength="100">
          </div>
        </div>

        <div class="pt-1">
          <p class="text-sm font-bold text-navy mb-2">Received By</p>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="received-name" class="block text-xs font-medium text-gray-500 mb-1">Name</label>
               <input type="text" id="received-name" name="received_name" class="form-input" placeholder="Receiver name" maxlength="100">
            </div>
            <div>
              <label for="received-contact" class="block text-xs font-medium text-gray-500 mb-1">Contact</label>
               <input type="text" id="received-contact" name="received_contact" class="form-input" placeholder="Phone" maxlength="20">
            </div>
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-medium text-gray-500">Signature</label>
            <button type="button" id="sig-clear" class="text-xs text-brand hover:underline">Clear</button>
          </div>
          <canvas id="sig-pad" class="signature-pad" width="600" height="160"></canvas>
        </div>

        <div>
          <label for="complete-notes" class="block text-sm font-semibold text-gray-700 mb-1.5">Completion Notes</label>
           <textarea id="complete-notes" name="notes" rows="2" class="form-textarea" placeholder="Notes about the completed work..." maxlength="1000"></textarea>
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" id="complete-create-task" name="create_task" class="w-4 h-4 accent-brand rounded">
          <label for="complete-create-task" class="text-sm text-gray-600">Also create a Task record for this order</label>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="button" onclick="closeModal('complete-order-modal')" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
          <button type="submit" class="flex-1 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand/90 transition-colors brand-glow">Confirm Complete</button>
        </div>
      </div>
    </div>
    </form>
  </div>

  <!-- Cancel Order Modal -->
  <div id="cancel-modal" class="modal-overlay" style="display:none">
    <form method="POST" id="cancel-form">
    <input type="hidden" name="action" value="cancel_order"><?= csrfHiddenField() ?>
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-danger"></i>
      </div>
      <input type="hidden" id="cancel-order-id" name="order_id" value="">
      <h3 class="text-lg font-bold text-navy text-center mb-2">Cancel Order?</h3>
      <p class="text-sm text-gray-500 text-center mb-6">Are you sure you want to cancel this order? This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeModal('cancel-modal')" class="btn btn-md btn-secondary flex-1">Go Back</button>
        <button type="submit" class="btn btn-md btn-danger flex-1">Yes, Cancel</button>
      </div>
    </div>
    </form>
  </div>

  <!-- Order Detail Modal -->
  <div id="order-detail-modal" class="modal-overlay" style="display:none">
    <div class="modal-content max-w-lg" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-bold text-gray-900">Order Details</h3>
        <button onclick="closeModal('order-detail-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div id="order-detail-body" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Customer</p>
            <p id="detail-customer" class="font-semibold text-navy"></p>
          </div>
          <div id="detail-status-badge"></div>
        </div>
        <hr class="border-gray-100">
        <div>
          <p class="text-sm text-gray-500">Problem / Issue</p>
          <p id="detail-problem" class="text-sm text-gray-800 mt-1"></p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Priority</p>
            <p id="detail-priority" class="font-semibold mt-1"></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Assigned To</p>
            <p id="detail-assigned" class="font-semibold mt-1 text-gray-700">Unassigned</p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Scheduled Date</p>
            <p id="detail-date" class="font-semibold mt-1 text-gray-700">Not scheduled</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Created</p>
            <p id="detail-created" class="font-semibold mt-1 text-gray-700"></p>
          </div>
        </div>
        <div id="detail-notes-row" class="hidden">
          <hr class="border-gray-100">
          <p class="text-sm text-gray-500">Notes</p>
          <p id="detail-notes" class="text-sm text-gray-700 mt-1 italic"></p>
        </div>
        <div id="detail-report-row" class="hidden">
          <hr class="border-gray-100">
          <p class="text-sm font-bold text-navy mb-2">Order Report</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Dispatch Date</p>
              <p id="detail-dispatch-date" class="font-semibold mt-1 text-gray-700">&ndash;</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Dispatch By</p>
              <p id="detail-dispatch-by" class="font-semibold mt-1 text-gray-700">&ndash;</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Received By</p>
              <p id="detail-received-name" class="font-semibold mt-1 text-gray-700">&ndash;</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Receiver Contact</p>
              <p id="detail-received-contact" class="font-semibold mt-1 text-gray-700">&ndash;</p>
            </div>
          </div>
          <div id="detail-sig-wrap" class="hidden mt-3">
            <p class="text-sm text-gray-500 mb-1">Signature</p>
            <img id="detail-signature" class="border border-gray-200 rounded-lg bg-white max-h-32" alt="Signature">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- DELETE ORDER CONFIRM MODAL -->
  <div id="delete-order-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900">Delete Order?</h3>
        <button type="button" onclick="closeOrderDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="text-sm text-gray-600 mb-1 space-y-1">
        <p><span class="font-medium">Customer:</span> <span id="del-order-customer"></span></p>
        <p><span class="font-medium">Problem:</span> <span id="del-order-problem"></span></p>
        <p><span class="font-medium">Status:</span> <span id="del-order-status"></span></p>
      </div>
      <p class="text-sm text-red-600 font-semibold mt-3 mb-5">This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeOrderDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="delete-order-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete</button>
      </div>
    </div>
  </div>

  <script>
    // ========== EMBEDDED DATA ==========
    var ordersData = <?= json_encode($orders_js) ?>;

    // ========== STATE ==========
    var currentOrderTab = 'all';
    var preselectedOrderType = null;

    // ========== INIT ==========
    document.addEventListener('DOMContentLoaded', function () {
      checkOrderFilter();
      setupOrderTabs();
      setupOrderSearch();
      setupInlineCustomerForm();
      setupInlineLocality();
      initSignaturePad();
      renderOrders();

      // Check if redirected from dashboard chooser
      try { preselectedOrderType = localStorage.getItem('fscrm_new_order_type'); } catch(e) { preselectedOrderType = null; }
      if (preselectedOrderType) {
        openNewOrderModal();
      }

      // Prefill modal dates
      var now = new Date();
      var dateStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
      document.getElementById('order-date').value = dateStr;
      document.getElementById('assign-date').value = dateStr;
      document.getElementById('dispatch-date').value = dateStr;

      // Event delegation for order buttons
      var container = document.getElementById('orders-container');
      if (container) {
        container.addEventListener('click', function(e) {
          var target = e.target.closest('button');
          if (!target) return;
          var orderId = target.dataset.orderId;
          if (target.classList.contains('assign-btn')) {
            document.getElementById('assign-order-id').value = orderId;
            openModal('assign-modal');
          } else if (target.classList.contains('complete-order-btn')) {
            openCompleteModal(orderId);
          } else if (target.classList.contains('cancel-btn')) {
            document.getElementById('cancel-order-id').value = orderId;
            openModal('cancel-modal');
          }
        });
      }

      // Handle complete order form submit with signature compression
      document.getElementById('complete-order-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        await prepareSignature();
        this.submit();
      });

      lucide.createIcons();
    });

    // ========== ORDER FILTER FROM DASHBOARD ==========
    function checkOrderFilter() {
      try {
        var stored = localStorage.getItem('fscrm_order_filter');
        if (stored) {
          if (stored === 'pending' || stored === 'assigned' || stored === 'completed' || stored === 'cancelled') {
            currentOrderTab = stored;
          }
          localStorage.removeItem('fscrm_order_filter');
          updateOrderTabUI();
        }
      } catch(e) {}
    }

    // ========== TABS ==========
    function setupOrderTabs() {
      document.querySelectorAll('.order-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          currentOrderTab = this.dataset.tab;
          updateOrderTabUI();
          renderOrders();
        });
      });
      updateOrderTabUI();
    }

    function updateOrderTabUI() {
      document.querySelectorAll('.order-tab-btn').forEach(function (btn) {
        if (btn.dataset.tab === currentOrderTab) {
          btn.className = 'order-tab-btn px-4 py-1.5 text-sm font-medium rounded-full transition-colors bg-brand text-white';
        } else {
          btn.className = 'order-tab-btn px-4 py-1.5 text-sm font-medium rounded-full transition-colors text-gray-600 hover:bg-gray-100';
        }
      });
    }

    // ========== SEARCH ==========
    function setupOrderSearch() {
      document.getElementById('order-search').addEventListener('input', function () {
        renderOrders();
      });
    }

    // ========== RENDER (show/hide cards) ==========
    function renderOrders() {
      var container = document.getElementById('orders-container');
      var cards = container.querySelectorAll('.order-card');
      var search = document.getElementById('order-search').value.toLowerCase().trim();
      var visibleCount = 0;

      cards.forEach(function(card) {
        var show = currentOrderTab === 'all' || card.dataset.status === currentOrderTab;
        if (show && search) {
          show = (card.dataset.searchText || '').toLowerCase().indexOf(search) >= 0;
        }
        card.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });

      var empty = container.querySelector('.empty-state-hidden');
      if (empty) {
        if (visibleCount === 0) {
          empty.style.display = '';
          document.getElementById('empty-state-text').textContent = 'No ' + currentOrderTab + ' orders found';
          document.getElementById('empty-state-sub').textContent = currentOrderTab === 'all' ? 'Create a new order to get started.' : 'No orders with this status.';
        } else {
          empty.style.display = 'none';
        }
      }
    }

    function escapeHtml(str) {
      if (!str) return '';
      var div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    // ========== INLINE CUSTOMER CREATION ==========
    function setupInlineCustomerForm() {
      var btnAdd = document.getElementById('btn-inline-customer');
      var section = document.getElementById('inline-customer-section');
      var btnCancel = document.getElementById('btn-cancel-inline-customer');

      btnAdd.addEventListener('click', function() {
        section.classList.remove('hidden');
        btnAdd.disabled = true;
        btnAdd.classList.add('opacity-50', 'pointer-events-none');
      });

      btnCancel.addEventListener('click', function() {
        section.classList.add('hidden');
        clearInlineCustomerForm();
        btnAdd.disabled = false;
        btnAdd.classList.remove('opacity-50', 'pointer-events-none');
      });
    }

    function clearInlineCustomerForm() {
      document.getElementById('inline-cust-name').value = '';
      document.getElementById('inline-cust-phone').value = '+977-';
      document.getElementById('inline-cust-address').value = '';
      document.getElementById('inline-cust-area').value = '';
      document.getElementById('inline-cust-service').value = '';
    }

    function setupInlineLocality() {
      var btnCustom = document.getElementById('btn-inline-add-locality');
      var row = document.getElementById('inline-custom-locality-row');
      var input = document.getElementById('inline-new-locality-input');
      var btnSave = document.getElementById('inline-save-locality-btn');
      var errEl = document.getElementById('inline-locality-error');
      var select = document.getElementById('inline-cust-area');

      btnCustom.addEventListener('click', function() {
        row.classList.toggle('hidden');
        if (!row.classList.contains('hidden')) input.focus();
      });

      btnSave.addEventListener('click', function() {
        var val = input.value.trim();
        errEl.classList.add('hidden');
        if (!val) { errEl.textContent = 'Enter a locality name.'; errEl.classList.remove('hidden'); return; }
        var csrfToken = document.querySelector('input[name="csrf_token"]').value;
        fetch('orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=add_locality&name=' + encodeURIComponent(val) + '&csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success) {
            var opt = document.createElement('option');
            opt.value = val;
            opt.textContent = val;
            select.appendChild(opt);
            select.value = val;
            row.classList.add('hidden');
            input.value = '';
          }
        });
      });

      input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); btnSave.click(); }
      });
    }

    // ========== MODALS ==========
    function openModal(id) {
      document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
    }

    document.addEventListener('click', function (e) {
      ['new-order-modal', 'assign-modal', 'complete-order-modal', 'cancel-modal', 'order-detail-modal'].forEach(function (id) {
        var el = document.getElementById(id);
        if (e.target === el) closeModal(id);
      });
    });

    // ========== NEW ORDER MODAL ==========
    function openNewOrderModal() {
      document.getElementById('order-problem').value = '';
      document.getElementById('order-staff').value = '';

      var inlineSection = document.getElementById('inline-customer-section');
      inlineSection.classList.add('hidden');
      clearInlineCustomerForm();
      var btnAdd = document.getElementById('btn-inline-customer');
      btnAdd.disabled = false;
      btnAdd.classList.remove('opacity-50', 'pointer-events-none');

      var now = new Date();
      document.getElementById('order-date').value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
      openModal('new-order-modal');
    }

    // ========== ORDER DETAIL ==========
    function showOrderDetail(orderId) {
      var order = null;
      for (var i = 0; i < ordersData.length; i++) {
        if (String(ordersData[i].id) === String(orderId)) { order = ordersData[i]; break; }
      }
      if (!order) {
        window.showToast('Order not found.', 'error');
        return;
      }

      document.getElementById('detail-customer').textContent = order.customerName;
      document.getElementById('detail-problem').textContent = order.problem || 'No description';

      document.getElementById('detail-priority').innerHTML = order.priority === 'urgent'
        ? '<span class="badge badge-priority-urgent"><i data-lucide="alert-triangle" class="w-3 h-3"></i> Urgent</span>'
        : '<span class="badge badge-priority-normal">Normal</span>';

      var statusMap = {
        pending: '<span class="badge badge-order-pending"><i data-lucide="clock" class="w-3 h-3"></i> Pending</span>',
        assigned: '<span class="badge badge-order-assigned"><i data-lucide="user-check" class="w-3 h-3"></i> Assigned</span>',
        completed: '<span class="badge badge-order-completed"><i data-lucide="check-circle" class="w-3 h-3"></i> Completed</span>',
        cancelled: '<span class="badge badge-order-cancelled"><i data-lucide="x-circle" class="w-3 h-3"></i> Cancelled</span>'
      };
      document.getElementById('detail-status-badge').innerHTML = statusMap[order.status] || '';

      document.getElementById('detail-assigned').textContent = order.assignedStaffName || 'Unassigned';
      document.getElementById('detail-date').textContent = order.scheduledDate ? formatDate(order.scheduledDate) : 'Not scheduled';
      document.getElementById('detail-created').textContent = order.createdAt ? formatRelative(order.createdAt) : '-';

      var notesRow = document.getElementById('detail-notes-row');
      var notesEl = document.getElementById('detail-notes');
      if (order.notes) {
        notesEl.textContent = order.notes;
        notesRow.classList.remove('hidden');
      } else {
        notesRow.classList.add('hidden');
      }

      var reportRow = document.getElementById('detail-report-row');
      var hasReport = order.dispatchDate || order.dispatchBy || order.receivedName || order.receivedContact || order.signature;
      if (hasReport) {
        document.getElementById('detail-dispatch-date').textContent = order.dispatchDate ? formatDate(order.dispatchDate) : '\u2013';
        document.getElementById('detail-dispatch-by').textContent = order.dispatchBy || '\u2013';
        document.getElementById('detail-received-name').textContent = order.receivedName || '\u2013';
        document.getElementById('detail-received-contact').textContent = order.receivedContact || '\u2013';
        var sigWrap = document.getElementById('detail-sig-wrap');
        if (order.signature) {
          document.getElementById('detail-signature').src = order.signature;
          sigWrap.classList.remove('hidden');
        } else {
          sigWrap.classList.add('hidden');
        }
        reportRow.classList.remove('hidden');
      } else {
        reportRow.classList.add('hidden');
      }

      try { lucide.createIcons(); } catch(e) {}
      openModal('order-detail-modal');
    }

    function formatDate(date) {
      if (!date) return '';
      var d = typeof date === 'string' ? (date.indexOf('T') >= 0 ? new Date(date) : new Date(date + 'T00:00:00')) : new Date(date);
      if (isNaN(d.getTime())) return '';
      var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    }

    function formatRelative(date) {
      if (!date) return '';
      var d = typeof date === 'string' ? (date.indexOf('T') >= 0 ? new Date(date) : new Date(date + 'T00:00:00')) : new Date(date);
      if (isNaN(d.getTime())) return '';
      var now = new Date();
      now.setHours(0,0,0,0);
      d.setHours(0,0,0,0);
      var diff = Math.round((d - now) / 86400000);
      if (diff === 0) return 'Today';
      if (diff === -1) return 'Yesterday';
      if (diff === 1) return 'Tomorrow';
      if (diff > 1 && diff <= 7) return 'In ' + diff + ' days';
      if (diff < 0 && diff >= -7) return Math.abs(diff) + ' days ago';
      return formatDate(date);
    }

    // ========== COMPLETE ==========
    function openCompleteModal(orderId) {
      var order = null;
      for (var i = 0; i < ordersData.length; i++) {
        if (String(ordersData[i].id) === String(orderId)) { order = ordersData[i]; break; }
      }
      document.getElementById('complete-order-id').value = orderId;
      document.getElementById('complete-notes').value = '';
      document.getElementById('complete-create-task').checked = false;
      var now = new Date();
      document.getElementById('dispatch-date').value =
        now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
      document.getElementById('dispatch-by').value = (order && order.assignedStaffName) || '';
      document.getElementById('received-name').value = (order && order.customerName) || '';
      document.getElementById('received-contact').value = '';
      clearSignature();
      openModal('complete-order-modal');
    }

    async function prepareSignature() {
      var dataUrl = getSignatureDataURL();
      if (dataUrl) {
        document.getElementById('signature-data').value = await window.compressSignature(dataUrl);
      } else {
        document.getElementById('signature-data').value = '';
      }
    }

    // ===== Signature pad =====
    var sigCanvas = null, sigCtx = null, sigDrawing = false, sigHasInk = false;

    function initSignaturePad() {
      sigCanvas = document.getElementById('sig-pad');
      if (!sigCanvas) return;
      sigCtx = sigCanvas.getContext('2d');
      sigCtx.lineWidth = 2;
      sigCtx.lineCap = 'round';
      sigCtx.strokeStyle = '#0B1E3D';

      function pos(e) {
        var rect = sigCanvas.getBoundingClientRect();
        var src = (e.touches && e.touches[0]) ? e.touches[0] : e;
        return {
          x: (src.clientX - rect.left) * (sigCanvas.width / rect.width),
          y: (src.clientY - rect.top) * (sigCanvas.height / rect.height)
        };
      }
      function start(e) { e.preventDefault(); sigDrawing = true; var p = pos(e); sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); }
      function move(e) { if (!sigDrawing) return; e.preventDefault(); var p = pos(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); sigHasInk = true; }
      function end() { sigDrawing = false; }

      sigCanvas.addEventListener('mousedown', start);
      sigCanvas.addEventListener('mousemove', move);
      window.addEventListener('mouseup', end);
      sigCanvas.addEventListener('touchstart', start, { passive: false });
      sigCanvas.addEventListener('touchmove', move, { passive: false });
      sigCanvas.addEventListener('touchend', end);

      document.getElementById('sig-clear').addEventListener('click', clearSignature);
    }

    function clearSignature() {
      if (!sigCtx || !sigCanvas) return;
      sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
      sigHasInk = false;
    }

    function getSignatureDataURL() {
      if (!sigHasInk || !sigCanvas) return null;
      return sigCanvas.toDataURL('image/png');
    }

    // ========== DELETE ORDER ==========
    var deleteOrderId = null;

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.delete-order-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          deleteOrderId = parseInt(this.dataset.orderId, 10);
          document.getElementById('del-order-customer').textContent = this.dataset.orderCustomer;
          document.getElementById('del-order-problem').textContent = this.dataset.orderProblem;
          document.getElementById('del-order-status').textContent = this.dataset.orderStatus;
          document.getElementById('delete-order-modal').style.display = 'flex';
        });
      });

      document.getElementById('delete-order-confirm-btn').addEventListener('click', async function () {
        if (!deleteOrderId) return;
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Deleting...';
        try {
          var res = await fetch('../api/orders.php?id=' + deleteOrderId, { method: 'DELETE' });
          var data = await res.json();
          if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); btn.disabled = false; btn.textContent = 'Delete'; return; }
          var card = document.querySelector('.delete-order-btn[data-order-id="' + deleteOrderId + '"]').closest('.order-card');
          if (card) card.remove();
          showToast('Order deleted successfully', 'success');
          closeOrderDeleteModal();
          try { lucide.createIcons(); } catch (e) {}
        } catch (e) {
          showToast('Network error', 'error');
          btn.disabled = false;
          btn.textContent = 'Delete';
        }
      });
    });

    function closeOrderDeleteModal() {
      document.getElementById('delete-order-modal').style.display = 'none';
      deleteOrderId = null;
    }

    // ========== REASSIGN ORDER ==========
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.reassign-order-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          window.reassignStaff({
            entityType: 'order',
            entityId: parseInt(this.dataset.orderId, 10),
            currentStaffId: this.dataset.currentStaff || null
          });
        });
      });
    });
  </script>

  <?php if ($totalPages > 1): ?>
  <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-100">
    <p class="text-sm text-gray-500">Page <?= $page ?> of <?= $totalPages ?> (<?= $totalRecords ?> records)</p>
    <div class="flex gap-2">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">&laquo; Previous</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php require_once '../includes/footer.php'; ?>
</body>
</html>
