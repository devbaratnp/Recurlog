<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_staff'])) {
    requireCsrfToken();
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $avatar = trim($_POST['avatar'] ?? '');
    if (!$avatar) {
      $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=22C55E&color=fff&size=200';
    }
    if ($name) {
      $stmt = $db->prepare("INSERT INTO fscrm_staff (name, phone, avatar) VALUES (?, ?, ?)");
      $stmt->bind_param('sss', $name, $phone, $avatar);
      $stmt->execute();
      setFlash('Staff "' . $name . '" added successfully');
    }
    header('Location: staff.php');
    exit;
  }
  if (isset($_POST['edit_staff'])) {
    requireCsrfToken();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $avatar = trim($_POST['avatar'] ?? '');
    if ($id && $name) {
      if (!$avatar) {
        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=22C55E&color=fff&size=200';
      }
      $stmt = $db->prepare("UPDATE fscrm_staff SET name = ?, phone = ?, avatar = ? WHERE id = ?");
      $stmt->bind_param('sssi', $name, $phone, $avatar, $id);
      $stmt->execute();
      setFlash('Staff "' . $name . '" updated successfully');
    }
    header('Location: staff.php');
    exit;
  }
  if (isset($_POST['delete_staff'])) {
    requireCsrfToken();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      $stmt = $db->prepare("SELECT name FROM fscrm_staff WHERE id = ?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $delRow = $stmt->get_result()->fetch_assoc();
      $delName = $delRow ? $delRow['name'] : 'Staff';
      $stmt = $db->prepare("DELETE FROM fscrm_users WHERE staff_id = ?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $stmt = $db->prepare("DELETE FROM fscrm_staff WHERE id = ?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      setFlash('Staff "' . $delName . '" deleted successfully');
    }
    header('Location: staff.php');
    exit;
  }
  if (isset($_POST['set_password'])) {
    requireCsrfToken();
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($staffId && $email && strlen($password) >= 4) {
      $stmt = $db->prepare("SELECT id, name FROM fscrm_staff WHERE id = ?");
      $stmt->bind_param('i', $staffId);
      $stmt->execute();
      $staffRow = $stmt->get_result()->fetch_assoc();
      if ($staffRow) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("SELECT id FROM fscrm_users WHERE staff_id = ?");
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
          $stmt = $db->prepare("UPDATE fscrm_users SET email = ?, password = ? WHERE staff_id = ?");
          $stmt->bind_param('ssi', $email, $hash, $staffId);
        } else {
          $stmt = $db->prepare("INSERT INTO fscrm_users (name, email, password, role, staff_id) VALUES (?, ?, ?, 'staff', ?)");
          $stmt->bind_param('sssi', $staffRow['name'], $email, $hash, $staffId);
        }
        $stmt->execute();
        setFlash('Login credentials ' . ($existing ? 'updated' : 'created') . ' for "' . $staffRow['name'] . '"');
      }
    }
    header('Location: staff.php');
    exit;
  }
}

$result = $db->query("SELECT s.*,
  (SELECT COUNT(*) FROM fscrm_tasks WHERE assigned_to = s.id AND status = 'pending') as active_tasks,
  (SELECT COUNT(*) FROM fscrm_tasks WHERE assigned_to = s.id) as total,
  (SELECT COUNT(*) FROM fscrm_tasks WHERE assigned_to = s.id AND status = 'completed') as completed,
  u.id as user_id,
  u.email as user_email
FROM fscrm_staff s
LEFT JOIN fscrm_users u ON u.staff_id = s.id
ORDER BY s.name");
$staff = [];
while ($row = $result->fetch_assoc()) {
  $row['completion_rate'] = $row['total'] > 0 ? round(($row['completed'] / $row['total']) * 100) : 0;
  $staff[] = $row;
}
$staffJson = json_encode($staff);
?><?php $pageTitle = 'Staff'; require_once '../includes/header.php'; ?>
  <div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Staff</h1>
        </div>
        <button onclick="openAddModal()" class="btn btn-sm btn-primary">
          <i data-lucide="plus" class="w-4 h-4"></i> Add Staff
        </button>
      </div>
    </header>

    <div class="p-4 sm:p-6">
      <?php if (empty($staff)): ?>
      <div class="empty-state">
        <i data-lucide="users"></i>
        <p>No staff found</p>
        <p class="empty-sub">Add your first staff member to get started</p>
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($staff as $s): ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all fade-in relative group">
          <div class="absolute top-3 right-3 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button onclick="openEditModal(<?= $s['id'] ?>)" class="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500" title="Edit">
              <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
            </button>
            <button onclick="openDeleteModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>')" class="p-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-danger" title="Delete">
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </div>
          <div class="flex items-center gap-4 mb-4">
            <img src="<?= htmlspecialchars($s['avatar']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" class="w-14 h-14 rounded-full object-cover border-2 border-gray-100">
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-navy text-base truncate"><?= htmlspecialchars($s['name']) ?></h3>
              <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($s['phone'] ?? '') ?></p>
            </div>
          </div>
          <div class="flex items-center gap-4 mb-3 text-sm">
            <div class="flex items-center gap-1.5 text-gray-500"><i data-lucide="clipboard-list" class="w-4 h-4"></i> <span class="font-medium text-navy"><?= (int)$s['active_tasks'] ?></span> Active</div>
            <div class="flex items-center gap-1.5 text-gray-500"><i data-lucide="check-circle" class="w-4 h-4 text-brand"></i> <span class="font-medium text-navy"><?= $s['completion_rate'] ?>%</span></div>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2 mb-3">
            <div class="bg-brand h-2 rounded-full transition-all" style="width:<?= $s['completion_rate'] ?>%"></div>
          </div>
          <div class="flex items-center justify-between">
            <a href="staff-detail.php?id=<?= $s['id'] ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand hover:text-brand/80 transition-colors">View Profile <i data-lucide="chevron-right" class="w-4 h-4"></i></a>
            <div class="flex items-center gap-2">
              <span class="badge <?= $s['user_id'] ? 'badge-completed' : 'badge-pending' ?>">
                <?= $s['user_id'] ? 'Has Login' : 'No Login' ?>
              </span>
              <button onclick="openPasswordModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['user_email'] ?? '', ENT_QUOTES) ?>')" class="text-xs font-medium text-navy hover:text-brand transition-colors">
                <i data-lucide="key" class="w-3.5 h-3.5 inline"></i> <?= $s['user_id'] ? 'Reset' : 'Set' ?> Password
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Add Modal -->
  <div id="add-modal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="closeModal(event,'add-modal')">
    <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
      <form method="POST" action="">
        <?= csrfHiddenField() ?>
        <input type="hidden" name="add_staff" value="1">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-navy">Add Staff</h3>
          <button type="button" onclick="closeModal(event,'add-modal')" class="text-gray-400 hover:text-gray-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div class="p-5 space-y-4">
          <div>
            <label class="form-label">Name</label>
            <input type="text" name="name" required class="form-input w-full" maxlength="100" placeholder="Full name">
          </div>
          <div>
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-input w-full" maxlength="20" placeholder="Phone number">
          </div>
          <div>
            <label class="form-label">Avatar URL <span class="text-gray-400 text-xs">(optional)</span></label>
            <input type="url" name="avatar" class="form-input w-full" maxlength="500" placeholder="https://ui-avatars.com/api/?name=...">
          </div>
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
          <button type="button" onclick="closeModal(event,'add-modal')" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button type="submit" class="btn btn-md btn-primary flex-1">Add Staff</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="edit-modal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="closeModal(event,'edit-modal')">
    <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
      <form method="POST" action="">
        <?= csrfHiddenField() ?>
        <input type="hidden" name="edit_staff" value="1">
        <input type="hidden" name="id" id="edit-id" value="">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-navy">Edit Staff</h3>
          <button type="button" onclick="closeModal(event,'edit-modal')" class="text-gray-400 hover:text-gray-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div class="p-5 space-y-4">
          <div>
            <label class="form-label">Name</label>
            <input type="text" name="name" id="edit-name" required class="form-input w-full" maxlength="100">
          </div>
          <div>
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="edit-phone" class="form-input w-full" maxlength="20">
          </div>
          <div>
            <label class="form-label">Avatar URL <span class="text-gray-400 text-xs">(optional)</span></label>
            <input type="url" name="avatar" id="edit-avatar" class="form-input w-full" maxlength="500">
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
        <input type="hidden" name="delete_staff" value="1">
        <input type="hidden" name="id" id="delete-id" value="">
        <div class="p-6 text-center">
          <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-danger"></i>
          </div>
          <h3 class="text-lg font-bold text-navy mb-2">Delete Staff?</h3>
          <p class="text-sm text-gray-500 mb-1">Are you sure you want to delete <strong id="delete-name"></strong>?</p>
          <p class="text-xs text-gray-400">Assigned tasks will become unassigned. This cannot be undone.</p>
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
          <button type="button" onclick="closeModal(event,'delete-modal')" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button type="submit" class="btn btn-md btn-danger flex-1">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Set Password Modal -->
  <div id="password-modal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="closeModal(event,'password-modal')">
    <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
      <form method="POST" action="">
        <?= csrfHiddenField() ?>
        <input type="hidden" name="set_password" value="1">
        <input type="hidden" name="staff_id" id="pwd-staff-id" value="">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-navy">Set Password</h3>
          <button type="button" onclick="closeModal(event,'password-modal')" class="text-gray-400 hover:text-gray-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div class="p-5 space-y-4">
          <div>
            <label class="form-label">Staff</label>
            <p id="pwd-staff-name" class="text-sm font-medium text-navy bg-gray-50 rounded-lg px-3 py-2"></p>
          </div>
          <div>
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="pwd-email" required class="form-input w-full" maxlength="255" placeholder="staff@example.com">
          </div>
          <div>
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <div class="relative">
              <input type="password" name="password" id="pwd-password" required class="form-input w-full pr-10" minlength="4" placeholder="Minimum 4 characters">
              <button type="button" onclick="togglePassword()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1">
                <i data-lucide="eye" id="pwd-eye-icon" class="w-4 h-4"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
          <button type="button" onclick="closeModal(event,'password-modal')" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button type="submit" class="btn btn-md btn-primary flex-1">Save Login</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    var STAFF_DATA = <?= $staffJson ?>;

    function openAddModal() {
      document.getElementById('add-modal').classList.remove('hidden');
      lucide.createIcons();
    }

    function openEditModal(id) {
      var s = STAFF_DATA.find(function(x) { return x.id == id; });
      if (!s) return;
      document.getElementById('edit-id').value = s.id;
      document.getElementById('edit-name').value = s.name;
      document.getElementById('edit-phone').value = s.phone || '';
      document.getElementById('edit-avatar').value = s.avatar || '';
      document.getElementById('edit-modal').classList.remove('hidden');
      lucide.createIcons();
    }

    function openDeleteModal(id, name) {
      document.getElementById('delete-id').value = id;
      document.getElementById('delete-name').textContent = name;
      document.getElementById('delete-modal').classList.remove('hidden');
      lucide.createIcons();
    }

    function openPasswordModal(id, name, email) {
      document.getElementById('pwd-staff-id').value = id;
      document.getElementById('pwd-staff-name').textContent = name;
      document.getElementById('pwd-email').value = email || '';
      document.getElementById('pwd-password').value = '';
      document.getElementById('password-modal').classList.remove('hidden');
      lucide.createIcons();
    }

    function togglePassword() {
      var inp = document.getElementById('pwd-password');
      var icon = document.getElementById('pwd-eye-icon');
      if (inp.type === 'password') {
        inp.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
      } else {
        inp.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
      }
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
