// service-worker.js

const CACHE_NAME = 'utms-cache-v1';
const ASSETS = [
  'index.php',
  'login.php',
  'dashboard.php',
  'public/css/style.css',
  'public/js/app.js',
  'manifest.json',
  'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js'
];

// Install Service Worker and cache resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[Service Worker] Caching app shell');
        return cache.addAll(ASSETS);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate Service Worker
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Fetch interception and Cache-First fallback to Network
self.addEventListener('fetch', event => {
  // Avoid caching non-GET requests (like form POSTs) and API sessions
  if (event.request.method !== 'GET' || event.request.url.includes('api/')) {
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        if (cachedResponse) {
          // Fetch fresh from network in background to update cache (stale-while-revalidate)
          fetch(event.request).then(networkResponse => {
            if (networkResponse.status === 200) {
              caches.open(CACHE_NAME).then(cache => cache.put(event.request, networkResponse));
            }
          }).catch(err => console.log('Offline: serving from stale cache', err));
          
          return cachedResponse;
        }
        
        return fetch(event.request);
      })
  );
});
