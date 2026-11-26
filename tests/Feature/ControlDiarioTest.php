<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\ControlInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlDiarioTest extends TestCase
{
    use RefreshDatabase;

    private Sucursal $jutiapa;
    private Sucursal $progreso;
    private User $tienda;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jutiapa = Sucursal::create(['nombre' => 'Jutiapa 1']);
        $this->progreso = Sucursal::create(['nombre' => 'Progreso']);

        $this->tienda = User::create([
            'name' => 'Tienda Jutiapa', 'email' => 'jutiapa@mipastel.test', 'password' => 'clave-de-prueba',
            'rol' => 'sucursal', 'sucursal_id' => $this->jutiapa->id,
        ]);

        $this->producto = Producto::create([
            'numero_orden' => 1, 'categoria' => 'pastel', 'nombre' => 'FRESAS MEDIANO', 'precio' => 125, 'activo' => true,
        ]);
    }

    private function guardar(array $extra = [], ?string $fecha = null)
    {
        return $this->actingAs($this->tienda)->post('/control-diario', array_merge([
            'fecha' => $fecha ?? today()->toDateString(),
            'conteos' => [$this->producto->id => [
                'inv_inicial' => 10, 'envio' => 5, 'pasteles_porcion' => 1,
                'gastos_familia' => 0, 'mal_estado' => 1, 'inv_final' => 8,
            ]],
            'gastos' => ['gastos_compras' => 25.5],
        ], $extra));
    }

    public function test_la_tienda_guarda_su_control_del_dia(): void
    {
        $this->guardar()->assertRedirect();

        $fila = ControlInventario::firstOrFail();
        $this->assertSame($this->jutiapa->id, $fila->sucursal_id);
        $this->assertSame($this->tienda->id, $fila->capturado_por);
        $this->assertSame(5, $fila->vendido); // 10 + 5 - 1 - 0 - 1 - 8
        $this->assertDatabaseHas('gastos_diarios', ['sucursal_id' => $this->jutiapa->id, 'gastos_compras' => 25.5]);
    }

    public function test_la_tienda_no_puede_guardar_en_otra_sucursal(): void
    {
        $this->guardar(['sucursal_id' => $this->progreso->id]);

        $this->assertSame(0, ControlInventario::where('sucursal_id', $this->progreso->id)->count());
        $this->assertSame(1, ControlInventario::where('sucursal_id', $this->jutiapa->id)->count());
    }

    public function test_guardar_dos_veces_no_duplica_filas(): void
    {
        $this->guardar();
        $this->guardar();

        $this->assertSame(1, ControlInventario::count());
    }

    public function test_cada_guardado_queda_en_la_auditoria(): void
    {
        $this->guardar();

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'guardar', 'recurso' => 'control_diario', 'sucursal_id' => $this->jutiapa->id,
        ]);
        $this->assertSame(1, Auditoria::where('recurso', 'control_diario')->count());
    }

    public function test_el_inventario_inicial_sale_del_final_del_dia_anterior(): void
    {
        $this->guardar([], today()->subDay()->toDateString());

        $this->actingAs($this->tienda)
            ->get('/control-diario?fecha=' . today()->toDateString())
            ->assertOk()
            ->assertSee('value="8"', false); // inv_final de ayer = inv_inicial de hoy
    }

    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'jutiapa@mipastel.test', 'password' => 'mala']);
        }

        $this->post('/login', ['email' => 'jutiapa@mipastel.test', 'password' => 'clave-de-prueba'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
