<?php
require_once '../includes/config.php';
$db = getDB();

// Fetch all tasks with joins
$result = $db->query("SELECT t.*, c.name AS customer_name, s.name AS staff_name FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id ORDER BY t.scheduled_date DESC");
$allTasks = array();
while ($r = $result->fetch_assoc()) {
    $allTasks[] = array(
        'id' => (int)$r['id'],
        'title' => $r['title'],
        'customerId' => $r['customer_id'] ? (int)$r['customer_id'] : null,
        'customerName' => $r['customer_name'],
        'assignedTo' => $r['assigned_to'] ? (int)$r['assigned_to'] : null,
        'staffName' => $r['staff_name'],
        'scheduledDate' => $r['scheduled_date'],
        'completedDate' => $r['completed_date'],
        'status' => $r['status'],
        'notes' => $r['notes'],
        'completedBy' => $r['completed_by'],
    );
}

// Fetch all orders
$orderResult = $db->query("SELECT * FROM fscrm_orders ORDER BY created_at DESC");
$allOrders = array();
while ($r = $orderResult->fetch_assoc()) {
    $allOrders[] = array(
        'id' => (int)$r['id'],
        'customerId' => $r['customer_id'] ? (int)$r['customer_id'] : null,
        'customerName' => $r['customer_name'],
        'problem' => $r['problem'],
        'status' => $r['status'],
        'priority' => $r['priority'],
        'assignedTo' => $r['assigned_to'] ? (int)$r['assigned_to'] : null,
        'scheduledDate' => $r['scheduled_date'],
        'completedDate' => $r['completed_date'],
        'createdAt' => $r['created_at'],
        'notes' => $r['notes'],
    );
}

// Customers and staff for lookups
$custResult = $db->query("SELECT id, name FROM fscrm_customers ORDER BY name");
$customers = array();
while ($r = $custResult->fetch_assoc()) {
    $customers[] = array('id' => (int)$r['id'], 'name' => $r['name']);
}
$staffResult = $db->query("SELECT id, name FROM fscrm_staff ORDER BY name");
$staff = array();
while ($r = $staffResult->fetch_assoc()) {
    $staff[] = array('id' => (int)$r['id'], 'name' => $r['name']);
}

function statusPill($status) {
    $map = array(
        'completed' => 'badge-completed',
        'in_progress' => 'badge-info',
        'pending' => 'badge-pending',
        'scheduled' => 'badge-pending',
        'missed' => 'badge-danger',
        'overdue' => 'badge-danger',
        'cancelled' => 'badge-secondary',
    );
    $class = isset($map[$status]) ? $map[$status] : 'badge-pending';
    return '<span class="badge ' . $class . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

$pageTitle = 'Daybook';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Daybook - Recurlog</title>
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
</head>
<body class="bg-gray-50 min-h-screen font-sans">
<?php require_once '../includes/header.php'; ?>
<div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Daybook</h1>
        </div>
      </div>
    </header>

    <div class="p-4 md:p-6 lg:p-8 max-w-4xl mx-auto">

      <!-- Date navigator -->
      <div class="card p-4 mb-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <button id="day-prev" class="btn btn-sm btn-secondary"><i data-lucide="chevron-left" class="w-4 h-4"></i> Prev</button>
          <div class="flex items-center gap-3">
            <input type="date" id="day-date" class="form-input">
            <button id="day-today" class="btn btn-sm btn-ghost">Today</button>
          </div>
          <button id="day-next" class="btn btn-sm btn-secondary">Next <i data-lucide="chevron-right" class="w-4 h-4"></i></button>
        </div>
        <p id="day-label" class="text-center text-sm font-semibold text-navy mt-3"></p>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="card p-3 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Scheduled</p>
          <p id="sum-scheduled" class="text-2xl font-bold text-navy mt-1">0</p>
        </div>
        <div class="card p-3 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Completed</p>
          <p id="sum-completed" class="text-2xl font-bold text-brand mt-1">0</p>
        </div>
        <div class="card p-3 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Orders Created</p>
          <p id="sum-orders-created" class="text-2xl font-bold text-navy mt-1">0</p>
        </div>
        <div class="card p-3 text-center">
          <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Orders Done</p>
          <p id="sum-orders-done" class="text-2xl font-bold text-brand mt-1">0</p>
        </div>
      </div>

      <!-- Tasks scheduled -->
      <div class="card overflow-hidden mb-5">
        <div class="card-header"><h2 class="font-semibold text-navy flex items-center gap-2"><i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i> Tasks Scheduled</h2></div>
        <div id="scheduled-list"></div>
      </div>

      <!-- Tasks completed -->
      <div class="card overflow-hidden mb-5">
        <div class="card-header"><h2 class="font-semibold text-navy flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4 text-brand"></i> Tasks Completed</h2></div>
        <div id="completed-list"></div>
      </div>

      <!-- Order activity -->
      <div class="card overflow-hidden mb-5">
        <div class="card-header"><h2 class="font-semibold text-navy flex items-center gap-2"><i data-lucide="clipboard-list" class="w-4 h-4 text-gray-400"></i> Order Activity</h2></div>
        <div id="orders-list"></div>
      </div>

    </div>
  </div>

  <script>
    // ── Embedded data from PHP ──
    var __tasks = <?= json_encode($allTasks) ?>;
    var __orders = <?= json_encode($allOrders) ?>;
    var __customers = <?= json_encode($customers) ?>;
    var __staff = <?= json_encode($staff) ?>;

    var __customerMap = {};
    __customers.forEach(function(c) { __customerMap[c.id] = c.name; });
    var __staffMap = {};
    __staff.forEach(function(s) { __staffMap[s.id] = s.name; });

    lucide.createIcons();

    function isoFor(d) {
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function todayISO() {
      return isoFor(new Date());
    }

    function customerName(id) {
      return __customerMap[id] || 'Unknown';
    }

    function staffName(id) {
      return __staffMap[id] || 'Unassigned';
    }

    function emptyRow(msg) {
      return '<div class="p-5 text-center text-sm text-gray-400">' + msg + '</div>';
    }

    function statusPill(status) {
      var cls = 'badge-pending';
      if (status === 'completed') cls = 'badge-completed';
      else if (status === 'in_progress') cls = 'badge-info';
      else if (status === 'missed' || status === 'overdue') cls = 'badge-danger';
      else if (status === 'cancelled') cls = 'badge-secondary';
      var label = status ? status.replace(/_/g, ' ') : 'Unknown';
      label = label.charAt(0).toUpperCase() + label.slice(1);
      return '<span class="badge ' + cls + '">' + label + '</span>';
    }

    function taskRow(t, showCompletion) {
      var extra = '';
      if (showCompletion) {
        var bits = [];
        if (t.completedBy) bits.push('By ' + t.completedBy);
        if (t.notes) bits.push(t.notes);
        if (bits.length) extra = '<p class="text-xs text-gray-400 mt-0.5">' + bits.join(' &middot; ') + '</p>';
      }
      return '<div class="px-4 py-3 border-b border-gray-50 last:border-0">' +
        '<div class="flex items-center justify-between gap-3">' +
          '<div class="min-w-0">' +
            '<p class="text-sm font-medium text-gray-900 truncate"><a href="task-detail.php?id=' + t.id + '" class="text-inherit hover:underline">' + t.title + '</a></p>' +
            '<p class="text-xs text-gray-500 mt-0.5">' + customerName(t.customerId) + ' &middot; ' + staffName(t.assignedTo) + '</p>' +
            extra +
          '</div>' +
          statusPill(t.status) +
        '</div>' +
      '</div>';
    }

    function orderRow(o, kind) {
      var badge = kind === 'created'
        ? '<span class="badge badge-info">Created</span>'
        : '<span class="badge badge-completed">Completed</span>';
      return '<div class="px-4 py-3 border-b border-gray-50 last:border-0">' +
        '<div class="flex items-center justify-between gap-3">' +
          '<div class="min-w-0">' +
            '<p class="text-sm font-medium text-gray-900 truncate">' + (o.customerName || 'Order') + '</p>' +
            '<p class="text-xs text-gray-500 mt-0.5 truncate">' + (o.problem || '') + '</p>' +
          '</div>' +
          badge +
        '</div>' +
      '</div>';
    }

    function render(dateStr) {
      var scheduled = __tasks.filter(function (t) { return t.scheduledDate === dateStr; });
      var completed = __tasks.filter(function (t) { return t.completedDate === dateStr && t.status === 'completed'; });
      var ordersCreated = __orders.filter(function (o) { return (o.createdAt || '').slice(0, 10) === dateStr; });
      var ordersDone = __orders.filter(function (o) { return o.completedDate === dateStr && o.status === 'completed'; });

      document.getElementById('sum-scheduled').textContent = scheduled.length;
      document.getElementById('sum-completed').textContent = completed.length;
      document.getElementById('sum-orders-created').textContent = ordersCreated.length;
      document.getElementById('sum-orders-done').textContent = ordersDone.length;

      document.getElementById('scheduled-list').innerHTML =
        scheduled.length ? scheduled.map(function (t) { return taskRow(t, false); }).join('') : emptyRow('No tasks scheduled.');
      document.getElementById('completed-list').innerHTML =
        completed.length ? completed.map(function (t) { return taskRow(t, true); }).join('') : emptyRow('No tasks completed.');

      var orderHtml = '';
      ordersCreated.forEach(function (o) { orderHtml += orderRow(o, 'created'); });
      ordersDone.forEach(function (o) { orderHtml += orderRow(o, 'done'); });
      document.getElementById('orders-list').innerHTML = orderHtml || emptyRow('No order activity.');

      var d = new Date(dateStr + 'T00:00:00');
      document.getElementById('day-label').textContent =
        d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

      try { lucide.createIcons(); } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', function () {
      var input = document.getElementById('day-date');
      input.value = todayISO();
      render(input.value);

      input.addEventListener('change', function () { if (this.value) render(this.value); });

      function shift(days) {
        var d = new Date(input.value + 'T00:00:00');
        d.setDate(d.getDate() + days);
        input.value = isoFor(d);
        render(input.value);
      }
      document.getElementById('day-prev').addEventListener('click', function () { shift(-1); });
      document.getElementById('day-next').addEventListener('click', function () { shift(1); });
      document.getElementById('day-today').addEventListener('click', function () {
        input.value = todayISO();
        render(input.value);
      });
    });
  </script>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>
