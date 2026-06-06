self.addEventListener('install', function() {
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  event.waitUntil(clients.claim());
});

self.addEventListener('push', function(event) {
  if (!event.data) return;

  var data = event.data.json();
  var title = data.title || 'Recurlog';
  var options = {
    body: data.body || '',
    icon: data.icon || '/assets/icons/icon-192.png',
    badge: '/assets/icons/icon-96.png',
    vibrate: [200, 100, 200],
    data: data.data || {},
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();

  var url = event.notification.data && event.notification.data.url
    ? event.notification.data.url
    : '/pages/dashboard.php';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url.indexOf(self.location.origin) === 0 && 'focus' in client) {
          return client.focus().then(function(focused) {
            focused.navigate(url);
          });
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});
