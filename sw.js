const CACHE_NAME = "devbase-cache-v1";
const ASSETS_TO_CACHE = [
  "assets/vendor/bootstrap/css/bootstrap.min.css",
  "assets/vendor/bootstrap-icons/font/bootstrap-icons.css",
  "assets/vendor/inter/css/inter.css",
  "assets/vendor/prism/themes/prism-tomorrow.min.css",
  "assets/vendor/quill/quill.snow.css",
  "assets/vendor/bootstrap/js/bootstrap.bundle.min.js",
  "assets/vendor/prism/prism.min.js",
  "assets/vendor/marked/marked.min.js",
  "assets/vendor/quill/quill.js",
  "assets/logoAlt.png",
];

// Install Event
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    }),
  );
  self.skipWaiting();
});

// Activate Event
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        }),
      );
    }),
  );
});

// Fetch Event
self.addEventListener("fetch", (event) => {
  // Strategy: Network First for PHP files and API calls
  if (
    event.request.url.includes(".php") ||
    event.request.url.includes("api/")
  ) {
    event.respondWith(
      fetch(event.request).catch(() => {
        return caches.match(event.request);
      }),
    );
    return;
  }

  // Strategy: Cache First for assets
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    }),
  );
});
