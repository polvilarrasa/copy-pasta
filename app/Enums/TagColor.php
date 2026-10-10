<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TagColor: string implements HasLabel
{
    case Mint = 't1';
    case Orange = 't2';
    case Violet = 't3';
    case Yellow = 't4';
    case Pink = 't5';

    public function getLabel(): string
    {
        return __('admin.tag_colors.'.$this->value);
    }
}
