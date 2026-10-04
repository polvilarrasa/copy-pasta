<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case HateOrHarassment = 'hate_or_harassment';
    case SexualContentMinors = 'sexual_content_minors';
    case PersonalData = 'personal_data';
    case NsfwUnmarked = 'nsfw_unmarked';
    case Other = 'other';
}
