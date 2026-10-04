<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
