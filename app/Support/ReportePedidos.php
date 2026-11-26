<?php

namespace App\Support;

/**
 * Arma los datos de los reportes PDF a partir de listas simples de pedidos.
 * No toca la base de datos: el controlador le pasa los pedidos ya convertidos a arreglos.
 *
 * Pedido de tienda:  id, sucursal, sabor, tamano, cantidad, precio
 * Pedido de cliente: id, sucursal, sabor, tamano, cantidad, precio, total, fecha_entrega (Y-m-d|null),
 *                    detalles, dedicatoria, color, tiene_foto (bool)
 */
class ReportePedidos
{
    /** Cuadrícula estándar de producción: siempre se imprime, tenga o no pedidos. */
    private const SABORES_BASE = [
        'Fresas', 'Frutas', 'Chocolate', 'Selva negra', 'Oreo', 'Chocofresa',
        'Tres Leches', 'Tres leches con Arándanos', 'Fiesta',
    ];

    private const TAMANOS_BASE = ['Mini', 'Pequeño', 'Mediano', 'Grande'];

    /** Sabores que además tienen tamaño "Extra grande" en la cuadrícula estándar. */
    private const CON_EXTRA_GRANDE = ['Fresas', 'Frutas'];

    private const ABREVIACIONES = [
        'Jutiapa 1' => 'Jut1', 'Jutiapa 2' => 'Jut2', 'Jutiapa 3' => 'Jut3', 'Progreso' => 'Prog',
        'Quesada' => 'Ques', 'Acatempa' => 'Acat', 'Yupiltepeque' => 'Yupe', 'Atescatempa' => 'Ates',
        'Adelanto' => 'Adel', 'Jeréz' => 'Jeréz', 'Comapa' => 'Comapa', 'Carina' => 'Carina',
    ];

    public static function abreviar(string $sucursal): string
    {
        return self::ABREVIACIONES[$sucursal] ?? mb_substr($sucursal, 0, 3);
    }

    /** "Mini de Fresas". Los productos de tamaño único (Pie de queso) llevan solo el nombre. */
    public static function descripcion(string $tamano, string $sabor): string
    {
        return $tamano === 'Único' ? $sabor : "{$tamano} de {$sabor}";
    }

    private static function clave(string $tamano, string $sabor): string
    {
        return mb_strtoupper(self::descripcion($tamano, $sabor));
    }

    /**
     * Reporte de producción: cuántos pasteles de cada tipo pide cada sucursal, más el control de pedidos de clientes.
     *
     * @param array $sucursales nombres de sucursal, en el orden en que irán las columnas
     */
    public static function produccion(array $normales, array $clientes, array $sucursales): array
    {
        // Si hay pedidos de una sucursal que no está en la lista, se agrega como columna para no perderlos.
        foreach (array_merge($normales, $clientes) as $p) {
            if (! in_array($p['sucursal'], $sucursales, true)) {
                $sucursales[] = $p['sucursal'];
            }
        }

        $totales = [];      // PRODUCTO => [sucursal => cantidad]
        foreach ($normales as $p) {
            $clave = self::clave($p['tamano'], $p['sabor']);
            $totales[$clave][$p['sucursal']] = ($totales[$clave][$p['sucursal']] ?? 0) + (int) $p['cantidad'];
        }

        // 1) cuadrícula estándar
        $orden = [];
        foreach (self::SABORES_BASE as $sabor) {
            foreach (self::TAMANOS_BASE as $tamano) {
                $orden[] = self::clave($tamano, $sabor);
            }
            if (in_array($sabor, self::CON_EXTRA_GRANDE, true)) {
                $orden[] = self::clave('Extra grande', $sabor);
            }
        }

        // 2) todo lo demás que se haya pedido (Media plancha, sabores personalizados, Ambiente...) va al final
        $extras = array_values(array_diff(array_keys($totales), $orden));
        sort($extras, SORT_STRING);
        $orden = array_merge($orden, $extras);

        $filas = [];
        $totalPorSucursal = array_fill_keys($sucursales, 0);
        $granTotal = 0;
        foreach ($orden as $producto) {
            $cantidades = [];
            $totalFila = 0;
            foreach ($sucursales as $s) {
                $cant = $totales[$producto][$s] ?? 0;
                $cantidades[] = $cant;
                $totalFila += $cant;
                $totalPorSucursal[$s] += $cant;
            }
            $granTotal += $totalFila;
            $filas[] = ['producto' => $producto, 'cantidades' => $cantidades, 'total' => $totalFila];
        }

        // Control de pedidos de clientes
        $filasClientes = [];
        $totalClientes = 0;
        foreach ($clientes as $c) {
            $cant = (int) $c['cantidad'];
            $totalClientes += $cant;
            $filasClientes[] = [
                'id' => $c['id'],
                'cantidad' => $cant,
                'descripcion' => self::descripcion($c['tamano'], $c['sabor']),
                'sucursal' => self::abreviar($c['sucursal']),
                'fecha_entrega' => ! empty($c['fecha_entrega']) ? date('d-m-Y', strtotime($c['fecha_entrega'])) : '',
                'detalles' => $c['detalles'] ?? '',
                'foto' => ! empty($c['tiene_foto']) ? 'SI' : 'NO',
                'dedicatoria' => $c['dedicatoria'] ?? '',
                'color' => $c['color'] ?? '',
            ];
        }

        return [
            'sucursalesAbrev' => array_map([self::class, 'abreviar'], $sucursales),
            'filas' => $filas,
            'totalPorSucursal' => array_values($totalPorSucursal),
            'granTotal' => $granTotal,
            'filasClientes' => $filasClientes,
            'totalClientes' => $totalClientes,
        ];
    }

    /** Reporte de ventas: lo vendido en pasteles de tienda (agrupado) y los pedidos de clientes (uno por uno). */
    public static function ventas(array $normales, array $clientes): array
    {
        $agrupado = [];
        foreach ($normales as $p) {
            $producto = self::descripcion($p['tamano'], $p['sabor']);
            $precio = round((float) $p['precio'], 2);
            $clave = $p['sucursal'] . '|' . $producto . '|' . number_format($precio, 2, '.', '');

            if (! isset($agrupado[$clave])) {
                $agrupado[$clave] = ['sucursal' => $p['sucursal'], 'producto' => $producto, 'precio' => $precio, 'cantidad' => 0];
            }
            $agrupado[$clave]['cantidad'] += (int) $p['cantidad'];
        }

        $agrupado = array_values($agrupado);
        usort($agrupado, fn ($a, $b) => [$a['sucursal'], $a['producto'], $a['precio']] <=> [$b['sucursal'], $b['producto'], $b['precio']]);

        $filasTienda = [];
        $totalTienda = 0.0;
        foreach ($agrupado as $g) {
            $subtotal = round($g['precio'] * $g['cantidad'], 2);
            $totalTienda += $subtotal;
            $filasTienda[] = [
                'sucursal' => self::abreviar($g['sucursal']),
                'producto' => $g['producto'],
                'cantidad' => $g['cantidad'],
                'precio' => $g['precio'],
                'subtotal' => $subtotal,
            ];
        }

        $filasClientes = [];
        $totalClientes = 0.0;
        foreach ($clientes as $c) {
            $cant = (int) $c['cantidad'];
            $precio = round((float) $c['precio'], 2);
            $total = ! empty($c['total']) ? round((float) $c['total'], 2) : round($precio * $cant, 2);
            $totalClientes += $total;
            $filasClientes[] = [
                'id' => $c['id'],
                'sucursal' => self::abreviar($c['sucursal']),
                'producto' => self::descripcion($c['tamano'], $c['sabor']),
                'cantidad' => $cant,
                'precio' => $precio,
                'total' => $total,
            ];
        }

        $totalTienda = round($totalTienda, 2);
        $totalClientes = round($totalClientes, 2);

        return [
            'filasTienda' => $filasTienda,
            'totalTienda' => $totalTienda,
            'filasClientes' => $filasClientes,
            'totalClientes' => $totalClientes,
            'totalGeneral' => round($totalTienda + $totalClientes, 2),
        ];
    }
}
