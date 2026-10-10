<?php

declare(strict_types=1);

use App\Enums\ReportReason;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\Report;
use App\Models\User;

test('copiar desde el feed registra la copia y confirma en pantalla', function (): void {
    $copypasta = Copypasta::factory()->create(['title' => 'Copia de prueba del navegador']);

    $page = visit('/')->assertSee($copypasta->title);

    // Headless Chromium denies clipboard writes without a granted permission; the rest of the flow stays real.
    $page->script('navigator.clipboard.writeText = async () => {}');

    $page->press('Copiar')
        ->waitForText(__('public.copy.copied'))
        ->assertSee(__('public.copy.copied'));

    expect($copypasta->refresh()->copies_count)->toBe(1);
});

test('votar a favor desde el feed suma un punto y deja marcado el voto', function (): void {
    $copypasta = Copypasta::factory()->create();
    $member = User::factory()->create();

    signInInBrowser($member);

    visit('/')
        ->assertSee($copypasta->title)
        ->click('button[aria-label="'.__('ui.card.vote_up').'"]')
        ->assertAttribute('button[aria-label="'.__('ui.card.vote_up').'"]', 'aria-pressed', 'true');

    expect($copypasta->refresh()->score)->toBe(1);
});

test('guardar en una carpeta desde el feed la añade a esa carpeta', function (): void {
    $copypasta = Copypasta::factory()->create();
    $member = User::factory()->create();
    $folder = Folder::factory()->for($member)->create(['name' => 'Recetas de prueba']);

    signInInBrowser($member);

    visit('/')
        ->assertSee($copypasta->title)
        ->click('article button[aria-haspopup="menu"]')
        ->click(__('public.folders.button'))
        ->check('Recetas de prueba')
        ->press('@folders-save-button')
        ->waitForText(__('public.folders.saved'));

    expect($copypasta->folders()->whereKey($folder->getKey())->exists())->toBeTrue();
});

test('reportar un copy-pasta envía el motivo y confirma el envío', function (): void {
    $copypasta = Copypasta::factory()->create();
    $reporter = User::factory()->established()->create();

    signInInBrowser($reporter);

    visit('/')
        ->assertSee($copypasta->title)
        ->click('article button[aria-haspopup="menu"]')
        ->click(__('public.report.button'))
        ->check(__('moderation.reasons.'.ReportReason::Spam->value))
        ->press(__('public.report.submit'))
        ->waitForText(__('public.report.sent'));

    expect(Report::query()->where('copypasta_id', $copypasta->getKey())->where('reporter_id', $reporter->getKey())->exists())
        ->toBeTrue();
});
