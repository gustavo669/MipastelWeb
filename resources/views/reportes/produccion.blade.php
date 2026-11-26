<!DOCTYPE html>
<html lang="es">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Reporte de producción - {{ $periodo }}</title>
<style>
  @page { margin: 22pt 20pt; }
  body { font-family: Helvetica, sans-serif; font-size: 7.5pt; color: #000; }
  h1 { font-size: 14pt; text-align: center; margin: 0 0 4pt 0; }
  h2 { font-size: 11pt; margin: 6pt 0 4pt 0; }
  .info { font-size: 9pt; margin: 0 0 2pt 0; }
  .logo { height: 28pt; margin-bottom: 4pt; }
  .salto { page-break-before: always; }
  table { width: 100%; border-collapse: collapse; }
  thead { display: table-header-group; }
  tr { page-break-inside: avoid; }
  th, td { border: 0.5pt solid #777; padding: 1.4pt 2.5pt; text-align: center; word-wrap: break-word; }
  td.izq { text-align: left; }
  table.produccion td.izq { white-space: nowrap; }
  table.produccion th { background: #f8c8dc; font-size: 7pt; padding: 1.6pt 2pt; }
  table.produccion td { font-size: 7pt; padding: 0.9pt 2.5pt; }
  table.clientes th { background: #ffdab9; font-size: 7.5pt; padding: 3pt 2.5pt; }
  table.clientes td { padding: 2.5pt; }
  tr.total td { background: #e0e0e0; font-weight: bold; border: 1pt solid #000; }
  .vacio { color: #666; padding: 8pt; }
</style>
</head>
<body>

@if ($logo)
<img class="logo" src="{{ $logo }}" alt="Mi Pastel">
@endif
<h1>REPORTE DE PRODUCCIÓN</h1>
<div class="info"><b>Período:</b> {{ $periodo }}</div>
@if ($sucursalNombre)
<div class="info"><b>Sucursal:</b> {{ $sucursalNombre }}</div>
@endif

<h2>Producción Pasteles de Tiendas</h2>
<table class="produccion">
  <thead>
    <tr>
      <th style="width:172pt">Producto</th>
      @foreach ($sucursalesAbrev as $abrev)
      <th style="width:34pt">{{ $abrev }}</th>
      @endforeach
      <th style="width:34pt">Total</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($filas as $fila)
    <tr>
      <td class="izq">{{ $fila['producto'] }}</td>
      @foreach ($fila['cantidades'] as $cantidad)
      <td>{{ $cantidad > 0 ? $cantidad : '' }}</td>
      @endforeach
      <td>{{ $fila['total'] > 0 ? $fila['total'] : '' }}</td>
    </tr>
    @endforeach
    <tr class="total">
      <td class="izq">TOTAL GENERAL</td>
      @foreach ($totalPorSucursal as $total)
      <td>{{ $total }}</td>
      @endforeach
      <td>{{ $granTotal }}</td>
    </tr>
  </tbody>
</table>

<div class="salto">
  @if ($logo)
  <img class="logo" src="{{ $logo }}" alt="Mi Pastel">
  @endif
  <h2>Control de Pedidos Clientes</h2>
  <table class="clientes">
    <thead>
      <tr>
        <th style="width:28pt">ID</th>
        <th style="width:28pt">Cant.</th>
        <th style="width:120pt">Descripción</th>
        <th style="width:34pt">Suc.</th>
        <th style="width:52pt">F. Entrega</th>
        <th style="width:140pt">Detalles</th>
        <th style="width:28pt">Foto</th>
        <th style="width:120pt">Dedicatoria</th>
        <th style="width:48pt">Color</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($filasClientes as $c)
      <tr>
        <td>{{ $c['id'] }}</td>
        <td>{{ $c['cantidad'] }}</td>
        <td class="izq">{{ $c['descripcion'] }}</td>
        <td>{{ $c['sucursal'] }}</td>
        <td>{{ $c['fecha_entrega'] }}</td>
        <td class="izq">{{ $c['detalles'] }}</td>
        <td>{{ $c['foto'] }}</td>
        <td class="izq">{{ $c['dedicatoria'] }}</td>
        <td>{{ $c['color'] }}</td>
      </tr>
      @endforeach
      @if (count($filasClientes) === 0)
      <tr><td colspan="9" class="vacio">No hay pedidos de clientes en este período</td></tr>
      @endif
      <tr class="total">
        <td></td>
        <td>{{ $totalClientes }}</td>
        <td class="izq">TOTAL PEDIDOS</td>
        <td colspan="6"></td>
      </tr>
    </tbody>
  </table>
</div>

</body>
</html>
