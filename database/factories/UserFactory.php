<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => Str::of(fake()->unique()->userName())->replace('.', '_')->limit(30, '')->value(),
            'email' => fake()->unique()->safeEmail(),
            'role' => Role::User,
            'show_nsfw' => false,
            'theme' => Theme::System,
            'email_verified_at' => now(),
            'onboarded_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A member who was never offered the welcome screen.
     */
    public function notOnboarded(): static
    {
        return $this->state(fn (array $attributes) => ['onboarded_at' => null]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            // A real, valid secret so tests can generate a real TOTP code, not just the recovery-code fallback.
            'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is a moderator.
     */
    /**
     * A member whose account is older than the 72 hours a new account must wait before reporting.
     */
    public function established(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays(4),
        ]);
    }

    public function trusted(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Trusted,
            'created_at' => now()->subDays(4),
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Moderator,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Admin,
        ]);
    }

    /**
     * Indicate that the user is banned.
     */
    public function banned(string $reason = 'Incumplimiento de las normas de la comunidad'): static
    {
        return $this->state(fn (array $attributes) => [
            'banned_at' => now(),
            'ban_reason' => $reason,
        ]);
    }
}
