const CACHE_NAME = 'recurlog-v1';
const ASSETS = [
  '/',
  '/pages/login.php',
  '/assets/css/custom.css',
  '/assets/js/app.js',
  '/assets/icons/android-icon-192x192.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
