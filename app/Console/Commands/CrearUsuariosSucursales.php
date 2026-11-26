<?php

namespace App\Console\Commands;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CrearUsuariosSucursales extends Command
{
    protected $signature = 'mipastel:usuarios {--resetear : Genera una contraseña nueva también para los usuarios que ya existen}';

    protected $description = 'Crea un usuario por cada sucursal activa con una contraseña aleatoria';

    public function handle(): int
    {
        $filas = [];

        foreach (Sucursal::where('activa', true)->orderBy('nombre')->get() as $sucursal) {
            $correo = Str::slug($sucursal->nombre) . '@mipastel.test';

            if (User::where('email', $correo)->exists() && ! $this->option('resetear')) {
                $filas[] = [$sucursal->nombre, $correo, '(ya existía, sin cambios)'];
                continue;
            }

            $clave = Str::password(10, symbols: false);

            User::updateOrCreate(
                ['email' => $correo],
                [
                    'name' => 'Tienda ' . $sucursal->nombre,
                    'password' => Hash::make($clave),
                    'rol' => 'sucursal',
                    'sucursal_id' => $sucursal->id,
                ]
            );

            $filas[] = [$sucursal->nombre, $correo, $clave];
        }

        $this->table(['Sucursal', 'Correo', 'Contraseña'], $filas);
        $this->warn('Guarda estas contraseñas ahora: no se pueden volver a ver.');

        return self::SUCCESS;
    }
}
