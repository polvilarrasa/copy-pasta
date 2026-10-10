<?php

namespace Database\Factories;

use App\Enums\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAchievement>
 */
class UserAchievementFactory extends Factory
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
            'achievement_key' => Achievement::FirstPaste->value,
            'unlocked_at' => now(),
        ];
    }

    public function ofAchievement(Achievement $achievement): static
    {
        return $this->state(fn (array $attributes) => ['achievement_key' => $achievement->value]);
    }

    public function revoked(string $reason = 'Motivo de prueba', ?User $by = null): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
            'revoked_by_id' => $by?->getKey(),
            'revoke_reason' => $reason,
        ]);
    }
}
