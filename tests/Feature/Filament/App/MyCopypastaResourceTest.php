<?php

declare(strict_types=1);

use App\Filament\App\Resources\MyCopypastas\MyCopypastaResource;
use App\Filament\App\Resources\MyCopypastas\Pages\CreateMyCopypasta;
use App\Filament\App\Resources\MyCopypastas\Pages\EditMyCopypasta;
use App\Filament\App\Resources\MyCopypastas\Pages\ListMyCopypastas;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('app')));

function validCopypastaForm(array $overrides = []): array
{
    return array_merge([
        'title' => 'Titulo válido',
        'body' => 'Cuerpo válido con suficiente texto',
        'tag_ids' => [Tag::factory()->create()->getKey()],
        'is_nsfw' => false,
    ], $overrides);
}

test('la lista muestra solo los copy-pastas del usuario', function (): void {
    $user = User::factory()->create();
    $own = Copypasta::factory()->create(['user_id' => $user->id]);
    $foreign = Copypasta::factory()->create();

    Livewire::actingAs($user)
        ->test(ListMyCopypastas::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

test('un usuario no abre la edición de un copy-pasta ajeno', function (): void {
    $user = User::factory()->create();
    $foreign = Copypasta::factory()->create();

    $this->actingAs($user)
        ->get(MyCopypastaResource::getUrl('edit', ['record' => $foreign]))
        ->assertNotFound();
});

test('un miembro crea un copy-pasta con etiquetas y NSFW', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateMyCopypasta::class)
        ->fillForm(validCopypastaForm(['tag_ids' => [$tag->getKey()], 'is_nsfw' => true]))
        ->call('create')
        ->assertHasNoFormErrors();

    $copypasta = Copypasta::query()->where('title', 'Titulo válido')->firstOrFail();

    expect($copypasta->user_id)->toBe($user->id)
        ->and($copypasta->is_nsfw)->toBeTrue()
        ->and($copypasta->tags->pluck('id')->all())->toBe([$tag->id]);
});

test('valida la longitud del título y del cuerpo', function (): void {
    Livewire::actingAs(User::factory()->create())
        ->test(CreateMyCopypasta::class)
        ->fillForm(validCopypastaForm(['title' => 'abc', 'body' => 'corto']))
        ->call('create')
        ->assertHasFormErrors(['title' => 'min', 'body' => 'min']);
});

test('valida que haya entre una y cinco etiquetas', function (): void {
    $tags = Tag::factory()->count(6)->create()->pluck('id')->all();

    Livewire::actingAs(User::factory()->create())
        ->test(CreateMyCopypasta::class)
        ->fillForm(validCopypastaForm(['tag_ids' => $tags]))
        ->call('create')
        ->assertHasFormErrors(['tag_ids' => 'max']);

    Livewire::actingAs(User::factory()->create())
        ->test(CreateMyCopypasta::class)
        ->fillForm(validCopypastaForm(['tag_ids' => []]))
        ->call('create')
        ->assertHasFormErrors(['tag_ids' => 'required']);
});

test('no ofrece etiquetas desactivadas en el formulario', function (): void {
    $inactive = Tag::factory()->inactive()->create();

    Livewire::actingAs(User::factory()->create())
        ->test(CreateMyCopypasta::class)
        ->fillForm(validCopypastaForm(['tag_ids' => [$inactive->getKey()]]))
        ->call('create')
        ->assertHasFormErrors(['tag_ids']);
});

test('avisa con enlace al existente cuando el cuerpo es duplicado, sin bloquear', function (): void {
    $existing = Copypasta::factory()->create(['body' => 'Cuerpo repetido en el feed']);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateMyCopypasta::class)
        ->fillForm(validCopypastaForm(['body' => 'cuerpo   REPETIDO en el feed']))
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified(__('app.duplicate.title'));

    expect(Copypasta::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Copypasta::query()->whereKey($existing->getKey())->exists())->toBeTrue();
});

test('el formulario de edición conserva la ocultación y muestra el motivo', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->hidden('Datos personales')->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(EditMyCopypasta::class, ['record' => $copypasta->getKey()])
        ->assertSee('Datos personales')
        ->fillForm(validCopypastaForm(['title' => 'Titulo editado']))
        ->call('save')
        ->assertHasNoFormErrors();

    $copypasta->refresh();

    expect($copypasta->title)->toBe('Titulo editado')
        ->and($copypasta->isHidden())->toBeTrue()
        ->and($copypasta->edited_at)->not->toBeNull();
});

test('el listado indica el estado oculto con su motivo', function (): void {
    $user = User::factory()->create();
    Copypasta::factory()->hidden('Spam repetido')->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ListMyCopypastas::class)
        ->assertSee('Oculto')
        ->assertSee('Spam repetido');
});

test('el autor borra su copy-pasta con borrado lógico', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ListMyCopypastas::class)
        ->callAction(TestAction::make('delete')->table($copypasta))
        ->assertHasNoActionErrors();

    expect(Copypasta::withTrashed()->whereKey($copypasta->getKey())->first()?->deleted_at)->not->toBeNull();
});

test('el recurso solo es visible para miembros verificados y activos', function (): void {
    $this->actingAs(User::factory()->unverified()->create());
    expect(MyCopypastaResource::canViewAny())->toBeFalse();

    $this->actingAs(User::factory()->create());
    expect(MyCopypastaResource::canViewAny())->toBeTrue();
});

test('la página de creación usa el recurso propio', function (): void {
    expect(CreateMyCopypasta::getResource())->toBe(MyCopypastaResource::class)
        ->and(ListMyCopypastas::getResource())->toBe(MyCopypastaResource::class)
        ->and(EditMyCopypasta::getResource())->toBe(MyCopypastaResource::class);
});
