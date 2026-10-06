<?php

declare(strict_types=1);

test('the modal of the components showcase opens from its trigger', function (): void {
    visit('/_componentes')
        ->press(__('ui.showcase.open_modal'))
        ->assertVisible('#demo-modal-light-title')
        ->assertSee(__('ui.showcase.modal_title'));
});
