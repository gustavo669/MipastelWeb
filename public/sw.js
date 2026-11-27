/* Mi Pastel · service worker
 *
 * Solo guarda archivos estáticos (estilos, logo, íconos y las librerías de CDN) para que la app abra rápido.
 * NUNCA guarda páginas ni respuestas JSON: los pedidos y el control diario son datos privados de cada
 * sucursal y deben venir siempre del servidor. Sin internet, las páginas muestran /offline.
 * Para forzar que todos los celulares se actualicen, sube el número de VERSION.
 */
const VERSION = 'mipastel-v3';
const PRECACHE = ['/offline', '/css/mipastel.css', '/Logo.png', '/icons/icon-192.png'];
const CDN = ['cdn.jsdelivr.net', 'cdnjs.cloudflare.com', 'fonts.googleapis.com', 'fonts.gstatic.com'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(VERSION)
      // uno por uno: si algún archivo falla, el service worker se instala igual
      .then((cache) => Promise.all(PRECACHE.map((url) => cache.add(url).catch(() => {}))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((claves) => Promise.all(claves.filter((c) => c !== VERSION).map((c) => caches.delete(c))))
      .then(() => self.clients.claim())
  );
});

function esEstatico(url) {
  if (url.origin === self.location.origin) {
    return url.pathname.startsWith('/css/') || url.pathname.startsWith('/icons/') || url.pathname === '/Logo.png';
  }
  return CDN.includes(url.hostname);
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return; // los POST/PUT/DELETE siempre van directo al servidor

  const url = new URL(req.url);

  // Páginas: siempre de la red; si no hay internet, aviso de "sin conexión"
  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => caches.match('/offline')));
    return;
  }

  // Estáticos: salen del caché al instante y se refrescan en segundo plano
  if (esEstatico(url)) {
    event.respondWith(
      caches.open(VERSION).then((cache) =>
        cache.match(req).then((guardado) => {
          const red = fetch(req)
            .then((resp) => {
              if (resp.ok || resp.type === 'opaque') cache.put(req, resp.clone());
              return resp;
            })
            .catch(() => guardado);
          return guardado || red;
        })
      )
    );
  }
  // Todo lo demás (JSON, fotos de pedidos, PDF): sin intervenir
});
