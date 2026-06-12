<?php
require_once __DIR__ . '/config.php';
requireAuth();
$user = authUser();
$isStaff = !empty($_SESSION['user_staff_id']);

$db = getDB();

// Get notification count for badge
$notifResult = $db->query("SELECT COUNT(*) as cnt FROM fscrm_notifications WHERE is_read = 0");
$notifRow = $notifResult->fetch_assoc();
$unreadCount = $notifRow['cnt'];
$page = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Recurlog';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= htmlspecialchars($pageTitle) ?> - Recurlog</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link rel="manifest" href="../manifest.json">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: '#22C55E', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' },
          fontFamily: { sans: ['Poppins', 'sans-serif'] }
        }
      }
    }
  </script>
</head>
<body class="bg-[#F2F2F7] min-h-screen font-sans<?= $isStaff ? ' staff-mode pb-20' : '' ?>">
<?php if ($isStaff):
  // Look up staff record for avatar
  $staffRecord = null;
  $staffId = (int)($_SESSION['user_staff_id']);
  if ($staffId) {
    $sr = $db->prepare("SELECT * FROM fscrm_staff WHERE id = ?");
    $sr->bind_param('i', $staffId);
    $sr->execute();
    $staffRecord = $sr->get_result()->fetch_assoc();
  }
  $staffAvatar = $staffRecord['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=22C55E&color=fff&size=200';
?>
<header class="bg-navy text-white sticky top-0 z-30 shadow-lg">
  <div class="flex items-center justify-between px-4 py-3 max-w-5xl mx-auto">
    <div class="flex items-center gap-3">
      <img src="<?= htmlspecialchars($staffAvatar) ?>" alt="" class="w-9 h-9 rounded-full border-2 border-brand/50">
      <div>
        <h1 class="text-sm font-bold leading-tight"><?= htmlspecialchars($user['name']) ?></h1>
        <p class="text-xs text-white/50">Field Staff</p>
      </div>
    </div>
    <a href="logout.php" class="flex items-center gap-1.5 text-sm text-white/70 hover:text-white bg-white/10 hover:bg-white/20 rounded-lg px-3 py-2 transition-colors">
      <i data-lucide="log-out" class="w-4 h-4"></i> Logout
    </a>
  </div>
</header>
<?php else: ?>
<?php include __DIR__ . '/sidebar.php'; ?>
<?php endif; ?>
<main id="main-content" style="transition: margin-left 0.25s ease">
