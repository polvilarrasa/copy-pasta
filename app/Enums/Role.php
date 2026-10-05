<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Trusted = 'trusted';
    case Moderator = 'moderator';
    case Admin = 'admin';
}
