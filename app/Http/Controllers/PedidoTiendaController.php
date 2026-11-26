<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PedidosDeSucursal;
use App\Models\PedidoNormal;
use App\Models\Precio;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PedidoTiendaController extends Controller
{
    use PedidosDeSucursal;

    /** Pantalla de pedidos (formularios de tienda y cliente, lista y registrados). */
    public function index(Request $request)
    {
        $sucursal = $this->sucursalDelUsuario($request, true);
        $esAdmin = $request->user()->rol === 'admin';

        // sabor => [{tamano, precio}, ...] en el orden en que están en la tabla de precios
        $catalogo = Precio::orderBy('id')->get()->groupBy('sabor')->map(
            fn ($filas) => $filas->map(
                fn ($p) => ['tamano' => $p->tamano, 'precio' => (float) $p->precio]
            )->values()
        );

        return view('pedidos.index', [
            'sucursal' => $sucursal,
            'esAdmin' => $esAdmin,
            'sucursales' => $esAdmin ? Sucursal::where('activa', true)->orderBy('nombre')->get(['id', 'nombre']) : collect(),
            'catalogo' => $catalogo,
            'tamanosOtro' => self::TAMANOS_OTRO,
            'tamanosExtraCliente' => self::TAMANOS_EXTRA_CLIENTE,
            'hoy' => today()->toDateString(),
            'manana' => today()->addDay()->toDateString(),
        ]);
    }

    /** Pedidos registrados en una fecha (por defecto hoy) de la sucursal del usuario. */
    public function listar(Request $request)
    {
        $sucursal = $this->sucursalDelUsuario($request);
        $fecha = $request->validate(['fecha' => ['nullable', 'date']])['fecha'] ?? today()->toDateString();

        $pedidos = PedidoNormal::where('sucursal_id', $sucursal->id)
            ->whereDate('fecha', $fecha)
            ->orderByDesc('fecha')->orderByDesc('id')
            ->get()
            ->map(fn (PedidoNormal $p) => [
                'id' => $p->id,
                'sucursal' => $sucursal->nombre,
                'tamano' => $p->tamano,
                'sabor' => $p->sabor,
                'cantidad' => $p->cantidad,
                'precio' => (float) $p->precio,
                'total' => (float) $p->total,
                'fecha_entrega' => $p->fecha_entrega?->toDateString(),
                'detalles' => $p->detalles,
                'editable' => $this->esEditable($p),
            ])->values();

        return response()->json(['pedidos' => $pedidos]);
    }

    /** Registra UN pedido (la pantalla manda uno por uno los de la lista). */
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
            'detalles' => ['nullable', 'string', 'max:500'],
        ]);

        $esOtro = mb_strtolower($datos['sabor']) === 'otro';

        if ($esOtro) {
            // Sabor personalizado: el precio lo escribe la tienda
            $saborReal = trim((string) ($datos['sabor_personalizado'] ?? ''));
            $precio = (float) ($datos['precio'] ?? 0);

            if ($saborReal === '') {
                throw ValidationException::withMessages(['sabor_personalizado' => 'Escribe el sabor personalizado.']);
            }
            if ($precio <= 0) {
                throw ValidationException::withMessages(['precio' => 'Escribe el precio manual.']);
            }
            if (! in_array($datos['tamano'], self::TAMANOS_OTRO, true)) {
                throw ValidationException::withMessages(['tamano' => 'Tamaño inválido.']);
            }
        } else {
            // Sabor del catálogo: el precio siempre sale de la tabla de precios, no de lo que mande el navegador
            $saborReal = $datos['sabor'];
            $precio = (float) Precio::where('sabor', $datos['sabor'])
                ->where('tamano', $datos['tamano'])->value('precio');

            if ($precio <= 0) {
                throw ValidationException::withMessages([
                    'precio' => "No hay precio para {$datos['sabor']} {$datos['tamano']}. Usa la opción «Otro» para un precio manual.",
                ]);
            }
        }

        $pedido = PedidoNormal::create([
            'sucursal_id' => $sucursal->id,
            'sabor' => $saborReal,
            'sabor_personalizado' => $esOtro ? $saborReal : null,
            'tamano' => $datos['tamano'],
            'cantidad' => $datos['cantidad'],
            'precio' => $precio,
            'fecha_entrega' => $datos['fecha_entrega'],
            'detalles' => $datos['detalles'] ?? null,
        ]);

        return response()->json(['success' => true, 'id' => $pedido->id, 'message' => 'Pedido registrado'], 201);
    }

    public function update(Request $request, PedidoNormal $pedido)
    {
        $this->autorizar($request, $pedido);
        abort_unless($this->esEditable($pedido), 422, 'No se pueden editar pedidos con fecha de entrega pasada.');

        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000'],
            'fecha_entrega' => ['required', 'date', 'after_or_equal:today'],
            'detalles' => ['nullable', 'string', 'max:500'],
        ]);

        $pedido->update([
            'cantidad' => $datos['cantidad'],
            'fecha_entrega' => $datos['fecha_entrega'],
            'detalles' => array_key_exists('detalles', $datos) ? $datos['detalles'] : $pedido->detalles,
        ]);

        return response()->json(['success' => true, 'message' => 'Pedido actualizado correctamente']);
    }

    public function destroy(Request $request, PedidoNormal $pedido)
    {
        $this->autorizar($request, $pedido);
        abort_unless($this->esEditable($pedido), 422, 'No se pueden eliminar pedidos con fecha de entrega pasada.');

        $pedido->delete();

        return response()->json(['success' => true, 'message' => 'Pedido eliminado correctamente']);
    }
}
