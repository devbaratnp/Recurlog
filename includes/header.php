<?php
require_once __DIR__ . '/config.php';
requireAuth();
$user = authUser();

// Redirect staff users to their portal
if (!empty($_SESSION['user_staff_id'])) {
    header('Location: staff-dashboard.php');
    exit;
}

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
          colors: { brand: '#1DB954', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' },
          fontFamily: { sans: ['Poppins', 'sans-serif'] }
        }
      }
    }
  </script>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<main id="main-content" style="transition: margin-left 0.25s ease">
