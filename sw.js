var CACHE_NAMES = {
  static: 'static-v1',
  fonts: 'fonts-v1',
  api: 'api-v1',
  dynamic: 'dynamic-v1',
  images: 'images-v1',
};

var STATIC_ASSETS = [
  '/offline.html',
  '/assets/css/custom.css',
  '/assets/js/sidebar.js',
  '/assets/js/app.js',
];

var ICON_ASSETS = [
  '/assets/icons/icon-48.png',
  '/assets/icons/icon-72.png',
  '/assets/icons/icon-96.png',
  '/assets/icons/icon-128.png',
  '/assets/icons/icon-144.png',
  '/assets/icons/icon-152.png',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-192-maskable.png',
  '/assets/icons/icon-384.png',
  '/assets/icons/icon-512.png',
  '/assets/icons/icon-512-maskable.png',
  '/assets/icons/apple-icon-152x152.png',
  '/assets/icons/apple-icon-180x180.png',
  '/assets/icons/apple-icon.png',
  '/assets/icons/apple-touch-icon.png',
  '/favicon.ico',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAMES.static).then(function (cache) {
      return cache.addAll(STATIC_ASSETS.concat(ICON_ASSETS));
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys
          .filter(function (key) {
            return !Object.values(CACHE_NAMES).includes(key);
          })
          .map(function (key) {
            return caches.delete(key);
          })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

function isNavigationRequest(request) {
  return request.mode === 'navigate';
}

function isApiRequest(url) {
  return url.pathname.startsWith('/api/');
}

function isAuthRequest(url) {
  return url.pathname.startsWith('/api/auth') || url.pathname.startsWith('/api/login');
}

function isStaticAsset(url) {
  return STATIC_ASSETS.some(function (a) { return url.pathname === a; }) ||
    ICON_ASSETS.some(function (a) { return url.pathname === a; }) ||
    url.pathname === '/manifest.json' ||
    url.pathname === '/sw.js';
}

function isFontRequest(url) {
  return url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';
}

function isCdnScript(url) {
  return url.hostname === 'cdn.tailwindcss.com' ||
    url.hostname === 'unpkg.com' ||
    url.hostname === 'cdn.jsdelivr.net' ||
    url.hostname === 'cdnjs.cloudflare.com';
}

function isImageRequest(url) {
  return /\.(png|jpg|jpeg|gif|svg|webp|ico)$/i.test(url.pathname);
}

function isMutationMethod(request) {
  return request.method !== 'GET';
}

function networkFirst(request, cacheName, fallbackUrl) {
  return fetch(request)
    .then(function (response) {
      if (response && response.status === 200) {
        var r = response.clone();
        caches.open(cacheName).then(function (cache) {
          cache.put(request, r);
        });
      }
      return response;
    })
    .catch(function () {
      return caches.match(request).then(function (cached) {
        if (cached) return cached;
        if (fallbackUrl) return caches.match(fallbackUrl);
        return new Response(
          JSON.stringify({ ok: false, error: 'You are offline' }),
          { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
      });
    });
}

function cacheFirst(request, cacheName) {
  return caches.match(request).then(function (cached) {
    if (cached) return cached;
    return fetch(request).then(function (response) {
      if (response && response.status === 200) {
        var r = response.clone();
        caches.open(cacheName).then(function (cache) {
          cache.put(request, r);
        });
      }
      return response;
    });
  });
}

function staleWhileRevalidate(request, cacheName) {
  var cachePromise = caches.open(cacheName);
  return cachePromise.then(function (cache) {
    return cache.match(request).then(function (cached) {
      var fetchPromise = fetch(request).then(function (response) {
        if (response && response.status === 200) {
          cache.put(request, response.clone());
        }
        return response;
      }).catch(function () {
        return cached;
      });
      return cached || fetchPromise;
    });
  });
}

self.addEventListener('fetch', function (event) {
  var url = new URL(event.request.url);

  // Network Only for non-GET requests
  if (isMutationMethod(event.request)) {
    return;
  }

  // Network Only for auth endpoints
  if (isAuthRequest(url)) {
    return;
  }

  // Cache First for static assets and icons
  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(event.request, CACHE_NAMES.static));
    return;
  }

  // Cache First for Google Fonts
  if (isFontRequest(url)) {
    event.respondWith(cacheFirst(event.request, CACHE_NAMES.fonts));
    return;
  }

  // Stale While Revalidate for CDN scripts
  if (isCdnScript(url)) {
    event.respondWith(staleWhileRevalidate(event.request, CACHE_NAMES.dynamic));
    return;
  }

  // Stale While Revalidate for images
  if (isImageRequest(url)) {
    event.respondWith(staleWhileRevalidate(event.request, CACHE_NAMES.images));
    return;
  }

  // Network First for API GET requests
  if (isApiRequest(url)) {
    event.respondWith(networkFirst(event.request, CACHE_NAMES.api));
    return;
  }

  // Network First with offline fallback for navigations
  if (isNavigationRequest(event.request)) {
    event.respondWith(networkFirst(event.request, CACHE_NAMES.static, '/offline.html'));
    return;
  }

  // Stale While Revalidate for all other GET requests
  event.respondWith(staleWhileRevalidate(event.request, CACHE_NAMES.dynamic));
});

// Push notification handling (from service-worker.js)
self.addEventListener('push', function (event) {
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

self.addEventListener('notificationclick', function (event) {
  event.notification.close();

  var url = event.notification.data && event.notification.data.url
    ? event.notification.data.url
    : '/pages/dashboard.php';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url.indexOf(self.location.origin) === 0 && 'focus' in client) {
          return client.focus().then(function (focused) {
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
