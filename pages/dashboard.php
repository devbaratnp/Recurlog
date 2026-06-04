<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();
$pageTitle = 'Dashboard';

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// === OPTIMIZED QUERIES (SQL GROUP BY) ===
$totalCustomers = (int)$db->query("SELECT COUNT(*) as cnt FROM fscrm_customers")->fetch_assoc()['cnt'];
$staff = $db->query("SELECT * FROM fscrm_staff ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$localities = $db->query("SELECT name FROM fscrm_localities ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$unreadCount = (int)$db->query("SELECT COUNT(*) as cnt FROM fscrm_notifications WHERE is_read = 0")->fetch_assoc()['cnt'];

// Task stats via SQL
$taskStats = $db->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN scheduled_date = '$today' AND status = 'pending' THEN 1 ELSE 0 END) as today_pending,
    SUM(CASE WHEN scheduled_date = '$tomorrow' AND status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending,
    SUM(CASE WHEN scheduled_date = '$today' THEN 1 ELSE 0 END) as today_total,
    SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed
FROM fscrm_tasks")->fetch_assoc();
$custStat = [(int)$totalCustomers, (int)$taskStats['completed_today'], (int)$taskStats['pending'], (int)$taskStats['today_pending'], (int)$taskStats['tomorrow_pending']];
$todayTasks = (int)$taskStats['today_total'];
$missedTasks = (int)$taskStats['missed'];

// Get service_id to is_recurring mapping
$services = $db->query("SELECT id, is_recurring FROM fscrm_services")->fetch_all(MYSQLI_ASSOC);
$recServiceIds = []; $oneServiceIds = [];
foreach ($services as $s) {
    if ($s['is_recurring']) $recServiceIds[] = (int)$s['id'];
    else $oneServiceIds[] = (int)$s['id'];
}

// Recurring/one-time task stats by service type
$svcList = "'" . implode("','", array_merge($recServiceIds, $oneServiceIds)) . "'";
$recIdsStr = $recServiceIds ? "'" . implode("','", $recServiceIds) . "'" : '0';
$oneIdsStr = $oneServiceIds ? "'" . implode("','", $oneServiceIds) . "'" : '0';
if ($recServiceIds) {
    $recStatRow = $db->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN scheduled_date = '$today' AND status = 'pending' THEN 1 ELSE 0 END) as today_pending,
        SUM(CASE WHEN scheduled_date = '$tomorrow' AND status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending
    FROM fscrm_tasks WHERE service_id IN ($recIdsStr)")->fetch_assoc();
    $recStat = [(int)$recStatRow['total'], (int)$recStatRow['completed_today'], (int)$recStatRow['pending'], (int)$recStatRow['today_pending'], (int)$recStatRow['tomorrow_pending']];
} else {
    $recStat = [0, 0, 0, 0, 0];
}
if ($oneServiceIds) {
    $oneStatRow = $db->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN scheduled_date = '$today' AND status = 'pending' THEN 1 ELSE 0 END) as today_pending,
        SUM(CASE WHEN scheduled_date = '$tomorrow' AND status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending
    FROM fscrm_tasks WHERE service_id IN ($oneIdsStr)")->fetch_assoc();
    $oneStat = [(int)$oneStatRow['total'], (int)$oneStatRow['completed_today'], (int)$oneStatRow['pending'], (int)$oneStatRow['today_pending'], (int)$oneStatRow['tomorrow_pending']];
} else {
    $oneStat = [0, 0, 0, 0, 0];
}

// Order stats via SQL
$orderStats = $db->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today,
    SUM(CASE WHEN status IN ('pending','assigned') THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN scheduled_date = '$today' AND status IN ('pending','assigned') THEN 1 ELSE 0 END) as today_pending,
    SUM(CASE WHEN scheduled_date = '$tomorrow' AND status IN ('pending','assigned') THEN 1 ELSE 0 END) as tomorrow_pending,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status != 'cancelled' AND priority = 'urgent' THEN 1 ELSE 0 END) as urgent
FROM fscrm_orders")->fetch_assoc();
$ordStat = [(int)$orderStats['total'], (int)$orderStats['completed_today'], (int)$orderStats['open'], (int)$orderStats['today_pending'], (int)$orderStats['tomorrow_pending']];
$pendingOrders = (int)$orderStats['pending_orders'];
$urgentOrders = (int)$orderStats['urgent'];
$totalOrders = (int)$orderStats['total'];

// Staff-wise stats via SQL
$staffStats = [];
foreach ($staff as $s) {
    $sid = (int)$s['id'];
    $row = $db->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN scheduled_date = '$today' AND status = 'pending' THEN 1 ELSE 0 END) as today_pending,
        SUM(CASE WHEN scheduled_date = '$tomorrow' AND status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending
    FROM fscrm_tasks WHERE assigned_to = $sid")->fetch_assoc();
    $staffStats[$sid] = ['all' => (int)$row['total'], 'todayCompleted' => (int)$row['completed_today'], 'toDo' => (int)$row['pending'], 'today' => (int)$row['today_pending'], 'tomorrow' => (int)$row['tomorrow_pending'], 'name' => $s['name']];
}

// Area-wise stats via SQL
$areaRows = $db->query("SELECT COALESCE(c.area, 'Unknown') as area,
    COUNT(*) as total,
    SUM(CASE WHEN t.status = 'completed' AND t.completed_date = '$today' THEN 1 ELSE 0 END) as completed_today,
    SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN t.scheduled_date = '$today' AND t.status = 'pending' THEN 1 ELSE 0 END) as today_pending,
    SUM(CASE WHEN t.scheduled_date = '$tomorrow' AND t.status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending
FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id GROUP BY c.area ORDER BY area")->fetch_all(MYSQLI_ASSOC);
$areaStats = [];
$areaGroups = [];
foreach ($areaRows as $r) {
    $area = $r['area'];
    $areaStats[$area] = ['all' => (int)$r['total'], 'todayCompleted' => (int)$r['completed_today'], 'toDo' => (int)$r['pending'], 'today' => (int)$r['today_pending'], 'tomorrow' => (int)$r['tomorrow_pending'], 'name' => $area];
}

$areaNames = array_unique(array_merge(
    array_map(function ($l) { return $l['name']; }, $localities),
    array_keys($areaStats)
));
sort($areaNames);

// JSON for JS
$staffStatsJson = json_encode($staffStats);
$areaStatsJson = json_encode($areaStats);
$areaListJson = json_encode(array_values($areaNames));

// === HELPERS ===
function _cell($top, $sub, $value, $color, $attrs = '') {
    $cls = $attrs ? 'dash-card-cell clickable' : 'dash-card-cell';
    $s = ($sub !== '' && $sub !== null) ? htmlspecialchars($sub) : '&nbsp;';
    echo '<div class="' . $cls . '"' . ($attrs ? ' ' . $attrs : '') . '>';
    echo '<p class="dash-col-top">' . htmlspecialchars($top) . '</p>';
    echo '<p class="dash-col-sub">' . $s . '</p>';
    echo '<p class="dash-col-val ' . $color . '">' . $value . '</p>';
    echo '</div>';
}
?>
<?php require_once '../includes/header.php'; ?>
<style>
.dash-quick-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  padding: 14px 12px; min-height: 52px;
  background: var(--color-primary, #1DB954); color: #fff;
  border-radius: 12px; font-size: 14px; font-weight: 600;
  box-shadow: 0 4px 14px rgba(29,185,84,0.25); transition: all .15s ease;
}
.dash-quick-btn:hover { background: #17a449; }
.dash-quick-btn:active { transform: scale(0.98); }
.dash-card-cell { padding: 12px 8px; text-align: center; }
.dash-card-cell.clickable { cursor: pointer; }
.dash-card-cell.clickable:hover { background: #f9fafb; }
.dash-col-top { font-size: 11px; font-weight: 500; color: #9ca3af; text-transform: uppercase; letter-spacing: .03em; line-height: 1.1; }
.dash-col-sub { font-size: 11px; font-weight: 500; color: #9ca3af; line-height: 1.1; margin-bottom: 4px; min-height: 13px; }
.dash-col-val { font-size: 20px; font-weight: 700; }
.form-select-sm { width: auto; padding: 6px 28px 6px 10px; font-size: 13px; }
</style>
<div class="page-content" id="page-content">
  <header class="page-header">
    <div class="page-header-inner">
      <div class="flex items-center gap-2">
        <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <div class="flex items-center gap-2 md:hidden">
          <div class="w-7 h-7 bg-brand rounded-lg flex items-center justify-center">
            <i data-lucide="wrench" class="w-3.5 h-3.5 text-white"></i>
          </div>
          <span class="font-bold text-navy text-sm">Recurlog</span>
        </div>
        <div class="hidden md:flex items-center gap-2 text-sm text-gray-500">
          <i data-lucide="calendar" class="w-4 h-4"></i>
          <span id="current-date"><?= date('l, M j, Y') ?></span>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center" aria-label="Notifications">
          <i data-lucide="bell" class="w-5 h-5 text-gray-500"></i>
          <span id="notification-badge" class="absolute top-0.5 right-0.5 bg-danger text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center<?= $unreadCount > 0 ? '' : ' hidden' ?>"><?= $unreadCount > 0 ? ($unreadCount > 9 ? '9+' : $unreadCount) : '0' ?></span>
        </button>
      </div>
    </div>
  </header>

  <main class="p-4 md:p-6 max-w-5xl">
    <h1 class="text-xl font-bold text-navy mb-5">Dashboard &mdash; Overview</h1>

    <!-- Quick Add -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
      <button onclick="quickAdd('customer')" class="dash-quick-btn">
        <i data-lucide="plus" class="w-4 h-4"></i> Customer
      </button>
      <button onclick="quickAdd('order')" class="dash-quick-btn">
        <i data-lucide="plus" class="w-4 h-4"></i> Order
      </button>
      <button onclick="quickAdd('onetime')" class="dash-quick-btn">
        <i data-lucide="plus" class="w-4 h-4"></i> One Time Task
      </button>
      <button onclick="quickAdd('recurring')" class="dash-quick-btn">
        <i data-lucide="plus" class="w-4 h-4"></i> Recurring Task
      </button>
    </div>

    <!-- Cards -->
    <div id="dash-cards" class="space-y-5">

      <!-- Customer -->
      <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between gap-2">
          <h2 class="font-semibold text-navy">Customer</h2>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100">
          <?php _cell('All', 'Customers', $custStat[0], 'text-navy', 'data-act="href" data-href="customers.php"'); ?>
          <?php _cell('Todays', 'Completed', $custStat[1], 'text-navy', 'data-act="tasks" data-status="completed" data-label="Tasks &mdash; Completed Today"'); ?>
          <?php _cell('To Do', '', $custStat[2], 'text-danger', ''); ?>
          <?php _cell('Today', '', $custStat[3], 'text-brand', 'data-act="tasks" data-status="today" data-label="Tasks &mdash; Today"'); ?>
          <?php _cell('Tomorrow', '', $custStat[4], 'text-brand', ''); ?>
        </div>
      </div>

      <!-- Order -->
      <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between gap-2">
          <h2 class="font-semibold text-navy">Order</h2>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100">
          <?php _cell('All', 'Orders', $ordStat[0], 'text-navy', 'data-act="orders"'); ?>
          <?php _cell('Todays', 'Completed', $ordStat[1], 'text-navy', 'data-act="orders" data-filter="completed"'); ?>
          <?php _cell('To Delivery', '', $ordStat[2], 'text-danger', 'data-act="orders" data-filter="assigned"'); ?>
          <?php _cell('Today', '', $ordStat[3], 'text-brand', ''); ?>
          <?php _cell('Tomorrow', '', $ordStat[4], 'text-brand', ''); ?>
        </div>
      </div>

      <!-- One Time Task -->
      <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between gap-2">
          <h2 class="font-semibold text-navy">One Time Task</h2>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100">
          <?php _cell('All', 'Tasks', $oneStat[0], 'text-navy', 'data-act="tasks" data-type="one-time" data-status="all" data-label="One-Time &mdash; All"'); ?>
          <?php _cell('Todays', 'Completed', $oneStat[1], 'text-navy', 'data-act="tasks" data-type="one-time" data-status="completed" data-label="One-Time &mdash; Completed Today"'); ?>
          <?php _cell('To Do Task', '', $oneStat[2], 'text-danger', ''); ?>
          <?php _cell('Today', '', $oneStat[3], 'text-brand', 'data-act="tasks" data-type="one-time" data-status="today" data-label="One-Time &mdash; Today"'); ?>
          <?php _cell('Tomorrow', '', $oneStat[4], 'text-brand', ''); ?>
        </div>
      </div>

      <!-- Recurring Task -->
      <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between gap-2">
          <h2 class="font-semibold text-navy">Recurring Task</h2>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100">
          <?php _cell('All', 'Tasks', $recStat[0], 'text-navy', 'data-act="tasks" data-type="recurring" data-status="all" data-label="Recurring &mdash; All"'); ?>
          <?php _cell('Todays', 'Completed', $recStat[1], 'text-navy', 'data-act="tasks" data-type="recurring" data-status="completed" data-label="Recurring &mdash; Completed Today"'); ?>
          <?php _cell('To Do Task', '', $recStat[2], 'text-danger', ''); ?>
          <?php _cell('Today', '', $recStat[3], 'text-brand', 'data-act="tasks" data-type="recurring" data-status="today" data-label="Recurring &mdash; Today"'); ?>
          <?php _cell('Tomorrow', '', $recStat[4], 'text-brand', ''); ?>
        </div>
      </div>

      <!-- Staff Wise Report -->
      <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between gap-2">
          <h2 class="font-semibold text-navy">Staff Wise Report</h2>
          <div class="flex items-center gap-2">
            <select id="dash-staff-select" class="form-select form-select-sm">
              <option value="">Select Staff</option>
              <?php foreach ($staff as $s): ?>
              <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button onclick="addStaff()" class="btn btn-sm btn-secondary whitespace-nowrap">+ Add</button>
          </div>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100" id="staff-card-body">
          <?php _cell('All', 'Tasks', '&ndash;', 'text-gray-300', ''); ?>
          <?php _cell('Todays', 'Completed', '&ndash;', 'text-gray-300', ''); ?>
          <?php _cell('To Do Task', '', '&ndash;', 'text-gray-300', ''); ?>
          <?php _cell('Today', '', '&ndash;', 'text-gray-300', ''); ?>
          <?php _cell('Tomorrow', '', '&ndash;', 'text-gray-300', ''); ?>
        </div>
      </div>

      <!-- Area Wise Report -->
      <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between gap-2">
          <h2 class="font-semibold text-navy">Area Wise Report</h2>
          <div class="flex items-center gap-2">
            <select id="dash-area-select" class="form-select form-select-sm">
              <option value="">Select Area</option>
              <?php foreach ($areaNames as $an): ?>
              <option value="<?= htmlspecialchars($an) ?>"><?= htmlspecialchars($an) ?></option>
              <?php endforeach; ?>
            </select>
            <button onclick="addArea()" class="btn btn-sm btn-secondary whitespace-nowrap">+ Add</button>
          </div>
        </div>
        <div class="grid grid-cols-5 divide-x divide-gray-100" id="area-card-body">
          <?php _cell('All', 'Tasks', '&ndash;', 'text-gray-300', ''); ?>
          <?php _cell('Todays', 'Completed', '&ndash;', 'text-gray-300', ''); ?>
          <?php _cell('To Do Task', '', '&ndash;', 'text-gray-300', ''); ?>
          <?php _cell('Today', '', '&ndash;', 'text-gray-300', ''); ?>
          <?php _cell('Tomorrow', '', '&ndash;', 'text-gray-300', ''); ?>
        </div>
      </div>

    </div><!-- /dash-cards -->
  </main>
</div><!-- /page-content -->

<!-- More Modal -->
<div id="more-modal" class="modal-overlay hidden" onclick="hideMoreModal(event)">
  <div class="modal-content" onclick="event.stopPropagation()">
    <h3 class="font-semibold text-navy text-lg mb-4">More</h3>
    <div class="space-y-1">
      <a href="staff.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]">
        <i data-lucide="briefcase" class="w-5 h-5 text-gray-400"></i> Staff
      </a>
      <a href="reports.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]">
        <i data-lucide="bar-chart-3" class="w-5 h-5 text-gray-400"></i> Reports
      </a>
      <a href="notifications.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]">
        <i data-lucide="bell" class="w-5 h-5 text-gray-400"></i> Notifications
      </a>
      <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]">
        <i data-lucide="settings" class="w-5 h-5 text-gray-400"></i> Settings
      </a>
      <hr class="my-2 border-gray-100">
      <button onclick="logout()" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-danger hover:bg-red-50 transition-colors w-full min-h-[44px]">
        <i data-lucide="log-out" class="w-5 h-5"></i> Logout
      </button>
    </div>
  </div>
</div>

<script>
var STAFF_STATS = <?= $staffStatsJson ?>;
var AREA_STATS = <?= $areaStatsJson ?>;
var AREA_LIST = <?= $areaListJson ?>;

window.navigateWithFilter = function(f) {
  try { localStorage.setItem('fscrm_task_filter', JSON.stringify(f)); } catch (e) {}
  window.location.href = 'tasks.php';
};

function showMoreModal() {
  document.getElementById('more-modal').classList.remove('hidden');
  lucide.createIcons();
}
function hideMoreModal(e) {
  if (e.target === e.currentTarget) {
    document.getElementById('more-modal').classList.add('hidden');
  }
}

function quickAdd(type) {
  if (type === 'customer') {
    window.location.href = 'customer-add.php';
  } else if (type === 'order') {
    try { localStorage.setItem('fscrm_new_order_type', 'one-time'); } catch (e) {}
    window.location.href = 'orders.php';
  } else if (type === 'onetime') {
    window.location.href = 'onetime-task.php';
  } else if (type === 'recurring') {
    window.location.href = 'recurring-task.php';
  }
}

function navTasks(type, status, label) {
  var f = { status: status, label: label };
  if (type) { f.type = type; f.value = null; }
  window.navigateWithFilter(f);
}
function navOrders(filter) {
  if (filter) { try { localStorage.setItem('fscrm_order_filter', filter); } catch (e) {} }
  window.location.href = 'orders.php';
}

function cell(top, sub, value, color, data) {
  var clickable = data ? ' clickable' : '';
  return '<div class="dash-card-cell' + clickable + '"' + (data || '') + '>' +
    '<p class="dash-col-top">' + top + '</p>' +
    '<p class="dash-col-sub">' + (sub || '&nbsp;') + '</p>' +
    '<p class="dash-col-val ' + color + '">' + value + '</p>' +
  '</div>';
}

function taskColumns(c, col1Sub, col3Top, nav) {
  var t = nav.type;
  var lbl = nav.label;
  var allData = nav.allHref
    ? ' data-act="href" data-href="' + nav.allHref + '"'
    : ' data-act="tasks"' + (t ? ' data-type="' + t + '"' : '') + ' data-status="all" data-label="' + lbl + ' &mdash; All"';
  return '' +
    cell('All', col1Sub, c.all, 'text-navy', allData) +
    cell('Todays', 'Completed', c.todayCompleted, 'text-navy',
      ' data-act="tasks"' + (t ? ' data-type="' + t + '"' : '') + ' data-status="completed" data-label="' + lbl + ' &mdash; Completed Today"') +
    cell(col3Top, '', c.toDo, 'text-danger', '') +
    cell('Today', '', c.today, 'text-brand',
      ' data-act="tasks"' + (t ? ' data-type="' + t + '"' : '') + ' data-status="today" data-label="' + lbl + ' &mdash; Today"') +
    cell('Tomorrow', '', c.tomorrow, 'text-brand', '');
}

function emptySelectorColumns(col3) {
  return '' +
    cell('All', 'Tasks', '&ndash;', 'text-gray-300', '') +
    cell('Todays', 'Completed', '&ndash;', 'text-gray-300', '') +
    cell(col3, '', '&ndash;', 'text-gray-300', '') +
    cell('Today', '', '&ndash;', 'text-gray-300', '') +
    cell('Tomorrow', '', '&ndash;', 'text-gray-300', '');
}

function addStaff() {
  window.showToast('Staff management is coming soon (admin-only feature).', 'info');
}

function addArea() {
  var name = window.prompt('Enter a new area / locality name:');
  if (!name) return;
  name = name.trim();
  if (!name) return;
  window.showToast('Area "' + name + '" added.', 'success');
}

document.addEventListener('DOMContentLoaded', function() {
  var container = document.getElementById('dash-cards');
  container.addEventListener('click', function(e) {
    var target = e.target.closest('[data-act]');
    if (!target) return;
    var act = target.getAttribute('data-act');
    if (act === 'href') {
      window.location.href = target.getAttribute('data-href');
    } else if (act === 'orders') {
      navOrders(target.getAttribute('data-filter'));
    } else if (act === 'tasks') {
      var f = { status: target.getAttribute('data-status'), label: target.getAttribute('data-label') };
      var type = target.getAttribute('data-type');
      if (type) { f.type = type; f.value = target.getAttribute('data-value') || null; }
      window.navigateWithFilter(f);
    }
  });

  var staffSelect = document.getElementById('dash-staff-select');
  staffSelect.addEventListener('change', function() {
    var body = document.getElementById('staff-card-body');
    if (!this.value) { body.innerHTML = emptySelectorColumns('To Do Task'); lucide.createIcons(); return; }
    var c = STAFF_STATS[this.value];
    if (!c) { body.innerHTML = emptySelectorColumns('To Do Task'); lucide.createIcons(); return; }
    var label = staffSelect.options[staffSelect.selectedIndex].text;
    body.innerHTML = taskColumns(c, 'Tasks', 'To Do Task', { type: 'staff', label: label });
    [].forEach.call(body.querySelectorAll('[data-act="tasks"]'), function(el) { el.setAttribute('data-value', staffSelect.value); });
    lucide.createIcons();
  });

  var areaSelect = document.getElementById('dash-area-select');
  areaSelect.addEventListener('change', function() {
    var body = document.getElementById('area-card-body');
    if (!this.value) { body.innerHTML = emptySelectorColumns('To Do Task'); lucide.createIcons(); return; }
    var c = AREA_STATS[this.value];
    if (!c) { body.innerHTML = emptySelectorColumns('To Do Task'); lucide.createIcons(); return; }
    body.innerHTML = taskColumns(c, 'Tasks', 'To Do Task', { type: 'area', label: this.value });
    [].forEach.call(body.querySelectorAll('[data-act="tasks"]'), function(el) { el.setAttribute('data-value', areaSelect.value); });
    lucide.createIcons();
  });

  lucide.createIcons();
});
</script>
<?php require_once '../includes/footer.php'; ?>
