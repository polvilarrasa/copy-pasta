<?php

declare(strict_types=1);

test('renders the component showcase in light and dark mode', function (): void {
    $this->get(route('components.showcase'))
        ->assertOk()
        ->assertSee('data-theme="light"', false)
        ->assertSee('data-theme="dark"', false);
});

test('does not render any Flux component on the showcase', function (): void {
    $html = $this->get(route('components.showcase'))->getContent();

    expect($html)->not->toContain('<flux:');
});
