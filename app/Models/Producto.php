<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
protected $fillable = ['numero_orden', 'categoria', 'nombre', 'precio', 'activo'];
protected $casts = ['precio' => 'decimal:2', 'activo' => 'boolean'];
}
