<?php

namespace Database\Seeders;

use App\Models\Precio;
use Illuminate\Database\Seeder;

class PrecioSeeder extends Seeder
{
    public function run(): void
    {
       
        $precios = [
            ['Fresas', 'Mini', 60.00],
            ['Fresas', 'Pequeño', 85.00],
            ['Fresas', 'Mediano', 125.00],
            ['Fresas', 'Grande', 155.00],
            ['Fresas', 'Extra grande', 185.00],
            ['Fresas', 'Media plancha', 325.00],
            ['Frutas', 'Mini', 65.00],
            ['Frutas', 'Pequeño', 90.00],
            ['Frutas', 'Mediano', 130.00],
            ['Frutas', 'Grande', 160.00],
            ['Frutas', 'Extra grande', 195.00],
            ['Frutas', 'Media plancha', 335.00],
            ['Chocolate', 'Mini', 70.00],
            ['Chocolate', 'Pequeño', 105.00],
            ['Chocolate', 'Mediano', 140.00],
            ['Chocolate', 'Grande', 185.00],
            ['Chocolate', 'Extra grande', 245.00],
            ['Chocolate', 'Media plancha', 400.00],
            ['Selva negra', 'Mini', 65.00],
            ['Selva negra', 'Pequeño', 100.00],
            ['Selva negra', 'Mediano', 130.00],
            ['Selva negra', 'Grande', 180.00],
            ['Selva negra', 'Extra grande', 240.00],
            ['Selva negra', 'Media plancha', 390.00],
            ['Oreo', 'Mini', 70.00],
            ['Oreo', 'Pequeño', 105.00],
            ['Oreo', 'Mediano', 135.00],
            ['Oreo', 'Grande', 185.00],
            ['Oreo', 'Extra grande', 245.00],
            ['Oreo', 'Media plancha', 400.00],
            ['Chocofresa', 'Mini', 70.00],
            ['Chocofresa', 'Pequeño', 105.00],
            ['Chocofresa', 'Mediano', 140.00],
            ['Chocofresa', 'Grande', 185.00],
            ['Chocofresa', 'Extra grande', 245.00],
            ['Chocofresa', 'Media plancha', 400.00],
            ['Tres Leches', 'Mini', 70.00],
            ['Tres Leches', 'Pequeño', 105.00],
            ['Tres Leches', 'Mediano', 140.00],
            ['Tres Leches', 'Grande', 185.00],
            ['Tres Leches', 'Extra grande', 245.00],
            ['Tres Leches', 'Media plancha', 400.00],
            ['Tres leches con Arándanos', 'Mini', 75.00],
            ['Tres leches con Arándanos', 'Pequeño', 110.00],
            ['Tres leches con Arándanos', 'Mediano', 145.00],
            ['Tres leches con Arándanos', 'Grande', 190.00],
            ['Tres leches con Arándanos', 'Extra grande', 255.00],
            ['Tres leches con Arándanos', 'Media plancha', 420.00],
            ['Fiesta', 'Mini', 55.00],
            ['Fiesta', 'Pequeño', 70.00],
            ['Fiesta', 'Mediano', 100.00],
            ['Fiesta', 'Grande', 125.00],
            ['Fiesta', 'Extra grande', 175.00],
            ['Fiesta', 'Media plancha', 315.00],
            ['Ambiente', 'Mini', 65.00],
            ['Ambiente', 'Pequeño', 90.00],
            ['Ambiente', 'Mediano', 125.00],
            ['Pie de queso', 'Único', 155.00],
        ];

        foreach ($precios as [$sabor, $tamano, $precio]) {
            Precio::updateOrCreate(
                ['sabor' => $sabor, 'tamano' => $tamano],
                ['precio' => $precio]
            );
        }
    }
}