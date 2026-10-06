<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\Theme;
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
 * @property Carbon|null $username_changed_at
 * @property string $share_code
 * @property string $email
 * @property Role $role
 * @property bool $show_nsfw
 * @property Theme $theme
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $banned_at
 * @property string|null $ban_reason
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
            'nsfw_confirmed_at' => 'datetime',
            'is_owner' => 'boolean',
            'password' => 'hashed',
            'username_changed_at' => 'datetime',
            'theme' => Theme::class,
        ];
    }

    /**
     * Every member gets a share code when created, and a username change keeps the previous name in the history.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->share_code)) {
                $user->share_code = self::uniqueShareCode();
            }
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('username')) {
                UsernameHistory::query()->create([
                    'user_id' => $user->getKey(),
                    'username' => (string) $user->getOriginal('username'),
                    'changed_at' => now(),
                ]);
            }
        });
    }

    /**
     * Eight random letters and digits: unrelated to the id and the username, and checked against the stored codes.
     */
    private static function uniqueShareCode(): string
    {
        do {
            $code = Str::random(8);
        } while (self::query()->withTrashed()->where('share_code', $code)->exists());

        return $code;
    }

    /**
     * Only admins impersonate, and only members who are not staff and not banned can be impersonated.
     */
    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Trusted members' reports weigh more and, for the sexual-content-with-minors reason, hide copy-pastas on their own.
     */
    public function isTrusted(): bool
    {
        return $this->role === Role::Trusted;
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->isStaff() && ! $this->isBanned();
    }

    /**
     * Adult content shows for staff, and for members who turned the preference on and confirmed their age.
     */
    public function canSeeNsfw(): bool
    {
        return $this->isStaff() || ($this->show_nsfw && $this->nsfw_confirmed_at !== null);
    }

    /**
     * Identifies the password an invitation was issued for. The invitation stops working once the password changes.
     */
    public function invitationFingerprint(): string
    {
        return sha1((string) $this->password);
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
            'app' => true,
            default => false,
        };
    }
}
