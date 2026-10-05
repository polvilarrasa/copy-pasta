<?php

declare(strict_types=1);

use App\Actions\AnonymizeUser;
use App\Actions\ChangeUsername;
use App\Models\User;
use App\Models\UsernameHistory;

test('anonimizar borra el historial de usernames de la cuenta', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    app(AnonymizeUser::class)->handle($member->refresh(), null);

    expect(UsernameHistory::query()->where('user_id', $member->getKey())->count())->toBe(0);
});

test('tras anonimizar, el nombre antiguo queda libre de inmediato', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');
    app(AnonymizeUser::class)->handle($member->refresh(), null);

    $newcomer = User::factory()->create(['username' => 'otra']);
    app(ChangeUsername::class)->handle($newcomer, 'ana');

    expect($newcomer->refresh()->username)->toBe('ana');
});
