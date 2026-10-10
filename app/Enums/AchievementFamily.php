<?php

declare(strict_types=1);

namespace App\Enums;

enum AchievementFamily: string
{
    case Creator = 'creator';
    case Popularity = 'popularity';
    case Copies = 'copies';
    case Trending = 'trending';
    case Collector = 'collector';
    case Guardian = 'guardian';
    case Voter = 'voter';
    case Diffusion = 'diffusion';
    case Veteran = 'veteran';
    case Secret = 'secret';
}
