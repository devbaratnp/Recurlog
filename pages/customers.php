<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$totalResult = $db->query("SELECT COUNT(*) as cnt FROM fscrm_customers");
$totalRecords = (int)$totalResult->fetch_assoc()['cnt'];
$totalPages = max(1, (int)ceil($totalRecords / $perPage));

$result = $db->query("SELECT * FROM fscrm_customers ORDER BY name ASC LIMIT $perPage OFFSET $offset");
$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}
$customerJson = json_encode($customers);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Customers - Field Service CRM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Poppins', 'sans-serif'] },
          colors: { brand: '#1DB954', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' }
        }
      }
    }
  </script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/custom.css?v=<?= cacheBust() ?>" />
  </head>
<body class="bg-gray-50 min-h-screen">
<?php $pageTitle = 'Customers'; require_once '../includes/header.php'; ?>
  <div class="page-content" id="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Customers</h1>
        </div>
        <a href="customer-add.php" class="btn btn-sm btn-primary hidden md:inline-flex">
          <i data-lucide="plus" class="w-4 h-4"></i> Add Customer
        </a>
      </div>
    </header>

    <main class="p-4 md:p-6 max-w-6xl">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div>
          <p class="text-gray-500 text-sm">Manage your customer accounts and service history</p>
        </div>
        <a href="customer-add.php" class="btn btn-md btn-primary md:hidden">
          <i data-lucide="plus" class="w-4 h-4"></i> Add Customer
        </a>
      </div>

      <div class="relative mb-4">
        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
        <input type="text" id="customer-search" placeholder="Search customers by name&hellip;" class="form-input pl-12" />
      </div>

      <div class="card overflow-hidden">
        <div class="p-0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Area</th>
                <th>Contact</th>
                <th>Services</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="customer-table-body"></tbody>
          </table>
        </div>
        <div id="customer-empty-state" class="empty-state hidden">
          <i data-lucide="users"></i>
          <p>No customers found</p>
          <p class="empty-sub">Try adjusting your search or add a new customer.</p>
        </div>
      </div>
    </main>
  </div>

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

  <script>
    window.__CUSTOMERS = <?= $customerJson ?>;
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      lucide.createIcons();

    (function() {
      var searchInput = document.getElementById('customer-search');
      var tbody = document.getElementById('customer-table-body');
      var emptyState = document.getElementById('customer-empty-state');
      var allCustomers = window.__CUSTOMERS || [];

      function getCategoryColor(service) {
        var map = {
          'RO': 'bg-emerald-100 text-emerald-700',
          'TV': 'bg-blue-100 text-blue-700',
          'Refrigerator': 'bg-cyan-100 text-cyan-700',
          'AC': 'bg-orange-100 text-orange-700',
          'Washing Machine': 'bg-purple-100 text-purple-700',
          'Other': 'bg-gray-100 text-gray-700'
        };
        return map[service] || 'bg-gray-100 text-gray-700';
      }

      function renderTable(filter) {
        var q = (filter || '').toLowerCase().trim();
        var filtered = q ? allCustomers.filter(function(c) { return c.name.toLowerCase().includes(q); }) : allCustomers;

        if (filtered.length === 0) {
          tbody.innerHTML = '';
          emptyState.classList.remove('hidden');
          return;
        }
        emptyState.classList.add('hidden');

        tbody.innerHTML = filtered.map(function(c) {
          var svc = c.services_for || '';
          var services = svc ? svc.split(',').map(function(s) { return s.trim(); }).filter(function(s) { return s; }) : [];
          var chips = services.map(function(s) {
            return '<span class="badge ' + getCategoryColor(s).replace('bg-', 'bg-').replace('text-', 'text-') + '">' + s + '</span>';
          }).join(' ');

          return '<tr>' +
            '<td data-label="Name" class="font-medium text-gray-900">' + c.name + '</td>' +
            '<td data-label="Address" class="text-gray-600">' + c.address + '</td>' +
            '<td data-label="Area" class="text-gray-600">' + (c.area || '\u2014') + '</td>' +
            '<td data-label="Contact" class="text-gray-600">' + c.phone + '</td>' +
            '<td data-label="Services"><div class="flex flex-wrap gap-1.5">' + chips + '</div></td>' +
            '<td data-label="" class="text-right">' +
              '<div class="flex items-center justify-end gap-1.5">' +
              '<a href="customer-add.php?id=' + c.id + '" class="btn btn-sm btn-ghost p-1.5" title="Edit">' +
                '<i data-lucide="pencil" class="w-3.5 h-3.5"></i>' +
              '</a>' +
              '<button onclick="goToCustomer(' + c.id + ')" class="btn btn-sm btn-primary">' +
                '<i data-lucide="eye" class="w-3.5 h-3.5"></i> View' +
              '</button>' +
              '</div>' +
            '</td>' +
          '</tr>';
        }).join('');

        try { lucide.createIcons(); } catch(e) {}
      }

      searchInput.addEventListener('input', function() {
        renderTable(this.value);
      });

      renderTable('');
    })();
    });
  </script>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>
