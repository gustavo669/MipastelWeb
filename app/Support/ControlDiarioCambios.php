<?php

namespace App\Support;

use App\Models\Auditoria;

/**
 * Compara lo que había guardado en el control diario con lo que se guarda ahora,
 * y arma el resumen que va al historial de auditoría. No toca la base de datos.
 */
class ControlDiarioCambios
{
    /**
     * @param array<int, array<string, mixed>> $antes    producto_id => [campo => valor] guardado antes
     * @param array<int, array<string, mixed>> $nuevos   producto_id => [campo => valor] que se guarda ahora
     * @param array<int, string>               $nombres  producto_id => nombre del producto
     * @param string[]                         $campos   campos de conteo a comparar
     *
     * @return array<int, array{producto: string, cambios: array<string, array{0: int, 1: int}>}>
     */
    public static function conteos(array $antes, array $nuevos, array $nombres, array $campos): array
    {
        $cambios = [];

        foreach ($nuevos as $productoId => $fila) {
            $previo = $antes[$productoId] ?? [];
            $diferencias = [];

            foreach ($campos as $campo) {
                $valorAntes = (int) ($previo[$campo] ?? 0);
                $valorAhora = (int) ($fila[$campo] ?? 0);

                if ($valorAntes !== $valorAhora) {
                    $diferencias[$campo] = [$valorAntes, $valorAhora];
                }
            }

            if ($diferencias) {
                $cambios[] = ['producto' => $nombres[$productoId] ?? '#' . $productoId, 'cambios' => $diferencias];
            }
        }

        return $cambios;
    }

    /** Deja solo los anticipos con datos, con números redondeados, para poder compararlos. */
    public static function normalizarAnticipos(array $anticipos): array
    {
        $lista = [];

        foreach ($anticipos as $a) {
            $noOrden = trim((string) ($a['no_orden'] ?? ''));
            $monto = round((float) ($a['monto'] ?? 0), 2);

            if ($noOrden !== '' || $monto > 0) {
                $lista[] = ['no_orden' => $noOrden, 'monto' => $monto];
            }
        }

        return $lista;
    }

    /**
     * Resumen completo de un guardado del control diario.
     *
     * @param array $gastosAntes  [campo => valor] (puede estar vacío si ese día no había nada)
     * @param array $gastosNuevos [campo => valor]
     */
    public static function resumen(
        string $fecha,
        string $sucursal,
        array $conteosAntes,
        array $conteosNuevos,
        array $nombres,
        array $camposConteo,
        array $gastosAntes,
        array $gastosNuevos,
        array $anticiposAntes,
        array $anticiposNuevos,
    ): array {
        $conteos = self::conteos($conteosAntes, $conteosNuevos, $nombres, $camposConteo);

        // Lo que no existía antes cuenta como cero
        $gastosAntes = array_merge(array_fill_keys(array_keys($gastosNuevos), 0), $gastosAntes);
        $gastos = Auditoria::diferencias($gastosAntes, $gastosNuevos);

        $anticiposAntes = self::normalizarAnticipos($anticiposAntes);
        $anticiposNuevos = self::normalizarAnticipos($anticiposNuevos);
        $anticiposCambiaron = $anticiposAntes !== $anticiposNuevos;

        $datos = [
            'fecha' => $fecha,
            'sucursal' => $sucursal,
            'conteos' => $conteos,
            'gastos' => $gastos,
        ];

        if ($anticiposCambiaron) {
            $datos['anticipos'] = ['antes' => $anticiposAntes, 'despues' => $anticiposNuevos];
        }

        if (! $conteos && ! $gastos && ! $anticiposCambiaron) {
            $datos['sin_cambios'] = true; // alguien guardó sin modificar nada
        }

        return $datos;
    }
}
