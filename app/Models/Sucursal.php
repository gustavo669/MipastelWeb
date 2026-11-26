<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales'; // Laravel pluralizaría en inglés ("sucursals")
    protected $fillable = ['nombre', 'activa'];
}