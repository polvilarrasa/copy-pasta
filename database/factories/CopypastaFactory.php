<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Copypasta>
 */
class CopypastaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => fake()->paragraphs(3, true),
            'is_nsfw' => false,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the copy-pasta is marked as NSFW.
     */
    public function nsfw(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_nsfw' => true,
        ]);
    }

    /**
     * Indicate that the copy-pasta is hidden by moderation.
     */
    public function hidden(string $reason = 'Pendiente de revisión'): static
    {
        return $this->state(fn (array $attributes) => [
            'hidden_at' => now(),
            'hidden_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the copy-pasta was published the given number of days ago.
     */
    public function publishedDaysAgo(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now()->subDays($days),
        ]);
    }

    /**
     * Indicate that the copy-pasta is still a draft.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }
}
