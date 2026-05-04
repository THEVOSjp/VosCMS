/**
 * RezlyX - Front-end Service Worker
 * Progressive Web App support for customer-facing pages
 */

// Auto-detect base path from SW file location
const BASE_PATH = self.location.pathname.replace('/sw.js', '');
const CACHE_NAME = 'voscms-front-v3';
const OFFLINE_URL = BASE_PATH + '/offline.html';

// 동일 출처 자원만 precache — 외부 CDN(tailwind, pretendard)은 CORS 거부되어
// 캐시 실패하므로 제외. 실제 fetch 시점에 브라우저 HTTP 캐시가 처리.
const PRECACHE_ASSETS = [
  BASE_PATH + '/',
  BASE_PATH + '/offline.html'
];

// Install event - cache essential assets
self.addEventListener('install', (event) => {
  console.log('[SW Front] Installing, basePath:', BASE_PATH);

  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW Front] Caching app shell');
        // Use addAll with individual catch to avoid failing on missing assets
        return Promise.allSettled(
          PRECACHE_ASSETS.map(url => cache.add(url).catch(err => {
            console.warn('[SW Front] Failed to cache:', url, err.message);
          }))
        );
      })
      .then(() => {
        console.log('[SW Front] Skip waiting');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('[SW Front] Install failed:', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[SW Front] Activating...');

  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => name.startsWith('rezlyx-front-') && name !== CACHE_NAME)
            .map((name) => {
              console.log('[SW Front] Deleting old cache:', name);
              return caches.delete(name);
            })
        );
      })
      .then(() => {
        console.log('[SW Front] Claiming clients');
        return self.clients.claim();
      })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip admin pages
  if (url.pathname.includes('/admin') || url.pathname.includes('/theadmin')) {
    return;
  }

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip API calls - always fetch from network
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(request)
        .catch(() => {
          return new Response(JSON.stringify({ error: 'Offline' }), {
            headers: { 'Content-Type': 'application/json' }
          });
        })
    );
    return;
  }

  // For navigation requests (HTML pages)
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .catch(() => {
          return caches.match(OFFLINE_URL);
        })
    );
    return;
  }

  // For other assets - cache first, then network
  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          // Return cached version and update cache in background
          event.waitUntil(
            fetch(request)
              .then((response) => {
                if (response && response.status === 200) {
                  const responseClone = response.clone();
                  caches.open(CACHE_NAME)
                    .then((cache) => cache.put(request, responseClone));
                }
              })
              .catch(() => {})
          );
          return cachedResponse;
        }

        // Not in cache, fetch from network
        return fetch(request)
          .then((response) => {
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            const responseToCache = response.clone();
            caches.open(CACHE_NAME)
              .then((cache) => cache.put(request, responseToCache));
            return response;
          })
          .catch(() => {
            if (request.destination === 'image') {
              return new Response('', { status: 404 });
            }
          });
      })
  );
});

// Push notification event
self.addEventListener('push', (event) => {
  console.log('[SW Front] Push received');

  let data = { title: 'RezlyX', body: 'New notification' };

  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const targetUrl = data.link || data.url || BASE_PATH + '/';
  const options = {
    body: data.body,
    icon: data.icon || BASE_PATH + '/storage/pwa/pwa_front_icon.png',
    badge: data.badge || BASE_PATH + '/storage/pwa/pwa_front_icon.png',
    tag: data.tag || undefined,
    vibrate: [100, 50, 100],
    data: {
      url: targetUrl,
      notification_id: data.notification_id || null,
    },
    actions: data.actions || []
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  console.log('[SW Front] Notification clicked');
  event.notification.close();

  const urlToOpen = event.notification.data?.url || BASE_PATH + '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        for (const client of clientList) {
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});
