<?php

declare(strict_types=1);

use App\Mail\CopypastaHiddenMail;
use App\Models\Copypasta;

test('el email de ocultación indica cómo recurrir', function (): void {
    $copypasta = Copypasta::factory()->hidden('Spam repetido')->create();

    $html = (new CopypastaHiddenMail($copypasta))->render();

    expect($html)->toContain('Si crees que es un error')
        ->and($html)->toContain((string) config('mail.from.address'));
});
