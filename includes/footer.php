<link rel="manifest" href="../manifest.json">
<script src="../assets/js/sidebar.js"></script>
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
</script>
</body>
</html>
