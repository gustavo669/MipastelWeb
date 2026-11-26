<?php

namespace App\Http\Controllers;

use App\Models\AnticipoPastel;
use App\Models\Auditoria;
use App\Models\ControlInventario;
use App\Models\GastoDiario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Support\ControlDiarioCambios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlDiarioController extends Controller
{
    private const CAMPOS_CONTEO = [
        'inv_inicial', 'envio', 'pasteles_porcion', 'gastos_familia', 'mal_estado', 'inv_final',
    ];

    private const CAMPOS_GASTO = [
        'gastos_compras', 'desc_degustacion', 'servilletas', 'tenedores', 'platos',
        'donaciones', 'consumo_empleado', 'otros_gastos', 'otros_ingresos', 'caja_chica_reportada',
    ];

    /** El admin elige la sucursal; un usuario de sucursal solo puede usar la suya. */
    private function sucursalActual(Request $request): Sucursal
    {
        $user = $request->user();

        if ($user->rol === 'admin') {
            $id = $request->input('sucursal_id') ?: Sucursal::orderBy('nombre')->value('id');
            return Sucursal::findOrFail($id);
        }

        return $user->sucursal ?? abort(403, 'Tu usuario no tiene sucursal asignada.');
    }

    public function edit(Request $request)
    {
        $request->validate(['fecha' => ['nullable', 'date']]);

        $sucursal = $this->sucursalActual($request);
        $fecha = $request->input('fecha', now()->toDateString());

        $productos = Producto::where('activo', true)->orderBy('numero_orden')->get();

        $hoy = ControlInventario::where('sucursal_id', $sucursal->id)
            ->where('fecha', $fecha)->get()->keyBy('producto_id');

        // Si ese día aún no hay captura, el inventario inicial sale del
        // inventario final del último día capturado antes de esa fecha.
        $anterior = collect();
        if ($hoy->isEmpty()) {
            $fechaAnterior = ControlInventario::where('sucursal_id', $sucursal->id)
                ->where('fecha', '<', $fecha)->max('fecha');

            if ($fechaAnterior) {
                $anterior = ControlInventario::where('sucursal_id', $sucursal->id)
                    ->where('fecha', $fechaAnterior)->get()->keyBy('producto_id');
            }
        }

        $gastos = GastoDiario::firstOrNew(['sucursal_id' => $sucursal->id, 'fecha' => $fecha]);

        $anticipos = AnticipoPastel::where('sucursal_id', $sucursal->id)
            ->where('fecha', $fecha)->orderBy('id')->get()->values();

        // Quién guardó por última vez la hoja de ese día
        $ultima = ControlInventario::where('sucursal_id', $sucursal->id)
            ->where('fecha', $fecha)->orderByDesc('updated_at')->first();

        $ultimaCaptura = $ultima ? [
            'usuario' => ($ultima->capturado_por ? User::find($ultima->capturado_por)?->email : null) ?? 'desconocido',
            'cuando' => $ultima->updated_at?->format('d/m/Y H:i'),
        ] : null;

        return view('control.edit', [
            'sucursal' => $sucursal,
            'ultimaCaptura' => $ultimaCaptura,
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'fecha' => $fecha,
            'productos' => $productos,
            'hoy' => $hoy,
            'anterior' => $anterior,
            'gastos' => $gastos,
            'anticipos' => $anticipos,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'fecha' => ['required', 'date'],
            'conteos.*.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'gastos.*' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'anticipos.*.no_orden' => ['nullable', 'string', 'max:50'],
            'anticipos.*.monto' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ]);

        $sucursal = $this->sucursalActual($request);
        $fecha = $request->input('fecha');
        $ahora = now();

        // Lo que había guardado antes, para saber qué cambia
        $conteosAntes = ControlInventario::where('sucursal_id', $sucursal->id)->where('fecha', $fecha)->get()
            ->keyBy('producto_id')->map(fn ($r) => $r->only(self::CAMPOS_CONTEO))->all();
        $gastosPrevios = GastoDiario::where('sucursal_id', $sucursal->id)->where('fecha', $fecha)->first();
        $gastosAntes = $gastosPrevios ? array_map('floatval', $gastosPrevios->only(self::CAMPOS_GASTO)) : [];
        $anticiposAntes = AnticipoPastel::where('sucursal_id', $sucursal->id)->where('fecha', $fecha)
            ->orderBy('id')->get()->map(fn ($a) => ['no_orden' => $a->no_orden, 'monto' => (float) $a->monto])->all();

        $conteosNuevos = [];
        $gastosNuevos = [];
        $anticiposNuevos = [];

        DB::transaction(function () use ($request, $sucursal, $fecha, $ahora, &$conteosNuevos, &$gastosNuevos, &$anticiposNuevos) {
            // 1) Conteos de todos los productos del catálogo
            $filas = [];
            foreach (Producto::pluck('id') as $productoId) {
                $fila = [
                    'sucursal_id' => $sucursal->id,
                    'fecha' => $fecha,
                    'producto_id' => $productoId,
                    'capturado_por' => $request->user()->id,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
                foreach (self::CAMPOS_CONTEO as $campo) {
                    $fila[$campo] = (int) $request->input("conteos.$productoId.$campo", 0);
                }
                $filas[] = $fila;
                $conteosNuevos[$productoId] = array_intersect_key($fila, array_flip(self::CAMPOS_CONTEO));
            }

            ControlInventario::upsert(
                $filas,
                ['sucursal_id', 'fecha', 'producto_id'],
                [...self::CAMPOS_CONTEO, 'capturado_por', 'updated_at']
            );

            // 2) Gastos, otros ingresos y caja chica
            $valores = [];
            foreach (self::CAMPOS_GASTO as $campo) {
                $valores[$campo] = (float) $request->input("gastos.$campo", 0);
            }
            $gastosNuevos = $valores;
            GastoDiario::updateOrCreate(
                ['sucursal_id' => $sucursal->id, 'fecha' => $fecha],
                $valores
            );

            // 3) Anticipos: se reemplazan los del día
            AnticipoPastel::where('sucursal_id', $sucursal->id)->where('fecha', $fecha)->delete();
            foreach ($request->input('anticipos', []) as $anticipo) {
                $noOrden = trim((string) ($anticipo['no_orden'] ?? ''));
                $monto = (float) ($anticipo['monto'] ?? 0);

                if ($noOrden !== '' || $monto > 0) {
                    $anticiposNuevos[] = ['no_orden' => $noOrden, 'monto' => $monto];
                    AnticipoPastel::create([
                        'sucursal_id' => $sucursal->id,
                        'fecha' => $fecha,
                        'no_orden' => $noOrden !== '' ? $noOrden : null,
                        'monto' => $monto,
                    ]);
                }
            }
        });

        // Historial: quién guardó, cuándo y qué cambió (se registra siempre, aunque no haya cambios)
        Auditoria::registrar(
            'guardar', 'control_diario', null,
            ControlDiarioCambios::resumen(
                $fecha, $sucursal->nombre,
                $conteosAntes, $conteosNuevos, Producto::pluck('nombre', 'id')->all(), self::CAMPOS_CONTEO,
                $gastosAntes, $gastosNuevos, $anticiposAntes, $anticiposNuevos
            ),
            $sucursal->id
        );

        return redirect()
            ->route('control.edit', ['fecha' => $fecha, 'sucursal_id' => $sucursal->id])
            ->with('ok', 'Control diario guardado.');
    }
}
