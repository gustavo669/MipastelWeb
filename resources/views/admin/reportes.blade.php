@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
<div class="container pb-5" style="max-width: 820px;">
  <div class="tarjeta">
    <h5 class="titulo mb-3">Generar reporte PDF</h5>

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <input type="radio" class="btn-check" name="tipo" id="tipoProduccion" value="produccion" checked>
        <label class="opcion-reporte" for="tipoProduccion">
          <strong>Producción</strong><br>
          <span class="text-muted small">Cuántos pasteles de cada tipo pide cada sucursal, más el control de pedidos de clientes (con dedicatoria, color y si tiene foto).</span>
        </label>
      </div>
      <div class="col-md-6">
        <input type="radio" class="btn-check" name="tipo" id="tipoVentas" value="ventas">
        <label class="opcion-reporte" for="tipoVentas">
          <strong>Ventas</strong><br>
          <span class="text-muted small">Lo vendido en pasteles de tienda y en pedidos de clientes, con subtotales y total general en quetzales.</span>
        </label>
      </div>
    </div>

    <div class="row g-2 align-items-end">
      <div class="col-6 col-md-3">
        <label class="form-label mb-1" for="fechaInicio">Fecha inicio</label>
        <input type="date" class="form-control" id="fechaInicio" value="{{ $hoy }}">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label mb-1" for="fechaFin">Fecha fin</label>
        <input type="date" class="form-control" id="fechaFin" value="{{ $hoy }}">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label mb-1" for="sucursal">Sucursal</label>
        <select class="form-select" id="sucursal">
          <option value="">Todas</option>
          @foreach ($sucursales as $s)
          <option value="{{ $s->id }}">{{ $s->nombre }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="d-flex gap-1 flex-wrap mt-3 filtros-acciones filtros-acciones--tres">
      <button type="button" class="btn btn-outline-primary btn-sm" data-rango="hoy" aria-pressed="false">Hoy</button>
      <button type="button" class="btn btn-outline-primary btn-sm" data-rango="semana" aria-pressed="false">Esta semana</button>
      <button type="button" class="btn btn-outline-primary btn-sm" data-rango="mes" aria-pressed="false">Este mes</button>
    </div>
    <div class="form-text mt-2">Para un solo día, deja la misma fecha en inicio y fin. Se toman los pedidos según la fecha en que se registraron.</div>

    <button type="button" class="btn btn-success w-100 mt-4" id="btnGenerar">Generar PDF</button>
  </div>
</div>
@endsection

@push('scripts')
<script>
const RANGOS = @json($rangos);
const el = (id) => document.getElementById(id);

function generar() {
  const ini = el('fechaInicio').value;
  const fin = el('fechaFin').value;
  if (!ini || !fin) {
    alert('Por favor selecciona ambas fechas');
    return;
  }
  if (fin < ini) {
    alert('La fecha fin debe ser igual o posterior a la fecha inicio');
    return;
  }
  const tipo = document.querySelector('input[name="tipo"]:checked').value;
  const params = new URLSearchParams({ fecha_inicio: ini, fecha_fin: fin });
  if (el('sucursal').value) params.set('sucursal_id', el('sucursal').value);
  window.open('/admin/reportes/' + tipo + '?' + params.toString(), '_blank');
}

function marcarRango(nombre) {
  document.querySelectorAll('[data-rango]').forEach((b) => {
    const seleccionado = b.getAttribute('data-rango') === nombre;
    b.classList.toggle('active', seleccionado);
    b.setAttribute('aria-pressed', String(seleccionado));
  });
}

document.querySelectorAll('[data-rango]').forEach((b) => {
  b.addEventListener('click', () => {
    const nombre = b.getAttribute('data-rango');
    const r = RANGOS[nombre];
    el('fechaInicio').value = r[0];
    el('fechaFin').value = r[1];
    marcarRango(nombre);
  });
});
['fechaInicio', 'fechaFin'].forEach((id) => el(id).addEventListener('change', () => marcarRango(null)));
el('btnGenerar').addEventListener('click', generar);
</script>
@endpush
