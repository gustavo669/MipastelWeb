@extends('layouts.app')

@section('titulo', 'Control diario')

@section('contenido')
@php $esAdmin = auth()->user()->rol === 'admin'; @endphp
@php $camposGasto = ['gastos_compras' => 'Gastos (compras)', 'desc_degustacion' => 'Desc. degustación', 'servilletas' => 'Servilletas', 'tenedores' => 'Tenedores', 'platos' => 'Platos', 'donaciones' => 'Donaciones', 'consumo_empleado' => 'Consumo empleado', 'otros_gastos' => 'Otros gastos']; @endphp
@php $camposIngreso = ['otros_ingresos' => 'Otros ingresos', 'caja_chica_reportada' => 'Caja chica']; @endphp
<div class="container pb-5" style="max-width: 1000px;">

  <div class="tarjeta">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h4 class="mb-1">Control diario {{ $sucursal->nombre }}</h4>
        @if ($ultimaCaptura)
        <div class="text-muted small">Última captura de esta fecha: <b>{{ $ultimaCaptura['usuario'] }}</b> el {{ $ultimaCaptura['cuando'] }}</div>
        @endif
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="/control-diario/pdf?fecha={{ $fecha }}&amp;sucursal_id={{ $sucursal->id }}">PDF</a>
        @if ($esAdmin)
        <a class="btn btn-sm btn-outline-secondary" href="/control-diario/pdf-todas?fecha={{ $fecha }}">ZIP de todas</a>
        @endif
      </div>
    </div>
  </div>

  @if (session('ok'))
  <div class="alert alert-success">{{ session('ok') }}</div>
  @endif
  @if ($errors->any())
  <div class="alert alert-danger">Revisa los datos: {{ $errors->first() }}</div>
  @endif

  {{-- Selector de fecha (y de sucursal solo para el admin) --}}
  <form method="GET" action="/control-diario" class="tarjeta">
    <div class="row g-2 align-items-end">
      @if ($esAdmin)
      <div class="col-12 col-md-5">
        <label class="form-label mb-1" for="selSucursal">Sucursal</label>
        <select class="form-select" id="selSucursal" name="sucursal_id">
          @foreach ($sucursales as $s)
          <option value="{{ $s->id }}" {{ $s->id == $sucursal->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
          @endforeach
        </select>
      </div>
      @endif
      <div class="col-8 col-md-4">
        <label class="form-label mb-1" for="selFecha">Fecha</label>
        <input type="date" class="form-control" id="selFecha" name="fecha" value="{{ $fecha }}">
      </div>
      <div class="col-4 col-md-3"><button type="submit" class="btn btn-primary w-100">Ver</button></div>
    </div>
  </form>

  <form method="POST" action="/control-diario">
    @csrf
    <input type="hidden" name="fecha" value="{{ $fecha }}">
    <input type="hidden" name="sucursal_id" value="{{ $sucursal->id }}">

    <div class="tarjeta p-2 p-md-3">
      <div class="table-responsive tabla-control">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>No</th>
              <th class="nombre">Producto</th>
              <th>Inv.<br>inicial</th>
              <th>Envío</th>
              <th>Pasteles<br>porción</th>
              <th>Gastos<br>familia</th>
              <th>Mal<br>estado</th>
              <th>Inv.<br>final</th>
            </tr>
          </thead>
          <tbody>
            @php $categoriaActual = null; @endphp
            @foreach ($productos as $p)
            @php $reg = $hoy[$p->id] ?? null; @endphp
            @php $inicial = $reg ? $reg->inv_inicial : ($anterior[$p->id]->inv_final ?? 0); @endphp

            @if ($p->categoria !== $categoriaActual)
            @php $categoriaActual = $p->categoria; @endphp
            <tr class="seccion"><td colspan="8">{{ $p->categoria === 'pastel' ? 'Pasteles' : 'Otros productos' }}</td></tr>
            @endif

            <tr>
              <td>{{ $p->numero_orden }}</td>
              <td class="nombre">{{ $p->nombre }}</td>
              <td><input type="number" class="form-control form-control-sm" inputmode="numeric" min="0" name="conteos[{{ $p->id }}][inv_inicial]" value="{{ old("conteos.{$p->id}.inv_inicial", $inicial) }}"></td>
              <td><input type="number" class="form-control form-control-sm" inputmode="numeric" min="0" name="conteos[{{ $p->id }}][envio]" value="{{ old("conteos.{$p->id}.envio", $reg->envio ?? 0) }}"></td>
              <td><input type="number" class="form-control form-control-sm" inputmode="numeric" min="0" name="conteos[{{ $p->id }}][pasteles_porcion]" value="{{ old("conteos.{$p->id}.pasteles_porcion", $reg->pasteles_porcion ?? 0) }}"></td>
              <td><input type="number" class="form-control form-control-sm" inputmode="numeric" min="0" name="conteos[{{ $p->id }}][gastos_familia]" value="{{ old("conteos.{$p->id}.gastos_familia", $reg->gastos_familia ?? 0) }}"></td>
              <td><input type="number" class="form-control form-control-sm" inputmode="numeric" min="0" name="conteos[{{ $p->id }}][mal_estado]" value="{{ old("conteos.{$p->id}.mal_estado", $reg->mal_estado ?? 0) }}"></td>
              <td><input type="number" class="form-control form-control-sm" inputmode="numeric" min="0" name="conteos[{{ $p->id }}][inv_final]" value="{{ old("conteos.{$p->id}.inv_final", $reg->inv_final ?? 0) }}"></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="tarjeta h-100">
          <h5 class="titulo mb-3">Gastos del día (Q)</h5>
          <div class="rejilla-formulario">
            @foreach ($camposGasto as $campo => $etiqueta)
            <label class="mb-0" for="g_{{ $campo }}">{{ $etiqueta }}</label>
            <input id="g_{{ $campo }}" class="form-control" type="number" inputmode="decimal" step="0.01" min="0" name="gastos[{{ $campo }}]" value="{{ old("gastos.$campo", $gastos->$campo ?? 0) }}">
            @endforeach
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="tarjeta mb-3">
          <h5 class="titulo mb-3">Otros ingresos y caja chica (Q)</h5>
          <div class="rejilla-formulario">
            @foreach ($camposIngreso as $campo => $etiqueta)
            <label class="mb-0" for="g_{{ $campo }}">{{ $etiqueta }}</label>
            <input id="g_{{ $campo }}" class="form-control" type="number" inputmode="decimal" step="0.01" min="0" name="gastos[{{ $campo }}]" value="{{ old("gastos.$campo", $gastos->$campo ?? 0) }}">
            @endforeach
          </div>
        </div>
        <div class="tarjeta mb-0">
          <h5 class="titulo mb-3">Anticipos de pasteles</h5>
          <div class="rejilla-formulario" style="max-width: 460px;">
            @for ($i = 0; $i < 8; $i++)
            @php $a = $anticipos[$i] ?? null; @endphp
            <input type="text" class="form-control" name="anticipos[{{ $i }}][no_orden]" placeholder="No. orden" maxlength="50" value="{{ old("anticipos.$i.no_orden", $a->no_orden ?? '') }}">
            <input type="number" class="form-control" inputmode="decimal" step="0.01" min="0" name="anticipos[{{ $i }}][monto]" placeholder="Q" value="{{ old("anticipos.$i.monto", $a ? $a->monto : '') }}">
            @endfor
          </div>
        </div>
      </div>
    </div>

    <div class="barra-guardar d-flex gap-2 mt-3">
      <button type="submit" class="btn btn-success flex-fill">Guardar control diario</button>
    </div>
  </form>
</div>
@endsection
