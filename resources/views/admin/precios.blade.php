@extends('layouts.app')

@section('titulo', 'Precios')

@section('contenido')
<div class="container pb-5" style="max-width: 900px;">

  <div class="tarjeta">
    <h5 class="titulo mb-3">Agregar sabor o tamaño nuevo</h5>
    <div class="row g-2 align-items-end">
      <div class="col-md-4"><label class="form-label mb-1" for="nuevoSabor">Sabor</label><input type="text" class="form-control" id="nuevoSabor" list="listaSabores" maxlength="50" placeholder="Ej: Ambiente"></div>
      <div class="col-md-3"><label class="form-label mb-1" for="nuevoTamano">Tamaño</label><input type="text" class="form-control" id="nuevoTamano" maxlength="50" placeholder="Ej: Mediano"></div>
      <div class="col-md-3"><label class="form-label mb-1" for="nuevoPrecio">Precio (Q)</label><input type="number" class="form-control" id="nuevoPrecio" min="0" step="0.01"></div>
      <div class="col-md-2"><button type="button" class="btn btn-success w-100" id="btnAgregar">Agregar</button></div>
    </div>
    <datalist id="listaSabores">
      @foreach ($sabores as $nombre)
      <option value="{{ $nombre }}">
      @endforeach
    </datalist>
  </div>

  <div class="tarjeta">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h5 class="titulo mb-0">Precios de pasteles <span class="badge bg-secondary" id="contador">0</span></h5>
      <input type="search" class="form-control" id="buscar" placeholder="Buscar sabor o tamaño..." style="max-width: 260px;">
    </div>
    <div class="alert alert-info py-2 small">Los cambios de precio solo afectan a los pedidos nuevos. Los pedidos ya registrados conservan el precio con el que se hicieron.</div>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        <thead class="table-light"><tr><th>Sabor</th><th>Tamaño</th><th class="text-end">Precio (Q)</th><th></th></tr></thead>
        <tbody id="tablaPrecios"><tr><td colspan="4" class="text-center">Cargando...</td></tr></tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end align-items-center gap-3">
      <span class="text-muted" id="infoCambios">Sin cambios</span>
      <button type="button" class="btn btn-success" id="btnGuardar" disabled>Guardar cambios</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

let precios = [];      // lista completa tal como está en el servidor
let editados = {};     // id -> precio nuevo (solo las filas que cambiaron)

const el = (id) => document.getElementById(id);
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

async function api(metodo, url, cuerpo) {
  const opciones = { method: metodo, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } };
  if (cuerpo !== undefined) {
    opciones.headers['Content-Type'] = 'application/json';
    opciones.body = JSON.stringify(cuerpo);
  }
  const resp = await fetch(url, opciones);
  let data = {};
  try { data = await resp.json(); } catch (e) { data = {}; }
  return { ok: resp.ok, data: data };
}

function mensajeDeError(data) {
  if (data && data.errors) {
    const primero = Object.values(data.errors)[0];
    if (primero && primero.length) return primero[0];
  }
  return (data && data.message) ? data.message : 'Error desconocido';
}

function actualizarInfo() {
  const n = Object.keys(editados).length;
  el('infoCambios').textContent = n ? n + ' precio(s) con cambios sin guardar' : 'Sin cambios';
  el('btnGuardar').disabled = n === 0;
}

function render() {
  const texto = el('buscar').value.trim().toLowerCase();
  const filas = precios.filter((p) => !texto || (p.sabor + ' ' + p.tamano).toLowerCase().includes(texto));
  el('contador').textContent = precios.length;

  if (!filas.length) {
    el('tablaPrecios').innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay precios que coincidan</td></tr>';
    return;
  }
  el('tablaPrecios').innerHTML = filas.map((p) => {
    const valor = Object.prototype.hasOwnProperty.call(editados, p.id) ? editados[p.id] : p.precio;
    const cambiado = Object.prototype.hasOwnProperty.call(editados, p.id);
    return '<tr class="' + (cambiado ? 'cambiado' : '') + '"><td>' + esc(p.sabor) + '</td><td>' + esc(p.tamano) + '</td>' +
      '<td class="text-end"><input type="number" class="form-control form-control-sm d-inline-block precio" min="0" step="0.01" data-id="' + p.id + '" value="' + esc(valor) + '"></td>' +
      '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" data-eliminar="' + p.id + '">Eliminar</button></td></tr>';
  }).join('');
}

async function cargar() {
  const r = await api('GET', '/admin/precios/lista');
  if (!r.ok) {
    el('tablaPrecios').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar precios</td></tr>';
    return;
  }
  precios = r.data.precios || [];
  editados = {};
  render();
  actualizarInfo();
}

function alCambiarPrecio(input) {
  const id = parseInt(input.getAttribute('data-id'), 10);
  const original = precios.find((p) => p.id === id);
  const nuevo = parseFloat(input.value);
  if (!original) return;
  if (isNaN(nuevo) || nuevo === original.precio) {
    delete editados[id];
  } else {
    editados[id] = nuevo;
  }
  input.closest('tr').classList.toggle('cambiado', Object.prototype.hasOwnProperty.call(editados, id));
  actualizarInfo();
}

async function guardarCambios() {
  const lista = Object.keys(editados).map((id) => ({ id: parseInt(id, 10), precio: editados[id] }));
  if (!lista.length) return;
  const invalido = lista.find((x) => !(x.precio > 0));
  if (invalido) {
    alert('Todos los precios deben ser mayores que cero');
    return;
  }
  const r = await api('PUT', '/admin/precios', { precios: lista });
  if (r.ok) {
    alert('Se actualizaron ' + r.data.actualizados + ' precio(s)');
    await cargar();
  } else {
    alert(mensajeDeError(r.data));
  }
}

async function agregar() {
  const cuerpo = {
    sabor: el('nuevoSabor').value.trim(),
    tamano: el('nuevoTamano').value.trim(),
    precio: parseFloat(el('nuevoPrecio').value) || 0,
  };
  if (!cuerpo.sabor || !cuerpo.tamano || !(cuerpo.precio > 0)) {
    alert('Escribe sabor, tamaño y un precio mayor que cero');
    return;
  }
  const r = await api('POST', '/admin/precios', cuerpo);
  if (r.ok) {
    el('nuevoSabor').value = '';
    el('nuevoTamano').value = '';
    el('nuevoPrecio').value = '';
    await cargar();
  } else {
    alert(mensajeDeError(r.data));
  }
}

async function eliminar(id) {
  const p = precios.find((x) => x.id === id);
  if (!p) return;
  if (!confirm('¿Eliminar el precio de ' + p.sabor + ' ' + p.tamano + '? Los pedidos ya registrados no se modifican.')) return;
  const r = await api('DELETE', '/admin/precios/' + id);
  if (r.ok) {
    await cargar();
  } else {
    alert(mensajeDeError(r.data));
  }
}

el('buscar').addEventListener('input', render);
el('btnGuardar').addEventListener('click', guardarCambios);
el('btnAgregar').addEventListener('click', agregar);
el('tablaPrecios').addEventListener('input', (ev) => {
  if (ev.target.matches('input.precio')) alCambiarPrecio(ev.target);
});
el('tablaPrecios').addEventListener('click', (ev) => {
  const b = ev.target.closest('[data-eliminar]');
  if (b) eliminar(parseInt(b.getAttribute('data-eliminar'), 10));
});

cargar();
</script>
@endpush
