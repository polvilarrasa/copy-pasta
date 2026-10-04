<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
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
            'reporter_id' => User::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'details' => null,
            'status' => ReportStatus::Pending,
        ];
    }

    /**
     * Indicate that the report has been resolved by a moderator.
     */
    public function resolved(ReportStatus $status = ReportStatus::Accepted): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'resolved_by_id' => User::factory()->moderator(),
            'resolved_at' => now(),
        ]);
    }
}
