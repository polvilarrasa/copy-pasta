<?php

declare(strict_types=1);

use App\Models\User;

test('el panel de administración enlaza de vuelta a la web pública', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee(__('admin.back_to_web'))
        ->assertSee(route('home'), escape: false);
});
