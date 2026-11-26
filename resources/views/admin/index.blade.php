@extends('layouts.app')

@section('titulo', 'Administración')

@section('contenido')
<div class="container-fluid px-4 pb-5">

  {{-- ===== Filtros ===== --}}
  <div class="tarjeta">
    <div class="row g-2 align-items-end">
      <div class="col-6 col-md-2">
        <label class="form-label mb-1" for="fechaInicio">Fecha inicio</label>
        <input type="date" class="form-control" id="fechaInicio" value="{{ $hoy }}">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1" for="fechaFin">Fecha fin</label>
        <input type="date" class="form-control" id="fechaFin" value="{{ $hoy }}">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label mb-1" for="filtroSucursal">Sucursal</label>
        <select class="form-select" id="filtroSucursal">
          <option value="">Todas</option>
          @foreach ($sucursales as $s)
          <option value="{{ $s->id }}">{{ $s->nombre }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-5 d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-outline-primary" data-rango="hoy" aria-pressed="false">Hoy</button>
        <button type="button" class="btn btn-outline-primary" data-rango="semana" aria-pressed="false">Esta semana</button>
        <button type="button" class="btn btn-outline-primary" data-rango="mes" aria-pressed="false">Este mes</button>
        <button type="button" class="btn btn-success" id="btnAplicar">Aplicar filtro</button>
      </div>
    </div>
    <div class="form-text mt-2">Se filtra por la fecha en que se registró el pedido.</div>
  </div>

  {{-- ===== Estadísticas ===== --}}
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="tarjeta stats-card text-center mb-0"><div class="text-muted">Pedidos Tiendas</div><h2 class="text-primary" id="statTiendas">0</h2></div></div>
    <div class="col-6 col-md-3"><div class="tarjeta stats-card text-center mb-0"><div class="text-muted">Pedidos Clientes</div><h2 class="text-primary" id="statClientes">0</h2></div></div>
    <div class="col-6 col-md-3"><div class="tarjeta stats-card text-center mb-0"><div class="text-muted">Ingresos Tiendas</div><h2 class="text-success" id="statIngresosTiendas">Q0.00</h2></div></div>
    <div class="col-6 col-md-3"><div class="tarjeta stats-card text-center mb-0"><div class="text-muted">Ingresos Clientes</div><h2 class="text-success" id="statIngresosClientes">Q0.00</h2></div></div>
  </div>

  {{-- ===== Tablas ===== --}}
  <ul class="nav nav-tabs mb-3">
    <li class="nav-item"><button class="nav-link active" id="tab-n" data-bs-toggle="tab" data-bs-target="#panel-n" type="button">Pedidos de Tienda</button></li>
    <li class="nav-item"><button class="nav-link" id="tab-c" data-bs-toggle="tab" data-bs-target="#panel-c" type="button">Pedidos de Cliente</button></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="panel-n">
      <div class="tarjeta">
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light"><tr><th>ID</th><th>Tamaño</th><th>Sabor</th><th>Cant.</th><th>P. unit.</th><th>Total</th><th>Sucursal</th><th>Entrega</th><th>Registro</th><th>Acciones</th></tr></thead>
            <tbody id="tablaN"><tr><td colspan="10" class="text-center">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="panel-c">
      <div class="tarjeta">
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light"><tr><th>ID</th><th>Tamaño</th><th>Sabor</th><th>Cant.</th><th>P. unit.</th><th>Total</th><th>Sucursal</th><th>Registro</th><th>Entrega</th><th>Color</th><th>Dedicatoria</th><th>Detalles</th><th>Imagen</th><th>Acciones</th></tr></thead>
            <tbody id="tablaC"><tr><td colspan="14" class="text-center">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ===== Modal de edición (admin puede cambiar todo) ===== --}}
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar pedido <span id="editId"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="eSabor">Sabor</label><input type="text" class="form-control" id="eSabor" list="listaSabores" maxlength="100"></div>
          <div class="col-md-6"><label class="form-label" for="eTamano">Tamaño</label><input type="text" class="form-control" id="eTamano" list="listaTamanos" maxlength="50"></div>
          <div class="col-md-4"><label class="form-label" for="eCantidad">Cantidad</label><input type="number" class="form-control" id="eCantidad" min="1" max="1000"></div>
          <div class="col-md-4"><label class="form-label" for="ePrecio">Precio unitario (Q)</label><input type="number" class="form-control" id="ePrecio" min="0" step="0.01"></div>
          <div class="col-md-4"><label class="form-label">Total</label><div class="form-control bg-light" id="eTotal">Q0.00</div></div>
          <div class="col-md-6">
            <label class="form-label" for="eSucursal">Sucursal</label>
            <select class="form-select" id="eSucursal">
              @foreach ($sucursales as $s)
              <option value="{{ $s->id }}">{{ $s->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6"><label class="form-label" for="eFecha">Fecha de entrega</label><input type="date" class="form-control" id="eFecha"></div>
          <div id="eClienteCampos" class="d-none col-12">
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label" for="eColor">Color</label><input type="text" class="form-control" id="eColor" maxlength="50"></div>
              <div class="col-md-8"><label class="form-label" for="eDedicatoria">Dedicatoria</label><input type="text" class="form-control" id="eDedicatoria" maxlength="500"></div>
            </div>
          </div>
          <div class="col-12"><label class="form-label" for="eDetalles">Detalles</label><textarea class="form-control" id="eDetalles" rows="2" maxlength="500"></textarea></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success" id="btnGuardar">Guardar cambios</button></div>
    </div>
  </div>
</div>

<datalist id="listaSabores">
  @foreach ($sabores as $nombre)
  <option value="{{ $nombre }}">
  @endforeach
</datalist>
<datalist id="listaTamanos">
  @foreach ($tamanos as $nombre)
  <option value="{{ $nombre }}">
  @endforeach
</datalist>
@endsection

@push('scripts')
<script>
const RANGOS = @json($rangos);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const URL_TIPO = { n: '/admin/normales', c: '/admin/clientes' };

let datos = { n: [], c: [] };
let edicion = null; // { tipo: 'n' | 'c', id: number }

const el = (id) => document.getElementById(id);
const dinero = (n) => 'Q' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

/* ---------- Filtros ---------- */
function consulta() {
  const params = new URLSearchParams({ fecha_inicio: el('fechaInicio').value, fecha_fin: el('fechaFin').value });
  if (el('filtroSucursal').value) params.set('sucursal_id', el('filtroSucursal').value);
  return params.toString();
}

function celdaTexto(t) {
  return t ? '<span class="d-inline-block recorte" title="' + esc(t) + '">' + esc(t) + '</span>' : '<span class="text-muted">-</span>';
}

function botones(p, tipo) {
  return '<button type="button" class="btn btn-sm btn-primary me-1" data-editar="' + p.id + '" data-tipo="' + tipo + '">Editar</button>' +
         '<button type="button" class="btn btn-sm btn-danger" data-eliminar="' + p.id + '" data-tipo="' + tipo + '">Eliminar</button>';
}

function filaNormal(p) {
  return '<tr><td><strong>' + p.id + '</strong></td><td>' + esc(p.tamano) + '</td><td>' + esc(p.sabor) + '</td>' +
    '<td class="text-center">' + p.cantidad + '</td><td class="text-end">' + dinero(p.precio) + '</td>' +
    '<td class="text-end"><strong>' + dinero(p.total) + '</strong></td><td><span class="badge bg-primary">' + esc(p.sucursal) + '</span></td>' +
    '<td class="text-center">' + esc(p.fecha_entrega || '') + '</td><td class="text-center">' + esc(p.fecha || '') + '</td>' +
    '<td class="text-center text-nowrap">' + botones(p, 'n') + '</td></tr>';
}

function filaCliente(p) {
  const imagen = p.imagen
    ? '<a href="' + esc(p.imagen) + '" target="_blank" rel="noopener"><img class="miniatura" src="' + esc(p.imagen) + '" alt="Foto del pedido"></a>'
    : '<span class="text-muted">-</span>';
  const color = p.color ? '<span class="badge bg-light text-dark border">' + esc(p.color) + '</span>' : '<span class="text-muted">-</span>';
  return '<tr><td><strong>' + p.id + '</strong></td><td>' + esc(p.tamano) + '</td><td>' + esc(p.sabor) + '</td>' +
    '<td class="text-center">' + p.cantidad + '</td><td class="text-end">' + dinero(p.precio) + '</td>' +
    '<td class="text-end"><strong>' + dinero(p.total) + '</strong></td><td><span class="badge bg-primary">' + esc(p.sucursal) + '</span></td>' +
    '<td class="text-center">' + esc(p.fecha || '') + '</td><td class="text-center">' + esc(p.fecha_entrega || '') + '</td>' +
    '<td class="text-center">' + color + '</td><td>' + celdaTexto(p.dedicatoria) + '</td><td>' + celdaTexto(p.detalles) + '</td>' +
    '<td class="text-center">' + imagen + '</td><td class="text-center text-nowrap">' + botones(p, 'c') + '</td></tr>';
}

async function cargarTipo(tipo, idCuerpo, columnas, filaFn, idStatCantidad, idStatIngresos) {
  const cuerpo = el(idCuerpo);
  try {
    const r = await api('GET', URL_TIPO[tipo] + '?' + consulta());
    if (!r.ok) throw new Error(mensajeDeError(r.data));
    datos[tipo] = r.data.pedidos || [];
    el(idStatCantidad).textContent = r.data.resumen ? r.data.resumen.pedidos : datos[tipo].length;
    el(idStatIngresos).textContent = dinero(r.data.resumen ? r.data.resumen.ingresos : 0);
  } catch (e) {
    datos[tipo] = [];
    cuerpo.innerHTML = '<tr><td colspan="' + columnas + '" class="text-center text-danger">Error al cargar pedidos: ' + esc(e.message) + '</td></tr>';
    return;
  }
  cuerpo.innerHTML = datos[tipo].length
    ? datos[tipo].map(filaFn).join('')
    : '<tr><td colspan="' + columnas + '" class="text-center text-muted">No hay pedidos en este rango</td></tr>';
}

async function aplicarFiltro() {
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
  await Promise.all([
    cargarTipo('n', 'tablaN', 10, filaNormal, 'statTiendas', 'statIngresosTiendas'),
    cargarTipo('c', 'tablaC', 14, filaCliente, 'statClientes', 'statIngresosClientes'),
  ]);
}

function marcarRango(nombre) {
  document.querySelectorAll('[data-rango]').forEach((b) => {
    const seleccionado = b.getAttribute('data-rango') === nombre;
    b.classList.toggle('active', seleccionado);
    b.setAttribute('aria-pressed', String(seleccionado));
  });
}

function aplicarRango(nombre) {
  el('fechaInicio').value = RANGOS[nombre][0];
  el('fechaFin').value = RANGOS[nombre][1];
  marcarRango(nombre);
  aplicarFiltro();
}

/* ---------- Edición ---------- */
function actualizarTotalEdicion() {
  const total = (parseFloat(el('ePrecio').value) || 0) * (parseInt(el('eCantidad').value, 10) || 0);
  el('eTotal').textContent = dinero(total);
}

function abrirEdicion(tipo, id) {
  const p = datos[tipo].find((x) => x.id === id);
  if (!p) return;
  edicion = { tipo: tipo, id: id };
  el('editId').textContent = '#' + id + (tipo === 'c' ? ' (cliente)' : ' (tienda)');
  el('eSabor').value = p.sabor;
  el('eTamano').value = p.tamano;
  el('eCantidad').value = p.cantidad;
  el('ePrecio').value = p.precio;
  el('eSucursal').value = String(p.sucursal_id);
  el('eFecha').value = p.fecha_entrega || '';
  el('eDetalles').value = p.detalles || '';
  el('eClienteCampos').classList.toggle('d-none', tipo !== 'c');
  el('eColor').value = p.color || '';
  el('eDedicatoria').value = p.dedicatoria || '';
  actualizarTotalEdicion();
  bootstrap.Modal.getOrCreateInstance(el('modalEditar')).show();
}

async function guardarEdicion() {
  if (!edicion) return;
  const cuerpo = {
    sabor: el('eSabor').value.trim(),
    tamano: el('eTamano').value.trim(),
    cantidad: parseInt(el('eCantidad').value, 10) || 0,
    precio: parseFloat(el('ePrecio').value) || 0,
    sucursal_id: parseInt(el('eSucursal').value, 10),
    fecha_entrega: el('eFecha').value,
    detalles: el('eDetalles').value,
  };
  if (edicion.tipo === 'c') {
    cuerpo.color = el('eColor').value;
    cuerpo.dedicatoria = el('eDedicatoria').value;
  }
  const r = await api('PUT', URL_TIPO[edicion.tipo] + '/' + edicion.id, cuerpo);
  if (r.ok) {
    bootstrap.Modal.getOrCreateInstance(el('modalEditar')).hide();
    await aplicarFiltro();
  } else {
    alert(mensajeDeError(r.data));
  }
}

async function eliminar(tipo, id) {
  if (!confirm('¿Eliminar el pedido #' + id + '? Esta acción no se puede deshacer.')) return;
  const r = await api('DELETE', URL_TIPO[tipo] + '/' + id);
  if (r.ok) {
    await aplicarFiltro();
  } else {
    alert(mensajeDeError(r.data));
  }
}

/* ---------- Eventos ---------- */
document.querySelectorAll('[data-rango]').forEach((b) => {
  b.addEventListener('click', () => aplicarRango(b.getAttribute('data-rango')));
});
el('btnAplicar').addEventListener('click', aplicarFiltro);
el('filtroSucursal').addEventListener('change', aplicarFiltro);
['fechaInicio', 'fechaFin'].forEach((id) => el(id).addEventListener('change', () => marcarRango(null)));
el('btnGuardar').addEventListener('click', guardarEdicion);
el('ePrecio').addEventListener('input', actualizarTotalEdicion);
el('eCantidad').addEventListener('input', actualizarTotalEdicion);
['tablaN', 'tablaC'].forEach((idCuerpo) => {
  el(idCuerpo).addEventListener('click', (ev) => {
    const e = ev.target.closest('[data-editar]');
    if (e) return abrirEdicion(e.getAttribute('data-tipo'), parseInt(e.getAttribute('data-editar'), 10));
    const d = ev.target.closest('[data-eliminar]');
    if (d) return eliminar(d.getAttribute('data-tipo'), parseInt(d.getAttribute('data-eliminar'), 10));
  });
});

aplicarFiltro();
</script>
@endpush
