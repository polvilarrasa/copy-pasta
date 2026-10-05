<?php

declare(strict_types=1);

use App\Actions\ReportCopypasta;
use App\Actions\SaveCopypastaRevision;
use App\Actions\UpdateCopypasta;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Filament\Admin\Pages\ModerationQueue;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

test('solo el staff abre la cola de reportes', function (): void {
    $this->actingAs(User::factory()->moderator()->withTwoFactor()->create())
        ->get(ModerationQueue::getUrl(panel: 'admin'))
        ->assertOk();

    $this->actingAs(User::factory()->create())
        ->get(ModerationQueue::getUrl(panel: 'admin'))
        ->assertForbidden();
});

test('la cola agrupa por copy-pasta y solo muestra los que tienen reportes pendientes', function (): void {
    $reported = Copypasta::factory()->create();
    Report::factory()->for($reported)->count(2)->create();
    $resolvedOnly = Copypasta::factory()->create();
    Report::factory()->for($resolvedOnly)->resolved()->create();

    Livewire::actingAs(User::factory()->moderator()->withTwoFactor()->create())
        ->test(ModerationQueue::class)
        ->call('loadTable')
        ->assertCanSeeTableRecords([$reported])
        ->assertCanNotSeeTableRecords([$resolvedOnly]);
});

test('los copy-pastas ocultos aparecen primero en la cola', function (): void {
    $visible = Copypasta::factory()->create();
    Report::factory()->for($visible)->count(5)->create();
    $hidden = Copypasta::factory()->hidden()->create();
    Report::factory()->for($hidden)->create();

    Livewire::actingAs(User::factory()->moderator()->withTwoFactor()->create())
        ->test(ModerationQueue::class)
        ->call('loadTable')
        ->assertCanSeeTableRecords([$hidden, $visible], inOrder: true);
});

test('el staff oculta desde la cola con motivo y los reportes quedan aceptados', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $copypasta = Copypasta::factory()->create();
    Report::factory()->for($copypasta)->create();

    Livewire::actingAs($moderator)
        ->test(ModerationQueue::class)
        ->call('loadTable')
        ->callAction(TestAction::make('hide')->table($copypasta), data: ['reason' => 'Acoso reiterado'])
        ->assertHasNoActionErrors();

    expect($copypasta->refresh()->isHidden())->toBeTrue()
        ->and(Report::query()->where('copypasta_id', $copypasta->id)->first()->status)->toBe(ReportStatus::Accepted);
});

test('restaurar solo aparece en los copy-pastas ocultos', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $visible = Copypasta::factory()->create();
    Report::factory()->for($visible)->create();
    $hidden = Copypasta::factory()->hidden()->create();
    Report::factory()->for($hidden)->create();

    Livewire::actingAs($moderator)
        ->test(ModerationQueue::class)
        ->call('loadTable')
        ->assertTableActionVisible('restore', $hidden)
        ->assertTableActionHidden('restore', $visible)
        ->assertTableActionVisible('hide', $visible)
        ->assertTableActionHidden('hide', $hidden);
});

test('la resolución en bloque descarta los reportes de varios copy-pastas a la vez', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $first = Copypasta::factory()->create();
    Report::factory()->for($first)->count(2)->create();
    $second = Copypasta::factory()->create();
    Report::factory()->for($second)->create();

    Livewire::actingAs($moderator)
        ->test(ModerationQueue::class)
        ->call('loadTable')
        ->selectTableRecords([$first, $second])
        ->callAction(TestAction::make('dismiss')->table()->bulk());

    expect(Report::query()->pending()->count())->toBe(0)
        ->and(Report::query()->where('status', ReportStatus::Rejected)->count())->toBe(3);
});

test('la navegación muestra el número de reportes pendientes', function (): void {
    $copypasta = Copypasta::factory()->create();
    Report::factory()->for($copypasta)->count(3)->create();
    Report::factory()->for($copypasta)->resolved()->create();

    expect(ModerationQueue::getNavigationBadge())->toBe('3');
});

test('el moderador ve el texto reportado aunque el autor lo haya editado después', function (): void {
    $author = User::factory()->established()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $author->getKey(), 'title' => 'Titulo', 'body' => 'Texto original reportado']);
    app(SaveCopypastaRevision::class)->handle($copypasta);
    app(ReportCopypasta::class)->handle(User::factory()->established()->create(), $copypasta, ReportReason::Spam, null);

    app(UpdateCopypasta::class)->handle($author, $copypasta->refresh(), [
        'title' => 'Titulo',
        'body' => 'Texto editado para esconder el abuso',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ]);

    $component = Livewire::actingAs(User::factory()->moderator()->withTwoFactor()->create())
        ->test(ModerationQueue::class)
        ->call('loadTable');

    $body = collect($component->instance()->reportedDiffs($copypasta)[0]['body']);

    expect($body->where('type', 'delete')->pluck('text')->implode(' '))->toBe('original reportado')
        ->and($body->where('type', 'insert')->pluck('text')->implode(' '))->toBe('editado para esconder el abuso');
});

test('los reportes por menores aparecen antes que el resto de la cola', function (): void {
    $spam = Copypasta::factory()->create();
    Report::factory()->for($spam)->count(3)->create();
    $minors = Copypasta::factory()->create();
    Report::factory()->for($minors)->create(['reason' => ReportReason::SexualContentMinors]);

    Livewire::actingAs(User::factory()->moderator()->withTwoFactor()->create())
        ->test(ModerationQueue::class)
        ->call('loadTable')
        ->assertCanSeeTableRecords([$minors, $spam], inOrder: true);
});
