@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
<div class="container pb-5" style="max-width: 860px;">
  <div class="tarjeta">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h4 class="mb-0">Productos <span class="badge bg-primary">{{ $productos->count() }}</span></h4>
      <input type="search" class="form-control" id="buscar" placeholder="Buscar producto..." style="max-width: 250px;">
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle tabla-productos" id="tabla">
        <thead><tr><th>No</th><th>Producto</th><th>Categoría</th><th class="text-end">Precio</th></tr></thead>
        <tbody>
          @foreach ($productos as $p)
          <tr>
            <td>{{ $p->numero_orden }}</td>
            <td>{{ $p->nombre }}</td>
            <td><span class="badge {{ $p->categoria === 'pastel' ? 'bg-primary' : 'bg-info text-dark' }}">{{ $p->categoria === 'pastel' ? 'Pastel' : 'Otro' }}</span></td>
            <td class="text-end">Q {{ number_format($p->precio, 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('buscar').addEventListener('input', (ev) => {
  const texto = ev.target.value.trim().toLowerCase();
  document.querySelectorAll('#tabla tbody tr').forEach((fila) => {
    fila.style.display = !texto || fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
  });
});
</script>
@endpush
