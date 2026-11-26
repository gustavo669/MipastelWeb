<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('gastos_diarios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sucursal_id')->constrained('sucursales');
    $table->date('fecha');
    foreach (['gastos_compras', 'desc_degustacion', 'servilletas', 'tenedores', 'platos',
              'donaciones', 'consumo_empleado', 'otros_gastos', 'otros_ingresos',
              'caja_chica_reportada'] as $columna) {
        $table->decimal($columna, 10, 2)->default(0);
    }
    $table->timestamps();
    $table->unique(['sucursal_id', 'fecha']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos_diarios');
    }
};
