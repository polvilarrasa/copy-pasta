<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;

/**
 * Clicks the button of the open Filament modal with the given label. The row action behind the modal shares the label.
 */
function clickModalButton(string $label): string
{
    $encoded = json_encode($label, JSON_THROW_ON_ERROR);

    return <<<JS
        [...document.querySelectorAll('.fi-modal button')]
            .find((button) => button.textContent.trim() === {$encoded})
            .click();
        JS;
}

/**
 * Sets a field of the open Filament modal and fires the input event Livewire listens to.
 */
function setModalField(string $id, string $value): string
{
    $encodedId = json_encode($id, JSON_THROW_ON_ERROR);
    $encodedValue = json_encode($value, JSON_THROW_ON_ERROR);

    return <<<JS
        const field = document.getElementById({$encodedId});
        field.value = {$encodedValue};
        field.dispatchEvent(new Event('input', { bubbles: true }));
        JS;
}

test('ocultar desde la cola de reportes saca el copy-pasta del feed', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $copypasta = Copypasta::factory()->create(['title' => 'Copia reportada de prueba']);
    Report::factory()->create(['copypasta_id' => $copypasta->getKey()]);

    signInInBrowser($moderator);

    $page = visit('/admin/cola-reportes')
        ->assertSee($copypasta->title)
        ->press(__('admin.actions.hide'))
        ->waitForText(__('admin.actions.hide_heading'));

    $page->script(setModalField('mountedActionSchema0.reason', 'Motivo de prueba del navegador'));
    $page->script(clickModalButton(__('admin.actions.hide_submit')));

    $page->assertMissing('.fi-modal-window');

    expect($copypasta->refresh()->isHidden())->toBeTrue();
});

test('actuar como un miembro muestra el aviso y Volver lo cierra', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create(['username' => 'miembro_de_prueba']);
    $banner = __('moderation.impersonation.banner', ['username' => $member->username]);

    signInInBrowser($admin);

    $page = visit('/admin/usuarios')
        ->assertSee($member->username)
        ->press(__('admin.users.actions.impersonate'));

    $page->script(clickModalButton(__('filament-actions::modal.actions.confirm.label')));

    $page->assertSee($banner)
        ->press(__('moderation.impersonation.leave'))
        ->assertDontSee($banner);
});
