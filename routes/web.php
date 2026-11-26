<?php

use App\Http\Controllers\AdminAuditoriaController;
use App\Http\Controllers\AdminControlDiarioController;
use App\Http\Controllers\AdminPedidosController;
use App\Http\Controllers\AdminPrecioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ControlDiarioController;
use App\Http\Controllers\ControlDiarioPdfController;
use App\Http\Controllers\PedidoClienteController;
use App\Http\Controllers\PedidoTiendaController;
use App\Http\Controllers\PrecioController;
use App\Http\Controllers\ReportePdfController;
use App\Models\Producto;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Aviso que el service worker muestra cuando no hay internet (debe ser público)
Route::view('/offline', 'offline');

// Solo para quien aún no inició sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Para cualquier usuario con sesión iniciada
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Precio de un sabor y tamaño
    Route::get('/api/obtener-precio', [PrecioController::class, 'obtener']);

    // Pedidos de tienda y de cliente (cada sucursal ve y toca solo lo suyo)
    Route::get('/pedidos', [PedidoTiendaController::class, 'index'])->name('pedidos');
    Route::get('/pedidos/normales', [PedidoTiendaController::class, 'listar']);
    Route::post('/pedidos/normales', [PedidoTiendaController::class, 'store']);
    Route::put('/pedidos/normales/{pedido}', [PedidoTiendaController::class, 'update']);
    Route::delete('/pedidos/normales/{pedido}', [PedidoTiendaController::class, 'destroy']);

    Route::get('/pedidos/clientes', [PedidoClienteController::class, 'listar']);
    Route::post('/pedidos/clientes', [PedidoClienteController::class, 'store']);
    Route::put('/pedidos/clientes/{pedido}', [PedidoClienteController::class, 'update']);
    Route::delete('/pedidos/clientes/{pedido}', [PedidoClienteController::class, 'destroy']);

    // Control diario: el admin elige sucursal, cada tienda solo ve la suya
    Route::get('/control-diario', [ControlDiarioController::class, 'edit'])->name('control.edit');
    Route::post('/control-diario', [ControlDiarioController::class, 'update'])->name('control.update');
    Route::get('/control-diario/pdf', [ControlDiarioPdfController::class, 'una'])->name('control.pdf');
    Route::get('/control-diario/pdf-todas', [ControlDiarioPdfController::class, 'todas'])->name('control.pdf.todas');

    // Solo administrador
    Route::middleware('rol:admin')->group(function () {
        Route::get('/productos', fn () => view('productos.index', [
            'productos' => Producto::orderBy('numero_orden')->get(),
        ]))->name('productos');

        Route::prefix('admin')->group(function () {
            // Pedidos de todas las sucursales
            Route::get('/', [AdminPedidosController::class, 'index'])->name('admin');
            Route::get('/normales', [AdminPedidosController::class, 'normales']);
            Route::put('/normales/{pedido}', [AdminPedidosController::class, 'updateNormal']);
            Route::delete('/normales/{pedido}', [AdminPedidosController::class, 'destroyNormal']);
            Route::get('/clientes', [AdminPedidosController::class, 'clientes']);
            Route::put('/clientes/{pedido}', [AdminPedidosController::class, 'updateCliente']);
            Route::delete('/clientes/{pedido}', [AdminPedidosController::class, 'destroyCliente']);

            // Reportes PDF (producción y ventas)
            Route::get('/reportes', [ReportePdfController::class, 'index'])->name('admin.reportes');
            Route::get('/reportes/produccion', [ReportePdfController::class, 'produccion'])->name('admin.reportes.produccion');
            Route::get('/reportes/ventas', [ReportePdfController::class, 'ventas'])->name('admin.reportes.ventas');

            // Quién capturó el control diario de cada sucursal
            Route::get('/control', [AdminControlDiarioController::class, 'index'])->name('admin.control');
            Route::get('/control/resumen', [AdminControlDiarioController::class, 'resumen']);

            // Historial de auditoría
            Route::get('/auditoria', [AdminAuditoriaController::class, 'index'])->name('admin.auditoria');
            Route::get('/auditoria/lista', [AdminAuditoriaController::class, 'lista']);

            // Precios de pasteles
            Route::get('/precios', [AdminPrecioController::class, 'index'])->name('admin.precios');
            Route::get('/precios/lista', [AdminPrecioController::class, 'listar']);
            Route::put('/precios', [AdminPrecioController::class, 'actualizar']);
            Route::post('/precios', [AdminPrecioController::class, 'store']);
            Route::delete('/precios/{precio}', [AdminPrecioController::class, 'destroy']);
        });
    });
});
