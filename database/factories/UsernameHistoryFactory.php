<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UsernameHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsernameHistory>
 */
class UsernameHistoryFactory extends Factory
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
            'username' => fake()->unique()->userName(),
            'changed_at' => now(),
        ];
    }
}
