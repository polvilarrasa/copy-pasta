<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_public
 * @property string|null $public_id
 * @property Carbon|null $public_locked_at
 * @property string|null $public_lock_reason
 * @property bool $is_default
 * @property int $position
 */
#[Fillable(['user_id', 'name', 'description', 'is_default', 'position'])]
class Folder extends Model
{
    public const DEFAULT_NAME = 'Favoritos';

    public const MAX_PER_USER = 50;

    public const MAX_DESCRIPTION_LENGTH = 280;

    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_public' => 'boolean',
            'public_locked_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * Returns the user's protected default folder, creating it when the user does not have one yet.
     */
    public static function ensureDefaultFor(User $user): self
    {
        return self::query()->firstOrCreate(
            ['user_id' => $user->getKey(), 'is_default' => true],
            ['name' => self::DEFAULT_NAME, 'position' => 0],
        );
    }

    /**
     * @param  Builder<Folder>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('is_public', true);
    }

    /**
     * Whether the staff made the folder private and its owner cannot make it public again.
     */
    public function isPublicLocked(): bool
    {
        return $this->public_locked_at !== null;
    }

    /**
     * The address of the public page, which only answers while the folder is public.
     */
    public function publicUrl(): ?string
    {
        return $this->public_id === null ? null : route('folders.public', $this->public_id);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Copypasta, $this>
     */
    public function copypastas(): BelongsToMany
    {
        return $this->belongsToMany(Copypasta::class)->withPivot('created_at');
    }
}
