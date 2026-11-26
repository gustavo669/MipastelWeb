<?php

namespace Database\Seeders;

use App\Models\Precio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Sucursales de Mi Pastel. */
    private const SUCURSALES = [
        'Jutiapa 1', 'Jutiapa 2', 'Jutiapa 3', 'Progreso', 'Quesada',
        'Acatempa', 'Yupiltepeque', 'Atescatempa', 'Adelanto', 'Jeréz', 'Comapa', 'Carina',
    ];

    /**
     * Es seguro ejecutarlo varias veces: no cambia la contraseña de un admin que ya existe.
     * Los usuarios de cada sucursal se crean con `php artisan mipastel:usuarios`.
     */
    public function run(): void
    {
        $this->call(ProductoSeeder::class);
        // Los precios se cargan solo la primera vez: así volver a sembrar no pisa los que el admin ya cambió
        if (! Precio::exists()) {
            $this->call(PrecioSeeder::class);
        }

        foreach (self::SUCURSALES as $nombre) {
            Sucursal::firstOrCreate(['nombre' => $nombre]);
        }

        $correo = config('mipastel.admin_email');

        if (User::where('email', $correo)->exists()) {
            return;
        }

        $clave = config('mipastel.admin_password') ?: Str::password(12, symbols: false);

        User::create([
            'name' => 'Administrador',
            'email' => $correo,
            'password' => Hash::make($clave),
            'rol' => 'admin',
            'sucursal_id' => null,
        ]);

        if (! config('mipastel.admin_password')) {
            $this->command?->warn("Administrador creado: {$correo} / {$clave}");
            $this->command?->warn('Guarda esta contraseña ahora: no se puede volver a ver.');
        }
    }
}
