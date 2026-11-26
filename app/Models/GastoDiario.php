<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoDiario extends Model
{
    protected $table = 'gastos_diarios';
protected $fillable = ['sucursal_id', 'fecha', 'gastos_compras', 'desc_degustacion', 'servilletas',
    'tenedores', 'platos', 'donaciones', 'consumo_empleado', 'otros_gastos',
    'otros_ingresos', 'caja_chica_reportada'];

public function getTotalGastosAttribute(): float
{
    return (float) $this->gastos_compras + $this->desc_degustacion + $this->servilletas
        + $this->tenedores + $this->platos + $this->donaciones
        + $this->consumo_empleado + $this->otros_gastos;
}
}
