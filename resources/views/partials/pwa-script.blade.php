<script>
(function () {
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function () {});
    });
  }

  var boton = document.getElementById('btnInstalar');
  if (!boton) return;

  var yaInstalada = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  if (yaInstalada) return;

  var evento = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    evento = e;
    boton.classList.remove('d-none');
  });
  window.addEventListener('appinstalled', function () { boton.classList.add('d-none'); });

  // iPhone/iPad no avisan: se instala desde el menú Compartir de Safari
  var esIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
  if (esIOS) boton.classList.remove('d-none');

  boton.addEventListener('click', function () {
    if (evento) {
      evento.prompt();
      evento.userChoice.finally(function () { evento = null; boton.classList.add('d-none'); });
    } else if (esIOS) {
      alert('Para instalar: toca el botón Compartir de Safari y elige "Agregar a pantalla de inicio".');
    }
  });
})();
</script>
