@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
@php $u = auth()->user(); $esAdmin = $u->rol === 'admin'; @endphp
<div class="container pb-5" style="max-width: 1040px;">

  <div class="tarjeta saludo">
    <h2>¡Hola, {{ $u->name }}!</h2>
    <p class="text-muted mb-0">
      @if ($esAdmin)
      Tienes acceso a todas las sucursales.
      @else
      Sucursal {{ $u->sucursal?->nombre }}.
      @endif
    </p>
  </div>

  <div class="row g-3">
    @if ($esAdmin)
    <div class="col-sm-6 col-lg-4"><a class="tile" href="/pedidos"><span class="tile-icon"><i class="fa-solid fa-circle-plus"></i></span><h5>Nuevo pedido</h5><p>Registra pedidos a nombre de cualquier sucursal.</p></a></div>
    <div class="col-sm-6 col-lg-4"><a class="tile" href="/admin"><span class="tile-icon"><i class="fa-solid fa-list-check"></i></span><h5>Pedidos</h5><p>Todas las sucursales, con filtros y estadísticas.</p></a></div>
    <div class="col-sm-6 col-lg-4"><a class="tile" href="/admin/precios"><span class="tile-icon"><i class="fa-solid fa-tags"></i></span><h5>Precios</h5><p>Cambia, agrega o quita precios de pasteles.</p></a></div>
    <div class="col-sm-6 col-lg-4"><a class="tile" href="/admin/reportes"><span class="tile-icon"><i class="fa-solid fa-file-pdf"></i></span><h5>Reportes PDF</h5><p>Producción y ventas por día o por rango.</p></a></div>
    <div class="col-sm-6 col-lg-4"><a class="tile" href="/admin/control"><span class="tile-icon"><i class="fa-solid fa-clipboard-check"></i></span><h5>Capturas del día</h5><p>Quién guardó el control diario de cada tienda.</p></a></div>
    <div class="col-sm-6 col-lg-4"><a class="tile" href="/admin/auditoria"><span class="tile-icon"><i class="fa-solid fa-shield-halved"></i></span><h5>Auditoría</h5><p>Historial de quién hizo qué y cuándo.</p></a></div>
    <div class="col-sm-6 col-lg-4"><a class="tile" href="/productos"><span class="tile-icon"><i class="fa-solid fa-boxes-stacked"></i></span><h5>Productos</h5><p>Catálogo de productos con precios.</p></a></div>
    <div class="col-sm-6 col-lg-4"><a class="tile" href="/control-diario"><span class="tile-icon"><i class="fa-solid fa-calculator"></i></span><h5>Control Diario</h5><p>Inventario, gastos y anticipos por sucursal.</p></a></div>
    @else
    <div class="col-sm-6"><a class="tile" href="/pedidos"><span class="tile-icon"><i class="fa-solid fa-cake-candles"></i></span><h5>Pedidos</h5><p>Registra pedidos de tienda y de clientes, y revisa los de hoy.</p></a></div>
    <div class="col-sm-6"><a class="tile" href="/control-diario"><span class="tile-icon"><i class="fa-solid fa-calculator"></i></span><h5>Control Diario</h5><p>Captura el inventario, los gastos y los anticipos del día.</p></a></div>
    @endif
  </div>
</div>
@endsection
