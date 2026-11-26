<?php

namespace App\Observers;

use App\Models\Auditoria;
use App\Models\PedidoCliente;
use App\Models\PedidoNormal;
use App\Models\Precio;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra en el historial todo lo que se crea, modifica o elimina en pedidos y precios,
 * sin importar desde qué pantalla se haga (tienda, admin, etc.).
 * No registra lo que se haga desde la consola (seeders, migraciones, importaciones).
 */
class AuditoriaObserver
{
    private function recurso(Model $modelo): string
    {
        return match (true) {
            $modelo instanceof PedidoNormal => 'pedido_normal',
            $modelo instanceof PedidoCliente => 'pedido_cliente',
            $modelo instanceof Precio => 'precio',
            default => class_basename($modelo),
        };
    }

    private function activo(): bool
    {
        return ! app()->runningInConsole();
    }

    public function created(Model $modelo): void
    {
        if (! $this->activo()) {
            return;
        }

        Auditoria::registrar('crear', $this->recurso($modelo), $modelo->getKey(), $modelo->attributesToArray(), $modelo->sucursal_id);
    }

    public function updated(Model $modelo): void
    {
        if (! $this->activo()) {
            return;
        }

        // En este momento getOriginal() todavía tiene los valores de antes y getChanges() los nuevos
        $diferencias = Auditoria::diferencias($modelo->getOriginal(), $modelo->getChanges());

        if ($diferencias) {
            Auditoria::registrar('actualizar', $this->recurso($modelo), $modelo->getKey(), $diferencias, $modelo->sucursal_id);
        }
    }

    public function deleted(Model $modelo): void
    {
        if (! $this->activo()) {
            return;
        }

        Auditoria::registrar('eliminar', $this->recurso($modelo), $modelo->getKey(), $modelo->attributesToArray(), $modelo->sucursal_id);
    }
}
