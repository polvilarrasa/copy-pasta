<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Achievement;
use Database\Factories\UserAchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $achievement_key
 * @property Carbon $unlocked_at
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by_id
 * @property string|null $revoke_reason
 */
#[Fillable(['user_id', 'achievement_key', 'unlocked_at', 'revoked_at', 'revoked_by_id', 'revoke_reason'])]
class UserAchievement extends Model
{
    /** @use HasFactory<UserAchievementFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * The registry entry, or null for a key that no longer exists in code.
     */
    public function achievement(): ?Achievement
    {
        return Achievement::tryFrom($this->achievement_key);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
