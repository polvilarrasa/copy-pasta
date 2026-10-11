<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Actions\DeleteCopypasta;
use App\Actions\HideCopypasta;
use App\Actions\PublishCopypasta;
use App\Actions\RecordCopypastaCopy;
use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function publishVariantOf(Copypasta $parent, User $author, string $body = 'Una variante distinta del original'): Copypasta
{
    return app(PublishCopypasta::class)->handle($author, [
        'title' => 'Variante de prueba',
        'body' => $body,
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ], $parent);
}

function copyTemplateTimes(Copypasta $copypasta, int $times, ?User $user = null): void
{
    foreach (range(1, $times) as $_) {
        app(RecordCopypastaCopy::class)->handle($copypasta, 'visitante-'.Str::random(12), $user);
    }
}

// Remezclador

test('Remezclador: publicar una variante visible del copy-pasta de otra persona lo concede', function (): void {
    $remixer = User::factory()->create();

    publishVariantOf(Copypasta::factory()->create(), $remixer);

    expect(holdsAchievement($remixer, Achievement::Remixer))->toBeTrue();
});

test('Remezclador: un copy-pasta normal no lo concede', function (): void {
    $author = User::factory()->create();

    app(PublishCopypasta::class)->handle($author, ['title' => 'Un copy-pasta', 'body' => 'Texto normal y corriente', 'is_nsfw' => false, 'tag_ids' => [Tag::factory()->create()->getKey()]]);

    expect(holdsAchievement($author, Achievement::Remixer))->toBeFalse();
});

test('Remezclador: ocultar o borrar la variante baja el progreso pero conserva el logro', function (): void {
    $remixer = User::factory()->create();
    $hidden = publishVariantOf(Copypasta::factory()->create(), $remixer);
    $deleted = publishVariantOf(Copypasta::factory()->create(), $remixer);

    expect(achievementProgress($remixer, AchievementMetric::VariantsPublished))->toBe(2);

    app(HideCopypasta::class)->handle(User::factory()->moderator()->create(), $hidden, 'Incumple las normas');
    app(DeleteCopypasta::class)->handle($remixer, $deleted);

    expect(achievementProgress($remixer, AchievementMetric::VariantsPublished))->toBe(0)
        ->and(holdsAchievement($remixer, Achievement::Remixer))->toBeTrue();
});

// Discípulo aventajado

test('Discípulo aventajado: una variante con 10 de score por encima del padre lo concede', function (): void {
    $parent = Copypasta::factory()->create(['score' => 4, 'upvotes_count' => 4]);
    $disciple = User::factory()->create();
    $variant = Copypasta::factory()->variantOf($parent)->for($disciple, 'user')->create(['score' => 9, 'upvotes_count' => 9]);

    app(CastVote::class)->handle(User::factory()->create(), $variant, 1);

    expect($variant->refresh()->score)->toBe(10)
        ->and(holdsAchievement($disciple, Achievement::StarDisciple))->toBeTrue();
});

test('Discípulo aventajado: no se concede con un score de 3 frente a 1', function (): void {
    $parent = Copypasta::factory()->create(['score' => 1, 'upvotes_count' => 1]);
    $disciple = User::factory()->create();
    $variant = Copypasta::factory()->variantOf($parent)->for($disciple, 'user')->create(['score' => 2, 'upvotes_count' => 2]);

    app(CastVote::class)->handle(User::factory()->create(), $variant, 1);

    expect($variant->refresh()->score)->toBe(3)
        ->and(holdsAchievement($disciple, Achievement::StarDisciple))->toBeFalse();
});

test('Discípulo aventajado: con 10 o más pero sin superar al padre no se concede', function (): void {
    $parent = Copypasta::factory()->create(['score' => 20, 'upvotes_count' => 20]);
    $disciple = User::factory()->create();
    $variant = Copypasta::factory()->variantOf($parent)->for($disciple, 'user')->create(['score' => 9, 'upvotes_count' => 9]);

    app(CastVote::class)->handle(User::factory()->create(), $variant, 1);

    expect(holdsAchievement($disciple, Achievement::StarDisciple))->toBeFalse();
});

test('Discípulo aventajado: se evalúa también cuando cambian los votos del padre', function (): void {
    $parent = Copypasta::factory()->create(['score' => 11, 'upvotes_count' => 11]);
    $disciple = User::factory()->create();
    Copypasta::factory()->variantOf($parent)->for($disciple, 'user')->create(['score' => 10, 'upvotes_count' => 10]);

    expect(holdsAchievement($disciple, Achievement::StarDisciple))->toBeFalse();

    app(CastVote::class)->handle(User::factory()->create(), $parent, -1);

    expect($parent->refresh()->score)->toBe(10)
        ->and(holdsAchievement($disciple, Achievement::StarDisciple))->toBeFalse();

    app(CastVote::class)->handle(User::factory()->create(), $parent, -1);

    expect(holdsAchievement($disciple, Achievement::StarDisciple))->toBeTrue();
});

test('Discípulo aventajado: una variante oculta no lo concede', function (): void {
    $parent = Copypasta::factory()->create(['score' => 11, 'upvotes_count' => 11]);
    $disciple = User::factory()->create();
    Copypasta::factory()->variantOf($parent)->hidden()->for($disciple, 'user')->create(['score' => 10, 'upvotes_count' => 10]);

    app(CastVote::class)->handle(User::factory()->create(), $parent, -1);
    app(CastVote::class)->handle(User::factory()->create(), $parent, -1);

    expect(holdsAchievement($disciple, Achievement::StarDisciple))->toBeFalse();
});

// Plantillero

test('Plantillero: 10 copias de otras personas de una misma plantilla lo conceden', function (): void {
    $author = User::factory()->create();
    $template = Copypasta::factory()->template()->for($author, 'user')->create();

    copyTemplateTimes($template, 9);
    expect(holdsAchievement($author, Achievement::Templater))->toBeFalse()
        ->and(achievementProgress($author, AchievementMetric::TemplateCopies))->toBe(9);

    copyTemplateTimes($template, 1);

    expect(holdsAchievement($author, Achievement::Templater))->toBeTrue()
        ->and($template->refresh()->template_copies_by_others)->toBe(10)
        ->and($template->copies_count)->toBe(10);
});

test('Plantillero: las copias del autor no cuentan', function (): void {
    $author = User::factory()->create();
    $template = Copypasta::factory()->template()->for($author, 'user')->create();

    copyTemplateTimes($template, 9);
    app(RecordCopypastaCopy::class)->handle($template, 'autor', $author);

    expect($template->refresh()->template_copies_by_others)->toBe(9)
        ->and($template->copies_count)->toBe(10)
        ->and(holdsAchievement($author, Achievement::Templater))->toBeFalse();
});

test('Plantillero: las copias se cuentan por copy-pasta, no sumadas entre plantillas', function (): void {
    $author = User::factory()->create();

    copyTemplateTimes(Copypasta::factory()->template()->for($author, 'user')->create(), 6);
    copyTemplateTimes(Copypasta::factory()->template()->for($author, 'user')->create(), 6);

    expect(achievementProgress($author, AchievementMetric::TemplateCopies))->toBe(6)
        ->and(holdsAchievement($author, Achievement::Templater))->toBeFalse();
});

test('Plantillero: las copias previas a marcarlo como plantilla no cuentan y desmarcarlo no resta', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    copyTemplateTimes($copypasta, 8);
    $copypasta->forceFill(['is_template' => true, 'body' => 'Hola {{nombre}}, texto de la plantilla'])->save();
    copyTemplateTimes($copypasta->fresh(), 2);

    expect($copypasta->refresh()->template_copies_by_others)->toBe(2)
        ->and(holdsAchievement($author, Achievement::Templater))->toBeFalse();

    $copypasta->forceFill(['is_template' => false])->save();

    expect($copypasta->refresh()->template_copies_by_others)->toBe(2)
        ->and(achievementProgress($author, AchievementMetric::TemplateCopies))->toBe(2);
});

test('una copia de una plantilla guarda template: true en el evento y las demás no', function (): void {
    prepareEventPartitions();

    $template = Copypasta::factory()->template()->create();
    $plain = Copypasta::factory()->create();

    app(RecordCopypastaCopy::class)->handle($template, 'v1', null, ['source' => 'feed']);
    app(RecordCopypastaCopy::class)->handle($plain, 'v2', null, ['source' => 'feed', 'template' => true]);

    $contexts = TrackedEvent::query()->where('type', EventType::Copy)->orderBy('id')->pluck('context');

    expect($contexts[0])->toBe(['source' => 'feed', 'template' => true])
        ->and($contexts[1])->toBe(['source' => 'feed']);
});

test('copiar una plantilla cuenta como copia normal: contador, hitos y progreso de copias recibidas', function (): void {
    $author = User::factory()->create();
    $template = Copypasta::factory()->template()->for($author, 'user')->create(['copies_count' => 9]);

    app(RecordCopypastaCopy::class)->handle($template, 'visitante');

    expect($template->refresh()->copies_count)->toBe(10)
        ->and(achievementProgress($author, AchievementMetric::CopiesReceived))->toBe(1)
        ->and(DB::table('copypasta_milestones')->where('copypasta_id', $template->getKey())->where('threshold', 10)->exists())->toBeTrue();
});

// Backfill

test('app:backfill-achievements calcula los tres logros con los datos existentes y es idempotente', function (): void {
    prepareEventPartitions();

    $remixer = User::factory()->create();
    publishVariantOf(Copypasta::factory()->create(), $remixer);
    Copypasta::factory()->variantOf(Copypasta::factory()->create(['score' => 3]))->for($remixer, 'user')->hidden()->create(['score' => 50]);

    $disciple = User::factory()->create();
    Copypasta::factory()->variantOf(Copypasta::factory()->create(['score' => 4]))->for($disciple, 'user')->create(['score' => 10]);
    $almost = User::factory()->create();
    Copypasta::factory()->variantOf(Copypasta::factory()->create(['score' => 1]))->for($almost, 'user')->create(['score' => 3]);

    $templater = User::factory()->create();
    $template = Copypasta::factory()->template()->for($templater, 'user')->create();
    TrackedEvent::factory()->forCopypasta($template)->ofType(EventType::Copy)->count(10)->create(['context' => ['template' => true]]);
    TrackedEvent::factory()->forCopypasta($template)->ofType(EventType::Copy)->count(5)->create(['context' => []]);
    TrackedEvent::factory()->forCopypasta($template)->ofType(EventType::Copy)->byUser($templater)->count(5)->create(['context' => ['template' => true]]);

    $other = User::factory()->create();
    $otherTemplate = Copypasta::factory()->template()->for($other, 'user')->create();
    TrackedEvent::factory()->forCopypasta($otherTemplate)->ofType(EventType::Copy)->count(9)->create(['context' => ['template' => true]]);

    Artisan::call('app:backfill-achievements', ['--force' => true]);
    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect(holdsAchievement($remixer, Achievement::Remixer))->toBeTrue()
        ->and(achievementProgress($remixer, AchievementMetric::VariantsPublished))->toBe(1)
        ->and(holdsAchievement($remixer, Achievement::StarDisciple))->toBeFalse()
        ->and(holdsAchievement($disciple, Achievement::StarDisciple))->toBeTrue()
        ->and(holdsAchievement($almost, Achievement::StarDisciple))->toBeFalse()
        ->and($template->refresh()->template_copies_by_others)->toBe(10)
        ->and(holdsAchievement($templater, Achievement::Templater))->toBeTrue()
        ->and($otherTemplate->refresh()->template_copies_by_others)->toBe(9)
        ->and(holdsAchievement($other, Achievement::Templater))->toBeFalse();
});

test('el backfill no baja el contador de copias de plantilla que ya se contó en vivo', function (): void {
    prepareEventPartitions();

    $template = Copypasta::factory()->template()->create(['template_copies_by_others' => 7]);
    TrackedEvent::factory()->forCopypasta($template)->ofType(EventType::Copy)->count(3)->create(['context' => ['template' => true]]);

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect($template->refresh()->template_copies_by_others)->toBe(7);
});
