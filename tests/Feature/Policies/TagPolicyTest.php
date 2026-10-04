<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un usuario normal no crea, edita ni borra etiquetas', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();

    expect(Gate::forUser($user)->allows('create', Tag::class))->toBeFalse();
    expect(Gate::forUser($user)->allows('update', $tag))->toBeFalse();
    expect(Gate::forUser($user)->allows('delete', $tag))->toBeFalse();
});

test('un moderador crea, edita y borra etiquetas', function (): void {
    $moderator = User::factory()->moderator()->create();
    $tag = Tag::factory()->create();

    expect(Gate::forUser($moderator)->allows('create', Tag::class))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('update', $tag))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('delete', $tag))->toBeTrue();
});

test('un admin crea, edita y borra etiquetas', function (): void {
    $admin = User::factory()->admin()->create();
    $tag = Tag::factory()->create();

    expect(Gate::forUser($admin)->allows('create', Tag::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $tag))->toBeTrue();
    expect(Gate::forUser($admin)->allows('delete', $tag))->toBeTrue();
});

test('solo el staff lista y ve etiquetas en el panel de administración', function (): void {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $tag = Tag::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Tag::class))->toBeFalse();
    expect(Gate::forUser($user)->allows('view', $tag))->toBeFalse();
    expect(Gate::forUser($moderator)->allows('viewAny', Tag::class))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('view', $tag))->toBeTrue();
});
