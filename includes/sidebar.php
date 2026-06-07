<?php
$page = basename($_SERVER['PHP_SELF']);
function isActive($match) { global $page; return $page === $match ? 'active' : ''; }
$collapsed = !empty($_COOKIE['fscrm_sidebar_collapsed']) && $_COOKIE['fscrm_sidebar_collapsed'] === 'true';
$user = authUser();
$userInitials = '';
if ($user) {
    $parts = explode(' ', $user['name']);
    foreach ($parts as $p) $userInitials .= strtoupper($p[0] ?? '');
    if (strlen($userInitials) > 2) $userInitials = substr($userInitials, 0, 2);
}
?><div class="sidebar-backdrop" id="sidebar-backdrop"></div>
<aside class="sidebar<?= $collapsed ? ' collapsed' : '' ?>" id="sidebar" tabindex="-1">
  <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
    <a href="dashboard.php" class="flex items-center gap-3" style="text-decoration:none">
      <div class="w-9 h-9 bg-brand rounded-xl flex items-center justify-center shadow-lg shadow-brand/25">
        <i data-lucide="wrench" class="w-5 h-5 text-white"></i>
      </div>
      <span class="sidebar-brand-name text-lg font-bold tracking-tight">Recurlog</span>
    </a>
    <div class="flex items-center gap-1">
      <button class="sidebar-desktop-toggle sidebar-collapse-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i data-lucide="panel-left-close" class="w-4 h-4"></i>
      </button>
      <button class="sidebar-close-btn" onclick="closeSidebar()" aria-label="Close sidebar">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="sidebar-nav-link <?= isActive('dashboard.php') ?>"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a>
    <a href="customers.php" class="sidebar-nav-link <?= isActive('customers.php') ?>"><i data-lucide="users"></i><span>Customer</span></a>
    <a href="orders.php" class="sidebar-nav-link <?= isActive('orders.php') ?>"><i data-lucide="clipboard-list"></i><span>Order</span></a>
    <a href="onetime-task.php" class="sidebar-nav-link <?= isActive('onetime-task.php') ?>"><i data-lucide="calendar-check"></i><span>Onetime Task</span></a>
    <a href="recurring-task.php" class="sidebar-nav-link <?= isActive('recurring-task.php') ?>"><i data-lucide="repeat"></i><span>Recurring Task</span></a>
    <a href="staff.php" class="sidebar-nav-link <?= isActive('staff.php') ?>"><i data-lucide="briefcase"></i><span>Staff</span></a>
    <a href="localities.php" class="sidebar-nav-link <?= isActive('localities.php') ?>"><i data-lucide="map-pin"></i><span>Locality</span></a>
    <a href="daybook.php" class="sidebar-nav-link <?= isActive('daybook.php') ?>"><i data-lucide="book-open"></i><span>Daybook</span></a>
    <a href="reports.php" class="sidebar-nav-link <?= isActive('reports.php') ?>"><i data-lucide="bar-chart-3"></i><span>Report</span></a>
    <a href="notifications.php" class="sidebar-nav-link <?= isActive('notifications.php') ?>">
      <i data-lucide="bell"></i><span>Notification</span>
      <?php if ($unreadCount > 0): ?>
      <span class="ml-auto bg-danger text-white text-xs font-bold px-1.5 py-0.5 rounded-full"><?= $unreadCount ?></span>
      <?php endif; ?>
    </a>
    <a href="settings.php" class="sidebar-nav-link <?= isActive('settings.php') ?>"><i data-lucide="settings"></i><span>Setting</span></a>
  </nav>
  <div class="p-4 border-t border-white/10 flex flex-col gap-2">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-full bg-brand flex items-center justify-center text-xs font-bold text-white"><?= htmlspecialchars($userInitials) ?></div>
      <div class="sidebar-user-info text-sm">
        <p class="font-medium text-white"><?= htmlspecialchars($user['name'] ?? 'Admin User') ?></p>
        <p class="text-xs text-white/40"><?= htmlspecialchars(ucfirst($user['role'] ?? 'admin')) ?></p>
      </div>
    </div>
    <a href="logout.php" class="sidebar-logout sidebar-nav-link" style="width:100%;text-align:left;margin-top:4px">
      <i data-lucide="log-out" class="w-4 h-4"></i><span>Logout</span>
    </a>
  </div>
</aside>
<!-- Bottom Nav (mobile) -->
<nav class="bottom-nav md:hidden">
  <a href="dashboard.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= isActive('dashboard.php') ? 'active' : 'text-gray-500' ?>"><i data-lucide="layout-dashboard" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">Dashboard</span></a>
  <a href="customers.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= isActive('customers.php') ? 'active' : 'text-gray-500' ?>"><i data-lucide="users" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">Customers</span></a>
  <a href="orders.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= isActive('orders.php') ? 'active' : 'text-gray-500' ?>"><i data-lucide="clipboard-list" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">Orders</span></a>
  <a href="daybook.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= isActive('daybook.php') ? 'active' : 'text-gray-500' ?>"><i data-lucide="book-open" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">Daybook</span></a>
  <button onclick="toggleSidebar()" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 text-gray-500"><i data-lucide="menu" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">More</span></button>
</nav>
