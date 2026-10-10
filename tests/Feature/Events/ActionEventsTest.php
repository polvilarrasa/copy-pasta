<?php

declare(strict_types=1);

use App\Actions\AddToFolder;
use App\Actions\CastVote;
use App\Actions\RecordCopypastaCopy;
use App\Actions\RemoveFromFolder;
use App\Actions\ReportCopypasta;
use App\Actions\ToggleFavorite;
use App\Enums\EventType;
use App\Enums\ReportReason;
use App\Livewire\Feed;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\TrackedEvent;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => prepareEventPartitions());

test('votar a favor registra un evento vote_up con el contexto de la lista', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    app(CastVote::class)->handle($member, $copypasta, 1, ['source' => 'random', 'position' => 2]);

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::VoteUp)
        ->user_id->toBe($member->getKey())
        ->copypasta_id->toBe($copypasta->getKey())
        ->context->toEqualCanonicalizing(['source' => 'random', 'position' => 2, 'previous' => null, 'next' => 1]);
});

test('repetir el mismo voto registra vote_removed y el voto contrario registra vote_down', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    app(CastVote::class)->handle($member, $copypasta, 1);
    app(CastVote::class)->handle($member, $copypasta, 1);
    app(CastVote::class)->handle($member, $copypasta, -1);

    expect(TrackedEvent::query()->orderBy('id')->pluck('type')->all())
        ->toBe([EventType::VoteUp, EventType::VoteRemoved, EventType::VoteDown]);
});

test('guardar en favoritos registra favorite_add y quitarlos registra favorite_remove', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    app(ToggleFavorite::class)->handle($member, $copypasta);
    app(ToggleFavorite::class)->handle($member, $copypasta);

    expect(TrackedEvent::query()->orderBy('id')->pluck('type')->all())
        ->toBe([EventType::FavoriteAdd, EventType::FavoriteRemove]);
});

test('añadir a una carpeta normal registra folder_add y quitarla registra folder_remove', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $folder = Folder::factory()->for($member)->create(['is_default' => false]);

    app(AddToFolder::class)->handle($member, $folder, $copypasta, ['source' => 'new', 'position' => 0]);
    app(RemoveFromFolder::class)->handle($member, $folder, $copypasta);

    expect(TrackedEvent::query()->orderBy('id')->get())
        ->toHaveCount(2)
        ->and($events = TrackedEvent::query()->orderBy('id')->get())
        ->and($events[0]->type)->toBe(EventType::FolderAdd)
        ->and($events[0]->context)->toEqualCanonicalizing(['source' => 'new', 'position' => 0])
        ->and($events[1]->type)->toBe(EventType::FolderRemove);
});

test('una copia contada registra un evento copy y una repetida en la misma hora no registra nada', function (): void {
    $copypasta = Copypasta::factory()->create();
    $action = app(RecordCopypastaCopy::class);

    $action->handle($copypasta, '203.0.113.7', null, ['source' => 'top_all']);
    $action->handle($copypasta, '203.0.113.7', null, ['source' => 'top_all']);

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::Copy)
        ->context->toEqualCanonicalizing(['source' => 'top_all']);
});

test('reportar registra un evento report con el motivo', function (): void {
    $reporter = User::factory()->established()->create();
    $copypasta = Copypasta::factory()->create();

    app(ReportCopypasta::class)->handle($reporter, $copypasta, ReportReason::Spam, null, ['source' => 'random']);

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::Report)
        ->user_id->toBe($reporter->getKey())
        ->context->toEqualCanonicalizing(['source' => 'random', 'reason' => 'spam']);
});

test('compartir sin sesión registra un evento share sin referencia', function (): void {
    $copypasta = Copypasta::factory()->create();

    $response = $this->postJson(route('copypastas.share', $copypasta), ['source' => 'top_week', 'position' => 5, 'ref' => 'forjado1'])
        ->assertOk();

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::Share)
        ->copypasta_id->toBe($copypasta->getKey())
        ->context->toEqualCanonicalizing(['source' => 'top_week', 'position' => 5, 'method' => 'link'])
        ->and($response->json('ref'))->toBeNull();
});

test('compartir con sesión devuelve y registra el share_code del usuario, no el que envía el cliente', function (): void {
    $copypasta = Copypasta::factory()->create();
    $member = User::factory()->create();

    $response = $this->actingAs($member)
        ->postJson(route('copypastas.share', $copypasta), ['ref' => 'forjado1', 'method' => 'image'])
        ->assertOk();

    expect($response->json('ref'))->toBe($member->share_code)
        ->and(TrackedEvent::query()->sole()->context)
        ->toEqualCanonicalizing(['ref' => $member->share_code, 'method' => 'image']);
});

test('abrir la página de detalle registra detail_view con el origen y la referencia de la query', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]).'?from=random&pos=4&ref=aB3dE9fG')
        ->assertOk();

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::DetailView)
        ->context->toEqualCanonicalizing(['source' => 'random', 'position' => 4, 'ref' => 'aB3dE9fG']);
});

test('buscar en el feed registra un evento search con el término solo para miembros', function (): void {
    $member = User::factory()->create();

    Livewire::actingAs($member)->test(Feed::class)->set('search', 'cafe');

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::Search)
        ->user_id->toBe($member->getKey())
        ->context->toMatchArray(['query' => 'cafe']);
});

test('buscar sin cuenta registra search sin guardar el término', function (): void {
    Livewire::test(Feed::class)->set('search', 'cafe');

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::Search)
        ->user_id->toBeNull()
        ->context->not->toHaveKey('query');
});

test('un fallo al registrar el evento no impide que el voto se guarde', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    TrackedEvent::creating(fn () => throw new RuntimeException('disco lleno'));

    $result = app(CastVote::class)->handle($member, $copypasta, 1);

    expect($result)->toBe(1)
        ->and($copypasta->refresh()->upvotes_count)->toBe(1)
        ->and(TrackedEvent::query()->count())->toBe(0);
});
