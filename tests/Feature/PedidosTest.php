<?php

namespace Tests\Feature;

use App\Models\PedidoCliente;
use App\Models\PedidoNormal;
use App\Models\Precio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidosTest extends TestCase
{
    use RefreshDatabase;

    private Sucursal $jutiapa;
    private Sucursal $progreso;
    private User $tiendaJutiapa;
    private User $tiendaProgreso;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jutiapa = Sucursal::create(['nombre' => 'Jutiapa 1']);
        $this->progreso = Sucursal::create(['nombre' => 'Progreso']);

        $this->tiendaJutiapa = $this->usuario('jutiapa@mipastel.test', 'sucursal', $this->jutiapa);
        $this->tiendaProgreso = $this->usuario('progreso@mipastel.test', 'sucursal', $this->progreso);
        $this->admin = $this->usuario('admin@mipastel.test', 'admin');

        Precio::create(['sabor' => 'Fresas', 'tamano' => 'Mediano', 'precio' => 125]);
    }

    private function usuario(string $correo, string $rol, ?Sucursal $sucursal = null): User
    {
        return User::create([
            'name' => $correo,
            'email' => $correo,
            'password' => 'clave-de-prueba',
            'rol' => $rol,
            'sucursal_id' => $sucursal?->id,
        ]);
    }

    private function pedido(Sucursal $sucursal, string $entrega = null): PedidoNormal
    {
        return PedidoNormal::create([
            'sucursal_id' => $sucursal->id,
            'sabor' => 'Fresas',
            'tamano' => 'Mediano',
            'cantidad' => 2,
            'precio' => 125,
            'fecha' => now(),
            'fecha_entrega' => $entrega ?? today()->addDay()->toDateString(),
        ]);
    }

    public function test_la_zona_horaria_es_guatemala(): void
    {
        $this->assertSame('America/Guatemala', config('app.timezone'));
    }

    public function test_sin_sesion_se_redirige_al_login(): void
    {
        $this->get('/pedidos')->assertRedirect('/login');
        $this->postJson('/pedidos/normales', [])->assertUnauthorized();
    }

    public function test_el_precio_sale_del_catalogo_y_no_de_lo_que_manda_el_navegador(): void
    {
        $this->actingAs($this->tiendaJutiapa)->postJson('/pedidos/normales', [
            'sabor' => 'Fresas',
            'tamano' => 'Mediano',
            'cantidad' => 2,
            'fecha_entrega' => today()->addDay()->toDateString(),
            'precio' => 1, // intento de abaratar el pedido
        ])->assertCreated();

        $pedido = PedidoNormal::firstOrFail();
        $this->assertEquals(125, $pedido->precio);
        $this->assertEquals(250, $pedido->fresh()->total);
    }

    public function test_una_tienda_siempre_registra_en_su_propia_sucursal(): void
    {
        $this->actingAs($this->tiendaJutiapa)->postJson('/pedidos/normales', [
            'sabor' => 'Fresas',
            'tamano' => 'Mediano',
            'cantidad' => 1,
            'fecha_entrega' => today()->toDateString(),
            'sucursal_id' => $this->progreso->id, // intento de registrar en otra tienda
        ])->assertCreated();

        $this->assertSame($this->jutiapa->id, PedidoNormal::firstOrFail()->sucursal_id);
    }

    public function test_el_pedido_de_otra_sucursal_no_se_puede_editar_ni_borrar(): void
    {
        $ajeno = $this->pedido($this->progreso);

        $this->actingAs($this->tiendaJutiapa)
            ->putJson("/pedidos/normales/{$ajeno->id}", [
                'cantidad' => 9,
                'fecha_entrega' => today()->addDay()->toDateString(),
            ])->assertForbidden();

        $this->actingAs($this->tiendaJutiapa)
            ->deleteJson("/pedidos/normales/{$ajeno->id}")->assertForbidden();

        $this->assertDatabaseHas('pedidos_normales', ['id' => $ajeno->id, 'cantidad' => 2]);
    }

    public function test_el_listado_solo_trae_pedidos_de_la_sucursal_del_usuario(): void
    {
        $this->pedido($this->jutiapa);
        $this->pedido($this->progreso);

        $respuesta = $this->actingAs($this->tiendaJutiapa)->getJson('/pedidos/normales');

        $respuesta->assertOk()->assertJsonCount(1, 'pedidos');
    }

    public function test_un_pedido_con_entrega_pasada_no_se_puede_editar(): void
    {
        $viejo = $this->pedido($this->jutiapa, today()->subDay()->toDateString());

        $this->actingAs($this->tiendaJutiapa)
            ->putJson("/pedidos/normales/{$viejo->id}", [
                'cantidad' => 5,
                'fecha_entrega' => today()->toDateString(),
            ])->assertStatus(422);
    }

    public function test_no_se_acepta_fecha_de_entrega_anterior_a_hoy(): void
    {
        $this->actingAs($this->tiendaJutiapa)->postJson('/pedidos/normales', [
            'sabor' => 'Fresas',
            'tamano' => 'Mediano',
            'cantidad' => 1,
            'fecha_entrega' => today()->subDay()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('fecha_entrega');
    }

    public function test_pedido_de_cliente_con_sabor_otro_exige_precio_manual(): void
    {
        $this->actingAs($this->tiendaJutiapa)->postJson('/pedidos/clientes', [
            'sabor' => 'Otro',
            'sabor_personalizado' => 'Maracuyá',
            'tamano' => 'Grande',
            'cantidad' => 1,
            'fecha_entrega' => today()->addDay()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('precio');

        $this->actingAs($this->tiendaJutiapa)->postJson('/pedidos/clientes', [
            'sabor' => 'Otro',
            'sabor_personalizado' => 'Maracuyá',
            'tamano' => 'Grande',
            'cantidad' => 1,
            'precio' => 200,
            'fecha_entrega' => today()->addDay()->toDateString(),
        ])->assertCreated();

        $this->assertSame(1, PedidoCliente::count());
    }

    public function test_una_tienda_no_entra_a_las_pantallas_del_administrador(): void
    {
        foreach (['/admin', '/admin/precios', '/admin/auditoria', '/admin/reportes', '/productos'] as $ruta) {
            $this->actingAs($this->tiendaJutiapa)->get($ruta)->assertForbidden();
        }

        $this->actingAs($this->tiendaJutiapa)
            ->putJson('/admin/precios', [])->assertForbidden();
    }

    public function test_el_admin_si_entra_al_panel(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertOk();
    }
}
