var CACHE_NAMES = {
  static: 'static-v1',
  fonts: 'fonts-v1',
  api: 'api-v1',
  dynamic: 'dynamic-v1',
  images: 'images-v1',
};

var BASE = self.location.pathname.replace(/\/sw\.js$/, '');

var PRECACHE_ASSETS = [
  BASE + '/offline.html',
  BASE + '/assets/css/custom.css',
  BASE + '/assets/js/sidebar.js',
  BASE + '/assets/js/app.js',
  BASE + '/assets/icons/icon-48.png',
  BASE + '/assets/icons/icon-72.png',
  BASE + '/assets/icons/icon-96.png',
  BASE + '/assets/icons/icon-128.png',
  BASE + '/assets/icons/icon-144.png',
  BASE + '/assets/icons/icon-152.png',
  BASE + '/assets/icons/icon-192.png',
  BASE + '/assets/icons/icon-192-maskable.png',
  BASE + '/assets/icons/icon-384.png',
  BASE + '/assets/icons/icon-512.png',
  BASE + '/assets/icons/icon-512-maskable.png',
  BASE + '/assets/icons/apple-icon-152x152.png',
  BASE + '/assets/icons/apple-icon-180x180.png',
  BASE + '/assets/icons/apple-icon.png',
  BASE + '/assets/icons/apple-touch-icon.png',
  BASE + '/favicon.ico',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAMES.static).then(function (cache) {
      return cache.addAll(PRECACHE_ASSETS);
    }).catch(function () {
      // Precache is best-effort
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

// ----- Helpers -----

function isStaticAsset(url) {
  return url.pathname === BASE + '/manifest.json' ||
    url.pathname === BASE + '/sw.js' ||
    PRECACHE_ASSETS.indexOf(url.pathname) !== -1;
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

function isApiRequest(url) {
  return url.pathname.indexOf(BASE + '/api/') !== -1;
}

function isAuthApi(url) {
  return url.pathname.indexOf(BASE + '/api/auth') !== -1 ||
    url.pathname.indexOf(BASE + '/api/login') !== -1;
}

function isPhpPage(url) {
  return url.pathname.match(/\.php$/);
}

function isNavigationRequest(request) {
  return request.mode === 'navigate';
}

function isMutationMethod(request) {
  return request.method !== 'GET';
}

// ----- Strategies -----

function fromCacheOrFallback(request, fallbackUrl) {
  return caches.match(request).then(function (cached) {
    if (cached) return cached;
    if (fallbackUrl) return caches.match(fallbackUrl);
    return null;
  });
}

function networkFirst(request, cacheName, fallbackUrl) {
  return fetch(request).then(function (response) {
    if (response && response.status === 200) {
      var r = response.clone();
      caches.open(cacheName).then(function (cache) { cache.put(request, r); });
    }
    return response;
  }).catch(function () {
    return fromCacheOrFallback(request, fallbackUrl).then(function (res) {
      if (res) return res;
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
        caches.open(cacheName).then(function (cache) { cache.put(request, r); });
      }
      return response;
    });
  });
}

function staleWhileRevalidate(request, cacheName) {
  return caches.open(cacheName).then(function (cache) {
    return cache.match(request).then(function (cached) {
      var fetchPromise = fetch(request).then(function (response) {
        if (response && response.status === 200) {
          cache.put(request, response.clone());
        }
        return response;
      }).catch(function () {
        return cached || new Response('', { status: 503 });
      });
      return cached || fetchPromise;
    });
  });
}

// ----- Fetch Handler -----

self.addEventListener('fetch', function (event) {
  var url = new URL(event.request.url);

  // Only handle same-origin requests
  if (url.origin !== self.location.origin) {
    // Cross-origin: just cache fonts and CDN
    if (isFontRequest(url)) {
      event.respondWith(cacheFirst(event.request, CACHE_NAMES.fonts));
    } else if (isCdnScript(url)) {
      event.respondWith(staleWhileRevalidate(event.request, CACHE_NAMES.dynamic));
    }
    return;
  }

  // Network Only for non-GET
  if (isMutationMethod(event.request)) return;

  // Network Only for auth APIs
  if (isAuthApi(url)) return;

  // Do NOT intercept PHP page navigations (dynamic content)
  if (isNavigationRequest(event.request) && isPhpPage(url)) return;

  // Cache First for known static assets
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

  // Offline fallback for non-PHP navigation requests
  if (isNavigationRequest(event.request)) {
    event.respondWith(networkFirst(event.request, CACHE_NAMES.static, BASE + '/offline.html'));
    return;
  }
});

// ----- Push Notifications -----

self.addEventListener('push', function (event) {
  if (!event.data) return;
  var data = event.data.json();
  var title = data.title || 'Recurlog';
  var options = {
    body: data.body || '',
    icon: data.icon || BASE + '/assets/icons/icon-192.png',
    badge: BASE + '/assets/icons/icon-96.png',
    vibrate: [200, 100, 200],
    data: data.data || {},
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = event.notification.data && event.notification.data.url
    ? event.notification.data.url
    : BASE + '/pages/dashboard.php';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var c = clientList[i];
        if (c.url.indexOf(self.location.origin) === 0 && 'focus' in c) {
          return c.focus().then(function (f) { f.navigate(url); });
        }
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
