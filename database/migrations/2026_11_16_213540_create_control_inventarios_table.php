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
      Schema::create('control_inventario_diario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->date('fecha');
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedInteger('inv_inicial')->default(0);
            $table->unsignedInteger('envio')->default(0);
            $table->unsignedInteger('pasteles_porcion')->default(0);
            $table->unsignedInteger('gastos_familia')->default(0);
            $table->unsignedInteger('mal_estado')->default(0);
            $table->unsignedInteger('inv_final')->default(0);
            $table->foreignId('capturado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['sucursal_id', 'fecha', 'producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_inventario_diario');
    }
};
