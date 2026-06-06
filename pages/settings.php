<?php
require_once '../includes/config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_service_type'])) {
        requireCsrfToken();
        if (isset($_POST['name']) && trim($_POST['name']) !== '') {
            $name = trim($_POST['name']);
            $stmt = $db->prepare("INSERT INTO fscrm_service_types (name) VALUES (?)");
            $stmt->bind_param('s', $name);
            $stmt->execute();
            setFlash('Service type "' . $name . '" added');
        }
        header('Location: settings.php');
        exit;
    }
    if (isset($_POST['save_service_types'])) {
        requireCsrfToken();
        if (isset($_POST['service_types_text'])) {
            $db->query("TRUNCATE fscrm_service_types");
            $lines = explode("\n", $_POST['service_types_text']);
            $stmt = $db->prepare("INSERT INTO fscrm_service_types (name) VALUES (?)");
            foreach ($lines as $line) {
                $name = trim($line);
                if ($name !== '') {
                    $stmt->bind_param('s', $name);
                    $stmt->execute();
                }
            }
        }
        setFlash('Service types updated');
        header('Location: settings.php');
        exit;
    }
    if (isset($_POST['delete_service_type'])) {
        requireCsrfToken();
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $count = $db->query("SELECT COUNT(*) as c FROM fscrm_service_types")->fetch_assoc()['c'];
            if ($count > 2) {
                $stmt = $db->prepare("DELETE FROM fscrm_service_types WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                setFlash('Service type deleted');
            }
        }
        header('Location: settings.php');
        exit;
    }
    if (isset($_POST['reset_data'])) {
        // form now submits directly to ../api/seed.php
        // this POST handler does nothing
        header('Location: settings.php');
        exit;
    }
}

$result = $db->query("SELECT id, name FROM fscrm_service_types ORDER BY id");
$serviceTypes = $result->fetch_all(MYSQLI_ASSOC);
$serviceTypesText = '';
foreach ($serviceTypes as $st) {
    $serviceTypesText .= $st['name'] . "\n";
}
$serviceTypesText = rtrim($serviceTypesText);

$pageTitle = 'Settings';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Settings - Recurlog</title>
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
<body class="bg-gray-50 font-sans min-h-screen">
<?php require_once '../includes/header.php'; ?>
  <div class="page-content" id="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Settings</h1>
        </div>
      </div>
    </header>

    <div class="max-w-2xl mx-auto p-4 md:p-6 space-y-6">
      <section class="card p-6">
        <div class="flex flex-col items-center text-center">
          <img src="https://ui-avatars.com/api/?name=Admin+User&background=1DB954&color=fff&size=200" alt="Admin User" class="w-20 h-20 rounded-full object-cover border-4 border-brand/20 shadow-lg mb-3">
          <h2 class="text-lg font-bold text-navy">Admin User</h2>
          <p class="text-sm text-gray-500">admin@demo.com</p>
          <button onclick="window.showToast('Edit profile is coming soon.', 'info')" class="btn btn-md btn-primary mt-4">
            <i data-lucide="edit" class="w-4 h-4"></i> Edit Profile
          </button>
        </div>
      </section>

      <section class="card overflow-hidden">
        <div class="card-header">
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Preferences</h3>
        </div>
        <div class="divide-y divide-gray-100">
          <div class="flex items-center justify-between px-5 py-4 min-h-[56px]">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-brand/10 flex items-center justify-center"><i data-lucide="bell" class="w-5 h-5 text-brand"></i></div>
              <div>
                <p class="text-sm font-medium text-navy">Notifications</p>
                <p class="text-xs text-gray-400">Daily reminders and alerts</p>
              </div>
            </div>
            <div class="relative inline-flex items-center cursor-pointer">
              <div class="w-11 h-6 bg-brand rounded-full p-1 transition-colors">
                <div class="bg-white w-4 h-4 rounded-full shadow-sm transform translate-x-5 transition-transform"></div>
              </div>
            </div>
          </div>
          <div class="flex items-center justify-between px-5 py-4 min-h-[56px]">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-brand/10 flex items-center justify-center"><i data-lucide="globe" class="w-5 h-5 text-brand"></i></div>
              <div>
                <p class="text-sm font-medium text-navy">Language</p>
                <p class="text-xs text-gray-400">Select your preferred language</p>
              </div>
            </div>
            <select class="form-select w-auto">
              <option>English</option>
              <option>Nepali</option>
            </select>
          </div>
          <div class="flex items-center justify-between px-5 py-4 min-h-[56px]">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-brand/10 flex items-center justify-center"><i data-lucide="monitor" class="w-5 h-5 text-brand"></i></div>
              <div>
                <p class="text-sm font-medium text-navy">Theme</p>
                <p class="text-xs text-gray-400">Light / Dark mode</p>
              </div>
            </div>
            <div class="flex gap-1.5">
              <button class="px-3 py-1.5 text-xs font-medium rounded-full bg-brand text-white min-h-[32px]">Light</button>
              <button class="px-3 py-1.5 text-xs font-medium rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 min-h-[32px]">Dark</button>
            </div>
          </div>
        </div>
      </section>

      <!-- Manage Service Types -->
      <section class="card overflow-hidden">
        <div class="card-header">
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Manage Service Types</h3>
        </div>
        <div class="p-5">
          <p class="text-sm text-gray-500 mb-3">Add or remove service types used in customer and job forms.</p>
          <form method="POST" action="" class="flex gap-2 mb-4">
            <?= csrfHiddenField() ?>
             <input type="text" name="name" placeholder="e.g. Water Heater" class="form-input flex-1" maxlength="100">
            <button type="submit" name="add_service_type" class="btn btn-sm btn-primary whitespace-nowrap">
              <i data-lucide="plus" class="w-4 h-4"></i> Add
            </button>
          </form>
          <form method="POST" action="">
            <?= csrfHiddenField() ?>
            <label class="text-sm font-medium text-gray-600 mb-1 block">Bulk Edit (one per line)</label>
             <textarea name="service_types_text" rows="6" class="form-input w-full mb-3 font-mono text-sm" maxlength="2000"><?= htmlspecialchars($serviceTypesText) ?></textarea>
            <button type="submit" name="save_service_types" class="btn btn-sm btn-primary">
              <i data-lucide="save" class="w-4 h-4"></i> Save Changes
            </button>
          </form>
        </div>
      </section>

      <section class="card overflow-hidden">
        <div class="card-header">
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Data</h3>
        </div>
        <div class="divide-y divide-gray-100">
          <div class="flex items-center justify-between px-5 py-4 min-h-[56px]">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-brand/10 flex items-center justify-center"><i data-lucide="download" class="w-5 h-5 text-brand"></i></div>
              <div>
                <p class="text-sm font-medium text-navy">Export Data</p>
                <p class="text-xs text-gray-400">Download as CSV or PDF</p>
              </div>
            </div>
            <div class="flex gap-2">
              <button onclick="window.showToast('CSV export coming soon.', 'info')" class="btn btn-sm btn-secondary">CSV</button>
              <button onclick="window.showToast('PDF export coming soon.', 'info')" class="btn btn-sm btn-secondary">PDF</button>
            </div>
          </div>
          <div class="flex items-center justify-between px-5 py-4 min-h-[56px]">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-danger/10 flex items-center justify-center"><i data-lucide="trash-2" class="w-5 h-5 text-danger"></i></div>
              <div>
                <p class="text-sm font-medium text-danger">Reset Demo Data</p>
                <p class="text-xs text-gray-400">Clear all data and restart fresh</p>
              </div>
            </div>
            <button onclick="openResetModal()" class="btn btn-sm btn-danger">Reset</button>
          </div>
        </div>
      </section>

      <section class="card overflow-hidden">
        <div class="card-header">
          <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Account</h3>
        </div>
        <div class="divide-y divide-gray-100">
          <div class="flex items-center justify-between px-5 py-4 min-h-[56px]">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center"><i data-lucide="log-out" class="w-5 h-5 text-gray-500"></i></div>
              <div>
                <p class="text-sm font-medium text-navy">Logout</p>
                <p class="text-xs text-gray-400">Sign out of your account</p>
              </div>
            </div>
            <button onclick="handleLogout()" class="btn btn-sm btn-secondary">Logout</button>
          </div>
        </div>
      </section>

      <p class="text-center text-xs text-gray-400 pb-4">Recurlog v1.0 &mdash; Field Service Management</p>
    </div>
  </div>

  <div id="reset-modal" class="modal-overlay hidden" onclick="closeResetModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-danger"></i>
      </div>
      <h3 class="text-lg font-bold text-navy text-center mb-2">Reset Demo Data?</h3>
      <p class="text-sm text-gray-500 text-center mb-6">This will clear all demo data including customers, tasks, and settings. This cannot be undone.</p>
      <form method="POST" action="../api/seed.php">
        <?= csrfHiddenField() ?>
        <div class="flex gap-3">
          <button type="button" onclick="closeResetModal(event)" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button type="submit" name="reset_data" class="btn btn-md btn-danger flex-1">Reset</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openResetModal() {
      document.getElementById('reset-modal').classList.remove('hidden');
      lucide.createIcons();
    }
    function closeResetModal(e) {
      if (!e || e.target === e.currentTarget) {
        document.getElementById('reset-modal').classList.add('hidden');
      }
    }
    function handleLogout() {
      window.logout();
    }
  </script>
  <?php require_once '../includes/footer.php'; ?>
</body>
</html>
