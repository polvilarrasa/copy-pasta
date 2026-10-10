<?php

declare(strict_types=1);

namespace App\Support;

final readonly class ForYouPage
{
    /**
     * @param  list<ForYouItem>  $items
     */
    public function __construct(public array $items, public bool $hasMore) {}
}
