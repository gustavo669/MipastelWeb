<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // [numero_orden en la hoja física, categoría, nombre, precio en Q]
        $productos = [
            [1, 'pastel', 'MINI DE FRESA', 60.00],
            [2, 'pastel', 'PEQUEÑO DE FRESA', 85.00],
            [3, 'pastel', 'MEDIANO DE FRESA', 125.00],
            [4, 'pastel', 'GRANDE DE FRESA', 155.00],
            [5, 'pastel', 'EXTRA GRANDE DE FRESA', 185.00],
            [6, 'pastel', 'MEDIA PLANCHA DE FRESA', 325.00],
            [7, 'pastel', 'MINI DE FRUTAS', 65.00],
            [8, 'pastel', 'PEQUEÑO DE FRUTAS', 90.00],
            [9, 'pastel', 'MEDIANO DE FRUTAS', 130.00],
            [10, 'pastel', 'GRANDE DE FRUTAS', 160.00],
            [11, 'pastel', 'EXTRA GRANDE DE FRUTAS', 195.00],
            [12, 'pastel', 'MEDIA PLANCHA DE FRUTAS', 335.00],
            [13, 'pastel', 'MINI DE FIESTA', 55.00],
            [14, 'pastel', 'PEQUEÑO DE FIESTA', 70.00],
            [15, 'pastel', 'MEDIANO DE FIESTA', 100.00],
            [16, 'pastel', 'GRANDE DE FIESTA', 125.00],
            [17, 'pastel', 'EXTRA GRANDE DE FIESTA', 175.00],
            [18, 'pastel', 'MEDIA PLANCHA DE FIESTA', 315.00],
            [19, 'pastel', 'MINI DE TRES LECHES', 70.00],
            [20, 'pastel', 'PEQUEÑO DE TRES LECHES', 105.00],
            [21, 'pastel', 'MEDIANO DE TRES LECHES', 140.00],
            [22, 'pastel', 'GRANDE DE TRES LECHES', 185.00],
            [23, 'pastel', 'EXTRA GRANDE DE TRES LECHES', 245.00],
            [24, 'pastel', 'MEDIA PLANCHA DE TRES LECHES', 400.00],
            [25, 'pastel', 'MINI DE CHOCOLATE', 70.00],
            [26, 'pastel', 'PEQUEÑO DE CHOCOLATE', 105.00],
            [27, 'pastel', 'MEDIANO DE CHOCOLATE', 140.00],
            [28, 'pastel', 'GRANDE DE CHOCOLATE', 185.00],
            [29, 'pastel', 'EXTRA GRANDE DE CHOCOLATE', 245.00],
            [30, 'pastel', 'MEDIA PLANCHA DE CHOCOLATE', 400.00],
            [31, 'pastel', 'MINI DE SELVA NEGRA', 65.00],
            [32, 'pastel', 'PEQUEÑO DE SELVA NEGRA', 100.00],
            [33, 'pastel', 'MEDIANO DE SELVA NEGRA', 130.00],
            [34, 'pastel', 'GRANDE DE SELVA NEGRA', 180.00],
            [35, 'pastel', 'EXTRA GRANDE DE SELVA NEGRA', 240.00],
            [36, 'pastel', 'MEDIA PLANCHA DE SELVA NEGRA', 390.00],
            [37, 'pastel', 'MINI 3 LECHES CON ARÁNDANO', 75.00],
            [38, 'pastel', 'PEQUEÑO 3 LECHES ARÁNDANO', 110.00],
            [39, 'pastel', 'MEDIANO 3 LECHES ARÁNDANO', 145.00],
            [40, 'pastel', 'MINI OREO', 70.00],
            [41, 'pastel', 'PEQUEÑO OREO', 105.00],
            [42, 'pastel', 'MEDIANO OREO', 135.00],
            [43, 'pastel', 'MINI AMBIENTE', 65.00],
            [44, 'pastel', 'PEQUEÑO AMBIENTE', 90.00],
            [45, 'pastel', 'MEDIANO AMBIENTE', 125.00],
            [46, 'pastel', 'PIE DE QUESO', 155.00],
            [57, 'otro', 'DONAS SIMPLE O NIDO', 6.00],
            [58, 'otro', 'DONAS RELLENAS', 7.00],
            [59, 'otro', 'SUBMARINOS', 7.00],
            [60, 'otro', 'MILHOJA', 6.00],
            [61, 'otro', 'PAÑUELOS', 6.00],
            [62, 'otro', 'HERRADURAS PIÑA/CHOCOLATE', 6.00],
            [63, 'otro', 'ALMOHADITAS', 6.00],
            [64, 'otro', 'STRUDEL DE PIÑA', 6.00],
            [65, 'otro', 'STRUDEL DE NUTELLA', 8.00],
            [66, 'otro', 'NAPOLEONES', 9.00],
            [67, 'otro', 'GALLETAS DE CHOCOLATE', 4.00],
            [68, 'otro', 'TARTALETAS', 9.00],
            [69, 'otro', 'MARGARITAS', 3.00],
            [70, 'otro', 'BORRACHOS', 7.00],
            [71, 'otro', 'RELÁMPAGOS', 7.00],
            [72, 'otro', 'PIE DE PIÑA', 8.00],
            [73, 'otro', 'PIE DE MORA', 9.00],
            [74, 'otro', 'MUSS', 9.00],
            [75, 'otro', 'VELA DE NUMEROS', 8.00],
            [76, 'otro', 'VELAS MAGICAS Y DE COLORES', 8.00],
            [77, 'otro', 'VELA VOLCAN PEQUEÑA', 5.00],
            [78, 'otro', 'VELA VOLCAN MEDIANA', 7.00],
            [79, 'otro', 'VELA VOLCAN GRANDE', 10.00],
            [80, 'otro', 'VELA PERSONAJE BRILLANTE', 10.00],
            [81, 'otro', 'VELA DE 15 AÑOS PEQUEÑA', 10.00],
            [82, 'otro', 'VELA DE 15 AÑOS', 17.00],
            [83, 'otro', 'VELA PERSONAJE ANIMADO', 15.00],
            [84, 'otro', 'CAKETOPPER MEDIANO', 15.00],
            [85, 'otro', 'CAKETOPPER GRANDE', 20.00],
            [86, 'otro', 'PLATOS DESECHABLES', 7.00],
            [87, 'otro', 'VASO DESECHABLE', 7.00],
            [88, 'otro', 'TENEDORES DESECHABLES', 7.00],
            [89, 'otro', 'SERVILLETAS', 7.00],
            [101, 'otro', 'MAGDALENA DE SABORES', 35.00],
            [102, 'otro', 'MAGDALENA VETEADA', 40.00],
            [104, 'otro', 'PORCIÓN FRESA Y/O FRUTA', 15.00],
            [105, 'otro', 'PORCIÓN DE 3L Y/O ARANDANO', 15.00],
            [106, 'otro', 'PORCIÓN DE CHOCO/SELVA/OREO', 15.00],
            [107, 'otro', 'PORCIÓN DE ZANAHORIA', 17.00],
            [108, 'otro', 'PORCIÓN DE PIE DE QUESO', 17.00],
        ];

        foreach ($productos as [$numero, $categoria, $nombre, $precio]) {
            Producto::updateOrCreate(
                ['numero_orden' => $numero],
                ['categoria' => $categoria, 'nombre' => $nombre, 'precio' => $precio, 'activo' => true]
            );
        }
    }
}