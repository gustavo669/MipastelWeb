<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnticipoPastel extends Model
{
    protected $table = 'anticipos_pasteles';
    protected $fillable = ['sucursal_id', 'fecha', 'no_orden', 'monto'];
}
