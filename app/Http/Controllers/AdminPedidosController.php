<?php

namespace App\Http\Controllers;

use App\Models\PedidoCliente;
use App\Models\PedidoNormal;
use App\Models\Precio;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Panel de administración: el admin ve los pedidos de TODAS las sucursales,
 * filtra por fechas y sucursal, y puede editar o eliminar cualquier pedido.
 * (Las rutas de este controlador van dentro del grupo con middleware rol:admin.)
 */
class AdminPedidosController extends Controller
{
    public function index()
    {
        $hoy = today();

        return view('admin.index', [
            'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
            // Sugerencias para el formulario de edición
            'sabores' => Precio::orderBy('id')->pluck('sabor')->unique()->values(),
            'tamanos' => Precio::orderBy('id')->pluck('tamano')->merge(['Boda', 'Quince Años'])->unique()->values(),
            // Atajos de fecha (semana de lunes a domingo), calculados con la zona horaria de la app
            'rangos' => [
                'hoy' => [$hoy->toDateString(), $hoy->toDateString()],
                'semana' => [
                    $hoy->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                    $hoy->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                ],
                'mes' => [$hoy->copy()->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString()],
            ],
            'hoy' => $hoy->toDateString(),
        ]);
    }

    /** Pedidos filtrados por fecha de REGISTRO y, opcionalmente, por sucursal. */
    private function filtrar(Request $request, string $modelo): Builder
    {
        $filtros = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
        ]);

        return $modelo::with('sucursal')
            ->whereDate('fecha', '>=', $filtros['fecha_inicio'])
            ->whereDate('fecha', '<=', $filtros['fecha_fin'])
            ->when($filtros['sucursal_id'] ?? null, fn ($q, $id) => $q->where('sucursal_id', $id))
            ->orderByDesc('fecha')->orderByDesc('id');
    }

    private function resumen($pedidos): array
    {
        return ['pedidos' => $pedidos->count(), 'ingresos' => round((float) $pedidos->sum('total'), 2)];
    }

    public function normales(Request $request)
    {
        $pedidos = $this->filtrar($request, PedidoNormal::class)->get();

        return response()->json([
            'pedidos' => $pedidos->map(fn (PedidoNormal $p) => [
                'id' => $p->id,
                'sucursal_id' => $p->sucursal_id,
                'sucursal' => $p->sucursal?->nombre,
                'sabor' => $p->sabor,
                'tamano' => $p->tamano,
                'cantidad' => $p->cantidad,
                'precio' => (float) $p->precio,
                'total' => (float) $p->total,
                'fecha' => $p->fecha?->format('Y-m-d H:i'),
                'fecha_entrega' => $p->fecha_entrega?->toDateString(),
                'detalles' => $p->detalles,
            ])->values(),
            'resumen' => $this->resumen($pedidos),
        ]);
    }

    public function clientes(Request $request)
    {
        $pedidos = $this->filtrar($request, PedidoCliente::class)->get();

        return response()->json([
            'pedidos' => $pedidos->map(fn (PedidoCliente $p) => [
                'id' => $p->id,
                'sucursal_id' => $p->sucursal_id,
                'sucursal' => $p->sucursal?->nombre,
                'sabor' => $p->sabor,
                'tamano' => $p->tamano,
                'cantidad' => $p->cantidad,
                'precio' => (float) $p->precio,
                'total' => (float) $p->total,
                'fecha' => $p->fecha?->format('Y-m-d H:i'),
                'fecha_entrega' => $p->fecha_entrega?->toDateString(),
                'color' => $p->color,
                'dedicatoria' => $p->dedicatoria,
                'detalles' => $p->detalles,
                'imagen' => $p->foto_path ? asset('storage/uploads/' . $p->foto_path) : null,
            ])->values(),
            'resumen' => $this->resumen($pedidos),
        ]);
    }

    private function reglasPedido(): array
    {
        return [
            'sabor' => ['required', 'string', 'max:100'],
            'tamano' => ['required', 'string', 'max:50'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000'],
            'precio' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'fecha_entrega' => ['nullable', 'date'],
            'detalles' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function updateNormal(Request $request, PedidoNormal $pedido)
    {
        $datos = $request->validate($this->reglasPedido());

        $pedido->update([
            'sabor' => $datos['sabor'],
            // si el pedido era de sabor personalizado, sigue marcado como tal con el nuevo nombre
            'sabor_personalizado' => $pedido->sabor_personalizado !== null ? $datos['sabor'] : null,
            'tamano' => $datos['tamano'],
            'cantidad' => $datos['cantidad'],
            'precio' => $datos['precio'],
            'sucursal_id' => $datos['sucursal_id'],
            'fecha_entrega' => $datos['fecha_entrega'] ?? null,
            'detalles' => $datos['detalles'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Pedido actualizado correctamente']);
    }

    public function destroyNormal(PedidoNormal $pedido)
    {
        $pedido->delete();

        return response()->json(['success' => true, 'message' => "Pedido #{$pedido->id} eliminado correctamente"]);
    }

    public function updateCliente(Request $request, PedidoCliente $pedido)
    {
        $datos = $request->validate($this->reglasPedido() + [
            'color' => ['nullable', 'string', 'max:50'],
            'dedicatoria' => ['nullable', 'string', 'max:500'],
        ]);

        $pedido->update([
            'sabor' => $datos['sabor'],
            'sabor_personalizado' => $pedido->sabor_personalizado !== null ? $datos['sabor'] : null,
            'tamano' => $datos['tamano'],
            'cantidad' => $datos['cantidad'],
            'precio' => $datos['precio'],
            'sucursal_id' => $datos['sucursal_id'],
            'fecha_entrega' => $datos['fecha_entrega'] ?? null,
            'color' => $datos['color'] ?? null,
            'dedicatoria' => $datos['dedicatoria'] ?? null,
            'detalles' => $datos['detalles'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Pedido actualizado correctamente']);
    }

    public function destroyCliente(PedidoCliente $pedido)
    {
        if ($pedido->foto_path) {
            Storage::disk('public')->delete('uploads/' . $pedido->foto_path);
        }
        $pedido->delete();

        return response()->json(['success' => true, 'message' => "Pedido #{$pedido->id} eliminado correctamente"]);
    }
}
