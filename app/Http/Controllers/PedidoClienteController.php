<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PedidosDeSucursal;
use App\Models\PedidoCliente;
use App\Models\Precio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PedidoClienteController extends Controller
{
    use PedidosDeSucursal;

    private function urlFoto(?string $nombre): ?string
    {
        return $nombre ? asset('storage/uploads/' . $nombre) : null;
    }

    /** Pedidos de cliente registrados en una fecha (por defecto hoy) de la sucursal del usuario. */
    public function listar(Request $request)
    {
        $sucursal = $this->sucursalDelUsuario($request);
        $fecha = $request->validate(['fecha' => ['nullable', 'date']])['fecha'] ?? today()->toDateString();

        $pedidos = PedidoCliente::where('sucursal_id', $sucursal->id)
            ->whereDate('fecha', $fecha)
            ->orderByDesc('fecha')->orderByDesc('id')
            ->get()
            ->map(fn (PedidoCliente $p) => [
                'id' => $p->id,
                'sucursal' => $sucursal->nombre,
                'tamano' => $p->tamano,
                'sabor' => $p->sabor,
                'cantidad' => $p->cantidad,
                'precio' => (float) $p->precio,
                'total' => (float) $p->total,
                'fecha_entrega' => $p->fecha_entrega?->toDateString(),
                'color' => $p->color,
                'dedicatoria' => $p->dedicatoria,
                'detalles' => $p->detalles,
                'imagen' => $this->urlFoto($p->foto_path),
                'editable' => $this->esEditable($p),
            ])->values();

        return response()->json(['pedidos' => $pedidos]);
    }

    /** Registra UN pedido de cliente (con foto opcional). Llega como multipart/form-data. */
    public function store(Request $request)
    {
        $sucursal = $this->sucursalDelUsuario($request);

        $datos = $request->validate([
            'sabor' => ['required', 'string', 'max:100'],
            'sabor_personalizado' => ['nullable', 'string', 'max:100'],
            'tamano' => ['required', 'string', 'max:50'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000'],
            'fecha_entrega' => ['required', 'date', 'after_or_equal:today'],
            'precio' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'color' => ['nullable', 'string', 'max:50'],
            'dedicatoria' => ['nullable', 'string', 'max:500'],
            'detalles' => ['nullable', 'string', 'max:500'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'foto.image' => 'La foto debe ser una imagen (jpg, png o webp).',
            'foto.mimes' => 'La foto debe ser jpg, png o webp.',
            'foto.max' => 'La foto pesa más de 5 MB.',
            'foto.uploaded' => 'No se pudo subir la foto (puede ser demasiado grande).',
        ]);

        $esOtro = mb_strtolower($datos['sabor']) === 'otro';
        $tamanosPermitidos = [...self::TAMANOS_OTRO, ...self::TAMANOS_EXTRA_CLIENTE];

        if ($esOtro) {
            $saborReal = trim((string) ($datos['sabor_personalizado'] ?? ''));
            if ($saborReal === '') {
                throw ValidationException::withMessages(['sabor_personalizado' => 'Escribe el sabor personalizado.']);
            }
            $precioCatalogo = 0.0;
        } else {
            $saborReal = $datos['sabor'];
            $precioCatalogo = (float) Precio::where('sabor', $datos['sabor'])
                ->where('tamano', $datos['tamano'])->value('precio');
        }

        if ($precioCatalogo > 0) {
            // Precio del catálogo: siempre manda el del servidor
            $precio = $precioCatalogo;
        } else {
            // Sin precio en el catálogo (sabor "Otro", Boda o Quince Años): la tienda escribe el precio
            $precio = (float) ($datos['precio'] ?? 0);

            if (! in_array($datos['tamano'], $tamanosPermitidos, true)) {
                throw ValidationException::withMessages(['tamano' => 'Tamaño inválido.']);
            }
            if ($precio <= 0) {
                throw ValidationException::withMessages(['precio' => 'Escribe el precio manual para este pedido.']);
            }
        }

        $fotoNombre = null;
        if ($request->hasFile('foto')) {
            $archivo = $request->file('foto');
            $fotoNombre = now()->format('YmdHis') . '_' . Str::random(6) . '.' . $archivo->extension();
            $archivo->storeAs('uploads', $fotoNombre, 'public');
        }

        try {
            $pedido = PedidoCliente::create([
                'sucursal_id' => $sucursal->id,
                'sabor' => $saborReal,
                'sabor_personalizado' => $esOtro ? $saborReal : null,
                'tamano' => $datos['tamano'],
                'cantidad' => $datos['cantidad'],
                'precio' => $precio,
                'fecha_entrega' => $datos['fecha_entrega'],
                'color' => $datos['color'] ?? null,
                'dedicatoria' => $datos['dedicatoria'] ?? null,
                'detalles' => $datos['detalles'] ?? null,
                'foto_path' => $fotoNombre,
            ]);
        } catch (\Throwable $e) {
            if ($fotoNombre) {
                Storage::disk('public')->delete('uploads/' . $fotoNombre); // no dejar fotos huérfanas
            }
            throw $e;
        }

        return response()->json(['success' => true, 'id' => $pedido->id, 'message' => 'Pedido de cliente registrado'], 201);
    }

    public function update(Request $request, PedidoCliente $pedido)
    {
        $this->autorizar($request, $pedido);
        abort_unless($this->esEditable($pedido), 422, 'No se pueden editar pedidos con fecha de entrega pasada.');

        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000'],
            'fecha_entrega' => ['required', 'date', 'after_or_equal:today'],
            'color' => ['nullable', 'string', 'max:50'],
            'dedicatoria' => ['nullable', 'string', 'max:500'],
            'detalles' => ['nullable', 'string', 'max:500'],
        ]);

        $pedido->update([
            'cantidad' => $datos['cantidad'],
            'fecha_entrega' => $datos['fecha_entrega'],
            'color' => array_key_exists('color', $datos) ? $datos['color'] : $pedido->color,
            'dedicatoria' => array_key_exists('dedicatoria', $datos) ? $datos['dedicatoria'] : $pedido->dedicatoria,
            'detalles' => array_key_exists('detalles', $datos) ? $datos['detalles'] : $pedido->detalles,
        ]);

        return response()->json(['success' => true, 'message' => 'Pedido actualizado correctamente']);
    }

    public function destroy(Request $request, PedidoCliente $pedido)
    {
        $this->autorizar($request, $pedido);
        abort_unless($this->esEditable($pedido), 422, 'No se pueden eliminar pedidos con fecha de entrega pasada.');

        if ($pedido->foto_path) {
            Storage::disk('public')->delete('uploads/' . $pedido->foto_path);
        }
        $pedido->delete();

        return response()->json(['success' => true, 'message' => 'Pedido eliminado correctamente']);
    }
}
