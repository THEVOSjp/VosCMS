/**
 * RezlyX Admin - Service Worker
 * Progressive Web App support for admin panel
 */

// Auto-detect base path from SW file location
const BASE_PATH = self.location.pathname.replace('/admin-sw.js', '');
const CACHE_NAME = 'rezlyx-admin-v2';
const OFFLINE_URL = BASE_PATH + '/admin/offline.html';

// Admin assets to cache
const PRECACHE_ASSETS = [
  BASE_PATH + '/admin/offline.html',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css'
];

// Install event
self.addEventListener('install', (event) => {
  console.log('[SW Admin] Installing, basePath:', BASE_PATH);

  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW Admin] Caching admin assets');
        return Promise.allSettled(
          PRECACHE_ASSETS.map(url => cache.add(url).catch(err => {
            console.warn('[SW Admin] Failed to cache:', url, err.message);
          }))
        );
      })
      .then(() => self.skipWaiting())
      .catch((error) => {
        console.error('[SW Admin] Install failed:', error);
      })
  );
});

// Activate event
self.addEventListener('activate', (event) => {
  console.log('[SW Admin] Activating...');

  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => name.startsWith('rezlyx-admin-') && name !== CACHE_NAME)
            .map((name) => {
              console.log('[SW Admin] Deleting old cache:', name);
              return caches.delete(name);
            })
        );
      })
      .then(() => self.clients.claim())
  );
});

// Fetch event - Network first for admin (always need fresh data)
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Only handle admin routes
  if (!url.pathname.startsWith(BASE_PATH + '/admin') && !url.pathname.startsWith(BASE_PATH + '/theadmin')) {
    return;
  }

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // API calls - network only with offline fallback
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(request)
        .catch(() => {
          return new Response(JSON.stringify({
            success: false,
            error: 'You are offline. Please check your connection.'
          }), {
            headers: { 'Content-Type': 'application/json' }
          });
        })
    );
    return;
  }

  // Navigation requests - network first
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME)
              .then((cache) => cache.put(request, responseClone));
          }
          return response;
        })
        .catch(() => {
          return caches.match(request)
            .then((cachedResponse) => {
              return cachedResponse || caches.match(OFFLINE_URL);
            });
        })
    );
    return;
  }

  // Static assets - cache first with network fallback
  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        const networkFetch = fetch(request)
          .then((response) => {
            if (response && response.status === 200) {
              const responseClone = response.clone();
              caches.open(CACHE_NAME)
                .then((cache) => cache.put(request, responseClone));
            }
            return response;
          })
          .catch(() => cachedResponse);

        return cachedResponse || networkFetch;
      })
  );
});

// Push notification for admin
self.addEventListener('push', (event) => {
  console.log('[SW Admin] Push received');

  let data = {
    title: 'RezlyX Admin',
    body: 'New notification'
  };

  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body,
    icon: data.icon || BASE_PATH + '/storage/pwa/pwa_admin_icon.png',
    vibrate: [200, 100, 200],
    tag: 'admin-notification',
    renotify: true,
    data: {
      url: data.url || BASE_PATH + '/admin'
    },
    actions: [
      { action: 'view', title: 'View' },
      { action: 'dismiss', title: 'Dismiss' }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Notification click
self.addEventListener('notificationclick', (event) => {
  console.log('[SW Admin] Notification clicked:', event.action);
  event.notification.close();

  if (event.action === 'dismiss') {
    return;
  }

  const urlToOpen = event.notification.data?.url || BASE_PATH + '/admin';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        for (const client of clientList) {
          if (client.url.includes('/admin') && 'focus' in client) {
            return client.focus().then((focusedClient) => {
              if ('navigate' in focusedClient) {
                return focusedClient.navigate(urlToOpen);
              }
            });
          }
        }
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});
