<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_locality'])) {
    requireCsrfToken();
    $name = trim($_POST['name'] ?? '');
    if ($name) {
      $stmt = $db->prepare("INSERT INTO fscrm_localities (name) VALUES (?)");
      $stmt->bind_param('s', $name);
      $stmt->execute();
      setFlash('Locality "' . $name . '" added successfully');
    }
    header('Location: localities.php');
    exit;
  }
  if (isset($_POST['edit_locality'])) {
    requireCsrfToken();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id && $name) {
      $stmt = $db->prepare("UPDATE fscrm_localities SET name = ? WHERE id = ?");
      $stmt->bind_param('si', $name, $id);
      $stmt->execute();
      setFlash('Locality updated successfully');
    }
    header('Location: localities.php');
    exit;
  }
  if (isset($_POST['delete_locality'])) {
    requireCsrfToken();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      $stmt = $db->prepare("SELECT name FROM fscrm_localities WHERE id = ?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
      $name = $row ? $row['name'] : 'Locality';
      $stmt = $db->prepare("DELETE FROM fscrm_localities WHERE id = ?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      setFlash('Locality "' . $name . '" deleted successfully');
    }
    header('Location: localities.php');
    exit;
  }
}

$result = $db->query("SELECT * FROM fscrm_localities ORDER BY name ASC");
$localities = [];
while ($row = $result->fetch_assoc()) {
    $localities[] = $row;
}
$localitiesJson = json_encode($localities);
$totalLoc = count($localities);
$pageTitle = 'Localities'; ?>
<?php require_once '../includes/header.php'; ?>
  <div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Localities</h1>
        </div>
        <button onclick="openAddModal()" class="btn btn-sm btn-primary">
          <i data-lucide="plus" class="w-4 h-4"></i> Add Locality
        </button>
      </div>
    </header>

    <div class="p-4 sm:p-6">
      <div class="flex items-center justify-between mb-4">
        <p class="text-gray-500 text-sm"><?= $totalLoc ?> area<?= $totalLoc !== 1 ? 's' : '' ?> defined</p>
      </div>

      <div class="card overflow-hidden">
        <div class="p-0">
          <table class="data-table">
            <thead>
              <tr>
                <th class="w-16">#</th>
                <th>Name</th>
                <th class="w-32 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($totalLoc === 0): ?>
              <tr>
                <td colspan="3" class="text-center py-12 text-gray-400">
                  <i data-lucide="map-pin" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                  <p class="font-medium">No localities defined yet</p>
                  <p class="text-sm">Click "Add Locality" to create one.</p>
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($localities as $i => $loc): ?>
              <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                <td class="px-4 py-3 text-gray-400 text-sm"><?= $i + 1 ?></td>
                <td class="px-4 py-3 font-medium text-navy"><?= htmlspecialchars($loc['name']) ?></td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button onclick="openEditModal(<?= (int)$loc['id'] ?>, '<?= htmlspecialchars($loc['name'], ENT_QUOTES) ?>')" class="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 transition-colors" title="Edit">
                      <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    </button>
                    <button onclick="openDeleteModal(<?= (int)$loc['id'] ?>, '<?= htmlspecialchars($loc['name'], ENT_QUOTES) ?>')" class="p-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-danger transition-colors" title="Delete">
                      <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Modal -->
  <div id="add-modal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="closeModal(event,'add-modal')">
    <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
      <form method="POST" action="">
        <?= csrfHiddenField() ?>
        <input type="hidden" name="add_locality" value="1">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-navy text-lg">Add Locality</h3>
          <button type="button" onclick="closeModal(event,'add-modal')" class="text-gray-400 hover:text-gray-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div class="p-5">
          <div>
            <label class="form-label">Area / Locality Name</label>
            <input type="text" name="name" required class="form-input w-full" maxlength="100" placeholder="e.g. Adarsh Nagar" autofocus>
          </div>
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
          <button type="button" onclick="closeModal(event,'add-modal')" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button type="submit" class="btn btn-md btn-primary flex-1">Add Locality</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="edit-modal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="closeModal(event,'edit-modal')">
    <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
      <form method="POST" action="">
        <?= csrfHiddenField() ?>
        <input type="hidden" name="edit_locality" value="1">
        <input type="hidden" name="id" id="edit-id" value="">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-navy text-lg">Edit Locality</h3>
          <button type="button" onclick="closeModal(event,'edit-modal')" class="text-gray-400 hover:text-gray-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div class="p-5">
          <div>
            <label class="form-label">Area / Locality Name</label>
            <input type="text" name="name" id="edit-name" required class="form-input w-full" maxlength="100">
          </div>
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
          <button type="button" onclick="closeModal(event,'edit-modal')" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button type="submit" class="btn btn-md btn-primary flex-1">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="delete-modal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="closeModal(event,'delete-modal')">
    <div class="bg-white w-full sm:max-w-sm rounded-t-2xl sm:rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
      <form method="POST" action="">
        <?= csrfHiddenField() ?>
        <input type="hidden" name="delete_locality" value="1">
        <input type="hidden" name="id" id="delete-id" value="">
        <div class="p-6 text-center">
          <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-danger"></i>
          </div>
          <h3 class="text-lg font-bold text-navy mb-2">Delete Locality?</h3>
          <p class="text-sm text-gray-500 mb-1">Are you sure you want to delete <strong id="delete-name"></strong>?</p>
          <p class="text-xs text-gray-400">Customers assigned to this area will not be affected. This cannot be undone.</p>
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
          <button type="button" onclick="closeModal(event,'delete-modal')" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button type="submit" class="btn btn-md btn-danger flex-1">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openAddModal() {
      document.getElementById('add-modal').classList.remove('hidden');
      lucide.createIcons();
    }

    function openEditModal(id, name) {
      document.getElementById('edit-id').value = id;
      document.getElementById('edit-name').value = name;
      document.getElementById('edit-modal').classList.remove('hidden');
      lucide.createIcons();
    }

    function openDeleteModal(id, name) {
      document.getElementById('delete-id').value = id;
      document.getElementById('delete-name').textContent = name;
      document.getElementById('delete-modal').classList.remove('hidden');
      lucide.createIcons();
    }

    function closeModal(e, id) {
      if (!e || e.target === e.currentTarget) {
        document.getElementById(id).classList.add('hidden');
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      if (typeof lucide !== 'undefined') lucide.createIcons();
    });
  </script>
  <?php require_once '../includes/footer.php'; ?>
</body>
</html>
