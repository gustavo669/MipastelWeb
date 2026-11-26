<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Precio extends Model
{
    protected $fillable = ['sabor', 'tamano', 'precio'];
    protected $casts = ['precio' => 'decimal:2'];
}
