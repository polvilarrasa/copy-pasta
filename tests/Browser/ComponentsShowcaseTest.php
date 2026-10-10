<?php

declare(strict_types=1);

test('the modal of the components showcase opens from its trigger', function (): void {
    visit('/_componentes')
        ->press(__('ui.showcase.open_modal'))
        ->assertVisible('#demo-modal-light-title')
        ->assertSee(__('ui.showcase.modal_title'));
});

test('escape closes the components showcase modal, which traps focus while it is open', function (): void {
    $page = visit('/_componentes')
        ->press(__('ui.showcase.open_modal'))
        ->assertVisible('#demo-modal-light-title');

    $page->assertScript("document.activeElement.closest('[role=\"dialog\"]') !== null");

    $page->keys('#demo-modal-light-title', 'Escape')
        ->assertMissing('#demo-modal-light-title');
});

test('arrow keys open and move through the components showcase dropdown, and escape returns focus to its trigger', function (): void {
    $trigger = '#demo-dropdown-light button[aria-haspopup="menu"]';
    $menu = '#demo-dropdown-light [role="menu"]';

    $page = visit('/_componentes');

    $page->keys($trigger, 'ArrowDown')
        ->assertAriaAttribute($trigger, 'expanded', 'true');

    $page->assertScript("document.activeElement.textContent.trim() === '".__('ui.showcase.menu_profile')."'");

    $page->keys($menu, 'ArrowDown')
        ->assertScript("document.activeElement.textContent.trim() === '".__('ui.showcase.menu_settings')."'");

    $page->keys($menu, 'Escape')
        ->assertAriaAttribute($trigger, 'expanded', 'false');

    $page->assertScript("document.activeElement === document.querySelector('{$trigger}')");
});

test('arrow keys move selection between the components showcase tabs', function (): void {
    $page = visit('/_componentes')
        ->assertAriaAttribute('#tabs-light-tab-top', 'selected', 'true')
        ->assertVisible('#tabs-light-panel-top')
        ->assertMissing('#tabs-light-panel-new');

    $page->keys('#tabs-light-tab-top', 'ArrowRight')
        ->assertAriaAttribute('#tabs-light-tab-new', 'selected', 'true')
        ->assertVisible('#tabs-light-panel-new')
        ->assertMissing('#tabs-light-panel-top');

    $page->keys('#tabs-light-tab-new', 'ArrowLeft')
        ->assertAriaAttribute('#tabs-light-tab-top', 'selected', 'true');
});
