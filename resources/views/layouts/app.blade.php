<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('titulo', 'Inicio') - Mi Pastel</title>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="/css/mipastel.css">
</head>
<body>

@php $usuario = auth()->user(); $esAdmin = $usuario && $usuario->rol === 'admin'; @endphp
<nav class="navbar navbar-expand-xl navbar-glass sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand" href="/dashboard">
      <img class="navbar-logo" src="/Logo.png" alt="Mi Pastel">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
      Menú
    </button>
    <div class="collapse navbar-collapse" id="menuPrincipal">
      <ul class="navbar-nav me-auto gap-xl-1 mt-2 mt-xl-0">
        @if ($esAdmin)
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle {{ request()->is('pedidos*') || request()->is('admin') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Operación
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/pedidos"><i class="fa-solid fa-circle-plus me-2"></i>Nuevo pedido</a></li>
            <li><a class="dropdown-item" href="/admin"><i class="fa-solid fa-list-check me-2"></i>Pedidos</a></li>
            <li><a class="dropdown-item" href="/control-diario"><i class="fa-solid fa-calculator me-2"></i>Control diario</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle {{ request()->is('admin/precios*') || request()->is('admin/reportes*') || request()->is('admin/control*') || request()->is('admin/auditoria*') || request()->is('productos*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Administración
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/admin/precios"><i class="fa-solid fa-tags me-2"></i>Precios</a></li>
            <li><a class="dropdown-item" href="/admin/reportes"><i class="fa-solid fa-file-pdf me-2"></i>Reportes</a></li>
            <li><a class="dropdown-item" href="/admin/control"><i class="fa-solid fa-clipboard-check me-2"></i>Capturas del día</a></li>
            <li><a class="dropdown-item" href="/admin/auditoria"><i class="fa-solid fa-shield-halved me-2"></i>Auditoría</a></li>
            <li><a class="dropdown-item" href="/productos"><i class="fa-solid fa-boxes-stacked me-2"></i>Productos</a></li>
          </ul>
        </li>
        @else
        <li class="nav-item"><a class="nav-link {{ request()->is('pedidos*') ? 'active' : '' }}" href="/pedidos"><i class="fa-solid fa-cake-candles"></i>Pedidos</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->is('control-diario*') ? 'active' : '' }}" href="/control-diario"><i class="fa-solid fa-calculator"></i>Control diario</a></li>
        @endif
      </ul>
      <div class="d-flex align-items-center gap-2 mt-2 mt-xl-0">
        <span class="chip {{ $esAdmin ? 'd-inline-flex d-xl-none d-xxl-inline-flex' : '' }}">{{ $esAdmin ? 'Administrador' : ($usuario?->sucursal?->nombre ?? 'Sin sucursal') }}</span>
        <form method="POST" action="/logout" class="m-0">
          @csrf
          <button class="btn btn-sm btn-outline-danger" type="submit">Salir</button>
        </form>
      </div>
    </div>
  </div>
</nav>

<main>
@yield('contenido')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
