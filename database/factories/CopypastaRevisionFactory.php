<?php

namespace Database\Factories;

use App\Models\Copypasta;
use App\Models\CopypastaRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CopypastaRevision>
 */
class CopypastaRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'copypasta_id' => Copypasta::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
        ];
    }
}
