<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

test('la caché de Redis guarda y recupera un valor', function (): void {
    $key = 'test:'.Str::random(12);
    $store = Cache::store('redis');

    $store->put($key, ['copias' => 3], 60);

    expect($store->get($key))->toBe(['copias' => 3]);

    $store->forget($key);
});
