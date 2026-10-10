<?php

namespace Database\Factories;

use App\Models\Copypasta;
use App\Models\FeaturedCopypasta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeaturedCopypasta>
 */
class FeaturedCopypastaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->toDateString(),
            'copypasta_id' => Copypasta::factory(),
            'picked_by_id' => null,
        ];
    }
}
