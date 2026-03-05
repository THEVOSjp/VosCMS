/**
 * RezlyX Admin - Service Worker
 * Progressive Web App support for admin panel
 */

const CACHE_NAME = 'rezlyx-admin-v1';
const OFFLINE_URL = '/admin/offline.html';

// Admin assets to cache
const PRECACHE_ASSETS = [
  '/admin/offline.html',
  '/assets/css/admin.css',
  '/assets/icons/admin-icon-192x192.png',
  '/assets/icons/admin-icon-512x512.png',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css'
];

// Install event
self.addEventListener('install', (event) => {
  console.log('[SW Admin] Installing service worker...');

  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW Admin] Caching admin assets');
        return cache.addAll(PRECACHE_ASSETS);
      })
      .then(() => self.skipWaiting())
      .catch((error) => {
        console.error('[SW Admin] Cache failed:', error);
      })
  );
});

// Activate event
self.addEventListener('activate', (event) => {
  console.log('[SW Admin] Activating service worker...');

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
  if (!url.pathname.startsWith('/admin')) {
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
          // Cache successful responses
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME)
              .then((cache) => cache.put(request, responseClone));
          }
          return response;
        })
        .catch(() => {
          // Try cache first, then offline page
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
    body: 'New notification',
    icon: '/assets/icons/admin-icon-192x192.png'
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
    icon: data.icon || '/assets/icons/admin-icon-192x192.png',
    badge: '/assets/icons/admin-icon-72x72.png',
    vibrate: [200, 100, 200],
    tag: 'admin-notification',
    renotify: true,
    data: {
      url: data.url || '/admin/dashboard'
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

  const urlToOpen = event.notification.data?.url || '/admin/dashboard';

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

// Periodic background sync for dashboard stats (if supported)
self.addEventListener('periodicsync', (event) => {
  console.log('[SW Admin] Periodic sync:', event.tag);

  if (event.tag === 'update-dashboard') {
    event.waitUntil(updateDashboardCache());
  }
});

async function updateDashboardCache() {
  try {
    const response = await fetch('/admin/api/dashboard-stats');
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      await cache.put('/admin/api/dashboard-stats', response);
    }
  } catch (error) {
    console.error('[SW Admin] Dashboard update failed:', error);
  }
}
