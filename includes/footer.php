<link rel="manifest" href="../manifest.json">
<script src="../assets/js/sidebar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
<script>
lucide.createIcons();
// Embed VAPID public key for web push
window.__VAPID_PUBLIC_KEY = '<?= defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '' ?>';
// Request permission + init web push on page load
if ('Notification' in window) {
  if (Notification.permission === 'granted') {
    window.initWebPush(window.__VAPID_PUBLIC_KEY);
  } else if (Notification.permission === 'default') {
    Notification.requestPermission().then(function(p) {
      if (p === 'granted') window.initWebPush(window.__VAPID_PUBLIC_KEY);
    });
  }
}
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
