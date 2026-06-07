<script src="../assets/js/sidebar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
<script>
lucide.createIcons();

// Flash message from PHP session
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
</body>
</html>
