<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlInventario extends Model
{
    protected $table = 'control_inventario_diario';
protected $fillable = ['sucursal_id', 'fecha', 'producto_id', 'inv_inicial', 'envio',
    'pasteles_porcion', 'gastos_familia', 'mal_estado', 'inv_final', 'capturado_por'];

public function producto() { return $this->belongsTo(Producto::class); }

public function getDisponibleAttribute(): int
{
    return $this->inv_inicial + $this->envio;
}

public function getVendidoAttribute(): int
{
    return $this->inv_inicial + $this->envio - $this->pasteles_porcion
         - $this->gastos_familia - $this->mal_estado - $this->inv_final;
}
}
