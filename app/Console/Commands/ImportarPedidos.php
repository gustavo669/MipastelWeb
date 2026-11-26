<?php

namespace App\Console\Commands;

use App\Models\Sucursal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarPedidos extends Command
{
    protected $signature = 'mipastel:importar {--carpeta=storage/app/import : Carpeta con los CSV, relativa a la raíz del proyecto}';

    protected $description = 'Importa los pedidos exportados desde SQL Server (pedidos_normales.csv y pedidos_clientes.csv)';

    /** @var array<string,int> nombre de sucursal => id */
    private array $sucursales = [];

    public function handle(): int
    {
        $carpeta = base_path($this->option('carpeta'));

        $archivos = [
            'pedidos_normales' => $carpeta . '/pedidos_normales.csv',
            'pedidos_clientes' => $carpeta . '/pedidos_clientes.csv',
        ];

        foreach ($archivos as $tabla => $ruta) {
            if (! is_file($ruta)) {
                $this->error("No encuentro el archivo: $ruta");
                return self::FAILURE;
            }
        }

        foreach ($archivos as $tabla => $ruta) {
            $total = $this->importar($tabla, $ruta);
            $this->info("$tabla: $total filas importadas");
        }

        $this->newLine();
        $this->info('Importación terminada.');

        return self::SUCCESS;
    }

    private function importar(string $tabla, string $ruta): int
    {
        $fh = fopen($ruta, 'r');
        $encabezado = fgetcsv($fh, null, ',', '"', '');

        $lote = [];
        $total = 0;

        DB::transaction(function () use ($fh, $encabezado, $tabla, &$lote, &$total) {
            while (($fila = fgetcsv($fh, null, ',', '"', '')) !== false) {
                if ($fila === [null]) {
                    continue; // línea vacía
                }

                $registro = [];
                foreach ($encabezado as $i => $columna) {
                    $valor = $fila[$i] ?? '';
                    $registro[$columna] = ($valor === '') ? null : $valor;
                }

                // sucursal (texto) -> sucursal_id
                $registro['sucursal_id'] = $this->sucursalId($registro['sucursal']);
                unset($registro['sucursal']);

                // solo guardamos el nombre del archivo de la foto
                if (array_key_exists('foto_path', $registro) && $registro['foto_path'] !== null) {
                    $registro['foto_path'] = basename(str_replace('\\', '/', $registro['foto_path']));
                }

                $lote[] = $registro;
                $total++;

                if (count($lote) >= 200) {
                    $this->guardarLote($tabla, $lote);
                    $lote = [];
                }
            }

            if ($lote) {
                $this->guardarLote($tabla, $lote);
            }
        });

        fclose($fh);

        return $total;
    }

    private function guardarLote(string $tabla, array $lote): void
    {
        $columnasAActualizar = array_values(array_diff(array_keys($lote[0]), ['id']));

        // upsert: si se vuelve a correr el comando no duplica pedidos
        DB::table($tabla)->upsert($lote, ['id'], $columnasAActualizar);
    }

    private function sucursalId(string $nombre): int
    {
        if (! isset($this->sucursales[$nombre])) {
            $sucursal = Sucursal::firstOrCreate(['nombre' => $nombre]);

            if ($sucursal->wasRecentlyCreated) {
                $this->warn("Sucursal nueva creada: $nombre");
            }

            $this->sucursales[$nombre] = $sucursal->id;
        }

        return $this->sucursales[$nombre];
    }
}
