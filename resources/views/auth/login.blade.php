<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ingresar - Mi Pastel</title>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/css/mipastel.css">
  @include('partials.pwa-head')
</head>
<body>
<div class="login-pagina">
  <div class="login-card">
    <div class="login-logo"><img src="/Logo.png" alt="Mi Pastel"></div>
    <div class="login-subtitulo">Inicia sesión para continuar</div>

    <form method="POST" action="/login">
      @csrf
      <div class="mb-3">
        <label class="form-label" for="email">Correo</label>
        <input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="correo@ejemplo.com" autocomplete="username" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Contraseña</label>
        <input id="password" type="password" name="password" class="form-control" placeholder="Tu contraseña" autocomplete="current-password" required>
      </div>
      @if ($errors->has('email'))
      <div class="alert alert-danger py-2">{{ $errors->first('email') }}</div>
      @endif
      <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Ingresar</button>
    </form>
    <button id="btnInstalar" class="btn btn-outline-primary w-100 mt-3 d-none" type="button">Instalar app en este dispositivo</button>
  </div>
</div>
@include('partials.pwa-script')
</body>
</html>
