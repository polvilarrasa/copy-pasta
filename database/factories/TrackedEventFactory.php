<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedEvent>
 */
class TrackedEventFactory extends Factory
{
    /**
     * Define the model's default state: an anonymous event on a copy-pasta, created now.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(EventType::cases()),
            'user_id' => null,
            'visitor_hash' => hash('sha256', fake()->uuid()),
            'copypasta_id' => Copypasta::factory(),
            'context' => [],
            'created_at' => now(),
        ];
    }

    public function ofType(EventType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }

    public function forCopypasta(Copypasta $copypasta): static
    {
        return $this->state(fn (array $attributes) => ['copypasta_id' => $copypasta->getKey()]);
    }

    /**
     * A member's event: it carries the user and no visitor hash.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $user->getKey(), 'visitor_hash' => null]);
    }

    public function at(CarbonInterface $createdAt): static
    {
        return $this->state(fn (array $attributes) => ['created_at' => $createdAt]);
    }
}
