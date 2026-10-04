<?php

declare(strict_types=1);

use App\Actions\DeleteOwnAccount;
use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Auth\Access\AuthorizationException;

test('anonimiza la cuenta y libera su usuario y su email para volver a usarlos', function (): void {
    $member = User::factory()->create(['username' => 'pol_vila']);
    $email = $member->email;

    app(DeleteOwnAccount::class)->handle($member);

    expect($member->refresh()->trashed())->toBeTrue()
        ->and($member->anonymized_at)->not->toBeNull()
        ->and(User::factory()->create(['username' => 'pol_vila', 'email' => $email])->exists)->toBeTrue();
});

test('retira sus votos de los contadores de los copy-pastas', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['upvotes_count' => 2, 'downvotes_count' => 1, 'score' => 1]);

    Vote::factory()->upvote()->create(['user_id' => $member->getKey(), 'copypasta_id' => $copypasta->getKey()]);
    Vote::factory()->upvote()->create(['copypasta_id' => $copypasta->getKey()]);
    Vote::factory()->downvote()->create(['copypasta_id' => $copypasta->getKey()]);

    app(DeleteOwnAccount::class)->handle($member);

    $copypasta->refresh();

    expect($copypasta->upvotes_count)->toBe(1)
        ->and($copypasta->downvotes_count)->toBe(1)
        ->and($copypasta->score)->toBe(0)
        ->and(Vote::query()->where('user_id', $member->getKey())->exists())->toBeFalse();
});

test('retira sus favoritos del contador sin quitar el copy-pasta de las carpetas de otros', function (): void {
    $member = User::factory()->create();
    $otherMember = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 2]);

    Folder::ensureDefaultFor($member)->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);
    Folder::ensureDefaultFor($otherMember)->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    app(DeleteOwnAccount::class)->handle($member);

    expect($copypasta->refresh()->favorites_count)->toBe(1)
        ->and(Folder::ensureDefaultFor($otherMember)->copypastas()->whereKey($copypasta->getKey())->exists())->toBeTrue();
});

test('borra sus carpetas y las entradas que contenían', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $folder = Folder::factory()->create(['user_id' => $member->getKey(), 'is_default' => false]);
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    app(DeleteOwnAccount::class)->handle($member);

    expect(Folder::query()->whereKey($folder->getKey())->exists())->toBeFalse()
        ->and($copypasta->refresh()->exists)->toBeTrue();
});

test('conserva sus copy-pastas publicados y los muestra como de un usuario eliminado', function (): void {
    $member = User::factory()->create(['username' => 'pol_vila']);
    $copypasta = Copypasta::factory()->create(['user_id' => $member->getKey()]);

    app(DeleteOwnAccount::class)->handle($member);

    expect($copypasta->refresh()->hidden_at)->toBeNull();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk()
        ->assertSee('usuario eliminado')
        ->assertDontSee('pol_vila');
});

test('conserva los reportes que envió', function (): void {
    $member = User::factory()->create();
    $report = Report::factory()->create(['reporter_id' => $member->getKey()]);

    app(DeleteOwnAccount::class)->handle($member);

    expect($report->refresh()->reporter_id)->toBe($member->getKey());
});

test('registra la anonimización en el log con el propio miembro como actor', function (): void {
    $member = User::factory()->create();

    app(DeleteOwnAccount::class)->handle($member);

    expect(ModerationAction::query()->where('action', ModerationActionType::UserAnonymized)->sole())
        ->actor_id->toEqual($member->getKey())
        ->subject_id->toEqual($member->getKey());
});

test('no permite anonimizar de nuevo una cuenta ya anonimizada', function (): void {
    $member = User::factory()->create();
    app(DeleteOwnAccount::class)->handle($member);

    app(DeleteOwnAccount::class)->handle($member->refresh());
})->throws(AuthorizationException::class);
