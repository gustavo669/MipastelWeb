<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sin conexión - Mi Pastel</title>
  <meta name="theme-color" content="#7625a6">
  <style>
    body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
           background: linear-gradient(135deg, #fff7fa, #fffaf8); color: #2b1b3a;
           font-family: 'Segoe UI', 'Open Sans', Lato, sans-serif; text-align: center; padding: 1.5rem; }
    .caja { max-width: 360px; }
    img { width: 220px; max-width: 70%; margin-bottom: 1rem; }
    h1 { font-size: 1.4rem; margin: 0 0 .5rem; color: #4e176f; }
    p { color: #6c5a7a; margin: 0 0 1.25rem; line-height: 1.5; }
    button { background: linear-gradient(135deg, #873bb0, #5b1d82); color: #fff; border: 0; border-radius: 999px;
             padding: .7rem 1.6rem; font-size: 1rem; cursor: pointer; }
  </style>
</head>
<body>
  <div class="caja">
    <img src="/Logo.png" alt="Mi Pastel">
    <h1>Sin conexión a internet</h1>
    <p>No se pudo conectar con el sistema. Revisa el internet del celular o de la tienda e intenta de nuevo. Lo que ya habías guardado está seguro.</p>
    <button type="button" onclick="location.reload()">Reintentar</button>
  </div>
</body>
</html>
