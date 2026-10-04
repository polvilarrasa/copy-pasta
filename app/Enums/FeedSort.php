<?php

declare(strict_types=1);

namespace App\Enums;

enum FeedSort: string
{
    case Random = 'random';
    case TopWeek = 'top_week';
    case TopMonth = 'top_month';
    case TopAll = 'top_all';
    case Newest = 'new';
}
