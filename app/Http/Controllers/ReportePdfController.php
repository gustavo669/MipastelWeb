<?php

namespace App\Http\Controllers;

use App\Models\PedidoCliente;
use App\Models\PedidoNormal;
use App\Models\Sucursal;
use App\Support\ReportePedidos;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Reportes PDF para el administrador. Las rutas van dentro del grupo con middleware rol:admin.
 * Los pedidos se filtran por la fecha en que se REGISTRARON.
 */
class ReportePdfController extends Controller
{
    public function index()
    {
        $hoy = today();

        return view('admin.reportes', [
            'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
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

    /** Producción: cuadrícula producto x sucursal + control de pedidos de clientes. Hoja horizontal. */
    public function produccion(Request $request)
    {
        $f = $this->filtros($request);
        [$normales, $clientes] = $this->pedidos($f);

        $nombresSucursales = Sucursal::where('activa', true)->orderBy('id')->pluck('nombre')->all();
        $datos = ReportePedidos::produccion($normales, $clientes, $nombresSucursales);

        return Pdf::loadView('reportes.produccion', $datos + $this->encabezado($f))
            ->setPaper('letter', 'landscape')
            ->stream($this->nombreArchivo('Reporte_Produccion', $f));
    }

    /** Ventas: pasteles de tienda agrupados, pedidos de clientes y resumen. Hoja vertical. */
    public function ventas(Request $request)
    {
        $f = $this->filtros($request);
        [$normales, $clientes] = $this->pedidos($f);

        $datos = ReportePedidos::ventas($normales, $clientes);

        return Pdf::loadView('reportes.ventas', $datos + $this->encabezado($f))
            ->setPaper('letter', 'portrait')
            ->stream($this->nombreArchivo('Reporte_Ventas', $f));
    }

    /** Valida el rango de fechas y la sucursal opcional (vacía = todas). */
    private function filtros(Request $request): array
    {
        $f = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
        ]);

        // fechas en formato Y-m-d, sin importar cómo hayan llegado
        $f['fecha_inicio'] = Carbon::parse($f['fecha_inicio'])->toDateString();
        $f['fecha_fin'] = Carbon::parse($f['fecha_fin'])->toDateString();
        $f['sucursal'] = ! empty($f['sucursal_id']) ? Sucursal::find($f['sucursal_id']) : null;

        return $f;
    }

    /** @return array{0: array, 1: array} pedidos de tienda y de cliente como arreglos simples */
    private function pedidos(array $f): array
    {
        $consulta = fn (string $modelo) => $modelo::with('sucursal')
            ->whereDate('fecha', '>=', $f['fecha_inicio'])
            ->whereDate('fecha', '<=', $f['fecha_fin'])
            ->when($f['sucursal_id'] ?? null, fn ($q, $id) => $q->where('sucursal_id', $id))
            ->orderBy('id');

        $normales = $consulta(PedidoNormal::class)->get()->map(fn (PedidoNormal $p) => [
            'id' => $p->id,
            'sucursal' => $p->sucursal?->nombre ?? 'Sin sucursal',
            'sabor' => $p->sabor_personalizado ?: $p->sabor,
            'tamano' => $p->tamano,
            'cantidad' => $p->cantidad,
            'precio' => (float) $p->precio,
        ])->all();

        $clientes = $consulta(PedidoCliente::class)->get()->map(fn (PedidoCliente $p) => [
            'id' => $p->id,
            'sucursal' => $p->sucursal?->nombre ?? 'Sin sucursal',
            'sabor' => $p->sabor_personalizado ?: $p->sabor,
            'tamano' => $p->tamano,
            'cantidad' => $p->cantidad,
            'precio' => (float) $p->precio,
            'total' => (float) $p->total,
            'fecha_entrega' => $p->fecha_entrega?->toDateString(),
            'detalles' => $p->detalles,
            'dedicatoria' => $p->dedicatoria,
            'color' => $p->color,
            'tiene_foto' => $p->foto_path !== null,
        ])->all();

        return [$normales, $clientes];
    }

    /** Datos del encabezado: período, sucursal y logo. */
    private function encabezado(array $f): array
    {
        $inicio = Carbon::parse($f['fecha_inicio']);
        $fin = Carbon::parse($f['fecha_fin']);

        $logo = null;
        $ruta = public_path('Logo.png');
        if (is_file($ruta)) {
            $logo = 'data:image/png;base64,' . base64_encode(file_get_contents($ruta));
        }

        return [
            'periodo' => $inicio->equalTo($fin)
                ? $inicio->format('d-m-Y')
                : $inicio->format('d-m-Y') . ' a ' . $fin->format('d-m-Y'),
            'sucursalNombre' => $f['sucursal']?->nombre,
            'logo' => $logo,
        ];
    }

    private function nombreArchivo(string $base, array $f): string
    {
        $nombre = "{$base}_{$f['fecha_inicio']}";
        if ($f['fecha_inicio'] !== $f['fecha_fin']) {
            $nombre .= "_a_{$f['fecha_fin']}";
        }
        if ($f['sucursal']) {
            $nombre .= '_' . preg_replace('/[^A-Za-z0-9]+/', '_', \Illuminate\Support\Str::ascii($f['sucursal']->nombre));
        }

        return $nombre . '.pdf';
    }
}
