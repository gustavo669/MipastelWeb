<?php

namespace App\Http\Controllers;

use App\Models\Precio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Administración de precios de pasteles (tabla `precios`). Solo administrador.
 * Los pedidos ya registrados conservan el precio con el que se hicieron.
 */
class AdminPrecioController extends Controller
{
    public function index()
    {
        return view('admin.precios', [
            'sabores' => Precio::orderBy('id')->pluck('sabor')->unique()->values(),
        ]);
    }

    public function listar()
    {
        return response()->json([
            'precios' => Precio::orderBy('id')->get()->map(fn (Precio $p) => [
                'id' => $p->id,
                'sabor' => $p->sabor,
                'tamano' => $p->tamano,
                'precio' => (float) $p->precio,
            ])->values(),
        ]);
    }

    /** Cambia el precio de varias combinaciones de una vez: { precios: [{id, precio}, ...] } */
    public function actualizar(Request $request)
    {
        $datos = $request->validate([
            'precios' => ['required', 'array', 'min:1', 'max:500'],
            'precios.*.id' => ['required', 'integer', 'exists:precios,id'],
            'precios.*.precio' => ['required', 'numeric', 'gt:0', 'max:100000'],
        ]);

        DB::transaction(function () use ($datos) {
            $precios = Precio::whereIn('id', array_column($datos['precios'], 'id'))->get()->keyBy('id');

            foreach ($datos['precios'] as $fila) {
                // save() en cada modelo (y no un update masivo) para que quede en el historial de auditoría
                $precio = $precios[$fila['id']];
                $precio->precio = $fila['precio'];
                $precio->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Precios actualizados correctamente',
            'actualizados' => count($datos['precios']),
        ]);
    }

    /** Agrega una combinación nueva (por ejemplo un sabor nuevo). */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'sabor' => ['required', 'string', 'max:50'],
            'tamano' => ['required', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'gt:0', 'max:100000'],
        ]);

        $sabor = trim($datos['sabor']);
        $tamano = trim($datos['tamano']);

        if (mb_strtolower($sabor) === 'otro') {
            throw ValidationException::withMessages(['sabor' => '«Otro» es una opción reservada para precios manuales.']);
        }
        if (Precio::where('sabor', $sabor)->where('tamano', $tamano)->exists()) {
            throw ValidationException::withMessages(['tamano' => 'Esa combinación de sabor y tamaño ya existe.']);
        }

        $precio = Precio::create(['sabor' => $sabor, 'tamano' => $tamano, 'precio' => $datos['precio']]);

        return response()->json(['success' => true, 'id' => $precio->id, 'message' => 'Precio agregado'], 201);
    }

    public function destroy(Precio $precio)
    {
        $precio->delete();

        return response()->json(['success' => true, 'message' => 'Precio eliminado']);
    }
}
