<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Reglas que comparten los pedidos de tienda y los de cliente.
 */
trait PedidosDeSucursal
{
    /** Tamaños disponibles cuando el sabor es "Otro" (precio manual). */
    private const TAMANOS_OTRO = ['Mini', 'Pequeño', 'Mediano', 'Grande', 'Extra grande', 'Media plancha'];

    /** Tamaños que solo existen en pedidos de cliente y no tienen precio en el catálogo. */
    private const TAMANOS_EXTRA_CLIENTE = ['Boda', 'Quince Años'];

    /**
     * Sucursal con la que se trabaja. Un usuario de tienda siempre usa la suya.
     * El administrador la elige con `sucursal_id` (en la dirección o en el cuerpo de la petición).
     *
     * @param bool $porDefecto si el admin no eligió ninguna, usa la primera (solo para abrir la pantalla)
     */
    protected function sucursalDelUsuario(Request $request, bool $porDefecto = false): Sucursal
    {
        $user = $request->user();

        if ($user->rol === 'admin') {
            $id = (int) $request->input('sucursal_id');

            if ($id > 0) {
                return Sucursal::find($id) ?? abort(422, 'La sucursal elegida no existe.');
            }
            if ($porDefecto) {
                return Sucursal::where('activa', true)->orderBy('nombre')->first() ?? abort(404, 'No hay sucursales registradas.');
            }

            abort(422, 'Elige la sucursal para la que vas a registrar pedidos.');
        }

        return $user->sucursal ?? abort(403, 'Tu usuario no tiene sucursal asignada.');
    }

    /** Un usuario de tienda solo toca pedidos de su sucursal; el admin, los de cualquiera. */
    protected function autorizar(Request $request, Model $pedido): void
    {
        $user = $request->user();

        abort_unless(
            $user->rol === 'admin' || (int) $pedido->sucursal_id === (int) $user->sucursal_id,
            403,
            'No tienes permiso sobre este pedido.'
        );
    }

    /** Solo se puede editar o eliminar si la fecha de entrega es hoy o futura. */
    protected function esEditable(Model $pedido): bool
    {
        return $pedido->fecha_entrega !== null
            && $pedido->fecha_entrega->toDateString() >= today()->toDateString();
    }
}
