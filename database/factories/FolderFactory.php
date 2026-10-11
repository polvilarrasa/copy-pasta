<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'is_default' => false,
            'is_public' => false,
            'position' => 0,
        ];
    }

    /**
     * Indicate that the folder is the user's protected default folder.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Favoritos',
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the folder is shared at its public address.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'public_id' => (string) Str::ulid(),
        ]);
    }
}
