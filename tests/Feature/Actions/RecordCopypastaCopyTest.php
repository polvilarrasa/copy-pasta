<?php

declare(strict_types=1);

use App\Actions\RecordCopypastaCopy;
use App\Models\Copypasta;

test('cuenta cada visitante por separado dentro de la misma hora', function (): void {
    $copypasta = Copypasta::factory()->create(['copies_count' => 0]);
    $action = app(RecordCopypastaCopy::class);

    expect($action->handle($copypasta, '10.0.0.1'))->toBeTrue()
        ->and($action->handle($copypasta, '10.0.0.1'))->toBeFalse()
        ->and($action->handle($copypasta, '10.0.0.2'))->toBeTrue()
        ->and($copypasta->refresh()->copies_count)->toBe(2);
});
