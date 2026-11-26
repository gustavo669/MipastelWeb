@extends('layouts.app')

@section('titulo', 'Capturas del día')

@section('contenido')
<div class="container pb-5" style="max-width: 1000px;">

  <div class="tarjeta">
    <div class="d-flex gap-2 align-items-end flex-wrap">
      <div>
        <label class="form-label mb-1" for="fecha">Fecha del control diario</label>
        <input type="date" class="form-control" id="fecha" value="{{ $hoy }}">
      </div>
      <button type="button" class="btn btn-outline-primary" id="btnHoy">Hoy</button>
      <button type="button" class="btn btn-outline-primary" id="btnAyer">Ayer</button>
      <button type="button" class="btn btn-success" id="btnActualizar">Actualizar</button>
    </div>
  </div>

  <div class="tarjeta">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h5 class="titulo mb-0">Estado por sucursal</h5>
      <span class="badge fs-6 bg-secondary" id="resumen">-</span>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered align-middle tabla-capturas">
        <thead class="table-light"><tr><th>Sucursal</th><th>Estado</th><th>Guardó</th><th>Última vez</th><th class="text-center">Veces</th><th class="text-end">Gastos</th><th>Hoja</th></tr></thead>
        <tbody id="tabla"><tr><td colspan="7" class="text-center">Cargando...</td></tr></tbody>
      </table>
    </div>
    <div class="form-text">"Veces" es cuántas veces se guardó esa hoja. El detalle de qué cambió en cada guardado está en <a href="/admin/auditoria">Auditoría</a> (filtra por "Control diario").</div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const HOY = @json($hoy);
const AYER = @json($ayer);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const el = (id) => document.getElementById(id);
const dinero = (n) => 'Q' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

async function api(url) {
  const resp = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
  let data = {};
  try { data = await resp.json(); } catch (e) { data = {}; }
  return { ok: resp.ok, data: data };
}

function fila(f, fecha) {
  const params = new URLSearchParams({ fecha: fecha, sucursal_id: String(f.sucursal_id) }).toString();
  const estado = f.capturado
    ? '<span class="badge bg-success">Capturado</span>'
    : '<span class="badge bg-danger">Sin captura</span>';
  const enlaces = '<a class="btn btn-sm btn-outline-primary me-1" href="/control-diario?' + params + '" target="_blank" rel="noopener">Ver</a>' +
    (f.capturado ? '<a class="btn btn-sm btn-outline-secondary" href="/control-diario/pdf?' + params + '" target="_blank" rel="noopener">PDF</a>' : '');
  return '<tr class="' + (f.capturado ? '' : 'sin-captura') + '"><td class="nombre-sucursal">' + esc(f.sucursal) + '</td><td>' + estado + '</td>' +
    '<td>' + (f.guardo ? esc(f.guardo) : '<span class="text-muted">-</span>') + '</td>' +
    '<td class="text-nowrap">' + (f.ultima ? esc(f.ultima) : '<span class="text-muted">-</span>') + '</td>' +
    '<td class="text-center">' + f.veces + '</td><td class="text-end">' + (f.capturado ? dinero(f.total_gastos) : '<span class="text-muted">-</span>') + '</td>' +
    '<td class="text-nowrap">' + enlaces + '</td></tr>';
}

async function cargar() {
  const fecha = el('fecha').value;
  if (!fecha) {
    alert('Selecciona una fecha');
    return;
  }
  const r = await api('/admin/control/resumen?fecha=' + encodeURIComponent(fecha));
  if (!r.ok) {
    el('tabla').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar las capturas</td></tr>';
    return;
  }
  const filas = r.data.filas || [];
  el('tabla').innerHTML = filas.map((f) => fila(f, r.data.fecha)).join('');
  el('resumen').textContent = r.data.capturadas + ' de ' + r.data.total + ' sucursales con captura';
  el('resumen').className = 'badge fs-6 ' + (r.data.capturadas === r.data.total ? 'bg-success' : 'bg-warning text-dark');
}

el('btnActualizar').addEventListener('click', cargar);
el('fecha').addEventListener('change', cargar);
el('btnHoy').addEventListener('click', () => { el('fecha').value = HOY; cargar(); });
el('btnAyer').addEventListener('click', () => { el('fecha').value = AYER; cargar(); });

cargar();
</script>
@endpush
