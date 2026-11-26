<!DOCTYPE html>
<html lang="es">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Reporte de ventas - {{ $periodo }}</title>
<style>
  @page { margin: 30pt; }
  body { font-family: Helvetica, sans-serif; font-size: 9pt; color: #000; }
  h1 { font-size: 16pt; text-align: center; margin: 0 0 4pt 0; }
  h2 { font-size: 12pt; margin: 14pt 0 6pt 0; }
  .info { font-size: 10pt; margin: 0 0 2pt 0; }
  .logo { height: 30pt; margin-bottom: 4pt; }
  table { width: 100%; border-collapse: collapse; }
  thead { display: table-header-group; }
  tr { page-break-inside: avoid; }
  th, td { border: 0.5pt solid #777; padding: 2.5pt 4pt; text-align: center; word-wrap: break-word; }
  td.izq { text-align: left; }
  td.der, th.der { text-align: right; }
  table.tienda th { background: #c8e6c9; }
  table.clientes th { background: #fff9c4; }
  table.tienda tr.total td { background: #a5d6a7; font-weight: bold; border: 1pt solid #000; }
  table.clientes tr.total td { background: #fff59d; font-weight: bold; border: 1pt solid #000; }
  .vacio { color: #666; padding: 8pt; }
  .resumen { margin-top: 18pt; font-size: 10.5pt; line-height: 1.5; }
  .resumen .general { font-size: 14pt; font-weight: bold; margin-top: 8pt; }
</style>
</head>
<body>

@if ($logo)
<img class="logo" src="{{ $logo }}" alt="Mi Pastel">
@endif
<h1>REPORTE DE VENTAS</h1>
<div class="info"><b>Período:</b> {{ $periodo }}</div>
@if ($sucursalNombre)
<div class="info"><b>Sucursal:</b> {{ $sucursalNombre }}</div>
@endif

<h2>Ventas - Pasteles de Tienda</h2>
<table class="tienda">
  <thead>
    <tr>
      <th style="width:46pt">Sucursal</th>
      <th style="width:190pt">Producto</th>
      <th style="width:36pt">Cant.</th>
      <th class="der" style="width:66pt">Precio Unit.</th>
      <th class="der" style="width:76pt">Subtotal</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($filasTienda as $f)
    <tr>
      <td>{{ $f['sucursal'] }}</td>
      <td class="izq">{{ $f['producto'] }}</td>
      <td>{{ $f['cantidad'] }}</td>
      <td class="der">Q {{ number_format($f['precio'], 2) }}</td>
      <td class="der">Q {{ number_format($f['subtotal'], 2) }}</td>
    </tr>
    @endforeach
    @if (count($filasTienda) === 0)
    <tr><td colspan="5" class="vacio">No hay pedidos de tienda en este período</td></tr>
    @endif
    <tr class="total">
      <td></td>
      <td class="izq">TOTAL</td>
      <td></td>
      <td></td>
      <td class="der">Q {{ number_format($totalTienda, 2) }}</td>
    </tr>
  </tbody>
</table>

<h2>Ventas - Pedidos de Clientes</h2>
<table class="clientes">
  <thead>
    <tr>
      <th style="width:34pt">ID</th>
      <th style="width:42pt">Suc.</th>
      <th style="width:170pt">Producto</th>
      <th style="width:36pt">Cant.</th>
      <th class="der" style="width:66pt">Precio</th>
      <th class="der" style="width:76pt">Total</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($filasClientes as $f)
    <tr>
      <td>{{ $f['id'] }}</td>
      <td>{{ $f['sucursal'] }}</td>
      <td class="izq">{{ $f['producto'] }}</td>
      <td>{{ $f['cantidad'] }}</td>
      <td class="der">Q {{ number_format($f['precio'], 2) }}</td>
      <td class="der">Q {{ number_format($f['total'], 2) }}</td>
    </tr>
    @endforeach
    @if (count($filasClientes) === 0)
    <tr><td colspan="6" class="vacio">No hay pedidos de clientes en este período</td></tr>
    @endif
    <tr class="total">
      <td></td>
      <td></td>
      <td class="izq">TOTAL</td>
      <td></td>
      <td></td>
      <td class="der">Q {{ number_format($totalClientes, 2) }}</td>
    </tr>
  </tbody>
</table>

<div class="resumen">
  <b>RESUMEN DE VENTAS</b><br>
  Total Pasteles de Tienda: Q {{ number_format($totalTienda, 2) }}<br>
  Total Pedidos de Clientes: Q {{ number_format($totalClientes, 2) }}<br>
  <div class="general">TOTAL GENERAL: Q {{ number_format($totalGeneral, 2) }}</div>
</div>

</body>
</html>
