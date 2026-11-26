<?php

namespace App\Providers;

use App\Models\Auditoria;
use App\Models\PedidoCliente;
use App\Models\PedidoNormal;
use App\Models\Precio;
use App\Observers\AuditoriaObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // La app instalable (PWA) solo funciona con HTTPS
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        PedidoNormal::observe(AuditoriaObserver::class);
        PedidoCliente::observe(AuditoriaObserver::class);
        Precio::observe(AuditoriaObserver::class);

        Event::listen(Login::class, fn (Login $e) => Auditoria::registrar(
            'login', 'sesion', $e->user->getAuthIdentifier(), [], $e->user->sucursal_id, $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user && Auditoria::registrar(
            'logout', 'sesion', $e->user->getAuthIdentifier(), [], $e->user->sucursal_id, $e->user));
        Event::listen(Failed::class, fn (Failed $e) => Auditoria::registrar(
            'login_fallido', 'sesion', null, ['motivo' => 'Credenciales incorrectas'], null, null, $e->credentials['email'] ?? 'desconocido'));
        Event::listen(Lockout::class, fn (Lockout $e) => Auditoria::registrar(
            'login_bloqueado', 'sesion', null, ['motivo' => 'Demasiados intentos'], null, null, (string) $e->request->input('email')));

            }
}
