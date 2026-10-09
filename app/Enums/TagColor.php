<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TagColor: string implements HasLabel
{
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Green = 'green';
    case Teal = 'teal';
    case Blue = 'blue';
    case Indigo = 'indigo';
    case Purple = 'purple';
    case Pink = 'pink';
    case Gray = 'gray';

    public function getLabel(): string
    {
        return __('admin.tag_colors.'.$this->value);
    }

    /**
     * The design system has 5 tag tones (t1 mint, t2 orange, t3 violet, t4 yellow, t5 pink); staff still pick from
     * this richer Filament palette, so each color maps onto the closest tone for the public tag chip.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Green, self::Teal => 't1',
            self::Orange, self::Amber => 't2',
            self::Purple, self::Indigo, self::Blue => 't3',
            self::Red, self::Gray => 't4',
            self::Pink => 't5',
        };
    }
}
