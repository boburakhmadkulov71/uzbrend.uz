const CACHE_NAME = 'zavod-v1';
const ASSETS_TO_CACHE = [
  '/',
  // HTML Sahifalar
  '/index.html',
  '/beton.html',
  '/gisht.html',
  '/metan.html',
  '/xitoy.html',

  // JPG Rasmlar
  '/beton.jpg',
  '/gazoblok.jpg',
  '/kolodes.jpg',
  '/kran.jpg',
  '/metan.jpg',
  '/og-image.jpg',
  '/plita.jpg',
  '/robot.jpg',
  '/stolba.jpg',

  // PNG Rasmlar
  '/60x60.png',
  '/brick-bg.png',
  '/logo.png',
  '/lotok.png',
  '/mini-kara.png',
  '/mishalka.png',
  '/mixer.png',
  '/paltara.png',
  '/photo.png',
  '/press.png',
  '/standart.png',
  '/store_icon.png',

  // Boshqa formatlar va tayanch fayllar
  '/kara.avif',
  '/drobilka.svg',
  '/manifest.json'
];

// O'rnatish: fayllarni keshga saqlash
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// Faollashtirish
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// So'rovlarni tutib olish: Internet bo'lmasa keshdan berish
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});
