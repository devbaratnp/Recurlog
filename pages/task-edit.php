<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$db = getDB();

$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$editId) { header('Location: onetime-task.php'); exit; }

$stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS staff_name, sv.service_for, sv.problem AS service_problem FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id WHERE t.id = ?");
$stmt->bind_param('i', $editId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
if (!$task) { header('Location: onetime-task.php'); exit; }

$staffList = $db->query("SELECT id, name FROM fscrm_staff ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireCsrfToken();
  $title = trim($_POST['title'] ?? '');
  $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
  $scheduledDate = $_POST['scheduled_date'] ?? '';
  $status = $_POST['status'] ?? 'pending';
  $notes = trim($_POST['notes'] ?? '');
  $error = null;

  if (!$title) $error = 'Title is required.';

  if (!$error) {
    $db->begin_transaction();
    try {
      $stmt = $db->prepare("UPDATE fscrm_tasks SET title=?, assigned_to=?, scheduled_date=?, status=?, notes=? WHERE id=?");
      $stmt->bind_param('sisssi', $title, $assignedTo, $scheduledDate, $status, $notes, $editId);
      $stmt->execute();
      $db->commit();
      setFlash('Task "' . $title . '" updated successfully');
      header('Location: task-detail.php?id=' . $editId);
      exit;
    } catch (Exception $e) {
      $db->rollback();
      $error = 'Failed to update task. Please try again.';
    }
  }
}

$pageTitle = 'Edit Task';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Edit Task - Recurlog</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
  <link rel="stylesheet" href="../assets/css/custom.css?v=<?= cacheBust() ?>">
</head>
<body class="bg-gray-50 min-h-screen">
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="page-content">
  <header class="page-header">
    <div class="page-header-inner">
      <div class="flex items-center gap-2">
        <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <a href="task-detail.php?id=<?= $editId ?>" class="p-2 -ml-1 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100">
          <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="page-title">Edit Task</h1>
      </div>
    </div>
  </header>

  <div class="p-4 md:p-6 max-w-3xl mx-auto">

    <p class="text-gray-500 text-sm mb-4">Update task details</p>

<?php if (!empty($error)): ?>
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

    <form method="POST" action=""><?= csrfHiddenField() ?>
    <div class="card p-5 sm:p-8 space-y-6">

      <div>
        <label for="task-customer" class="block text-sm font-semibold text-gray-700 mb-1.5">Customer</label>
        <input type="text" id="task-customer" readonly value="<?= htmlspecialchars($task['customer_name'] ?: '—') ?>" class="form-input bg-gray-50 cursor-not-allowed">
      </div>

      <div>
        <label for="task-title" class="block text-sm font-semibold text-gray-700 mb-1.5">Title <span class="text-danger">*</span></label>
        <input type="text" id="task-title" name="title" value="<?= htmlspecialchars($task['title']) ?>" class="form-input" maxlength="255">
      </div>

      <div>
        <label for="task-status" class="block text-sm font-semibold text-gray-700 mb-1.5">Status</label>
        <select id="task-status" name="status" class="form-select">
          <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="missed" <?= $task['status'] === 'missed' ? 'selected' : '' ?>>Missed</option>
        </select>
      </div>

      <div>
        <label for="task-date" class="block text-sm font-semibold text-gray-700 mb-1.5">Scheduled Date</label>
        <input type="date" id="task-date" name="scheduled_date" value="<?= htmlspecialchars($task['scheduled_date']) ?>" class="form-input max-w-xs">
      </div>

      <div>
        <label for="task-staff" class="block text-sm font-semibold text-gray-700 mb-1.5">Assign To</label>
        <select id="task-staff" name="assigned_to" class="form-select">
          <option value="">Unassigned</option>
          <?php foreach ($staffList as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $task['assigned_to'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="task-notes" class="block text-sm font-semibold text-gray-700 mb-1.5">Notes</label>
        <textarea id="task-notes" name="notes" rows="3" class="form-textarea" maxlength="1000"><?= htmlspecialchars($task['notes'] ?? '') ?></textarea>
      </div>

    </div>

    <div class="mt-6 flex gap-3">
      <a href="task-detail.php?id=<?= $editId ?>" class="btn btn-md btn-secondary flex-1 md:flex-none">Cancel</a>
      <button type="submit" class="btn btn-md btn-primary flex-1 md:flex-none brand-glow">
        <i data-lucide="save" class="w-4 h-4"></i> Update Task
      </button>
    </div>
    </form>

  </div>
</div>
<script>lucide.createIcons();</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
