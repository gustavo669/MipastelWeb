@extends('layouts.app')

@section('titulo', 'Auditoría')

@section('contenido')
<div class="container-fluid px-4 pb-5">

  <div class="tarjeta">
    <div class="row g-2 align-items-end">
      <div class="col-6 col-md-2"><label class="form-label mb-1" for="fechaInicio">Desde</label><input type="date" class="form-control" id="fechaInicio" value="{{ $hoy }}"></div>
      <div class="col-6 col-md-2"><label class="form-label mb-1" for="fechaFin">Hasta</label><input type="date" class="form-control" id="fechaFin" value="{{ $hoy }}"></div>
      <div class="col-6 col-md-2"><label class="form-label mb-1" for="filtroUsuario">Usuario</label><input type="text" class="form-control" id="filtroUsuario" placeholder="correo..." maxlength="100"></div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1" for="filtroAccion">Acción</label>
        <select class="form-select" id="filtroAccion">
          <option value="">Todas</option>
          <option value="login">Inicio de sesión</option>
          <option value="login_fallido">Intento fallido</option>
          <option value="login_bloqueado">Cuenta bloqueada</option>
          <option value="logout">Cierre de sesión</option>
          <option value="crear">Creó</option>
          <option value="actualizar">Modificó</option>
          <option value="eliminar">Eliminó</option>
          <option value="guardar">Guardó</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1" for="filtroRecurso">Sobre</label>
        <select class="form-select" id="filtroRecurso">
          <option value="">Todo</option>
          <option value="sesion">Sesión</option>
          <option value="pedido_normal">Pedido de tienda</option>
          <option value="pedido_cliente">Pedido de cliente</option>
          <option value="precio">Precio</option>
          <option value="control_diario">Control diario</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1" for="filtroSucursal">Sucursal</label>
        <select class="form-select" id="filtroSucursal">
          <option value="">Todas</option>
          @foreach ($sucursales as $s)
          <option value="{{ $s->id }}">{{ $s->nombre }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="d-flex gap-1 flex-wrap mt-3 filtros-acciones">
      <button type="button" class="btn btn-outline-primary btn-sm" data-rango="hoy" aria-pressed="false">Hoy</button>
      <button type="button" class="btn btn-outline-primary btn-sm" data-rango="semana" aria-pressed="false">Esta semana</button>
      <button type="button" class="btn btn-outline-primary btn-sm" data-rango="mes" aria-pressed="false">Este mes</button>
      <button type="button" class="btn btn-success btn-sm ms-2" id="btnBuscar">Buscar</button>
    </div>
  </div>

  <div class="tarjeta">
    <div class="d-flex justify-content-between align-items-center mb-2 auditoria-cabecera">
      <h5 class="titulo mb-0">Historial <span class="badge bg-secondary" id="total">0</span></h5>
      <div class="d-flex align-items-center gap-2 auditoria-paginacion">
        <button type="button" class="btn btn-sm btn-outline-secondary btn-paginacion-auditoria" id="btnAnterior" aria-label="Anterior" title="Anterior" disabled><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <span class="small text-muted" id="infoPagina">Página 1 de 1</span>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-paginacion-auditoria" id="btnSiguiente" aria-label="Siguiente" title="Siguiente" disabled><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover table-bordered align-middle">
        <thead class="table-light"><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Sobre</th><th>Sucursal</th><th>Detalle</th><th>IP</th></tr></thead>
        <tbody id="tabla"><tr><td colspan="7" class="text-center">Cargando...</td></tr></tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const RANGOS = @json($rangos);
const SUCURSALES = @json($mapaSucursales);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const ACCIONES = {
  login: ['Inicio de sesión', 'success'], login_fallido: ['Intento fallido', 'warning'], login_bloqueado: ['Cuenta bloqueada', 'danger'],
  logout: ['Cierre de sesión', 'secondary'], crear: ['Creó', 'primary'], actualizar: ['Modificó', 'info'], eliminar: ['Eliminó', 'danger'], guardar: ['Guardó', 'success'],
};
const RECURSOS = { sesion: 'Sesión', pedido_normal: 'Pedido de tienda', pedido_cliente: 'Pedido de cliente', precio: 'Precio', control_diario: 'Control diario' };
const CAMPOS_CONTEO = { inv_inicial: 'Inv. inicial', envio: 'Envío', pasteles_porcion: 'Pasteles porción', gastos_familia: 'Gastos familia', mal_estado: 'Mal estado', inv_final: 'Inv. final' };
const CAMPOS_GASTO = {
  gastos_compras: 'Compras', desc_degustacion: 'Desc. degustación', servilletas: 'Servilletas', tenedores: 'Tenedores', platos: 'Platos',
  donaciones: 'Donaciones', consumo_empleado: 'Consumo empleado', otros_gastos: 'Otros gastos', otros_ingresos: 'Otros ingresos', caja_chica_reportada: 'Caja chica',
};
const CAMPOS = {
  sabor: 'Sabor', tamano: 'Tamaño', cantidad: 'Cantidad', precio: 'Precio', sucursal_id: 'Sucursal',
  fecha_entrega: 'Entrega', detalles: 'Detalles', color: 'Color', dedicatoria: 'Dedicatoria',
};

let pagina = 1;
let paginas = 1;

const el = (id) => document.getElementById(id);
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

async function api(url) {
  const resp = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
  let data = {};
  try { data = await resp.json(); } catch (e) { data = {}; }
  return { ok: resp.ok, data: data };
}

function valorLegible(campo, valor) {
  if (valor === null || valor === undefined || valor === '') return '(vacío)';
  if (campo === 'sucursal_id') return SUCURSALES[String(valor)] || ('#' + valor);
  if (campo === 'precio') return 'Q' + Number(valor).toFixed(2);
  if (campo === 'fecha_entrega') return String(valor).slice(0, 10);
  return String(valor);
}

function listaAnticipos(lista) {
  if (!lista || !lista.length) return '(ninguno)';
  return lista.map((a) => (a.no_orden || 's/n') + ' ' + valorLegible('precio', a.monto)).join(', ');
}

/* Detalle de un guardado del control diario: qué hoja, qué productos cambiaron, gastos y anticipos */
function detalleControl(d) {
  const fechaTxt = d.fecha ? String(d.fecha).split('-').reverse().join('/') : '';
  let html = '<div><strong>Hoja del ' + esc(fechaTxt) + '</strong></div>';
  if (d.sin_cambios) return html + '<span class="text-muted">Se guardó sin cambios</span>';

  const conteos = d.conteos || [];
  if (conteos.length) {
    html += '<details><summary>' + conteos.length + ' producto(s) con cambios</summary>' + conteos.map((c) =>
      '<div>' + esc(c.producto) + ': ' + Object.keys(c.cambios).map((k) =>
        esc(CAMPOS_CONTEO[k] || k) + ' <span class="antes">' + esc(c.cambios[k][0]) + '</span> &rarr; <span class="despues">' + esc(c.cambios[k][1]) + '</span>'
      ).join(', ') + '</div>').join('') + '</details>';
  }
  Object.keys(d.gastos || {}).forEach((k) => {
    const par = d.gastos[k] || {};
    html += '<div><strong>' + esc(CAMPOS_GASTO[k] || k) + ':</strong> <span class="antes">' + esc(valorLegible('precio', par.antes || 0)) +
      '</span> &rarr; <span class="despues">' + esc(valorLegible('precio', par.despues || 0)) + '</span></div>';
  });
  if (d.anticipos) {
    html += '<div><strong>Anticipos:</strong> <span class="antes">' + esc(listaAnticipos(d.anticipos.antes)) +
      '</span> &rarr; <span class="despues">' + esc(listaAnticipos(d.anticipos.despues)) + '</span></div>';
  }
  return html;
}

function detalle(a) {
  const d = a.datos || {};
  if (a.recurso === 'control_diario') return detalleControl(d);
  if (a.accion === 'actualizar') {
    return Object.keys(d).map((campo) => {
      const par = d[campo] || {};
      return '<div><strong>' + esc(CAMPOS[campo] || campo) + ':</strong> <span class="antes">' + esc(valorLegible(campo, par.antes)) +
        '</span> &rarr; <span class="despues">' + esc(valorLegible(campo, par.despues)) + '</span></div>';
    }).join('') || '<span class="text-muted">-</span>';
  }
  if (a.accion === 'crear' || a.accion === 'eliminar') {
    if (a.recurso === 'precio') return esc((d.sabor || '') + ' ' + (d.tamano || '') + ': ' + valorLegible('precio', d.precio));
    if (a.recurso === 'pedido_normal' || a.recurso === 'pedido_cliente') {
      return esc((d.tamano || '') + ' de ' + (d.sabor || '') + ' × ' + (d.cantidad || '') + ' · ' + valorLegible('precio', d.precio));
    }
  }
  if (d.motivo) return esc(d.motivo);
  return '<span class="text-muted">-</span>';
}

function fila(a) {
  const tipo = ACCIONES[a.accion] || [a.accion, 'secondary'];
  const sobre = (RECURSOS[a.recurso] || a.recurso) + (a.recurso_id && a.recurso !== 'sesion' ? ' #' + a.recurso_id : '');
  return '<tr><td class="text-nowrap">' + esc(a.fecha) + '</td><td>' + esc(a.usuario) + '</td>' +
    '<td><span class="badge bg-' + tipo[1] + '">' + esc(tipo[0]) + '</span></td><td>' + esc(sobre) + '</td>' +
    '<td>' + (a.sucursal ? '<span class="badge bg-primary">' + esc(a.sucursal) + '</span>' : '<span class="text-muted">-</span>') + '</td>' +
    '<td class="detalle">' + detalle(a) + '</td><td class="text-nowrap small">' + esc(a.ip || '') + '</td></tr>';
}

function consulta() {
  const p = new URLSearchParams({ fecha_inicio: el('fechaInicio').value, fecha_fin: el('fechaFin').value, page: String(pagina) });
  [['usuario', 'filtroUsuario'], ['accion', 'filtroAccion'], ['recurso', 'filtroRecurso'], ['sucursal_id', 'filtroSucursal']].forEach(([k, id]) => {
    const v = el(id).value.trim();
    if (v) p.set(k, v);
  });
  return p.toString();
}

async function cargar() {
  const ini = el('fechaInicio').value;
  const fin = el('fechaFin').value;
  if (!ini || !fin) { alert('Por favor selecciona ambas fechas'); return; }
  if (fin < ini) { alert('La fecha fin debe ser igual o posterior a la fecha inicio'); return; }

  const r = await api('/admin/auditoria/lista?' + consulta());
  if (!r.ok) {
    el('tabla').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar el historial</td></tr>';
    return;
  }
  const registros = r.data.registros || [];
  paginas = r.data.paginas || 1;
  el('total').textContent = r.data.total || 0;
  el('infoPagina').textContent = 'Página ' + (r.data.pagina || 1) + ' de ' + paginas;
  el('btnAnterior').disabled = pagina <= 1;
  el('btnSiguiente').disabled = pagina >= paginas;
  el('tabla').innerHTML = registros.length
    ? registros.map(fila).join('')
    : '<tr><td colspan="7" class="text-center text-muted">No hay registros con estos filtros</td></tr>';
}

function buscar() { pagina = 1; return cargar(); }

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
    buscar();
  });
});
['fechaInicio', 'fechaFin'].forEach((id) => el(id).addEventListener('change', () => marcarRango(null)));
el('btnBuscar').addEventListener('click', buscar);
['filtroAccion', 'filtroRecurso', 'filtroSucursal'].forEach((id) => el(id).addEventListener('change', buscar));
el('filtroUsuario').addEventListener('keydown', (ev) => { if (ev.key === 'Enter') buscar(); });
el('btnAnterior').addEventListener('click', () => { if (pagina > 1) { pagina--; cargar(); } });
el('btnSiguiente').addEventListener('click', () => { if (pagina < paginas) { pagina++; cargar(); } });

cargar();
</script>
@endpush
