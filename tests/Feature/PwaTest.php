<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_el_manifest_es_json_valido_y_sus_iconos_existen_en_disco(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertIsArray($manifest);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['start_url']);

        $tamanos = [];
        foreach ($manifest['icons'] as $icono) {
            $this->assertFileExists(public_path(ltrim($icono['src'], '/')), "Falta el ícono {$icono['src']}");
            $tamanos[] = $icono['sizes'];
        }

        // Chrome exige al menos un ícono de 192 y uno de 512 para ofrecer la instalación
        $this->assertContains('192x192', $tamanos);
        $this->assertContains('512x512', $tamanos);
    }

    public function test_el_service_worker_y_los_archivos_que_precarga_existen(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        foreach (['css/mipastel.css', 'Logo.png', 'icons/icon-192.png'] as $archivo) {
            $this->assertFileExists(public_path($archivo));
        }
    }

    public function test_la_pantalla_de_login_enlaza_el_manifest_y_trae_el_boton_de_instalar(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('id="btnInstalar"', false)
            ->assertSee("register('/sw.js')", false);
    }

    public function test_la_pagina_sin_conexion_es_publica(): void
    {
        $this->get('/offline')->assertOk()->assertSee('Sin conexión');
    }
}
