<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\ControlInventario;
use App\Models\GastoDiario;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Resumen para el administrador: qué sucursales ya capturaron su control diario de una fecha
 * y quién fue la última persona que lo guardó. Las rutas van dentro del grupo con middleware rol:admin.
 */
class AdminControlDiarioController extends Controller
{
    public function index()
    {
        return view('admin.control', [
            'hoy' => today()->toDateString(),
            'ayer' => today()->subDay()->toDateString(),
        ]);
    }

    public function resumen(Request $request)
    {
        $fecha = Carbon::parse($request->validate(['fecha' => ['required', 'date']])['fecha'])->toDateString();

        $sucursales = Sucursal::where('activa', true)->orderBy('id')->get();

        // Cada vez que alguien guarda la hoja queda un registro en el historial (el más reciente primero)
        $guardados = Auditoria::where('recurso', 'control_diario')
            ->where('accion', 'guardar')
            ->where('datos->fecha', $fecha)
            ->orderByDesc('id')
            ->get()
            ->groupBy('sucursal_id');

        // Respaldo para capturas hechas antes de existir el historial
        $conteos = ControlInventario::where('fecha', $fecha)->orderByDesc('updated_at')->get()->groupBy('sucursal_id');
        $gastos = GastoDiario::where('fecha', $fecha)->get()->keyBy('sucursal_id');

        $filas = $sucursales->map(function (Sucursal $s) use ($guardados, $conteos, $gastos) {
            $historial = $guardados->get($s->id);
            $ultimoConteo = $conteos->get($s->id)?->first();
            $gasto = $gastos->get($s->id);

            $quien = $historial?->first()->usuario
                ?? ($ultimoConteo?->capturado_por ? User::find($ultimoConteo->capturado_por)?->email : null);

            $cuando = $historial?->first()->created_at ?? $ultimoConteo?->updated_at ?? $gasto?->updated_at;

            return [
                'sucursal_id' => $s->id,
                'sucursal' => $s->nombre,
                'capturado' => (bool) ($historial || $ultimoConteo || $gasto),
                'guardo' => $quien,
                'ultima' => $cuando?->format('Y-m-d H:i'),
                'veces' => $historial?->count() ?? 0,
                'total_gastos' => round((float) ($gasto?->total_gastos ?? 0), 2),
            ];
        })->values();

        return response()->json([
            'fecha' => $fecha,
            'filas' => $filas,
            'capturadas' => $filas->where('capturado', true)->count(),
            'total' => $filas->count(),
        ]);
    }
}
