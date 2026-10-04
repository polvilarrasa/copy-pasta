<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Tags\Pages\CreateTag;
use App\Filament\Admin\Resources\Tags\Pages\EditTag;
use App\Filament\Admin\Resources\Tags\Pages\ListTags;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

test('un moderador ve el listado de etiquetas', function (): void {
    $moderator = User::factory()->moderator()->create();
    $tag = Tag::factory()->create();

    Livewire::actingAs($moderator)
        ->test(ListTags::class)
        ->assertCanSeeTableRecords([$tag]);
});

test('un moderador crea una etiqueta desde el formulario', function (): void {
    $moderator = User::factory()->moderator()->create();

    Livewire::actingAs($moderator)
        ->test(CreateTag::class)
        ->fillForm([
            'name' => 'Clásicos',
            'slug' => 'clasicos',
            'color' => 'amber',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('tags', ['slug' => 'clasicos', 'color' => 'amber']);
    $this->assertDatabaseHas('moderation_actions', ['actor_id' => $moderator->id, 'action' => 'tag_created']);
});

test('un moderador desactiva una etiqueta desde el formulario de edición', function (): void {
    $moderator = User::factory()->moderator()->create();
    $tag = Tag::factory()->create(['is_active' => true]);

    Livewire::actingAs($moderator)
        ->test(EditTag::class, ['record' => $tag->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tag->refresh()->is_active)->toBeFalse();
});
