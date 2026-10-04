<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property Role $role
 * @property bool $show_nsfw
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $banned_at
 * @property string|null $ban_reason
 * @property bool $must_change_password
 * @property Carbon|null $deleted_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['username', 'email', 'password', 'show_nsfw'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Impersonate, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Any password change clears the temporary-password flag, unless the caller sets the flag in the same save
     * (as admin-created accounts do). Covers the settings page, the reset flow and the Filament profile.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty('password') && ! $user->isDirty('must_change_password')) {
                $user->must_change_password = false;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'show_nsfw' => 'boolean',
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'must_change_password' => 'boolean',
            'is_owner' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Only admins impersonate, and only members who are not staff and not banned can be impersonated.
     */
    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->isStaff() && ! $this->isBanned();
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    /**
     * The name shown next to a copy-pasta. An anonymized account shows a neutral label instead of its old username.
     */
    public function displayName(): string
    {
        return $this->isAnonymized() ? __('public.card.deleted_user') : $this->username;
    }

    /**
     * @return HasMany<Copypasta, $this>
     */
    public function copypastas(): HasMany
    {
        return $this->hasMany(Copypasta::class);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reportsSent(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * Moderation log entries whose subject is this user (bans, role changes, impersonations, ...).
     *
     * @return HasMany<ModerationAction, $this>
     */
    public function subjectActions(): HasMany
    {
        return $this->hasMany(ModerationAction::class, 'subject_id')
            ->where('subject_type', self::class);
    }

    /**
     * @return HasMany<Folder, $this>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Admins whose address is verified, the only ones who receive staff alerts.
     */
    /**
     * @param  Builder<User>  $query
     */
    public function scopeVerifiedAdmins(Builder $query): void
    {
        $query->where('role', Role::Admin)->whereNotNull('email_verified_at');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->username, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Moderators and admins can access staff-only areas.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, [Role::Moderator, Role::Admin], true);
    }

    /**
     * Only admins can manage users, roles and bans.
     */
    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Whether the user is currently banned.
     */
    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * Name shown in the Filament user menu.
     */
    public function getFilamentName(): string
    {
        return $this->username;
    }

    /**
     * Staff can use the admin panel and verified members can use the user panel; banned users use neither.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isBanned()) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'app' => $this->hasVerifiedEmail(),
            default => false,
        };
    }
}
