<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoCliente extends Model
{
    protected $table = 'pedidos_clientes';
    public $timestamps = false;
    protected $fillable = ['color', 'sabor', 'tamano', 'cantidad', 'precio', 'sucursal_id', 'fecha',
                        'dedicatoria', 'detalles', 'sabor_personalizado', 'foto_path', 'fecha_entrega'];
    protected $casts = ['fecha' => 'datetime', 'fecha_entrega' => 'datetime'];

    public function sucursal() { return $this->belongsTo(Sucursal::class); }
}
