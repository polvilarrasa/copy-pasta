<?php

declare(strict_types=1);

use App\Actions\ChangeUsername;
use App\Models\User;
use Livewire\Livewire;

test('ajustes muestra un error si el username ya se cambió hace menos de 30 días', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    $this->actingAs($member->refresh());

    Livewire::test('pages::settings.profile')
        ->set('username', 'ana_otra')
        ->call('updateProfileInformation')
        ->assertHasErrors(['username']);

    expect($member->refresh()->username)->toBe('ana_nueva');
});
