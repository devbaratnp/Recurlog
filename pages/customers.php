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
$pageTitle = 'Customers'; ?>
<?php require_once '../includes/header.php'; ?>
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
      <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">Manage your customer accounts and service history</p>
        <a href="customer-add.php" class="btn btn-sm btn-primary md:hidden">
          <i data-lucide="plus" class="w-4 h-4"></i> Add
        </a>
      </div>

      <div class="relative mb-4">
        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
        <input type="text" id="customer-search" placeholder="Search customers&hellip;" class="form-input pl-10" />
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
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
        <div id="customer-empty-state" class="empty-state hidden">
          <i data-lucide="users"></i>
          <p>No customers found</p>
          <p class="empty-sub">Try adjusting your search or add a new customer.</p>
        </div>
      </div>
    </main>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-100 ml-0 md:ml-[240px]">
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

    var deleteCustId = null;

    function attachCustDeleteHandlers() {
      document.querySelectorAll('.delete-cust-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          deleteCustId = parseInt(this.dataset.custId, 10);
          document.getElementById('del-cust-name').textContent = this.dataset.custName;
          document.getElementById('del-cust-phone').textContent = this.dataset.custPhone || '\u2014';
          document.getElementById('del-cust-address').textContent = this.dataset.custAddress || '\u2014';
          document.getElementById('delete-cust-modal').style.display = 'flex';
        });
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      lucide.createIcons();

    (function() {
      var searchInput = document.getElementById('customer-search');
      var tbody = document.getElementById('customer-table-body');
      var emptyState = document.getElementById('customer-empty-state');
      var allCustomers = window.__CUSTOMERS || [];

      var origRender = function(filter) {
        var q = (filter || '').toLowerCase().trim();
        var filtered = q ? allCustomers.filter(function(c) { return c.name.toLowerCase().includes(q) || c.phone.includes(q); }) : allCustomers;

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
            var colors = {
              'RO': 'bg-emerald-100 text-emerald-700',
              'TV': 'bg-blue-100 text-blue-700',
              'Refrigerator': 'bg-cyan-100 text-cyan-700',
              'AC': 'bg-orange-100 text-orange-700',
              'Washing Machine': 'bg-purple-100 text-purple-700'
            };
            var cls = colors[s] || 'bg-gray-100 text-gray-700';
            return '<span class="badge ' + cls + '">' + s + '</span>';
          }).join(' ');

          return '<tr>' +
            '<td data-label="Name" class="font-medium text-gray-900">' + c.name + '</td>' +
            '<td data-label="Address" class="text-gray-600">' + c.address + '</td>' +
            '<td data-label="Area" class="text-gray-600">' + (c.area || '\u2014') + '</td>' +
            '<td data-label="Contact" class="text-gray-600"><a href="tel:' + c.phone + '" class="text-brand hover:text-green-700 transition-colors">' + c.phone + '</a></td>' +
            '<td data-label="Services"><div class="flex flex-wrap gap-1.5">' + chips + '</div></td>' +
            '<td data-label="" class="text-right">' +
              '<div class="flex items-center justify-end gap-1.5">' +
              '<button class="delete-cust-btn btn btn-sm btn-ghost p-1.5 text-red-500 hover:text-red-700" title="Delete" data-cust-id="' + c.id + '" data-cust-name="' + c.name.replace(/'/g, "\\'") + '" data-cust-phone="' + (c.phone || '') + '" data-cust-address="' + (c.address || '').replace(/'/g, "\\'") + '">' +
                '<i data-lucide="trash-2" class="w-3.5 h-3.5"></i>' +
              '</button>' +
              '<a href="customer-add.php?id=' + c.id + '" class="btn btn-sm btn-ghost p-1.5" title="Edit">' +
                '<i data-lucide="pencil" class="w-3.5 h-3.5"></i>' +
              '</a>' +
              '</div>' +
              '<div class="mt-1.5"><a href="customer-detail.php?id=' + c.id + '" class="text-brand text-xs font-medium hover:text-green-700 transition-colors">View Details &rarr;</a></div>' +
            '</td>' +
          '</tr>';
        }).join('');

        try { lucide.createIcons(); } catch(e) {}
      };

      window.renderTable = function(filter) {
        origRender(filter);
        attachCustDeleteHandlers();
      };

      searchInput.addEventListener('input', function() {
        renderTable(this.value);
      });

      renderTable('');
      attachCustDeleteHandlers();
    })();

      document.getElementById('delete-cust-confirm-btn').addEventListener('click', async function () {
        if (!deleteCustId) return;
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Deleting...';
        try {
          var res = await fetch('../api/customers.php?id=' + deleteCustId, { method: 'DELETE' });
          var data = await res.json();
          if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); btn.disabled = false; btn.textContent = 'Delete Everything'; return; }
          var row = document.querySelector('.delete-cust-btn[data-cust-id="' + deleteCustId + '"]').closest('tr');
          if (row) row.remove();
          showToast('Customer deleted successfully', 'success');
          closeCustDeleteModal();
          try { lucide.createIcons(); } catch (e) {}
        } catch (e) {
          showToast('Network error', 'error');
          btn.disabled = false;
          btn.textContent = 'Delete Everything';
        }
      });
    });

    function closeCustDeleteModal() {
      document.getElementById('delete-cust-modal').style.display = 'none';
      deleteCustId = null;
    }
  </script>
  <!-- DELETE CUSTOMER CONFIRM MODAL -->
  <div id="delete-cust-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900">Delete Customer?</h3>
        <button type="button" onclick="closeCustDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="text-sm text-gray-600 mb-1 space-y-1">
        <p><span class="font-medium">Name:</span> <span id="del-cust-name"></span></p>
        <p><span class="font-medium">Phone:</span> <span id="del-cust-phone"></span></p>
        <p><span class="font-medium">Address:</span> <span id="del-cust-address"></span></p>
      </div>
      <p class="text-sm text-red-600 font-semibold mt-4 mb-1">This will also delete:</p>
      <ul class="text-xs text-red-500 list-disc pl-5 mb-4 space-y-0.5">
        <li>All services and tasks for this customer</li>
        <li>All orders for this customer</li>
      </ul>
      <p class="text-sm text-red-600 font-semibold mb-5">This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeCustDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="delete-cust-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete Everything</button>
      </div>
    </div>
  </div>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>
