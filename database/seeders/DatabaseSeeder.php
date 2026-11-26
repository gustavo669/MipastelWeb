<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
{
    $this->call(ProductoSeeder::class);
    $sucursales = ['Jutiapa 1', 'Jutiapa 2', 'Jutiapa 3', 'Progreso', 'Quesada',
        'Acatempa', 'Yupiltepeque', 'Atescatempa', 'Adelanto', 'Jeréz', 'Comapa', 'Carina'];

    foreach ($sucursales as $nombre) {
        \App\Models\Sucursal::firstOrCreate(['nombre' => $nombre]);
    }

    \App\Models\User::updateOrCreate(
        ['email' => 'admin@mipastel.test'],
        ['name' => 'Administrador', 'password' => \Hash::make('cambiar123'),
         'rol' => 'admin', 'sucursal_id' => null]
    );

    $atescatempa = \App\Models\Sucursal::where('nombre', 'Atescatempa')->first();
    \App\Models\User::updateOrCreate(
        ['email' => 'atescatempa@mipastel.test'],
        ['name' => 'Tienda Atescatempa', 'password' => \Hash::make('cambiar123'),
         'rol' => 'sucursal', 'sucursal_id' => $atescatempa->id]
    );
}

}
