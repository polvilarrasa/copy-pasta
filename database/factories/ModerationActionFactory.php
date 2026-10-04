<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<ModerationAction>
 */
class ModerationActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->moderator(),
            'action' => ModerationActionType::Hide,
            'subject_type' => Copypasta::class,
            'subject_id' => (string) Str::ulid(),
            'reason' => fake()->sentence(),
            'meta' => null,
        ];
    }

    /**
     * Point the action at the given model.
     */
    public function forSubject(Model $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
        ]);
    }
}
