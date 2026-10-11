<?php

declare(strict_types=1);

use App\Actions\NotifyCopypastaVariant;
use App\Actions\PublishCopypasta;
use App\Enums\NotificationType;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use App\Support\NotificationPresenter;

function publishVariant(Copypasta $parent, ?User $author = null, string $body = 'Una variante cualquiera distinta'): Copypasta
{
    return app(PublishCopypasta::class)->handle($author ?? User::factory()->create(), [
        'title' => 'Variante de prueba',
        'body' => $body,
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ], $parent);
}

/**
 * @return array<int, array<string, mixed>>
 */
function variantNotifications(User $user): array
{
    return $user->notifications()->where('type', NotificationType::VariantPublished->value)->get()
        ->map(fn ($notification): array => $notification->data)
        ->all();
}

test('publicar una variante notifica al autor del original con el id y sin nombrar a nadie', function (): void {
    $parent = Copypasta::factory()->create();

    publishVariant($parent);

    expect(variantNotifications($parent->user))->toEqual([[
        'type' => 'variant_published',
        'copypasta_id' => $parent->getKey(),
        'count' => 1,
    ]]);
});

test('varias variantes del mismo original en menos de una hora se agrupan en una notificación', function (): void {
    $parent = Copypasta::factory()->create();

    publishVariant($parent, body: 'Primera variante distinta');
    $this->travel(30)->minutes();
    publishVariant($parent, body: 'Segunda variante distinta');

    expect(variantNotifications($parent->user))->toHaveCount(1)
        ->and(variantNotifications($parent->user)[0]['count'])->toBe(2);
});

test('pasada una hora, o ya leída, la siguiente variante crea otra notificación', function (): void {
    $parent = Copypasta::factory()->create();

    publishVariant($parent, body: 'Primera variante distinta');
    $this->travel(61)->minutes();
    publishVariant($parent, body: 'Segunda variante distinta');
    $parent->user->unreadNotifications->markAsRead();
    publishVariant($parent, body: 'Tercera variante distinta');

    expect(variantNotifications($parent->user))->toHaveCount(3);
});

test('las variantes de originales distintos no se agrupan', function (): void {
    $author = User::factory()->create();
    $first = Copypasta::factory()->for($author, 'user')->create();
    $second = Copypasta::factory()->for($author, 'user')->create();

    publishVariant($first);
    publishVariant($second);

    expect(variantNotifications($author))->toHaveCount(2);
});

test('no notifica al propio autor de la variante', function (): void {
    $author = User::factory()->create();
    $parent = Copypasta::factory()->for($author, 'user')->create();
    $variant = Copypasta::factory()->variantOf($parent)->for($author, 'user')->create();

    app(NotifyCopypastaVariant::class)->handle($variant);

    expect(variantNotifications($author))->toBe([]);
});

test('no notifica a una cuenta baneada ni anonimizada', function (string $state): void {
    $owner = $state === 'baneada' ? User::factory()->banned()->create() : User::factory()->create(['anonymized_at' => now()]);
    $parent = Copypasta::factory()->for($owner, 'user')->create();

    publishVariant($parent);

    expect(variantNotifications($owner))->toBe([]);
})->with(['baneada', 'anonimizada']);

test('quien desactiva el tipo no recibe el aviso', function (): void {
    $parent = Copypasta::factory()->create();
    $parent->user->forceFill(['notification_prefs' => ['variant_published' => false]])->save();

    publishVariant($parent);

    expect(variantNotifications($parent->user))->toBe([]);
});

test('el tipo es configurable y todos los usuarios lo tienen activado por defecto', function (): void {
    expect(NotificationType::configurable())->toContain(NotificationType::VariantPublished)
        ->and(User::factory()->create()->wantsNotification(NotificationType::VariantPublished))->toBeTrue();
});

test('el texto es singular o plural, enlaza a las variantes del original y no nombra a nadie', function (): void {
    $parent = Copypasta::factory()->create(['title' => 'Original con variantes']);
    publishVariant($parent, User::factory()->create(['username' => 'remezclador']), 'Primera variante distinta');

    $present = fn () => app(NotificationPresenter::class)->present($parent->user->fresh()->notifications)->sole();

    expect($present()->text)->toBe('Alguien ha publicado una variante de tu copy-pasta «Original con variantes».')
        ->and($present()->text)->not->toContain('remezclador')
        ->and($present()->url)->toBe(route('copypastas.show', [$parent, $parent->slug]).'#variantes');

    publishVariant($parent, body: 'Segunda variante distinta');

    expect($present()->text)->toBe('Se han publicado 2 variantes de tu copy-pasta «Original con variantes».');
});

test('con el original oculto o borrado el texto es Contenido retirado y sin enlace', function (): void {
    $parent = Copypasta::factory()->create();
    publishVariant($parent);
    $parent->delete();

    $item = app(NotificationPresenter::class)->present($parent->user->fresh()->notifications)->sole();

    expect($item->text)->toBe(__('notifications.removed'))->and($item->url)->toBeNull();
});
