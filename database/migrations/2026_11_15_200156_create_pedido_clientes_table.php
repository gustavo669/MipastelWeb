<?php

use Illuminate\Support\Facades\DB;
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
       Schema::create('pedidos_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('color', 50)->nullable();
            $table->string('sabor', 100);
            $table->string('tamano', 50);
            $table->unsignedInteger('cantidad');
            $table->decimal('precio', 10, 2);
            $table->decimal('total', 10, 2)->storedAs('cantidad * precio');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->dateTime('fecha')->useCurrent();
            $table->text('dedicatoria')->nullable();
            $table->text('detalles')->nullable();
            $table->string('sabor_personalizado', 100)->nullable();
            $table->string('foto_path', 500)->nullable();
            $table->dateTime('fecha_entrega')->nullable();
            $table->index(['fecha', 'sucursal_id']);
        });
        DB::statement('ALTER TABLE pedidos_clientes AUTO_INCREMENT = 10000');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_clientes');
    }
};
