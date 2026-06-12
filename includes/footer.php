<script src="../assets/js/sidebar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
<script>
lucide.createIcons();

<?php $flash = getFlash(); if ($flash): ?>
Swal.fire({
  icon: '<?= $flash['type'] ?>',
  title: '<?= addslashes($flash['title']) ?>',
  text: '<?= addslashes($flash['text']) ?>',
  timer: 3000,
  timerProgressBar: true,
  toast: true,
  position: 'top-end',
  showConfirmButton: false
});
<?php endif; ?>
</script>

<?php if (!empty($isStaff)): ?>
<!-- Staff Bottom Nav -->
<nav class="bottom-nav md:hidden" style="background:white;position:fixed;bottom:0;left:0;right:0;z-index:40;display:flex;justify-content:space-around;align-items:center;padding:4px 0 8px;border-top:1px solid #E2E8F0">
  <a href="staff-dashboard.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= $page === 'staff-dashboard.php' ? 'active' : 'text-gray-500' ?>"><i data-lucide="layout-dashboard" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">Dashboard</span></a>
  <a href="tasks.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= $page === 'tasks.php' ? 'active' : 'text-gray-500' ?>"><i data-lucide="calendar" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">Tasks</span></a>
  <a href="orders.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= $page === 'orders.php' ? 'active' : 'text-gray-500' ?>"><i data-lucide="briefcase" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">Orders</span></a>
  <a href="daybook.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 <?= $page === 'daybook.php' ? 'active' : 'text-gray-500' ?>"><i data-lucide="book-open" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">Daybook</span></a>
  <button onclick="document.getElementById('staff-more-modal').classList.toggle('hidden')" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 text-gray-500"><i data-lucide="menu" class="w-5 h-5"></i><span class="text-[10px] font-medium truncate w-full text-center">More</span></button>
</nav>

<!-- Staff More Modal -->
<div id="staff-more-modal" class="modal-overlay hidden" onclick="if(event.target===this)this.classList.add('hidden')">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:320px">
    <h3 class="font-semibold text-navy text-lg mb-4">More</h3>
    <div class="space-y-1">
      <a href="customers.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="users" class="w-5 h-5 text-gray-400"></i> Customers</a>
      <a href="customer-add.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="user-plus" class="w-5 h-5 text-gray-400"></i> Add Customer</a>
      <a href="onetime-task.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="calendar-check" class="w-5 h-5 text-gray-400"></i> One-Time Tasks</a>
      <a href="recurring-task.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="repeat" class="w-5 h-5 text-gray-400"></i> Recurring Tasks</a>
      <a href="order-add.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="plus" class="w-5 h-5 text-gray-400"></i> Add Order</a>
      <a href="notifications.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="bell" class="w-5 h-5 text-gray-400"></i> Notifications</a>
      <hr class="my-2 border-gray-100">
      <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-danger hover:bg-red-50 transition-colors w-full min-h-[44px]"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</a>
    </div>
  </div>
</div>
<?php endif; ?>
</body>
</html>
