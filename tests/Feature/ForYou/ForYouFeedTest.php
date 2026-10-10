<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Actions\DismissCopypasta;
use App\Actions\ListForYouFeed;
use App\Actions\RecordCopypastaCopy;
use App\Actions\UpdateFavoriteTags;
use App\Livewire\Feed;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\TrackedEvent;
use App\Models\User;
use App\Support\ForYouCache;
use App\Support\ForYouItem;
use App\Support\TagAffinities;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Copy-pastas by other authors carrying the tag, published the given number of days ago.
 *
 * @return Collection<int, Copypasta>
 */
function catalog(Tag $tag, int $count, float $daysAgo = 5, array $attributes = []): Collection
{
    return Copypasta::factory()->count($count)->create([
        'published_at' => now()->subHours((int) ($daysAgo * 24)),
        ...$attributes,
    ])->each(fn (Copypasta $copypasta) => $copypasta->tags()->attach($tag->id));
}

function favoriteTagsOf(User $member, Tag ...$tags): void
{
    foreach ($tags as $tag) {
        DB::table('user_favorite_tags')->insert(['user_id' => $member->id, 'tag_id' => $tag->id, 'created_at' => now()]);
    }
}

/**
 * @return list<ForYouItem>
 */
function forYouItems(User $member, int $limit = 20, bool $refresh = false): array
{
    return app(ListForYouFeed::class)->handle($member, $limit, $refresh)->items;
}

beforeEach(function (): void {
    $this->liked = Tag::factory()->create(['name' => 'humor']);
    $this->other = Tag::factory()->create(['name' => 'gaming']);
    $this->fresh = Tag::factory()->create(['name' => 'noticias']);
    $this->member = User::factory()->create();
    favoriteTagsOf($this->member, $this->liked);
});

test('una página de 20 lleva 14 de afinidad, 4 de exploración y 2 recientes, en posiciones fijas', function (): void {
    catalog($this->liked, 30);
    catalog($this->other, 12);
    catalog($this->fresh, 6, daysAgo: 0.1);

    $items = forYouItems($this->member);

    expect($items)->toHaveCount(20)
        ->and(collect($items)->countBy(fn (ForYouItem $item): string => $item->group)->all())->toBe(['affinity' => 14, 'explore' => 4, 'recent' => 2])
        ->and(collect($items)->filter(fn (ForYouItem $item): bool => $item->group === 'explore')->keys()->all())->toBe([4, 9, 14, 18])
        ->and(collect($items)->filter(fn (ForYouItem $item): bool => $item->group === 'recent')->keys()->all())->toBe([7, 16]);
});

test('dentro de cada grupo el orden es calidad y frescura', function (): void {
    $oldButHuge = Copypasta::factory()->create(['published_at' => now()->subDays(20), 'score' => 5]);
    $newAndGood = Copypasta::factory()->create(['published_at' => now()->subHours(6), 'score' => 40, 'copies_count' => 20]);
    $middle = Copypasta::factory()->create(['published_at' => now()->subDays(2), 'score' => 40]);
    foreach ([$oldButHuge, $newAndGood, $middle] as $copypasta) {
        $copypasta->tags()->attach($this->liked->id);
    }

    $ids = array_map(fn (ForYouItem $item): string => $item->copypasta->id, forYouItems($this->member));

    expect($ids)->toBe([$newAndGood->id, $middle->id, $oldButHuge->id]);
});

test('nunca salen lo ya votado, copiado o descartado, lo propio, lo oculto, lo borrado ni lo NSFW', function (): void {
    $voted = catalog($this->liked, 1)->first();
    $copied = catalog($this->liked, 1)->first();
    $dismissed = catalog($this->liked, 1)->first();
    $own = catalog($this->liked, 1)->first();
    $own->update(['user_id' => $this->member->id]);
    $hidden = catalog($this->liked, 1, attributes: ['hidden_at' => now()])->first();
    $deleted = catalog($this->liked, 1)->first();
    $deleted->delete();
    $nsfw = catalog($this->liked, 1, attributes: ['is_nsfw' => true])->first();
    $unpublished = catalog($this->liked, 1, attributes: ['published_at' => null])->first();
    $shown = catalog($this->liked, 3);

    app(CastVote::class)->handle($this->member, $voted, 1);
    app(RecordCopypastaCopy::class)->handle($copied, 'a', $this->member);
    app(DismissCopypasta::class)->handle($this->member, $dismissed);

    $ids = collect(forYouItems($this->member))->map(fn (ForYouItem $item): string => $item->copypasta->id);

    foreach ([$voted, $copied, $dismissed, $own, $hidden, $deleted, $nsfw, $unpublished] as $excluded) {
        expect($ids->contains($excluded->id))->toBeFalse();
    }

    expect($ids->sort()->values()->all())->toBe($shown->pluck('id')->sort()->values()->all());
});

test('el NSFW sale para quien lo tiene activado y confirmó la edad', function (): void {
    $nsfw = catalog($this->liked, 1, attributes: ['is_nsfw' => true])->first();
    $this->member->forceFill(['show_nsfw' => true, 'nsfw_confirmed_at' => now()])->save();

    expect(collect(forYouItems($this->member))->pluck('copypasta.id')->all())->toBe([$nsfw->id]);
});

test('una etiqueta con afinidad efectiva por debajo de −5 saca los copy-pastas que la llevan de los tres grupos y del relleno', function (): void {
    $disliked = Tag::factory()->create();
    DB::table('user_tag_affinities')->insert(['user_id' => $this->member->id, 'tag_id' => $disliked->id, 'score' => -5.5, 'updated_at' => now()]);

    $clean = catalog($this->liked, 2);
    $dislikedOnly = catalog($disliked, 6);
    $dislikedWithLiked = catalog($this->liked, 3);
    $dislikedWithLiked->each(fn (Copypasta $copypasta) => $copypasta->tags()->attach($disliked->id));
    $dislikedFresh = catalog($disliked, 3, daysAgo: 0.1);

    $ids = collect(forYouItems($this->member))->map(fn (ForYouItem $item): string => $item->copypasta->id)->all();

    expect($ids)->toHaveCount(2)
        ->and($ids)->toEqualCanonicalizing($clean->pluck('id')->all());
    foreach ([$dislikedOnly, $dislikedWithLiked, $dislikedFresh] as $group) {
        expect(array_intersect($ids, $group->pluck('id')->all()))->toBe([]);
    }
});

test('con −5 justos la etiqueta todavía no se excluye', function (): void {
    $borderline = Tag::factory()->create();
    DB::table('user_tag_affinities')->insert(['user_id' => $this->member->id, 'tag_id' => $borderline->id, 'score' => -5.0, 'updated_at' => now()]);
    catalog($borderline, 1);

    expect(forYouItems($this->member))->toHaveCount(1);
});

test('el decaimiento puede devolver una etiqueta excluida a la exploración', function (): void {
    $disliked = Tag::factory()->create();
    DB::table('user_tag_affinities')->insert(['user_id' => $this->member->id, 'tag_id' => $disliked->id, 'score' => -8.0, 'updated_at' => now()->subDays(30)]);
    catalog($disliked, 1);

    // −8 × 0,5 = −4: ya no está por debajo de −5.
    expect(forYouItems($this->member))->toHaveCount(1);
});

test('si un grupo no llega a su cuota, se rellena con los otros', function (): void {
    catalog($this->liked, 40);

    $items = forYouItems($this->member);

    expect($items)->toHaveCount(20)
        ->and(collect($items)->pluck('group')->unique()->all())->toBe(['affinity']);
});

test('Para ti nunca sale vacío: con todo votado, copiado o visto sigue enseñando contenido', function (): void {
    $all = catalog($this->liked, 4);
    foreach ($all as $copypasta) {
        app(CastVote::class)->handle($this->member, $copypasta, 1);
    }

    $items = forYouItems($this->member);

    expect($items)->toHaveCount(4)
        ->and(collect($items)->pluck('group')->unique()->all())->toBe(['fallback']);
});

test('dos páginas seguidas no repiten elementos y lo mostrado no vuelve a salir al actualizar', function (): void {
    catalog($this->liked, 60);
    catalog($this->other, 25);

    $first = collect(forYouItems($this->member, 20))->map(fn (ForYouItem $item): string => $item->copypasta->id);
    $both = collect(forYouItems($this->member, 40))->map(fn (ForYouItem $item): string => $item->copypasta->id);

    expect($both->take(20)->all())->toBe($first->all())
        ->and($both->unique())->toHaveCount(40);

    app(ForYouCache::class)->markSeen($this->member, $first->all());
    $refreshed = collect(forYouItems($this->member, 20, refresh: true))->map(fn (ForYouItem $item): string => $item->copypasta->id);

    expect($refreshed)->toHaveCount(20)
        ->and($refreshed->intersect($first))->toHaveCount(0);
});

test('los 500 últimos mostrados se guardan por usuario, con caducidad de 7 días', function (): void {
    $cache = app(ForYouCache::class);
    $ids = array_map(fn (int $number): string => sprintf('%026d', $number), range(1, 520));

    $cache->markSeen($this->member, $ids);

    expect($cache->seen($this->member))->toHaveCount(500)
        ->and($cache->seen($this->member)[0])->toBe($ids[20])
        ->and($cache->seen(User::factory()->create()))->toBe([]);

    $this->travel(8)->days();
    expect($cache->seen($this->member))->toBe([]);
});

test('la lista se cachea 30 minutos y "Actualizar" la regenera', function (): void {
    catalog($this->liked, 3);
    expect(forYouItems($this->member))->toHaveCount(3);

    catalog($this->liked, 2);
    expect(forYouItems($this->member))->toHaveCount(3);

    $this->travel(31)->minutes();
    expect(forYouItems($this->member))->toHaveCount(5);

    catalog($this->liked, 2);
    expect(forYouItems($this->member))->toHaveCount(5)
        ->and(forYouItems($this->member, refresh: true))->toHaveCount(7);
});

test('cambiar las favoritas invalida la lista cacheada', function (): void {
    catalog($this->liked, 2);
    catalog($this->other, 3);
    $before = collect(forYouItems($this->member))->pluck('group', 'copypasta.id');
    expect($before->filter(fn (string $group): bool => $group === 'affinity'))->toHaveCount(2);

    $extra = Tag::factory()->count(2)->create();
    app(UpdateFavoriteTags::class)->handle($this->member, [$this->liked->id, $this->other->id, $extra[0]->id]);

    $after = collect(forYouItems($this->member))->pluck('group', 'copypasta.id');
    expect($after->filter(fn (string $group): bool => $group === 'affinity'))->toHaveCount(5);
});

test('lo oculto, borrado o descartado desde que se hizo la lista deja de salir sin regenerarla', function (): void {
    $copypastas = catalog($this->liked, 3);
    forYouItems($this->member);

    $copypastas[0]->forceFill(['hidden_at' => now()])->save();
    $copypastas[1]->delete();
    DB::table('copypasta_dismissals')->insert(['user_id' => $this->member->id, 'copypasta_id' => $copypastas[2]->id, 'created_at' => now()]);

    // Nada de lo cacheado sigue siendo visible: se reconstruye una vez y sigue sin salir vacío si hay contenido.
    $extra = catalog($this->liked, 1)->first();

    expect(collect(forYouItems($this->member))->pluck('copypasta.id')->all())->toBe([$extra->id]);
});

test('la explicación es la etiqueta de mayor afinidad, "descubre" o "recién publicado"', function (): void {
    $stronger = Tag::factory()->create(['name' => 'oficina']);
    DB::table('user_tag_affinities')->insert(['user_id' => $this->member->id, 'tag_id' => $stronger->id, 'score' => 20.0, 'updated_at' => now()]);
    $liked = catalog($this->liked, 1)->first();
    $liked->tags()->attach($stronger->id);
    catalog($this->other, 1);
    catalog($this->fresh, 1, daysAgo: 0.1);

    $byGroup = collect(forYouItems($this->member))->keyBy('group');

    expect($byGroup['affinity']->explanation)->toBe('liked')
        ->and($byGroup['affinity']->tag->name)->toBe('oficina')
        ->and($byGroup['explore']->explanation)->toBe('discover')
        ->and($byGroup['recent']->explanation)->toBe('recent');
});

test('un miembro con etiquetas favoritas o con 5 señales tiene Para ti por defecto, y con menos no', function (): void {
    $affinities = app(TagAffinities::class);
    $quiet = User::factory()->create();
    $withFavorites = User::factory()->create();
    favoriteTagsOf($withFavorites, $this->liked);

    expect($affinities->defaultsToForYou($quiet))->toBeFalse()
        ->and($affinities->defaultsToForYou($withFavorites))->toBeTrue();

    foreach (catalog($this->liked, 4) as $copypasta) {
        app(CastVote::class)->handle($quiet, $copypasta, 1);
    }
    expect($affinities->defaultsToForYou($quiet))->toBeFalse();

    app(RecordCopypastaCopy::class)->handle(catalog($this->liked, 1)->first(), 'k', $quiet);
    expect($affinities->defaultsToForYou($quiet))->toBeTrue();
});

test('el feed enseña Para ti por defecto a quien cumple y el aleatorio a quien no', function (): void {
    catalog($this->liked, 2);
    $quiet = User::factory()->create();

    Livewire::actingAs($this->member)->test(Feed::class)
        ->assertSee(__('public.feed.for_you_refresh'))
        ->assertSeeHtml('data-test="tab-for-you"');

    Livewire::actingAs($quiet)->test(Feed::class)
        ->assertDontSee(__('public.feed.for_you_refresh'))
        ->assertSeeHtml('data-test="tab-for-you"')
        ->assertSee(__('public.feed.shuffle'));
});

test('los anónimos no ven la pestaña Para ti, ni aunque la pidan en la URL', function (): void {
    catalog($this->liked, 2);

    Livewire::test(Feed::class)
        ->assertDontSeeHtml('data-test="tab-for-you"')
        ->set('sort', Feed::FOR_YOU)
        ->assertDontSee(__('public.feed.for_you_refresh'));
});

test('al cambiar a Para ti se limpian de la URL el buscador y las etiquetas', function (): void {
    catalog($this->liked, 2);

    Livewire::actingAs($this->member)->test(Feed::class)
        ->set('sort', 'new')
        ->set('search', 'router')
        ->set('tags', 'humor')
        ->set('sort', Feed::FOR_YOU)
        ->assertSet('search', '')
        ->assertSet('tags', '');
});

test('abrir un enlace con ?sort=para_ti&q=… tampoco conserva los filtros', function (): void {
    Livewire::withQueryParams(['sort' => Feed::FOR_YOU, 'q' => 'router', 'tags' => 'humor'])
        ->actingAs($this->member)
        ->test(Feed::class)
        ->assertSet('search', '')
        ->assertSet('tags', '');
});

test('Para ti muestra la explicación en cada tarjeta y marca lo mostrado como visto', function (): void {
    catalog($this->liked, 2);

    Livewire::actingAs($this->member)->test(Feed::class)
        ->assertSee(__('ui.card.reason'))
        ->assertSee('#humor');

    expect(app(ForYouCache::class)->seen($this->member))->toHaveCount(2);
});

test('el botón Actualizar regenera la lista y deja fuera lo ya mostrado', function (): void {
    $first = catalog($this->liked, 2);

    $component = Livewire::actingAs($this->member)->test(Feed::class);
    $later = catalog($this->liked, 2);

    $component->call('refreshForYou')->assertDispatched('ui-toast');

    foreach ($later as $copypasta) {
        $component->assertSee($copypasta->title);
    }
    foreach ($first as $copypasta) {
        $component->assertDontSee($copypasta->title);
    }
});

test('copiar, votar y guardar desde Para ti llevan source, posición y grupo en el contexto del evento', function (): void {
    $copypasta = catalog($this->liked, 1)->first();
    $this->actingAs($this->member);
    $context = ['source' => 'para_ti', 'position' => 7, 'group' => 'recent'];

    $this->postJson(route('copypastas.copy', $copypasta), $context)->assertNoContent();
    $this->postJson(route('copypastas.vote', $copypasta), ['value' => 1, ...$context])->assertOk();
    $this->postJson(route('copypastas.favorite', $copypasta), $context)->assertOk();

    expect(TrackedEvent::query()->where('user_id', $this->member->id)->pluck('context')->all())->each->toMatchArray($context);
});

test('el grupo del contexto solo admite los valores conocidos', function (): void {
    $copypasta = catalog($this->liked, 1)->first();
    $this->actingAs($this->member);

    $this->postJson(route('copypastas.vote', $copypasta), ['value' => 1, 'source' => 'para_ti', 'group' => 'inventado'])->assertOk();

    expect(TrackedEvent::query()->where('user_id', $this->member->id)->sole()->context)->not->toHaveKey('group');
});

test('el feed de Para ti es la misma consulta para cuentas distintas: cada una ve su lista', function (): void {
    catalog($this->liked, 3);
    $other = User::factory()->create();
    favoriteTagsOf($other, $this->other);
    catalog($this->other, 2);

    $mine = collect(forYouItems($this->member))->pluck('group', 'copypasta.id');
    $theirs = collect(forYouItems($other))->pluck('group', 'copypasta.id');

    expect($mine->filter(fn (string $group): bool => $group === 'affinity'))->toHaveCount(3)
        ->and($theirs->filter(fn (string $group): bool => $group === 'affinity'))->toHaveCount(2);
    expect(Cache::has('for-you:candidates:'.$this->member->id))->toBeTrue()
        ->and(Cache::has('for-you:candidates:'.$other->id))->toBeTrue();
});
