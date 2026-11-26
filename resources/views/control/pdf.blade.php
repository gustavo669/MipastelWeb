<!DOCTYPE html>
<html lang="es">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Control diario - {{ $sucursal->nombre }} - {{ $fechaTexto }}</title>
<style>
  @page { margin: 22pt 24pt; }
  body { font-family: Helvetica, sans-serif; font-size: 7.5pt; color: #000; }
  .logo { height: 28pt; margin-bottom: 4pt; }
  .salto { page-break-after: always; }
  .titulo { font-size: 11pt; margin: 0 0 5pt 0; }
  .sin-captura { color: #b00020; font-weight: bold; font-size: 8pt; }
  table { border-collapse: collapse; }
  table.productos { width: 100%; }
  table.productos th, table.productos td { border: 0.5pt solid #444; padding: 1.5pt 2pt; text-align: center; }
  table.productos th { background: #f8c8dc; font-size: 6.5pt; }
  table.productos td.nombre { text-align: left; white-space: nowrap; }
  table.productos td.dinero { text-align: right; white-space: nowrap; }
  tr.total td { background: #e0e0e0; font-weight: bold; }
  .neg { color: #b00020; font-weight: bold; }
  table.cajas { width: 100%; margin-top: 10pt; }
  table.cajas > tbody > tr > td { vertical-align: top; padding-right: 8pt; }
  table.caja { width: 100%; margin-bottom: 8pt; }
  table.caja th, table.caja td { border: 0.5pt solid #444; padding: 2pt 3pt; font-size: 7.5pt; }
  table.caja th { background: #f0f0f0; text-align: center; }
  table.caja td.dinero { text-align: right; }
  table.caja tr.fuerte td { background: #fff59d; font-weight: bold; }
  table.caja tr.subtotal td { background: #e0e0e0; font-weight: bold; }
</style>
</head>
<body>

@foreach ($secciones as $i => $seccion)
<div class="{{ $i === 0 ? 'salto' : '' }}">
  @if ($logo)
  <img class="logo" src="{{ $logo }}" alt="Mi Pastel">
  @endif
  <div class="titulo"><b>TIENDA {{ mb_strtoupper($sucursal->nombre) }}</b> &nbsp;&nbsp;&nbsp; FECHA: {{ $fechaTexto }}</div>
  @if (! $capturado)
  <div class="sin-captura">Sin captura para esta fecha</div>
  @endif

  <table class="productos">
    <thead>
      <tr>
        <th style="width:14pt">No</th>
        <th style="width:150pt">{{ $seccion['titulo'] }}</th>
        <th style="width:30pt">Inv.<br>inicial</th>
        <th style="width:30pt">Envío</th>
        <th style="width:30pt">Producto<br>disponible</th>
        <th style="width:30pt">Pasteles<br>porción</th>
        <th style="width:30pt">Gastos<br>familia</th>
        <th style="width:30pt">Mal<br>estado</th>
        <th style="width:30pt">Inv.<br>final</th>
        <th style="width:30pt">Producto<br>vendido</th>
        <th style="width:36pt">Valor del<br>producto</th>
        <th style="width:50pt">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($seccion['filas'] as $f)
      <tr>
        <td>{{ $f['numero'] }}</td>
        <td class="nombre">{{ $f['nombre'] }}</td>
        <td>{{ $f['inv_inicial'] }}</td>
        <td>{{ $f['envio'] }}</td>
        <td>{{ $f['disponible'] }}</td>
        <td>{{ $f['pasteles_porcion'] }}</td>
        <td>{{ $f['gastos_familia'] }}</td>
        <td>{{ $f['mal_estado'] }}</td>
        <td>{{ $f['inv_final'] }}</td>
        <td class="{{ $f['vendido'] < 0 ? 'neg' : '' }}">{{ $f['vendido'] }}</td>
        <td class="dinero">Q {{ number_format($f['precio'], 2) }}</td>
        <td class="dinero {{ $f['importe'] < 0 ? 'neg' : '' }}">Q {{ number_format($f['importe'], 2) }}</td>
      </tr>
      @endforeach
      <tr class="total">
        <td colspan="10" style="text-align:right">{{ $seccion['etiqueta_total'] }}</td>
        <td colspan="2" class="dinero">Q {{ number_format($seccion['total'], 2) }}</td>
      </tr>
    </tbody>
  </table>

  @if ($i === count($secciones) - 1)
  <table class="cajas">
    <tbody>
    <tr>
      <td style="width:30%">
        <table class="caja">
          <tr><th colspan="2">ANTICIPOS DE PASTELES</th></tr>
          <tr><th>No. Orden</th><th>Cantidad</th></tr>
          @for ($n = 0; $n < 8; $n++)
          @php $a = $anticipos[$n] ?? null; @endphp
          <tr><td>{{ $a['no_orden'] ?? '' }}&nbsp;</td><td class="dinero">{{ $a ? 'Q '.number_format($a['monto'], 2) : '' }}&nbsp;</td></tr>
          @endfor
          <tr class="subtotal"><td>TOTAL</td><td class="dinero">Q {{ number_format($totalAnticipos, 2) }}</td></tr>
        </table>
      </td>
      <td style="width:35%">
        <table class="caja">
          <tr><th colspan="2">GASTOS</th></tr>
          @foreach ($lineasGasto as [$etiqueta, $monto])
          <tr><td>{{ $etiqueta }}</td><td class="dinero">Q {{ number_format($monto, 2) }}</td></tr>
          @endforeach
          <tr class="subtotal"><td>TOTAL GASTOS</td><td class="dinero">Q {{ number_format($totalGastos, 2) }}</td></tr>
        </table>
        <table class="caja">
          <tr><td>OTROS INGRESOS</td><td class="dinero">Q {{ number_format($otrosIngresos, 2) }}</td></tr>
        </table>
      </td>
      <td style="width:35%">
        <table class="caja">
          @foreach ($resumen as [$etiqueta, $monto, $fuerte])
          <tr class="{{ $fuerte ? 'fuerte' : '' }}"><td>{{ $etiqueta }}</td><td class="dinero">Q {{ number_format($monto, 2) }}</td></tr>
          @endforeach
        </table>
        <table class="caja">
          <tr><td>CAJA CHICA</td><td class="dinero">Q {{ number_format($cajaChica, 2) }}</td></tr>
        </table>
      </td>
    </tr>
    </tbody>
  </table>
  @endif
</div>
@endforeach

</body>
</html>
