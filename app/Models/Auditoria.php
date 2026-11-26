<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de acciones: quién hizo qué, cuándo y desde qué dirección IP.
 */
class Auditoria extends Model
{
    protected $table = 'auditorias';

    public $timestamps = false; // solo existe created_at

    protected $fillable = [
        'user_id', 'usuario', 'accion', 'recurso', 'recurso_id', 'sucursal_id', 'datos', 'ip', 'created_at',
    ];

    protected $casts = [
        'datos' => 'array',
        'created_at' => 'datetime',
    ];

    /** Campos que no aportan al historial. */
    private const IGNORAR = ['updated_at', 'created_at'];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Guarda un evento. Si falla, solo lo reporta: el historial nunca debe tumbar la operación principal.
     *
     * @param Authenticatable|null $usuario quién lo hizo (por defecto, el usuario con sesión)
     * @param string|null          $etiqueta texto a mostrar como usuario (para intentos de login sin usuario)
     */
    public static function registrar(
        string $accion,
        string $recurso,
        mixed $recursoId = null,
        array $datos = [],
        ?int $sucursalId = null,
        ?Authenticatable $usuario = null,
        ?string $etiqueta = null,
    ): void {
        try {
            $user = $usuario ?? auth()->user();

            static::create([
                'user_id' => $user?->getAuthIdentifier(),
                'usuario' => $etiqueta ?? ($user?->email ?? 'anónimo'),
                'accion' => $accion,
                'recurso' => $recurso,
                'recurso_id' => $recursoId,
                'sucursal_id' => $sucursalId ?? ($user?->sucursal_id),
                'datos' => $datos ?: null,
                'ip' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Compara los valores de antes y después de una edición: [campo => ['antes' => x, 'despues' => y]].
     * Ignora fechas internas y números que son iguales aunque estén escritos distinto (60 y "60.00").
     */
    public static function diferencias(array $original, array $cambios): array
    {
        $diferencias = [];

        foreach ($cambios as $campo => $nuevo) {
            if (in_array($campo, self::IGNORAR, true)) {
                continue;
            }

            $antes = $original[$campo] ?? null;

            if (is_numeric($antes) && is_numeric($nuevo) && abs((float) $antes - (float) $nuevo) < 0.00001) {
                continue;
            }

            $diferencias[$campo] = ['antes' => $antes, 'despues' => $nuevo];
        }

        return $diferencias;
    }
}
