<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Copypasta;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
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
            'copypasta_id' => Copypasta::factory(),
            'value' => fake()->randomElement([1, -1]),
        ];
    }

    /**
     * Indicate that the vote is an upvote.
     */
    public function upvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => 1,
        ]);
    }

    /**
     * Indicate that the vote is a downvote.
     */
    public function downvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => -1,
        ]);
    }
}
