<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un invitado ve copy-pastas visibles pero no los ocultos', function (): void {
    $visible = Copypasta::factory()->create();
    $hidden = Copypasta::factory()->hidden()->create();

    expect(Gate::allows('view', $visible))->toBeTrue();
    expect(Gate::allows('view', $hidden))->toBeFalse();
});

test('el autor ve, edita y borra su copy-pasta aunque esté oculto', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->hidden()->for($author, 'user')->create();

    expect(Gate::forUser($author)->allows('view', $copypasta))->toBeTrue();
    expect(Gate::forUser($author)->allows('update', $copypasta))->toBeTrue();
    expect(Gate::forUser($author)->allows('delete', $copypasta))->toBeTrue();
});

test('un usuario normal no edita, borra ni oculta copy-pastas ajenos', function (): void {
    $other = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    expect(Gate::forUser($other)->allows('update', $copypasta))->toBeFalse();
    expect(Gate::forUser($other)->allows('delete', $copypasta))->toBeFalse();
    expect(Gate::forUser($other)->allows('hide', $copypasta))->toBeFalse();
});

test('un usuario sin email verificado no puede publicar', function (): void {
    $unverified = User::factory()->unverified()->create();
    $verified = User::factory()->create();

    expect(Gate::forUser($unverified)->allows('create', Copypasta::class))->toBeFalse();
    expect(Gate::forUser($verified)->allows('create', Copypasta::class))->toBeTrue();
});

test('un moderador ve, oculta, restaura y marca nsfw cualquier copy-pasta', function (): void {
    $moderator = User::factory()->moderator()->create();
    $hidden = Copypasta::factory()->hidden()->create();

    expect(Gate::forUser($moderator)->allows('view', $hidden))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('hide', $hidden))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('restore', $hidden))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('markNsfw', $hidden))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('update', $hidden))->toBeFalse();
});

test('un admin tiene los mismos permisos de moderación que un moderador', function (): void {
    $admin = User::factory()->admin()->create();
    $hidden = Copypasta::factory()->hidden()->create();

    expect(Gate::forUser($admin)->allows('hide', $hidden))->toBeTrue();
    expect(Gate::forUser($admin)->allows('restore', $hidden))->toBeTrue();
    expect(Gate::forUser($admin)->allows('markNsfw', $hidden))->toBeTrue();
});

test('solo el staff lista copy-pastas en el panel de administración', function (): void {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();

    expect(Gate::forUser($user)->allows('viewAny', Copypasta::class))->toBeFalse();
    expect(Gate::forUser($moderator)->allows('viewAny', Copypasta::class))->toBeTrue();
});

test('votar y guardar: miembros verificados sí, el autor no, y no sobre ocultos', function (): void {
    $member = User::factory()->create();
    $unverified = User::factory()->unverified()->create();
    $author = User::factory()->create();
    $published = Copypasta::factory()->create(['user_id' => $author->id]);
    $hidden = Copypasta::factory()->hidden()->create();
    $unpublished = Copypasta::factory()->unpublished()->create();

    expect(Gate::forUser($member)->allows('vote', $published))->toBeTrue()
        ->and(Gate::forUser($member)->allows('favorite', $published))->toBeTrue()
        ->and(Gate::forUser($unverified)->allows('vote', $published))->toBeTrue()
        ->and(Gate::forUser($author)->allows('vote', $published))->toBeFalse()
        ->and(Gate::forUser($author)->allows('favorite', $published))->toBeFalse()
        ->and(Gate::forUser($member)->allows('vote', $hidden))->toBeFalse()
        ->and(Gate::forUser($member)->allows('favorite', $unpublished))->toBeFalse();
});
