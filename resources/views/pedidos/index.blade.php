@extends('layouts.app')

@section('titulo', 'Pedidos')

@section('contenido')
<div class="container pb-5" style="max-width: 1100px;">

  @if ($esAdmin)
  <div class="tarjeta">
    <label class="form-label mb-1" for="sucursalAdmin">Registrar pedidos para la sucursal</label>
    <select class="form-select" id="sucursalAdmin">
      @foreach ($sucursales as $s)
      <option value="{{ $s->id }}" {{ $s->id == $sucursal->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
      @endforeach
    </select>
    <div class="form-text">Modo administrador: cada pedido que agregues a la lista se registra a nombre de la sucursal que esté elegida en ese momento.</div>
  </div>
  @endif

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" id="tab-t" data-bs-toggle="tab" data-bs-target="#panel-t" type="button">Pedido de Tienda</button></li>
    <li class="nav-item"><button class="nav-link" id="tab-c" data-bs-toggle="tab" data-bs-target="#panel-c" type="button">Pedido de Cliente</button></li>
    <li class="nav-item"><button class="nav-link" id="tab-lista" data-bs-toggle="tab" data-bs-target="#panel-lista" type="button">Mi Lista <span class="badge bg-secondary" id="contadorLista">0</span></button></li>
    <li class="nav-item"><button class="nav-link" id="tab-registrados" data-bs-toggle="tab" data-bs-target="#panel-registrados" type="button">Registrados</button></li>
  </ul>

  <div class="tab-content">

    {{-- ===== Formularios: p = t (tienda) o c (cliente) ===== --}}
    @foreach (['t' => 'Pedido de Tienda', 'c' => 'Pedido de Cliente'] as $p => $titulo)
    <div class="tab-pane fade {{ $p === 't' ? 'show active' : '' }}" id="panel-{{ $p }}">
      <div class="tarjeta">
        <h5 class="titulo mb-3">{{ $titulo }}</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="{{ $p }}Sabor">Sabor</label>
            <select class="form-select" id="{{ $p }}Sabor">
              <option value="">Seleccionar...</option>
              @foreach ($catalogo as $nombreSabor => $tamanosDelSabor)
              <option value="{{ $nombreSabor }}">{{ $nombreSabor }}</option>
              @endforeach
              <option value="Otro">Otro</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="{{ $p }}SaborOtro" maxlength="100" placeholder="Sabor personalizado">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="{{ $p }}Tamano">Tamaño</label>
            <select class="form-select" id="{{ $p }}Tamano"><option value="">Seleccionar...</option></select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="{{ $p }}Cantidad">Cantidad</label>
            <input type="number" class="form-control" id="{{ $p }}Cantidad" value="1" min="1" max="1000">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="{{ $p }}Fecha">Fecha de entrega</label>
            <input type="date" class="form-control" id="{{ $p }}Fecha" value="{{ $manana }}" min="{{ $hoy }}">
          </div>
          <div class="col-md-4 d-none" id="{{ $p }}PrecioManualBox">
            <label class="form-label" for="{{ $p }}PrecioManual">Precio unitario manual (Q)</label>
            <input type="number" class="form-control" id="{{ $p }}PrecioManual" min="0" step="0.01" placeholder="0.00">
          </div>
          @if ($p === 'c')
          <div class="col-md-4">
            <label class="form-label" for="cColor">Color</label>
            <input type="text" class="form-control" id="cColor" maxlength="50" placeholder="Ej: Rosa">
          </div>
          <div class="col-md-8">
            <label class="form-label" for="cDedicatoria">Dedicatoria</label>
            <input type="text" class="form-control" id="cDedicatoria" maxlength="500" placeholder="Texto para el pastel">
          </div>
          <div class="col-md-12">
            <label class="form-label" for="cDetalles">Detalles</label>
            <textarea class="form-control" id="cDetalles" rows="2" maxlength="500" placeholder="Detalles adicionales"></textarea>
          </div>
          <div class="col-md-12">
            <label class="form-label" for="cFoto">Foto de referencia (opcional)</label>
            <input type="file" class="form-control" id="cFoto" accept="image/*">
            <div class="form-text" id="cFotoInfo">Se reduce automáticamente para subirla rápido.</div>
          </div>
          @endif
        </div>
        <div class="precio-box mt-3 row">
          <div class="col-6"><strong>Precio unitario:</strong> <span id="{{ $p }}PrecioUnitario">Q0.00</span></div>
          <div class="col-6"><strong>Total:</strong> <span id="{{ $p }}PrecioTotal" class="titulo fw-bold">Q0.00</span></div>
        </div>
        <button type="button" class="btn btn-success w-100 mt-3" data-agregar="{{ $p }}">Agregar a la lista</button>
      </div>
    </div>
    @endforeach

    {{-- ===== Lista (carrito) ===== --}}
    <div class="tab-pane fade" id="panel-lista">
      <div class="tarjeta">
        <h5 class="titulo mb-3">Mi Lista de Pedidos</h5>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light"><tr><th>#</th><th>Tipo</th><th>Sabor</th><th>Tamaño</th><th>Cant.</th><th>Entrega</th><th>P. unit.</th><th>Total</th><th>Extras</th><th></th></tr></thead>
            <tbody id="tablaLista"></tbody>
          </table>
        </div>
        <div class="text-end fw-bold mb-3">Total de la lista: <span id="totalLista" class="titulo">Q0.00</span></div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-danger flex-fill" id="btnLimpiar">Limpiar lista</button>
          <button type="button" class="btn btn-primary flex-fill" id="btnRegistrar">Registrar todos</button>
        </div>
      </div>
    </div>

    {{-- ===== Registrados ===== --}}
    <div class="tab-pane fade" id="panel-registrados">
      <div class="tarjeta">
        <div class="d-flex gap-2 align-items-end mb-2">
          <div>
            <label class="form-label mb-1" for="fechaBusqueda">Buscar por fecha de registro</label>
            <input type="date" class="form-control" id="fechaBusqueda" value="{{ $hoy }}">
          </div>
          <button type="button" class="btn btn-primary" id="btnBuscar">Buscar</button>
        </div>
      </div>

      <div class="tarjeta">
        <h5 class="titulo mb-3">Pedidos de Tienda</h5>
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light"><tr><th>ID</th><th>Sucursal</th><th>Tamaño</th><th>Sabor</th><th>Cant.</th><th>P. unit.</th><th>Total</th><th>Entrega</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaT"><tr><td colspan="10" class="text-center">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>

      <div class="tarjeta">
        <h5 class="titulo mb-3">Pedidos de Cliente</h5>
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light"><tr><th>ID</th><th>Sucursal</th><th>Tamaño</th><th>Sabor</th><th>Cant.</th><th>P. unit.</th><th>Total</th><th>Entrega</th><th>Color</th><th>Dedicatoria</th><th>Detalles</th><th>Imagen</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaC"><tr><td colspan="14" class="text-center">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ===== Modal de edición (sirve para tienda y cliente) ===== --}}
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar pedido <span id="editId"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="text-muted mb-3" id="editResumen"></p>
        <label class="form-label" for="editCantidad">Cantidad</label>
        <input type="number" class="form-control mb-3" id="editCantidad" min="1" max="1000">
        <label class="form-label" for="editFecha">Fecha de entrega</label>
        <input type="date" class="form-control mb-3" id="editFecha" min="{{ $hoy }}">
        <div id="editClienteCampos" class="d-none">
          <label class="form-label" for="editColor">Color</label>
          <input type="text" class="form-control mb-3" id="editColor" maxlength="50">
          <label class="form-label" for="editDedicatoria">Dedicatoria</label>
          <input type="text" class="form-control mb-3" id="editDedicatoria" maxlength="500">
        </div>
        <label class="form-label" for="editDetalles">Detalles</label>
        <textarea class="form-control" id="editDetalles" rows="2" maxlength="500"></textarea>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success" id="btnGuardarEdicion">Guardar</button></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const CATALOGO = @json($catalogo);
const TAMANOS_OTRO = @json($tamanosOtro);
const TAMANOS_EXTRA_CLIENTE = @json($tamanosExtraCliente);
const MANANA = @json($manana);
const ES_ADMIN = @json($esAdmin);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const MAX_FOTO_BYTES = 5 * 1024 * 1024;

let carrito = [];
let registrados = { t: [], c: [] };
let edicion = null; // { tipo: 't' | 'c', id: number }

const el = (id) => document.getElementById(id);
const dinero = (n) => 'Q' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const URL_TIPO = { t: '/pedidos/normales', c: '/pedidos/clientes' };

function mostrarTab(idBoton) {
  bootstrap.Tab.getOrCreateInstance(el(idBoton)).show();
}

/* Llamada al servidor: devuelve { ok, data }. Si el cuerpo es FormData se manda como multipart. */
async function api(metodo, url, cuerpo) {
  const opciones = {
    method: metodo,
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
  };
  if (cuerpo instanceof FormData) {
    opciones.body = cuerpo;
  } else if (cuerpo !== undefined) {
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

/* ---------- Formularios (p = 't' tienda, 'c' cliente) ---------- */
function precioCatalogo(sabor, tamano) {
  const fila = (CATALOGO[sabor] || []).find((t) => t.tamano === tamano);
  return fila ? fila.precio : 0;
}

/* El precio lo escribe la tienda solo si el catálogo no tiene precio: sabor "Otro", Boda o Quince Años */
function necesitaPrecioManual(p) {
  const sabor = el(p + 'Sabor').value;
  const tamano = el(p + 'Tamano').value;
  if (!sabor) return false;
  if (sabor === 'Otro') return true;
  return tamano !== '' && precioCatalogo(sabor, tamano) <= 0;
}

function precioUnitario(p) {
  if (necesitaPrecioManual(p)) return parseFloat(el(p + 'PrecioManual').value) || 0;
  return precioCatalogo(el(p + 'Sabor').value, el(p + 'Tamano').value);
}

function actualizarPrecios(p) {
  const manual = necesitaPrecioManual(p);
  el(p + 'PrecioManualBox').classList.toggle('d-none', !manual);
  const unit = precioUnitario(p);
  const cantidad = parseInt(el(p + 'Cantidad').value, 10) || 0;
  el(p + 'PrecioUnitario').textContent = dinero(unit);
  el(p + 'PrecioTotal').textContent = dinero(unit * cantidad);
}

function llenarTamanos(p) {
  const sabor = el(p + 'Sabor').value;
  el(p + 'SaborOtro').classList.toggle('d-none', sabor !== 'Otro');

  let nombres = [];
  if (sabor === 'Otro') {
    nombres = TAMANOS_OTRO.slice();
  } else if (sabor) {
    nombres = (CATALOGO[sabor] || []).map((t) => t.tamano);
  }
  if (p === 'c' && sabor) nombres = nombres.concat(TAMANOS_EXTRA_CLIENTE);

  el(p + 'Tamano').innerHTML = '<option value="">Seleccionar...</option>' +
    nombres.map((n) => '<option value="' + esc(n) + '">' + esc(n) + '</option>').join('');
  actualizarPrecios(p);
}

function limpiarFormulario(p) {
  el(p + 'Sabor').value = '';
  el(p + 'SaborOtro').value = '';
  el(p + 'PrecioManual').value = '';
  el(p + 'Cantidad').value = '1';
  el(p + 'Fecha').value = MANANA;
  if (p === 'c') {
    el('cColor').value = '';
    el('cDedicatoria').value = '';
    el('cDetalles').value = '';
    el('cFoto').value = '';
    el('cFotoInfo').textContent = 'Se reduce automáticamente para subirla rápido.';
  }
  llenarTamanos(p);
}

/* Reduce la foto a un máximo de 1600 px para que suba rápido desde el celular. Si algo falla, se usa la original. */
async function reducirImagen(archivo) {
  try {
    const url = URL.createObjectURL(archivo);
    const img = await new Promise((resolve, reject) => {
      const i = new Image();
      i.onload = () => resolve(i);
      i.onerror = reject;
      i.src = url;
    });
    const escala = Math.min(1, 1600 / Math.max(img.width, img.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(img.width * escala);
    canvas.height = Math.round(img.height * escala);
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
    URL.revokeObjectURL(url);
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
    if (!blob || blob.size >= archivo.size) return archivo;
    return new File([blob], archivo.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
  } catch (e) {
    return archivo;
  }
}

/* ---------- Lista (carrito) ---------- */
async function agregar(p) {
  const esCliente = p === 'c';
  const sabor = el(p + 'Sabor').value;
  const esOtro = sabor === 'Otro';
  const saborOtro = el(p + 'SaborOtro').value.trim();
  const tamano = el(p + 'Tamano').value;
  const cantidad = parseInt(el(p + 'Cantidad').value, 10) || 0;
  const fecha = el(p + 'Fecha').value;
  const precio = precioUnitario(p);

  if (!sabor || !tamano || cantidad < 1 || !fecha) {
    alert('Por favor completa todos los campos requeridos');
    return;
  }
  if (esOtro && !saborOtro) {
    alert('Escribe el sabor personalizado');
    return;
  }
  if (!(precio > 0)) {
    alert(necesitaPrecioManual(p)
      ? 'Escribe el precio unitario manual'
      : 'El precio no es válido. Verifica que el sabor y tamaño tengan precio configurado.');
    return;
  }

  const item = {
    tipo: p,
    sucursal_id: ES_ADMIN ? el('sucursalAdmin').value : null,
    sucursal_nombre: ES_ADMIN ? el('sucursalAdmin').options[el('sucursalAdmin').selectedIndex].textContent : '',
    sabor: sabor,
    sabor_personalizado: esOtro ? saborOtro : '',
    tamano: tamano,
    cantidad: cantidad,
    fecha_entrega: fecha,
    precio: precio,
  };

  if (esCliente) {
    item.color = el('cColor').value.trim();
    item.dedicatoria = el('cDedicatoria').value.trim();
    item.detalles = el('cDetalles').value.trim();
    item.foto = null;
    const archivo = el('cFoto').files && el('cFoto').files[0];
    if (archivo) {
      if (archivo.size > MAX_FOTO_BYTES * 4) {
        alert('La foto es demasiado grande. Elige otra.');
        return;
      }
      item.foto = await reducirImagen(archivo);
      if (item.foto.size > MAX_FOTO_BYTES) {
        alert('La foto sigue pesando más de 5 MB. Elige otra.');
        return;
      }
    }
  }

  carrito.push(item);
  renderLista();
  limpiarFormulario(p);
  mostrarTab('tab-lista');
}

function extrasDe(item) {
  if (item.tipo !== 'c') return '<span class="text-muted">-</span>';
  const partes = [];
  if (item.color) partes.push('Color: ' + esc(item.color));
  if (item.dedicatoria) partes.push('Dedicatoria: ' + esc(item.dedicatoria));
  if (item.detalles) partes.push('Detalles: ' + esc(item.detalles));
  if (item.foto) partes.push('Con foto');
  return partes.length ? '<small>' + partes.join('<br>') + '</small>' : '<span class="text-muted">-</span>';
}

function renderLista() {
  el('contadorLista').textContent = carrito.length;
  const cuerpo = el('tablaLista');

  if (!carrito.length) {
    cuerpo.innerHTML = '<tr><td colspan="10" class="text-center text-muted">La lista está vacía</td></tr>';
    el('totalLista').textContent = dinero(0);
    return;
  }

  let totalGeneral = 0;
  cuerpo.innerHTML = carrito.map((p, i) => {
    const total = p.precio * p.cantidad;
    totalGeneral += total;
    const nombre = p.sabor === 'Otro' ? p.sabor_personalizado : p.sabor;
    const tipo = p.tipo === 'c' ? '<span class="badge bg-warning text-dark">Cliente</span>' : '<span class="badge bg-info text-dark">Tienda</span>';
    const sucursalTxt = p.sucursal_nombre ? '<div class="small text-muted">' + esc(p.sucursal_nombre) + '</div>' : '';
    return '<tr><td>' + (i + 1) + '</td><td>' + tipo + sucursalTxt + '</td><td>' + esc(nombre) + '</td><td>' + esc(p.tamano) + '</td><td>' + p.cantidad +
      '</td><td>' + esc(p.fecha_entrega) + '</td><td class="text-end">' + dinero(p.precio) +
      '</td><td class="text-end">' + dinero(total) + '</td><td>' + extrasDe(p) + '</td>' +
      '<td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar="' + i + '">Quitar</button></td></tr>';
  }).join('');
  el('totalLista').textContent = dinero(totalGeneral);
}

function limpiarLista() {
  if (carrito.length && !confirm('¿Vaciar la lista?')) return;
  carrito = [];
  renderLista();
}

/* Arma lo que se manda al servidor: JSON para tienda, multipart (con foto) para cliente */
function cuerpoDe(item) {
  if (item.tipo === 't') {
    const cuerpo = {
      sabor: item.sabor, sabor_personalizado: item.sabor_personalizado, tamano: item.tamano,
      cantidad: item.cantidad, fecha_entrega: item.fecha_entrega, precio: item.precio,
    };
    if (item.sucursal_id) cuerpo.sucursal_id = item.sucursal_id;
    return cuerpo;
  }
  const fd = new FormData();
  ['sabor', 'sabor_personalizado', 'tamano', 'cantidad', 'fecha_entrega', 'precio', 'color', 'dedicatoria', 'detalles']
    .forEach((campo) => fd.append(campo, item[campo] == null ? '' : item[campo]));
  if (item.sucursal_id) fd.append('sucursal_id', item.sucursal_id);
  if (item.foto) fd.append('foto', item.foto, item.foto.name);
  return fd;
}

/* Registra uno por uno; los que se guardan salen de la lista y los que fallan se quedan para reintentar */
async function registrarTodos() {
  if (!carrito.length) {
    alert('No hay pedidos en la lista');
    return;
  }
  if (!confirm('¿Registrar todos los pedidos?')) return;

  const pendientes = carrito.slice();
  const fallidos = [];
  const errores = [];
  let exitosos = 0;

  for (let i = 0; i < pendientes.length; i++) {
    const item = pendientes[i];
    try {
      const r = await api('POST', URL_TIPO[item.tipo], cuerpoDe(item));
      if (r.ok) {
        exitosos++;
      } else {
        fallidos.push(item);
        errores.push('Pedido ' + (i + 1) + ': ' + mensajeDeError(r.data));
      }
    } catch (e) {
      fallidos.push(item);
      errores.push('Pedido ' + (i + 1) + ': sin conexión con el servidor');
    }
  }

  carrito = fallidos;
  renderLista();

  if (fallidos.length) {
    alert('Registrados: ' + exitosos + '. Con error: ' + fallidos.length + '\n\n' + errores.join('\n') +
      '\n\nLos pedidos con error siguen en tu lista.');
  } else {
    alert('Se registraron ' + exitosos + ' pedido(s) correctamente');
  }

  if (exitosos) {
    el('fechaBusqueda').value = new Date().toLocaleDateString('en-CA');
    await cargarRegistrados();
    if (!fallidos.length) mostrarTab('tab-registrados');
  }
}

/* ---------- Registrados ---------- */
const estadoBadge = (p) => p.editable
  ? '<span class="badge bg-success">Editable</span>'
  : '<span class="badge bg-secondary">Bloqueado</span>';

const botonesDe = (p, tipo) => p.editable
  ? '<button type="button" class="btn btn-sm btn-primary me-1" data-editar="' + p.id + '" data-tipo="' + tipo + '">Editar</button>' +
    '<button type="button" class="btn btn-sm btn-danger" data-eliminar="' + p.id + '" data-tipo="' + tipo + '">Eliminar</button>'
  : '<span class="text-muted">-</span>';

const celdaTexto = (t) => t ? '<span class="d-inline-block recorte" title="' + esc(t) + '">' + esc(t) + '</span>' : '<span class="text-muted">-</span>';

function filaTienda(p) {
  return '<tr><td><strong>' + p.id + '</strong></td><td><span class="badge bg-primary">' + esc(p.sucursal) + '</span></td>' +
    '<td>' + esc(p.tamano) + '</td><td>' + esc(p.sabor) + '</td><td class="text-center">' + p.cantidad + '</td>' +
    '<td class="text-end">' + dinero(p.precio) + '</td><td class="text-end"><strong>' + dinero(p.total) + '</strong></td>' +
    '<td class="text-center">' + esc(p.fecha_entrega || '') + '</td><td class="text-center">' + estadoBadge(p) + '</td>' +
    '<td class="text-center text-nowrap">' + botonesDe(p, 't') + '</td></tr>';
}

function filaCliente(p) {
  const imagen = p.imagen
    ? '<a href="' + esc(p.imagen) + '" target="_blank" rel="noopener"><img class="miniatura" src="' + esc(p.imagen) + '" alt="Foto del pedido"></a>'
    : '<span class="text-muted">-</span>';
  const color = p.color ? '<span class="badge bg-light text-dark border">' + esc(p.color) + '</span>' : '<span class="text-muted">-</span>';
  return '<tr><td><strong>' + p.id + '</strong></td><td><span class="badge bg-primary">' + esc(p.sucursal) + '</span></td>' +
    '<td>' + esc(p.tamano) + '</td><td>' + esc(p.sabor) + '</td><td class="text-center">' + p.cantidad + '</td>' +
    '<td class="text-end">' + dinero(p.precio) + '</td><td class="text-end"><strong>' + dinero(p.total) + '</strong></td>' +
    '<td class="text-center">' + esc(p.fecha_entrega || '') + '</td><td class="text-center">' + color + '</td>' +
    '<td>' + celdaTexto(p.dedicatoria) + '</td><td>' + celdaTexto(p.detalles) + '</td>' +
    '<td class="text-center">' + imagen + '</td><td class="text-center">' + estadoBadge(p) + '</td>' +
    '<td class="text-center text-nowrap">' + botonesDe(p, 'c') + '</td></tr>';
}

async function cargarTabla(tipo, idCuerpo, columnas, filaFn) {
  const fecha = el('fechaBusqueda').value;
  const cuerpo = el(idCuerpo);
  try {
    const sucursalParam = ES_ADMIN ? '&sucursal_id=' + encodeURIComponent(el('sucursalAdmin').value) : '';
    const r = await api('GET', URL_TIPO[tipo] + '?fecha=' + encodeURIComponent(fecha) + sucursalParam);
    if (!r.ok) throw new Error(mensajeDeError(r.data));
    registrados[tipo] = r.data.pedidos || [];
  } catch (e) {
    cuerpo.innerHTML = '<tr><td colspan="' + columnas + '" class="text-center text-danger">Error al cargar pedidos</td></tr>';
    return;
  }
  cuerpo.innerHTML = registrados[tipo].length
    ? registrados[tipo].map(filaFn).join('')
    : '<tr><td colspan="' + columnas + '" class="text-center text-muted">No hay pedidos registrados en esta fecha</td></tr>';
}

async function cargarRegistrados() {
  await Promise.all([
    cargarTabla('t', 'tablaT', 10, filaTienda),
    cargarTabla('c', 'tablaC', 14, filaCliente),
  ]);
}

/* ---------- Edición ---------- */
function abrirEdicion(tipo, id) {
  const p = registrados[tipo].find((x) => x.id === id);
  if (!p) return;
  edicion = { tipo: tipo, id: id };
  el('editId').textContent = '#' + id;
  el('editResumen').textContent = p.sabor + ' · ' + p.tamano + (tipo === 'c' ? ' (cliente)' : ' (tienda)');
  el('editCantidad').value = p.cantidad;
  el('editFecha').value = p.fecha_entrega || '';
  el('editDetalles').value = p.detalles || '';
  el('editClienteCampos').classList.toggle('d-none', tipo !== 'c');
  el('editColor').value = p.color || '';
  el('editDedicatoria').value = p.dedicatoria || '';
  bootstrap.Modal.getOrCreateInstance(el('modalEditar')).show();
}

async function guardarEdicion() {
  if (!edicion) return;
  const cuerpo = {
    cantidad: parseInt(el('editCantidad').value, 10) || 0,
    fecha_entrega: el('editFecha').value,
    detalles: el('editDetalles').value,
  };
  if (edicion.tipo === 'c') {
    cuerpo.color = el('editColor').value;
    cuerpo.dedicatoria = el('editDedicatoria').value;
  }
  const r = await api('PUT', URL_TIPO[edicion.tipo] + '/' + edicion.id, cuerpo);
  if (r.ok) {
    bootstrap.Modal.getOrCreateInstance(el('modalEditar')).hide();
    await cargarRegistrados();
  } else {
    alert(mensajeDeError(r.data));
  }
}

async function eliminar(tipo, id) {
  if (!confirm('¿Eliminar este pedido? Esta acción no se puede deshacer.')) return;
  const r = await api('DELETE', URL_TIPO[tipo] + '/' + id);
  if (r.ok) {
    await cargarRegistrados();
  } else {
    alert(mensajeDeError(r.data));
  }
}

/* ---------- Eventos ---------- */
['t', 'c'].forEach((p) => {
  el(p + 'Sabor').addEventListener('change', () => llenarTamanos(p));
  el(p + 'Tamano').addEventListener('change', () => actualizarPrecios(p));
  el(p + 'Cantidad').addEventListener('input', () => actualizarPrecios(p));
  el(p + 'PrecioManual').addEventListener('input', () => actualizarPrecios(p));
});
document.querySelectorAll('[data-agregar]').forEach((b) => {
  b.addEventListener('click', () => agregar(b.getAttribute('data-agregar')));
});
el('cFoto').addEventListener('change', () => {
  const f = el('cFoto').files && el('cFoto').files[0];
  el('cFotoInfo').textContent = f ? f.name : 'Se reduce automáticamente para subirla rápido.';
});
el('btnLimpiar').addEventListener('click', limpiarLista);
el('btnRegistrar').addEventListener('click', registrarTodos);
el('btnBuscar').addEventListener('click', cargarRegistrados);
el('btnGuardarEdicion').addEventListener('click', guardarEdicion);
el('tablaLista').addEventListener('click', (ev) => {
  const b = ev.target.closest('[data-quitar]');
  if (!b) return;
  carrito.splice(parseInt(b.getAttribute('data-quitar'), 10), 1);
  renderLista();
});
['tablaT', 'tablaC'].forEach((idCuerpo) => {
  el(idCuerpo).addEventListener('click', (ev) => {
    const e = ev.target.closest('[data-editar]');
    if (e) return abrirEdicion(e.getAttribute('data-tipo'), parseInt(e.getAttribute('data-editar'), 10));
    const d = ev.target.closest('[data-eliminar]');
    if (d) return eliminar(d.getAttribute('data-tipo'), parseInt(d.getAttribute('data-eliminar'), 10));
  });
});
el('tab-registrados').addEventListener('shown.bs.tab', cargarRegistrados);
if (ES_ADMIN) el('sucursalAdmin').addEventListener('change', cargarRegistrados);

renderLista();
cargarRegistrados();
</script>
@endpush
