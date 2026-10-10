<?php

declare(strict_types=1);

use App\Models\Copypasta;

test('las URLs del antiguo panel /app redirigen de forma permanente a las nuevas', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get('/app')->assertRedirect('/estadisticas');
    $this->get('/app/copypastas')->assertRedirect('/mis-copypastas');
    $this->get('/app/copypastas/create')->assertRedirect('/publicar');
    $this->get('/app/copypastas/'.$copypasta->getKey().'/edit')->assertRedirect('/c/'.$copypasta->getKey().'/editar');
    $this->get('/app/carpetas')->assertRedirect('/carpetas');
    $this->get('/app/carpetas/3')->assertRedirect('/carpetas/3');
});

test('las redirecciones del antiguo panel son permanentes (301)', function (): void {
    $this->get('/app')->assertStatus(301);
});
