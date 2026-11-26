<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoNormal extends Model
{
    protected $table = 'pedidos_normales';
    public $timestamps = false;
    protected $fillable = ['sabor', 'tamano', 'cantidad', 'precio', 'sucursal_id',
                        'fecha', 'fecha_entrega', 'detalles', 'sabor_personalizado'];
    protected $casts = ['fecha' => 'datetime', 'fecha_entrega' => 'datetime'];

    public function sucursal() { return $this->belongsTo(Sucursal::class); }
}
