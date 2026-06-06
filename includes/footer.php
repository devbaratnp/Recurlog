<link rel="manifest" href="../manifest.json">
<script src="../assets/js/sidebar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
<script>
lucide.createIcons();

// ===== PWA: Service Worker Registration =====
(function () {
  if (!('serviceWorker' in navigator)) return;

  function registerSW() {
    navigator.serviceWorker.register('/Recurlog/sw.js', { scope: '/Recurlog/' }).then(function (reg) {
      // Update detection
      reg.addEventListener('updatefound', function () {
        var installing = reg.installing;
        installing.addEventListener('statechange', function () {
          if (installing.state === 'installed' && navigator.serviceWorker.controller) {
            if (window.Swal) {
              Swal.fire({
                icon: 'info',
                title: 'Update Available',
                text: 'A new version is ready. Reload to update?',
                showConfirmButton: true,
                confirmButtonText: 'Reload',
                showCancelButton: true,
                cancelButtonText: 'Later',
                confirmButtonColor: '#1DB954',
              }).then(function (r) { if (r.isConfirmed) window.location.reload(); });
            }
          }
        });
      });
    }).catch(function () {});
  }

  if (document.readyState === 'complete') {
    registerSW();
  } else {
    document.addEventListener('DOMContentLoaded', registerSW);
  }
})();

// ===== PWA: Connection Monitor =====
(function () {
  function showConnectionToast(online) {
    if (!window.Swal) return;
    Swal.fire({
      icon: online ? 'success' : 'warning',
      title: online ? 'Back Online' : 'No Connection',
      text: online ? 'Your internet connection has been restored.' : 'You are currently offline. Some features may be limited.',
      timer: 3000,
      timerProgressBar: true,
      toast: true,
      position: 'top-end',
      showConfirmButton: false
    });
  }

  window.addEventListener('online', function () { showConnectionToast(true); });
  window.addEventListener('offline', function () { showConnectionToast(false); });
})();

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
