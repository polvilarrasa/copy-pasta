<?php

declare(strict_types=1);

use App\Actions\ChangeUserTitle;
use App\Actions\RevokeAchievement;
use App\Enums\Achievement;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function memberWith(Achievement ...$achievements): User
{
    $member = User::factory()->create();

    foreach ($achievements as $achievement) {
        UserAchievement::factory()->for($member)->ofAchievement($achievement)->create();
    }

    return $member;
}

test('elige el título de un logro conseguido, lo guarda en el usuario y registra title_change', function (): void {
    $member = memberWith(Achievement::PasteFactory);

    app(ChangeUserTitle::class)->handle($member, 'paste_factory');

    expect($member->refresh()->title_key)->toBe('paste_factory')
        ->and($member->titleLabel())->toBe('Maestro del Ctrl+V')
        ->and(TrackedEvent::query()->where('type', EventType::TitleChange)->where('user_id', $member->id)->sole()->context)
        ->toBe(['title' => 'paste_factory']);
});

test('rechaza el título de un logro que no se tiene', function (): void {
    $member = memberWith(Achievement::FirstPaste);

    app(ChangeUserTitle::class)->handle($member, 'legend');
})->throws(ValidationException::class);

test('rechaza una clave de título inventada', function (): void {
    app(ChangeUserTitle::class)->handle(memberWith(Achievement::FirstPaste), 'inventado');
})->throws(ValidationException::class);

test('rechaza el título de un logro revocado', function (): void {
    $member = User::factory()->create();
    UserAchievement::factory()->for($member)->ofAchievement(Achievement::Legend)->revoked()->create();

    app(ChangeUserTitle::class)->handle($member, 'legend');
})->throws(ValidationException::class);

test('los logros sin título no ofrecen ninguno', function (): void {
    expect(Achievement::FirstApplause->titleKey())->toBeNull();

    app(ChangeUserTitle::class)->handle(memberWith(Achievement::FirstApplause), 'first_applause');
})->throws(ValidationException::class);

test('quitar el título lo deja en ninguno y repetirlo no registra otro evento', function (): void {
    $member = memberWith(Achievement::Viral);
    $member->forceFill(['title_key' => 'viral'])->save();

    app(ChangeUserTitle::class)->handle($member, null);
    app(ChangeUserTitle::class)->handle($member, null);

    expect($member->refresh()->title_key)->toBeNull()
        ->and($member->titleLabel())->toBeNull()
        ->and(TrackedEvent::query()->where('type', EventType::TitleChange)->count())->toBe(1);
});

test('un logro revocado no muestra su título', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = memberWith(Achievement::Viral);
    $member->forceFill(['title_key' => 'viral'])->save();
    expect($member->titleLabel())->toBe('Viral');

    app(RevokeAchievement::class)->handle($admin, $member->achievements()->sole(), 'Cuenta de granja de copias');

    expect($member->refresh()->title_key)->toBeNull()
        ->and($member->titleLabel())->toBeNull();
});

test('revocar otro logro no quita el título activo', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = memberWith(Achievement::Viral, Achievement::Collector);
    $member->forceFill(['title_key' => 'viral'])->save();

    app(RevokeAchievement::class)->handle(
        $admin,
        $member->achievements()->where('achievement_key', 'collector')->sole(),
        'Motivo',
    );

    expect($member->refresh()->title_key)->toBe('viral');
});

test('banear o anonimizar oculta el título en pantalla', function (): void {
    $banned = User::factory()->banned()->create(['title_key' => 'viral']);
    $anonymized = User::factory()->create(['title_key' => 'viral', 'anonymized_at' => now()]);

    expect($banned->titleLabel())->toBeNull()
        ->and($anonymized->titleLabel())->toBeNull();
});

test('la página de ajustes ofrece solo los títulos conseguidos y guarda la elección', function (): void {
    $member = memberWith(Achievement::Viral, Achievement::FirstApplause);

    Livewire::actingAs($member)
        ->test('pages::settings.title')
        ->assertSee('Viral')
        ->assertDontSee('Leyenda del foro')
        ->set('titleKey', 'viral')
        ->assertHasNoErrors();

    expect($member->refresh()->title_key)->toBe('viral');

    Livewire::actingAs($member)->test('pages::settings.title')->set('titleKey', '');

    expect($member->refresh()->title_key)->toBeNull();
});

test('la página de ajustes avisa a quien no tiene ningún título', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('title.edit'))
        ->assertOk()
        ->assertSee(__('achievements.settings.empty'));
});

test('la página de ajustes exige sesión y email verificado', function (): void {
    $this->get(route('title.edit'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->unverified()->create())->get(route('title.edit'))->assertRedirect();
});

test('el título aparece junto al nombre en la tarjeta, el detalle y el perfil', function (): void {
    $author = User::factory()->create(['username' => 'paco_nocturno', 'title_key' => 'paste_factory']);
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['title' => 'Aviso del táper']);

    $this->get(route('home'))->assertSee('Maestro del Ctrl+V');
    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))->assertSee('Maestro del Ctrl+V');
    $this->get(route('profile.show', 'paco_nocturno'))->assertSee('Maestro del Ctrl+V');
});

test('mostrar el título no añade consultas por tarjeta', function (): void {
    Copypasta::factory()->for(User::factory()->create(), 'user')->count(3)->create();

    $countFeedQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('home'))->assertOk();

        return count(DB::getQueryLog());
    };

    $countFeedQueries(); // warm-up: the first request pays for cold caches
    $without = $countFeedQueries();

    User::query()->update(['title_key' => 'paste_factory']);

    expect($countFeedQueries())->toBe($without);
});
