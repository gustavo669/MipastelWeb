<?php

namespace App\Http\Controllers;

use App\Models\Precio;
use Illuminate\Http\Request;

class PrecioController extends Controller
{
    public function obtener(Request $request)
{
    $sabor = (string) $request->query('sabor');
    $tamano = (string) $request->query('tamano');

    if (mb_strtolower($sabor) === 'otro') {
        return response()->json(['precio' => 0, 'encontrado' => false, 'detalle' => 'Precio manual requerido']);
    }

    $precio = Precio::where('sabor', $sabor)->where('tamano', $tamano)->value('precio');

    return $precio > 0
        ? response()->json(['precio' => (float) $precio, 'encontrado' => true])
        : response()->json(['precio' => 0, 'encontrado' => false, 'detalle' => 'No registrado']);
}
}
