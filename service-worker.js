const CACHE_NAME = 'exam-generator-cache-v2';
const STATIC_ASSETS = [
    'offline.html',
    'dashboard.php',
    'create_exam_offline.php',
    'profile_offline.php',
    'my_subjects_offline.php',
    'view_exams_offline.php',
];

self.addEventListener('install', (event) => {
  console.log('[ServiceWorker] Install');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return cache.addAll(STATIC_ASSETS);
      })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  console.log('[ServiceWorker] Activate');
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[ServiceWorker] Deleting old cache:', cache);
            return caches.delete(cache);
          }
        })
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

     // عند الطلب على dashboard.php
  if (url.pathname.endsWith('dashboard.php')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // تحديث الكاش بأحدث نسخة
          const clonedResponse = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(request, clonedResponse);
          });
          return response;
        })
        .catch(() => {
          // عند انقطاع الاتصال: عرض آخر نسخة محفوظة في الكاش
          return caches.match(request).then((cachedResponse) => {
            return cachedResponse || caches.match('offline.html');
          });
        })
    );
    return;
  }
  if (url.pathname.endsWith('my_subjects.php')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          return response;
        })
        .catch(() => {
          // عند انقطاع الاتصال: عرض آخر نسخة محفوظة في الكاش 
          return caches.match(request).then((cachedResponse) => {
            return caches.match('my_subjects_offline.php');
          });
        })
    );
    return;
  }
  if (url.pathname.endsWith('profile.php')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          return response;
        })
        .catch(() => {
          // عند انقطاع الاتصال: عرض آخر نسخة محفوظة في الكاش  
          return caches.match(request).then((cachedResponse) => {
            return caches.match('profile_offline.php');
          });
        })
    );
    return;
  }
  if (url.pathname.endsWith('create_exam.php')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          return response;
        })
        .catch(() => {
          // عند انقطاع الاتصال: عرض آخر نسخة محفوظة في الكاش 
          return caches.match(request).then((cachedResponse) => {
            return caches.match('create_exam_offline.php');
          });
        })
    );
    return;
  }
  if (url.pathname.endsWith('view_exams.php')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          return response;
        })
        .catch(() => {
          // عند انقطاع الاتصال: عرض آخر نسخة محفوظة في الكاش 
          return caches.match(request).then((cachedResponse) => {
            return caches.match('view_exams_offline.php');
          });
        })
    );
    return;
  }

    event.respondWith(
        fetch(request)
            .then((response) => {
                return response;
            })
            .catch(() => {
                // عند انقطاع الاتصال
                // إذا كان الطلب لملف PHP أو API أظهر offline.html
                if (url.pathname.endsWith('.php') || url.pathname.startsWith('/api/')) {
                    return caches.match('offline.html');
                }

                // إذا كان الطلب لملف HTML
                if (request.headers.get('accept').includes('text/html')) {
                    return caches.match('offline.html');
                }

                // في باقي الحالات، حاول إرجاع الملف من الكاش إن وجد
                return caches.match(request);
            })
    );
});
