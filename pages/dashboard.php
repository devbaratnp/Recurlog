<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();
$pageTitle = 'Dashboard';

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$totalCustomers = (int)$db->query("SELECT COUNT(*) as cnt FROM fscrm_customers")->fetch_assoc()['cnt'];
$staff = $db->query("SELECT * FROM fscrm_staff ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$localities = $db->query("SELECT name FROM fscrm_localities ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$unreadCount = (int)$db->query("SELECT COUNT(*) as cnt FROM fscrm_notifications WHERE is_read = 0")->fetch_assoc()['cnt'];

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

$services = $db->query("SELECT id, is_recurring FROM fscrm_services")->fetch_all(MYSQLI_ASSOC);
$recServiceIds = []; $oneServiceIds = [];
foreach ($services as $s) {
    if ($s['is_recurring']) $recServiceIds[] = (int)$s['id'];
    else $oneServiceIds[] = (int)$s['id'];
}

$recIdsStr = $recServiceIds ? "'" . implode("','", $recServiceIds) . "'" : '0';
$oneIdsStr = $oneServiceIds ? "'" . implode("','", $oneServiceIds) . "'" : '0';

if ($recServiceIds) {
    $recStatRow = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN scheduled_date = '$today' AND status = 'pending' THEN 1 ELSE 0 END) as today_pending, SUM(CASE WHEN scheduled_date = '$tomorrow' AND status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending FROM fscrm_tasks WHERE service_id IN ($recIdsStr)")->fetch_assoc();
    $recStat = [(int)$recStatRow['total'], (int)$recStatRow['completed_today'], (int)$recStatRow['pending'], (int)$recStatRow['today_pending'], (int)$recStatRow['tomorrow_pending']];
} else { $recStat = [0,0,0,0,0]; }

if ($oneServiceIds) {
    $oneStatRow = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN scheduled_date = '$today' AND status = 'pending' THEN 1 ELSE 0 END) as today_pending, SUM(CASE WHEN scheduled_date = '$tomorrow' AND status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending FROM fscrm_tasks WHERE service_id IN ($oneIdsStr)")->fetch_assoc();
    $oneStat = [(int)$oneStatRow['total'], (int)$oneStatRow['completed_today'], (int)$oneStatRow['pending'], (int)$oneStatRow['today_pending'], (int)$oneStatRow['tomorrow_pending']];
} else { $oneStat = [0,0,0,0,0]; }

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

$staffStats = [];
foreach ($staff as $s) {
    $sid = (int)$s['id'];
    $row = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN scheduled_date = '$today' AND status = 'pending' THEN 1 ELSE 0 END) as today_pending, SUM(CASE WHEN scheduled_date = '$tomorrow' AND status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending FROM fscrm_tasks WHERE assigned_to = $sid")->fetch_assoc();
    $staffStats[$sid] = ['all' => (int)$row['total'], 'todayCompleted' => (int)$row['completed_today'], 'toDo' => (int)$row['pending'], 'today' => (int)$row['today_pending'], 'tomorrow' => (int)$row['tomorrow_pending'], 'name' => $s['name']];
}

$areaRows = $db->query("SELECT COALESCE(c.area, 'Unknown') as area, COUNT(*) as total, SUM(CASE WHEN t.status = 'completed' AND t.completed_date = '$today' THEN 1 ELSE 0 END) as completed_today, SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN t.scheduled_date = '$today' AND t.status = 'pending' THEN 1 ELSE 0 END) as today_pending, SUM(CASE WHEN t.scheduled_date = '$tomorrow' AND t.status = 'pending' THEN 1 ELSE 0 END) as tomorrow_pending FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id GROUP BY c.area ORDER BY area")->fetch_all(MYSQLI_ASSOC);
$areaStats = [];
foreach ($areaRows as $r) {
    $area = $r['area'];
    $areaStats[$area] = ['all' => (int)$r['total'], 'todayCompleted' => (int)$r['completed_today'], 'toDo' => (int)$r['pending'], 'today' => (int)$r['today_pending'], 'tomorrow' => (int)$r['tomorrow_pending'], 'name' => $area];
}
$areaNames = array_unique(array_merge(
    array_map(function ($l) { return $l['name']; }, $localities),
    array_keys($areaStats)
));
sort($areaNames);

$staffStatsJson = json_encode($staffStats);
$areaStatsJson = json_encode($areaStats);
$areaListJson = json_encode(array_values($areaNames));
?>
<?php require_once '../includes/header.php'; ?>
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
        <button class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center" aria-label="Notifications" onclick="window.location.href='notifications.php'">
          <i data-lucide="bell" class="w-5 h-5 text-gray-500"></i>
          <span id="notification-badge" class="absolute top-0.5 right-0.5 bg-danger text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center<?= $unreadCount > 0 ? '' : ' hidden' ?>"><?= $unreadCount > 0 ? ($unreadCount > 9 ? '9+' : $unreadCount) : '0' ?></span>
        </button>
      </div>
    </div>
  </header>

  <main class="p-4 md:p-6 max-w-5xl" style="padding-bottom: 80px">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h1 class="text-xl font-bold text-navy">Dashboard</h1>
        <p class="text-sm text-gray-500"><?= date('l, M j, Y') ?></p>
      </div>
    </div>

    <!-- Quick Actions (mobile-inspired row) -->
    <div class="flex gap-2 mb-5 overflow-x-auto pb-1" style="-webkit-overflow-scrolling:touch">
      <button onclick="quickAdd('customer')" class="quick-action-btn flex-shrink-0">
        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Customer
      </button>
      <button onclick="quickAdd('order')" class="quick-action-btn flex-shrink-0" style="background:#2563EB">
        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i> Order
      </button>
      <button onclick="quickAdd('onetime')" class="quick-action-btn flex-shrink-0" style="background:#D97706">
        <i data-lucide="clock" class="w-3.5 h-3.5"></i> One Time
      </button>
      <button onclick="quickAdd('recurring')" class="quick-action-btn flex-shrink-0" style="background:#059669">
        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Recurring
      </button>
    </div>

    <div class="space-y-3">

      <!-- Customer -->
      <div class="section-card">
        <div class="section-card-header">
          <div class="section-card-icon" style="background:rgba(34,197,94,0.08)">
            <i data-lucide="users" style="width:13px;height:13px;color:var(--primary)"></i>
          </div>
          <span class="section-card-title">Customer</span>
          <span class="section-card-badge">
            <span class="w-1.5 h-1.5 rounded-full" style="background:var(--primary)"></span>
            <?= $custStat[2] ?> due
          </span>
        </div>
        <div class="section-card-body">
          <div class="stat-grid">
            <div class="stat-box compact" onclick="window.location.href='customers.php'">
              <div class="stat-box-label">All</div>
              <div class="stat-box-value"><?= $custStat[0] ?></div>
            </div>
            <div class="stat-box compact" onclick="navTasks(null, 'completed', 'Completed Today')">
              <div class="stat-box-label">Today</div>
              <div class="stat-box-value" style="color:var(--primary)"><?= $custStat[1] ?></div>
            </div>
            <div class="stat-box compact" style="background:#EFF6FF;border-color:#BFDBFE">
              <div class="stat-box-label" style="color:var(--info)">Today</div>
              <div class="stat-box-value" style="color:var(--info)"><?= $custStat[3] ?></div>
            </div>
            <div class="stat-box compact" onclick="navTasks(null, 'pending', 'Pending')">
              <div class="stat-box-label">Pending</div>
              <div class="stat-box-value" style="color:var(--amber)"><?= $custStat[2] ?></div>
            </div>
            <div class="stat-box compact">
              <div class="stat-box-label">Missed</div>
              <div class="stat-box-value" style="color:var(--danger)"><?= $missedTasks ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Order -->
      <div class="section-card">
        <div class="section-card-header">
          <div class="section-card-icon" style="background:rgba(59,130,246,0.08)">
            <i data-lucide="shopping-cart" style="width:13px;height:13px;color:var(--info)"></i>
          </div>
          <span class="section-card-title">Order</span>
          <span class="section-card-badge">
            <span class="w-1.5 h-1.5 rounded-full" style="background:var(--amber)"></span>
            <?= $ordStat[2] ?> open
          </span>
        </div>
        <div class="section-card-body">
          <div class="stat-grid">
            <div class="stat-box compact" onclick="window.location.href='orders.php'">
              <div class="stat-box-label">All</div>
              <div class="stat-box-value"><?= $ordStat[0] ?></div>
            </div>
            <div class="stat-box compact">
              <div class="stat-box-label">Done</div>
              <div class="stat-box-value" style="color:var(--primary)"><?= $ordStat[1] ?></div>
            </div>
            <div class="stat-box compact" style="background:#EFF6FF;border-color:#BFDBFE">
              <div class="stat-box-label" style="color:var(--info)">Today</div>
              <div class="stat-box-value" style="color:var(--info)"><?= $ordStat[3] ?></div>
            </div>
            <div class="stat-box compact">
              <div class="stat-box-label">Open</div>
              <div class="stat-box-value" style="color:var(--amber)"><?= $ordStat[2] ?></div>
            </div>
            <div class="stat-box compact">
              <div class="stat-box-label">Urgent</div>
              <div class="stat-box-value" style="color:var(--danger)"><?= $urgentOrders ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- One Time Task -->
      <div class="section-card">
        <div class="section-card-header">
          <div class="section-card-icon" style="background:rgba(245,158,11,0.08)">
            <i data-lucide="clock" style="width:13px;height:13px;color:var(--amber)"></i>
          </div>
          <span class="section-card-title">One Time Task</span>
          <span class="section-card-badge">
            <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $oneStat[2] > 0 ? 'var(--danger)' : 'var(--primary)' ?>"></span>
            <?= $oneStat[2] ?> pending
          </span>
        </div>
        <div class="section-card-body">
          <div class="stat-grid">
            <div class="stat-box compact" onclick="navTasks('onetime', 'all', 'One-Time - All')">
              <div class="stat-box-label">All</div>
              <div class="stat-box-value"><?= $oneStat[0] ?></div>
            </div>
            <div class="stat-box compact" onclick="navTasks('onetime', 'completed', 'One-Time - Completed Today')">
              <div class="stat-box-label">Done</div>
              <div class="stat-box-value" style="color:var(--primary)"><?= $oneStat[1] ?></div>
            </div>
            <div class="stat-box compact" style="background:#EFF6FF;border-color:#BFDBFE">
              <div class="stat-box-label" style="color:var(--info)">Today</div>
              <div class="stat-box-value" style="color:var(--info)"><?= $oneStat[3] ?></div>
            </div>
            <div class="stat-box compact" onclick="navTasks('onetime', 'pending', 'One-Time - Pending')">
              <div class="stat-box-label">Pending</div>
              <div class="stat-box-value" style="color:var(--amber)"><?= $oneStat[2] ?></div>
            </div>
            <div class="stat-box compact">
              <div class="stat-box-label">Tom</div>
              <div class="stat-box-value"><?= $oneStat[4] ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recurring Task -->
      <div class="section-card">
        <div class="section-card-header">
          <div class="section-card-icon" style="background:rgba(34,197,94,0.08)">
            <i data-lucide="refresh-cw" style="width:13px;height:13px;color:var(--primary)"></i>
          </div>
          <span class="section-card-title">Recurring Task</span>
          <span class="section-card-badge">
            <span class="w-1.5 h-1.5 rounded-full" style="background:<?= $recStat[2] > 0 ? 'var(--danger)' : 'var(--primary)' ?>"></span>
            <?= $recStat[2] ?> pending
          </span>
        </div>
        <div class="section-card-body">
          <div class="stat-grid">
            <div class="stat-box compact" onclick="navTasks('recurring', 'all', 'Recurring - All')">
              <div class="stat-box-label">All</div>
              <div class="stat-box-value"><?= $recStat[0] ?></div>
            </div>
            <div class="stat-box compact" onclick="navTasks('recurring', 'completed', 'Recurring - Completed Today')">
              <div class="stat-box-label">Done</div>
              <div class="stat-box-value" style="color:var(--primary)"><?= $recStat[1] ?></div>
            </div>
            <div class="stat-box compact" style="background:#EFF6FF;border-color:#BFDBFE">
              <div class="stat-box-label" style="color:var(--info)">Today</div>
              <div class="stat-box-value" style="color:var(--info)"><?= $recStat[3] ?></div>
            </div>
            <div class="stat-box compact" onclick="navTasks('recurring', 'pending', 'Recurring - Pending')">
              <div class="stat-box-label">Pending</div>
              <div class="stat-box-value" style="color:var(--amber)"><?= $recStat[2] ?></div>
            </div>
            <div class="stat-box compact">
              <div class="stat-box-label">Tom</div>
              <div class="stat-box-value"><?= $recStat[4] ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Staff Wise Report -->
      <div class="section-card">
        <div class="section-card-header">
          <div class="section-card-icon" style="background:rgba(139,92,246,0.08)">
            <i data-lucide="users" style="width:13px;height:13px;color:#8B5CF6"></i>
          </div>
          <span class="section-card-title">Staff</span>
          <div class="flex items-center gap-1 ml-auto">
            <select id="dash-staff-select" class="text-xs border border-gray-200 rounded-full px-2.5 py-1 bg-white font-medium text-gray-600 outline-none focus:border-brand" style="font-family:inherit;min-height:28px;appearance:none;padding-right:20px;background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394A3B8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Cpath d=%22m6 9 6 6 6-6%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 6px center;background-size:12px">
              <option value="">Select Staff</option>
              <?php foreach ($staff as $s): ?>
              <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="section-card-body">
          <div class="stat-grid" id="staff-card-body">
            <div class="stat-box compact"><div class="stat-box-label">All</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
            <div class="stat-box compact"><div class="stat-box-label">Done</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
            <div class="stat-box compact"><div class="stat-box-label">Today</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
            <div class="stat-box compact"><div class="stat-box-label">Pending</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
            <div class="stat-box compact"><div class="stat-box-label">Tom</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
          </div>
        </div>
      </div>

      <!-- Area Wise Report -->
      <div class="section-card">
        <div class="section-card-header">
          <div class="section-card-icon" style="background:rgba(245,158,11,0.08)">
            <i data-lucide="map-pin" style="width:13px;height:13px;color:var(--amber)"></i>
          </div>
          <span class="section-card-title">Area</span>
          <div class="flex items-center gap-1 ml-auto">
            <select id="dash-area-select" class="text-xs border border-gray-200 rounded-full px-2.5 py-1 bg-white font-medium text-gray-600 outline-none focus:border-brand" style="font-family:inherit;min-height:28px;appearance:none;padding-right:20px;background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394A3B8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Cpath d=%22m6 9 6 6 6-6%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 6px center;background-size:12px">
              <option value="">Select Area</option>
              <?php foreach ($areaNames as $an): ?>
              <option value="<?= htmlspecialchars($an) ?>"><?= htmlspecialchars($an) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="section-card-body">
          <div class="stat-grid" id="area-card-body">
            <div class="stat-box compact"><div class="stat-box-label">All</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
            <div class="stat-box compact"><div class="stat-box-label">Done</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
            <div class="stat-box compact"><div class="stat-box-label">Today</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
            <div class="stat-box compact"><div class="stat-box-label">Pending</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
            <div class="stat-box compact"><div class="stat-box-label">Tom</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>
          </div>
        </div>
      </div>

    </div><!-- /section cards -->
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

function showMoreModal() {
  document.getElementById('more-modal').classList.remove('hidden');
  lucide.createIcons();
}
function hideMoreModal(e) {
  if (e.target === e.currentTarget) document.getElementById('more-modal').classList.add('hidden');
}

function quickAdd(type) {
  if (type === 'customer') window.location.href = 'customer-add.php';
  else if (type === 'order') { try { localStorage.setItem('fscrm_new_order_type', 'one-time'); } catch(e) {} window.location.href = 'orders.php'; }
  else if (type === 'onetime') window.location.href = 'onetime-task.php';
  else if (type === 'recurring') window.location.href = 'recurring-task.php';
}

function navTasks(type, status, label) {
  try { localStorage.setItem('fscrm_task_filter', JSON.stringify({ status: status, label: label, type: type, value: null })); } catch(e) {}
  window.location.href = 'onetime-task.php';
}

function logout() {
  window.location.href = 'logout.php';
}

function renderStaffStats(stats, name) {
  if (!stats) return '<div class="stat-grid">' + emptyCells() + '</div>';
  return '<div class="stat-grid">' +
    '<div class="stat-box compact"><div class="stat-box-label">All</div><div class="stat-box-value">' + stats.all + '</div></div>' +
    '<div class="stat-box compact"><div class="stat-box-label">Done</div><div class="stat-box-value" style="color:var(--primary)">' + stats.todayCompleted + '</div></div>' +
    '<div class="stat-box compact" style="background:#EFF6FF;border-color:#BFDBFE"><div class="stat-box-label" style="color:#3B82F6">Today</div><div class="stat-box-value" style="color:#3B82F6">' + stats.today + '</div></div>' +
    '<div class="stat-box compact"><div class="stat-box-label">Pending</div><div class="stat-box-value" style="color:var(--amber)">' + stats.toDo + '</div></div>' +
    '<div class="stat-box compact"><div class="stat-box-label">Tom</div><div class="stat-box-value">' + stats.tomorrow + '</div></div>' +
  '</div>';
}

function emptyCells() {
  return '' +
    '<div class="stat-box compact"><div class="stat-box-label">All</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>' +
    '<div class="stat-box compact"><div class="stat-box-label">Done</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>' +
    '<div class="stat-box compact"><div class="stat-box-label">Today</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>' +
    '<div class="stat-box compact"><div class="stat-box-label">Pending</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>' +
    '<div class="stat-box compact"><div class="stat-box-label">Tom</div><div class="stat-box-value" style="color:var(--ink-300)">&ndash;</div></div>';
}

document.addEventListener('DOMContentLoaded', function() {
  var staffSelect = document.getElementById('dash-staff-select');
  staffSelect.addEventListener('change', function() {
    if (!this.value) { document.getElementById('staff-card-body').innerHTML = emptyCells(); return; }
    var c = STAFF_STATS[this.value];
    document.getElementById('staff-card-body').innerHTML = c ? renderStaffStats(c, this.options[this.selectedIndex].text) : emptyCells();
  });

  var areaSelect = document.getElementById('dash-area-select');
  areaSelect.addEventListener('change', function() {
    if (!this.value) { document.getElementById('area-card-body').innerHTML = emptyCells(); return; }
    var c = AREA_STATS[this.value];
    document.getElementById('area-card-body').innerHTML = c ? renderStaffStats(c, this.value) : emptyCells();
  });

  lucide.createIcons();
});
</script>
<?php require_once '../includes/footer.php'; ?>
