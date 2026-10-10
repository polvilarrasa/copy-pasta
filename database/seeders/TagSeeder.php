<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const NAMES = [
        'Humor', 'Absurdo', 'Política', 'Internet', 'Anime',
        'Videojuegos', 'Música', 'Cine y series', 'Deportes', 'Historia',
        'Ciencia', 'Tecnología', 'Filosofía', 'Memes', 'Random',
    ];

    /**
     * @var array<int, string>
     */
    private const COLORS = ['t1', 't2', 't3', 't4', 't5'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::NAMES as $index => $name) {
            Tag::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'color' => self::COLORS[$index % count(self::COLORS)],
                    'is_active' => true,
                ],
            );
        }
    }
}
