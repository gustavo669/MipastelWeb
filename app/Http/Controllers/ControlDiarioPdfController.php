<?php

namespace App\Http\Controllers;

use App\Models\AnticipoPastel;
use App\Models\ControlInventario;
use App\Models\GastoDiario;
use App\Models\Producto;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ZipArchive;

class ControlDiarioPdfController extends Controller
{
    /** PDF de una sucursal (el admin elige cuál; una tienda solo puede pedir la suya). */
    public function una(Request $request)
    {
        $request->validate(['fecha' => ['required', 'date']]);

        $sucursal = $this->sucursalActual($request);
        $fecha = Carbon::parse($request->input('fecha'))->toDateString();

        return $this->generarPdf($sucursal, $fecha)
            ->stream($this->nombreArchivo($sucursal, $fecha));
    }

    /** ZIP con un PDF por cada sucursal activa (solo administrador). */
    public function todas(Request $request)
    {
        abort_unless($request->user()->rol === 'admin', 403);
        $request->validate(['fecha' => ['required', 'date']]);

        set_time_limit(0);
        $fecha = Carbon::parse($request->input('fecha'))->toDateString();

        $ruta = tempnam(sys_get_temp_dir(), 'cd');
        $zip = new ZipArchive();
        abort_unless($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No se pudo crear el ZIP.');

        foreach (Sucursal::where('activa', true)->orderBy('nombre')->get() as $sucursal) {
            $zip->addFromString(
                $this->nombreArchivo($sucursal, $fecha),
                $this->generarPdf($sucursal, $fecha)->output()
            );
        }
        $zip->close();

        return response()
            ->download($ruta, "ControlDiario_{$fecha}_todas-las-sucursales.zip")
            ->deleteFileAfterSend(true);
    }

    private function sucursalActual(Request $request): Sucursal
    {
        $user = $request->user();

        if ($user->rol === 'admin') {
            $id = $request->input('sucursal_id') ?: Sucursal::orderBy('nombre')->value('id');
            return Sucursal::findOrFail($id);
        }

        return $user->sucursal ?? abort(403, 'Tu usuario no tiene sucursal asignada.');
    }

    private function nombreArchivo(Sucursal $sucursal, string $fecha): string
    {
        $nombre = preg_replace('/[^A-Za-z0-9]+/', '_', \Illuminate\Support\Str::ascii($sucursal->nombre));
        return "ControlDiario_{$fecha}_{$nombre}.pdf";
    }

    private function generarPdf(Sucursal $sucursal, string $fecha)
    {
        return Pdf::loadView('control.pdf', $this->datos($sucursal, $fecha))
            ->setPaper('letter', 'portrait');
    }

    /** Reúne todo lo que la plantilla necesita para una sucursal y una fecha. */
    private function datos(Sucursal $sucursal, string $fecha): array
    {
        $rutaLogo = public_path('Logo.png');
        $logo = is_file($rutaLogo)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($rutaLogo))
            : null;

        $registros = ControlInventario::where('sucursal_id', $sucursal->id)
            ->where('fecha', $fecha)->get()->keyBy('producto_id');

        $gastos = GastoDiario::firstOrNew(['sucursal_id' => $sucursal->id, 'fecha' => $fecha]);

        $anticipos = AnticipoPastel::where('sucursal_id', $sucursal->id)
            ->where('fecha', $fecha)->orderBy('id')->get()
            ->map(fn ($a) => ['no_orden' => $a->no_orden, 'monto' => (float) $a->monto])
            ->all();

        // Tabla 1 = pasteles, tabla 2 = otros productos (igual que la hoja física)
        $definiciones = [
            'pastel' => ['PASTELES', 'TOTAL 1'],
            'otro' => ['OTROS PRODUCTOS', 'TOTAL 2'],
        ];

        $secciones = [];
        foreach ($definiciones as $categoria => [$titulo, $etiquetaTotal]) {
            $filas = [];
            $total = 0.0;

            $productos = Producto::where('activo', true)
                ->where('categoria', $categoria)->orderBy('numero_orden')->get();

            foreach ($productos as $p) {
                $r = $registros[$p->id] ?? null;
                $vendido = $r ? $r->vendido : 0;
                $importe = $vendido * (float) $p->precio;
                $total += $importe;

                $filas[] = [
                    'numero' => $p->numero_orden,
                    'nombre' => $p->nombre,
                    'inv_inicial' => $r->inv_inicial ?? 0,
                    'envio' => $r->envio ?? 0,
                    'disponible' => $r ? $r->disponible : 0,
                    'pasteles_porcion' => $r->pasteles_porcion ?? 0,
                    'gastos_familia' => $r->gastos_familia ?? 0,
                    'mal_estado' => $r->mal_estado ?? 0,
                    'inv_final' => $r->inv_final ?? 0,
                    'vendido' => $vendido,
                    'precio' => (float) $p->precio,
                    'importe' => $importe,
                ];
            }

            $secciones[] = [
                'titulo' => $titulo,
                'etiqueta_total' => $etiquetaTotal,
                'filas' => $filas,
                'total' => $total,
            ];
        }

        $total1 = $secciones[0]['total'];
        $total2 = $secciones[1]['total'];
        $totalAnticipos = array_sum(array_column($anticipos, 'monto'));
        $otrosIngresos = (float) $gastos->otros_ingresos;
        $totalGeneral = $total1 + $total2 + $totalAnticipos + $otrosIngresos;
        $totalGastos = (float) $gastos->total_gastos;
        $totalEfectivo = $totalGeneral - $totalGastos;

        return [
            'logo' => $logo,
            'sucursal' => $sucursal,
            'fechaTexto' => Carbon::parse($fecha)->format('d/m/Y'),
            'capturado' => $registros->isNotEmpty(),
            'secciones' => $secciones,
            'anticipos' => $anticipos,
            'totalAnticipos' => $totalAnticipos,
            'lineasGasto' => [
                ['Gastos (Compras)', (float) $gastos->gastos_compras],
                ['Desc. Degustación', (float) $gastos->desc_degustacion],
                ['Servilletas', (float) $gastos->servilletas],
                ['Tenedores', (float) $gastos->tenedores],
                ['Platos', (float) $gastos->platos],
                ['Donaciones', (float) $gastos->donaciones],
                ['Consumo Empleado', (float) $gastos->consumo_empleado],
                ['Otros Gastos', (float) $gastos->otros_gastos],
            ],
            'totalGastos' => $totalGastos,
            'otrosIngresos' => $otrosIngresos,
            'resumen' => [
                ['TOTAL 1', $total1, false],
                ['TOTAL 2', $total2, false],
                ['ANTICIPOS', $totalAnticipos, false],
                ['OTROS INGRESOS', $otrosIngresos, false],
                ['TOTAL', $totalGeneral, false],
                ['(Menos) GASTOS', $totalGastos, false],
                ['TOTAL EFECTIVO', $totalEfectivo, true],
            ],
            'cajaChica' => (float) $gastos->caja_chica_reportada,
        ];
    }
}
