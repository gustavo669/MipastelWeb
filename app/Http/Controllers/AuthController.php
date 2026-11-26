<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** Intentos fallidos permitidos antes de bloquear. */
    private const MAX_INTENTOS = 5;

    /** Cuánto dura el bloqueo, en segundos (5 minutos). */
    private const BLOQUEO_SEGUNDOS = 300;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // El límite es por correo + dirección IP, así un intruso no bloquea a otras personas
        $clave = Str::lower($credenciales['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            event(new Lockout($request));
            $segundos = RateLimiter::availableIn($clave);
            $minutos = (int) ceil($segundos / 60);

            return back()
                ->withErrors(['email' => "Demasiados intentos fallidos. Intenta de nuevo en {$minutos} minuto(s)."])
                ->onlyInput('email');
        }

        if (Auth::attempt($credenciales)) {
            RateLimiter::clear($clave);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);

        return back()
            ->withErrors(['email' => 'Correo o contraseña incorrectos.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
