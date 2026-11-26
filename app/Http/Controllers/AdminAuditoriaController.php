<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Consulta del historial de acciones. Las rutas van dentro del grupo con middleware rol:admin. */
class AdminAuditoriaController extends Controller
{
    private const ACCIONES = ['login', 'login_fallido', 'login_bloqueado', 'logout', 'crear', 'actualizar', 'eliminar', 'guardar'];

    private const RECURSOS = ['sesion', 'pedido_normal', 'pedido_cliente', 'precio', 'control_diario'];

    public function index()
    {
        $hoy = today();
        $sucursales = Sucursal::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.auditoria', [
            'sucursales' => $sucursales,
            'mapaSucursales' => $sucursales->pluck('nombre', 'id'),
            'hoy' => $hoy->toDateString(),
            'rangos' => [
                'hoy' => [$hoy->toDateString(), $hoy->toDateString()],
                'semana' => [
                    $hoy->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                    $hoy->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                ],
                'mes' => [$hoy->copy()->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString()],
            ],
        ]);
    }

    public function lista(Request $request)
    {
        $f = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'usuario' => ['nullable', 'string', 'max:100'],
            'accion' => ['nullable', Rule::in(self::ACCIONES)],
            'recurso' => ['nullable', Rule::in(self::RECURSOS)],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
        ]);

        $pagina = Auditoria::with('sucursal')
            ->whereDate('created_at', '>=', $f['fecha_inicio'])
            ->whereDate('created_at', '<=', $f['fecha_fin'])
            ->when($f['usuario'] ?? null, fn ($q, $u) => $q->where('usuario', 'like', '%' . $u . '%'))
            ->when($f['accion'] ?? null, fn ($q, $a) => $q->where('accion', $a))
            ->when($f['recurso'] ?? null, fn ($q, $r) => $q->where('recurso', $r))
            ->when($f['sucursal_id'] ?? null, fn ($q, $id) => $q->where('sucursal_id', $id))
            ->orderByDesc('id')
            ->paginate(50);

        return response()->json([
            'registros' => $pagina->getCollection()->map(fn (Auditoria $a) => [
                'id' => $a->id,
                'fecha' => $a->created_at?->format('Y-m-d H:i:s'),
                'usuario' => $a->usuario,
                'accion' => $a->accion,
                'recurso' => $a->recurso,
                'recurso_id' => $a->recurso_id,
                'sucursal' => $a->sucursal?->nombre,
                'ip' => $a->ip,
                'datos' => $a->datos,
            ])->values(),
            'pagina' => $pagina->currentPage(),
            'paginas' => $pagina->lastPage(),
            'total' => $pagina->total(),
        ]);
    }
}
